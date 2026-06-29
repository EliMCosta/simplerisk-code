<?php
/**
 * E2E regression for the Encryption Extra's admin surface.
 *
 * Encryption is intentionally NOT toggled here: enabling runs a single-pass
 * re-encrypt/backfill over live data, and a bulk re-encrypt is out of scope for a
 * test. So the admin-page render is always asserted, and the "no-key XXXX
 * indicator is absent when enabled" assertion self-skips when the extra is off.
 */
declare(strict_types=1);

final class EncryptionStatusTest extends E2ETestCase
{
    public function test_encryption_admin_page_renders_and_is_auth_gated(): void
    {
        // On this Community Edition the extra is entitlement-restricted, so the
        // page renders an "upgrade" notice rather than the Activate/Status panel —
        // the stable contract is that the page is routed for an authenticated user
        // and gated for an anonymous one (the HostTreePageTest pattern).
        [$code, , , $err] = $this->authedGet('/admin/encryption.php');
        self::assertSame('', $err, "curl error: {$err}");
        self::assertSame(200, $code, 'expected 200 for the authenticated encryption admin page');

        [$unauthed] = $this->unauthedGet('/admin/encryption.php');
        self::assertNotSame(200, $unauthed, 'unauthenticated encryption admin page must not render');
        self::assertGreaterThanOrEqual(300, $unauthed);
        self::assertLessThan(400, $unauthed, 'expected a 3xx redirect for the unauthenticated page');
    }

    public function test_when_enabled_the_no_key_xxxx_indicator_is_absent(): void
    {
        if ($this->readSetting('encryption') !== '1') {
            self::markTestSkipped('Encryption Extra is not enabled — status path absent by design (not toggled to avoid a bulk re-encrypt).');
        }
        [, , $body] = $this->authedGet('/admin/encryption.php');
        self::assertStringNotContainsString('XXXX', $body, 'encryption is enabled but the no-key XXXX indicator is showing');
    }
}
