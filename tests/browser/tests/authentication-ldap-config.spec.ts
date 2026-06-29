import { test, expect } from '@playwright/test';
import { login } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { readSettings, restoreSettings, writeSetting, settingValue, type SettingsSnapshot } from '../lib/settings';

// The LDAP config form renders and saving it persists LDAP_HOST to the settings
// table. custom_auth + the touched LDAP_* settings are force-enabled/restored.
// No actual LDAP bind is attempted (lockout risk; no test-connection endpoint).

let snap: SettingsSnapshot = {};

test.beforeEach(() => {
  snap = readSettings(['custom_auth', 'LDAP_HOST', 'LDAP_PORT', 'LDAP_BIND_DN']);
  writeSetting('custom_auth', '1');
});

test.afterEach(() => {
  restoreSettings(snap);
  sweepE2E();
});

test('LDAP config form renders and saving persists the host', async ({ page }) => {
  await login(page);
  await page.goto('/admin/authentication.php');
  await expect(page.locator('#ldap_host')).toBeVisible({ timeout: 10_000 });

  // Hostname-safe value (ldapauth sanitises to [a-zA-Z0-9._:-]; no underscores).
  const host = `e2e-ldap-ui-${process.pid}.test`;
  await page.locator('#ldap_host').fill(host);
  await page.locator("form:has(#ldap_host) button[type='submit']").first().click();
  await page.waitForLoadState('networkidle');

  expect(settingValue('LDAP_HOST')).toBe(host);
});
