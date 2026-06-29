import { test, expect } from '@playwright/test';
import { login, loginRestricted, apiPost, unique } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { seedTeam, seedBusinessUnit, assignUserToTeam, assignRiskToTeam } from '../lib/seed';
import { userIdByUsername } from '../lib/db';
import { readSettings, restoreSettings, writeSetting, type SettingsSnapshot } from '../lib/settings';

// The genuinely browser-only OH test: the header Business Unit switcher chip
// renders for a NON-admin (it is hidden for admins), and clicking a BU in the
// dropdown submits the select form — whose __csrf_magic is injected client-side
// by csrf-magic.js (the form has no server-rendered token) — and the active BU
// (the chip label) updates. The rescoping of the risk list is covered by the
// PHPUnit OrganizationalHierarchyScopingTest; this spec guards the DOM flow.

let snap: SettingsSnapshot = {};

test.beforeEach(async () => {
  snap = readSettings(['team_separation', 'organizational_hierarchy', 'allow_team_member_to_risk', 'allow_all_to_risk_noassign_team']);
  writeSetting('team_separation', '1');
  writeSetting('organizational_hierarchy', '1');
  writeSetting('allow_team_member_to_risk', '1');
  writeSetting('allow_all_to_risk_noassign_team', '0');
});

test.afterEach(() => {
  restoreSettings(snap);
  sweepE2E();
});

test('BU switcher renders for a non-admin and switching updates the active BU', async ({ page }) => {
  const t1 = seedTeam('UI_OH_T1');
  const t2 = seedTeam('UI_OH_T2');
  seedBusinessUnit('UI_OH_BU_X', [t1]);
  seedBusinessUnit('UI_OH_BU_Y', [t2]);
  const uid = userIdByUsername('e2e_restricted_user');
  assignUserToTeam(uid, t1); // member of both teams → both BUs accessible
  assignUserToTeam(uid, t2);

  await login(page);
  const subj1 = unique('RISK_UI_OH_1');
  const subj2 = unique('RISK_UI_OH_2');
  const r1 = await apiPost(page, '/api/v2/risks/submit', { subject: subj1 });
  const r2 = await apiPost(page, '/api/v2/risks/submit', { subject: subj2 });
  assignRiskToTeam((r1.json.data.risk_id as number) - 1000, t1);
  assignRiskToTeam((r2.json.data.risk_id as number) - 1000, t2);

  await page.context().clearCookies();
  await loginRestricted(page);

  // Land on an authenticated page; the BU chip renders in the header everywhere.
  // (We do NOT depend on the risk grid's AJAX — only on the switcher chip.)
  await page.goto('/management/review_risks.php');

  // The switcher chip renders for a non-admin (it is hidden for admins).
  const chip = page.locator('.business-unit-chip').first();
  await expect(chip).toBeVisible({ timeout: 15_000 });
  const labelBefore = (await page.locator('.bu-current-name').first().innerText()).trim();

  // Open the dropdown and submit the (single) other BU's form.
  await chip.click();
  const item = page.locator('.business-unit-switcher-items form button.dropdown-item').first();
  await expect(item).toBeVisible({ timeout: 10_000 });
  await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes('/business_unit/select') && r.request().method() === 'POST',
      { timeout: 15_000 },
    ),
    item.click(),
  ]);
  await page.waitForLoadState('domcontentloaded');

  // The chip label now reflects the switched Business Unit.
  const labelAfter = (await page.locator('.bu-current-name').first().innerText()).trim();
  expect(labelAfter.length).toBeGreaterThan(0);
  expect(labelAfter).not.toBe(labelBefore);
});
