<?php
/**
 * E2E regression for the Import-Export Extra.
 *
 * Export: POST risks_export=1 streams a text/csv with a header row. Import: the
 * two-step flow (ie_action=upload staging the CSV in $_SESSION, then ie_action=
 * import running it) creates risks via submit_risk + submit_risk_scoring. The two
 * POSTs MUST share one cookie jar (the staged path lives in the session). The
 * default mapping (seeded on enable) maps the "Subject" header, so no mapping is
 * authored. import_export is snapshotted/restored; imported risks are swept.
 */
declare(strict_types=1);

final class ImportExportExtraCsvTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    private array $snapshot = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->snapshot = $this->snapshotSettings('import_export');
        $this->writeSetting('import_export', '1');
    }

    protected function tearDown(): void
    {
        $this->restoreSettings($this->snapshot);
        $this->sweepE2ERisks(); // imported risks use the E2E_RISK_ subject prefix
        parent::tearDown();
    }

    public function test_export_risks_returns_text_csv(): void
    {
        [$code, $type, $body] = $this->authedPost('/admin/importexport.php', ['risks_export' => 1]);
        self::assertSame(200, $code, "export returned {$code}");
        self::assertStringContainsString('text/csv', $type, "expected a CSV content-type, got {$type}");
        $firstLine = strtok((string) $body, "\n");
        self::assertStringContainsString('Subject', $firstLine ?: (string) $body, 'CSV header row missing the Subject column');
    }

    public function test_import_csv_creates_risks(): void
    {
        $subject = $this->uniqueSubject('IMP');
        // The upload validates the .csv extension, so the temp file must end in .csv.
        $fixture = rtrim(sys_get_temp_dir(), '/') . '/e2ecsv_' . uniqid() . '.csv';
        try {
            file_put_contents($fixture, "Subject\n{$subject}\n");

            // Step 1: upload (multipart) — stages the CSV path in the session.
            [$code1] = $this->multipartPost(
                '/admin/importexport.php',
                ['ie_action' => 'upload', 'ie_import_mapping' => 0],
                'ie_csv',
                $fixture
            );
            self::assertSame(302, $code1, 'upload should PRG-redirect (staging the CSV)');

            // Step 2: confirm import (same cookie jar → same staged session).
            [$code2] = $this->authedPost('/admin/importexport.php', ['ie_action' => 'import']);
            self::assertSame(302, $code2, 'import should PRG-redirect');

            // The imported subject is encrypted at rest under the Encryption Extra —
            // match by decrypting the stored column instead of a plaintext SQL equality.
            $n = $this->countByDecryptedColumn('risks', 'subject', $subject);
            self::assertSame(1, $n, "imported risk '{$subject}' was not created");
        } finally {
            @unlink($fixture);
        }
    }
}
