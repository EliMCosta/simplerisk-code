<?php
/**
 * Shared risk-management seeding, action, read and cleanup helpers for the e2e
 * risk journey tests. To be used by a class that also extends E2ETestCase (it
 * relies on authedPost / authedGet / authedPostAs / decodeJson / request).
 *
 * submitRisk() drives the real /api/v2/risks/submit route and tracks the created
 * db id; the other action* helpers POST the real /api/v2/management/risk/* Slim
 * routes. Each created risk is deleted in tearDown via delete_risk(), plus a
 * safety-net sweep of any E2E_RISK_* row and its children.
 *
 * The exception-path tests need a SECOND session that lacks a specific
 * permission. Because check_permission()/has_permission() read $_SESSION[<key>]
 * populated from permission_to_user at login time, we provision a restricted
 * user (all permissions minus the excluded ones) and log it into a SEPARATE
 * cookie jar (E2ETestCase::loginSession / authedPostAs) with its OWN csrf token.
 * resetRestrictedUserPermissions() re-grants everything in tearDown so the
 * account self-heals for the next run.
 *
 * Public id = db id + 1000 (api_v2/includes/risks.php submit returns risk_id as
 * the public id; the management/risk/* handlers all take ?id=<publicId>).
 */
declare(strict_types=1);

trait RiskTestSupportTrait
{
    private const SUBJECT_PREFIX = 'E2E_RISK_';

    /** Prefix for extras seed rows (teams, business units, custom fields) — swept in tearDown. */
    private const EXTRA_PREFIX = 'E2E_';

    /** Dedicated restricted account for permission-denied exception tests. */
    protected const RESTRICTED_USER = 'e2e_restricted_user';
    protected const RESTRICTED_PASS = 'E2E-Restricted-2026!xQ';

    /** @var int[] db ids of risks created during a test, deleted in tearDown. */
    private array $createdRiskDbIds = [];

    /** @var int db id of the restricted user (0 until provisioned). */
    private int $restrictedUserUid = 0;

    // ------------------------------------------------------------------
    // Submit + action helpers (admin session)
    // ------------------------------------------------------------------

    /**
     * Submit a risk over the API and return its PUBLIC id (db id + 1000).
     * Tracks the db id for tearDown cleanup. $extra merges into the POST body so
     * scoring-method tests can send scoring_method + metric params at submit.
     */
    protected function submitRisk(string $subject, array $extra = []): int
    {
        [$code, , $body] = $this->actionPost('/api/v2/risks/submit', ['subject' => $subject] + $extra);
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
     * risk detail page so getTabHtml() (called by saveDetails/saveScore/
     * saveMitigation/saveReview/updateStatus/closerisk) does not warn on a
     * missing HTTP_REFERER, which under display_errors=on would prepend text and
     * corrupt the JSON response.
     */
    protected function actionPost(string $path, array $post): array
    {
        return $this->authedPost($path, $post, ['referer' => 'https://localhost/management/view.php']);
    }

    /** Authenticated GET to a risk route with the same Referer pin. */
    protected function actionGet(string $path): array
    {
        return $this->authedGet($path, ['referer' => 'https://localhost/management/view.php']);
    }

    // ------------------------------------------------------------------
    // Restricted session helpers (second session, fewer permissions)
    // ------------------------------------------------------------------

    /**
     * Idempotently ensure the restricted user exists, has the fixed password,
     * admin=0, and is granted every permission EXCEPT the named keys. Returns
     * ['uid'=>..., 'username'=>..., 'pass'=>...]. MUST be called before
     * loginRestrictedSession() — permissions are read from permission_to_user at
     * login. admin=0 deliberately (the admin flag can short-circuit checks and
     * mask the permission-under-test).
     */
    protected function ensureRestrictedUser(array $withoutPermissionKeys = []): array
    {
        $db = db_open();

        $stmt = $db->prepare("SELECT value FROM user WHERE username = ?");
        $stmt->execute([self::RESTRICTED_USER]);
        $uid = (int) $stmt->fetchColumn();
        $hash = password_hash(self::RESTRICTED_PASS, PASSWORD_BCRYPT);

        if (!$uid) {
            $role = (int) $db->query("SELECT value FROM role ORDER BY value LIMIT 1")->fetchColumn();
            $ins = $db->prepare(
                "INSERT INTO user (username, name, email, password, role_id, admin, type, enabled, change_password)
                 VALUES (?, ?, ?, ?, ?, 0, 'simplerisk', 1, 0)"
            );
            $ins->execute([
                self::RESTRICTED_USER,
                'E2E Restricted Suite',
                'e2e-restricted@example.test',
                $hash,
                $role,
            ]);
            $uid = (int) $db->lastInsertId();
        } else {
            $db->prepare("UPDATE user SET password = ?, enabled = 1, admin = 0, change_password = 0 WHERE value = ?")
                ->execute([$hash, $uid]);
        }

        // Grant everything except the excluded keys.
        $del = $db->prepare("DELETE FROM permission_to_user WHERE user_id = ?");
        $del->execute([$uid]);
        if ($withoutPermissionKeys) {
            $placeholders = implode(',', array_fill(0, count($withoutPermissionKeys), '?'));
            $grant = $db->prepare(
                "INSERT INTO permission_to_user (permission_id, user_id)
                 SELECT id, ? FROM permissions WHERE `key` NOT IN ({$placeholders})"
            );
            $grant->execute(array_merge([$uid], $withoutPermissionKeys));
        } else {
            $db->prepare("INSERT INTO permission_to_user (permission_id, user_id) SELECT id, ? FROM permissions")
                ->execute([$uid]);
        }

        db_close($db);
        $this->restrictedUserUid = $uid;
        return ['uid' => $uid, 'username' => self::RESTRICTED_USER, 'pass' => self::RESTRICTED_PASS];
    }

    /** Re-grant ALL permissions to the restricted user (tearDown self-heal). */
    protected function resetRestrictedUserPermissions(): void
    {
        if (!$this->restrictedUserUid) {
            return;
        }
        $db = db_open();
        $del = $db->prepare("DELETE FROM permission_to_user WHERE user_id = ?");
        $del->execute([$this->restrictedUserUid]);
        $db->prepare("INSERT INTO permission_to_user (permission_id, user_id) SELECT id, ? FROM permissions")
            ->execute([$this->restrictedUserUid]);
        db_close($db);
    }

    /**
     * Log the restricted user into a separate 'restricted' session (fresh cookie
     * jar + its own csrf cache). Returns true once authenticated.
     */
    protected function loginRestrictedSession(string $sessionName = 'restricted'): bool
    {
        return $this->loginSession(self::RESTRICTED_USER, self::RESTRICTED_PASS, $sessionName);
    }

    /** Authenticated POST from the restricted session. */
    protected function restrictedActionPost(string $path, array $post): array
    {
        return $this->authedPostAs('restricted', $path, $post, ['referer' => 'https://localhost/management/view.php']);
    }

    /** Authenticated POST to /api/v2/risks/submit from the restricted session. */
    protected function restrictedSubmitPost(array $post): array
    {
        return $this->authedPostAs('restricted', '/api/v2/risks/submit', $post);
    }

    // ------------------------------------------------------------------
    // Extras seed helpers (team / business-unit / custom-field)
    // ------------------------------------------------------------------
    // Separation and Org-Hierarchy scoping tests need real team/BU membership.
    // ensureRestrictedUser() grants permissions but does NOT seed user_to_team,
    // so these helpers create the junction rows directly. Every name is E2E_-
    // prefixed so sweepExtraSeed() (called in tearDown) reclaims them; the team
    // and business_unit PKs auto-increment, so lastInsertId() returns the id.

    /** E2E_-prefixed unique name for a team/BU/custom-field (swept in tearDown). */
    private function e2eName(string $tag): string
    {
        return self::EXTRA_PREFIX . $tag . '_' . uniqid();
    }

    /** Insert a team, return its id (team.value auto-increments). */
    protected function seedTeam(string $tag = 'TEAM'): int
    {
        $db = db_open();
        $db->prepare("INSERT INTO team (name) VALUES (?)")->execute([$this->e2eName($tag)]);
        $id = (int) $db->lastInsertId();
        db_close($db);
        return $id;
    }

    /** Link a user to a team (INSERT IGNORE so re-runs are idempotent). */
    protected function assignUserToTeam(int $uid, int $teamId): void
    {
        $db = db_open();
        $db->prepare("INSERT IGNORE INTO user_to_team (user_id, team_id) VALUES (?, ?)")->execute([$uid, $teamId]);
        db_close($db);
    }

    /** Link a risk (DB id) to a team. */
    protected function assignRiskToTeam(int $riskDbId, int $teamId): void
    {
        $db = db_open();
        $db->prepare("INSERT IGNORE INTO risk_to_team (risk_id, team_id) VALUES (?, ?)")->execute([$riskDbId, $teamId]);
        db_close($db);
    }

    /**
     * Insert a business unit and (optionally) link teams to it. Returns the BU id.
     * business_unit_to_team has a UNIQUE(team_id) — a team may belong to at most one
     * BU — so use fresh E2E_ teams per test to avoid colliding with real mappings.
     */
    protected function seedBusinessUnit(string $tag = 'BU', int ...$teamIds): int
    {
        $db = db_open();
        $db->prepare("INSERT INTO business_unit (name, description) VALUES (?, 'E2E')")->execute([$this->e2eName($tag)]);
        $buId = (int) $db->lastInsertId();
        foreach ($teamIds as $teamId) {
            $db->prepare("INSERT IGNORE INTO business_unit_to_team (business_unit_id, team_id) VALUES (?, ?)")->execute([$buId, $teamId]);
        }
        db_close($db);
        return $buId;
    }

    /** Pin a user's selected business unit (NULL clears it). Read by get_user_teams' OH override. */
    protected function setUserSelectedBusinessUnit(int $uid, ?int $buId): void
    {
        $db = db_open();
        $db->prepare("UPDATE user SET selected_business_unit = ? WHERE value = ?")->execute([$buId, $uid]);
        db_close($db);
    }

    // ------------------------------------------------------------------
    // Scoring payload builders
    // ------------------------------------------------------------------
    // Scoring method: 1=Classic, 2=CVSS, 3=DREAD, 4=OWASP, 5=Custom.
    // NOTE the submit-vs-saveScore field-name divergence:
    //  - submit (api/v2/includes/risks.php): DREAD is prefixed DREADDamage…,
    //    OWASP is prefixed OWASPSkillLevel… (see saveDetailsForm/submit).
    //  - saveScore ?action=update_dread|update_owasp (includes/api.php:3154+):
    //    UNPREFIXED — DamagePotential, SkillLevel, EaseOfDiscovery, … .
    // Two builder variants are provided for DREAD and OWASP.

    protected static function classicScorePayload(int $likelihood, int $impact): array
    {
        return ['scoring_method' => 1, 'likelihood' => $likelihood, 'impact' => $impact];
    }

    /**
     * CVSS v2 metrics using the SHORT codes the CVSS_scoring lookup table expects
     * (get_cvss_numeric_value: abrv_metric_value = 'N'/'L'/'P'/…, NOT 'AV:N').
     * Partial impacts + medium complexity yield a mid (< 10) score, which matters
     * because a fresh risk starts at calculated_risk=10 (submit defaults to Custom
     * 10) and the score functions only append history when the value CHANGES.
     * ALL 14 fields are sent because saveScoreForm's update_cvss reads each one
     * raw from $_POST (an absent key raises an undefined-key notice → 500).
     * Temporal/environmental metrics use 'ND' (not defined).
     */
    protected static function cvssScorePayload(): array
    {
        return [
            'scoring_method' => 2,
            'AccessVector' => 'N', 'AccessComplexity' => 'M', 'Authentication' => 'N',
            'ConfImpact' => 'P', 'IntegImpact' => 'P', 'AvailImpact' => 'P',
            'Exploitability' => 'ND', 'RemediationLevel' => 'ND', 'ReportConfidence' => 'ND',
            'CollateralDamagePotential' => 'ND', 'TargetDistribution' => 'ND',
            'ConfidentialityRequirement' => 'ND', 'IntegrityRequirement' => 'ND', 'AvailabilityRequirement' => 'ND',
        ];
    }

    /** DREAD params for the SUBMIT route (prefixed). */
    protected static function dreadSubmitPayload(): array
    {
        return [
            'scoring_method' => 3,
            'DREADDamage' => 8, 'DREADReproducibility' => 7, 'DREADExploitability' => 9,
            'DREADAffectedUsers' => 8, 'DREADDiscoverability' => 6,
        ];
    }

    /** DREAD params for saveScore ?action=update_dread (UNPREFIXED). */
    protected static function dreadSaveScorePayload(): array
    {
        return [
            'DamagePotential' => 8, 'Reproducibility' => 7, 'Exploitability' => 9,
            'AffectedUsers' => 8, 'Discoverability' => 6,
        ];
    }

    /**
     * OWASP params for the SUBMIT route (prefixed). All-LOW → LOW/LOW → 0, a
     * score that differs from a fresh risk's initial 10 (so a re-score records
     * history). LOW factors are 1.
     */
    protected static function owaspSubmitPayload(): array
    {
        return [
            'scoring_method' => 4,
            'OWASPSkillLevel' => 1, 'OWASPMotive' => 1, 'OWASPOpportunity' => 1, 'OWASPSize' => 1,
            'OWASPEaseOfDiscovery' => 1, 'OWASPEaseOfExploit' => 1, 'OWASPAwareness' => 1, 'OWASPIntrusionDetection' => 1,
            'OWASPLossOfConfidentiality' => 1, 'OWASPLossOfIntegrity' => 1, 'OWASPLossOfAvailability' => 1, 'OWASPLossOfAccountability' => 1,
            'OWASPFinancialDamage' => 1, 'OWASPReputationDamage' => 1, 'OWASPNonCompliance' => 1, 'OWASPPrivacyViolation' => 1,
        ];
    }

    /** OWASP params for saveScore ?action=update_owasp (UNPREFIXED). All-LOW → 0. */
    protected static function owaspSaveScorePayload(): array
    {
        return [
            'SkillLevel' => 1, 'Motive' => 1, 'Opportunity' => 1, 'Size' => 1,
            'EaseOfDiscovery' => 1, 'EaseOfExploit' => 1, 'Awareness' => 1, 'IntrusionDetection' => 1,
            'LossOfConfidentiality' => 1, 'LossOfIntegrity' => 1, 'LossOfAvailability' => 1, 'LossOfAccountability' => 1,
            'FinancialDamage' => 1, 'ReputationDamage' => 1, 'NonCompliance' => 1, 'PrivacyViolation' => 1,
        ];
    }

    protected static function customScorePayload(float $value): array
    {
        return ['scoring_method' => 5, 'Custom' => $value];
    }

    // ------------------------------------------------------------------
    // Read helpers (DB truth)
    // ------------------------------------------------------------------

    protected function uniqueSubject(string $tag): string
    {
        return self::SUBJECT_PREFIX . $tag . '_' . uniqid();
    }

    protected static function dbId(int $publicId): int
    {
        return $publicId - 1000;
    }

    /**
     * Decrypt a single stored value. The Encryption Extra stores free-text fields
     * (risk subject/comment, framework name, …) as ENC1: ciphertext at rest;
     * try_decrypt() recovers the plaintext and is an identity on plaintext, so this
     * is safe whether encryption is on OR off. Returns '' for null so callers can
     * compare cleanly. try_decrypt() is available because the suite runs inside the
     * simplerisk-app container (bootstrapped via includes/functions.php).
     */
    private function decryptValue(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return function_exists('try_decrypt') ? (string) try_decrypt($value) : (string) $value;
    }

    /**
     * Fetch $column from $table and return every value DECRYPTED. SQL equality can't
     * match ciphertext (AES-256-GCM uses a random nonce per encryption), so for any
     * encrypted-at-rest free-text column callers must decrypt in PHP and compare.
     * $whereSql/$params scope the candidate set (e.g. to one risk). Used for risk
     * subject, framework name, comment text, …
     */
    protected function selectDecryptedColumn(string $table, string $column, string $whereSql = '', array $params = []): array
    {
        $db = db_open();
        $sql = "SELECT `{$column}` FROM `{$table}`" . ($whereSql !== '' ? " WHERE {$whereSql}" : '');
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $values = $stmt->fetchAll(PDO::FETCH_COLUMN);
        db_close($db);
        return array_map(fn ($v) => $this->decryptValue($v), $values);
    }

    /** Count rows whose (encrypted-at-rest) $column decrypts to exactly $plaintext. */
    protected function countByDecryptedColumn(string $table, string $column, string $plaintext, string $whereSql = '', array $params = []): int
    {
        $values = $this->selectDecryptedColumn($table, $column, $whereSql, $params);
        return count(array_filter($values, fn ($v) => $v === $plaintext));
    }

    /** id, subject, status, mitigation_id, mgmt_review for a risk by public id. */
    protected function readRiskRow(int $publicId): array
    {
        $row = $this->fetchRow(
            'SELECT id, subject, status, mitigation_id, mgmt_review FROM risks WHERE id = ?',
            [self::dbId($publicId)]
        );
        self::assertIsArray($row, "risk db row missing for public id {$publicId}");
        $row['subject'] = $this->decryptValue($row['subject'] ?? null);
        return $row;
    }

    /** Persisted edit-details columns (category, source, owner, manager, reference_id, notes). */
    protected function readRiskDetailsRow(int $publicId): array
    {
        $row = $this->fetchRow(
            'SELECT id, subject, category, source, owner, manager, reference_id FROM risks WHERE id = ?',
            [self::dbId($publicId)]
        );
        if (is_array($row)) {
            $row['subject'] = $this->decryptValue($row['subject'] ?? null);
        }
        return $row;
    }

    /** risk_scoring columns for a risk by public id. */
    protected function readRiskScoringRow(int $publicId): array
    {
        return $this->fetchRow(
            'SELECT calculated_risk, scoring_method, CLASSIC_likelihood, CLASSIC_impact,
                    CVSS_AccessVector, DREAD_DamagePotential, OWASP_SkillLevel, Custom
             FROM risk_scoring WHERE id = ?',
            [self::dbId($publicId)]
        );
    }

    /** Count of risk_scoring_history rows for a risk by public id. */
    protected function countScoringHistory(int $publicId): int
    {
        return $this->countScalar(
            'SELECT COUNT(*) FROM risk_scoring_history WHERE risk_id = ?',
            [self::dbId($publicId)]
        );
    }

    private function fetchRow(string $sql, array $params = []): ?array
    {
        $db = db_open();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        db_close($db);
        return $row === false ? null : $row;
    }

    protected function countScalar(string $sql, array $params = []): int
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
     * status whose name is not 'New' and not 'Closed'. Returns ['value','name'].
     */
    protected function changeableStatus(): ?array
    {
        $row = $this->fetchRow("SELECT value, name FROM status WHERE name != 'New' AND name != 'Closed' ORDER BY value LIMIT 1");
        return $row === null ? null : ['value' => (int) $row['value'], 'name' => (string) $row['name']];
    }

    /** A valid mgmt-review decision value (the review table's value column). */
    protected function reviewDecisionValue(): int
    {
        return $this->countScalar('SELECT value FROM review ORDER BY value LIMIT 1') ?: 1;
    }

    /** First available close_reason value. */
    protected function firstCloseReasonValue(): int
    {
        return $this->countScalar('SELECT value FROM close_reason ORDER BY value LIMIT 1') ?: 1;
    }

    /** First available category/source/likelihood/impact value (avoid hardcoding ids). */
    protected function firstCategoryId(): int
    {
        return $this->countScalar('SELECT value FROM category ORDER BY value LIMIT 1') ?: 1;
    }

    protected function firstSourceId(): int
    {
        return $this->countScalar('SELECT value FROM source ORDER BY value LIMIT 1') ?: 1;
    }

    protected function firstLikelihoodId(): int
    {
        return $this->countScalar('SELECT value FROM likelihood ORDER BY value LIMIT 1') ?: 1;
    }

    protected function firstImpactId(): int
    {
        return $this->countScalar('SELECT value FROM impact ORDER BY value LIMIT 1') ?: 1;
    }

    /**
     * The test admin's user id. mitigation_owner (and any other owner field)
     * must reference a real user, and this process's own $_SESSION is empty (it
     * curls Apache over HTTP), so we read the provisioned admin's id directly.
     */
    protected function testAdminUid(): int
    {
        return $this->countScalar('SELECT value FROM user WHERE username = ?', [E2ETestCase::TEST_USER]) ?: 1;
    }

    // ------------------------------------------------------------------
    // Cleanup
    // ------------------------------------------------------------------

    protected function deleteRisk(int $dbId): void
    {
        // delete_risk cleans risks/comments/mitigations/mgmt_reviews/closures/files/risk_scoring/history.
        if (function_exists('delete_risk')) {
            delete_risk($dbId);
        }
    }

    /**
     * Last-resort cleanup: delete any leftover E2E_RISK_ risks and the children
     * delete_risk() would have removed for them, so escapee risks do not leave
     * orphaned rows behind. Children are deleted before the risks row.
     */
    protected function sweepE2ERisks(): void
    {
        $db = db_open();
        // The subject may be encrypted at rest (ENC1:), so a plaintext LIKE misses
        // escapees — decrypt each subject and match the prefix in PHP instead.
        $rows = $db->query('SELECT id, subject FROM risks')->fetchAll(PDO::FETCH_ASSOC);
        $ids = [];
        foreach ($rows as $r) {
            if (str_starts_with($this->decryptValue($r['subject']), self::SUBJECT_PREFIX)) {
                $ids[] = (int) $r['id'];
            }
        }
        if (!$ids) {
            db_close($db);
            return;
        }
        foreach ($ids as $id) {
            foreach (['mitigations', 'mgmt_reviews', 'closures', 'comments', 'files'] as $t) {
                $db->prepare("DELETE FROM `{$t}` WHERE risk_id = ?")->execute([$id]);
            }
            $db->prepare('DELETE FROM risk_scoring_history WHERE risk_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM residual_risk_scoring_history WHERE risk_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM risk_scoring WHERE id = ?')->execute([$id]);
        }
        $db->exec('DELETE FROM risks WHERE id IN (' . implode(',', $ids) . ')');
        db_close($db);
    }

    /**
     * Shared tearDown body for every risk test: delete tracked risks, sweep any
     * escapees, and restore the restricted user's permissions so the next run is
     * clean. The cookie jars (incl. the restricted session's) are unlinked by
     * E2ETestCase::tearDown.
     */
    protected function tearDownRisks(): void
    {
        foreach ($this->createdRiskDbIds as $dbId) {
            $this->deleteRisk($dbId);
        }
        $this->sweepE2ERisks();
        $this->sweepExtraSeed();
        $this->resetRestrictedUserPermissions();
    }

    /**
     * Delete every E2E_-prefixed extras seed row (teams, business units, custom
     * fields) and the junction / data rows that reference them. Best-effort within
     * a single connection; wrapped so a missing extras table (extra never activated
     * on this image) does not turn a green test red.
     */
    protected function sweepExtraSeed(): void
    {
        $db = db_open();
        try {
            // Junction rows referencing E2E_ teams (team_id FK by value, not name).
            $teams = $db->query("SELECT value FROM team WHERE name LIKE '" . self::EXTRA_PREFIX . "%'")->fetchAll(PDO::FETCH_COLUMN);
            if ($teams) {
                $in = implode(',', $teams);
                $db->exec("DELETE FROM business_unit_to_team WHERE team_id IN ({$in})");
                $db->exec("DELETE FROM risk_to_team WHERE team_id IN ({$in})");
                $db->exec("DELETE FROM user_to_team WHERE team_id IN ({$in})");
            }
            // Junction rows referencing E2E_ business units, then the units themselves.
            $bus = $db->query("SELECT id FROM business_unit WHERE name LIKE '" . self::EXTRA_PREFIX . "%'")->fetchAll(PDO::FETCH_COLUMN);
            if ($bus) {
                $in = implode(',', $bus);
                $db->exec("DELETE FROM business_unit_to_team WHERE business_unit_id IN ({$in})");
            }
            $db->exec("DELETE FROM business_unit WHERE name LIKE '" . self::EXTRA_PREFIX . "%'");
            $db->exec("DELETE FROM team WHERE name LIKE '" . self::EXTRA_PREFIX . "%'");
            // Custom fields + their template-group-field rows + any saved values.
            $cf = $db->query("SELECT id FROM custom_fields WHERE name LIKE '" . self::EXTRA_PREFIX . "%'")->fetchAll(PDO::FETCH_COLUMN);
            if ($cf) {
                $in = implode(',', $cf);
                $db->exec("DELETE FROM custom_risk_data WHERE field_id IN ({$in})");
                $db->exec("DELETE FROM custom_template_group_fields WHERE field_id IN ({$in})");
            }
            $db->exec("DELETE FROM custom_fields WHERE name LIKE '" . self::EXTRA_PREFIX . "%'");
            // Any custom value saved against an E2E_ risk (delete_risk does not know
            // about the extra's custom_risk_data table).
            $db->exec("DELETE FROM custom_risk_data WHERE risk_id IN (SELECT id FROM risks WHERE subject LIKE '" . self::SUBJECT_PREFIX . "%')");
        } catch (Throwable $e) {
            // A missing extras table means the extra was never activated here; the
            // seed helpers could not have created rows in it, so nothing to clean.
        }
        db_close($db);
    }
}
