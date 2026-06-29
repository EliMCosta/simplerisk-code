import { containerPhp } from './container';

/**
 * Safety-net cleanup: delete every E2E_-prefixed row the browser suite (or a
 * mid-test abort) may have left behind. SimpleRisk browser tests have no
 * transaction rollback, so this runs in afterEach to guarantee isolation.
 *
 * Implemented as a one-shot in-container PHP snippet (containerPhp), deleting
 * children before parents. Mirrors the PHPUnit suite's E2E_ prefix convention.
 */
export function sweepE2E(): void {
  const php = `<?php
require '/var/www/simplerisk/includes/functions.php';
$db = db_open();
$db->exec("DELETE FROM framework_control_mappings WHERE reference_name = 'E2E'");
$db->exec("DELETE FROM framework_control_test_results WHERE test_audit_id IN (SELECT id FROM framework_control_test_audits WHERE name LIKE 'E2E_%')");
$db->exec("DELETE FROM framework_control_test_audits WHERE name LIKE 'E2E_%'");
$db->exec("DELETE FROM framework_control_tests WHERE name LIKE 'E2E_%'");
$db->exec("DELETE FROM framework_controls WHERE short_name LIKE 'E2E_%'");
$db->exec("DELETE FROM frameworks WHERE name LIKE 'E2E_%'");
$db->exec("DELETE FROM comments WHERE comment LIKE 'E2E_%'");
$db->exec("DELETE FROM risks WHERE subject LIKE 'E2E_%'");
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
