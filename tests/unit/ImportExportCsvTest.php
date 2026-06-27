<?php
/**
 * Unit tests for the Import-Export Extra's CSV + value helpers
 * (extras/import-export/includes/{csv,fields,import}.php).
 *
 * Covers the pure parser plumbing: ie_split_multi() separator handling,
 * ie_value_empty()'s emptiness rules, and ie_open_csv()/ie_read_header()'s file
 * handling (delimiter, BOM stripping, header trimming, missing-file). No DB.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ImportExportCsvTest extends TestCase
{
    use LoadsExtras;

    /** @var string[] temp files removed in tearDown */
    private array $tmpFiles = [];

    public static function setUpBeforeClass(): void
    {
        self::loadExtra('import-export', 'csv.php', 'fields.php', 'import.php');
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $f) {
            @unlink($f);
        }
        $this->tmpFiles = [];
    }

    public static function multiValues(): array
    {
        return [
            'empty'         => ['', []],
            'single'        => ['solo', ['solo']],
            'comma'         => ['a,b,c', ['a', 'b', 'c']],
            'mixed seps'    => ['a; b | c, d', ['a', 'b', 'c', 'd']],
            'trimmed'       => ['  a  ,  b ', ['a', 'b']],
            'drops empties' => ['a,, ,b', ['a', 'b']],
        ];
    }

    /**
     * @dataProvider multiValues
     */
    public function test_split_multi(string $text, array $expected): void
    {
        self::assertSame($expected, ie_split_multi($text));
    }

    public function test_value_empty_rules(): void
    {
        self::assertTrue(ie_value_empty(''));
        self::assertTrue(ie_value_empty('0'));
        self::assertTrue(ie_value_empty(0));
        self::assertTrue(ie_value_empty(null));
        self::assertTrue(ie_value_empty('   '));
        self::assertTrue(ie_value_empty([]));
        self::assertTrue(ie_value_empty(['', '0', null]));

        self::assertFalse(ie_value_empty('x'));
        self::assertFalse(ie_value_empty(['x']));
        self::assertFalse(ie_value_empty('00')); // string "00" is not "0"
    }

    public function test_read_header_parses_and_trims_columns(): void
    {
        $path = $this->writeCsv("ID, Subject ,Owner\n1, My Risk, 2\n");
        self::assertSame(['ID', 'Subject', 'Owner'], ie_read_header($path));
    }

    public function test_read_header_strips_utf8_bom(): void
    {
        // A leading UTF-8 BOM must not become part of the first column name.
        $path = $this->writeCsv("\xEF\xBB\xBFID,Subject\n1,X\n");
        self::assertSame(['ID', 'Subject'], ie_read_header($path));
    }

    public function test_read_header_respects_quoted_fields(): void
    {
        $path = $this->writeCsv("\"ID\",\"Subject Name\"\n1,X\n");
        self::assertSame(['ID', 'Subject Name'], ie_read_header($path));
    }

    public function test_open_and_read_header_return_false_for_missing_file(): void
    {
        $missing = sys_get_temp_dir() . '/sr_no_such_' . substr(uniqid('', true), -8) . '.csv';
        // @ suppresses fopen()'s "No such file" warning; the contract is the
        // false return, not the warning.
        self::assertFalse(@ie_open_csv($missing));
        self::assertFalse(@ie_read_header($missing));
    }

    private function writeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sr_csv_');
        file_put_contents($path, $content);
        $this->tmpFiles[] = $path;
        return $path;
    }
}
