<?php
/**
 * Integration tests for the Organizational Hierarchy Extra's Business Unit CRUD
 * (extras/organizational_hierarchy/includes/{schema,business_units,teams,api}.php).
 *
 * oh_create_business_unit() / oh_delete_business_unit() manage their OWN
 * transactions (begin/commit), so these cannot run inside IntegrationTestCase's
 * rollback wrapper (same cached PDO -> "already an active transaction"). Instead
 * each test uses a unique SRTEST- prefixed name and tearDown scrubs every
 * SRTEST- business unit (+ orphan team links + test teams) so nothing leaks.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class OrgHierarchyTest extends TestCase
{
    use LoadsExtras;

    private const PREFIX = 'SRTEST-';

    protected function setUp(): void
    {
        self::loadExtra('organizational_hierarchy', 'schema.php', 'business_units.php', 'teams.php', 'api.php');
        oh_install_schema(); // CREATE TABLE IF NOT EXISTS ... idempotent
    }

    protected function tearDown(): void
    {
        $db = db_open();
        // Remove only the business units this suite created (+ their team links).
        $ids = $db->query("SELECT id FROM business_unit WHERE name LIKE '" . self::PREFIX . "%'")->fetchAll(PDO::FETCH_COLUMN);
        if ($ids) {
            $in = implode(',', array_map('intval', $ids));
            $db->exec("DELETE FROM business_unit_to_team WHERE business_unit_id IN ({$in})");
            $db->exec("DELETE FROM business_unit WHERE id IN ({$in})");
        }
        $db->exec("DELETE FROM team WHERE name LIKE '" . self::PREFIX . "%'");
    }

    public function test_install_schema_is_idempotent(): void
    {
        // Running twice must not throw (CREATE TABLE IF NOT EXISTS / guarded ALTER).
        oh_install_schema();
        oh_install_schema();
        $this->addToAssertionCount(1);
    }

    public function test_create_get_and_name_exists_roundtrip(): void
    {
        $name = self::PREFIX . substr(uniqid('', true), -6);
        $id = oh_create_business_unit($name, 'a test BU', []);

        self::assertGreaterThan(0, $id);
        $bu = oh_get_business_unit($id);
        self::assertIsArray($bu);
        self::assertSame($name, $bu['name']);
        self::assertSame($name, oh_get_business_unit_name($id));
        self::assertTrue(oh_business_unit_name_exists($name));
        self::assertFalse(oh_business_unit_name_exists($name . '-nope'));
    }

    public function test_create_rejects_empty_name(): void
    {
        self::assertSame(0, oh_create_business_unit('   ', 'desc', []));
    }

    public function test_get_business_units_lists_created(): void
    {
        $a = oh_create_business_unit(self::PREFIX . 'a', '', []);
        $b = oh_create_business_unit(self::PREFIX . 'b', '', []);
        self::assertGreaterThan(0, $a);
        self::assertGreaterThan(0, $b);

        $names = array_column(oh_get_business_units(), 'name');
        self::assertContains(self::PREFIX . 'a', $names);
        self::assertContains(self::PREFIX . 'b', $names);
    }

    public function test_team_links_are_stored_and_retrieved(): void
    {
        $teamId = $this->createTeam();
        $id = oh_create_business_unit(self::PREFIX . 'linked', 'desc', [$teamId]);
        self::assertGreaterThan(0, $id);

        self::assertContains($teamId, oh_get_team_ids_for_business_unit($id));
    }

    public function test_delete_removes_business_unit(): void
    {
        $name = self::PREFIX . 'gone';
        $id = oh_create_business_unit($name, '', []);
        self::assertTrue(oh_delete_business_unit($id));

        self::assertNull(oh_get_business_unit($id));      // gone
        self::assertFalse(oh_business_unit_name_exists($name));
        self::assertFalse(oh_delete_business_unit(0));    // no-op for invalid id
    }

    private function createTeam(): int
    {
        $db = db_open();
        $name = self::PREFIX . 'team-' . substr(uniqid('', true), -6);
        $stmt = $db->prepare("INSERT INTO team (`name`) VALUES (:name)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$db->lastInsertId();
    }
}
