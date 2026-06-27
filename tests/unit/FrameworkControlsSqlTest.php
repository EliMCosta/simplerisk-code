<?php
/**
 * Unit tests for the framework-controls visibility SQL builders added in the
 * header/sidebar refactor (simplerisk/includes/governance.php):
 *   framework_controls_active_framework_visibility_sql($control_alias='t1') :1002
 *   framework_is_leaf_framework_sql($framework_alias='f')                  :951
 *
 * These are pure string builders (no DB, no session), so they are fast unit
 * tests that lock the generated SQL shape — the NOT EXISTS / EXISTS visibility
 * contract and alias embedding — against drift. governance.php is NOT loaded by
 * the test bootstrap's functions.php cascade (it loads lazily via display.php /
 * api.php), so we require_once it here.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FrameworkControlsSqlTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!function_exists('framework_controls_active_framework_visibility_sql')) {
            require_once SIMPLERISK_APP_ROOT . '/includes/governance.php';
        }
    }

    public function test_visibility_sql_default_alias_shape(): void
    {
        $sql = trim(framework_controls_active_framework_visibility_sql());

        // A control is visible if it has NO framework mapping...
        self::assertStringContainsString('NOT EXISTS', $sql);
        self::assertStringContainsString('`framework_control_mappings`', $sql);
        self::assertStringContainsString('m_vis.control_id = t1.id', $sql);
        // ...OR it is mapped to an ACTIVE leaf framework.
        self::assertStringContainsString('OR EXISTS', $sql);
        self::assertStringContainsString('f_vis.status = 1', $sql);
        // The leaf-framework subquery (framework_is_leaf_framework_sql) is embedded.
        self::assertStringContainsString('f_child.parent = f_vis.value', $sql);
        // Wrapping parentheses make the fragment safe to AND into a WHERE clause.
        self::assertStringStartsWith('(', $sql);
        self::assertStringEndsWith(')', $sql);
    }

    public function test_visibility_sql_custom_alias_replaces_default(): void
    {
        $sql = framework_controls_active_framework_visibility_sql('c');

        self::assertStringContainsString('m_vis.control_id = c.id', $sql);
        // The default alias must NOT leak through when a custom one is supplied.
        self::assertStringNotContainsString('t1.id', $sql);
        self::assertStringNotContainsString('= t1', $sql);
    }

    public function test_leaf_framework_sql_embeds_alias_into_self_join(): void
    {
        $sql = framework_is_leaf_framework_sql('f');
        self::assertStringContainsString('NOT EXISTS', $sql);
        self::assertStringContainsString('f_child.parent = f.value', $sql);

        // A different alias is wired into the self-join and the default does not leak.
        $sql2 = framework_is_leaf_framework_sql('zz');
        self::assertStringContainsString('f_child.parent = zz.value', $sql2);
        self::assertStringNotContainsString('f.value', $sql2);
    }
}
