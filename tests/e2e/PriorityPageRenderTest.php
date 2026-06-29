<?php
/**
 * E2E render/auth-gate coverage for the priority user-facing pages that the
 * existing HostTreePageTest does NOT already cover — specifically the Risk
 * Management and Compliance surfaces that the journey tests (RiskLifecycle,
 * Compliance/Audit) exercise functionally.
 *
 * Same black-box contract as HostTreePageTest: for each page, an authenticated
 * request by the all-permission e2e admin must return 200 and contain the
 * page's main-surface marker (proving the route resolves and the page actually
 * renders, not just a 200 shell), and an unauthenticated request must NOT
 * render — enforce_permission() redirects to login (a 3xx), never a 200.
 *
 * These are the no-id pages. Pages that need a seeded entity (view.php?id=,
 * testing.php?id=) are covered inside the journey tests where the entity is
 * created, so this file stays a pure, dependency-free data-provider suite.
 *
 * Authenticated via the real CSRF-gated login flow in E2ETestCase because these
 * are web routes, not /api/v2/ routes.
 */
declare(strict_types=1);

final class PriorityPageRenderTest extends E2ETestCase
{
    /**
     * Each priority page and a string the authenticated rendered body must
     * contain — the page's primary surface marker (form field, DataTables
     * settings target, or filter control), verified in source before use:
     *  - submit-risk form:     name='subject'          (display.php:201, via management/index.php:275 display_add_risk)
     *  - review-risks grid:    data-sr-target="review-risks"   (review_risks.php:33)
     *  - plan-mitigations:     data-sr-target="plan-mitigations" (plan_mitigations.php:41)
     *  - management-review:    data-sr-target="management-review" (management_review.php:41)
     *  - active-audits grid:   custom_display_settings-active_audits (active_audits.php:28)
     *  - define-tests page:    filter_by_control_framework (compliance/index.php:167)
     */
    public static function priorityPages(): array
    {
        return [
            'submit-risk'     => ['/management/index.php',          "name='subject'"],
            'review-risks'    => ['/management/review_risks.php',   'data-sr-target="review-risks"'],
            'plan-mitigations'=> ['/management/plan_mitigations.php','data-sr-target="plan-mitigations"'],
            'management-review'=>['/management/management_review.php','data-sr-target="management-review"'],
            'active-audits'   => ['/compliance/active_audits.php',  'custom_display_settings-active_audits'],
            'define-tests'    => ['/compliance/index.php',          'filter_by_control_framework'],
        ];
    }

    /**
     * @dataProvider priorityPages
     */
    public function test_priority_page_renders_for_authenticated(string $path, string $marker): void
    {
        [$code, , $body, $err] = $this->authedGet($path);
        self::assertSame('', $err, "curl error hitting {$path}: {$err}");
        self::assertSame(200, $code, "expected 200 for authenticated {$path}, got {$code}");
        self::assertNotEmpty($body, "empty body for authenticated {$path}");
        self::assertStringContainsString(
            $marker,
            $body,
            "render marker '{$marker}' missing from {$path} — page did not render its main surface"
        );
    }

    /**
     * @dataProvider priorityPages
     */
    public function test_priority_page_redirects_unauthenticated(string $path): void
    {
        [$code] = $this->unauthedGet($path);
        // Unauthenticated => enforce_permission() redirects to the login page.
        self::assertNotSame(200, $code, "unauthenticated {$path} must not render (got 200)");
        self::assertGreaterThanOrEqual(300, $code, "expected a redirect for unauthenticated {$path}, got {$code}");
        self::assertLessThan(400, $code, "expected a 3xx redirect for unauthenticated {$path}, got {$code}");
    }
}
