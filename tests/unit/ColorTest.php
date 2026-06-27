<?php
/**
 * Unit tests for color helpers in simplerisk/includes/functions.php:
 *   convert_color_code($color_name)            :5499
 *   hex2rgba($color, $opacity = false)         :5670
 *   get_risk_color_from_levels($risk, $levels) :6200
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ColorTest extends TestCase
{
    public function test_convert_color_code_known_names(): void
    {
        foreach (require __DIR__ . '/../fixtures/colors.php' as [$name, $hex]) {
            self::assertSame($hex, convert_color_code($name), "color '{$name}' should map to {$hex}");
        }
    }

    public function test_convert_color_code_unknown_returns_lowered_input(): void
    {
        // Not in the table -> the (lowercased) input is returned verbatim.
        self::assertSame('notarealcolor', convert_color_code('notarealcolor'));
        self::assertSame('stillnope', convert_color_code('StillNope'));
        // A hex string isn't a known name, so it passes through (lowercased).
        self::assertSame('#ff0000', convert_color_code('#FF0000'));
    }

    public function test_hex2rgba_six_digit_without_hash(): void
    {
        self::assertSame('rgb(255,0,0)', hex2rgba('FF0000'));
        self::assertSame('rgb(0,0,0)', hex2rgba('000000'));
        self::assertSame('rgb(18,52,86)', hex2rgba('123456'));
    }

    public function test_hex2rgba_strips_leading_hash(): void
    {
        self::assertSame('rgb(255,0,0)', hex2rgba('#FF0000'));
    }

    public function test_hex2rgba_three_digit_expands(): void
    {
        self::assertSame('rgb(255,0,0)', hex2rgba('F00'));
        self::assertSame('rgb(17,34,51)', hex2rgba('123'));
    }

    public function test_hex2rgba_opacity_rgba_and_clamped(): void
    {
        self::assertSame('rgba(255,0,0,0.5)', hex2rgba('#FF0000', 0.5));
        // abs(opacity) > 1 is clamped to 1.0, rendered as "1"
        self::assertSame('rgba(255,0,0,1)', hex2rgba('#FF0000', 5));
    }

    public function test_hex2rgba_empty_and_wrong_length(): void
    {
        self::assertSame('rgb(0,0,0)', hex2rgba(''));      // empty -> default
        self::assertSame('rgb(0,0,0)', hex2rgba('abcd'));  // 4 chars -> default
    }

    public function test_get_risk_color_from_levels_picks_correct_band(): void
    {
        // Levels MUST be sorted ascending by value for the function's logic.
        $levels = [
            ['name' => 'Low',    'value' => 0,  'color' => 'green'],
            ['name' => 'Medium', 'value' => 5,  'color' => 'yellow'],
            ['name' => 'High',   'value' => 10, 'color' => 'red'],
        ];

        self::assertSame('green', get_risk_color_from_levels(3, $levels));
        self::assertSame('yellow', get_risk_color_from_levels(5, $levels));
        self::assertSame('yellow', get_risk_color_from_levels(9, $levels));
        self::assertSame('red', get_risk_color_from_levels(12, $levels));
    }

    public function test_get_risk_color_from_levels_below_all_is_white(): void
    {
        $levels = [
            ['name' => 'Low', 'value' => 1, 'color' => 'green'],
            ['name' => 'High', 'value' => 5, 'color' => 'red'],
        ];
        // Nothing matches -> default 'white'
        self::assertSame('white', get_risk_color_from_levels(0, $levels));
    }
}
