import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright config for the SimpleRisk browser/UX acceptance suite.
 *
 * Runs HOST-SIDE against the published dev instance (https://localhost:8443,
 * self-signed -> ignoreHTTPSErrors). This deliberately stays OUT of the
 * simplerisk-app container: it keeps Node/browser tooling separate from the
 * PHP/pcov world and the PHPUnit `bin/test` runner, and matches how the dev
 * instance is exposed (compose.app.yml publishes :8443).
 *
 * Invoked via `bin/test-browser` (host wrapper). NOT part of `bin/test`.
 *
 * Auth: the test admin (e2e_regression_admin) is provisioned by the PHPUnit
 * e2e suite (E2ETestCase::ensureTestAdminUser) — run `bin/test e2e` once first.
 */
export default defineConfig({
  testDir: './tests',
  outputDir: 'test-results', // pin to <config-dir>/test-results (gitignored) — never the repo root
  fullyParallel: false, // SimpleRisk uses a single shared session/DB; avoid concurrent writes
  forbidOnly: !!process.env.CI,
  retries: 0,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never' }]],
  timeout: 30_000,
  expect: { timeout: 10_000 },
  use: {
    baseURL: 'https://localhost:8443',
    ignoreHTTPSErrors: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    actionTimeout: 15_000,
  },
  projects: [
    {
      name: 'firefox',
      use: { browserName: 'firefox' },
    },
  ],
});
