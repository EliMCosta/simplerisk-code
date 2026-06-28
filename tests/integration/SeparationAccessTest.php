<?php
/**
 * Integration tests for the Team Separation Extra's risk access control
 * (extras/separation/includes/access.php).
 *
 * extra_grant_access() is the chokepoint core uses to decide whether a user may
 * see a risk (risk_id is the DISPLAY id = internal id + 1000). Covers the admin
 * short-circuit, owner grant, team-overlap grant, the deny path, and
 * strip_no_access_risks() filtering a list against the session user. All writes
 * go through the per-test transaction and roll back.
 *
 * _sep_flag() defaults every allow_* flag to '1' (true); we set the flags we
 * rely on explicitly so the result is independent of any live DB customization.
 */
declare(strict_types=1);

final class SeparationAccessTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::loadExtra('separation', 'access.php', 'queries.php', 'permissions.php');
        foreach ([
            'allow_owner_to_risk',
            'allow_team_member_to_risk',
            'allow_all_to_risk_noassign_team',
        ] as $flag) {
            update_setting($flag, '1', $this->txdb);
            $this->clearSettingCache($flag);
        }

        // Core's get_user_teams() reroutes non-admins through the Organizational
        // Hierarchy extra's business-unit filter when OH is enabled
        // (organizational_hierarchy setting = "1"), returning only teams in the
        // user's selected business unit. The users below have no BU, so the
        // team-overlap grant path would see an empty team list and wrongly deny.
        // Disable OH for this test so team lookups use the plain user_to_team
        // join that the separation grant logic is meant to exercise.
        update_setting('organizational_hierarchy', 'false', $this->txdb);
        $this->clearSettingCache('organizational_hierarchy');
        unset($GLOBALS['organizational_hierarchy_extra']);

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        // Forget the OH memo so the next test re-reads from the rolled-back DB
        // (back to "1") instead of inheriting this test's in-transaction 'false'.
        unset($GLOBALS['organizational_hierarchy_extra']);
        $this->clearSettingCache('organizational_hierarchy');
        parent::tearDown();
    }

    public function test_admin_sees_everything(): void
    {
        $admin = $this->createUser(admin: true);
        $risk = $this->createRisk(owner: $this->createUser(admin: false));

        self::assertTrue(extra_grant_access($admin, $risk + 1000));
    }

    public function test_owner_and_team_member_granted_unrelated_user_denied(): void
    {
        $team = $this->createTeam();
        $owner = $this->createUser(admin: false);           // owner, NOT on the team
        $member = $this->createUser(admin: false);          // on the team, not owner
        $other = $this->createUser(admin: false);           // neither

        $this->assignToTeam($member, $team);
        $risk = $this->createRisk(owner: $owner);           // risk owned by $owner
        $this->assignRiskToTeam($risk, $team);              // ...and on $team

        // Owner path grants even though the owner is not a team member.
        self::assertTrue(extra_grant_access($owner, $risk + 1000));
        // Team-overlap path grants the member.
        self::assertTrue(extra_grant_access($member, $risk + 1000));
        // An unrelated user (no team overlap, not owner/etc) is denied.
        self::assertFalse(extra_grant_access($other, $risk + 1000));
    }

    public function test_nonexistent_risk_is_denied(): void
    {
        $user = $this->createUser(admin: false);
        self::assertFalse(extra_grant_access($user, 9_999_999 + 1000));
    }

    public function test_strip_no_access_risks_filters_by_session_user(): void
    {
        $teamA = $this->createTeam();
        $teamB = $this->createTeam();
        $member = $this->createUser(admin: false);
        $this->assignToTeam($member, $teamA);

        $riskA = $this->createRisk(owner: $this->createUser(admin: false));
        $this->assignRiskToTeam($riskA, $teamA);            // member's team -> visible
        $riskB = $this->createRisk(owner: $this->createUser(admin: false));
        $this->assignRiskToTeam($riskB, $teamB);            // other team -> hidden

        $_SESSION['uid'] = $member;
        $rows = [
            ['id' => $riskA, 'subject' => 'A'],
            ['id' => $riskB, 'subject' => 'B'],
        ];
        $visible = strip_no_access_risks($rows);

        self::assertCount(1, $visible);
        self::assertSame($riskA, $visible[0]['id']);
    }

    /* ----------------------------- fixtures ----------------------------- */

    private function createUser(bool $admin): int
    {
        $roleId = $this->anyInt('role', 'value');
        $stamp = substr(uniqid('', true), -8);
        $username = 'sep_' . $stamp;
        $email = $stamp . '@example.test';
        $password = str_repeat('x', 60);
        $stmt = $this->txdb->prepare(
            "INSERT INTO user (`username`, `name`, `email`, `password`, `role_id`, `admin`, `type`)
             VALUES (:username, :name, :email, :password, :role_id, :admin, 'simplerisk')"
        );
        $stmt->bindParam(':username', $username, PDO::PARAM_LOB);
        $stmt->bindValue(':name', 'Sep Test ' . $stamp, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_LOB);
        $stmt->bindParam(':password', $password, PDO::PARAM_LOB);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->bindValue(':admin', $admin ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->txdb->lastInsertId();
    }

    private function createTeam(): int
    {
        $name = 'Team ' . substr(uniqid('', true), -6);
        $stmt = $this->txdb->prepare("INSERT INTO team (`name`) VALUES (:name)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$this->txdb->lastInsertId();
    }

    private function assignToTeam(int $userId, int $teamId): void
    {
        $stmt = $this->txdb->prepare("INSERT INTO user_to_team (user_id, team_id) VALUES (:u, :t)");
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':t', $teamId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function assignRiskToTeam(int $riskId, int $teamId): void
    {
        $stmt = $this->txdb->prepare("INSERT INTO risk_to_team (risk_id, team_id) VALUES (:r, :t)");
        $stmt->bindValue(':r', $riskId, PDO::PARAM_INT);
        $stmt->bindValue(':t', $teamId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function createRisk(int $owner): int
    {
        $source = $this->anyInt('source', 'value');
        $category = $this->anyInt('category', 'value');
        $subject = 'Risk ' . substr(uniqid('', true), -6);
        $stmt = $this->txdb->prepare(
            "INSERT INTO risks
                (status, subject, source, category, owner, manager, assessment, notes, submitted_by)
             VALUES
                ('Open', :subject, :source, :category, :owner, 0, '', '', 1)"
        );
        $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
        $stmt->bindValue(':source', $source, PDO::PARAM_INT);
        $stmt->bindValue(':category', $category, PDO::PARAM_INT);
        $stmt->bindValue(':owner', $owner, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->txdb->lastInsertId();
    }

    /** Lowest existing id from a lookup table, so FK-like columns resolve. */
    private function anyInt(string $table, string $column): int
    {
        $v = $this->txdb->query("SELECT `$column` FROM `$table` ORDER BY `$column` LIMIT 1")->fetchColumn();
        return $v !== false ? (int)$v : 1;
    }
}
