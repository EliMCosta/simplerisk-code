import { containerPhp } from './container';

/**
 * Read-only DB helpers that shell out to the in-container PHP (where db_open()
 * and the schema live). Used to verify a browser-driven write landed — the
 * source of truth — without depending on a particular UI rendering.
 */

function phpScalar(phpExpr: string): string {
  return containerPhp(
    `<?php require '/var/www/simplerisk/includes/functions.php'; $db=db_open(); ${phpExpr} db_close($db);`,
  ).trim();
}

/** Count of risks whose subject matches exactly (subject is E2E_-prefixed). */
export function countRisksBySubject(subject: string): number {
  const subjJson = JSON.stringify(subject);
  const n = phpScalar(`$s=$db->prepare("SELECT COUNT(*) FROM risks WHERE subject = ?"); $s->execute([${subjJson}]); echo (int)$s->fetchColumn();`);
  return parseInt(n, 10) || 0;
}

/** Count of framework_control_test_audits initiated for a given test id. */
export function countAuditsForTest(testId: number): number {
  const n = phpScalar(`echo (int)$db->query("SELECT COUNT(*) FROM framework_control_test_audits WHERE test_id = ${(testId | 0)}")->fetchColumn();`);
  return parseInt(n, 10) || 0;
}
