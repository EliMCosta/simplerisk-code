import type { Page } from '@playwright/test';

/**
 * Shared test-admin credentials. These MUST match the account provisioned by
 * the PHPUnit e2e suite (tests/E2ETestCase.php::ensureTestAdminUser). Run
 * `bin/test e2e` once before the browser suite so the account exists.
 */
export const TEST_USER = 'e2e_regression_admin';
export const TEST_PASS = 'E2E-Regression-Suite-2026!xQ';

/**
 * Restricted (non-admin) account for separation/OH scoping specs. Matches the
 * account provisioned by tests/e2e/RiskTestSupportTrait::ensureRestrictedUser
 * (admin=0, all permissions). Run `bin/test e2e` first so it exists.
 */
export const RESTRICTED_USER = 'e2e_restricted_user';
export const RESTRICTED_PASS = 'E2E-Restricted-2026!xQ';

/** Prefix shared with the PHPUnit e2e suite so the sweeper cleans both. */
export const E2E_PREFIX = 'E2E_';

let uniqueCounter = 0;
/** A unique E2E_-prefixed string (no Date.now/random needed for uniqueness). */
export function unique(tag: string): string {
  uniqueCounter += 1;
  return `${E2E_PREFIX}${tag}_${process.pid}_${uniqueCounter}`;
}

/**
 * Drive the real login form and assert the resulting session is authenticated
 * (verified via /api/v2/whoami, which answers 200 only for a logged-in session
 * — the same contract the PHPUnit E2ETestCase uses). Driving the form (not a
 * cookie injection) is itself a UX assertion: CSRF hidden field render,
 * password wiring, and the success redirect.
 */
export async function login(page: Page, user: string = TEST_USER, pass: string = TEST_PASS): Promise<void> {
  await page.goto('/');
  await page.locator('input[name="user"]').fill(user);
  await page.locator('input[type="password"]').fill(pass);
  await page.locator('input[name="user"]').press('Enter');

  // Wait until the session is authenticated. The browser's cookie jar is shared
  // with page.request, so this GET carries the freshly-set SimpleRisk session.
  await expectAuthenticated(page);
}

/** Log in as the restricted (non-admin) user — for separation/OH scoping specs. */
export async function loginRestricted(page: Page): Promise<void> {
  await login(page, RESTRICTED_USER, RESTRICTED_PASS);
}

/** Assert the page's session is authenticated (whoami -> 200). */
export async function expectAuthenticated(page: Page): Promise<void> {
  const r = await page.request.get('/api/v2/whoami');
  if (r.status() !== 200) {
    throw new Error(
      `Login did not produce an authenticated session (whoami returned ${r.status()}). ` +
      `Run \`bin/test e2e\` first to provision the ${TEST_USER} account.`
    );
  }
}

/**
 * Per-page csrf token cache. Each test gets an isolated browser context (and
 * therefore its own session/cookie jar), and the token is session-derived, so
 * the cache is scoped to the Page (mirrors the PHPUnit per-instance cache).
 * Keyed on Page via a WeakMap so it cannot leak across sessions/tests.
 */
const csrfTokenByPage = new WeakMap<Page, string>();

/**
 * Extract the session's csrf-magic token from a rendered page (csrf-magic
 * injects <input name="__csrf_magic" value="sid:..."> into forms). Needed for
 * /api/v2 POST seeding from the browser context, because every authenticated
 * Slim POST runs csrf_check() (csrf-magic.php:432). Cached per-page for the test.
 */
export async function csrfToken(page: Page): Promise<string> {
  const cached = csrfTokenByPage.get(page);
  if (cached) return cached;
  const html = await (await page.request.get('/management/index.php')).text();
  const m =
    html.match(/name=["']__csrf_magic["'][^>]*value=["']([^"']+)["']/i) ||
    html.match(/value=["']([^"']+)["'][^>]*name=["']__csrf_magic["']/i);
  if (!m) throw new Error('Could not extract __csrf_magic token from /management/index.php');
  csrfTokenByPage.set(page, m[1]);
  return m[1];
}

/**
 * Authenticated /api/v2 POST from the browser context (shares the session
 * cookie) carrying the csrf-magic token. Returns the Response.
 */
export async function apiPost(page: Page, path: string, body: Record<string, string | number>): Promise<{ status: number; json: any }> {
  const token = await csrfToken(page);
  const form = new URLSearchParams({ __csrf_magic: token });
  for (const [k, v] of Object.entries(body)) form.set(k, String(v));
  const r = await page.request.post(path, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', Referer: 'https://localhost:8443/' },
    data: form.toString(),
  });
  let json: any = null;
  try { json = await r.json(); } catch { /* non-JSON (e.g. HTML redirect) */ }
  return { status: r.status(), json };
}
