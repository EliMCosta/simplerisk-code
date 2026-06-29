<?php
/**
 * E2E regression for the Organizational Hierarchy × Separation cross-extra
 * integration — the documented auto-scoping that had NO test coverage.
 *
 * When BOTH extras are enabled, a non-admin's risk list is scoped not just by
 * team membership but by the Business Unit they have selected: get_user_teams()
 * delegates to get_teams_of_user_from_selected_business_unit(), intersecting the
 * user's teams with the selected BU's teams. Switching the selected BU (the
 * header switcher's POST .../business_unit/select) therefore rescopes the SAME
 * user's risk list — the crown-jewel integration this test locks down.
 *
 * Both extras are force-enabled and snapshotted/restored. The BU is switched via
 * the real select endpoint (which updates $_SESSION + the user column), because
 * get_selected_business_unit() caches the value in $_SESSION on first read, so a
 * mid-test DB-column change alone would not take effect.
 */
declare(strict_types=1);

final class OrganizationalHierarchyScopingTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    /** @var array<string,?string> snapshot of settings touched (null = absent). */
    private array $snapshot = [];
    private int $t1 = 0;
    private int $t2 = 0;
    private int $buX = 0;
    private int $buY = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->snapshot = $this->snapshotSettings(
            'team_separation',
            'organizational_hierarchy',
            'allow_team_member_to_risk',
            'allow_all_to_risk_noassign_team'
        );
        $this->writeSetting('team_separation', '1');
        $this->writeSetting('organizational_hierarchy', '1');
        $this->writeSetting('allow_team_member_to_risk', '1');
        $this->writeSetting('allow_all_to_risk_noassign_team', '1');

        // Two teams, each in its own Business Unit.
        $this->t1 = $this->seedTeam('OH_T1');
        $this->t2 = $this->seedTeam('OH_T2');
        $this->buX = $this->seedBusinessUnit('OH_BU_X', $this->t1);
        $this->buY = $this->seedBusinessUnit('OH_BU_Y', $this->t2);
    }

    protected function tearDown(): void
    {
        // Clear the restricted user's selected BU so it doesn't leak across runs.
        $info = $this->ensureRestrictedUser();
        $this->setUserSelectedBusinessUnit($info['uid'], null);

        $this->restoreSettings($this->snapshot);
        $this->tearDownRisks(); // deletes risks + sweeps teams / BUs / memberships
        parent::tearDown();
    }

    /**
     * The OH BU create endpoint makes a business_unit row AND links the selected
     * team via business_unit_to_team. Admin-only, JSON {data:{id}}.
     */
    public function test_bu_create_endpoint_creates_unit_and_links_team(): void
    {
        // A fresh team (not already in a BU) avoids the UNIQUE(team_id) constraint.
        $t3 = $this->seedTeam('OH_T3');

        [$code, , $body] = $this->authedPost(
            '/api/v2/organizational_hierarchy/business_unit/create',
            ['name' => $this->e2eName('OH_BU_CREATE'), 'selected_teams' => [$t3]]
        );
        self::assertSame(200, $code, "BU create returned {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'BU create');
        $buId = (int) ($decoded['data']['id'] ?? 0);
        self::assertGreaterThan(0, $buId, "expected data.id > 0, got: {$body}");

        $link = $this->fetchRow(
            'SELECT business_unit_id FROM business_unit_to_team WHERE team_id = ?',
            [$t3]
        );
        self::assertSame($buId, (int) ($link['business_unit_id'] ?? 0), 'team was not linked to the new BU');
    }

    /**
     * The SAME user (member of both t1 and t2) sees different risks after switching
     * their selected Business Unit — the OH auto-scoping of Separation.
     */
    public function test_switching_selected_business_unit_rescopes_risk_list(): void
    {
        $info = $this->ensureRestrictedUser();
        $this->assignUserToTeam($info['uid'], $this->t1);  // member of both teams
        $this->assignUserToTeam($info['uid'], $this->t2);
        $this->loginRestrictedSession();

        $r1 = $this->submitRisk($this->uniqueSubject('OH_R1')); // team t1 (BU-X)
        $r2 = $this->submitRisk($this->uniqueSubject('OH_R2')); // team t2 (BU-Y)
        $this->assignRiskToTeam(self::dbId($r1), $this->t1);
        $this->assignRiskToTeam(self::dbId($r2), $this->t2);

        // Select BU-X: only t1's risk is visible.
        $this->selectBusinessUnit('restricted', $this->buX);
        $inX = $this->visibleRiskIds('restricted');
        self::assertContains($r1, $inX, 'with BU-X selected, the t1 risk must be visible');
        self::assertNotContains($r2, $inX, 'with BU-X selected, the t2 risk must be hidden');

        // Switch to BU-Y: now only t2's risk is visible.
        $this->selectBusinessUnit('restricted', $this->buY);
        $inY = $this->visibleRiskIds('restricted');
        self::assertContains($r2, $inY, 'with BU-Y selected, the t2 risk must be visible');
        self::assertNotContains($r1, $inY, 'with BU-Y selected, the t1 risk must be hidden');
    }

    /**
     * POST the header switcher's select endpoint as $session, asserting the PRG
     * 302 (it redirects back to the Referer). Updates $_SESSION + the user column.
     */
    private function selectBusinessUnit(string $session, int $buId): void
    {
        $resp = $session === 'admin'
            ? $this->authedPost('/api/v2/organizational_hierarchy/business_unit/select', ['selected_business_unit' => $buId])
            : $this->authedPostAs($session, '/api/v2/organizational_hierarchy/business_unit/select', ['selected_business_unit' => $buId]);
        [$code] = $resp;
        self::assertSame(302, $code, "BU select ({$session}, bu {$buId}) should PRG-redirect, got {$code}");
    }

    /** PUBLIC risk ids (db id + 1000) visible to a session in /api/v2/risks. */
    private function visibleRiskIds(string $session = 'admin'): array
    {
        $resp = $session === 'admin'
            ? $this->authedGet('/api/v2/risks')
            : $this->authedGetAs($session, '/api/v2/risks');
        [$code, , $body] = $resp;
        self::assertSame(200, $code, "/api/v2/risks as '{$session}' returned {$code}: {$body}");
        $decoded = $this->decodeJson($body, 'risk list');
        $ids = [];
        foreach (($decoded['data']['risks'] ?? []) as $r) {
            $ids[] = (int) ($r['id'] ?? 0);
        }
        return $ids;
    }
}
