<?php
/**
 * Unit tests for core sanitization helpers in simplerisk/includes/functions.php:
 *   sanitize_int_array($int_array)              :26990
 *   isValidJson($string, $type = 'array')       :31046
 *   sanitze_array_for_json_encode($array)       :31068  (sic: typo is the real name)
 *
 * sanitize_int_array guards every ID-list sent to SQL (it drops anything that is
 * not 0-9 digits, including negatives and SQL-significant chars). isValidJson
 * underpins AI/config parsing. The control-char stripper protects json_encode
 * (which aborts on invalid UTF-8/bytes 0x00-0x1F).
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SanitizeTest extends TestCase
{
    public function test_sanitize_int_array_keeps_digits_and_drops_the_rest(): void
    {
        $in = [1, 'a', 2, '3x', null, '0', -1, 0, '42'];
        // ctype_digit((string)v): 1,'42' kept; 'a','3x',null,-1 dropped; '0'/0 kept.
        self::assertSame([1, 2, '0', 0, '42'], array_values(sanitize_int_array($in)));
    }

    public function test_sanitize_int_array_drops_negatives_and_sql_significant_chars(): void
    {
        // The whole point: '-1', '1; DROP', '1 OR 1=1' must never survive.
        self::assertSame([], array_values(sanitize_int_array(['-1', '-0', '1; DROP', '1 OR 1=1'])));
        self::assertSame([1, 2], array_values(sanitize_int_array([1, 2])));
    }

    public function test_sanitize_int_array_preserves_keys(): void
    {
        // array_filter preserves keys — callers that re-index rely on knowing this.
        $out = sanitize_int_array([1, 'x', 2]);
        self::assertSame([0 => 1, 2 => 2], $out);
    }

    public function test_sanitize_int_array_empty_and_non_array_inputs(): void
    {
        self::assertSame([], sanitize_int_array([]));
        self::assertSame([], sanitize_int_array('1,2,3')); // not an array
        self::assertSame([], sanitize_int_array(null));
    }

    public function test_isValidJson_object_is_valid_as_array_and_object(): void
    {
        self::assertTrue(isValidJson('{"a":1}'));                 // default type=array
        self::assertTrue(isValidJson('{"a":1}', 'array'));
        self::assertTrue(isValidJson('{"a":1}', 'object'));
    }

    public function test_isValidJson_array_is_valid_as_array_but_not_object(): void
    {
        // A JSON array literal is not a JSON object — this distinction matters.
        self::assertTrue(isValidJson('[1,2,3]', 'array'));
        self::assertFalse(isValidJson('[1,2,3]', 'object'));
    }

    public function test_isValidJson_rejects_scalars_invalid_and_null(): void
    {
        // Bare scalars, null, and garbage are neither arrays nor objects.
        self::assertFalse(isValidJson('123'));
        self::assertFalse(isValidJson('"text"'));
        self::assertFalse(isValidJson('null'));
        self::assertFalse(isValidJson('not json'));
        self::assertFalse(isValidJson(''));
    }

    public function test_sanitze_array_strips_control_chars_from_strings_only(): void
    {
        $in = ['a' => "x\x01y", 'nested' => ['c' => "p\x02q"], 'n' => 5, 'b' => true];
        $out = sanitze_array_for_json_encode($in);

        self::assertSame('xy', $out['a']);               // control char removed, recursion reaches it
        self::assertSame('pq', $out['nested']['c']);     // recurses into nested arrays
        self::assertSame(5, $out['n']);                  // non-string left untouched
        self::assertTrue($out['b']);                     // non-string left untouched
    }

    public function test_sanitze_array_returns_false_for_non_arrays(): void
    {
        // The is_array gate returns false (not the input) for non-arrays.
        self::assertFalse(sanitze_array_for_json_encode('notarray'));
        self::assertFalse(sanitze_array_for_json_encode(42));
        self::assertFalse(sanitze_array_for_json_encode(null));
    }
}
