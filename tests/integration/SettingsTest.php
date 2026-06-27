<?php
/**
 * Integration test: settings CRUD round-trip.
 *
 * Covers the DB-injectable helpers (add_setting / update_or_insert_setting /
 * get_setting / setting_exists / delete_setting — simplerisk/includes/functions.php)
 * that the entire app depends on. All writes go through the per-test transaction
 * (see IntegrationTestCase), which is rolled back in tearDown, so nothing
 * persists. Reads use cached=false + unique names to dodge the $GLOBALS cache.
 */
declare(strict_types=1);

final class SettingsTest extends IntegrationTestCase
{
    public function test_add_get_exists_delete_roundtrip(): void
    {
        $name = $this->uniqueName();
        $db = $this->txdb;

        // Absent initially -> exists=false, falls back to default
        self::assertFalse(setting_exists($name, $db));
        self::assertSame('default', get_setting($name, 'default', false, $db));

        // Add -> present with the given value
        add_setting($name, 'first', $db);
        self::assertTrue(setting_exists($name, $db));
        self::assertSame('first', get_setting($name, false, false, $db));

        // Update via REPLACE (no audit-log side effects) -> new value
        update_or_insert_setting($name, 'second', $db);
        self::assertTrue(update_or_insert_setting($name, 'second', $db));
        self::assertSame('second', get_setting($name, false, false, $db));

        // Delete -> gone again, default fallback restored
        delete_setting($name, $db);
        self::assertFalse(setting_exists($name, $db));
        self::assertSame('gone', get_setting($name, 'gone', false, $db));
    }

    public function test_setting_values_roundtrip_from_fixture(): void
    {
        $cases = require __DIR__ . '/../fixtures/settings.php';
        foreach ($cases as [$base, $value]) {
            $name = $this->uniqueName($base);
            add_setting($name, $value, $this->txdb);
            self::assertSame($value, $this->getSetting($name), "value mismatch for {$name}");
        }
    }

    public function test_add_setting_is_idempotent_via_insert_ignore(): void
    {
        $name = $this->uniqueName();
        $db = $this->txdb;

        add_setting($name, 'v1', $db);
        // A second add with INSERT IGNORE must NOT overwrite the existing value.
        add_setting($name, 'v2', $db);
        self::assertSame('v1', get_setting($name, false, false, $db));
    }
}
