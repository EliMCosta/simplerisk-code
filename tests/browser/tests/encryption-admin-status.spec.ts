import { test, expect } from '@playwright/test';
import { login } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { settingValue } from '../lib/settings';

// Encryption is intentionally NOT toggled (enabling runs a bulk re-encrypt). When
// it is already enabled, the admin page must render the status panel with NO
// "XXXX" (no-key) indicator; otherwise the test self-skips.

test.afterEach(() => sweepE2E());

test('encryption admin page shows no XXXX indicator when enabled', async ({ page }) => {
  test.skip(settingValue('encryption') !== '1', 'Encryption Extra is not enabled');

  await login(page);
  const r = await page.request.get('/admin/encryption.php');
  expect(r.status()).toBe(200);
  expect(await r.text()).not.toContain('XXXX');
});
