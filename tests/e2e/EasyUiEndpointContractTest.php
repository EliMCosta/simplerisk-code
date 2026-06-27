<?php
/**
 * E2E wire-contract tests for the AJAX endpoints that feed the jQuery EasyUI
 * treegrid widgets. These are the data contracts a Laravel rewrite MUST keep
 * serving — same URLs, same status, same content-type, same JSON envelope.
 *
 * Each endpoint has a committed GOLDEN fixture (tests/fixtures/easyui/<slug>.json)
 * capturing the live {status, contentType, envelope}. This test asserts the
 * running app still matches it; refresh the fixtures after an intentional
 * change with:  bin/regen-easyui-fixtures
 *
 * Notably the contract is heterogeneous and even a little surprising:
 *   - frameworks & documents treegrid echo raw JSON and exit, so Apache labels
 *     them text/html (no Content-Type header is set). Captured on purpose.
 *   - exceptions / asset-group / associated-exceptions go through json_response()
 *     and return application/json with {status,status_message,data}.
 * A rewrite that "normalizes" any of these shows up as a golden diff to approve.
 */
declare(strict_types=1);

final class EasyUiEndpointContractTest extends E2ETestCase
{
    /**
     * The EasyUI data-surface catalog. Each entry is [slug, path].
     * The slug names the golden fixture file; the path is what the widget hits.
     * Shared with RegenEasyUiFixturesTest so the two never drift.
     */
    public static function endpoints(): array
    {
        return [
            'frameworks-status1' => ['frameworks-status1', '/api/v2/governance/frameworks/treegrid?status=1'],
            'frameworks-status2' => ['frameworks-status2', '/api/v2/governance/frameworks/treegrid?status=2'],
            'documents-policy'   => ['documents-policy',   '/api/v2/governance/documents/treegrid?type=policy'],
            'exceptions-policy'  => ['exceptions-policy',  '/api/v2/exceptions/tree?type=policy'],
            'exceptions-control' => ['exceptions-control', '/api/v2/exceptions/tree?type=control'],
            'asset-group'        => ['asset-group',        '/api/v2/asset-group/tree?page=1&rows=10'],
            'associated-exc'     => ['associated-exc',     '/api/v2/associated-exceptions/tree?type=policy&id=1'],
        ];
    }

    private function fixtureDir(): string
    {
        return __DIR__ . '/../fixtures/easyui';
    }

    private function loadGolden(string $slug): array
    {
        $file = $this->fixtureDir() . "/{$slug}.json";
        self::assertFileExists($file, "missing golden fixture for '{$slug}' — run bin/regen-easyui-fixtures");
        $g = json_decode((string) file_get_contents($file), true);
        self::assertIsArray($g, "golden fixture for '{$slug}' is not valid JSON");
        return $g;
    }

    /**
     * @dataProvider endpoints
     */
    public function test_authenticated_endpoint_matches_golden(string $slug, string $path): void
    {
        [$code, $contentType, $body, $err] = $this->authedGet($path);
        self::assertSame('', $err, "curl error hitting {$path}: {$err}");

        $golden = $this->loadGolden($slug);

        self::assertSame($golden['status'], $code,
            "status for {$path} drifted from golden {$golden['status']}");

        // The body is JSON regardless of the (possibly text/html) Content-Type.
        $decoded = $this->decodeJson($body, $path);
        $this->assertEnvelope($decoded, $golden['envelope'], $path);

        // Content-Type is part of the contract too (see class docs).
        self::assertStringContainsString(
            $golden['contentType'],
            $contentType,
            "Content-Type for {$path} drifted from golden '{$golden['contentType']}'"
        );
    }

    /**
     * Every EasyUI data endpoint must reject an unauthenticated request with a
     * 401 JSON response (the /api/v2/ gate fires before routing). A rewrite that
     * accidentally serves tree data anonymously is a security regression.
     *
     * @dataProvider endpoints
     */
    public function test_unauthenticated_request_is_rejected(string $slug, string $path): void
    {
        [$code, $contentType, $body] = $this->unauthedGet($path);

        self::assertSame(401, $code, "expected 401 for {$path} when unauthenticated, got {$code}");
        self::assertStringContainsString('json', $contentType, "expected JSON 401 for {$path}");
        $decoded = $this->decodeJson($body, $path);
        self::assertSame(401, $decoded['status'] ?? null, "401 body missing status=401 for {$path}");
    }
}
