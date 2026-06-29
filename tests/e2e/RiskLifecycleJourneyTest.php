<?php
/**
 * E2e journey: the full risk lifecycle over HTTP, asserting each step on the
 * wire AND in the database (the source of truth).
 *
 * Submit a real risk -> read it back -> comment -> plan a mitigation ->
 * management review -> change status -> close -> reopen. Each action hits the
 * real Slim route (/api/v2/ and /api/v2/management/risk/*) over HTTPS as the shared
 * e2e test admin, then we read the resulting risks/comments/mitigations/
 * mgmt_reviews/closures rows directly from the DB to prove the write landed.
 *
 * CSRF: every authenticated /api/v2 request runs is_session_authenticated() ->
 * include_csrf_magic() -> csrf_init() -> csrf_check() (csrf-magic.php:432), so
 * unlike the plain admin/*.php pages (which use their own nonce, not csrf-magic),
 * every Slim POST here MUST carry a valid __csrf_magic token. The token is
 * session-derived (sid:csrf_hash(session_id)) and injected into any rendered
 * form by csrf-magic's output handler; we fetch it once from the submit-risk
 * page and replay it on every POST. The /api/v2/management/risk/* form handlers read
 * ?id=<publicRiskId>; public id is db id + 1000 (api_v2/includes/risks.php:627).
 * We standardize every action on ?id= so the id convention is uniform.
 *
 * No transaction rollback in e2e: every created risk is tracked and deleted in
 * tearDown via delete_risk() (cleans risks/comments/mitigations/mgmt_reviews/
 * closures/files in one call), plus a safety-net sweep of any E2E_RISK_* row.
 */
declare(strict_types=1);

final class RiskLifecycleJourneyTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    protected function tearDown(): void
    {
        $this->tearDownRisks();
        parent::tearDown();
    }

    public function test_submit_creates_db_row_and_is_readable(): void
    {
        $subject = $this->uniqueSubject('Submit');
        $publicId = $this->submitRisk($subject);

        // DB truth: a risks row with this subject, status 'New'.
        $row = $this->readRiskRow($publicId);
        self::assertSame($subject, $row['subject'], 'submitted subject must persist');
        self::assertSame('New', $row['status'], 'a fresh risk must be in the New status');

        // Readable over HTTP: the risk detail page renders the right id.
        [, , $body] = $this->authedGet('/management/view.php?id=' . $publicId);
        self::assertStringContainsString(
            "window.simplerisk_current_risk_id = {$publicId}",
            $body,
            'view.php must render the submitted risk id'
        );
    }

    public function test_comment_is_persisted(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Comment'));
        $comment = 'E2E comment ' . uniqid();

        [$code, , $body] = $this->actionPost('/api/v2/management/risk/saveComment?id=' . $publicId, ['comment' => $comment]);
        self::assertSame(200, $code, "saveComment returned {$code}: {$body}");

        // Comment text is encrypted at rest under the Encryption Extra, so match it
        // by decrypting the stored column rather than a plaintext SQL equality.
        $count = $this->countByDecryptedColumn('comments', 'comment', $comment, 'risk_id = ?', [self::dbId($publicId)]);
        self::assertSame(1, $count, 'the comment must be persisted on the risk');
    }

    public function test_plan_mitigation_persists(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Mitigation'));

        [$code, , $body] = $this->actionPost('/api/v2/management/risk/saveMitigation?id=' . $publicId, [
            'planning_strategy'       => 1,
            'mitigation_effort'       => 1,
            'mitigation_cost'         => 1,
            'mitigation_owner'        => $this->testAdminUid(),
            'current_solution'        => 'E2E planned solution',
            'security_requirements'   => 'E2E security requirement',
            'security_recommendations'=> 'E2E security recommendation',
        ]);
        self::assertSame(200, $code, "saveMitigation returned {$code}: {$body}");

        // A mitigation row exists and the risk advanced to 'Mitigation Planned'.
        $mitCount = $this->countScalar(
            'SELECT COUNT(*) FROM mitigations m JOIN risks r ON r.mitigation_id = m.id WHERE r.id = ?',
            [self::dbId($publicId)]
        );
        self::assertGreaterThanOrEqual(1, $mitCount, 'a mitigation must be created for the risk');
        self::assertSame('Mitigation Planned', $this->readRiskRow($publicId)['status']);
    }

    public function test_management_review_persists_and_sets_status(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Review'));

        [$code, , $body] = $this->actionPost('/api/v2/management/risk/saveReview?id=' . $publicId, [
            'review'      => $this->reviewDecisionValue(),
            'next_step'   => 0,
            'comments'    => 'E2E mgmt review',
            'custom_date' => 'no',
        ]);
        self::assertSame(200, $code, "saveReview returned {$code}: {$body}");

        $reviewCount = $this->countScalar(
            'SELECT COUNT(*) FROM mgmt_reviews m JOIN risks r ON r.mgmt_review = m.id WHERE r.id = ?',
            [self::dbId($publicId)]
        );
        self::assertGreaterThanOrEqual(1, $reviewCount, 'a management review must be created');
        self::assertSame('Mgmt Reviewed', $this->readRiskRow($publicId)['status']);
    }

    public function test_change_status_via_updateStatus(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Status'));
        // A status genuinely different from New (and not Closed, which is the
        // closerisk path). Pick the first such status from the live status table
        // — names are seeded (New/Mitigation Planned/Mgmt Reviewed/Closed/
        // Reopened/Untreated/Treated) but we read them rather than hardcode.
        $target = $this->changeableStatus();
        self::assertNotNull($target, 'expected at least one non-New, non-Closed status');

        [$code, , $body] = $this->actionPost('/api/v2/management/risk/updateStatus?id=' . $publicId, ['status' => $target['value']]);
        self::assertSame(200, $code, "updateStatus returned {$code}: {$body}");

        self::assertSame($target['name'], $this->readRiskRow($publicId)['status'], 'updateStatus must move the risk to the chosen status');
    }

    public function test_close_then_reopen(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('CloseReopen'));

        // Close it.
        [$code, , $body] = $this->actionPost('/api/v2/management/risk/closerisk?id=' . $publicId, [
            'close_reason' => $this->firstCloseReasonValue(),
            'note'         => 'E2E close note',
        ]);
        self::assertSame(200, $code, "closerisk returned {$code}: {$body}");
        self::assertSame('Closed', $this->readRiskRow($publicId)['status'], 'risk must be Closed after closerisk');
        $closures = $this->countScalar('SELECT COUNT(*) FROM closures WHERE risk_id = ?', [self::dbId($publicId)]);
        self::assertGreaterThanOrEqual(1, $closures, 'a closure row must be recorded');

        // Reopen it.
        [$code, , $body] = $this->actionPost('/api/v2/management/risk/reopen?id=' . $publicId, []);
        self::assertSame(200, $code, "reopen returned {$code}: {$body}");
        self::assertNotSame('Closed', $this->readRiskRow($publicId)['status'], 'risk must not be Closed after reopen');
    }

    public function test_full_journey_single_risk_end_to_end(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('FullJourney'));
        self::assertSame('New', $this->readRiskRow($publicId)['status']);

        // Comment
        $this->actionPost('/api/v2/management/risk/saveComment?id=' . $publicId, ['comment' => 'journey comment']);
        self::assertSame(1, $this->countScalar(
            'SELECT COUNT(*) FROM comments WHERE risk_id = ?',
            [self::dbId($publicId)]
        ));

        // Mitigation
        $this->actionPost('/api/v2/management/risk/saveMitigation?id=' . $publicId, [
            'planning_strategy' => 1, 'mitigation_effort' => 1, 'mitigation_cost' => 1,
            'mitigation_owner' => $this->testAdminUid(), 'current_solution' => 'sol',
            'security_requirements' => 'req', 'security_recommendations' => 'rec',
        ]);
        self::assertSame('Mitigation Planned', $this->readRiskRow($publicId)['status']);

        // Review
        $this->actionPost('/api/v2/management/risk/saveReview?id=' . $publicId, [
            'review' => $this->reviewDecisionValue(), 'next_step' => 0,
            'comments' => 'review', 'custom_date' => 'no',
        ]);
        self::assertSame('Mgmt Reviewed', $this->readRiskRow($publicId)['status']);

        // Close + reopen
        $this->actionPost('/api/v2/management/risk/closerisk?id=' . $publicId, [
            'close_reason' => $this->firstCloseReasonValue(), 'note' => 'close',
        ]);
        self::assertSame('Closed', $this->readRiskRow($publicId)['status']);
        $this->actionPost('/api/v2/management/risk/reopen?id=' . $publicId, []);
        self::assertNotSame('Closed', $this->readRiskRow($publicId)['status']);
    }
}
