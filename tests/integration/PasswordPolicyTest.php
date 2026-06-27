<?php
/**
 * Integration tests for the password-policy validators in simplerisk/includes/functions.php:
 *   check_valid_min_chars($password)   :7538
 *   check_valid_alpha($password)       :7562
 *   check_valid_upper($password)       :7589
 *   check_valid_lower($password)       :7616
 *   check_valid_digits($password)      :7643
 *   check_valid_specials($password)    :7670
 *
 * These gate every password change and are security-critical, yet had zero
 * coverage. Each reads a pass_policy_* setting (via the cached get_setting path,
 * so we clear the cache in setUp) and the *_required ones are only enforced when
 * the setting == 1. Writes go through the per-test transaction (see
 * IntegrationTestCase) and are rolled back, and we set every setting the test
 * depends on — including to '0' for the disabled cases — so the live DB's real
 * policy can't leak into the assertions.
 */
declare(strict_types=1);

final class PasswordPolicyTest extends \IntegrationTestCase
{
    private const SETTINGS = [
        'pass_policy_min_chars',
        'pass_policy_alpha_required',
        'pass_policy_upper_required',
        'pass_policy_lower_required',
        'pass_policy_digits_required',
        'pass_policy_special_required',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // Drop any cached live-DB values so each test sees only what it writes.
        $this->clearSettingCache(...self::SETTINGS);
        // set_alert() appends to $_SESSION['alerts'] on each failure; reset so
        // tests don't accumulate alert state across each other.
        $_SESSION['alerts'] = [];
    }

    /** Write a policy value within the transaction (REPLACE = true upsert, caches). */
    private function setPolicy(string $name, string $value): void
    {
        update_or_insert_setting($name, $value, $this->txdb);
    }

    public function test_min_chars_enforced_against_strlen_threshold(): void
    {
        $this->setPolicy('pass_policy_min_chars', '8');
        self::assertFalse(check_valid_min_chars('short'));         // 5 chars
        self::assertFalse(check_valid_min_chars('seven77'));       // 7 chars
        self::assertTrue(check_valid_min_chars('longenough'));     // 11 chars
        self::assertTrue(check_valid_min_chars('exactly8'));       // 8 chars (>= threshold)
    }

    public function test_min_chars_zero_threshold_passes_anything(): void
    {
        $this->setPolicy('pass_policy_min_chars', '0');
        self::assertTrue(check_valid_min_chars(''));
        self::assertTrue(check_valid_min_chars('x'));
    }

    public function test_alpha_required_rejects_digits_only_when_enabled(): void
    {
        $this->setPolicy('pass_policy_alpha_required', '1');
        self::assertFalse(check_valid_alpha('123456'));
        self::assertTrue(check_valid_alpha('abc123'));
        self::assertTrue(check_valid_alpha('ABC'));
    }

    public function test_alpha_disabled_passes_anything(): void
    {
        $this->setPolicy('pass_policy_alpha_required', '0');
        self::assertTrue(check_valid_alpha('123456'));
    }

    public function test_upper_required_rejects_lowercase_only_when_enabled(): void
    {
        $this->setPolicy('pass_policy_upper_required', '1');
        self::assertFalse(check_valid_upper('alllower'));
        self::assertTrue(check_valid_upper('HasUpper'));
    }

    public function test_lower_required_rejects_uppercase_only_when_enabled(): void
    {
        $this->setPolicy('pass_policy_lower_required', '1');
        self::assertFalse(check_valid_lower('ALLUPPER'));
        self::assertTrue(check_valid_lower('hasLower'));
    }

    public function test_digits_required_rejects_letters_only_when_enabled(): void
    {
        $this->setPolicy('pass_policy_digits_required', '1');
        self::assertFalse(check_valid_digits('NoDigitsHere'));
        self::assertTrue(check_valid_digits('has1digit'));
    }

    public function test_special_required_rejects_alnum_only_when_enabled(): void
    {
        $this->setPolicy('pass_policy_special_required', '1');
        // [^A-Za-z0-9] — so a space or punctuation counts as "special".
        self::assertFalse(check_valid_specials('Abc123'));
        self::assertTrue(check_valid_specials('Abc123!'));
        self::assertTrue(check_valid_specials('Abc 123')); // space is non-alnum
    }

    public function test_required_flags_disabled_pass_anything(): void
    {
        // Every *_required flag returns true when not set to exactly 1.
        $this->setPolicy('pass_policy_upper_required', '0');
        $this->setPolicy('pass_policy_lower_required', '0');
        $this->setPolicy('pass_policy_digits_required', '0');
        $this->setPolicy('pass_policy_special_required', '0');
        self::assertTrue(check_valid_upper('lowercase'));
        self::assertTrue(check_valid_lower('UPPERCASE'));
        self::assertTrue(check_valid_digits('NoDigits'));
        self::assertTrue(check_valid_specials('AlnumOnly123'));
    }

    public function test_a_realistic_strong_password_passes_all_classes(): void
    {
        // A password that satisfies every enabled class passes each check.
        $this->setPolicy('pass_policy_min_chars', '12');
        $this->setPolicy('pass_policy_alpha_required', '1');
        $this->setPolicy('pass_policy_upper_required', '1');
        $this->setPolicy('pass_policy_lower_required', '1');
        $this->setPolicy('pass_policy_digits_required', '1');
        $this->setPolicy('pass_policy_special_required', '1');

        $strong = 'C0rrect-Horse-Battery!'; // long, both cases, digit, special
        self::assertTrue(check_valid_min_chars($strong));
        self::assertTrue(check_valid_alpha($strong));
        self::assertTrue(check_valid_upper($strong));
        self::assertTrue(check_valid_lower($strong));
        self::assertTrue(check_valid_digits($strong));
        self::assertTrue(check_valid_specials($strong));
    }
}
