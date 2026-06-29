import { test, expect } from '@playwright/test';
import { login, unique } from '../lib/auth';
import { sweepE2E } from '../lib/cleanup';
import { countRisksBySubject } from '../lib/db';

// The risk submit button is <button type='button' class='save-risk-form'> — the
// type='button' is what makes it an AJAX submit (addRisk() in risk.js:208 POSTs
// the form to /management/index.php, then navigates to view.php?id=<newId>). If
// a regression changed it to type='submit', the form would POST/navigate BEFORE
// the AJAX and the risk would never be created via the button. This spec drives
// the real flow and locks down the type='button' AJAX behavior.
//
// Note: the page renders TWO subject inputs (main form + a new-risk modal), so
// we target .first() to fill the visible main-form field.

test.afterEach(() => sweepE2E());

test('submit-risk button AJAX-creates the risk and opens it', async ({ page }) => {
  await login(page);
  await page.goto('/management/index.php');
  const subject = unique('RISK_UI');
  await page.locator("input[name='subject']").first().fill(subject);

  // The button must fire an AJAX POST to /management/index.php (type='button').
  const [response] = await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes('/management/index.php') && r.request().method() === 'POST',
      { timeout: 15_000 },
    ),
    page.locator('.save-risk-form').first().click(),
  ]);
  expect(response.status()).toBeLessThan(400);

  // On success addRisk() navigates to the new risk's detail page.
  await page.waitForURL(/\/management\/view\.php\?id=\d+/, { timeout: 15_000 });

  // And the risk actually landed in the DB.
  expect(await countRisksBySubject(subject)).toBe(1);
});

test('submit-risk with an empty subject is rejected client-side (no AJAX, no navigation)', async ({ page }) => {
  await login(page);
  await page.goto('/management/index.php');
  const subject = unique('RISK_UI_EMPTY');
  await page.locator("input[name='subject']").first().fill('');
  await page.locator('.save-risk-form').first().click();

  // checkAndSetValidation (common.js:123) flags the empty required subject — it
  // adds the `error` class to the field (common.js:153), blocks the AJAX, and
  // stays on the submit page. Wait for that marker (a fixed sleep is brittle);
  // its presence means validation ran and addRisk() never fired the POST.
  await expect(page.locator("input[name='subject']").first()).toHaveClass(/\berror\b/);
  await expect(page).toHaveURL(/\/management\/index\.php/);
  expect(await countRisksBySubject(subject)).toBe(0);
});
