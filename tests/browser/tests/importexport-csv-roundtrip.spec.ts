import { test, expect } from '@playwright/test';
import { login, unique, csrfToken } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { countRisksBySubject } from '../lib/db';
import { readSettings, restoreSettings, writeSetting, type SettingsSnapshot } from '../lib/settings';

// The genuinely browser-only Import-Export flow: upload a CSV via the file input,
// confirm the import (two PRG steps whose staged path lives in the session), then
// export risks back as a CSV download. The data-path round-trip is also covered
// by the PHPUnit ImportExportExtraCsvTest; this spec guards the DOM/download flow.

let snap: SettingsSnapshot = {};

test.beforeEach(() => {
  snap = readSettings(['import_export']);
  writeSetting('import_export', '1');
});

test.afterEach(() => {
  restoreSettings(snap);
  sweepE2E();
});

test('import a CSV via the UI and export risks back as a CSV download', async ({ page }) => {
  await login(page);
  await page.goto('/admin/importexport.php');

  const fileInput = page.locator("input[name='ie_csv']");
  await expect(fileInput).toBeVisible({ timeout: 10_000 });

  const subject = unique('IMP_UI');
  await fileInput.setInputFiles({
    name: 'one-risk.csv',
    mimeType: 'text/csv',
    buffer: Buffer.from(`Subject\n${subject}\n`),
  });

  // Step 1: Upload & Preview (PRG → the preview page with the Confirm button).
  await page.locator("form:has(input[name='ie_csv']) button[type='submit']").click();
  await page.waitForLoadState('networkidle');

  // Step 2: Confirm Import (PRG → the import runs).
  await page.locator("form:has(input[name='ie_action'][value='import']) button[type='submit']").click();
  await page.waitForLoadState('networkidle');

  expect(await countRisksBySubject(subject)).toBe(1);

  // Export risks → a CSV body containing the imported subject. (Captured as the
  // POST response rather than a download event: firefox handles the small text/csv
  // inline, so waitForEvent('download') is unreliable here.)
  const token = await csrfToken(page);
  const form = new URLSearchParams({ __csrf_magic: token, risks_export: '1' });
  const r = await page.request.post('/admin/importexport.php', {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', Referer: 'https://localhost:8443/' },
    data: form.toString(),
  });
  expect(r.status()).toBe(200);
  expect(await r.text()).toContain(subject);
});
