import { test, expect } from '@playwright/test';
import { login, unique, apiPost } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { riskCalculatedRisk } from '../lib/db';

// The Score tab "Update" AJAX flow. A fresh risk is scored as Custom (method 5,
// baseline 10), so the visible update form is form[name="update_custom"]. Its
// Update button submits via updateScore() (risk.js:1299) -> POST
// /api/v2/management/risk/saveScore?id=&action=update_custom. Re-scoring Custom
// recomputes calculated_risk (= the custom value, clamped to 10). This locks the
// score-recompute AJAX wiring and proves the new score round-trips to risk_scoring.

test.afterEach(() => sweepE2E());

test('custom score update recomputes calculated_risk via AJAX', async ({ page }) => {
  await login(page);
  const subject = unique('SCORE_UI');
  const res = await apiPost(page, '/api/v2/risks/submit', { subject });
  const publicId = res.json?.data?.risk_id;
  expect(publicId).toBeTruthy();

  const before = await riskCalculatedRisk(subject);

  await page.goto(`/management/view.php?id=${publicId}`);

  // The scoring details are collapsed: .show-score (the accordion toggle,
  // score.php:20) expands .scoredetails (containing the .update-score button);
  // .update-score (risk.js:615) then reveals the #updatescore edit form. For a
  // fresh (Custom) risk that is "Update Custom Score" (display.php:3136).
  await page.locator('.show-score').first().click();
  await page.locator('.update-score').first().click();
  const form = page.locator('form[name="update_custom"]').first();
  await form.waitFor({ state: 'visible' });
  await form.locator('input[name="Custom"]').first().fill('3.5');

  const [response] = await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes('/api/v2/management/risk/saveScore') && r.url().includes('action=update_custom') && r.request().method() === 'POST',
      { timeout: 20_000 },
    ),
    form.locator('button[name="update_custom"]').first().click(),
  ]);
  expect(response.status()).toBeLessThan(400);

  const after = await riskCalculatedRisk(subject);
  expect(after).not.toBe(before);
  expect(Number(after)).toBeCloseTo(3.5, 1);
});
