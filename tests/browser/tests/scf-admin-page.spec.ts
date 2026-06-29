import { test, expect } from '@playwright/test';
import { login } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { readSettings, restoreSettings, writeSetting, type SettingsSnapshot } from '../lib/settings';

// SCF admin page renders the framework panel. extra_scf is force-enabled +
// restored; the bulk re-import is never triggered (slow, ~151k rows).

let snap: SettingsSnapshot = {};

test.beforeEach(() => {
  snap = readSettings(['extra_scf']);
  writeSetting('extra_scf', '1');
});

test.afterEach(() => {
  restoreSettings(snap);
  sweepE2E();
});

test('SCF admin page renders the framework', async ({ page }) => {
  await login(page);
  await page.goto('/admin/securecontrolsframework.php');
  await expect(page.getByText('Secure Controls Framework').first()).toBeVisible({ timeout: 10_000 });
});
