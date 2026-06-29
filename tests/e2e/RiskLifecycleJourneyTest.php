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
    private const SUBJECT_PREFIX = 'E2E_RISK_';

    /** @var int[] db ids of risks created during a test, deleted in tearDown. */
    private array $createdRiskDbIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdRiskDbIds as $dbId) {
            $this->deleteRisk($dbId);
        }
        // Safety net: any E2E_RISK_ row that escaped per-test tracking.
        $this->sweepE2ERisks();
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

        $count = $this->countScalar(
            'SELECT COUNT(*) FROM comments WHERE risk_id = ? AND comment = ?',
            [self::dbId($publicId), $comment]
        );
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

    // ---------- helpers ----------

    /**
     * Submit a risk over the API and return its PUBLIC id (db id + 1000).
     * Tracks the db id for tearDown cleanup.
     */
    private function submitRisk(string $subject): int
    {
        [$code, , $body] = $this->actionPost('/api/v2/risks/submit', ['subject' => $subject]);
        self::assertSame(200, $code, "risk submit returned {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'risk submit');
        $publicId = (int) ($decoded['data']['risk_id'] ?? 0);
        self::assertGreaterThan(1000, $publicId, "expected data.risk_id > 1000, got: {$body}");
        $this->createdRiskDbIds[] = self::dbId($publicId);
        return $publicId;
    }

    /**
     * Authenticated POST to a risk-action Slim route. Delegates to
     * E2ETestCase::authedPost (cookie + csrf token) with a Referer pinned to the
     * risk detail page so getTabHtml() (called by saveMitigation/saveReview/
     * updateStatus/closerisk) does not warn on a missing server var.
     */
    private function actionPost(string $path, array $post): array
    {
        return $this->authedPost($path, $post, ['referer' => 'https://localhost/management/view.php']);
    }

    private function uniqueSubject(string $tag): string
    {
        return self::SUBJECT_PREFIX . $tag . '_' . uniqid();
    }

    private static function dbId(int $publicId): int
    {
        return $publicId - 1000;
    }

    /** subject + status (+ mitigation_id, mgmt_review) for a risk by public id. */
    private function readRiskRow(int $publicId): array
    {
        $db = db_open();
        $stmt = $db->prepare('SELECT id, subject, status, mitigation_id, mgmt_review FROM risks WHERE id = ?');
        $stmt->execute([self::dbId($publicId)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        db_close($db);
        self::assertIsArray($row, "risk db row missing for public id {$publicId}");
        return $row;
    }

    private function countScalar(string $sql, array $params = []): int
    {
        $db = db_open();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $c = (int) $stmt->fetchColumn();
        db_close($db);
        return $c;
    }

    /**
     * The first status that a plain updateStatus can move a New risk to: any
     * status whose name is not 'New' and not 'Closed' (Closed is the closerisk
     * path, which also writes a closure). Returns ['value'=>int,'name'=>string]
     * or null if none qualify.
     */
    private function changeableStatus(): ?array
    {
        $db = db_open();
        $row = $db->query("SELECT value, name FROM status WHERE name != 'New' AND name != 'Closed' ORDER BY value LIMIT 1")
            ->fetch(PDO::FETCH_ASSOC);
        db_close($db);
        return $row === false ? null : ['value' => (int) $row['value'], 'name' => (string) $row['name']];
    }

    /** A valid mgmt-review decision value (the review table's value column). */
    private function reviewDecisionValue(): int
    {
        $v = $this->countScalar('SELECT value FROM review ORDER BY value LIMIT 1');
        return $v ?: 1;
    }

    /** First available close_reason value. */
    private function firstCloseReasonValue(): int
    {
        $v = $this->countScalar('SELECT value FROM close_reason ORDER BY value LIMIT 1');
        return $v ?: 1;
    }

    /**
     * The test admin's user id. mitigation_owner (and any other owner field)
     * must reference a real user, and this process's own $_SESSION is empty (it
     * curls Apache over HTTP), so we read the provisioned admin's id directly.
     */
    private function testAdminUid(): int
    {
        return $this->countScalar('SELECT value FROM user WHERE username = ?', [E2ETestCase::TEST_USER]) ?: 1;
    }

    private function deleteRisk(int $dbId): void
    {
        // delete_risk cleans risks/comments/mitigations/mgmt_reviews/closures/files.
        if (function_exists('delete_risk')) {
            delete_risk($dbId);
        }
    }

    /**
     * Last-resort cleanup: delete any leftover E2E_RISK_ risks and the children
     * delete_risk() would have removed for them — mitigations, mgmt_reviews,
     * closures, comments, files (all carry a risk_id) — so escapee risks do not
     * leave orphaned rows behind. Children are deleted before the risks row.
     */
    private function sweepE2ERisks(): void
    {
        $db = db_open();
        $ids = $db->query("SELECT id FROM risks WHERE subject LIKE '" . self::SUBJECT_PREFIX . "%'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            foreach (['mitigations', 'mgmt_reviews', 'closures', 'comments', 'files'] as $t) {
                $db->prepare("DELETE FROM `{$t}` WHERE risk_id = ?")->execute([$id]);
            }
        }
        $db->exec("DELETE FROM risks WHERE subject LIKE '" . self::SUBJECT_PREFIX . "%'");
        db_close($db);
    }
}
