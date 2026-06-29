# SimpleRisk browser/UX acceptance suite (Playwright)

A host-side Playwright suite that drives the real SimpleRisk UI in a browser,
guarding the **DOM/AJAX** flows that the curl/PHPUnit e2e suite structurally
cannot observe. Two real bugs in this area were invisible to curl because they
are client-side faults — these specs lock them down:

- **Audit-initiation tag modal** — the "Add Tags To Test Audit" Continue button
  must be `type="button"`. Without it, the button defaulted to `submit` inside a
  `<form method=post>`, navigating away and aborting the async initiate AJAX.
  (`tests/audit-initiation-tag-modal.spec.ts`)
- **Audit-initiation lazy tree** — clicking a row's `.sr-tree-toggle` must
  trigger the lazy fetch (`api.srToggle()` → `fetchLazyChildren()`); the bug was
  that the click bypassed the fetch and the tree never expanded.
  (`tests/audit-initiation-lazy-tree.spec.ts`)

Plus foundation specs: login form (`login.spec.ts`), risk submit AJAX
(`risk-submit.spec.ts`), and DataTables grid load (`datatables-loads.spec.ts`).

## Layout

```
tests/browser/
  package.json            @playwright/test dependency
  playwright.config.ts    baseURL https://localhost:8443, firefox, ignoreHTTPSErrors
  lib/
    auth.ts               login(), csrfToken(), apiPost() helpers + test creds
    seed.ts               seedAuditTarget() — framework+control+test+mapping for the tree
    cleanup.ts            sweepE2E() — afterEach residue sweeper (podman exec)
    db.ts                 read-only DB checks (countRisksBySubject, countAuditsForTest)
  tests/                  the *.spec.ts files
```

## Prerequisites (one-time)

1. Node.js on the host.
2. Install deps + the Playwright-instrumented firefox (system firefox lacks
   Juggler and will **not** work):
   ```sh
   cd tests/browser
   npm install
   npx playwright install firefox
   ```
3. Provision the shared test admin (the same account the PHPUnit e2e suite uses):
   ```sh
   bin/test e2e          # from repo root — creates e2e_regression_admin
   ```

## Run

```sh
bin/test-browser                       # run all browser specs (from repo root)
bin/test-browser --grep "tag modal"    # filter
bin/test-browser --headed              # watch it run
```

`bin/test-browser` is a host wrapper around `npx playwright test`. The browser
suite is **not** part of `bin/test` and does not affect the PHPUnit suites.

## Isolation

There is no transaction rollback. Every spec uses the shared `E2E_` name prefix
(convention shared with the PHPUnit e2e suite) and `sweepE2E()` runs in
`afterEach` to delete all `E2E_`-prefixed rows (risks, frameworks, controls,
tests, audits, results, mappings) via a single `podman exec` PHP snippet — the
safety net that guarantees isolation even on a mid-test abort.

## CSRF

The browser flow does **not** need a manual csrf token for AJAX: csrf-magic's JS
(`csrf-magic.js`) auto-injects the `__csrf_magic` token into `$.ajax` requests.
(The `apiPost()` helper in `lib/auth.ts` is used only for /api/v2 *seeding* from
the test and adds the token explicitly.)
