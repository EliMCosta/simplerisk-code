<?php
/**
 * Integration tests for the Import-Export Extra's risk CSV import engine
 * (extras/import-export/includes/import.php).
 *
 * Covers the engine's branching logic under transaction rollback:
 *   - ie_match_existing_risk()   create-vs-update classification (Risk ID vs
 *                                reference_id vs no-match, incl. the not-found
 *                                fall-through)
 *   - ie_resolve_field()         name→id resolution for lookup/user kinds
 *   - ie_write_mitigation()       mitigation upsert (INSERT then UPDATE path)
 *   - ie_preview()                row classification counts + bad-file error
 *   - ie_run_import()             the real commit path (create via submit_risk,
 *                                update via update_risk) — proves core
 *                                submit_risk/update_risk run under rollback
 *
 * Isolation:
 *   - $GLOBALS['db_global'] is pinned to $this->txdb so every db_open() the
 *     engine makes returns the rollback connection (db_close() is a no-op).
 *   - $GLOBALS['customization_extra'] = false neutralizes the submit_risk()
 *     customization fatal — the exact technique from ApiDispatchTest.
 *   - $_SESSION is seeded as an admin so the engine's team-scope guard
 *     (allowed_team = null) never blocks a row. seedSession()/createUser() are
 *     copied from ApiDispatchTest to keep this test self-contained.
 *
 * export_xls() and the ie_export_* streamers call exit() (via ie_stream_csv) so
 * they are not testable here — e2e territory. Their pure helpers are covered in
 * ImportExportHelpersTest.
 */
declare(strict_types=1);

final class ImportExportImportTest extends IntegrationTestCase
{
    /** @var string[] temp CSV files removed in tearDown */
    private array $tmpFiles = [];

    /** @var int|null cached valid role id for user inserts */
    private static ?int $roleId = null;

    public static function setUpBeforeClass(): void
    {
        // The engine resolves the default mapping via ie_get_default_mapping(),
        // which queries import_export_mappings — the table must exist.
        self::loadExtra('import-export', 'schema.php', 'csv.php', 'fields.php', 'import.php', 'mappings.php');
        ie_install_schema();
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::loadExtra('import-export', 'schema.php', 'csv.php', 'fields.php', 'import.php', 'mappings.php');
        $GLOBALS['db_global']          = $this->txdb;
        $GLOBALS['customization_extra'] = false;
        $_SESSION = [];
        $_POST    = [];
        $_GET     = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $f) {
            @unlink($f);
        }
        $this->tmpFiles = [];
        $_SESSION = [];
        $_POST    = [];
        unset($GLOBALS['db_global'], $GLOBALS['customization_extra']);
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Insert a minimal plaintext risk and return its db id. */
    private function insertRisk(string $subject, string $referenceId = ''): int
    {
        $stmt = $this->txdb->prepare(
            "INSERT INTO risks (status, subject, reference_id, source, category, owner, manager, assessment, notes)
             VALUES ('New', :subject, :ref, 0, 0, 0, 0, '', '')"
        );
        $stmt->execute([':subject' => $subject, ':ref' => $referenceId]);
        $id = (int)$this->txdb->lastInsertId();
        // get_risk_by_id() INNER JOINs risk_scoring; without a matching row it
        // returns empty and update_risk() emits notices that PHPUnit turns into
        // exceptions. Seed a minimal Classic-scored row (a real submit_risk does).
        $this->txdb->prepare("INSERT INTO risk_scoring (id, scoring_method, calculated_risk) VALUES (?, 1, 0)")
            ->execute([$id]);
        return $id;
    }

    /** Seed a lookup-table row (value is auto-increment) and return its value. */
    private function seedLookup(string $table, string $name): int
    {
        $stmt = $this->txdb->prepare("INSERT INTO `{$table}` (`name`) VALUES (:name)");
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        return (int)$this->txdb->lastInsertId();
    }

    private function writeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sr_imp_');
        file_put_contents($path, $content);
        $this->tmpFiles[] = $path;
        return $path;
    }

    /** A 2-column mapping (Risk ID + Subject) sufficient to classify rows. */
    private function idSubjectMapping(): array
    {
        return ['columns' => [
            ['field' => 'risk_id', 'header' => 'Risk ID', 'included' => true],
            ['field' => 'subject', 'header' => 'Subject', 'included' => true],
        ]];
    }

    private function seedSession(bool $admin): int
    {
        $uid = $this->createUser($admin);
        $_SESSION['uid']       = $uid;
        $_SESSION['user']      = 'import_' . $uid;
        $_SESSION['name']      = 'Import Test';
        $_SESSION['admin']     = $admin ? '1' : '0';
        $_SESSION['user_type'] = 'simplerisk';
        $_SESSION['access']    = '1';
        foreach (get_possible_permissions() as $perm) {
            $_SESSION[$perm] = 1;
        }
        return $uid;
    }

    private function createUser(bool $admin): int
    {
        $roleId = self::$roleId ??= $this->anyRoleId();
        $stamp  = substr(uniqid('', true), -8);
        $stmt = $this->txdb->prepare(
            "INSERT INTO user
                (`username`, `name`, `email`, `password`, `role_id`, `admin`, `type`)
             VALUES
                (:username, :name, :email, :password, :role_id, :admin, 'simplerisk')"
        );
        $username = 'imptest_' . $stamp;
        $name     = 'Import User ' . $stamp;
        $email    = $stamp . '@example.test';
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

    // ------------------------------------------------------------------
    // ie_match_existing_risk()
    // ------------------------------------------------------------------

    public function test_match_by_risk_id_when_risk_exists(): void
    {
        $id = $this->insertRisk('Existing A');
        $match = ie_match_existing_risk(['risk_id' => $id + 1000]);

        self::assertSame('update', $match['action']);
        self::assertSame($id, $match['db_id']);
        self::assertSame($id + 1000, $match['risk_id']);
        self::assertStringContainsString('Risk ID', $match['reason']);
    }

    public function test_match_by_reference_id(): void
    {
        $ref = 'REF-' . substr(uniqid('', true), -8);
        $id = $this->insertRisk('Existing Ref', $ref);
        $match = ie_match_existing_risk(['reference_id' => $ref]);

        self::assertSame('update', $match['action']);
        self::assertSame($id, $match['db_id']);
        self::assertStringContainsString('Reference ID', $match['reason']);
    }

    public function test_no_match_classifies_as_create(): void
    {
        self::assertSame('create', ie_match_existing_risk([])['action']);
    }

    public function test_risk_id_present_but_absent_falls_through_to_create(): void
    {
        // risk_id >= 1000, but no such risk and no reference_id: the engine must
        // NOT treat it as an update of a phantom row.
        $match = ie_match_existing_risk(['risk_id' => 999999]);
        self::assertSame('create', $match['action']);
    }

    // ------------------------------------------------------------------
    // ie_resolve_field() lookup/user branches (passthrough kinds in unit test)
    // ------------------------------------------------------------------

    public function test_resolve_field_lookup_and_user_kinds(): void
    {
        $catName = 'Cat-' . substr(uniqid('', true), -6);
        $catId   = $this->seedLookup('category', $catName);

        $uid = $this->createUser(true);
        $userName = $this->txdb->query("SELECT `name` FROM `user` WHERE `value` = {$uid}")->fetchColumn();

        self::assertSame($catId, ie_resolve_field('category', ['kind' => 'lookup', 'table' => 'category'], $catName));
        self::assertSame(0, ie_resolve_field('category', ['kind' => 'lookup', 'table' => 'category'], 'NoSuchCategory'));

        self::assertSame($uid, ie_resolve_field('owner', ['kind' => 'user', 'table' => 'user'], (string)$userName));
        self::assertSame(0, ie_resolve_field('owner', ['kind' => 'user', 'table' => 'user'], 'NoSuchUser'));
    }

    // ------------------------------------------------------------------
    // ie_write_mitigation()
    // ------------------------------------------------------------------

    public function test_write_mitigation_inserts_then_updates(): void
    {
        $_SESSION['uid'] = $this->createUser(true);
        $displayId = $this->insertRisk('Mit Risk') + 1000;

        // INSERT path: no existing mitigation.
        self::assertTrue(ie_write_mitigation($displayId, ['mitigation_cost' => 3]));
        $row = $this->txdb->query("SELECT `id`, `mitigation_cost` FROM `mitigations` WHERE `risk_id` = " . ($displayId - 1000))->fetch(PDO::FETCH_ASSOC);
        self::assertNotEmpty($row);
        self::assertEquals(3, $row['mitigation_cost']);
        // The risk is linked to its new mitigation.
        $linked = (int)$this->txdb->query("SELECT `mitigation_id` FROM `risks` WHERE `id` = " . ($displayId - 1000))->fetchColumn();
        self::assertSame((int)$row['id'], $linked);

        // UPDATE path: a second write upserts the existing row, not a new one.
        self::assertTrue(ie_write_mitigation($displayId, ['mitigation_cost' => 7]));
        $count = (int)$this->txdb->query("SELECT COUNT(*) FROM `mitigations` WHERE `risk_id` = " . ($displayId - 1000))->fetchColumn();
        self::assertSame(1, $count, 'UPDATE path must not insert a second mitigation');
        $cost = (int)$this->txdb->query("SELECT `mitigation_cost` FROM `mitigations` WHERE `risk_id` = " . ($displayId - 1000))->fetchColumn();
        self::assertSame(7, $cost);
    }

    // ------------------------------------------------------------------
    // ie_preview()
    // ------------------------------------------------------------------

    public function test_preview_counts_create_update_and_invalid(): void
    {
        $this->seedSession(true); // admin -> team-scope guard skipped
        $existing = $this->insertRisk('Pre-Existing') + 1000;
        $mapping  = $this->idSubjectMapping();

        $csv = "Risk ID,Subject\n"
            . ",New One\n"                      // create
            . "{$existing},Update Me\n"         // update
            . ",\n";                            // invalid (no subject)
        $preview = ie_preview($this->writeCsv($csv), $mapping);

        self::assertSame(['create' => 1, 'update' => 1, 'invalid' => 1], $preview['counts']);
        self::assertSame(3, $preview['total']);
    }

    public function test_preview_returns_error_for_missing_file(): void
    {
        $this->seedSession(true);
        // The engine fopen()s the path (via ie_read_header) before reaching its
        // missing-file guard, so @ suppresses that expected warning; the guard
        // then returns the error payload (same pattern as @ie_open_csv in the
        // CSV unit test).
        $preview = @ie_preview('/no/such/file_' . substr(uniqid('', true), -6) . '.csv', $this->idSubjectMapping());
        self::assertArrayHasKey('error', $preview);
    }

    // ------------------------------------------------------------------
    // ie_run_import()
    // ------------------------------------------------------------------

    public function test_run_import_creates_a_risk(): void
    {
        $this->seedSession(true);
        $subject = 'Imported Risk ' . uniqid();
        $mapping = ['columns' => [['field' => 'subject', 'header' => 'Subject', 'included' => true]]];
        $path    = $this->writeCsv("Subject\n{$subject}\n");

        $before = (int)$this->txdb->query("SELECT COUNT(*) FROM risks")->fetchColumn();
        $stats  = ie_run_import($path, $mapping);
        $after  = (int)$this->txdb->query("SELECT COUNT(*) FROM risks")->fetchColumn();

        self::assertSame(1, $stats['created']);
        self::assertSame(0, $stats['updated']);
        self::assertSame(0, $stats['skipped']);
        self::assertSame($before + 1, $after, 'a row was inserted (and rolls back after the test)');

        // The newest risk's subject round-trips through try_decrypt (identity
        // without the Encryption Extra, a token with it).
        $stored = (string)$this->txdb->query("SELECT `subject` FROM `risks` ORDER BY `id` DESC LIMIT 1")->fetchColumn();
        self::assertSame($subject, try_decrypt($stored));
    }

    public function test_run_import_updates_an_existing_risk(): void
    {
        // Exercises ie_apply_update -> core update_risk($id, true). The subject
        // is applied by a direct UPDATE AFTER update_risk returns, so the CSV
        // must also map at least one field update_risk reads from $_POST
        // (here 'notes'); otherwise update_risk would build "UPDATE risks SET
        // WHERE ..." (malformed) and throw. A real mapping always carries more
        // than the subject, so this mirrors real use.
        $this->seedSession(true);
        $id       = $this->insertRisk('Original Subject');
        $display  = $id + 1000;
        $newSubj  = 'Updated Subject ' . uniqid();
        $newNotes = 'Updated Notes ' . uniqid();
        $mapping  = ['columns' => [
            ['field' => 'risk_id', 'header' => 'Risk ID',         'included' => true],
            ['field' => 'subject', 'header' => 'Subject',         'included' => true],
            ['field' => 'notes',   'header' => 'Additional Notes','included' => true],
        ]];
        $path = $this->writeCsv("Risk ID,Subject,Additional Notes\n{$display},{$newSubj},{$newNotes}\n");

        $stats = ie_run_import($path, $mapping);

        self::assertSame(1, $stats['updated']);
        self::assertSame(0, $stats['created']);
        $stored = (string)$this->txdb->query("SELECT `subject` FROM `risks` WHERE `id` = {$id}")->fetchColumn();
        self::assertSame($newSubj, try_decrypt($stored));
    }
}
