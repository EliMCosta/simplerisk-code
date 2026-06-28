<?php
/**
 * Unit tests mirroring the pure helpers in js/simplerisk/dataTables.tree.js
 * ($.srTree.flattenTree, computeVisibility, mergeLazyChildren).
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SrTreeHelpersTest extends TestCase
{
    /** @param list<array<string, mixed>> $nested */
    private static function flattenTree(
        array $nested,
        int|string|null $parentId,
        int $depth,
        array $out,
        string $idField,
        bool $lazyLoad
    ): array {
        foreach ($nested as $row) {
            if (!$row) {
                continue;
            }
            $kids = $row['children'] ?? null;
            $id = $row[$idField];
            $hasLazyChildren = $lazyLoad && !empty($row['hasLazyChildren']);
            $hasChildren = (is_array($kids) && count($kids) > 0) || $hasLazyChildren;
            $out[] = [
                'id' => $id,
                'parentId' => $parentId,
                'depth' => $depth,
                'hasChildren' => $hasChildren,
                'lazyChildren' => $hasLazyChildren,
            ];
            if (is_array($kids) && count($kids) > 0) {
                $out = self::flattenTree($kids, $id, $depth + 1, $out, $idField, $lazyLoad);
            }
        }
        return $out;
    }

    /** @param list<array<string, mixed>> $flat */
    private static function computeVisibility(array $flat, callable $isOpenFn): array
    {
        $visible = [];
        foreach ($flat as $row) {
            $id = $row['id'];
            $pid = $row['parentId'];
            $isRoot = ($pid === null || $pid === '' || $pid === 0);
            $visible[$id] = $isRoot ? true : (($visible[$pid] ?? false) && (bool)$isOpenFn($pid));
        }
        return $visible;
    }

    /** @param list<array<string, mixed>> $nested */
    private static function mergeLazyChildren(array &$nested, string $idField, int|string $parentId, array $children): bool
    {
        foreach ($nested as &$row) {
            if (($row[$idField] ?? null) == $parentId) {
                $row['children'] = $children;
                $row['hasLazyChildren'] = false;
                return true;
            }
            if (!empty($row['children']) && self::mergeLazyChildren($row['children'], $idField, $parentId, $children)) {
                return true;
            }
        }
        unset($row);
        return false;
    }

    public function test_flattenTree_marks_lazy_nodes_as_expandable(): void
    {
        $flat = self::flattenTree([
            ['id' => 'framework_1', 'hasLazyChildren' => true],
        ], null, 0, [], 'id', true);

        self::assertCount(1, $flat);
        self::assertTrue($flat[0]['hasChildren']);
        self::assertTrue($flat[0]['lazyChildren']);
    }

    public function test_computeVisibility_hides_collapsed_descendants(): void
    {
        $flat = [
            ['id' => 'a', 'parentId' => null],
            ['id' => 'b', 'parentId' => 'a'],
        ];
        $expanded = ['a' => false];
        $visible = self::computeVisibility($flat, static fn ($id) => $expanded[$id] ?? true);

        self::assertTrue($visible['a']);
        self::assertFalse($visible['b']);
    }

    public function test_mergeLazyChildren_attaches_fetched_children(): void
    {
        $nested = [
            ['id' => 'framework_1', 'hasLazyChildren' => true],
        ];
        self::assertTrue(self::mergeLazyChildren($nested, 'id', 'framework_1', [
            ['id' => 'control_1_2', 'name' => 'AC-2'],
        ]));
        self::assertSame('control_1_2', $nested[0]['children'][0]['id']);
        self::assertFalse($nested[0]['hasLazyChildren']);
    }
}
