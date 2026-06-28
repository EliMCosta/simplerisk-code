<?php
/**
 * Integration regression tests for the server-side builders that feed the
 * jQuery DataTables tree grid widgets. These lock down the response SHAPE and the
 * tree-assembly LOGIC that a future DataTables migration must preserve.
 *
 * Why integration (not e2e): the treegrid handlers do `echo json_encode(...);
 * exit`, which kills a test process. We instead call the underlying builders
 * directly — get_frameworks_as_treegrid() / makeTree() — which return arrays
 * with no side effects. Every write lands in the IntegrationTestCase rollback
 * transaction (get_frameworks() opens the shared global PDO that setUp already
 * wrapped), so seeded rows are visible to the builder and never persist.
 *
 * The flagship surface is the Governance Frameworks treegrid (governance/index.php),
 * fed by makeTree() + get_frameworks_as_treegrid() in includes/governance.php.
 */
declare(strict_types=1);

final class TreeBuilderTest extends IntegrationTestCase
{
    /** Stable field set every framework row exposes to the treegrid (status=1). */
    private const ACTIVE_ROW_FIELDS = [
        'value', 'parent', 'name', 'description', 'status', 'order',
        'last_audit_date', 'next_audit_date', 'desired_frequency',
        'actions', 'children',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // get_frameworks_as_treegrid() / makeTree() live in includes/governance.php,
        // which the test bootstrap does not cascade in. loadExtra() is for extras;
        // core includes are pulled in directly (same pattern as FrameworkControlsSqlTest).
        require_once SIMPLERISK_APP_ROOT . '/includes/governance.php';

        // get_frameworks_as_treegrid() reads `global $escaper` to build the
        // actions HTML and escape the name. Under the CLI bootstrap $escaper is
        // not instantiated, so provide the same escaper the web app uses.
        if (!isset($GLOBALS['escaper'])) {
            $GLOBALS['escaper'] = new \Laminas\Escaper\Escaper('utf-8');
        }
    }

    /**
     * Seed a framework row inside the rollback transaction and return its value.
     * name/description are stored exactly as the app stores them; the builder
     * round-trips them through try_decrypt(), so assertions mirror that.
     */
    private function seedFramework(string $name, int $parent = 0, int $status = 1, int $order = 0): int
    {
        $stmt = $this->txdb->prepare(
            "INSERT INTO frameworks (`name`, `description`, `parent`, `status`, `order`)
             VALUES (:name, :desc, :parent, :status, :order)"
        );
        $desc = $name . ' description';
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':desc', $desc);
        $stmt->bindValue(':parent', $parent, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_INT);
        $stmt->bindValue(':order', $order, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $this->txdb->lastInsertId();
    }

    public function test_status_1_nests_frameworks_by_parent_via_makeTree(): void
    {
        // A (root) -> B -> C : a three-deep chain.
        $a = $this->seedFramework('TierA', 0);
        $b = $this->seedFramework('TierB', $a);
        $c = $this->seedFramework('TierC', $b);

        $result = get_frameworks_as_treegrid(1);

        // Envelope: DataTables tree grid expects {totalCount, rows}.
        self::assertArrayHasKey('totalCount', $result);
        self::assertArrayHasKey('rows', $result);

        // Only the seeded roots that have parent==0 surface as top-level rows;
        // the live DB may contain other active frameworks, so find OUR chain.
        $roots = array_values(array_filter($result['rows'], fn ($r) => (int) $r['value'] === $a));
        self::assertCount(1, $roots, 'seeded root must appear as a top-level row');

        $root = $roots[0];
        self::assertSame([$b], array_map(fn ($c) => (int) $c['value'], $root['children'] ?? []),
            'B must nest under A');
        self::assertSame([$c], array_map(fn ($gc) => (int) $gc['value'], $root['children'][0]['children'] ?? []),
            'C must nest under B (3-deep chain)');
        self::assertGreaterThanOrEqual(3, $result['totalCount'], 'totalCount counts every node in the tree');
    }

    public function test_status_1_row_schema_matches_contract(): void
    {
        $a = $this->seedFramework('SchemaRoot', 0);
        $b = $this->seedFramework('SchemaChild', $a);

        $result = get_frameworks_as_treegrid(1);
        $root = current(array_filter($result['rows'], fn ($r) => (int) $r['value'] === $a)) ?: null;
        self::assertNotNull($root, 'seeded root missing');

        // A root row carries exactly the contract field set (no more, no less).
        self::assertSame(self::ACTIVE_ROW_FIELDS, array_keys($root),
            'framework row field set drifted — treegrid columns depend on these names');

        // actions is the rendered HTML for the edit/delete buttons.
        self::assertStringContainsString('framework-block--edit', (string) $root['actions']);
        self::assertStringContainsString('framework-block--delete', (string) $root['actions']);
        self::assertStringContainsString('data-id=\'' . $a . '\'', (string) $root['actions']);

        // name is HTML-escaped and round-trips through try_decrypt (the storage
        // transform get_frameworks() applies).
        $escaper = $GLOBALS['escaper'];
        self::assertSame($escaper->escapeHtml((string) try_decrypt('SchemaRoot')), $root['name']);
    }

    public function test_status_2_returns_flat_list_without_nesting(): void
    {
        // status=2 (inactive) bypasses makeTree entirely — flat rows, no children.
        $x = $this->seedFramework('InactiveOne', 0, 2);
        $y = $this->seedFramework('InactiveTwo', $x, 2);

        $result = get_frameworks_as_treegrid(2);

        self::assertArrayHasKey('totalCount', $result);
        self::assertArrayHasKey('rows', $result);

        $ours = array_filter($result['rows'], fn ($r) => in_array((int) $r['value'], [$x, $y], true));
        self::assertCount(2, $ours, 'both inactive frameworks present in the flat list');
        foreach ($ours as $row) {
            self::assertArrayNotHasKey('children', $row, 'status=2 rows must NOT be nested');
        }
        self::assertSame(count($result['rows']), $result['totalCount'],
            'status=2 totalCount is the flat row count (not a node count)');
    }

    public function test_empty_active_tree_returns_zero_totalCount_and_empty_rows(): void
    {
        // With no active frameworks at all (we seeded none with status=1 here),
        // the live DB may still have active frameworks, so we instead assert the
        // shape contract directly via makeTree on an empty input.
        $results = [];
        $count = 0;
        makeTree([], 0, $results, $count);

        $empty = ['totalCount' => $count, 'rows' => $results['children'] ?? []];
        self::assertSame(0, $empty['totalCount']);
        self::assertSame([], $empty['rows']);
    }

    public function test_makeTree_nests_multiple_roots_and_siblings(): void
    {
        // Two roots, one with two children, one childless — exercises sibling
        // ordering and multi-root handling.
        $r1 = $this->seedFramework('Root1', 0);
        $r2 = $this->seedFramework('Root2', 0);
        $c1 = $this->seedFramework('Child1a', $r1);
        $c2 = $this->seedFramework('Child1b', $r1);

        $result = get_frameworks_as_treegrid(1);
        $byValue = [];
        foreach ($result['rows'] as $row) {
            $byValue[(int) $row['value']] = $row;
        }

        self::assertArrayHasKey($r1, $byValue);
        self::assertArrayHasKey($r2, $byValue);
        self::assertSame([$c1, $c2], array_map(fn ($c) => (int) $c['value'], $byValue[$r1]['children'] ?? []),
            'both children nest under their parent in seed order');
        self::assertArrayNotHasKey('children', $byValue[$r2],
            'childless root has no children key');
    }
}
