<?php
/**
 * E2E regression for the Team-Based Separation Extra's risk-list scoping.
 *
 * The core contract: when separation is enabled, a NON-admin user's
 * /api/v2/risks list is filtered by get_user_teams_query() so they see only
 * risks their team owns (plus owner/submitter/stakeholder risks per the
 * allow_* flags), while an admin sees everything. This is the separation
 * behavior that has only been covered at the integration (function) level, not
 * over HTTP — so a moved filter or a broken extra-loader would slip through.
 *
 * Separation is force-enabled (setting `team_separation`) and snapshotted/restored.
 * OH is deliberately left OFF (absent) so get_user_teams uses the plain
 * user_to_team join this test exercises. The team_member + noassign allow_*
 * flags are pinned explicitly for determinism (they default ON, but the test
 * should not depend on instance state).
 */
declare(strict_types=1);

final class SeparationExtraRiskScopingTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    /** @var array<string,?string> snapshot of settings touched (null = absent). */
    private array $snapshot = [];
    private int $teamA = 0;
    private int $teamB = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->snapshot = $this->snapshotSettings(
            'team_separation',
            'allow_team_member_to_risk',
            'allow_all_to_risk_noassign_team'
        );
        $this->writeSetting('team_separation', '1');
        $this->writeSetting('allow_team_member_to_risk', '1');
        $this->writeSetting('allow_all_to_risk_noassign_team', '1');
        $this->teamA = $this->seedTeam('TEAM_A');
        $this->teamB = $this->seedTeam('TEAM_B');
    }

    protected function tearDown(): void
    {
        $this->restoreSettings($this->snapshot);
        $this->tearDownRisks(); // deletes risks + sweeps teams / memberships
        parent::tearDown();
    }

    public function test_admin_sees_all_risks_unfiltered(): void
    {
        $a = $this->submitRisk($this->uniqueSubject('SEP_A'));
        $b = $this->submitRisk($this->uniqueSubject('SEP_B'));
        $this->assignRiskToTeam(self::dbId($a), $this->teamA);
        $this->assignRiskToTeam(self::dbId($b), $this->teamB);

        $ids = $this->visibleRiskIds('admin');
        self::assertContains($a, $ids, 'admin must see the team-A risk');
        self::assertContains($b, $ids, 'admin must see the team-B risk');
    }

    public function test_restricted_user_sees_only_own_team_risks(): void
    {
        $info = $this->ensureRestrictedUser();                  // all perms, admin=0
        $this->assignUserToTeam($info['uid'], $this->teamA);     // member of team A only
        $this->loginRestrictedSession();

        $a = $this->submitRisk($this->uniqueSubject('SEP_A'));
        $b = $this->submitRisk($this->uniqueSubject('SEP_B'));
        $this->assignRiskToTeam(self::dbId($a), $this->teamA);
        $this->assignRiskToTeam(self::dbId($b), $this->teamB);

        $ids = $this->visibleRiskIds('restricted');
        self::assertContains($a, $ids, 'restricted (team A) must see the team-A risk');
        self::assertNotContains($b, $ids, 'restricted (team A) must NOT see the team-B risk');
    }

    public function test_noassign_risk_visibility_follows_flag(): void
    {
        $info = $this->ensureRestrictedUser();
        $this->assignUserToTeam($info['uid'], $this->teamA);
        $this->loginRestrictedSession();

        $c = $this->submitRisk($this->uniqueSubject('SEP_NOASSIGN')); // intentionally no team

        // Flag ON (pinned in setUp): an unassigned risk is visible to everyone.
        self::assertContains($c, $this->visibleRiskIds('restricted'), 'unassigned risk must be visible with noassign flag ON');

        // Flip the flag OFF: the unassigned risk must disappear for the team-scoped user.
        $this->writeSetting('allow_all_to_risk_noassign_team', '0');
        self::assertNotContains($c, $this->visibleRiskIds('restricted'), 'unassigned risk must be hidden with noassign flag OFF');
    }

    /**
     * The set of PUBLIC risk ids (db id + 1000) a session sees in /api/v2/risks.
     * The admin session is the default; pass 'restricted' for the second session.
     */
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
