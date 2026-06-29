import { test, expect } from '@playwright/test';
import { login } from '../lib/auth';
import { seedAuditTarget } from '../lib/seed';
import { sweepE2E } from '../lib/cleanup';

// PRIMARY GUARD for the 2026-06-28 lazy-tree bug on audit_initiation (the only
// lazy tree in the app): clicking a row's .sr-tree-toggle must trigger the lazy
// fetch via api.srToggle() -> fetchLazyChildren() (dataTables.tree.js). The bug
// was that the .sr-tree-toggle click handler bypassed the lazy fetch, so the
// tree never expanded. This spec seeds a framework row with a lazy child and
// asserts the expand click fires the ?id=<framework> fetch and renders children.

test.afterEach(() => sweepE2E());

test('expanding a framework row lazily loads its children', async ({ page }) => {
  await login(page);
  const target = await seedAuditTarget(page);

  // Register the lazy-fetch listener BEFORE navigating (it fires on click after
  // load; registering after goto would race and miss the root load ordering).
  // The lazy id is "framework_<fwId>" (matches the row's data-sr-toggle attr).
  const lazyFetchPromise = page.waitForResponse(
    (r) => r.url().includes('/api/v2/compliance/initiate_audits') &&
            r.url().includes(`id=framework_${target.fwId}`) &&
            r.request().method() === 'GET',
    { timeout: 15_000 },
  );

  await page.goto('/compliance/audit_initiation.php');
  await page.waitForSelector('#initiate_audit_treegrid .sr-tree-toggle', { timeout: 15_000 });

  // The framework row's toggle. Clicking it must fire the lazy fetch.
  const rowsBefore = await page.locator('#initiate_audit_treegrid tbody tr').count();
  await page.locator('#initiate_audit_treegrid .sr-tree-toggle').first().click();

  const lazyFetch = await lazyFetchPromise;
  expect(lazyFetch.status()).toBe(200);

  // Children rendered: the tree now has more rows than before the expand.
  await expect.poll(async () => page.locator('#initiate_audit_treegrid tbody tr').count(), {
    timeout: 10_000,
  }).toBeGreaterThan(rowsBefore);
});
