<?php
/**
 * Integration tests for the Customization Extra's custom-field lifecycle
 * (extras/customization/includes/{fields,admin,schema,seeds,template_groups}.php).
 *
 * create_field() / get_active_fields() / delete_custom_field() use the cached
 * global PDO with plain INSERT/SELECT/DELETE (no DDL), so they run cleanly inside
 * IntegrationTestCase's rollback transaction. BUT customization_install_schema()
 * issues CREATE TABLE, whose DDL implicitly commits in MySQL and would destroy
 * that transaction — so the schema (+ default template group) is installed once
 * in setUpBeforeClass, OUTSIDE any transaction. Each test's data then rolls back.
 */
declare(strict_types=1);

final class CustomizationFieldsTest extends IntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::loadExtra(
            'customization',
            'schema.php',
            'seeds.php',
            'template_groups.php',
            'fields.php',
            'admin.php'
        );
        // DDL commits whatever is open, so do this before any transaction.
        customization_install_schema();
        customization_seed_defaults();
    }

    public function test_create_field_appears_in_active_fields(): void
    {
        $name = 'My Field ' . substr(uniqid('', true), -6);
        $fid = create_field('risk', $name, 'text');

        self::assertGreaterThan(0, $fid);
        $field = get_field_by_id($fid);
        self::assertSame($name, $field['name']);
        self::assertSame('text', $field['type']);

        $active = get_active_fields('risk');
        self::assertContains($name, array_column($active, 'name'));
    }

    public function test_create_field_rejects_empty_name(): void
    {
        self::assertFalse(create_field('risk', '   ', 'text'));
    }

    public function test_create_field_defaults_unknown_type_to_text(): void
    {
        $fid = create_field('risk', 'Typed ' . substr(uniqid('', true), -6), 'not-a-real-type');
        $field = get_field_by_id($fid);
        self::assertSame('text', $field['type']);
    }

    public function test_get_all_fields_includes_created(): void
    {
        $name = 'All Fields ' . substr(uniqid('', true), -6);
        create_field('risk', $name, 'textarea');

        $names = array_column(get_all_fields('risk'), 'name');
        self::assertContains($name, $names);
    }

    public function test_delete_custom_field_removes_from_active(): void
    {
        $name = 'ToDelete ' . substr(uniqid('', true), -6);
        $fid = create_field('risk', $name, 'text');
        self::assertContains($name, array_column(get_active_fields('risk'), 'name'));

        self::assertTrue(delete_custom_field($fid));
        self::assertNotContains($name, array_column(get_active_fields('risk'), 'name'));
    }
}
