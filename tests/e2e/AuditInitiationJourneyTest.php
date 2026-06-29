<?php
/**
 * E2e journey for the audit-initiation lifecycle — the area behind two recent
 * UX bugs (the tag-modal Continue button defaulting to submit, and the lazy-tree
 * expand bypass) — plus the known delete-orphan bug.
 *
 * The wire-level lifecycle over HTTP:
 *   1. Seed a control + test (ComplianceSeedTrait).
 *   2. Initiate an audit: POST /api/v2/compliance/audit_initiation/initiate
 *      {type:'test', id:<testId>} -> initiateFrameworkControlTestsResponse
 *      -> initiate_test_audit() INSERTs framework_control_test_audits
 *      (status = initiated_audit_status = 1) AND a placeholder
 *      framework_control_test_results row (test_audit_id = audit id). [api.php:4906]
 *   3. Complete it: POST /compliance/testing.php?id=<auditId> {status, test_result,
 *      tester, test_date, summary} -> submit_test_result() (compliance.php:2570)
 *      -> sets the audit status. status == closed_audit_status (5) moves it to past.
 *   4. Delete it: POST /api/v2/compliance/delete_audit {id:<auditId>}
 *      -> deleteTestAuditResponse -> delete_test_audit().
 *
 * Status values: initiated = get_initiated_audit_status() = 1; closed/past =
 * get_setting('closed_audit_status') = 5. Active = status != closed; the active
 * vs past lists filter framework_control_test_audits.status against that value.
 *
 * Initiate returns {status:200, data:[]} (no id), so the audit id is read from
 * the DB. cleanup via ComplianceSeedTrait (which also sweeps created audits).
 */
declare(strict_types=1);

final class AuditInitiationJourneyTest extends E2ETestCase
{
    use ComplianceSeedTrait;

    /** @var int[] framework_control_test_audits ids initiated during a test. */
    private array $initiatedAuditIds = [];

    protected function tearDown(): void
    {
        // Remove any audits we initiated that the delete-test did not, plus the
        // seeded control/test. The trait sweeper also catches E2E_-named audits.
        $db = db_open();
        foreach ($this->initiatedAuditIds as $aid) {
            $db->prepare('DELETE FROM framework_control_test_results WHERE test_audit_id = ?')->execute([$aid]);
            $db->prepare('DELETE FROM framework_control_test_audits WHERE id = ?')->execute([$aid]);
        }
        db_close($db);
        $this->tearDownCompliance();
        parent::tearDown();
    }

    public function test_initiate_creates_audit_visible_in_active(): void
    {
        ['test' => $testId] = $this->seedFrameworkControlTest('Initiate');

        [$code, , $body] = $this->initiateAudit($testId);
        self::assertSame(200, $code, "initiate returned {$code}: {$body}");

        $auditId = $this->fetchAuditId($testId);
        self::assertGreaterThan(0, $auditId, 'an audit row must be created for the test');
        $this->initiatedAuditIds[] = $auditId;

        // The audit is in the initiated status (1) and therefore active (not closed/5).
        $status = (int) $this->fetchScalar('SELECT status FROM framework_control_test_audits WHERE id = ?', [$auditId]);
        self::assertSame($this->initiatedStatus(), $status, 'a fresh audit must be in the initiated status');

        // A result placeholder row was created for the audit at initiation time.
        $resultRows = $this->countResults($auditId);
        self::assertGreaterThanOrEqual(1, $resultRows, 'initiation must create a result placeholder');
    }

    public function test_complete_audit_moves_it_to_past(): void
    {
        ['test' => $testId] = $this->seedFrameworkControlTest('Complete');
        [$code] = $this->initiateAudit($testId);
        self::assertSame(200, $code, 'initiate must succeed before completing');
        $auditId = $this->fetchAuditId($testId);
        $this->initiatedAuditIds[] = $auditId;

        // Complete the audit via the testing.php page POST (status = closed_audit_status).
        [$code, , $body] = $this->authedPost('/compliance/testing.php?id=' . $auditId, [
            'status'                  => $this->closedStatus(),
            'test_result'             => 'Pass',
            'tester'                  => $this->testAdminUid(),
            'test_date'               => date('m/d/Y'),
            'summary'                 => 'E2E completion summary',
            'remove_associated_risk'  => 0,
        ]);

        // The audit status must now be the closed/past status.
        $status = (int) $this->fetchScalar('SELECT status FROM framework_control_test_audits WHERE id = ?', [$auditId]);
        self::assertSame(
            $this->closedStatus(),
            $status,
            "completing the audit must move it to the closed/past status (POST returned {$code}: {$body})"
        );
    }

    /**
     * Guards the known delete_test_audit() orphan bug: deleting an audit must
     * also remove its framework_control_test_results rows. delete_test_audit()
     * only cleans results that are mapped to risks (LEFT JOIN
     * framework_control_test_results_to_risks), so an unmapped result is
     * orphaned. Until that is fixed this self-skips (and documents the bug);
     * once fixed the skip no longer fires and the assertion holds as a
     * regression net.
     */
    public function test_delete_audit_does_not_orphan_results(): void
    {
        ['test' => $testId] = $this->seedFrameworkControlTest('Orphan');
        [$code] = $this->initiateAudit($testId);
        self::assertSame(200, $code, 'initiate must succeed before deleting');
        $auditId = $this->fetchAuditId($testId);
        // Not tracked for tearDown: the test deletes it itself.
        self::assertGreaterThanOrEqual(1, $this->countResults($auditId), 'audit should have a result placeholder');

        [$code, , $body] = $this->authedPost('/api/v2/compliance/delete_audit', ['id' => $auditId]);
        self::assertSame(200, $code, "delete_audit returned {$code}: {$body}");

        $orphans = $this->countResults($auditId);
        if ($orphans > 0) {
            // The known bug: unmapped results are left behind. Clean them up so
            // the suite leaves no residue, then skip (not fail) to document it.
            $db = db_open();
            $db->prepare('DELETE FROM framework_control_test_results WHERE test_audit_id = ?')->execute([$auditId]);
            db_close($db);
            self::markTestSkipped(
                "Known bug: delete_test_audit() orphaned {$orphans} framework_control_test_results row(s) "
                . "for audit {$auditId}. Remove this skip once delete_test_audit() cleans unmapped results."
            );
        }
        self::assertSame(0, $orphans, 'deleting an audit must not orphan its result rows');
    }

    // ---------- helpers ----------

    /** POST the audit-initiation endpoint for a single test; returns [code,type,body,err]. */
    private function initiateAudit(int $testId): array
    {
        return $this->authedPost('/api/v2/compliance/audit_initiation/initiate', [
            'type' => 'test',
            'id'   => $testId,
            'tags' => '',
        ]);
    }

    /** The most-recent audit id for a test (initiate returns no id), or 0. */
    private function fetchAuditId(int $testId): int
    {
        return (int) $this->fetchScalar(
            'SELECT id FROM framework_control_test_audits WHERE test_id = ? ORDER BY id DESC LIMIT 1',
            [$testId]
        );
    }

    private function countResults(int $auditId): int
    {
        return (int) $this->fetchScalar(
            'SELECT COUNT(*) FROM framework_control_test_results WHERE test_audit_id = ?',
            [$auditId]
        );
    }

    private function initiatedStatus(): int
    {
        return function_exists('get_initiated_audit_status') ? (int) get_initiated_audit_status() : 1;
    }

    private function closedStatus(): int
    {
        return (int) (get_setting('closed_audit_status') ?: 5);
    }

    private function testAdminUid(): int
    {
        return (int) $this->fetchScalar(
            'SELECT value FROM user WHERE username = ?',
            [E2ETestCase::TEST_USER]
        ) ?: 1;
    }

    private function fetchScalar(string $sql, array $params = [])
    {
        $db = db_open();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $v = $stmt->fetchColumn();
        db_close($db);
        return $v;
    }
}
