import { test, expect } from '@playwright/test';
import { login } from '../lib/auth';
import { seedAuditTarget } from '../lib/seed';
import { sweepE2E } from '../lib/cleanup';
import { countAuditsForTest } from '../lib/db';

// PRIMARY GUARD for the 2026-06-28 audit-initiation tag-modal bug: the "Add
// Tags To Test Audit" Continue button must be type='button'. Without it, the
// button defaulted to submit inside <form method=post> and its click POSTed the
// page itself, navigating away and aborting the async
// /api/v2/compliance/audit_initiation/initiate AJAX (so no audit was created).
// curl could not see this — it is a DOM/AJAX fault. This spec drives the real
// modal click and asserts the initiate AJAX fires and an audit is created.

test.afterEach(() => sweepE2E());

test('Continue button is type=button and fires the initiate AJAX', async ({ page }) => {
  await login(page);
  const target = await seedAuditTarget(page);
  await page.goto('/compliance/audit_initiation.php');
  // Let the tree initialize (root load).
  await page.waitForSelector('#initiate_audit_treegrid .sr-tree-toggle', { timeout: 15_000 });

  // Robust, seed-independent guard: the Continue button MUST carry type='button'.
  const btnType = await page.locator('button[name="continue_add_tags"]').getAttribute('type');
  expect(btnType).toBe('button');

  // Point the modal at the seeded test (mirrors what a row's "Add Tags" action
  // does) and open it. tags[] is left empty — initiate accepts empty tags.
  await page.evaluate((testId: number) => {
    (document.querySelector('#tags--edit [name="audit_type"]') as HTMLInputElement).value = 'test';
    (document.querySelector('#tags--edit [name="id"]') as HTMLInputElement).value = String(testId);
    (window as any).jQuery('#tags--edit').modal('show');
  }, target.testId);
  await expect(page.locator('#tags--edit')).toBeVisible();

  // Clicking Continue must fire the initiate AJAX (NOT submit the form/navigate).
  const [response] = await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes('/api/v2/compliance/audit_initiation/initiate') && r.request().method() === 'POST',
      { timeout: 15_000 },
    ),
    page.locator('button[name="continue_add_tags"]').click(),
  ]);
  expect(response.status()).toBe(200);

  // And an audit was actually created for the test.
  expect(await countAuditsForTest(target.testId)).toBeGreaterThanOrEqual(1);
});
