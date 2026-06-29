<?php
/**
 * Shared compliance seeding + cleanup for the e2e compliance/audit journey
 * tests. To be used by a class that also extends E2ETestCase (it relies on
 * authedPost / authedDelete / decodeJson).
 *
 * Seeds a framework -> control -> test chain over the real /api/v2 CRUD routes
 * (createFrameworkCrud / createControlCrud / createTest), each returning
 * {status:201, data:{id}}. A control needs only short_name; a test needs name +
 * framework_control_id. We do NOT map the control to the framework (the mapping
 * is a separate step the initiate('test', ...) path does not require), keeping
 * the seed minimal.
 *
 * Cleanup tears the chain down via the DELETE routes in reverse order. DELETE is
 * not csrf-checked (csrf_check only runs on POST), so authedDelete needs only the
 * session cookie. A safety-net sweep removes any E2E_-prefixed rows left behind.
 */
declare(strict_types=1);

trait ComplianceSeedTrait
{
    private const PREFIX = 'E2E_';

    /** @var array{frameworks:int[], controls:int[], tests:int[]} ids created during a test. */
    private array $createdCompliance = ['frameworks' => [], 'controls' => [], 'tests' => []];

    /**
     * Seed a fresh framework->control->test chain. Returns
     * ['framework' => fwId, 'control' => ctrlId, 'test' => testId]. All three ids
     * are tracked for tearDown cleanup.
     */
    protected function seedFrameworkControlTest(string $tag = 'FCT'): array
    {
        $uniq = uniqid();
        $fwId = $this->createFramework(self::PREFIX . "FW_{$tag}_{$uniq}");
        $ctrlId = $this->createControl(self::PREFIX . "CTRL_{$tag}_{$uniq}");
        $testId = $this->createTest(self::PREFIX . "TEST_{$tag}_{$uniq}", $ctrlId);
        return ['framework' => $fwId, 'control' => $ctrlId, 'test' => $testId];
    }

    protected function createFramework(string $name): int
    {
        [$code, , $body] = $this->authedPost('/api/v2/governance/frameworks', ['name' => $name]);
        self::assertSame(201, $code, "create framework returned {$code}: {$body}");
        $id = (int) ($this->decodeJson($body, 'create framework')['data']['id'] ?? 0);
        self::assertGreaterThan(0, $id, "expected data.id for framework: {$body}");
        $this->createdCompliance['frameworks'][] = $id;
        return $id;
    }

    protected function createControl(string $shortName): int
    {
        [$code, , $body] = $this->authedPost('/api/v2/governance/controls', ['short_name' => $shortName]);
        self::assertSame(201, $code, "create control returned {$code}: {$body}");
        $id = (int) ($this->decodeJson($body, 'create control')['data']['id'] ?? 0);
        self::assertGreaterThan(0, $id, "expected data.id for control: {$body}");
        $this->createdCompliance['controls'][] = $id;
        return $id;
    }

    protected function createTest(string $name, int $controlId): int
    {
        [$code, , $body] = $this->authedPost('/api/v2/compliance/tests', [
            'name' => $name,
            'framework_control_id' => $controlId,
        ]);
        self::assertSame(201, $code, "create test returned {$code}: {$body}");
        $id = (int) ($this->decodeJson($body, 'create test')['data']['id'] ?? 0);
        self::assertGreaterThan(0, $id, "expected data.id for test: {$body}");
        $this->createdCompliance['tests'][] = $id;
        return $id;
    }

    /** Tear down everything tracked, in reverse dependency order. */
    protected function tearDownCompliance(): void
    {
        foreach ($this->createdCompliance['tests'] as $id) {
            $this->authedDelete('/api/v2/compliance/tests/' . $id);
        }
        foreach ($this->createdCompliance['controls'] as $id) {
            $this->authedDelete('/api/v2/governance/controls/' . $id);
        }
        foreach ($this->createdCompliance['frameworks'] as $id) {
            $this->authedDelete('/api/v2/governance/frameworks/' . $id);
        }
        // Safety net: any E2E_-prefixed rows that escaped tracking.
        $this->sweepE2ECompliance();
    }

    private function sweepE2ECompliance(): void
    {
        $db = db_open();
        // Tests reference controls; controls may reference frameworks. Delete
        // children first, then parents, by name prefix.
        $db->exec("DELETE FROM framework_control_mappings WHERE reference_name = 'E2E'");
        $db->exec("DELETE FROM framework_control_tests WHERE name LIKE '" . self::PREFIX . "%'");
        $db->exec("DELETE FROM framework_controls WHERE short_name LIKE '" . self::PREFIX . "%'");
        $db->exec("DELETE FROM frameworks WHERE name LIKE '" . self::PREFIX . "%'");
        // Initiated audits copy the test name; clean their results then the audits.
        $db->exec("DELETE FROM framework_control_test_results WHERE test_audit_id IN "
            . "(SELECT id FROM framework_control_test_audits WHERE name LIKE '" . self::PREFIX . "%')");
        $db->exec("DELETE FROM framework_control_test_audits WHERE name LIKE '" . self::PREFIX . "%'");
        db_close($db);
    }
}
