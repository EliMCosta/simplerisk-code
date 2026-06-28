<?php
/**
 * Integration regression tests for update_framework_status() in
 * includes/governance.php — the function behind the governance frameworks
 * "Move to Active/Inactive" toggle.
 *
 * These lock down the cascade contract that makes the toggle reversible:
 *   - Deactivating a parent marks every descendant inactive (existing behavior).
 *   - Reactivating a parent MUST mark every descendant active again, so a
 *     deactivate -> reactivate round-trip restores the exact prior state.
 *     Without this, reactivating a parent left all children inactive, so the
 *     user had to reactivate each child one by one — and the children appeared
 *     "unlinked" from the parent in the active tree.
 *   - A status toggle must NEVER mutate the `parent` column. Parent links are
 *     the tree structure; clearing them is what literally unlinks children.
 *
 * Every write lands in the IntegrationTestCase rollback transaction
 * (update_framework_status / get_framework / write_log all open the shared
 * global PDO that setUp already wrapped), so seeded rows never persist.
 */
declare(strict_types=1);

final class FrameworkStatusCascadeTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // update_framework_status / get_framework live in includes/governance.php,
        // which the test bootstrap does not cascade in (same pattern as TreeBuilderTest).
        require_once SIMPLERISK_APP_ROOT . '/includes/governance.php';

        // update_framework_status() reads `global $escaper` (for the audit message)
        // and $_SESSION['user']/['uid'] (for write_log). Provide both under CLI.
        if (!isset($GLOBALS['escaper'])) {
            $GLOBALS['escaper'] = new \Laminas\Escaper\Escaper('UTF-8');
        }
        $_SESSION['user'] = 'test-user';
        $_SESSION['uid'] = 1;
    }

    protected function tearDown(): void
    {
        unset($_SESSION['user'], $_SESSION['uid']);
        parent::tearDown();
    }

    /**
     * Seed a framework row inside the rollback transaction and return its value.
     * name/description are stored as plain strings; get_framework round-trips
     * them through try_decrypt(), which returns them unchanged.
     */
    private function seedFramework(string $name, int $parent = 0, int $status = 1): int
    {
        $stmt = $this->txdb->prepare(
            "INSERT INTO frameworks (`name`, `description`, `parent`, `status`, `order`)
             VALUES (:name, :desc, :parent, :status, 0)"
        );
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':desc', $name . ' description');
        $stmt->bindValue(':parent', $parent, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $this->txdb->lastInsertId();
    }

    /** Read back the status + parent of a framework by id. */
    private function row(int $id): array
    {
        $stmt = $this->txdb->prepare("SELECT `status`, `parent` FROM `frameworks` WHERE `value` = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['status' => (int) $r['status'], 'parent' => (int) $r['parent']];
    }

    public function test_deactivating_parent_cascades_to_all_descendants(): void
    {
        $p = $this->seedFramework('CascadeParent', 0);
        $c1 = $this->seedFramework('CascadeChild1', $p);
        $c2 = $this->seedFramework('CascadeChild2', $p);
        $g = $this->seedFramework('CascadeGrandchild', $c1); // 3-deep

        update_framework_status(2, $p);

        foreach ([$p, $c1, $c2, $g] as $id) {
            self::assertSame(2, $this->row($id)['status'], "framework {$id} must be inactive after parent deactivation");
        }
    }

    public function test_reactivating_parent_cascades_to_all_descendants(): void
    {
        // This is the regression: the bug left children inactive after the parent
        // was reactivated, forcing one-by-one reactivation.
        $p = $this->seedFramework('ReactParent', 0);
        $c1 = $this->seedFramework('ReactChild1', $p);
        $c2 = $this->seedFramework('ReactChild2', $p);
        $g = $this->seedFramework('ReactGrandchild', $c1);

        // Deactivate the whole subtree, then reactivate the parent.
        update_framework_status(2, $p);
        update_framework_status(1, $p);

        foreach ([$p, $c1, $c2, $g] as $id) {
            self::assertSame(1, $this->row($id)['status'], "framework {$id} must be active again after parent reactivation");
        }
    }

    public function test_status_toggle_never_mutates_parent_links(): void
    {
        // The `parent` column IS the tree structure. Toggling status must never
        // touch it — clearing it is what literally unlinks a child from its parent.
        $p = $this->seedFramework('LinkParent', 0);
        $c = $this->seedFramework('LinkChild', $p);
        $g = $this->seedFramework('LinkGrandchild', $c);

        // Round-trip the parent through inactive and back.
        update_framework_status(2, $p);
        self::assertSame($p, $this->row($c)['parent'], 'child->parent link survives deactivation');
        self::assertSame($c, $this->row($g)['parent'], 'grandchild->parent link survives deactivation');

        update_framework_status(1, $p);
        self::assertSame($p, $this->row($c)['parent'], 'child->parent link survives reactivation');
        self::assertSame($c, $this->row($g)['parent'], 'grandchild->parent link survives reactivation');

        // And toggling a child directly must not unlink it from its parent either.
        update_framework_status(2, $c);
        self::assertSame($p, $this->row($c)['parent'], 'child->parent link survives direct child deactivation');
        update_framework_status(1, $c);
        self::assertSame($p, $this->row($c)['parent'], 'child->parent link survives direct child reactivation');
    }

    public function test_activating_child_activates_ancestors_but_not_siblings(): void
    {
        // Cascade-UP is preserved: activating a child activates its parent chain,
        // but must not bleed into sibling branches.
        $root = $this->seedFramework('AncRoot', 0);
        $mid = $this->seedFramework('AncMid', $root);
        $leaf = $this->seedFramework('AncLeaf', $mid);
        $sibling = $this->seedFramework('AncSibling', $root); // sibling branch, stays inactive

        // Start the whole tree inactive.
        update_framework_status(2, $root);

        // Activate just the leaf.
        update_framework_status(1, $leaf);

        self::assertSame(1, $this->row($leaf)['status'], 'activated leaf is active');
        self::assertSame(1, $this->row($mid)['status'], 'ancestor (mid) cascade-activated');
        self::assertSame(1, $this->row($root)['status'], 'ancestor (root) cascade-activated');
        self::assertSame(2, $this->row($sibling)['status'], 'sibling branch is untouched');
    }
}
