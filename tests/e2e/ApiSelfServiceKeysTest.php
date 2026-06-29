<?php
/**
 * E2E regression for the API Extra's self-service user keys.
 *
 * A non-admin user can generate a personal API key from their profile page
 * (POST /account/profile.php action=api_profile_create), the key entry is bound
 * to their uid in the shared api_keys blob, and the plaintext is flashed once on
 * the profile page (the reveal-once contract). This is the self-service surface
 * that had no HTTP coverage (only the admin key store + dispatch are unit-tested).
 *
 * The api extra + self-service toggle are force-enabled and snapshotted/restored;
 * the whole api_keys blob is snapshotted/restored too (it is shared across users).
 */
declare(strict_types=1);

final class ApiSelfServiceKeysTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    /** @var array<string,?string> snapshot of the toggles + the shared api_keys blob. */
    private array $snapshot = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->snapshot = $this->snapshotSettings('api', 'api_allow_user_keys', 'api_keys');
        $this->writeSetting('api', '1');
        $this->writeSetting('api_allow_user_keys', '1');
    }

    protected function tearDown(): void
    {
        // Restore the shared api_keys blob verbatim (removes the test-created key),
        // then the toggles.
        $this->restoreSettings($this->snapshot);
        parent::tearDown();
    }

    public function test_non_admin_can_generate_a_uid_bound_self_service_key(): void
    {
        $info = $this->ensureRestrictedUser();
        $this->loginRestrictedSession();

        $label = 'E2E_KEY_' . uniqid();
        $nonce = $this->extractProfileNonce($this->authedGetAs('restricted', '/account/profile.php')[2]);
        self::assertNotSame('', $nonce, 'expected an api_profile_nonce in the rendered profile page');

        [$code, , $body] = $this->authedPostAs('restricted', '/account/profile.php', [
            'action'            => 'api_profile_create',
            'api_profile_nonce' => $nonce,
            'label'             => $label,
        ]);
        self::assertSame(302, $code, "key creation should PRG-redirect; body: {$body}");

        // The shared api_keys blob gained an entry bound to the restricted user.
        $entry = $this->findKeyEntryByLabel($info['uid'], $label);
        self::assertNotNull($entry, "expected a uid-bound key entry for label '{$label}'");
        self::assertSame($info['uid'], (int) ($entry['uid'] ?? 0));
        self::assertTrue((bool) ($entry['enabled'] ?? false));

        // The plaintext key is flashed once on the profile page after the redirect.
        [, , $profile] = $this->authedGetAs('restricted', '/account/profile.php');
        self::assertSame(
            1,
            preg_match('/id=["\']api-profile-newkey["\'][^>]*value=["\']([0-9a-f]+)["\']/i', $profile, $m)
                ?: preg_match('/value=["\']([0-9a-f]{64})["\'][^>]*id=["\']api-profile-newkey["\']/i', $profile, $m),
            'the new plaintext key was not surfaced in the profile flash'
        );
        self::assertSame(64, strlen($m[1]), 'expected a 64-hex-char plaintext key');
    }

    private function extractProfileNonce(string $html): string
    {
        if (preg_match('/name=["\']api_profile_nonce["\'][^>]*value=["\']([0-9a-f]+)["\']/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/value=["\']([0-9a-f]{32})["\'][^>]*name=["\']api_profile_nonce["\']/i', $html, $m)) {
            return $m[1];
        }
        return '';
    }

    /** Find the api_keys entry for $uid with $label (null if none / blob unreadable). */
    private function findKeyEntryByLabel(int $uid, string $label): ?array
    {
        $blob = $this->readSetting('api_keys');
        if ($blob === null || $blob === '') {
            return null;
        }
        // The API Extra wraps the api_keys blob in try_encrypt() at rest once the
        // Encryption Extra is active (extras/api/includes/auth.php:153; the canonical
        // reader api_get_stored_keys decrypts at auth.php:137). Mirror it, with the
        // same raw-JSON fallback for a blob that has not yet been re-encrypted.
        $plain = function_exists('try_decrypt') ? try_decrypt($blob) : $blob;
        $keys = json_decode($plain, true);
        if (!is_array($keys)) {
            $keys = json_decode($blob, true);
        }
        if (!is_array($keys)) {
            return null;
        }
        foreach ($keys as $k) {
            if ((int) ($k['uid'] ?? 0) === $uid && ($k['label'] ?? null) === $label) {
                return $k;
            }
        }
        return null;
    }
}
