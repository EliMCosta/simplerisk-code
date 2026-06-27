<?php
/**
 * Unit tests for the core encryption trampolines try_encrypt() / try_decrypt()
 * (simplerisk/includes/functions.php).
 *
 * Contract under test: WITHOUT the Encryption Extra enabled, every value passes
 * through UNCHANGED (enabling encryption later is non-destructive). try_decrypt
 * also short-circuits empty/falsey input. (With the extra enabled these route to
 * enc_encrypt/enc_decrypt, covered by EncryptionCryptoTest — so this test skips
 * itself if the extra is on.)
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TryEncryptTest extends TestCase
{
    protected function setUp(): void
    {
        if (encryption_extra()) {
            static::markTestSkipped('Encryption Extra is enabled — identity contract does not apply.');
        }
    }

    public static function passthroughValues(): array
    {
        return [
            'ascii'     => ['hello'],
            'json'      => ['{"a":1}'],
            'unicode'   => ['Üñîçødé'],
            'spaces'    => ['  leading and trailing  '],
            'looks enc' => ['ENC1:something'],
        ];
    }

    /**
     * @dataProvider passthroughValues
     */
    public function test_try_encrypt_is_identity_without_extra(string $value): void
    {
        self::assertSame($value, try_encrypt($value));
    }

    /**
     * @dataProvider passthroughValues
     */
    public function test_try_decrypt_is_identity_without_extra(string $value): void
    {
        self::assertSame($value, try_decrypt($value));
    }

    public function test_try_decrypt_short_circuits_empty_input(): void
    {
        self::assertSame('', try_decrypt(''));
        self::assertSame(0, try_decrypt(0));
        self::assertSame(null, try_decrypt(null));
        self::assertFalse(try_decrypt(false));
    }
}
