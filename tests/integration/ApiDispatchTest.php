<?php
/**
 * Integration tests for the API Extra tool dispatcher
 * (extras/api/includes/tools.php :: api_execute_tool) and the tool-visibility
 * filter. Auth (auth.php) and the key store are covered elsewhere; this file
 * exercises what a tool call actually DOES — the authorization gates, argument
 * validation, and read/write execution paths that LibreChat depends on.
 *
 * Dispatch reads the bound user straight from $_SESSION (is_admin() reads
 * $_SESSION['admin']; check_permission() reads $_SESSION[<perm>] and needs a
 * non-empty $_SESSION['user']). So instead of starting a real session (which
 * would need @runInSeparateProcess), seedSession() writes $_SESSION directly,
 * mirroring api_seed_session()'s flag layout. Every db_open() the dispatcher
 * makes is pinned to $this->txdb, so writes land in the rollback transaction.
 */
declare(strict_types=1);

final class ApiDispatchTest extends IntegrationTestCase
{
    /** @var int|null cached valid role id for user inserts */
    private static ?int $roleId = null;

    protected function setUp(): void
    {
        parent::setUp();
        self::loadExtra('api', 'auth.php', 'protocol.php', 'tools.php');
        // Route every db_open() the dispatcher makes onto the rollback txn.
        $GLOBALS['db_global'] = $this->txdb;
        // Neutralize the submit_risk() customization path (it fatals when the
        // customization extra is enabled without its files). Pinning the cache
        // makes customization_extra() return false without a DB write.
        $GLOBALS['customization_extra'] = false;
        $this->clearSettingCache('api_keys', 'api_server_enabled', 'api_allow_user_keys');
        $_SESSION = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($GLOBALS['customization_extra']);
        parent::tearDown();
    }

    /**
     * Seed $_SESSION as a fully-authenticated user WITHOUT starting a real
     * session (mirrors api_seed_session()'s layout). Admins get every
     * permission; a non-admin gets every permission except those in $deny.
     */
    private function seedSession(bool $admin, array $deny = []): int
    {
        $uid = $this->createUser($admin);
        $_SESSION = [];
        $_SESSION['uid']       = $uid;
        $_SESSION['user']      = 'dispatch_' . $uid; // non-empty -> check_permission proceeds
        $_SESSION['name']      = 'Dispatch Test';
        $_SESSION['admin']     = $admin ? '1' : '0';
        $_SESSION['user_type'] = 'simplerisk';
        $_SESSION['access']    = '1';
        foreach (get_possible_permissions() as $perm) {
            $_SESSION[$perm] = ($admin || !in_array($perm, $deny, true)) ? 1 : 0;
        }
        return $uid;
    }

    public function test_non_admin_cannot_call_a_write_tool(): void
    {
        // Req #2: the top gate blocks non-administrator keys from every write
        // tool before any permission check runs.
        $this->seedSession(admin: false);
        try {
            api_execute_tool('submit_risk', ['subject' => 'x']);
            self::fail('Expected ApiToolException for a non-admin write attempt');
        } catch (ApiToolException $e) {
            self::assertStringContainsString('non-administrator', $e->getMessage());
            self::assertFalse($e->protocol_error, 'denial is an isError result');
        }
    }

    public function test_non_admin_can_call_a_read_tool(): void
    {
        $this->seedSession(admin: false); // deny=[] -> riskmanagement granted
        $res = api_execute_tool('list_risks', []);
        self::assertIsArray($res);
        self::assertArrayHasKey('risks', $res);
        self::assertArrayHasKey('total', $res);
    }

    public function test_admin_submit_risk_inserts_a_row(): void
    {
        $this->seedSession(admin: true);
        $before = (int)$this->txdb->query("SELECT COUNT(*) FROM risks")->fetchColumn();

        $res = api_execute_tool('submit_risk', ['subject' => 'Dispatch Risk ' . uniqid()]);

        self::assertArrayHasKey('risk_id', $res);
        self::assertGreaterThan(1000, $res['risk_id'], 'risk_id is the display id (internal + 1000)');
        $after = (int)$this->txdb->query("SELECT COUNT(*) FROM risks")->fetchColumn();
        self::assertSame($before + 1, $after, 'a row was inserted (and rolls back after the test)');
    }

    public function test_get_risk_round_trips_a_submitted_risk(): void
    {
        $this->seedSession(admin: true);
        $created = api_execute_tool('submit_risk', ['subject' => 'Roundtrip Subject']);

        $fetched = api_execute_tool('get_risk', ['risk_id' => $created['risk_id']]);
        self::assertIsArray($fetched);
        // get_risk_by_id() stores the internal id (display - 1000) on the row.
        self::assertSame($created['risk_id'] - 1000, (int)$fetched['id']);
    }

    public function test_missing_required_param_is_a_protocol_error(): void
    {
        $this->seedSession(admin: true);
        try {
            api_execute_tool('get_risk', []); // risk_id is required
            self::fail('Expected ApiToolException for a missing required parameter');
        } catch (ApiToolException $e) {
            self::assertTrue($e->protocol_error, 'a bad argument is a JSON-RPC invalid-params error');
            self::assertStringContainsString('risk_id', $e->getMessage());
        }
    }

    public function test_permission_denial_is_a_result_error_not_protocol(): void
    {
        // A non-admin passes the write-gate (list_risks is read-only) but is then
        // denied by api_require_perm('riskmanagement'); that is an isError result
        // (protocol_error=false), not a malformed-request protocol error.
        $this->seedSession(admin: false, deny: ['riskmanagement']);
        try {
            api_execute_tool('list_risks', []);
            self::fail('Expected ApiToolException for a permission denial');
        } catch (ApiToolException $e) {
            self::assertFalse($e->protocol_error);
            self::assertStringContainsString('riskmanagement', $e->getMessage());
        }
    }

    public function test_unknown_tool_is_a_protocol_error(): void
    {
        $this->seedSession(admin: true);
        try {
            api_execute_tool('no_such_tool', []);
            self::fail('Expected ApiToolException for an unknown tool');
        } catch (ApiToolException $e) {
            self::assertTrue($e->protocol_error);
            self::assertStringContainsString('Unknown tool', $e->getMessage());
        }
    }

    public function test_visible_tool_definitions_role_scoping(): void
    {
        // Administrator: sees every classified tool, including writes.
        $this->seedSession(admin: true);
        $adminNames = array_column(api_visible_tool_definitions(), 'name');
        self::assertContains('list_risks', $adminNames);
        self::assertContains('submit_risk', $adminNames);

        // Non-administrator: sees read-only tools only, never writes.
        $this->seedSession(admin: false);
        $userNames = array_column(api_visible_tool_definitions(), 'name');
        self::assertContains('list_risks', $userNames);
        self::assertNotContains('submit_risk', $userNames);
        self::assertNotContains('close_risk', $userNames);
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
