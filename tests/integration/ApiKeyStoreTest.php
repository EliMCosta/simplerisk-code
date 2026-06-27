<?php
/**
 * Integration tests for the API Extra key store (extras/api/includes/auth.php):
 * the peppered-HMAC key lifecycle backed by the settings table.
 *
 * Covers mint -> resolve round-trip, disabled/unknown rejection, the legacy
 * SHA-256 -> v1 HMAC upgrade-in-place, last_used_at tracking, and the REST
 * admin-only gate (authenticate_key refuses a non-admin key WITHOUT seeding the
 * session). All writes go through the per-test transaction (IntegrationTestCase)
 * and roll back; the pepper is pinned so verifier output is deterministic.
 */
declare(strict_types=1);

final class ApiKeyStoreTest extends IntegrationTestCase
{
    private const PEPPER = 'integration-test-pepper';

    /** @var int|null cached valid role id for user inserts */
    private static ?int $roleId = null;

    public static function setUpBeforeClass(): void
    {
        putenv('SIMPLERISK_API_TOKEN_PEPPER=' . self::PEPPER);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Loaded per-test (idempotent) so a @runInSeparateProcess child has the
        // extra's functions available without relying on the parent's setUp.
        self::loadExtra('api', 'auth.php');
        // Extras read fixed-name settings via the cached path; start each test
        // from an empty cache so it sees this transaction's writes.
        $this->clearSettingCache(
            'api_keys',
            'api_token_pepper',
            'api_pepper_drift_noted',
            'api_server_enabled',
            'api_allow_user_keys'
        );
        $_SESSION = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        // Discard any session api_seed_session() started WITHOUT writing it to
        // the (already-rolled-back) DB session table at shutdown.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_abort();
        }
        $_SESSION = [];
        unset($_SERVER['HTTP_X_API_KEY']);
        parent::tearDown();
    }

    public function test_mint_then_resolve_roundtrip(): void
    {
        $uid = $this->createUser(admin: true);
        $plain = api_create_key_entry($uid);

        self::assertSame(64, strlen($plain), 'a key is 64 hex chars');
        self::assertTrue(ctype_xdigit($plain), 'a key is hexadecimal');

        $entry = api_resolve_key_entry($plain);
        self::assertNotNull($entry, 'a freshly minted key must resolve');
        self::assertSame($uid, (int)$entry['uid']);
        self::assertTrue(!empty($entry['enabled']));
        self::assertSame('hmacsha256', $entry['alg']);
        // Only the verifier (not the plaintext) is stored.
        self::assertNotSame($plain, $entry['verifier'] ?? null);

        // First resolve persists last_used_at on the stored entry (the returned
        // entry is the pre-touch snapshot, so read the store back).
        $matched = $this->storedEntryForUser($uid);
        self::assertNotNull($matched, 'the minted key must be present in the store');
        self::assertNotNull($matched['last_used_at'], 'last_used_at is set on first use');
    }

    public function test_resolve_returns_null_for_unknown_and_empty(): void
    {
        $this->createUser(admin: true); // ensure a key exists, but unknown one is asked
        api_create_key_entry($this->createUser(admin: true));

        self::assertNull(api_resolve_key_entry('not-a-real-key'));
        self::assertNull(api_resolve_key_entry(''));
        self::assertNull(api_resolve_key_entry(null));
    }

    public function test_resolve_returns_null_for_disabled_key(): void
    {
        $uid = $this->createUser(admin: true);
        $plain = api_create_key_entry($uid);

        // Flip the entry to disabled and persist.
        $keys = api_get_stored_keys();
        foreach ($keys as &$e) {
            if (($e['uid'] ?? null) == $uid) {
                $e['enabled'] = false;
            }
        }
        unset($e);
        api_save_stored_keys($keys);
        $this->clearSettingCache('api_keys');

        self::assertNull(api_resolve_key_entry($plain), 'a disabled key must not authenticate');
    }

    public function test_legacy_sha256_entry_is_upgraded_in_place(): void
    {
        $uid = $this->createUser(admin: true);
        $legacy = 'legacy-plain-key';

        // Store a pre-HMAC entry: plain SHA-256, no alg/verifier.
        api_save_stored_keys([[
            'uid'     => $uid,
            'hash'    => hash('sha256', $legacy),
            'enabled' => true,
            'label'   => 'old',
        ]]);

        $entry = api_resolve_key_entry($legacy);
        self::assertNotNull($entry, 'legacy key must still authenticate');
        self::assertSame($uid, (int)$entry['uid']);

        // The single matched entry is upgraded to v1 (HMAC verifier + index).
        $this->clearSettingCache('api_keys');
        $upgraded = api_get_stored_keys();
        self::assertSame('hmacsha256', $upgraded[0]['alg'] ?? null);
        self::assertArrayHasKey('verifier', $upgraded[0]);
        // ...and now resolves via the fast pass.
        self::assertNotNull(api_resolve_key_entry($legacy));
    }

    public function test_user_is_admin_reads_admin_flag_directly(): void
    {
        $admin = $this->createUser(admin: true);
        $plain = $this->createUser(admin: false);
        self::assertTrue(api_user_is_admin($admin));
        self::assertFalse(api_user_is_admin($plain));
        self::assertFalse(api_user_is_admin(0));
    }

    public function test_authenticate_key_refuses_non_admin_without_seeding(): void
    {
        $uid = $this->createUser(admin: false);
        $plain = api_create_key_entry($uid);
        $_SERVER['HTTP_X_API_KEY'] = $plain;

        // REST is admin-only: a non-admin key is refused and MUST NOT seed the
        // session as that user (update_setting's audit log may leave a harmless
        // $_SESSION['uid'] = -1, but the resolved user's id must never land).
        self::assertFalse(authenticate_key());
        self::assertNotSame($uid, $_SESSION['uid'] ?? null);
    }

    /**
     * The session-seeding path calls session_set_save_handler(), which PHP
     * forbids once any output has been emitted — and PHPUnit prints its banner
     * before the test under the CLI SAPI. Run in a clean process so no output
     * precedes the session start.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_authenticate_key_seeds_session_for_admin(): void
    {
        $uid = $this->createUser(admin: true);
        $plain = api_create_key_entry($uid);
        $_SERVER['HTTP_X_API_KEY'] = $plain;

        $resolved = authenticate_key();
        self::assertSame($uid, $resolved);
        self::assertSame($uid, $_SESSION['uid'] ?? null);
        self::assertSame('1', (string)($_SESSION['admin'] ?? null));
    }

    /** Find the stored key entry bound to $uid (null if none). */
    private function storedEntryForUser(int $uid): ?array
    {
        foreach (api_get_stored_keys() as $entry) {
            if ((int)($entry['uid'] ?? 0) === $uid) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Insert a minimal user row (PK `value` is auto-increment) and return its id.
     * Only the NOT-NULL-no-default columns are set; admin is controllable.
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
        $password = str_repeat('x', 60); // binary(60); value is irrelevant here
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
