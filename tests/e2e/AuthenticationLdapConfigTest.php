<?php
/**
 * E2E regression for the Custom Authentication (LDAP) Extra's config surface.
 *
 * The admin page renders the LDAP configuration form, and submitting it persists
 * the connection settings (LDAP_HOST etc.) to the settings table. The form is
 * gated by a per-session csrf nonce (rendered as <input name="csrf">, checked via
 * hash_equals against $_SESSION['ldapauth_csrf']). An actual LDAP bind/login is
 * NOT attempted (no standalone "test connection" endpoint, and a mis-bind risks
 * lockout) — config persistence is the safe, observable contract.
 *
 * custom_auth + the touched LDAP_* settings are snapshotted/restored.
 */
declare(strict_types=1);

final class AuthenticationLdapConfigTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    /** @var array<string,?string> */
    private array $snapshot = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->snapshot = $this->snapshotSettings('custom_auth', 'LDAP_HOST', 'LDAP_PORT', 'LDAP_BIND_DN');
        $this->writeSetting('custom_auth', '1');
    }

    protected function tearDown(): void
    {
        $this->restoreSettings($this->snapshot);
        parent::tearDown();
    }

    public function test_authentication_admin_page_renders_ldap_form(): void
    {
        [$code, , $body, $err] = $this->authedGet('/admin/authentication.php');
        self::assertSame('', $err, "curl error: {$err}");
        self::assertSame(200, $code);
        self::assertStringContainsString('ldap_host', $body, 'LDAP host field missing from the authentication admin page');
    }

    public function test_ldap_connection_config_persists(): void
    {
        $csrf = $this->extractLdapCsrf($this->authedGet('/admin/authentication.php')[2]);
        self::assertNotSame('', $csrf, 'expected a csrf nonce in the LDAP config form');

        $host = 'e2e-ldap-' . uniqid('h', false) . '.test'; // hostname chars only (ldapauth sanitises)
        [$code, , $body] = $this->authedPost('/admin/authentication.php', [
            'update_ldap' => 1,
            'ldap_host'   => $host,
            'ldap_port'   => 1389,
            'csrf'        => $csrf,
        ]);
        self::assertSame(302, $code, "ldap config save should PRG-redirect; body: {$body}");
        self::assertSame($host, $this->readSetting('LDAP_HOST'), 'LDAP_HOST did not persist');
    }

    private function extractLdapCsrf(string $html): string
    {
        if (preg_match('/name=["\']csrf["\'][^>]*value=["\']([0-9a-f]+)["\']/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/value=["\']([0-9a-f]+)["\'][^>]*name=["\']csrf["\']/i', $html, $m)) {
            return $m[1];
        }
        return '';
    }
}
