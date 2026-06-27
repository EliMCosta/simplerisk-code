<?php
/**
 * Base test cases for the SimpleRisk suite.
 *
 * IntegrationTestCase wraps the shared global PDO — db_open() caches it in
 * $GLOBALS['db_global'] — in a transaction that is rolled back in tearDown, so
 * DB writes from a test never persist. (db_close() here only nulls a local
 * variable; the global PDO and its transaction are never disturbed.)
 *
 * Rules for integration tests (see SettingsTest):
 *   1. Pass $this->txdb into every ?PDO $db parameter so writes land inside the
 *      transaction. The CRUD helpers (add_setting / update_or_insert_setting /
 *      delete_setting / get_setting / setting_exists) all accept it.
 *   2. Read with cached=false (get_setting defaults to a $GLOBALS cache that
 *      add_setting also populates) and use unique setting names per test so
 *      cache writes can't collide across tests.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Pull an extra's code into the test process.
 *
 * Extra functions are NOT loaded by tests/bootstrap.php (extras load via
 * index.php -> includes/extras.php). Each extra's includes/bootstrap.php is
 * safe to require_once standalone: it no-ops on already-loaded core and defines
 * its constants behind !defined() guards. Verified loadable for every target
 * extra. Unit/integration tests `use LoadsExtras;` then call loadExtra() with
 * the extra name and the specific include file(s) under test.
 */
trait LoadsExtras
{
    /** require_once the extra's bootstrap (+ config) then each named include. */
    protected static function loadExtra(string $extra, string ...$includes): void
    {
        $base = rtrim(SIMPLERISK_APP_ROOT, '/') . "/extras/{$extra}/includes";
        require_once "{$base}/bootstrap.php";
        foreach ($includes as $include) {
            require_once "{$base}/{$include}";
        }
    }
}

abstract class IntegrationTestCase extends TestCase
{
    use LoadsExtras;
    protected ?PDO $txdb = null;

    protected function setUp(): void
    {
        if (defined('SIMPLERISK_TEST_NO_DB')) {
            static::markTestSkipped('No database available (SIMPLERISK_TEST_NO_DB set).');
        }
        // db_open() returns the cached global PDO (and caches it on first call).
        $this->txdb = db_open();
        $this->txdb->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->txdb !== null) {
            if ($this->txdb->inTransaction()) {
                $this->txdb->rollBack();
            }
            $this->txdb = null;
        }
    }

    /**
     * Read a setting bypassing get_setting()'s $GLOBALS cache, via our
     * transactional PDO.
     */
    protected function getSetting(string $name, $default = false)
    {
        return get_setting($name, $default, /*cached*/ false, $this->txdb);
    }

    /**
     * A unique setting name so cache entries written by add_setting
     * ($GLOBALS['setting_<name>']) can't collide across tests.
     */
    protected function uniqueName(string $prefix = 'test_setting'): string
    {
        return $prefix . '_' . substr(sha1(uniqid('', true)), 0, 10);
    }

    /**
     * Drop get_setting()'s $GLOBALS cache entry for the given names.
     *
     * Extras read FIXED-NAME settings (api_keys, api_token_pepper,
     * api_server_enabled, ...) via the default cached path. That cache persists
     * for the whole process, so without clearing it a test sees whatever the
     * previous test (or the live DB) left there instead of this transaction's
     * writes. Call this in setUp for every fixed-name setting the test mutates.
     */
    protected function clearSettingCache(string ...$names): void
    {
        foreach ($names as $name) {
            unset($GLOBALS['setting_' . $name]);
        }
    }
}
