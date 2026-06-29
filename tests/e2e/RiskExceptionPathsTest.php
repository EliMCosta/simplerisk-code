<?php
/**
 * E2e: risk-management EXCEPTION / failure paths — the validation, auth and
 * idempotency gates a real user hits when they misuse the surface. These guard
 * exactly the regressions a UI rewrite or refactor can silently break.
 *
 * Two flavors:
 *  - Validation / idempotency paths run as the all-permission admin (empty
 *    subject, tag-too-long, missing-id, null-comment, missing-CSRF, double
 *    close/reopen).
 *  - PERMISSION-DENIED paths run as a SECOND, restricted session
 *    (e2e_restricted_user, admin=0) that lacks exactly one permission per test.
 *    check_permission/has_permission read $_SESSION[<key>] populated from
 *    permission_to_user at login, so the user is provisioned (all perms minus
 *    the excluded one) BEFORE it is logged in. The access-based 403s inside the
 *    handlers are out of scope here — check_access_for_risk() is always true
 *    while the Team Separation Extra is off.
 *
 * No transaction rollback in e2e: created risks are tracked + swept in tearDown,
 * and the restricted user's permissions are re-granted then (self-healing).
 */
declare(strict_types=1);

final class RiskExceptionPathsTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    protected function tearDown(): void
    {
        $this->tearDownRisks();
        parent::tearDown();
    }

    /** Provision + log in the restricted user missing the named permissions. */
    private function asRestricted(array $without): void
    {
        $this->ensureRestrictedUser($without);
        if (!$this->loginRestrictedSession()) {
            self::fail('restricted session could not log in');
        }
    }

    // ---- Validation exceptions (admin session) -----------------------------

    public function test_submit_empty_subject_returns_400(): void
    {
        [$code, , $body] = $this->actionPost('/api/v2/risks/submit', ['subject' => '   ']);
        self::assertSame(400, $code, "empty subject must be 400, got {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'empty subject submit');
        self::assertSame(400, (int) ($decoded['status'] ?? 0), 'envelope status must be 400');
    }

    public function test_submit_tag_over_255_chars_returns_400(): void
    {
        $subject = $this->uniqueSubject('LongTag');
        [$code, , $body] = $this->actionPost('/api/v2/risks/submit', [
            'subject' => $subject,
            'tags'    => str_repeat('a', 256),
        ]);
        self::assertSame(400, $code, "oversize tag must be 400, got {$code}: {$body}");
        // And critically, no risk was created.
        self::assertSame(0, $this->countScalar('SELECT COUNT(*) FROM risks WHERE subject = ?', [$subject]));
    }

    public function test_saveDetails_missing_id_returns_400(): void
    {
        [$code] = $this->actionPost('/api/v2/management/risk/saveDetails', ['category' => $this->firstCategoryId()]);
        self::assertSame(400, $code, "saveDetails without id must be 400");
    }

    public function test_saveScore_missing_id_returns_400(): void
    {
        [$code] = $this->actionPost('/api/v2/management/risk/saveScore?action=update_classic', ['likelihood' => 1, 'impact' => 1]);
        self::assertSame(400, $code, "saveScore without id must be 400");
    }

    public function test_closerock_and_reopen_missing_id_return_400(): void
    {
        [$closeCode] = $this->actionPost('/api/v2/management/risk/closerisk', ['close_reason' => $this->firstCloseReasonValue(), 'note' => 'x']);
        self::assertSame(400, $closeCode, 'closerisk without id must be 400');

        [$reopenCode] = $this->actionPost('/api/v2/management/risk/reopen', []);
        self::assertSame(400, $reopenCode, 'reopen without id must be 400');
    }

    public function test_null_comment_returns_400(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('NullComment'));
        // Empty POST body (only the injected csrf token) => comment key absent => null.
        [$code, , $body] = $this->actionPost('/api/v2/risks/' . $publicId . '/comments', []);
        self::assertSame(400, $code, "null comment must be 400, got {$code}: {$body}");
    }

    public function test_post_without_csrf_token_is_rejected(): void
    {
        $subject = $this->uniqueSubject('NoCsrf');
        // Raw POST with NO __csrf_magic (csrf_check runs on every /api/v2 POST).
        [$code, , $body] = $this->request('POST', '/api/v2/risks/submit', [
            'cookie' => true,
            'post'   => http_build_query(['subject' => $subject]),
        ]);
        // The security property: a token-less POST must NOT create the risk.
        self::assertSame(0, $this->countScalar('SELECT COUNT(*) FROM risks WHERE subject = ?', [$subject]),
            "token-less submit must not create a risk (HTTP {$code}): {$body}");
        self::assertNotSame(200, $code, 'a token-less POST must not look like a successful 200');
    }

    public function test_double_close_and_reopen_non_closed_are_idempotent(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Idempotent'));

        // Close, then close again — the second must not error and stays Closed.
        [$c1] = $this->actionPost('/api/v2/management/risk/closerisk?id=' . $publicId, [
            'close_reason' => $this->firstCloseReasonValue(), 'note' => 'first',
        ]);
        self::assertSame(200, $c1, 'first close must be 200');
        [$c2] = $this->actionPost('/api/v2/management/risk/closerisk?id=' . $publicId, [
            'close_reason' => $this->firstCloseReasonValue(), 'note' => 'second',
        ]);
        self::assertSame(200, $c2, 'double-close must not error');
        self::assertSame('Closed', $this->readRiskRow($publicId)['status']);

        // Reopen, then reopen again — neither errors and the risk stays non-Closed.
        [$r1] = $this->actionPost('/api/v2/management/risk/reopen?id=' . $publicId, []);
        self::assertSame(200, $r1, 'first reopen must be 200');
        [$r2] = $this->actionPost('/api/v2/management/risk/reopen?id=' . $publicId, []);
        self::assertSame(200, $r2, 'reopen of a non-closed risk must not error');
        self::assertNotSame('Closed', $this->readRiskRow($publicId)['status']);
    }

    // ---- Permission-denied exceptions (restricted session) -----------------

    public function test_submit_without_submit_risks_returns_401(): void
    {
        $this->asRestricted(['submit_risks']);
        [$code, , $body] = $this->restrictedSubmitPost(['subject' => $this->uniqueSubject('NoSubmitPerm')]);
        self::assertSame(401, $code, "submit without submit_risks must be 401, got {$code}: {$body}");
    }

    public function test_saveDetails_without_modify_risks_returns_400(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('NoModifyRisk'));
        $this->asRestricted(['modify_risks']);

        [$code, , $body] = $this->restrictedActionPost('/api/v2/management/risk/saveDetails?id=' . $publicId, [
            'category' => $this->firstCategoryId(),
        ]);
        self::assertSame(400, $code, "saveDetails without modify_risks must be 400, got {$code}: {$body}");
    }

    public function test_closerock_without_close_risks_returns_400(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('NoCloseRisk'));
        $this->asRestricted(['close_risks']);

        [$code, , $body] = $this->restrictedActionPost('/api/v2/management/risk/closerisk?id=' . $publicId, [
            'close_reason' => $this->firstCloseReasonValue(), 'note' => 'x',
        ]);
        self::assertSame(400, $code, "closerisk without close_risks must be 400, got {$code}: {$body}");
    }

    public function test_read_endpoint_without_riskmanagement_returns_403(): void
    {
        // The riskmanagement gate is shared by viewrisk / getRiskAuditTrail /
        // scoringHistory / reopenForm (which check it FIRST). Every management
        // page also enforces check_riskmanagement, so a no-riskmanagement session
        // cannot render a __csrf_magic form — therefore we exercise the gate via
        // a GET endpoint (not csrf-gated): getRiskAuditTrail returns 403 first.
        $publicId = $this->submitRisk($this->uniqueSubject('NoRiskMgmt'));
        $this->asRestricted(['riskmanagement']);

        [$code, , $body] = $this->authedGetAs('restricted', '/api/v2/risks/' . $publicId . '/audit-trail', ['referer' => 'https://localhost/management/view.php']);
        self::assertSame(403, $code, "audit-trail without riskmanagement must be 403, got {$code}: {$body}");
    }

    public function test_reopen_without_modify_risks_returns_403(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('NoModifyReopen'));
        $this->asRestricted(['modify_risks']); // has riskmanagement, lacks modify_risks

        [$code, , $body] = $this->restrictedActionPost('/api/v2/management/risk/reopen?id=' . $publicId, []);
        self::assertSame(403, $code, "reopen without modify_risks must be 403, got {$code}: {$body}");
    }

    public function test_accept_mitigation_without_accept_mitigation_returns_400(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('NoAcceptMit'));
        $this->asRestricted(['accept_mitigation']);

        [$code, , $body] = $this->restrictedActionPost('/api/v2/risks/' . $publicId . '/accept-mitigation', ['accept' => 1]);
        self::assertSame(400, $code, "accept-mitigation without the perm must be 400, got {$code}: {$body}");
    }

    public function test_comment_without_comment_permission_returns_403(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('NoCommentPerm'));
        $this->asRestricted(['comment_risk_management']); // has riskmanagement + access, lacks comment perm

        [$code, , $body] = $this->restrictedActionPost('/api/v2/risks/' . $publicId . '/comments', ['comment' => 'should be blocked']);
        self::assertSame(403, $code, "comment without comment_risk_management must be 403, got {$code}: {$body}");
    }
}
