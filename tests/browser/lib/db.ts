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

/** A plain risks column for the risk with this subject (e.g. 'reference_id'). */
export function riskColumn(subject: string, column: string): string {
  const subjJson = JSON.stringify(subject);
  // column is validated to a safe identifier (letters/underscore) before interpolation.
  if (!/^[A-Za-z_]+$/.test(column)) throw new Error(`unsafe column: ${column}`);
  return phpScalar(`$s=$db->prepare("SELECT \`${column}\` FROM risks WHERE subject = ?"); $s->execute([${subjJson}]); echo (string)$s->fetchColumn();`);
}

/** The current calculated_risk for the risk with this subject (from risk_scoring). */
export function riskCalculatedRisk(subject: string): string {
  const subjJson = JSON.stringify(subject);
  return phpScalar(`$s=$db->prepare("SELECT rs.calculated_risk FROM risk_scoring rs JOIN risks r ON r.id = rs.id WHERE r.subject = ?"); $s->execute([${subjJson}]); echo (string)$s->fetchColumn();`);
}

/** Count of framework_control_test_audits initiated for a given test id. */
export function countAuditsForTest(testId: number): number {
  const n = phpScalar(`echo (int)$db->query("SELECT COUNT(*) FROM framework_control_test_audits WHERE test_id = ${(testId | 0)}")->fetchColumn();`);
  return parseInt(n, 10) || 0;
}
