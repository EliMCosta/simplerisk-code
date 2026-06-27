<?php
/**
 * End-to-end smoke test: the app answers over HTTPS and the login page renders.
 * Runs inside the simplerisk-app container (curl hits the container's own Apache
 * on :443). The self-signed cert is expected, so TLS verification is disabled.
 * Authenticated flows are intentionally out of scope (smoke only).
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HttpSmokeTest extends TestCase
{
    /** GET a path over HTTPS, following redirects; returns [code, body, error]. */
    private function httpsGet(string $path): array
    {
        $ch = curl_init('https://localhost' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false, // self-signed dev cert
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return [(int) $code, (string) $body, $err];
    }

    public function test_app_answers_over_https(): void
    {
        [$code, $body, $err] = $this->httpsGet('/');
        self::assertSame('', $err, "curl error hitting /: {$err}");
        self::assertLessThan(400, $code, "expected a 2xx/3xx at /, got {$code}");
        self::assertNotEmpty($body, 'expected a non-empty response body at /');
    }

    public function test_login_form_renders(): void
    {
        [, $body, ] = $this->httpsGet('/');
        // simplerisk/index.php renders name="user" + type="password" inputs.
        self::assertStringContainsStringIgnoringCase('name="user"', $body, 'login user field missing');
        self::assertStringContainsStringIgnoringCase('type="password"', $body, 'password field missing');
    }

    public function test_installer_is_not_exposed(): void
    {
        [, $body, ] = $this->httpsGet('/install.php');
        // Once config.php exists, bootstrap.php must NOT render the installer.
        self::assertStringNotContainsStringIgnoringCase(
            'SimpleRisk Installation',
            $body,
            'installer form should not be exposed on an installed instance'
        );
    }

    /**
     * GET an /api/v2 path WITHOUT following redirects; return [code, contentType].
     * Redirects are disabled so a 302-to-login can't masquerade as a 200 here.
     */
    private function apiStatus(string $path): array
    {
        $ch = curl_init('https://localhost' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // self-signed dev cert
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        return [$code, $type];
    }

    /**
     * The three risk-view endpoints added in the header/sidebar refactor
     * (api/v2/index.php: comments, audit-trail, score_over_time).
     *
     * The /api/v2/ API-key gate fires BEFORE routing, so an unauthenticated
     * request gets 401 whether or not the route is registered. This therefore
     * guards the bootstrap health and the JSON response shape (the
     * lang-not-loaded regression returned text/html here) — NOT route
     * registration, which needs an authenticated request and is out of scope for
     * this smoke suite. The tool layer's authenticated dispatch is covered by
     * ApiDispatchTest.
     *
     * @dataProvider provideNewRiskViewEndpoints
     */
    public function test_new_risk_view_endpoints_reject_unauthenticated_with_json(string $path): void
    {
        [$code, $type] = $this->apiStatus($path);
        self::assertSame(401, $code, "expected 401 for {$path}, got {$code}");
        self::assertStringContainsString('json', $type, "expected JSON for {$path}, got {$type}");
    }

    public function provideNewRiskViewEndpoints(): array
    {
        return [
            'comments'        => ['/api/v2/risks/1000/comments'],
            'audit-trail'     => ['/api/v2/risks/1000/audit-trail'],
            'score_over_time' => ['/api/v2/management/risk/score_over_time?id=1000'],
        ];
    }
}
