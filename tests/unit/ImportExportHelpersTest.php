<?php
/**
 * Unit tests for the Import-Export Extra's pure (no-DB) helpers
 * (extras/import-export/includes/{import,export,fields,csv}.php).
 *
 * Covers the request-shaping functions that sit between CSV parsing and the DB:
 *   - ie_field_to_column_map()   header→column resolution
 *   - ie_resolve_field()         raw-cell coercion (passthrough kinds only; the
 *                                lookup/user kinds need a DB — ImportExportImportTest)
 *   - ie_build_create_args()     submit_risk() positional arg builder
 *   - ie_build_mitigation_post() mitigation field selector
 *   - ie_normalize_standard_date()/ie_normalize_standard_datetime() date coercion
 *   - ie_type_map()/ie_format_risk_cell()  export shaping (the streaming
 *                                functions themselves exit() -> e2e only)
 *
 * No DB writes. In-container, functions.php is bootstrapped so try_decrypt /
 * get_default_date_format are available (same as ImportExportCsvTest).
 *
 * ie_value_empty() and ie_split_multi() are already covered in
 * ImportExportCsvTest and are not duplicated here.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ImportExportHelpersTest extends TestCase
{
    use LoadsExtras;

    public static function setUpBeforeClass(): void
    {
        self::loadExtra('import-export', 'csv.php', 'fields.php', 'import.php', 'export.php');
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function test_field_to_column_map_is_case_insensitive_and_respects_flags(): void
    {
        $mapping = ['columns' => [
            ['field' => 'subject', 'header' => 'Subject', 'included' => true],
            ['field' => 'status',  'header' => '',         'included' => true],  // blank header -> ignored
            ['field' => 'notes',   'header' => 'Notes',    'included' => false], // excluded
            ['field' => 'category','header' => 'Category', 'included' => true],  // not in CSV -> no entry
        ]];

        // 'SUBJECT' (upper) in the header still matches the 'Subject' mapping
        // header via mb_strtolower.
        $map = ie_field_to_column_map(['ID', 'SUBJECT', 'Notes'], $mapping);

        self::assertSame(['subject' => 1], $map);
    }

    public static function passthroughKinds(): array
    {
        return [
            'text'     => ['text'],
            'longtext' => ['longtext'],
            'date'     => ['date'],
            'int'      => ['int'],
            'readonly' => ['readonly'],
        ];
    }

    /**
     * @dataProvider passthroughKinds
     */
    public function test_resolve_field_passthrough_kinds_return_trimmed_raw(string $kind): void
    {
        $val = ie_resolve_field('any', ['kind' => $kind], "  hello  ");
        self::assertSame('hello', $val);
    }

    public function test_resolve_field_multi_text_splits_on_separators(): void
    {
        // multi_text (tags / affected assets / mitigation controls) splits a cell
        // into trimmed names without any id lookup.
        self::assertSame(['a', 'b', 'c'], ie_resolve_field('tags', ['kind' => 'multi_text'], 'a, b; c'));
        self::assertSame([], ie_resolve_field('tags', ['kind' => 'multi_text'], '   '));
    }

    public function test_build_create_args_defaults_and_session_submitted_by(): void
    {
        $_SESSION['uid'] = 7;

        $args = ie_build_create_args([]);

        self::assertSame('New', $args[0]);          // status
        self::assertSame('(Imported)', $args[1]);   // subject
        self::assertSame(0, $args[14]);             // project_id
        self::assertSame(7, $args[15]);             // submitted_by = $_SESSION['uid']
        self::assertFalse($args[21]);               // subject_order (skip reindex)
    }

    public function test_build_create_args_passes_through_set_values_and_coerces_arrays(): void
    {
        $_SESSION['uid'] = 9;

        $args = ie_build_create_args([
            'status'   => 'Mitigated',
            'subject'  => 'Real Subject',
            'location' => 'not-an-array', // must be coerced to []
        ]);

        self::assertSame('Mitigated', $args[0]);
        self::assertSame('Real Subject', $args[1]);
        self::assertSame([], $args[5]);   // location coerced
        self::assertSame(9, $args[15]);
    }

    public function test_build_mitigation_post_is_null_without_mitigation_data(): void
    {
        self::assertNull(ie_build_mitigation_post([]));
        self::assertNull(ie_build_mitigation_post(['mitigation_cost' => 0])); // 0 counts as empty
        self::assertNull(ie_build_mitigation_post(['current_solution' => '']));
    }

    public function test_build_mitigation_post_keeps_only_non_empty_fields(): void
    {
        $post = ie_build_mitigation_post([
            'mitigation_cost'   => 3,    // kept
            'current_solution'  => '',   // dropped (empty)
            'mitigation_effort' => 0,    // dropped (empty)
            'planning_date'     => '2026-06-26', // kept
        ]);

        self::assertSame([
            'mitigation_cost' => 3,
            'planning_date'   => '2026-06-26',
        ], $post);
    }

    public function test_normalize_standard_date(): void
    {
        self::assertSame('2026-06-26', ie_normalize_standard_date('2026-06-26'));
        self::assertSame('', ie_normalize_standard_date(''));
        self::assertSame('', ie_normalize_standard_date('notadate'));
    }

    public function test_normalize_standard_datetime(): void
    {
        self::assertSame('2026-06-26 10:00:00', ie_normalize_standard_datetime('2026-06-26 10:00:00'));
        self::assertSame('', ie_normalize_standard_datetime(''));
        self::assertSame('', ie_normalize_standard_datetime('notadate'));
    }

    public function test_type_map_covers_all_types(): void
    {
        $map = ie_type_map();
        self::assertCount(11, $map);
        self::assertSame(['entity' => 'risk', 'table' => null], $map['risks']);
        self::assertSame(['entity' => 'mitigation', 'table' => 'mitigations'], $map['mitigations']);
        self::assertSame('asset', $map['assets']['entity']);
    }

    public function test_format_risk_cell_risk_id_adds_thousand_offset(): void
    {
        self::assertSame('1005', ie_format_risk_cell('risk_id', ['risk_id' => 5]));
    }

    public function test_format_risk_cell_decrypts_text_columns_and_passes_others_through(): void
    {
        // subject/assessment/notes (and the mitigation text columns) are run
        // through try_decrypt; without the Encryption Extra that is the identity
        // transform, so plaintext passes through unchanged.
        self::assertSame('My Subject', ie_format_risk_cell('subject', ['subject' => 'My Subject']));
        self::assertSame('Notes', ie_format_risk_cell('notes', ['notes' => 'Notes']));
        // A plain (non-encrypted) column is returned verbatim.
        self::assertSame('New', ie_format_risk_cell('status', ['status' => 'New']));
    }
}
