<?php
/**
 * Integration tests for the framework-filter expansion added in the
 * header/sidebar refactor (simplerisk/includes/governance.php):
 *   expand_framework_filter_ids($framework_ids)                 :963
 *   build_framework_controls_datatable_filter_sql($f, &$params) :1025
 *
 * expand_framework_filter_ids walks the frameworks tree (get_all_child_frameworks)
 * and returns only LEAF descendants — parents-with-children are excluded. We seed
 * a private 3-level tree (ROOT -> MID -> LEAF, where MID is both a child and a
 * parent) inside the rollback txn so expansion is deterministic regardless of the
 * dev DB's real frameworks. The build helper inlines expanded ids into its SQL via
 * sprintf ("m.framework IN (%s)"), so we assert on the returned string.
 *
 * NOTE: the build helper's collapse-to-[0] branch (selection expands to nothing,
 * no -1) is effectively unreachable with acyclic data — every parent has at least
 * one leaf descendant — so it is intentionally not exercised here.
 *
 * governance.php is NOT in the test bootstrap's functions.php cascade, so we
 * require_once it; db_open() inside the functions is pinned to $this->txdb.
 */
declare(strict_types=1);

final class FrameworkFilterExpansionTest extends IntegrationTestCase
{
    /** Private high-id tree so it never collides with the dev DB's frameworks. */
    private const ROOT = 900000; // parent
    private const MID  = 900001; // child of ROOT, parent of LEAF
    private const LEAF = 900002; // leaf

    public static function setUpBeforeClass(): void
    {
        if (!function_exists('expand_framework_filter_ids')) {
            require_once SIMPLERISK_APP_ROOT . '/includes/governance.php';
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['db_global'] = $this->txdb;
        $this->seedTree();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['db_global']);
        parent::tearDown();
    }

    private function seedTree(): void
    {
        $ins = $this->txdb->prepare(
            "INSERT INTO `frameworks` (`value`, `parent`, `name`, `description`, `status`, `order`)
             VALUES (:value, :parent, :name, :description, 1, 0)"
        );
        foreach ([
            [self::ROOT, 0,          'Test Root'],
            [self::MID,  self::ROOT, 'Test Mid'],
            [self::LEAF, self::MID,  'Test Leaf'],
        ] as [$value, $parent, $name]) {
            $ins->bindValue(':value', $value, PDO::PARAM_INT);
            $ins->bindValue(':parent', $parent, PDO::PARAM_INT);
            $ins->bindValue(':name', $name, PDO::PARAM_LOB);
            $ins->bindValue(':description', 'seed', PDO::PARAM_LOB);
            $ins->execute();
        }
    }

    public function test_empty_or_non_positive_input_returns_empty(): void
    {
        self::assertSame([], expand_framework_filter_ids([]));
        self::assertSame([], expand_framework_filter_ids([0, -5, 'x']));
    }

    public function test_leaf_framework_is_kept_verbatim(): void
    {
        self::assertSame([self::LEAF], expand_framework_filter_ids([self::LEAF]));
    }

    public function test_root_expands_to_leaf_descendants_only(): void
    {
        // ROOT's descendants are MID (a parent) + LEAF; only the leaf survives.
        self::assertSame([self::LEAF], expand_framework_filter_ids([self::ROOT]));
    }

    public function test_intermediate_parent_expands_to_its_leaf(): void
    {
        // MID is itself a parent but should still resolve down to its leaf.
        self::assertSame([self::LEAF], expand_framework_filter_ids([self::MID]));
    }

    public function test_mixed_selection_is_deduped(): void
    {
        self::assertSame([self::LEAF], expand_framework_filter_ids([self::ROOT, self::LEAF]));
        self::assertSame([self::LEAF], expand_framework_filter_ids([self::MID, self::LEAF]));
    }

    public function test_build_filter_sql_inlines_expanded_leaf_for_parent(): void
    {
        $bind = [];
        $sql = build_framework_controls_datatable_filter_sql(
            ['control_framework' => [self::ROOT]],
            $bind
        );
        // The parent filter expands to LEAF and is inlined into m.framework IN (...).
        self::assertStringContainsString('m.framework IN (' . self::LEAF . ')', $sql);
        // The visibility fragment is always wired in.
        self::assertStringContainsString('NOT EXISTS', $sql);
    }

    public function test_build_filter_sql_unassigned_sentinel_only(): void
    {
        $bind = [];
        $sql = build_framework_controls_datatable_filter_sql(
            ['control_framework' => [-1]],
            $bind
        );
        // -1 means "unassigned" -> the m.control_id IS NULL branch, no IN clause.
        self::assertStringContainsString('m.control_id is NULL', $sql);
        self::assertStringNotContainsString('m.framework IN', $sql);
    }

    public function test_build_filter_sql_parent_plus_unassigned(): void
    {
        $bind = [];
        $sql = build_framework_controls_datatable_filter_sql(
            ['control_framework' => [self::ROOT, -1]],
            $bind
        );
        self::assertStringContainsString('m.framework IN (' . self::LEAF . ')', $sql);
        self::assertStringContainsString('m.control_id is NULL', $sql);
    }
}
