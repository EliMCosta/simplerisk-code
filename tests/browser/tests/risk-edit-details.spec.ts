import { test, expect } from '@playwright/test';
import { login, unique, apiPost } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { riskColumn } from '../lib/db';

// The Details tab "Edit" -> change a field -> "Save Details" AJAX flow. The save
// posts a FormData blob to /api/v2/management/risk/saveDetails (risk.js:722
// updateRisk), NOT a full form submit — so a regression that made the button a
// real submit, or detached the handler, would silently break editing. This spec
// drives the real DOM flow and proves the persisted value round-trips to the DB.
//
// We seed the risk via /api/v2/risks/submit (apiPost) so the spec only exercises
// the edit-save UX, not submit. reference_id is a plain VARCHAR(20) column, so
// it is a clean field to set and read back.

test.afterEach(() => sweepE2E());

test('details-tab edit save persists reference_id via AJAX', async ({ page }) => {
  await login(page);
  const subject = unique('EDIT_UI');
  const res = await apiPost(page, '/api/v2/risks/submit', { subject });
  const publicId = res.json?.data?.risk_id;
  expect(publicId).toBeTruthy();

  await page.goto(`/management/view.php?id=${publicId}`);

  // Enter edit mode (partials/details.php renders button[name="edit_details"]).
  await page.locator('button[name="edit_details"]').first().click();
  // The edit form is AJAX-loaded into .content-container; reference_id input
  // (displayrisks.php / display.php) appears then.
  const ref = page.locator('.content-container input[name="reference_id"]').first();
  await ref.waitFor({ state: 'visible' });
  const refValue = 'REF-' + process.pid;
  await ref.fill(refValue);

  // Save Details (button.save-details, name="update_details", display.php:1150)
  // -> updateRisk() -> POST saveDetails. Lock the AJAX response.
  const [response] = await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes('/api/v2/management/risk/saveDetails') && r.request().method() === 'POST',
      { timeout: 20_000 },
    ),
    page.locator('button.save-details').first().click(),
  ]);
  expect(response.status()).toBeLessThan(400);

  // DB truth: the reference_id persisted.
  expect(await riskColumn(subject, 'reference_id')).toBe(refValue);
});
