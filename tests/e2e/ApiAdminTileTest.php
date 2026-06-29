<?php
/**
 * E2E regression for the API Extra admin tile (admin/api.php).
 *
 * Exercises the real POST dispatch path. Core calls update_api_config()
 * (-> api_admin_save()) from admin/api.php's POST block, which runs BEFORE
 * render_header_and_sidebar() starts the SimpleRisk session. The nonce that
 * gates every action lives in $_SESSION, which is only populated from the
 * session store after session_start() — so api_admin_save() must open the
 * session itself first. Without that, $_SESSION is the empty auto-array, the
 * nonce check always fails, and every POST (toggle_server, toggle_user_keys,
 * create, enable/disable, revoke) silently refresh()es without persisting.
 * That is the regression locked here: the toggles appeared to do nothing.
 *
 * Each toggle is a Post/Redirect/Get (302 -> GET), so we assert the redirect,
 * that the settings row actually changed in the DB (the source of truth), and
 * that the rendered button label reflects the new state. Two successive toggles
 * also prove the nonce survives rotation across POSTs.
 *
 * Runs against the in-container Apache over HTTPS, authenticated as the shared
 * e2e test admin. The two global settings are snapshotted in setUp and restored
 * in tearDown so the suite leaves no residue.
 */
declare(strict_types=1);

final class ApiAdminTileTest extends E2ETestCase
{
    /** setting name => [enable-button-label, disable-button-label] */
    private const TOGGLES = [
        'api_server_enabled'  => ['Enable server', 'Disable server'],
        'api_allow_user_keys' => ['Allow self-service', 'Disable self-service'],
    ];

    /** Settings snapshotted in setUp and restored in tearDown (null = absent row). */
    private const SNAPSHOT_SETTINGS = ['api', 'api_server_enabled', 'api_allow_user_keys'];

    /** @var array<string,?string> snapshot: setting name => value (null = absent) */
    private array $snapshot = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::SNAPSHOT_SETTINGS as $name) {
            $this->snapshot[$name] = $this->readSetting($name);
        }
        // The toggle tests hit the activated admin tile (display_api()); without
        // the `api` setting the page only renders the Activate button and no nonce.
        $this->writeSetting('api', '1');
    }

    protected function tearDown(): void
    {
        foreach ($this->snapshot as $name => $value) {
            $this->writeSetting($name, $value);
        }
        parent::tearDown();
    }

    public function test_toggle_server_persists(): void
    {
        $this->assertToggleRoundTrips('toggle_server', 'api_server_enabled', ...self::TOGGLES['api_server_enabled']);
    }

    public function test_toggle_user_keys_persists(): void
    {
        $this->assertToggleRoundTrips('toggle_user_keys', 'api_allow_user_keys', ...self::TOGGLES['api_allow_user_keys']);
    }

    /**
     * Two successive toggles: each must be a PRG 302, must flip the DB row, and
     * the rendered button label must agree with the DB value. Starting state is
     * read fresh, so the test is deterministic regardless of prior value.
     */
    private function assertToggleRoundTrips(string $action, string $setting, string $enableLabel, string $disableLabel): void
    {
        // --- First toggle ---
        $nonce = $this->extractApiNonce($this->authedGet('/admin/api.php')[2]);
        self::assertNotSame('', $nonce, 'expected an api_nonce in the rendered admin tile');

        [$code] = $this->request('POST', '/admin/api.php', [
            'cookie' => true,
            'post'   => http_build_query(['action' => $action, 'api_nonce' => $nonce, 'submit' => '1']),
        ]);
        self::assertSame(302, $code, "expected a PRG 302 redirect after {$action}");

        $page1   = $this->authedGet('/admin/api.php')[2];
        $value1  = $this->readSetting($setting);
        self::assertContains($value1, ['0', '1'], "{$setting} should now be a real persisted row, not absent");
        $this->assertButtonLabelMatches($page1, $value1, $enableLabel, $disableLabel);

        // --- Second toggle (flips back; also proves nonce rotation survives) ---
        $nonce = $this->extractApiNonce($page1);
        [$code] = $this->request('POST', '/admin/api.php', [
            'cookie' => true,
            'post'   => http_build_query(['action' => $action, 'api_nonce' => $nonce, 'submit' => '1']),
        ]);
        self::assertSame(302, $code, "expected a PRG 302 redirect after the second {$action}");

        $page2  = $this->authedGet('/admin/api.php')[2];
        $value2 = $this->readSetting($setting);
        self::assertNotSame($value1, $value2, "the second {$action} must flip the value");
        $this->assertButtonLabelMatches($page2, $value2, $enableLabel, $disableLabel);
    }

    /** The enable button shows when OFF ('0'/absent); the disable button when ON ('1'). */
    private function assertButtonLabelMatches(string $html, ?string $value, string $enableLabel, string $disableLabel): void
    {
        $expected = $value === '1' ? $disableLabel : $enableLabel;
        // The Disable buttons carry an onclick="confirm(...)" so the label is
        // wrapped in whitespace; tolerate it rather than matching the raw tag.
        $pattern = '#>\s*' . preg_quote($expected, '#') . '\s*</button>#i';
        self::assertSame(
            1,
            preg_match($pattern, $html),
            "expected the '{$expected}' submit button for value " . var_export($value, true)
        );
    }

    private function extractApiNonce(string $html): string
    {
        // Renders: <input type="hidden" name="api_nonce" value="32 hex chars">
        if (preg_match('/name=["\']api_nonce["\'][^>]*value=["\']([0-9a-f]+)["\']/i', $html, $m)) {
            return $m[1];
        }
        return '';
    }
}
