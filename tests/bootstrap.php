<?php
/**
 * PHPUnit bootstrap for the SimpleRisk test suite.
 *
 * Tests run INSIDE the simplerisk-app container (the suite is bind-mounted at
 * /var/www/simplerisk/tests — see compose.app.yml). Core functions live in
 * simplerisk/includes/functions.php, whose include cascades the full app
 * bootstrap (config.php + a DB-backed language file). That only works where the
 * app is installed — i.e. the running container — so we check for config.php
 * BEFORE including functions.php: simplerisk/includes/bootstrap.php calls
 * exit(1) under CLI when config is missing, which would kill the test process.
 *
 * If config is absent (e.g. running only the pure-CVSS suite outside a
 * container) we fall back to including just includes/cvss.php and define
 * SIMPLERISK_TEST_NO_DB so tests that need the DB skip themselves gracefully.
 */
declare(strict_types=1);

define('SIMPLERISK_APP_ROOT', getenv('SIMPLERISK_APP_ROOT') ?: '/var/www/simplerisk');

$configFile = SIMPLERISK_APP_ROOT . '/includes/config.php';

if (file_exists($configFile)) {
    // Full app bootstrap — exposes every core function (CVSS, settings, risk
    // getters, colors, validation, ...). Works because the container has a
    // generated config.php and a live DB.
    require_once SIMPLERISK_APP_ROOT . '/includes/functions.php';
} else {
    // No installed app reachable — CVSS math only (no DB, no config).
    require_once SIMPLERISK_APP_ROOT . '/includes/cvss.php';
    define('SIMPLERISK_TEST_NO_DB', true);
}

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/E2ETestCase.php';
require_once __DIR__ . '/e2e/ComplianceSeedTrait.php';
require_once __DIR__ . '/e2e/RiskTestSupportTrait.php';
