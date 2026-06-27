<?php
/**
 * Unit tests for safe_round() — simplerisk/includes/functions.php:31392.
 *   safe_round($value, $precision = 2)
 *   returns is_numeric($value) ? round((float)$value, $precision) : null
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SafeRoundTest extends TestCase
{
    private const DELTA = 0.0001;

    public function test_rounds_numeric_values_to_precision(): void
    {
        self::assertEqualsWithDelta(1.23, safe_round(1.234, 2), self::DELTA);
        self::assertEqualsWithDelta(1.24, safe_round(1.236, 2), self::DELTA); // rounds up
        self::assertEqualsWithDelta(3.142, safe_round(3.14159, 3), self::DELTA);
        self::assertEqualsWithDelta(2.0, safe_round(1.5, 0), self::DELTA);
    }

    public function test_default_precision_is_two(): void
    {
        self::assertEqualsWithDelta(1.24, safe_round(1.236), self::DELTA);
    }

    public function test_numeric_string_is_coerced(): void
    {
        self::assertEqualsWithDelta(1.5, safe_round('1.49', 1), self::DELTA);
        self::assertEqualsWithDelta(5.0, safe_round('5'), self::DELTA);
    }

    public function test_non_numeric_returns_null(): void
    {
        self::assertNull(safe_round('not-a-number'));
        self::assertNull(safe_round(null));
        self::assertNull(safe_round(''));
        self::assertNull(safe_round([]));
    }
}
