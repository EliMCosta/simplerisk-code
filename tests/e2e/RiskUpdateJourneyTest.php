<?php
/**
 * E2e: the risk UPDATE / edit-details surface over HTTP, asserted on the wire
 * AND in the DB.
 *
 * Covers the three ways the UI mutates an existing risk:
 *  - saveDetails  (POST /api/v2/management/risk/saveDetails?id=<pub>) — the
 *    "Details" tab save. Calls update_risk() (reference_id/category/owner/
 *    manager/notes/...) then update_risk_scoring(). Crucially update_risk() does
 *    NOT touch subject — that is locked here as a regression test.
 *  - saveSubject  (POST /api/v2/management/risk/saveSubject?id=<pub>) — the ONLY
 *    path that changes risks.subject (via update_risk_subject).
 *  - PATCH /risks/{id}  (updateRisk) — the REST verb; parses php://input, not
 *    csrf-gated (csrf_check runs on POST only).
 *
 * All ids are PUBLIC (db + 1000). No transaction rollback in e2e: created risks
 * are tracked and deleted in tearDown, plus an E2E_RISK_* safety-net sweep.
 */
declare(strict_types=1);

final class RiskUpdateJourneyTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    protected function tearDown(): void
    {
        $this->tearDownRisks();
        parent::tearDown();
    }

    public function test_saveDetails_persists_category_owner_and_reference_id(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Update'));
        $category = $this->firstCategoryId();
        $owner = $this->testAdminUid();
        // risks.reference_id is VARCHAR(20); keep the value within that bound.
        $referenceId = 'REF-' . uniqid();

        // A real Details-tab save also carries the scoring fields; send a valid
        // Classic scoring so update_risk_scoring() gets sane input.
        [$code, , $body] = $this->actionPost('/api/v2/management/risk/saveDetails?id=' . $publicId, [
            'category'      => $category,
            'owner'         => $owner,
            'reference_id'  => $referenceId,
            'scoring_method'=> 1,
            'likelihood'    => $this->firstLikelihoodId(),
            'impact'        => $this->firstImpactId(),
        ]);
        self::assertSame(200, $code, "saveDetails returned {$code}: {$body}");

        $row = $this->readRiskDetailsRow($publicId);
        self::assertSame((string) $category, (string) $row['category'], 'category must persist');
        self::assertSame((string) $owner, (string) $row['owner'], 'owner must persist');
        self::assertSame($referenceId, $row['reference_id'], 'reference_id must persist');
    }

    public function test_saveDetails_does_not_mutate_subject(): void
    {
        // Set a distinct subject via saveSubject (the only subject-mutating path),
        // then a saveDetails with unrelated fields must leave the subject intact —
        // update_risk() has no subject key in its UPDATE.
        $publicId = $this->submitRisk($this->uniqueSubject('KeepSubject'));
        $newSubject = $this->uniqueSubject('Renamed');

        [$code, , $body] = $this->actionPost('/api/v2/management/risk/saveSubject?id=' . $publicId, ['subject' => $newSubject]);
        self::assertSame(200, $code, "saveSubject returned {$code}: {$body}");
        self::assertSame($newSubject, $this->readRiskRow($publicId)['subject']);

        $this->actionPost('/api/v2/management/risk/saveDetails?id=' . $publicId, [
            'category'      => $this->firstCategoryId(),
            'reference_id'  => 'E2E-REF-DET-' . uniqid(),
            'scoring_method'=> 1,
            'likelihood'    => $this->firstLikelihoodId(),
            'impact'        => $this->firstImpactId(),
        ]);

        self::assertSame(
            $newSubject,
            $this->readRiskRow($publicId)['subject'],
            'saveDetails must not change the subject (update_risk omits it)'
        );
    }

    public function test_saveSubject_updates_subject(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('OrigSubject'));
        $newSubject = $this->uniqueSubject('SubjectChanged');

        [$code, , $body] = $this->actionPost('/api/v2/management/risk/saveSubject?id=' . $publicId, ['subject' => $newSubject]);
        self::assertSame(200, $code, "saveSubject returned {$code}: {$body}");

        self::assertSame($newSubject, $this->readRiskRow($publicId)['subject'], 'saveSubject must persist the new subject');
    }

    public function test_patch_risks_id_updates_fields(): void
    {
        $publicId = $this->submitRisk($this->uniqueSubject('Patch'));
        $category = $this->firstCategoryId();
        $referenceId = 'REF-' . uniqid();
        $newSubject = $this->uniqueSubject('PatchSubject');

        // PATCH is NOT csrf-gated (csrf_check runs on POST only), and updateRisk
        // parses the body from php://input. NOTE updateRisk's subject check is
        // `!trim($new_subject)`, which is true even when subject is ABSENT (trim
        // of false == ''), so a PATCH must carry a non-empty subject — send one.
        [$code, , $body] = $this->request('PATCH', '/api/v2/risks/' . $publicId, [
            'cookie'  => true,
            'post'    => http_build_query([
                'subject'      => $newSubject,
                'category'     => $category,
                'reference_id' => $referenceId,
            ]),
            'headers' => ['Referer: https://localhost/management/view.php'],
        ]);
        self::assertSame(200, $code, "PATCH /risks/{id} returned {$code}: {$body}");

        $row = $this->readRiskDetailsRow($publicId);
        self::assertSame((string) $category, (string) $row['category'], 'PATCH must update category');
        self::assertSame($referenceId, $row['reference_id'], 'PATCH must update reference_id');
        self::assertSame($newSubject, $row['subject'], 'PATCH must update subject');
    }

    public function test_patch_risks_nonexistent_id_returns_400(): void
    {
        // A public id far outside any real risk → get_risk_by_id returns empty →
        // updateRisk replies 400 RiskIdDoesNotExist.
        [$code, , $body] = $this->request('PATCH', '/api/v2/risks/9999999', [
            'cookie'  => true,
            'post'    => http_build_query(['category' => $this->firstCategoryId()]),
            'headers' => ['Referer: https://localhost/management/view.php'],
        ]);
        self::assertSame(400, $code, "PATCH on a nonexistent risk must be 400, got: {$body}");
    }
}
