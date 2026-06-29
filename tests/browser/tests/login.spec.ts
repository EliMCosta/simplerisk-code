import { test, expect } from '@playwright/test';
import { login, TEST_USER } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';

// The login page is the one UX surface every user touches first. Driving the
// real form (not injecting a cookie) locks down: the CSRF hidden field renders,
// the password input is wired, and a successful submit establishes an
// authenticated session (asserted via /api/v2/whoami inside login()).

test.afterEach(() => sweepE2E());

test('login form authenticates and lands on the authenticated app', async ({ page }) => {
  await login(page);

  // We left the login page: the username/password fields are gone.
  await expect(page.locator('input[name="user"]')).toHaveCount(0);
  await expect(page.locator('input[type="password"]')).toHaveCount(0);

  // The authenticated app shell rendered — the header's Logout control is in
  // the DOM (it lives inside a collapsed dropdown, so check presence, not
  // visibility). Combined with the whoami check inside login(), this confirms a
  // real authenticated landing.
  await expect(page.locator('a[href*="logout"]')).toHaveCount(1);
});

test('login rejects an invalid password', async ({ page }) => {
  await page.goto('/');
  await page.locator('input[name="user"]').fill(TEST_USER);
  await page.locator('input[type="password"]').fill('definitely-wrong-password');
  await page.locator('input[name="user"]').press('Enter');

  // Stays on the login page (no authenticated session).
  const whoami = await page.request.get('/api/v2/whoami');
  expect(whoami.status()).toBe(401);
  await expect(page.locator('input[type="password"]')).toBeVisible();
});
