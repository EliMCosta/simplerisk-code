import { test, expect } from '@playwright/test';
import { login } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { readSettings, restoreSettings, writeSetting, settingValue, type SettingsSnapshot } from '../lib/settings';

// The LDAP config form renders and saving it persists LDAP_HOST to the settings
// table. custom_auth + the touched LDAP_* settings are force-enabled/restored.
// No actual LDAP bind is attempted (lockout risk; no test-connection endpoint).

let snap: SettingsSnapshot = {};

test.beforeEach(() => {
  // Snapshot EVERY setting ldapauth_admin_save() writes on a config POST. The
  // form persists the whole block from $_POST, so a partial submit (this spec
  // only sets ldap_host) clobbers the unfilled connection settings to insecure
  // defaults (LDAP_BASE_DN -> '', LDAP_TLS_MODE -> 'starttls', ...) and then
  // breaks every real LDAP login. Restore the full set so the dev stack survives.
  snap = readSettings([
    'custom_auth',
    'LDAP_HOST', 'LDAP_PORT', 'LDAP_TLS_MODE', 'LDAP_ALLOW_PLAINTEXT',
    'LDAP_BASE_DN', 'LDAP_BIND_DN', 'LDAP_USER_FILTER', 'LDAP_USER_DN_TEMPLATE',
    'LDAP_NAME_ATTRIBUTE', 'LDAP_EMAIL_ATTRIBUTE', 'LDAP_MANAGER_ATTRIBUTE',
    'LDAP_DEFAULT_ROLE_ID', 'AUTHENTICATION_ADD_NEW_USERS',
    'UPDATE_USER_WITH_DATA_FROM_IDP', 'LDAP_MFA_REQUIRED',
  ]);
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
