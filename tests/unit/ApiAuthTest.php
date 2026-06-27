<?php
/**
 * Unit tests for the API Extra key-auth helpers (extras/api/includes/auth.php)
 * that need no database: request-key extraction, token index/verifier math,
 * the legacy-entry predicate, the affirmative-value toggles, and the MCP-only
 * session gate.
 *
 * Headers are simulated via $_SERVER['HTTP_*'] — the ralouphie/getallheaders
 * polyfill (autoloaded by core) reads them there, and auth.php consumes
 * getallheaders(). The pepper is pinned via the env var so verifier output is
 * deterministic.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ApiAuthTest extends TestCase
{
    use LoadsExtras;

    private const PEPPER = 'unit-test-pepper';

    /** @var array Snapshot restored in tearDown so HTTP_* keys never leak. */
    private array $serverBackup;

    public static function setUpBeforeClass(): void
    {
        // Pin the pepper (option 1 in api_token_pepper()) so verifiers are stable
        // and api_token_pepper() never auto-generates into the settings table.
        putenv('SIMPLERISK_API_TOKEN_PEPPER=' . self::PEPPER);
        self::loadExtra('api', 'auth.php');
    }

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_GET = [];
        unset($GLOBALS['setting_api_server_enabled'], $GLOBALS['setting_api_allow_user_keys']);
        $_SESSION = [];
    }

    /** Set the given HTTP headers (via $_SERVER) + query params, then read the key. */
    private function keyFrom(array $headers, array $get = []): ?string
    {
        foreach ($headers as $name => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $_SERVER[$key] = $value;
        }
        $_GET = $get;
        return api_read_request_key();
    }

    public function test_reads_key_from_x_api_key_header(): void
    {
        self::assertSame('hdr-key', $this->keyFrom(['X-API-KEY' => 'hdr-key']));
        // Header lookup is case-insensitive (array_change_key_case -> lower).
        self::assertSame('hdr-key', $this->keyFrom(['x-api-key' => 'hdr-key']));
    }

    public function test_reads_key_from_bearer_authorization_header(): void
    {
        self::assertSame('bear-key', $this->keyFrom(['Authorization' => 'Bearer bear-key']));
        self::assertSame('bear-key', $this->keyFrom(['authorization' => 'Bearer   bear-key  ']));
    }

    public function test_reads_bare_authorization_header(): void
    {
        // Some clients send the raw key instead of "Bearer <key>".
        self::assertSame('raw-key', $this->keyFrom(['Authorization' => 'raw-key']));
    }

    public function test_reads_key_from_query_parameter(): void
    {
        self::assertSame('q-key', $this->keyFrom([], ['key' => 'q-key']));
    }

    public function test_returns_null_when_no_key_present(): void
    {
        self::assertNull($this->keyFrom([], []));
        // Empty strings count as absent.
        self::assertNull($this->keyFrom(['X-API-KEY' => '   '], ['key' => '']));
    }

    public function test_x_api_key_header_takes_precedence_over_query(): void
    {
        self::assertSame(
            'from-header',
            $this->keyFrom(['X-API-KEY' => 'from-header'], ['key' => 'from-query'])
        );
    }

    public function test_token_index_is_deterministic_public_lookup(): void
    {
        // First 16 hex chars of SHA-256; stable and non-secret.
        $token = 'abc123';
        self::assertSame(substr(hash('sha256', $token), 0, 16), api_token_index($token));
        self::assertSame(api_token_index($token), api_token_index($token));
        // Distinct tokens get distinct indexes.
        self::assertNotSame(api_token_index('abc123'), api_token_index('abc124'));
    }

    public function test_token_verifier_is_peppered_hmac_and_differs_from_index(): void
    {
        $token = 'some-secret-token';
        $expected = hash_hmac('sha256', $token, self::PEPPER);
        self::assertSame($expected, api_token_verifier($token));
        // Verifier is never the same as the public index.
        self::assertNotSame(api_token_index($token), api_token_verifier($token));
        // Distinct tokens -> distinct verifiers.
        self::assertNotSame(api_token_verifier('t1'), api_token_verifier('t2'));
    }

    public static function legacyEntries(): array
    {
        return [
            'plain sha256 hash, no alg' => [['hash' => 'x'], true],
            'has alg but no verifier'   => [['alg' => 'hmacsha256'], true],
            'modern v1 entry'           => [['alg' => 'hmacsha256', 'verifier' => 'y'], false],
            'empty entry'               => [[], true],
        ];
    }

    /**
     * @dataProvider legacyEntries
     */
    public function test_entry_is_legacy_predicate(array $entry, bool $expected): void
    {
        self::assertSame($expected, api_entry_is_legacy($entry));
    }

    public static function toggleValues(): array
    {
        return [
            '1' => ['1', true],
            'true' => ['true', true],
            'TRUE' => ['TRUE', true],
            'on' => ['on', true],
            'yes' => ['yes', true],
            'enabled' => ['enabled', true],
            '0' => ['0', false],
            'absent' => [null, false],
            'garbage' => ['nope', false],
            'empty' => ['', false],
        ];
    }

    /**
     * @dataProvider toggleValues
     */
    public function test_server_enabled_is_strictly_affirmative(?string $value, bool $expected): void
    {
        $this->setCached('api_server_enabled', $value);
        self::assertSame($expected, api_is_server_enabled());
    }

    /**
     * @dataProvider toggleValues
     */
    public function test_user_keys_allowed_is_strictly_affirmative(?string $value, bool $expected): void
    {
        $this->setCached('api_allow_user_keys', $value);
        self::assertSame($expected, api_user_keys_allowed());
    }

    public function test_session_blocks_rest_auth_reflects_mcp_flag(): void
    {
        $_SESSION = [];
        self::assertFalse(api_session_blocks_rest_auth());
        $_SESSION['api_mcp_only'] = true;
        self::assertTrue(api_session_blocks_rest_auth());
    }

    /** Seed get_setting()'s $GLOBALS cache so the toggle reads without touching the DB. */
    private function setCached(string $name, ?string $value): void
    {
        if ($value === null) {
            unset($GLOBALS['setting_' . $name]);
        } else {
            $GLOBALS['setting_' . $name] = $value;
        }
    }
}
