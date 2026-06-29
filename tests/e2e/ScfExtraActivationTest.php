<?php
/**
 * E2E regression for the ComplianceForge SCF Extra.
 *
 * SCF is already activated on this image (extra_scf=1, ~1468 controls indexed).
 * The admin page render + the presence of the SCF root framework and indexed
 * controls are asserted. The bulk re-import (~151k rows) is NEVER triggered — it
 * is slow and timeout-prone; activation/import idempotency is out of scope.
 *
 * extra_scf is snapshotted/restored (force-on for the run).
 */
declare(strict_types=1);

final class ScfExtraActivationTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    private array $snapshot = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->snapshot = $this->snapshotSettings('extra_scf');
        $this->writeSetting('extra_scf', '1');
    }

    protected function tearDown(): void
    {
        $this->restoreSettings($this->snapshot);
        parent::tearDown();
    }

    public function test_scf_admin_page_renders(): void
    {
        [$code, , $body, $err] = $this->authedGet('/admin/securecontrolsframework.php');
        self::assertSame('', $err, "curl error: {$err}");
        self::assertSame(200, $code);
        self::assertStringContainsString('Secure Controls Framework', $body, 'SCF admin page did not render');
    }

    public function test_scf_root_framework_and_controls_are_present(): void
    {
        // frameworks.name is encrypted at rest (ENC1:), so a SQL LIKE on the name
        // misses the SCF root framework; decrypt each name and match the substring.
        $names = $this->selectDecryptedColumn('frameworks', 'name');
        $root = count(array_filter($names, fn (string $n) => str_contains($n, 'Secure Controls Framework')));
        self::assertGreaterThan(0, $root, 'expected the SCF root framework');
        $indexed = $this->countScalar("SELECT COUNT(*) FROM scf_control_index");
        self::assertGreaterThan(0, $indexed, 'expected SCF controls in scf_control_index');
    }
}
