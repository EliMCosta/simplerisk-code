<?php
/**
 * Integration tests for the Import-Export Extra's mapping template CRUD
 * (extras/import-export/includes/mappings.php) and the schema installer
 * (schema.php).
 *
 * A "mapping" is a named, per-entity template of CSV columns stored as JSON in
 * `import_export_mappings`. These tests exercise the full create/read/update/
 * delete lifecycle plus the default-mapping and normalization helpers.
 *
 *   - ie_install_schema() is idempotent DDL (CREATE TABLE IF NOT EXISTS + a
 *     one-time 'Standard Risk' seed). It runs once in setUpBeforeClass and
 *     commits (MySQL implicitly commits DDL), so the table persists in the dev
 *     DB across runs — same approach as OrgHierarchyTest/CustomizationFieldsTest.
 *   - All CRUD is plain INSERT/UPDATE/DELETE, so it lands in the rollback
 *     transaction (every db_open() the mappings code makes returns the pinned
 *     $GLOBALS['db_global']) and never persists. No manual row cleanup needed.
 *   - Test mappings use unique names ('IE-...') so the persisted 'Standard Risk'
 *     seed never collides.
 */
declare(strict_types=1);

final class ImportExportMappingsTest extends IntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        // Ensure the table exists before any CRUD. Idempotent: a second run finds
        // both the table and the 'Standard Risk' seed and does nothing.
        self::loadExtra('import-export', 'schema.php', 'fields.php', 'mappings.php');
        ie_install_schema();
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::loadExtra('import-export', 'schema.php', 'fields.php', 'mappings.php');
        // Route every db_open() the mappings CRUD makes onto the rollback txn.
        $GLOBALS['db_global'] = $this->txdb;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['db_global']);
        parent::tearDown();
    }

    /** Read the is_default flag for a mapping directly from the table. */
    private function isDefault(int $id): int
    {
        $stmt = $this->txdb->prepare("SELECT `is_default` FROM `" . IE_MAPPINGS_TABLE . "` WHERE `id` = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function test_save_mapping_creates_and_round_trips(): void
    {
        $name = $this->uniqueName();
        $cols = [
            ['field' => 'subject', 'header' => 'Subject', 'included' => true],
            ['field' => 'status',  'header' => 'Status'],  // 'included' omitted -> defaults true
        ];

        $id = ie_save_mapping($name, 'risk', 'both', $cols);
        self::assertIsInt($id);
        self::assertGreaterThan(0, $id);

        $m = ie_get_mapping($id);
        self::assertNotNull($m);
        self::assertSame($name, $m['name']);
        self::assertSame('risk', $m['entity']);
        self::assertSame('both', $m['direction']);
        // 'included' defaulted to true for the status column.
        self::assertTrue($m['columns'][1]['included']);
        self::assertSame(0, $this->isDefault($id), 'a fresh mapping is not the default');
    }

    public function test_save_mapping_rejects_empty_name(): void
    {
        self::assertFalse(ie_save_mapping('   ', 'risk', 'both', []));
    }

    public function test_save_mapping_rejects_duplicate_name_on_create(): void
    {
        $name = $this->uniqueName();
        self::assertNotFalse(ie_save_mapping($name, 'risk', 'both', []));

        // A second create with the same name (no id) is refused by the UNIQUE key.
        self::assertFalse(ie_save_mapping($name, 'risk', 'both', []));
    }

    public function test_save_mapping_updates_in_place_by_id(): void
    {
        $id = ie_save_mapping($this->uniqueName(), 'risk', 'both', [['field' => 'subject', 'header' => 'Subject']]);
        self::assertNotFalse($id);

        $newName = $this->uniqueName('IE-upd');
        $out = ie_save_mapping($newName, 'risk', 'import', [['field' => 'notes', 'header' => 'Notes']], $id);
        self::assertSame($id, $out, 'update returns the same id');

        $m = ie_get_mapping($id);
        self::assertSame($newName, $m['name']);
        self::assertSame('import', $m['direction']);
        self::assertSame('notes', $m['columns'][0]['field']);
    }

    public function test_get_mappings_filters_by_entity(): void
    {
        $riskName  = $this->uniqueName();
        $assetName = $this->uniqueName();
        ie_save_mapping($riskName,  'risk',  'both', []);
        ie_save_mapping($assetName, 'asset', 'both', []);

        $riskNames = array_column(ie_get_mappings('risk'), 'name');
        self::assertContains($riskName, $riskNames);
        self::assertNotContains($assetName, $riskNames, 'asset mapping excluded by entity filter');

        $allNames = array_column(ie_get_mappings(null), 'name');
        self::assertContains($riskName, $allNames);
        self::assertContains($assetName, $allNames);
    }

    public function test_get_mapping_by_name_and_bad_id_misses_return_null(): void
    {
        self::assertNull(ie_get_mapping_by_name($this->uniqueName('IE-missing')));
        self::assertNull(ie_get_mapping(0));
        self::assertNull(ie_get_mapping(-1));
    }

    public function test_normalize_mapping_decodes_and_cleans_columns(): void
    {
        $row = [
            'id'    => 5,
            'name'  => 'Synthetic',
            'value' => json_encode(['columns' => [
                ['field' => 'subject', 'header' => 'Subject', 'included' => true],
                ['field' => '',        'header' => 'Bad'],      // empty field -> dropped
                'not-an-array',                                  // non-array  -> dropped
            ]]),
        ];

        $m = ie_normalize_mapping($row);
        self::assertSame(5, $m['id']);
        self::assertSame('Synthetic', $m['name']);
        self::assertSame('risk', $m['entity'], 'entity defaults to risk');
        self::assertCount(1, $m['columns'], 'malformed columns were dropped');
        self::assertSame('subject', $m['columns'][0]['field']);
    }

    public function test_normalize_mapping_returns_null_for_false_row(): void
    {
        self::assertNull(ie_normalize_mapping(false));
    }

    public function test_set_default_mapping_clears_previous_default(): void
    {
        $id1 = ie_save_mapping($this->uniqueName(), 'risk', 'both', []);
        $id2 = ie_save_mapping($this->uniqueName(), 'risk', 'both', []);

        ie_set_default_mapping($id1);
        self::assertSame(1, $this->isDefault($id1));

        // Promoting the second must demote the first (only one default per entity).
        ie_set_default_mapping($id2);
        self::assertSame(0, $this->isDefault($id1));
        self::assertSame(1, $this->isDefault($id2));
    }

    public function test_get_default_mapping_synthesizes_when_none_stored(): void
    {
        // An entity with no rows falls back to an in-memory default.
        $m = ie_get_default_mapping('entity_with_no_rows_' . substr(uniqid('', true), -6));
        self::assertSame(0, $m['id']);
        self::assertSame('Default', $m['name']);
        self::assertNotEmpty($m['columns']);
    }

    public function test_delete_mapping_contract(): void
    {
        $id = ie_save_mapping($this->uniqueName(), 'risk', 'both', []);

        self::assertTrue(delete_mapping($id));
        self::assertFalse(delete_mapping($id), 'a second delete finds no row -> false');
        self::assertFalse(delete_mapping(0), 'id <= 0 -> false');
    }
}
