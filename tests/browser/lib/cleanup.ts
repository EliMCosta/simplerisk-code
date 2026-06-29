import { containerPhp } from './container';
import { E2E_PREFIX } from './auth';

/**
 * Safety-net cleanup: delete every E2E_-prefixed row the browser suite (or a
 * mid-test abort) may have left behind. SimpleRisk browser tests have no
 * transaction rollback, so this runs in afterEach to guarantee isolation.
 *
 * Two naming conventions are swept:
 *  - RISK rows: the subject prefix is E2E_PREFIX (from ./auth, the same constant
 *    unique() uses), so the risk sweep is built from it — change the prefix in
 *    one place and both creation and cleanup follow.
 *  - COMPLIANCE rows (frameworks/controls/audits, reference_name='E2E'): use
 *    their own hardcoded 'E2E'/'E2E_' convention owned by ./seed, so those lines
 *    mirror seed's literals rather than E2E_PREFIX.
 *
 * Implemented as a one-shot in-container PHP snippet (containerPhp), deleting
 * children before parents. Mirrors the PHPUnit suite's E2E_ prefix convention.
 */
export function sweepE2E(): void {
  // Risk subject prefix — single source of truth with auth.unique().
  const riskLike = `${E2E_PREFIX}%`;
  const php = `<?php
require '/var/www/simplerisk/includes/functions.php';
$db = db_open();
$db->exec("DELETE FROM framework_control_mappings WHERE reference_name = 'E2E'");
$db->exec("DELETE FROM framework_control_test_results WHERE test_audit_id IN (SELECT id FROM framework_control_test_audits WHERE name LIKE 'E2E_%')");
$db->exec("DELETE FROM framework_control_test_audits WHERE name LIKE 'E2E_%'");
$db->exec("DELETE FROM framework_control_tests WHERE name LIKE 'E2E_%'");
$db->exec("DELETE FROM framework_controls WHERE short_name LIKE 'E2E_%'");
$db->exec("DELETE FROM frameworks WHERE name LIKE 'E2E_%'");
// Risk children first (delete_risk cascade), then the E2E risks themselves — so
// editing/scoring/close flows do not leave orphaned rows behind.
$db->exec("DELETE FROM comments WHERE comment LIKE '${riskLike}'");
$ids = $db->query("SELECT id FROM risks WHERE subject LIKE '${riskLike}'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($ids as $id) {
    foreach (['mitigations','mgmt_reviews','closures','comments','files'] as $t) {
        $db->prepare("DELETE FROM {$t} WHERE risk_id = ?")->execute([$id]);
    }
    $db->prepare('DELETE FROM risk_scoring_history WHERE risk_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM residual_risk_scoring_history WHERE risk_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM risk_scoring WHERE id = ?')->execute([$id]);
}
$db->exec("DELETE FROM risks WHERE subject LIKE '${riskLike}'");
// Extras seed rows (teams, business units, custom fields) + their junction/data
// rows. Wrapped in try/catch: if an extra was never activated its tables are
// absent, and that must not abort the risk sweep above (best-effort, like the TS).
try {
    $teams = $db->query("SELECT value FROM team WHERE name LIKE '${riskLike}'")->fetchAll(PDO::FETCH_COLUMN);
    if ($teams) {
        $in = implode(',', $teams);
        $db->exec("DELETE FROM business_unit_to_team WHERE team_id IN ({$in})");
        $db->exec("DELETE FROM risk_to_team WHERE team_id IN ({$in})");
        $db->exec("DELETE FROM user_to_team WHERE team_id IN ({$in})");
    }
    $bus = $db->query("SELECT id FROM business_unit WHERE name LIKE '${riskLike}'")->fetchAll(PDO::FETCH_COLUMN);
    if ($bus) {
        $in = implode(',', $bus);
        $db->exec("DELETE FROM business_unit_to_team WHERE business_unit_id IN ({$in})");
    }
    $db->exec("DELETE FROM business_unit WHERE name LIKE '${riskLike}'");
    $db->exec("DELETE FROM team WHERE name LIKE '${riskLike}'");
    $cf = $db->query("SELECT id FROM custom_fields WHERE name LIKE '${riskLike}'")->fetchAll(PDO::FETCH_COLUMN);
    if ($cf) {
        $in = implode(',', $cf);
        $db->exec("DELETE FROM custom_risk_data WHERE field_id IN ({$in})");
        $db->exec("DELETE FROM custom_template_group_fields WHERE field_id IN ({$in})");
    }
    $db->exec("DELETE FROM custom_fields WHERE name LIKE '${riskLike}'");
    // Clear the restricted user's selected BU so OH specs don't leak across runs.
    $db->exec("UPDATE user SET selected_business_unit = NULL WHERE username = 'e2e_restricted_user'");
} catch (Throwable $e) {}
db_close($db);
echo "swept";
`;
  try {
    containerPhp(php);
  } catch (e) {
    // Non-fatal: the sweeper is a best-effort safety net. A failure here should
    // not turn a passing test red; the next run sweeps again.
    // eslint-disable-next-line no-console
    console.warn('sweepE2E: podman exec failed (non-fatal):', (e as Error).message);
  }
}
