<?php
/**
 * Integration test (read-only): risk getter functions.
 *   get_risk_by_id($id)             :10554   (subtracts 1000; big JOIN)
 *   get_calculated_risk_by_id($id)  :9306    (wraps get_risk_by_id)
 *
 * These only READ, so the per-test transaction is harmless here. If no risk is
 * seeded in the dev DB the tests skip rather than fail. Separation/team filtering
 * can hide a risk from the CLI (no session user), so structure is only asserted
 * when a row is actually returned.
 */
declare(strict_types=1);

final class RiskReadTest extends IntegrationTestCase
{
    /**
     * Display IDs are internal id + 1000 (see get_risk_by_id). Return a real
     * internal id if one exists, else null so the test can skip.
     */
    private function anyInternalRiskId(): ?int
    {
        $stmt = $this->txdb->query('SELECT id FROM risk_scoring ORDER BY id LIMIT 1');
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function test_get_calculated_risk_by_id_is_numeric(): void
    {
        $id = $this->anyInternalRiskId();
        if ($id === null) {
            static::markTestSkipped('No risks seeded in the dev DB; nothing to read.');
        }

        $score = get_calculated_risk_by_id($id + 1000);
        self::assertTrue(
            is_numeric($score),
            'calculated_risk should be numeric, got: ' . var_export($score, true)
        );
    }

    public function test_get_risk_by_id_returns_array_without_fataling(): void
    {
        $id = $this->anyInternalRiskId();
        if ($id === null) {
            static::markTestSkipped('No risks seeded in the dev DB; nothing to read.');
        }

        $result = get_risk_by_id($id + 1000);
        self::assertIsArray($result, 'get_risk_by_id must return an array');

        if (!empty($result)) {
            // Every risk row carries these JOINed columns.
            self::assertArrayHasKey('subject', $result[0]);
            self::assertArrayHasKey('calculated_risk', $result[0]);
        }
    }
}
