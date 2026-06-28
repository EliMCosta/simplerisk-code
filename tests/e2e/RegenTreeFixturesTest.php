<?php
/**
 * Golden-fixture generator for the DataTables tree endpoint contract.
 *
 * NOT part of the normal test run (opt-in via @group regen; excluded by default
 * in tests/bin/test). Hit each DataTables tree data endpoint as the test admin, capture
 * its live {status, contentType, envelope}, and (re)write the committed golden
 * under tests/fixtures/tree/. Run after an INTENTIONAL contract change so the
 * new shape becomes the baseline TreeEndpointContractTest checks against:
 *
 *     bin/regen-tree-fixtures        # host wrapper
 *
 * The envelope is DERIVED from the live JSON (a bare array -> "list"; an object
 * -> "object:" + its keys), so no hand-maintenance — only a real shape change
 * moves the golden.
 */
declare(strict_types=1);

/**
 * @group regen
 */
final class RegenTreeFixturesTest extends E2ETestCase
{
    private function fixtureDir(): string
    {
        return __DIR__ . '/../fixtures/tree';
    }

    /** Inverse of E2ETestCase::assertEnvelope(): derive the shape string. */
    private function deriveEnvelope(array $decoded): string
    {
        if ($decoded === [] || array_is_list($decoded)) {
            return 'list';
        }
        return 'object:' . implode(',', array_keys($decoded));
    }

    /** Bare Content-Type (drop the "; charset=..." suffix). */
    private function bareContentType(string $contentType): string
    {
        $bare = strstr($contentType, ';', true);
        return trim($bare !== false ? $bare : $contentType);
    }

    /**
     * @dataProvider endpoints
     */
    public function test_capture_golden_for_endpoint(string $slug, string $path): void
    {
        [$code, $contentType, $body, $err] = $this->authedGet($path);
        self::assertSame('', $err, "curl error hitting {$path}: {$err}");
        self::assertJson($body, "expected JSON body for {$path}");

        $golden = [
            'status'      => $code,
            'contentType' => $this->bareContentType($contentType),
            'envelope'    => $this->deriveEnvelope((array) json_decode($body, true)),
        ];

        if (!is_dir($this->fixtureDir())) {
            mkdir($this->fixtureDir(), 0777, true);
        }
        $file = $this->fixtureDir() . "/{$slug}.json";
        file_put_contents(
            $file,
            json_encode($golden, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        self::assertFileExists($file, "failed to write golden for {$slug}");
        echo "\n  captured {$slug}: HTTP {$golden['status']} | {$golden['contentType']} | {$golden['envelope']}";
    }

    public static function endpoints(): array
    {
        // Same catalog as TreeEndpointContractTest — kept in sync deliberately.
        // require_once because this data provider runs during discovery, before
        // PHPUnit has necessarily loaded the (alphabetically later) contract file.
        require_once __DIR__ . '/TreeEndpointContractTest.php';
        return TreeEndpointContractTest::endpoints();
    }
}
