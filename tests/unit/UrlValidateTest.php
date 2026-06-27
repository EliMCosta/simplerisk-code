<?php
/**
 * Unit test for check_if_valid_url() — simplerisk/includes/functions.php:26227.
 *
 * Thin wrapper over filter_var(FILTER_VALIDATE_URL). Returns the URL string
 * (truthy) when valid and bool false when not. It gates outbound fetches and
 * stored links, so the truthy/falsy contract is what must not regress.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UrlValidateTest extends TestCase
{
    public function test_accepts_well_formed_urls_and_returns_them(): void
    {
        // On success the URL string is returned (truthy), not a bare true.
        self::assertSame('https://example.com', check_if_valid_url('https://example.com'));
        self::assertSame('http://example.com/path?q=1#frag', check_if_valid_url('http://example.com/path?q=1#frag'));
    }

    public function test_truthy_on_valid_so_callers_can_use_it_inline(): void
    {
        self::assertTrue(check_if_valid_url('https://example.com') ? true : false);
    }

    public function test_rejects_strings_without_a_scheme(): void
    {
        // A bare host (no scheme) is NOT a valid URL per filter_var.
        self::assertFalse(check_if_valid_url('example.com'));
        self::assertFalse(check_if_valid_url('not a url at all'));
        self::assertFalse(check_if_valid_url(''));
    }
}
