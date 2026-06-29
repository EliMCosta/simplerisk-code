import { containerPhp } from './container';

/**
 * Settings snapshot/restore for the browser suite — the Playwright mirror of the
 * PHPUnit E2ETestCase readSetting/writeSetting pattern. Browser specs run host-side
 * against the live container (no transaction rollback) and with workers=1 over a
 * shared DB, so any spec that needs an Extra ENABLED must snapshot the settings it
 * touches in beforeEach and restore them verbatim in afterEach.
 *
 * Values are round-tripped to the in-container PHP as base64-encoded JSON so names
 * and values never have to be quote-escaped into a PHP string literal.
 */

export type SettingsSnapshot = Record<string, string | null>;

/** Read the current value of each named setting (null = absent row). */
export function readSettings(names: string[]): SettingsSnapshot {
  const b64 = Buffer.from(JSON.stringify(names)).toString('base64');
  const out = containerPhp(`<?php
require '/var/www/simplerisk/includes/functions.php';
$db = db_open();
$names = json_decode(base64_decode('${b64}'), true);
$res = [];
$s = $db->prepare("SELECT value FROM settings WHERE name = ?");
foreach ($names as $n) { $s->execute([$n]); $v = $s->fetchColumn(); $res[$n] = $v === false ? null : (string)$v; }
db_close($db);
echo json_encode($res);
`);
  return JSON.parse(out.trim());
}

/** Read a single setting value (null = absent). */
export function settingValue(name: string): string | null {
  return readSettings([name])[name];
}

/** Restore a snapshot produced by readSettings (null deletes the row → absent again). */
export function restoreSettings(snapshot: SettingsSnapshot): void {
  const b64 = Buffer.from(JSON.stringify(snapshot)).toString('base64');
  containerPhp(`<?php
require '/var/www/simplerisk/includes/functions.php';
$db = db_open();
$snap = json_decode(base64_decode('${b64}'), true);
$del = $db->prepare("DELETE FROM settings WHERE name = ?");
$up = $db->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ($snap as $n => $v) {
    if ($v === null) { $del->execute([$n]); }
    else { $up->execute([$n, (string)$v]); }
}
db_close($db);
`);
}

/**
 * Force a setting for a spec. Prefer the snapshot/restore pair in beforeEach/
 * afterEach; this is for mid-spec toggles (e.g. flipping allow_all_to_risk_noassign_team).
 * value=null deletes the row.
 */
export function writeSetting(name: string, value: string | null): void {
  const b64 = Buffer.from(JSON.stringify({ name, value })).toString('base64');
  containerPhp(`<?php
require '/var/www/simplerisk/includes/functions.php';
$db = db_open();
$d = json_decode(base64_decode('${b64}'), true);
if ($d['value'] === null) { $db->prepare("DELETE FROM settings WHERE name = ?")->execute([$d['name']]); }
else { $db->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([$d['name'], (string)$d['value']]); }
db_close($db);
`);
}
