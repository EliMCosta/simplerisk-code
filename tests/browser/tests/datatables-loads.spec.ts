import { test, expect } from '@playwright/test';
import { login, apiPost, unique } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';

// A DataTables grid that fails to initialize (broken JS, a moved AJAX URL, a
// changed response envelope) returns a 200 page with an EMPTY grid — invisible
// to the curl/PHPUnit render tests, which only check for a mount marker. This
// spec drives the real grid: it fires its server-side AJAX, returns 200, and
// renders at least one row. We seed a risk first so the grid has data to show.

test.afterEach(() => sweepE2E());

test('review_risks grid loads its AJAX data and renders rows', async ({ page }) => {
  await login(page);

  // Seed a risk so the grid has something to display.
  const subject = unique('RISK_GRID');
  const created = await apiPost(page, '/api/v2/risks/submit', { subject });
  expect(created.status).toBe(200);

  // Register the listener BEFORE navigating: the grid fires its server-side
  // AJAX immediately on load, so a listener set up after goto would race and miss it.
  const ajaxPromise = page.waitForResponse(
    (r) => r.url().includes('/api/v2/risk_management/review_risks'),
    { timeout: 15_000 },
  );
  await page.goto('/management/review_risks.php');
  const ajax = await ajaxPromise;
  expect(ajax.status()).toBe(200);

  // And at least one row rendered in the grid body (DataTables fills tbody).
  const rowCount = await page.locator('table.dataTable tbody tr, table tbody tr').count();
  expect(rowCount).toBeGreaterThanOrEqual(1);
});
