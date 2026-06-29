<?php
/**
 * E2e: the risk READ surface over HTTP — the GET endpoints a risk owner hits to
 * inspect a risk (list, view, comments, mitigations, reviews, scoring history,
 * residual scoring history). Asserts each responds 200 with the expected JSON
 * shape and, where it matters, that the persisted data round-trips.
 *
 * All ids are PUBLIC (db + 1000). No transaction rollback in e2e: created risks
 * are tracked and deleted in tearDown, plus an E2E_RISK_* safety-net sweep.
 */
declare(strict_types=1);

final class RiskReadEndpointsTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    protected function tearDown(): void
    {
        $this->tearDownRisks();
        parent::tearDown();
    }

    public function test_get_risks_list_includes_submitted_risk(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('List'));

        [$code, , $body] = $this->actionGet('/api/v2/risks');
        self::assertSame(200, $code, "GET /api/v2/risks returned {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'risk list');
        self::assertIsArray($decoded['data']['risks'] ?? null, 'data.risks must be a list');

        $ids = array_column($decoded['data']['risks'], 'id');
        self::assertContains($publicId, $ids, 'the submitted risk (public id) must appear in the list');
    }

    public function test_view_risk_returns_decrypted_subject_and_status(): void
    {
        $subject = $this->uniqueSubject('View');
        $publicId = $this->submitRisk($subject);

        // /risks/{id} → viewrisk → data is a single-element list [{id, subject, ...}].
        [$code, , $body] = $this->actionGet('/api/v2/risks/' . $publicId);
        self::assertSame(200, $code, "GET /risks/{id} returned {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'view risk');
        self::assertSame($subject, $decoded['data'][0]['subject'] ?? null, 'view must return the decrypted subject');
        self::assertSame('New', $decoded['data'][0]['status'] ?? null, 'view must return the status');
    }

    public function test_management_view_route_returns_same_risk(): void
    {
        // /management/risk/view?id= is a second registered route to the same
        // viewrisk() handler (api/v2/index.php:152 + :161) — confirm both resolve.
        $subject = $this->uniqueSubject('MgmtView');
        $publicId = $this->submitRisk($subject);

        [$code, , $body] = $this->actionGet('/api/v2/management/risk/view?id=' . $publicId);
        self::assertSame(200, $code, "GET /management/risk/view returned {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'management view risk');
        self::assertSame($subject, $decoded['data'][0]['subject'] ?? null, 'management view must return the same risk');
    }

    public function test_comments_mitigations_reviews_get_endpoints_respond(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('ReadChildren'));
        // Give the risk a comment, mitigation and review so the endpoints have data.
        $this->actionPost('/api/v2/management/risk/saveComment?id=' . $publicId, ['comment' => 'E2E read comment']);
        $this->actionPost('/api/v2/management/risk/saveMitigation?id=' . $publicId, [
            'planning_strategy' => 1, 'mitigation_effort' => 1, 'mitigation_cost' => 1,
            'mitigation_owner' => $this->testAdminUid(),
            'current_solution' => 'sol', 'security_requirements' => 'req', 'security_recommendations' => 'rec',
        ]);
        $this->actionPost('/api/v2/management/risk/saveReview?id=' . $publicId, [
            'review' => $this->reviewDecisionValue(), 'next_step' => 0,
            'comments' => 'review', 'custom_date' => 'no',
        ]);

        foreach (['comments', 'mitigations', 'reviews'] as $sub) {
            [$code, , $body] = $this->actionGet('/api/v2/risks/' . $publicId . '/' . $sub);
            self::assertSame(200, $code, "GET /risks/{id}/{$sub} returned {$code}: {$body}");
            $decoded = $this->decodeJson($body, "risk {$sub}");
            self::assertIsArray($decoded['data'], "data for /risks/{id}/{$sub} must be a list");
        }
    }

    public function test_residual_scoring_history_endpoint_responds(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('ResidHist'));

        [$code, , $body] = $this->actionGet('/api/v2/risks/' . $publicId . '/residual-scoring-history');
        self::assertSame(200, $code, "residual-scoring-history returned {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'residual scoring history');
        self::assertIsArray($decoded['data'], 'residual scoring history data must be a list');
    }

    public function test_get_risk_comments_endpoint_returns_persisted_comment(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Comments'));
        $comment = 'E2E persisted comment ' . uniqid();

        [$code, , $body] = $this->actionPost('/api/v2/management/risk/saveComment?id=' . $publicId, ['comment' => $comment]);
        self::assertSame(200, $code, "saveComment returned {$code}: {$body}");

        [$code, , $body] = $this->actionGet('/api/v2/risks/' . $publicId . '/comments');
        self::assertSame(200, $code, "GET comments returned {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'risk comments');
        $texts = array_column($decoded['data'], 'comment');
        self::assertContains($comment, $texts, 'the persisted comment must be returned by the comments endpoint');
    }

    public function test_audit_trail_endpoint_returns_entries(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Audit'));
        // An action that writes to the audit log (a comment).
        $this->actionPost('/api/v2/management/risk/saveComment?id=' . $publicId, ['comment' => 'E2E audited comment']);

        [$code, , $body] = $this->actionGet('/api/v2/risks/' . $publicId . '/audit-trail');
        self::assertSame(200, $code, "audit-trail returned {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'audit trail');
        self::assertIsArray($decoded['data'], 'audit trail data must be a list');
        self::assertNotEmpty($decoded['data'], 'the audit trail must contain at least one entry after an action');
    }
}
