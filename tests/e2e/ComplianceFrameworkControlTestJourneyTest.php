<?php
/**
 * E2e journey for the compliance CRUD chain: create a framework, then a control,
 * then a test on that control — each over its real /api/v2 CRUD route — and
 * verify each write landed in the DB (the source of truth). Also locks down the
 * duplicate-framework-name rejection (HTTP 409).
 *
 * Endpoints (Slim POST, session-authenticated, csrf-gated — authedPost handles
 * the token):
 *   POST /api/v2/governance/frameworks  -> createFrameworkCrud (name)         -> 201 {data.id}  (id = frameworks.value)
 *   POST /api/v2/governance/controls    -> createControlCrud (short_name)     -> 201 {data.id}  (id = framework_controls.id)
 *   POST /api/v2/compliance/tests       -> createTest (name, framework_control_id) -> 201 {data.id}
 *
 * Seeding + cleanup via ComplianceSeedTrait. No transaction rollback in e2e:
 * tearDown deletes the chain (DELETE routes) and sweeps any E2E_ residue.
 */
declare(strict_types=1);

final class ComplianceFrameworkControlTestJourneyTest extends E2ETestCase
{
    use ComplianceSeedTrait;

    protected function tearDown(): void
    {
        $this->tearDownCompliance();
        parent::tearDown();
    }

    public function test_create_framework_control_test_chain(): void
    {
        ['framework' => $fwId, 'control' => $ctrlId, 'test' => $testId] = $this->seedFrameworkControlTest('Chain');

        // frameworks PK is `value`; controls/tests PK is `id`.
        self::assertSame(1, $this->countWhere('frameworks', 'value = ?', [$fwId]), 'framework row must exist');
        self::assertSame(1, $this->countWhere('framework_controls', 'id = ?', [$ctrlId]), 'control row must exist');
        self::assertSame(1, $this->countWhere('framework_control_tests', 'id = ?', [$testId]), 'test row must exist');

        // The test is linked to the control it was created under.
        $linkedControl = (int) $this->fetchScalar('SELECT framework_control_id FROM framework_control_tests WHERE id = ?', [$testId]);
        self::assertSame($ctrlId, $linkedControl, 'the test must reference its parent control');
    }

    public function test_duplicate_framework_name_is_rejected(): void
    {
        $name = 'E2E_FW_DUP_' . uniqid();

        $fwId = $this->createFramework($name); // first create succeeds (201), tracked for cleanup
        self::assertGreaterThan(0, $fwId);

        // Second create with the same name must be rejected with 409.
        [$code, , $body] = $this->authedPost('/api/v2/governance/frameworks', ['name' => $name]);
        self::assertSame(409, $code, "duplicate framework name should be 409, got {$code}: {$body}");
    }

    // ---------- helpers ----------

    private function countWhere(string $table, string $where, array $params): int
    {
        $db = db_open();
        $stmt = $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE {$where}");
        $stmt->execute($params);
        $c = (int) $stmt->fetchColumn();
        db_close($db);
        return $c;
    }

    private function fetchScalar(string $sql, array $params)
    {
        $db = db_open();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $v = $stmt->fetchColumn();
        db_close($db);
        return $v;
    }
}
