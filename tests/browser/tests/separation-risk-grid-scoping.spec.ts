import { test, expect } from '@playwright/test';
import { login, loginRestricted, apiPost, unique } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { seedTeam, assignUserToTeam, assignRiskToTeam } from '../lib/seed';
import { userIdByUsername } from '../lib/db';
import { readSettings, restoreSettings, writeSetting, type SettingsSnapshot } from '../lib/settings';

// Browser counterpart to the PHPUnit SeparationExtraRiskScopingTest: a team-scoped
// (non-admin) user's risk grid must render only their own team's risks. The grid's
// server-side AJAX applies the same get_user_teams_query() filter, but this spec
// asserts the rows actually RENDER in the DOM — the part curl cannot see.

let snap: SettingsSnapshot = {};

test.beforeEach(async () => {
  snap = readSettings(['team_separation', 'allow_team_member_to_risk', 'allow_all_to_risk_noassign_team']);
  writeSetting('team_separation', '1');
  writeSetting('allow_team_member_to_risk', '1');
  // Hide unassigned risks so only the team-scoped risk is visible (deterministic).
  writeSetting('allow_all_to_risk_noassign_team', '0');
});

test.afterEach(() => {
  restoreSettings(snap);
  sweepE2E();
});

test('team-scoped user sees only their own team risk in the grid', async ({ page }) => {
  const teamA = seedTeam('UI_SEP_A');
  const teamB = seedTeam('UI_SEP_B');
  assignUserToTeam(userIdByUsername('e2e_restricted_user'), teamA); // member of A only

  // Seed the two risks as the admin first (apiPost uses the page session).
  await login(page);
  const subjA = unique('RISK_UI_SEP_A');
  const subjB = unique('RISK_UI_SEP_B');
  const a = await apiPost(page, '/api/v2/risks/submit', { subject: subjA });
  const b = await apiPost(page, '/api/v2/risks/submit', { subject: subjB });
  expect(a.status).toBe(200);
  expect(b.status).toBe(200);
  assignRiskToTeam((a.json.data.risk_id as number) - 1000, teamA);
  assignRiskToTeam((b.json.data.risk_id as number) - 1000, teamB);

  // Drop the admin session so '/' shows the login form again, then log in as the
  // restricted (team A) user on the same page.
  await page.context().clearCookies();
  await loginRestricted(page);

  const ajaxPromise = page.waitForResponse(
    (r) => r.url().includes('/api/v2/risk_management/review_risks'),
    { timeout: 15_000 },
  );
  await page.goto('/management/review_risks.php');
  expect((await ajaxPromise).status()).toBe(200);

  const rows = page.locator('table.dataTable tbody tr, table tbody tr');
  await expect(rows.filter({ hasText: subjA })).toHaveCount(1, { timeout: 10_000 });
  await expect(rows.filter({ hasText: subjB })).toHaveCount(0);
});
