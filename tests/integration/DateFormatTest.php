<?php
/**
 * Integration tests for the core date helpers in simplerisk/includes/functions.php:
 *   format_date($date, $default = "")                        :6958
 *   format_datetime($date, $default = "", $timeformat="H:i:s") :6969
 *   trim_date($date, $default = "")                          :6983
 *
 * These format every date shown in the UI and must never render a zero-date
 * placeholder ("0000-00-00") or the Unix epoch to users. The two behaviors most
 * worth pinning: (1) zero/empty dates fall back to $default, and (2) a present
 * but unparseable date returns "" — NOT $default — because strtotime() failed.
 * We set default_date_format to a known value per test so output is deterministic.
 */
declare(strict_types=1);

final class DateFormatTest extends \IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // YYYY-MM-DD -> get_default_date_format() returns "Y-m-d"; drop any
        // cached live value first so the test sees only this write.
        $this->clearSettingCache('default_date_format');
        update_or_insert_setting('default_date_format', 'YYYY-MM-DD', $this->txdb);
    }

    public function test_format_date_reformats_valid_dates(): void
    {
        self::assertSame('2026-06-26', format_date('2026-06-26'));
        self::assertSame('2026-06-26', format_date('2026-06-26 10:00:00'));
    }

    public function test_format_date_zero_and_empty_fall_back_to_default(): void
    {
        // Default default is "". Must NOT render "0000-00-00" or "1970-01-01".
        self::assertSame('', format_date('0000-00-00'));
        self::assertSame('', format_date('0000-00-00 00:00:00'));
        self::assertSame('', format_date(''));
        self::assertSame('', format_date(null));
    }

    public function test_format_date_uses_custom_default_for_zero_dates(): void
    {
        self::assertSame('N/A', format_date('0000-00-00', 'N/A'));
        // A valid date is still formatted — the default does not apply.
        self::assertSame('2026-06-26', format_date('2026-06-26', 'N/A'));
    }

    public function test_format_date_unparseable_present_date_returns_empty_not_default(): void
    {
        // strtotime('garbage') is false -> returns "" rather than the $default.
        // This is the subtle one: present-but-bad differs from zero/empty.
        self::assertSame('', format_date('garbage', 'N/A'));
    }

    public function test_format_datetime_appends_time_component(): void
    {
        self::assertSame('2026-06-26 10:00:00', format_datetime('2026-06-26 10:00:00'));
        self::assertSame('2026-06-26 10:00', format_datetime('2026-06-26 10:00:00', '', 'H:i'));
    }

    public function test_format_datetime_zero_and_empty_fall_back_to_default(): void
    {
        self::assertSame('', format_datetime('0000-00-00 00:00:00'));
        self::assertSame('—', format_datetime('', '—'));
        self::assertSame('', format_datetime(null));
    }

    public function test_trim_date_passes_through_real_dates(): void
    {
        self::assertSame('2026-06-26', trim_date('2026-06-26'));
        self::assertSame('2026-06-26', trim_date('2026-06-26', 'N/A')); // default unused
    }

    public function test_trim_date_blanks_any_zero_prefixed_date(): void
    {
        // stripos "0000-00" — matches both the date and datetime forms.
        self::assertSame('', trim_date('0000-00-00'));
        self::assertSame('', trim_date('0000-00-00 12:00:00'));
        self::assertSame('none', trim_date('0000-00-00', 'none'));
        self::assertSame('', trim_date(''));
    }
}
