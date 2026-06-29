<?php
/**
 * E2e: risk DELETION. There is NO /api/v2 DELETE route for risks — deletion is
 * only the admin web page POST /admin/delete_risks.php (admin-gated via
 * render_header_and_sidebar check_admin), which reads $_POST['risks'] as an
 * array of DB ids and hard-deletes each via delete_risks() -> delete_risk()
 * (cascade: risks + closures/comments/files/mgmt_reviews/mitigations/risk_scoring/
 * risk_scoring_history). It returns an HTML page with a flash alert, not JSON.
 *
 * We prove (1) a real risk + its children vanish, (2) the page is admin-gated,
 * and (3) a nonexistent id is handled gracefully (no crash, no phantom rows).
 */
declare(strict_types=1);

final class RiskDeletionJourneyTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    protected function tearDown(): void
    {
        $this->tearDownRisks();
        parent::tearDown();
    }

    public function test_admin_delete_risks_page_removes_risk_and_children(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Delete'));
        $dbId = self::dbId($publicId);
        // Give the risk children so the cascade is observable.
        $this->actionPost('/api/v2/management/risk/saveComment?id=' . $publicId, ['comment' => 'E2E delete comment']);
        $this->actionPost('/api/v2/management/risk/saveMitigation?id=' . $publicId, [
            'planning_strategy' => 1, 'mitigation_effort' => 1, 'mitigation_cost' => 1,
            'mitigation_owner' => $this->testAdminUid(),
            'current_solution' => 'sol', 'security_requirements' => 'req', 'security_recommendations' => 'rec',
        ]);

        // The delete page takes DB ids (not public ids) as risks[].
        [$code, , $body] = $this->authedPost('/admin/delete_risks.php', [
            'delete_risks' => 1,
            'risks'        => [$dbId],
        ]);
        self::assertSame(200, $code, "delete_risks.php returned {$code}: {$body}");

        // DB truth: the risk and every child are gone.
        self::assertSame(0, $this->countScalar('SELECT COUNT(*) FROM risks WHERE id = ?', [$dbId]), 'risk row must be gone');
        self::assertSame(0, $this->countScalar('SELECT COUNT(*) FROM comments WHERE risk_id = ?', [$dbId]), 'comments must be cascade-deleted');
        self::assertSame(0, $this->countScalar('SELECT COUNT(*) FROM mitigations WHERE risk_id = ?', [$dbId]), 'mitigations must be cascade-deleted');
        self::assertSame(0, $this->countScalar('SELECT COUNT(*) FROM risk_scoring WHERE id = ?', [$dbId]), 'risk_scoring must be cascade-deleted');
        self::assertSame(0, $this->countScalar('SELECT COUNT(*) FROM risk_scoring_history WHERE risk_id = ?', [$dbId]), 'risk_scoring_history must be cascade-deleted');

        // It is no longer reachable over HTTP either.
        [$viewCode] = $this->actionGet('/api/v2/risks/' . $publicId);
        self::assertSame(404, $viewCode, 'the deleted risk must 404 on view');
    }

    public function test_delete_risks_page_is_admin_gated(): void
    {
        // Unauthenticated → bounced to login (302; followLocation=false surfaces it).
        [$code] = $this->unauthedGet('/admin/delete_risks.php');
        self::assertNotSame(200, $code, "unauthenticated delete_risks.php must not be 200 (got {$code})");

        // A non-admin (restricted) session is also bounced (check_admin gate).
        $this->ensureRestrictedUser();
        self::assertTrue($this->loginRestrictedSession(), 'restricted session must log in');
        [$code] = $this->authedGetAs('restricted', '/admin/delete_risks.php');
        self::assertNotSame(200, $code, "a non-admin session must not reach delete_risks.php (got {$code})");
    }

    public function test_delete_risks_handles_nonexistent_id_gracefully(): void
    {
        $beforeAll = $this->countScalar("SELECT COUNT(*) FROM risks WHERE subject LIKE '" . self::SUBJECT_PREFIX . "%'");

        // A far-out-of-range db id that no risk has: delete_risk is a no-op on
        // an empty set and returns true, so the page reports success (200).
        [$code, , $body] = $this->authedPost('/admin/delete_risks.php', [
            'delete_risks' => 1,
            'risks'        => [999990],
        ]);
        self::assertSame(200, $code, "deleting a nonexistent id must not error (got {$code}): {$body}");

        $afterAll = $this->countScalar("SELECT COUNT(*) FROM risks WHERE subject LIKE '" . self::SUBJECT_PREFIX . "%'");
        self::assertSame($beforeAll, $afterAll, 'no E2E risk should be touched by a nonexistent-id delete');
    }
}
