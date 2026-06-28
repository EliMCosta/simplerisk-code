<?php
/**
 * Integration tests for the API Extra administration surface
 * (extras/api/includes/auth.php toggle setters + self-service key contract).
 *
 * The admin/profile POST handlers (api_admin_save / api_profile_handle_post)
 * end every action with refresh() (Post/Redirect/Get) and so call exit(); they
 * are not callable in-process and are left to the e2e smoke. This file covers
 * the primitive setters those handlers delegate to — the server-endpoint and
 * self-service-key toggles — plus the self-service non-admin key contract
 * (active, bound to the creator, and non-administrative). All writes roll back.
 */
declare(strict_types=1);

final class ApiKeyMgmtTest extends IntegrationTestCase
{
    private const PEPPER = 'mgmt-test-pepper';

    /** @var int|null cached valid role id for user inserts */
    private static ?int $roleId = null;

    public static function setUpBeforeClass(): void
    {
        putenv('SIMPLERISK_API_TOKEN_PEPPER=' . self::PEPPER);
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::loadExtra('api', 'auth.php');
        $this->clearSettingCache(
            'api_keys',
            'api_token_pepper',
            'api_server_enabled',
            'api_allow_user_keys'
        );
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_server_enabled_toggle_round_trips(): void
    {
        api_set_server_enabled(true);
        $this->clearSettingCache('api_server_enabled');
        self::assertTrue(api_is_server_enabled());
        self::assertSame('1', $this->getSetting('api_server_enabled'), 'persists "1"');

        api_set_server_enabled(false);
        $this->clearSettingCache('api_server_enabled');
        self::assertFalse(api_is_server_enabled());
        self::assertSame('0', $this->getSetting('api_server_enabled'));
    }

    public function test_server_enabled_recognizes_affirmative_words(): void
    {
        update_setting('api_server_enabled', 'yes');
        $this->clearSettingCache('api_server_enabled');
        self::assertTrue(api_is_server_enabled());

        update_setting('api_server_enabled', 'off');
        $this->clearSettingCache('api_server_enabled');
        self::assertFalse(api_is_server_enabled());
    }

    public function test_user_keys_allowed_toggle_round_trips(): void
    {
        // Default off: a missing row reads as off. Delete any live row first
        // (rolled back by the test transaction) so this assertion is independent
        // of whether an admin has enabled self-service at runtime.
        $this->txdb->prepare("DELETE FROM settings WHERE name = 'api_allow_user_keys'")->execute();
        $this->clearSettingCache('api_allow_user_keys');
        self::assertFalse(api_user_keys_allowed());

        api_set_user_keys_allowed(true);
        $this->clearSettingCache('api_allow_user_keys');
        self::assertTrue(api_user_keys_allowed());
        self::assertSame('1', $this->getSetting('api_allow_user_keys'));

        api_set_user_keys_allowed(false);
        $this->clearSettingCache('api_allow_user_keys');
        self::assertFalse(api_user_keys_allowed());
    }

    public function test_self_service_non_admin_key_is_active_and_non_admin(): void
    {
        $uid = $this->createUser(admin: false);
        $plain = api_create_key_entry($uid);

        $entry = api_resolve_key_entry($plain);
        self::assertNotNull($entry, 'a freshly minted non-admin key resolves');
        self::assertSame($uid, (int)$entry['uid'], 'bound to its creator');
        self::assertTrue(!empty($entry['enabled']), 'created active');
        // The read-only-for-non-administrators contract depends on this:
        self::assertFalse(api_user_is_admin($uid));
    }

    /**
     * Insert a minimal user row and return its id (mirrors ApiKeyStoreTest).
     */
    private function createUser(bool $admin): int
    {
        $roleId = self::$roleId ??= $this->anyRoleId();
        $stamp = substr(uniqid('', true), -8);
        $stmt = $this->txdb->prepare(
            "INSERT INTO user
                (`username`, `name`, `email`, `password`, `role_id`, `admin`, `type`)
             VALUES
                (:username, :name, :email, :password, :role_id, :admin, 'simplerisk')"
        );
        $username = 'apitest_' . $stamp;
        $name = 'API Test ' . $stamp;
        $email = $stamp . '@example.test';
        $password = str_repeat('x', 60);
        $stmt->bindParam(':username', $username, PDO::PARAM_LOB);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_LOB);
        $stmt->bindValue(':password', $password, PDO::PARAM_LOB);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->bindValue(':admin', $admin ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->txdb->lastInsertId();
    }

    private function anyRoleId(): int
    {
        $v = $this->txdb->query("SELECT `value` FROM role ORDER BY `value` LIMIT 1")->fetchColumn();
        return $v !== false ? (int)$v : 1;
    }
}
