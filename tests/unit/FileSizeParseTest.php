<?php
/**
 * Unit tests for convert_file_size_into_bytes() — simplerisk/includes/functions.php:20581.
 *
 * Parses shorthand sizes like "10M" into bytes. The regression magnet here is
 * the switch in the implementation, which is INTENTIONALLY break-less (C-style
 * fallthrough) so that:
 *     k = x1024       m = x1024 x1024       g = x1024 x1024 x1024
 * Locking these exact multipliers down guards against a well-meaning "fix" that
 * adds `break` statements (which would make every value x1024).
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FileSizeParseTest extends TestCase
{
    public function test_suffix_multipliers_use_powers_of_1024(): void
    {
        // The fallthrough is load-bearing: g must be 1024^3, not 1024.
        self::assertSame(1024, convert_file_size_into_bytes('1K'));
        self::assertSame(1048576, convert_file_size_into_bytes('1M'));       // 1024^2
        self::assertSame(1073741824, convert_file_size_into_bytes('1G'));    // 1024^3
    }

    public function test_multiplies_the_leading_number(): void
    {
        self::assertSame(5120, convert_file_size_into_bytes('5K'));          // 5 * 1024
        self::assertSame(10485760, convert_file_size_into_bytes('10M'));     // 10 * 1024^2
        self::assertSame(10737418240, convert_file_size_into_bytes('10G'));  // 10 * 1024^3
    }

    public function test_suffix_is_case_insensitive(): void
    {
        self::assertSame(1024, convert_file_size_into_bytes('1k'));
        self::assertSame(1048576, convert_file_size_into_bytes('1m'));
        self::assertSame(1073741824, convert_file_size_into_bytes('1g'));
        self::assertSame(10737418240, convert_file_size_into_bytes('10g'));
    }

    public function test_tolerates_surrounding_whitespace(): void
    {
        self::assertSame(2097152, convert_file_size_into_bytes(' 2 m '));    // 2 * 1024^2
        self::assertSame(1024, convert_file_size_into_bytes("\t1K\n"));
    }

    public function test_trailing_letters_after_suffix_are_ignored(): void
    {
        // Regex is ^\s*(\d+)\s*([kmg]) with no end anchor, so "5KB" matches 5 + k.
        self::assertSame(5120, convert_file_size_into_bytes('5KB'));
        self::assertSame(1048576, convert_file_size_into_bytes('1MB'));
    }

    public function test_returns_false_when_no_shorthand_suffix_present(): void
    {
        // A bare number with no k/m/g suffix does not match the regex.
        self::assertFalse(convert_file_size_into_bytes('1024'));
        self::assertFalse(convert_file_size_into_bytes('abc'));
        self::assertFalse(convert_file_size_into_bytes(''));
        self::assertFalse(convert_file_size_into_bytes('100T')); // T is not recognized
    }

    public function test_returns_int_on_success_and_bool_false_on_failure(): void
    {
        self::assertIsInt(convert_file_size_into_bytes('1K'));
        self::assertIsBool(convert_file_size_into_bytes('nope'));
    }
}
