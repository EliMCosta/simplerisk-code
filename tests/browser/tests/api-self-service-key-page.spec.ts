import { test, expect } from '@playwright/test';
import { loginRestricted } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { readSettings, restoreSettings, writeSetting, type SettingsSnapshot } from '../lib/settings';

// The API Extra self-service key page: a non-admin can generate a personal API
// key from /account/profile.php, and the reveal-once plaintext is flashed into
// #api-profile-newkey. Guards the DOM/AJAX flow the PHPUnit test cannot drive if
// the profile POST dispatch runs before the session is started.

let snap: SettingsSnapshot = {};

test.beforeEach(async () => {
  snap = readSettings(['api', 'api_allow_user_keys', 'api_keys']);
  writeSetting('api', '1');
  writeSetting('api_allow_user_keys', '1');
});

test.afterEach(() => {
  restoreSettings(snap); // restores the shared api_keys blob (removes the test key)
  sweepE2E();
});

test('non-admin generates a self-service key and the plaintext is flashed once', async ({ page }) => {
  await loginRestricted(page);
  await page.goto('/account/profile.php');

  await expect(page.getByRole('heading', { name: 'My API keys' })).toBeVisible({ timeout: 10_000 });

  const generate = page.locator(
    'form:has(input[name="action"][value="api_profile_create"]) button[type="submit"]',
  );
  await expect(generate).toBeVisible();
  await generate.click();

  // After the PRG redirect, the reveal-once key appears in the readonly input.
  const newKey = page.locator('#api-profile-newkey');
  await expect(newKey).toBeVisible({ timeout: 15_000 });
  const value = await newKey.inputValue();
  expect(value).toMatch(/^[0-9a-f]{64}$/);
});
