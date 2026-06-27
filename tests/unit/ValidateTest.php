<?php
/**
 * Unit tests for validation helpers in simplerisk/includes/functions.php:
 *   validate_date($date, $format = 'Y-m-d H:i:s') :17278
 *   validate_language($lang, $allowed_languages)  :16299
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidateTest extends TestCase
{
    public function test_validate_date_accepts_matching_format(): void
    {
        self::assertTrue(validate_date('2026-06-26 10:00:00'));
        self::assertTrue(validate_date('2026-06-26', 'Y-m-d'));
    }

    public function test_validate_date_rejects_non_matching_or_invalid(): void
    {
        // Default format expects a time component
        self::assertFalse(validate_date('2026-06-26'));
        self::assertFalse(validate_date('not-a-date'));
        // Plausible shape but impossible values
        self::assertFalse(validate_date('2026-13-45 99:99:99'));
    }

    public function test_validate_language_accepts_whitelisted(): void
    {
        $allowed = ['en', 'es', 'zh-CN'];
        self::assertSame('en', validate_language('en', $allowed));
        self::assertSame('zh-CN', validate_language('zh-CN', $allowed));
    }

    public function test_validate_language_rejects_non_whitelisted_and_empty(): void
    {
        $allowed = ['en'];
        self::assertNull(validate_language('de', $allowed)); // not whitelisted
        self::assertNull(validate_language('', $allowed));   // empty
        self::assertNull(validate_language(null, $allowed));
    }

    public function test_validate_language_strips_path_traversal(): void
    {
        $allowed = ['en'];
        // basename('../etc/passwd') = 'passwd' -> not whitelisted -> null
        self::assertNull(validate_language('../etc/passwd', $allowed));
        // basename('en/../../etc') = 'etc' -> not whitelisted -> null
        self::assertNull(validate_language('en/../../etc', $allowed));
        // Slashes/backticks stripped, then checked: 'en' survives cleaning and is whitelisted
        self::assertSame('en', validate_language('en', $allowed));
    }
}
