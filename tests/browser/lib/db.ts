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

/**
 * Encryption-aware risk lookups. With the encryption extra on (this stack runs it on),
 * risks.subject is stored as `ENC1:` ciphertext, so a plaintext `WHERE subject = ?`
 * never matches. try_decrypt() is idempotent on plaintext and available after
 * require'ing functions.php, but the simplerisk DB user lacks EXECUTE on it as a SQL
 * routine — so we fetch recent rows and decrypt in PHP. E2E subjects are unique and
 * freshly created, so scanning the most-recent rows is sufficient.
 */

/** Count of risks whose (encrypted) subject decrypts to this exact value. */
export function countRisksBySubject(subject: string): number {
  const subjJson = JSON.stringify(subject);
  const n = phpScalar(
    `$rows=$db->query("SELECT subject FROM risks ORDER BY id DESC LIMIT 1000")->fetchAll(PDO::FETCH_COLUMN);` +
    `$c=0; foreach($rows as $enc){ if(try_decrypt($enc)===${subjJson}) $c++; } echo (int)$c;`,
  );
  return parseInt(n, 10) || 0;
}

/** A plain risks column for the risk whose (encrypted) subject decrypts to this value. */
export function riskColumn(subject: string, column: string): string {
  const subjJson = JSON.stringify(subject);
  // column is validated to a safe identifier (letters/underscore) before interpolation.
  if (!/^[A-Za-z_]+$/.test(column)) throw new Error(`unsafe column: ${column}`);
  return phpScalar(
    `$rows=$db->query("SELECT id, subject FROM risks ORDER BY id DESC LIMIT 1000")->fetchAll();` +
    `$id=0; foreach($rows as $r){ if(try_decrypt($r["subject"])===${subjJson}){ $id=(int)$r["id"]; break; } }` +
    `echo $id ? (string)$db->query("SELECT \`${column}\` FROM risks WHERE id=".(int)$id)->fetchColumn() : "";`,
  );
}

/** The current calculated_risk for the risk with this subject (from risk_scoring). */
export function riskCalculatedRisk(subject: string): string {
  const subjJson = JSON.stringify(subject);
  return phpScalar(
    `$rows=$db->query("SELECT id, subject FROM risks ORDER BY id DESC LIMIT 1000")->fetchAll();` +
    `$id=0; foreach($rows as $r){ if(try_decrypt($r["subject"])===${subjJson}){ $id=(int)$r["id"]; break; } }` +
    `echo $id ? (string)$db->query("SELECT calculated_risk FROM risk_scoring WHERE id=".(int)$id)->fetchColumn() : "";`,
  );
}

/** Count of framework_control_test_audits initiated for a given test id. */
export function countAuditsForTest(testId: number): number {
  const n = phpScalar(`echo (int)$db->query("SELECT COUNT(*) FROM framework_control_test_audits WHERE test_id = ${(testId | 0)}")->fetchColumn();`);
  return parseInt(n, 10) || 0;
}

/** The user id (user.value) for a username (0 if not found). */
export function userIdByUsername(username: string): number {
  const u = JSON.stringify(username);
  const n = phpScalar(`$s=$db->prepare("SELECT value FROM user WHERE username = ?"); $s->execute([${u}]); echo (int)$s->fetchColumn();`);
  return parseInt(n, 10) || 0;
}

/** The custom_fields.id for a field by name (0 if not found). */
export function customFieldIdByName(name: string): number {
  const j = JSON.stringify(name);
  const n = phpScalar(`$s=$db->prepare("SELECT id FROM custom_fields WHERE name = ?"); $s->execute([${j}]); echo (int)$s->fetchColumn();`);
  return parseInt(n, 10) || 0;
}

/** The saved value of a custom field for a risk (DB id) + field id, review_id=0. */
export function readCustomFieldValue(riskDbId: number, fieldId: number): string {
  const rid = riskDbId | 0;
  const fid = fieldId | 0;
  return phpScalar(`$s=$db->prepare("SELECT value FROM custom_risk_data WHERE risk_id = ? AND field_id = ? AND review_id = 0"); $s->execute([${rid}, ${fid}]); echo (string)$s->fetchColumn();`);
}
