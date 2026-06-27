<?php
/**
 * Unit tests for the API Extra protocol layer (extras/api/includes/protocol.php):
 * the JSON-RPC 2.0 / MCP response builders and the ApiToolException type.
 *
 * These are pure data builders with no side effects (the only functions in the
 * file that exit() — api_send_json / api_handle_cors — are intentionally not
 * under test, since they terminate the process). protocol.php is otherwise 0%
 * covered; this takes its builder logic to ~100%.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ApiProtocolTest extends TestCase
{
    use LoadsExtras;

    public static function setUpBeforeClass(): void
    {
        self::loadExtra('api', 'protocol.php');
    }

    public function test_result_wraps_payload_as_jsonrpc_success(): void
    {
        $r = api_result(7, ['risk_id' => 1042]);
        self::assertSame('2.0', $r['jsonrpc']);
        self::assertSame(7, $r['id']);
        self::assertSame(['risk_id' => 1042], $r['result']);
        self::assertArrayNotHasKey('error', $r);
    }

    public function test_error_shape_without_data(): void
    {
        $e = api_error('abc', JSONRPC_INVALID_PARAMS, 'bad arg');
        self::assertSame('2.0', $e['jsonrpc']);
        self::assertSame('abc', $e['id']);
        self::assertSame(JSONRPC_INVALID_PARAMS, $e['error']['code']);
        self::assertSame('bad arg', $e['error']['message']);
        self::assertArrayNotHasKey('data', $e['error']);
    }

    public function test_error_shape_includes_data_when_given(): void
    {
        $e = api_error(1, API_ERROR_TOOL, 'boom', ['hint' => 'x']);
        self::assertSame(API_ERROR_TOOL, $e['error']['code']);
        self::assertSame(['hint' => 'x'], $e['error']['data']);
    }

    public function test_text_result_wraps_value_as_single_text_block(): void
    {
        $r = api_text_result(['risk_id' => 1042]);
        self::assertCount(1, $r['content']);
        self::assertSame('text', $r['content'][0]['type']);
        // A non-string value is JSON-encoded (pretty-printed) into the text block.
        self::assertSame(['risk_id' => 1042], json_decode($r['content'][0]['text'], true));
        self::assertArrayNotHasKey('isError', $r);
    }

    public function test_text_result_passes_strings_through_unencoded(): void
    {
        $r = api_text_result('a plain string stays a plain string');
        self::assertSame('a plain string stays a plain string', $r['content'][0]['text']);
    }

    public function test_text_result_marks_errors(): void
    {
        $r = api_text_result('nope', true);
        self::assertTrue($r['isError']);
    }

    public function test_api_tool_exception_defaults_to_result_error(): void
    {
        // protocol_error=false -> the dispatcher renders an MCP result with
        // isError=true (the request was understood; the caller may retry).
        $ex = new ApiToolException('Permission denied.');
        self::assertSame('Permission denied.', $ex->getMessage());
        self::assertFalse($ex->protocol_error);
    }

    public function test_api_tool_exception_protocol_error_marks_invalid_params(): void
    {
        // protocol_error=true -> the dispatcher renders a JSON-RPC
        // invalid-params error (the request was malformed).
        $ex = new ApiToolException("Missing required parameter: 'risk_id'.", true);
        self::assertTrue($ex->protocol_error);
    }
}
