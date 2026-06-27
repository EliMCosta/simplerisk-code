<?php
/**
 * Unit tests for the ComplianceForge SCF Extra's CSV + header-mapping helpers
 * (extras/complianceforgescf/includes/csv.php).
 *
 * Covers scf_clean_header() (multiline -> " / " joined), scf_first_segment(),
 * the scf_header_matches_keyword() matcher, and scf_open_csv()/scf_read_header()
 * (delimiter, BOM strip, missing-file). No DB.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ScfCsvTest extends TestCase
{
    use LoadsExtras;

    /** @var string[] */
    private array $tmpFiles = [];

    public static function setUpBeforeClass(): void
    {
        self::loadExtra('complianceforgescf', 'csv.php');
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $f) {
            @unlink($f);
        }
        $this->tmpFiles = [];
    }

    public function test_clean_header_joins_multiline_and_trims(): void
    {
        self::assertSame('Control', scf_clean_header('Control'));
        self::assertSame('A / B / C', scf_clean_header("A\r\nB\rC\n"));
        self::assertSame('Keep', scf_clean_header("  Keep  "));
        // Blank lines are dropped, not turned into empty segments.
        self::assertSame('X / Y', scf_clean_header("X\n\n  \nY"));
    }

    public function test_first_segment_lowercases_first_part(): void
    {
        self::assertSame('risk', scf_first_segment('Risk / Owner'));
        self::assertSame('solo', scf_first_segment('Solo'));
        self::assertSame('ctrl', scf_first_segment("Ctrl / Extra / Bits"));
    }

    public function test_header_matches_keyword_rules(): void
    {
        // Exact whole-label match.
        self::assertTrue(scf_header_matches_keyword('control', 'control'));
        // Matches a single " / "-delimited segment.
        self::assertTrue(scf_header_matches_keyword('risk / owner', 'owner'));
        // Substring allowed only for keywords >= 5 chars.
        self::assertTrue(scf_header_matches_keyword('control identifier', 'control'));
        self::assertFalse(scf_header_matches_keyword('category', 'cat')); // short, substring only
        // No match.
        self::assertFalse(scf_header_matches_keyword('owner', 'control'));
        // Empty keyword never matches.
        self::assertFalse(scf_header_matches_keyword('anything', ''));
    }

    public function test_read_header_parses_and_cleans(): void
    {
        $path = $this->writeCsv("Control ID,Control Description\nCG-1,Foo\n");
        self::assertSame(['Control ID', 'Control Description'], scf_read_header($path));
    }

    public function test_read_header_strips_bom(): void
    {
        $path = $this->writeCsv("\xEF\xBB\xBFControl\nCG-1\n");
        self::assertSame(['Control'], scf_read_header($path));
    }

    public function test_open_and_read_header_return_false_for_missing_file(): void
    {
        $missing = sys_get_temp_dir() . '/sr_scf_nosuch_' . substr(uniqid('', true), -8) . '.csv';
        self::assertFalse(@scf_open_csv($missing));
        self::assertFalse(@scf_read_header($missing));
    }

    private function writeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sr_scf_');
        file_put_contents($path, $content);
        $this->tmpFiles[] = $path;
        return $path;
    }
}
