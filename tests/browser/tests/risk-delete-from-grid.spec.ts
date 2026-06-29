import { test, expect } from '@playwright/test';
import { login, unique, apiPost } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { countRisksBySubject } from '../lib/db';

// The admin "Delete Risks" grid flow: check a risk's box -> click Delete -> a
// CUSTOM confirm() modal (common.js:294, not a native dialog) -> click "Yes"
// -> the form POSTs to /admin/delete_risks.php (there is no /api/v2 DELETE for
// risks). The grid is a DataTable; a regression that broke the modal callback
// wiring or the risks[] payload would silently fail to delete. This drives the
// real DOM flow and proves the row is gone from the DB.
//
// The checkboxes carry DB ids (public id - 1000), matching delete_risks.php.

test.afterEach(() => sweepE2E());

test('delete-risks grid removes the risk after confirm modal', async ({ page }) => {
  await login(page);
  const subject = unique('DEL_UI');
  const res = await apiPost(page, '/api/v2/risks/submit', { subject });
  const publicId = res.json?.data?.risk_id;
  expect(publicId).toBeTruthy();
  const dbId = Number(publicId) - 1000;

  await page.goto('/admin/delete_risks.php');

  // The grid is a DataTable; the newest (highest-id) risk may be on a later
  // page, and force-checking a hidden checkbox does not toggle reliably. The
  // .btn-delete handler and the form submit both key off the checked STATE
  // (jQuery $('[name="risks[]"]:checked') + native serialization), so set it
  // directly — the delete UX under test (button -> modal -> submit) is unchanged.
  await page.waitForSelector(`input[name="risks[]"][value="${dbId}"]`);
  await page.evaluate((id) => {
    const cb = document.querySelector(`input[name="risks[]"][value="${id}"]`) as HTMLInputElement | null;
    if (cb) cb.checked = true;
  }, dbId);

  // Delete button -> opens the custom confirm modal.
  await page.locator('.btn-delete').first().click();
  const yes = page.locator('.modal .btn-submit').last();
  await yes.waitFor({ state: 'visible' });

  // "Yes" runs the callback that submits the form -> POST delete_risks.php.
  const [response] = await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes('/admin/delete_risks.php') && r.request().method() === 'POST',
      { timeout: 20_000 },
    ),
    yes.click(),
  ]);
  expect(response.status()).toBeLessThan(400);

  // DB truth: the risk is gone.
  expect(await countRisksBySubject(subject)).toBe(0);
});
