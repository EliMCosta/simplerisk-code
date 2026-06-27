<?php
/**
 * Unit tests for the Encryption Extra core crypto (extras/encryption/includes/crypto.php).
 *
 * AES-256-GCM, versioned ENC1: tokens. enc_encrypt()/enc_decrypt() take the key
 * as an explicit argument and normalize it internally (SHA-256 -> 32 bytes), so
 * these tests are fully hermetic: no global key, no DB, no config required. The
 * design invariant under test is fail-OPEN — on any error (wrong key, tampering,
 * truncation) decrypt returns the value unchanged rather than blanking data.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EncryptionCryptoTest extends TestCase
{
    use LoadsExtras;

    public static function setUpBeforeClass(): void
    {
        self::loadExtra('encryption', 'crypto.php');
    }

    /** A fail-open decrypt must never trigger the shutdown audit-log write. */
    protected function tearDown(): void
    {
        $GLOBALS['enc_encrypt_failures'] = 0;
    }

    public static function roundtripValues(): array
    {
        return [
            'ascii'        => ['hello world'],
            'numeric str'  => ['0'],
            'empty-ish'    => [' '],
            'unicode'      => ['Üñîçødé — 日本語 — 🚀'],
            'long'         => [str_repeat('x', 4096)],
            'json payload' => ['{"a":1,"b":[2,3],"c":"d"}'],
            'special chars'=> ["<>&\"'\\ \n\t"],
        ];
    }

    /**
     * @dataProvider roundtripValues
     */
    public function test_encrypt_decrypt_roundtrips(string $value): void
    {
        $key = 'test-key-' . uniqid();
        $token = enc_encrypt($key, $value);

        self::assertNotSame($value, $token, 'ciphertext must differ from plaintext');
        self::assertStringStartsWith('ENC1:', $token);
        self::assertSame($value, enc_decrypt($key, $token), 'must round-trip to the original plaintext');
    }

    public function test_encryption_is_non_deterministic_but_decrypts_same(): void
    {
        // Random 12-byte nonce -> two encryptions of the same value differ...
        $key = 'k';
        $a = enc_encrypt($key, 'same');
        $b = enc_encrypt($key, 'same');
        self::assertNotSame($a, $b, 'nonce must make ciphertexts differ');
        // ...but both decrypt back to the same plaintext.
        self::assertSame('same', enc_decrypt($key, $a));
        self::assertSame('same', enc_decrypt($key, $b));
    }

    public function test_key_form_is_irrelevant_because_it_is_normalized(): void
    {
        // The stored secret can be hex/base64/raw of any length; enc_encrypt
        // SHA-256s it to 32 bytes internally, so all of these round-trip.
        foreach (['x', str_repeat('a', 64), bin2hex(random_bytes(16)), ''] as $keyMaterial) {
            // An empty key still encrypts (SHA-256 of '' is a valid 32-byte key);
            // this tests normalization, not whether a key SHOULD be empty.
            $token = enc_encrypt($keyMaterial, 'payload');
            self::assertSame('payload', enc_decrypt($keyMaterial, $token));
        }
    }

    public function test_no_double_encrypt(): void
    {
        $token = enc_encrypt('k', 'secret');
        // Encrypting an already-ENC1: value is a no-op (never re-wraps).
        self::assertSame($token, enc_encrypt('k', $token));
    }

    public function test_empty_and_null_pass_through_unchanged(): void
    {
        self::assertNull(enc_encrypt('k', null));
        self::assertSame('', enc_encrypt('k', ''));
        self::assertNull(enc_decrypt('k', null));
        self::assertSame('', enc_decrypt('k', ''));
    }

    public function test_is_encrypted_truth_table(): void
    {
        self::assertTrue(enc_is_encrypted(enc_encrypt('k', 'x')));
        self::assertTrue(enc_is_encrypted('ENC1:anything'));
        self::assertFalse(enc_is_encrypted('plain'));
        self::assertFalse(enc_is_encrypted('enc1:lowercase-prefix'));
        self::assertFalse(enc_is_encrypted(''));
        self::assertFalse(enc_is_encrypted(null));
        self::assertFalse(enc_is_encrypted(123));
    }

    public function test_decrypt_wrong_key_is_fail_open(): void
    {
        $token = enc_encrypt('right-key', 'secret');
        // A wrong key fails GCM auth -> decrypt returns the token UNCHANGED
        // (never false, never partial plaintext).
        self::assertSame($token, enc_decrypt('wrong-key', $token));
    }

    public function test_decrypt_tampered_ciphertext_is_fail_open(): void
    {
        $token = enc_encrypt('k', 'secret');
        // Flip a character in the base64 body -> auth tag mismatch.
        $tampered = $token;
        $tampered[strlen($tampered) - 1] = ($tampered[strlen($tampered) - 1] === 'A') ? 'B' : 'A';
        self::assertSame($tampered, enc_decrypt('k', $tampered));
    }

    public function test_decrypt_truncated_token_is_fail_open(): void
    {
        // nonce(12)+tag(16)=28 bytes minimum; a shorter payload is rejected.
        $short = 'ENC1:' . base64_encode(random_bytes(10));
        self::assertSame($short, enc_decrypt('k', $short));
    }

    public function test_decrypt_base64_garbage_is_fail_open(): void
    {
        $garbage = 'ENC1:!!!not-base64!!!';
        self::assertSame($garbage, enc_decrypt('k', $garbage));
    }

    public function test_decrypt_legacy_plaintext_passes_through(): void
    {
        // Values written before the extra existed have no ENC1: prefix and must
        // display unchanged (enabling the extra is non-destructive).
        self::assertSame('legacy plaintext', enc_decrypt('k', 'legacy plaintext'));
    }

    public function test_normalize_key_is_32_bytes_and_deterministic(): void
    {
        $a = enc_normalize_key('any-password');
        $b = enc_normalize_key('any-password');
        self::assertSame(32, strlen($a), 'AES-256 needs exactly 32 bytes');
        self::assertSame($a, $b, 'normalization must be deterministic');
        self::assertNotSame(enc_normalize_key('other'), $a);
    }
}
