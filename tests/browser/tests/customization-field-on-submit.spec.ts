import { test, expect } from '@playwright/test';
import { login, unique } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { createCustomFieldAdmin } from '../lib/seed';
import { readCustomFieldValue } from '../lib/db';
import { readSettings, restoreSettings, writeSetting, type SettingsSnapshot } from '../lib/settings';

// Guards the DOM/AJAX contract curl cannot see: a custom field created on the
// admin page must RENDER as an input on the live submit form, and its value must
// be carried by the AJAX submit into custom_risk_data. A regression that stops
// rendering the field, or drops it from the POST, breaks this.

let snap: SettingsSnapshot = {};

test.beforeEach(async () => {
  snap = readSettings(['customization']);
  writeSetting('customization', '1');
});

test.afterEach(() => {
  restoreSettings(snap);
  sweepE2E();
});

test('custom field renders on the submit form and its value persists on save', async ({ page }) => {
  await login(page);
  const fieldName = unique('UI_CF');
  const fieldId = await createCustomFieldAdmin(page, fieldName);

  await page.goto('/management/index.php');
  const customInput = page.locator(`input[name="custom_field_${fieldId}"]`).first();
  await expect(customInput).toBeVisible({ timeout: 10_000 });

  const subject = unique('RISK_UI_CF');
  const sentinel = `ui-custom-${fieldId}`;
  await page.locator("input[name='subject']").first().fill(subject);
  await customInput.fill(sentinel);

  // The submit button is type='button' → AJAX POST to /management/index.php,
  // then addRisk() navigates to the new risk's detail page.
  const [response] = await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes('/management/index.php') && r.request().method() === 'POST',
      { timeout: 15_000 },
    ),
    page.locator('.save-risk-form').first().click(),
  ]);
  expect(response.status()).toBeLessThan(400);
  await page.waitForURL(/\/management\/view\.php\?id=\d+/, { timeout: 15_000 });

  // The custom value landed in custom_risk_data. URL id is the PUBLIC id (db+1000).
  const pubId = parseInt(page.url().match(/id=(\d+)/)![1], 10);
  expect(await readCustomFieldValue(pubId - 1000, fieldId)).toBe(sentinel);
});
