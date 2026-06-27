<?php
/**
 * Unit tests for sanitize_json_response() — simplerisk/includes/functions.php:31005.
 *
 * Parses the free-form JSON an AI/LLM returns for risk details+mitigation: it
 * strips a UTF-8 BOM and control bytes, extracts just the two fields it cares
 * about via regex, converts literal "\n" escapes to newlines, and re-emits clean
 * JSON. The contract worth pinning is that the OUTPUT is always valid JSON with
 * exactly the keys details+mitigation, so assertions decode the result.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class JsonResponseTest extends TestCase
{
    /** Decode the sanitized output — always valid JSON by construction. */
    private function decode(string $input): array
    {
        $out = sanitize_json_response($input);
        self::assertJson($out, 'sanitized output must be valid JSON');
        return json_decode($out, true);
    }

    public function test_extracts_details_and_mitigation_fields(): void
    {
        $d = $this->decode('{"details":"foo","mitigation":"bar","extra":"ignore"}');
        self::assertSame('foo', $d['details']);
        self::assertSame('bar', $d['mitigation']);
        // Only the two known keys survive.
        self::assertSame(['details', 'mitigation'], array_keys($d));
    }

    public function test_missing_fields_default_to_empty_string(): void
    {
        $d = $this->decode('{"unrelated":"value"}');
        self::assertSame('', $d['details']);
        self::assertSame('', $d['mitigation']);
    }

    public function test_converts_literal_backslash_n_to_newlines(): void
    {
        // Input JSON has the escaped sequence \n (backslash + n) inside the value.
        $input = '{"details":"line1\\nline2","mitigation":"x"}';
        $d = $this->decode($input);
        self::assertStringContainsString('line1', $d['details']);
        self::assertStringContainsString('line2', $d['details']);
        self::assertStringContainsString("\n", $d['details']); // now a real newline
    }

    public function test_strips_utf8_bom_and_control_bytes(): void
    {
        $bom = "\xEF\xBB\xBF";
        // BOM prefix, a NUL, and a control byte surrounding the payload.
        $input = $bom . '{"details":"a"}' . "\x00" . '{"mitigation":"b"}' . "\x01";
        $d = $this->decode($input);
        self::assertSame('a', $d['details']);
        self::assertSame('b', $d['mitigation']);
    }

    public function test_output_is_canonical_json_with_unescaped_slashes(): void
    {
        // JSON_UNESCAPED_SLASHES means forward slashes are not backslash-escaped.
        $out = sanitize_json_response('{"details":"a/b","mitigation":"c"}');
        self::assertStringNotContainsString('\\/', $out);
        self::assertStringContainsString('a/b', $out);
    }

    public function test_empty_input_yields_empty_fields(): void
    {
        $d = $this->decode('');
        self::assertSame('', $d['details']);
        self::assertSame('', $d['mitigation']);
    }
}
