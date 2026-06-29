<?php
/**
 * E2e: every risk SCORING METHOD (Classic / CVSS / DREAD / OWASP / Custom) over
 * HTTP, asserted on the wire AND in the DB.
 *
 * For each method we submit a risk, re-score it via the real Slim route
 * POST /api/v2/management/risk/saveScore?id=<pub>&action=<update_*>, then prove:
 *  - the response is 200,
 *  - the risk_scoring row stores the method's metrics (+ calculated_risk),
 *  - risk_scoring_history grew by exactly one (the score functions append a
 *    history row only when calculated_risk actually changes).
 *
 * GOTCHA locked here: the saveScore ?action=update_dread|update_owasp handlers
 * read UNPREFIXED field names (DamagePotential / SkillLevel / EaseOfDiscovery),
 * whereas the submit + saveDetails paths use the prefixed names (DREADDamage /
 * OWASPSkillLevel). Two payload builders per method encode both contracts.
 *
 * calculate_risk() is available in-process (bootstrap includes functions.php),
 * so the expected Classic score is computed with the same function the app uses.
 */
declare(strict_types=1);

final class RiskScoringMethodsTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    protected function tearDown(): void
    {
        $this->tearDownRisks();
        parent::tearDown();
    }

    /** POST saveScore ?action=<a> with payload, assert 200, return [code,body]. */
    private function saveScore(int $publicId, string $action, array $payload): array
    {
        return $this->actionPost(
            '/api/v2/management/risk/saveScore?id=' . $publicId . '&action=' . $action,
            $payload
        );
    }

    public function test_classic_score_persists_and_records_history(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Classic'));
        $likelihood = $this->firstLikelihoodId();
        $impact = $this->firstImpactId();
        $before = $this->countScoringHistory($publicId);

        [$code, , $body] = $this->saveScore($publicId, 'update_classic', [
            'likelihood' => $likelihood, 'impact' => $impact,
        ]);
        self::assertSame(200, $code, "update_classic returned {$code}: {$body}");

        $row = $this->readRiskScoringRow($publicId);
        self::assertSame((string) $likelihood, (string) $row['CLASSIC_likelihood'], 'CLASSIC_likelihood must persist');
        self::assertSame((string) $impact, (string) $row['CLASSIC_impact'], 'CLASSIC_impact must persist');
        // calculated_risk is computed with the very same function the app uses.
        self::assertEqualsWithDelta(
            (float) calculate_risk($impact, $likelihood),
            (float) $row['calculated_risk'],
            0.001,
            'calculated_risk must equal calculate_risk(impact, likelihood)'
        );
        self::assertSame($before + 1, $this->countScoringHistory($publicId), 'a classic re-score must append one history row');
    }

    public function test_cvss_score_persists_and_records_history(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Cvss'));
        $before = $this->countScoringHistory($publicId);

        [$code, , $body] = $this->saveScore($publicId, 'update_cvss', self::cvssScorePayload());
        self::assertSame(200, $code, "update_cvss returned {$code}: {$body}");

        $row = $this->readRiskScoringRow($publicId);
        self::assertSame('N', $row['CVSS_AccessVector'], 'CVSS_AccessVector must persist the short code');
        $score = (float) $row['calculated_risk'];
        self::assertGreaterThan(0, $score, 'a partial-impact CVSS score must be > 0');
        self::assertLessThan(10, $score, 'the chosen CVSS metrics must score < 10 (so it differs from the initial 10 and records history)');
        self::assertSame($before + 1, $this->countScoringHistory($publicId), 'a CVSS re-score must append one history row');
    }

    public function test_dread_score_persists_and_records_history(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Dread'));
        $payload = self::dreadSaveScorePayload(); // UNPREFIXED names for saveScore
        $expected = round(($payload['DamagePotential'] + $payload['Reproducibility'] + $payload['Exploitability'] + $payload['AffectedUsers'] + $payload['Discoverability']) / 5, 2);
        $before = $this->countScoringHistory($publicId);

        [$code, , $body] = $this->saveScore($publicId, 'update_dread', $payload);
        self::assertSame(200, $code, "update_dread returned {$code}: {$body}");

        $row = $this->readRiskScoringRow($publicId);
        self::assertSame((string) $payload['DamagePotential'], (string) $row['DREAD_DamagePotential'], 'DREAD_DamagePotential must persist');
        self::assertEquals($expected, (float) $row['calculated_risk'], 'DREAD calculated_risk = round(sum/5, 2)');
        self::assertSame($before + 1, $this->countScoringHistory($publicId), 'a DREAD re-score must append one history row');
    }

    public function test_owasp_score_persists_and_records_history(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Owasp'));
        $payload = self::owaspSaveScorePayload(); // UNPREFIXED names for saveScore
        $before = $this->countScoringHistory($publicId);

        [$code, , $body] = $this->saveScore($publicId, 'update_owasp', $payload);
        self::assertSame(200, $code, "update_owasp returned {$code}: {$body}");

        $row = $this->readRiskScoringRow($publicId);
        self::assertSame((string) $payload['SkillLevel'], (string) $row['OWASP_SkillLevel'], 'OWASP_SkillLevel must persist');
        // All-LOW threat + impact factors ⇒ LOW/LOW ⇒ "Note" ⇒ 0. That differs
        // from a fresh risk's initial 10, so the re-score records history.
        self::assertEquals(0, (float) $row['calculated_risk'], 'all-LOW OWASP must score 0');
        self::assertSame($before + 1, $this->countScoringHistory($publicId), 'an OWASP re-score must append one history row');
    }

    public function test_custom_score_persists_records_history_and_clamps(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Custom'));
        $before = $this->countScoringHistory($publicId);

        // A normal custom value lands verbatim.
        [$code, , $body] = $this->saveScore($publicId, 'update_custom', ['Custom' => 7.5]);
        self::assertSame(200, $code, "update_custom returned {$code}: {$body}");
        $row = $this->readRiskScoringRow($publicId);
        self::assertEquals(7.5, (float) $row['Custom'], 'Custom must persist');
        self::assertEquals(7.5, (float) $row['calculated_risk'], 'calculated_risk == Custom');
        self::assertSame($before + 1, $this->countScoringHistory($publicId), 'a custom re-score must append one history row');

        // A value out of [0,10] is clamped to 10 (update_custom_score:9182).
        $this->saveScore($publicId, 'update_custom', ['Custom' => 99]);
        $row = $this->readRiskScoringRow($publicId);
        self::assertEquals(10, (float) $row['Custom'], 'Custom > 10 must clamp to 10');
        self::assertEquals(10, (float) $row['calculated_risk'], 'clamped calculated_risk == 10');
    }

    public function test_score_change_appends_to_scoring_history_endpoint(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('ScoreHist'));
        $impact = $this->firstImpactId();
        $likelihood = $this->firstLikelihoodId();
        $this->saveScore($publicId, 'update_classic', ['likelihood' => $likelihood, 'impact' => $impact]);
        $expected = (float) calculate_risk($impact, $likelihood);

        // The scoring-history endpoint returns the history list; the appended
        // "current" entry carries the latest calculated_risk.
        [$code, , $body] = $this->actionGet('/api/v2/risks/' . $publicId . '/scoring-history');
        self::assertSame(200, $code, "scoring-history returned {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'scoring-history');
        self::assertIsArray($decoded['data'], 'scoring-history data must be a list');
        self::assertNotEmpty($decoded['data'], 'there must be at least one history entry');
        $last = end($decoded['data']);
        self::assertArrayHasKey('calculated_risk', $last, 'history entries carry calculated_risk');
        self::assertEqualsWithDelta($expected, (float) $last['calculated_risk'], 0.001, 'latest history entry reflects the new score');
    }
}
