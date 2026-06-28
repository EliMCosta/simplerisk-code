<?php
/**
 * E2E render tests for the PHP host pages that mount a jQuery DataTables tree grid.
 *
 * A DataTables migration will replace these pages wholesale; these tests lock down
 * the CURRENT contract that each page (a) is routed, (b) is gated behind login
 * (an unauthenticated GET must NOT render the page), and (c) renders its
 * treegrid mount marker for an authenticated, permitted user. When the rewrite
 * lands, the per-page marker changes on purpose — approve it via the test, the
 * same way golden fixtures are approved via bin/regen-tree-fixtures.
 *
 * Authenticated via the real CSRF-gated login flow in E2ETestCase, because the
 * host pages are web routes (governance/*.php, admin/*.php, assets/*.php), not
 * /api/v2/ routes, so an API key would not cover them.
 */
declare(strict_types=1);

final class HostTreePageTest extends E2ETestCase
{
    /**
     * Each host page and a string the authenticated rendered body must contain.
     * The marker is the DataTables tree grid mount point (or, for the org-hierarchy
     * page whose treegrid only renders when its Extra is enabled, the page's
     * always-present OH content).
     */
    public static function hostPages(): array
    {
        return [
            'frameworks'       => ['/governance/index.php',                'initAsFrameworkTreegrid'],
            'documentation'    => ['/governance/documentation.php',         'treegrid-container'],
            'doc-exceptions'   => ['/governance/document_exceptions.php',   'initAsExceptionTreegrid'],
            'org-hierarchy'    => ['/admin/organizational_hierarchy.php',   'business_unit'],
            'asset-groups'     => ['/assets/manage_asset_groups.php',       'manage-asset-groups-table-container'],
            'initiate-audits'  => ['/compliance/audit_initiation.php',      'initiate_audit_treegrid'],
        ];
    }

    /**
     * @dataProvider hostPages
     */
    public function test_host_page_renders_treegrid_for_authenticated(string $path, string $marker): void
    {
        [$code, , $body, $err] = $this->authedGet($path);
        self::assertSame('', $err, "curl error hitting {$path}: {$err}");
        self::assertSame(200, $code, "expected 200 for authenticated {$path}, got {$code}");
        self::assertNotEmpty($body, "empty body for authenticated {$path}");
        self::assertStringContainsString(
            $marker,
            $body,
            "treegrid mount marker '{$marker}' missing from {$path} — page did not render its DataTables tree surface"
        );
    }

    /**
     * @dataProvider hostPages
     */
    public function test_host_page_redirects_unauthenticated_to_login(string $path): void
    {
        [$code, , $body] = $this->unauthedGet($path);
        // Unauthenticated => enforce_permission() redirects to the login page.
        self::assertNotSame(200, $code, "unauthenticated {$path} must not render (got 200)");
        self::assertGreaterThanOrEqual(300, $code, "expected a redirect for unauthenticated {$path}, got {$code}");
        self::assertLessThan(400, $code, "expected a 3xx redirect for unauthenticated {$path}, got {$code}");
    }

    /**
     * The org-hierarchy page mounts its DataTables tree (<table id='business_units'
     * ... data-sr-tree> pointing at /api/v2/organizational_hierarchy/
     * business_unit/tree) ONLY when the Organizational Hierarchy Extra is enabled.
     * When it is, lock that mount down; when it isn't, there is nothing to assert
     * here (the page itself is still covered by the data provider above).
     */
    public function test_organizational_hierarchy_treegrid_mount_when_extra_enabled(): void
    {
        if (!organizational_hierarchy_extra()) {
            self::markTestSkipped('Organizational Hierarchy Extra is not enabled — treegrid mount is absent by design.');
        }
        [, , $body] = $this->authedGet('/admin/organizational_hierarchy.php');
        self::assertStringContainsString("id='business_units'", $body, 'business_units tree table missing');
        self::assertStringContainsString('data-sr-tree', $body, 'data-sr-tree mount attribute missing');
        self::assertStringContainsString(
            '/api/v2/organizational_hierarchy/business_unit/tree',
            $body,
            'tree data-url missing'
        );
    }
}
