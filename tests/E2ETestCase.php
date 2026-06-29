<?php
/**
 * Base class for end-to-end regression tests that observe the app over real HTTP.
 *
 * These tests are BLACK-BOX: they curl the running Apache (https://localhost,
 * inside the simplerisk-app container) and assert on status code, content-type
 * and JSON shape. Because they only watch the wire, they survive any internal
 * rewrite (Leaf -> Laravel) and catch exactly the regressions a UI rewrite can
 * introduce — a moved URL, a changed response envelope, a broken auth gate.
 *
 * Authentication is SESSION-based (a real curl login), not an API key: the API
 * Extra is not guaranteed to be enabled on every instance, but a logged-in
 * session is accepted by both the /api/v2/ router (is_session_authenticated)
 * and the web host pages. The same session therefore reaches every surface.
 *
 * A dedicated, idempotent test-admin account is provisioned in setUp (created
 * once, password reset each run, granted every permission via permission_to_user
 * — RBAC on this instance is per-user, not role-derived). This is the only
 * persistent residue and is clearly named; it can be removed manually anytime.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class E2ETestCase extends TestCase
{
    /** Dedicated test account. Strong, fixed password; test-only. */
    protected const TEST_USER = 'e2e_regression_admin';
    protected const TEST_PASS = 'E2E-Regression-Suite-2026!xQ';

    /** @var string cookie jar path holding the authenticated session cookie */
    protected string $cookieJar = '';

    /**
     * Named sessions beyond the admin session (e.g. 'restricted' for
     * permission-denied tests). Each maps to ['jar'=>path, 'csrf'=>token].
     * The admin session reuses $cookieJar and is NOT listed here.
     *
     * @var array<string, array{jar:string, csrf:string}>
     */
    protected array $extraSessions = [];

    protected function setUp(): void
    {
        if (defined('SIMPLERISK_TEST_NO_DB')) {
            self::markTestSkipped('No database available (SIMPLERISK_TEST_NO_DB set).');
        }
        // Provision the test admin (idempotent) before logging in.
        $this->ensureTestAdminUser();

        $this->cookieJar = tempnam(sys_get_temp_dir(), 'e2eck');

        if (!$this->sessionLogin()) {
            // Most likely cause: not running inside the container, so curl
            // cannot reach https://localhost. Skip rather than fail.
            @unlink($this->cookieJar);
            self::markTestSkipped('Could not establish an authenticated e2e session (is the app reachable inside the container?).');
        }
    }

    protected function tearDown(): void
    {
        if ($this->cookieJar !== '' && is_file($this->cookieJar)) {
            @unlink($this->cookieJar);
        }
        // Unlink any extra (non-admin) session cookie jars created during the test.
        foreach ($this->extraSessions as $session) {
            if (is_file($session['jar'])) {
                @unlink($session['jar']);
            }
        }
        $this->extraSessions = [];
    }

    /**
     * Idempotently ensure the dedicated test admin exists, has the fixed
     * password, and is granted every permission. Writes persist (e2e is
     * cross-process; there is no rollback transaction to hide behind).
     */
    protected function ensureTestAdminUser(): void
    {
        $db = db_open();

        $stmt = $db->prepare("SELECT value FROM user WHERE username = ?");
        $stmt->execute([self::TEST_USER]);
        $uid = (int) $stmt->fetchColumn();

        $hash = password_hash(self::TEST_PASS, PASSWORD_BCRYPT);

        if (!$uid) {
            $role = (int) $db->query("SELECT value FROM role ORDER BY value LIMIT 1")->fetchColumn();
            $ins = $db->prepare(
                "INSERT INTO user (username, name, email, password, role_id, admin, type, enabled, change_password)
                 VALUES (?, ?, ?, ?, ?, 1, 'simplerisk', 1, 0)"
            );
            $ins->execute([
                self::TEST_USER,
                'E2E Regression Suite',
                'e2e-regression@example.test',
                $hash,
                $role,
            ]);
            $uid = (int) $db->lastInsertId();
        } else {
            // Reset password + flags each run so the account is always usable.
            $db->prepare("UPDATE user SET password = ?, enabled = 1, admin = 1, change_password = 0 WHERE value = ?")
                ->execute([$hash, $uid]);
        }

        // Grant every permission. On this instance check_permission() reads
        // $_SESSION[<perm>] flags populated from permission_to_user (direct
        // per-user grants), so admin=1 alone does NOT confer governance etc.
        $del = $db->prepare("DELETE FROM permission_to_user WHERE user_id = ?");
        $del->execute([$uid]);
        $db->exec("INSERT INTO permission_to_user (permission_id, user_id) SELECT id, {$uid} FROM permissions");
    }

    /**
     * Perform the CSRF-gated login flow over curl, populating the cookie jar.
     * Returns true when the resulting session is authenticated (verified via
     * /api/v2/whoami, which answers 200 only for an authenticated session).
     */
    protected function sessionLogin(): bool
    {
        return $this->loginSession(self::TEST_USER, self::TEST_PASS, 'admin');
    }

    /**
     * Log $user/$pass into a named session, returning true once authenticated.
     * The 'admin' session reuses $this->cookieJar (created in setUp); any other
     * $sessionName allocates a fresh cookie jar registered in $extraSessions.
     * This is what lets the permission-denied tests drive a second, restricted
     * user with its own session cookie AND its own csrf token (csrfTokenFor).
     */
    protected function loginSession(string $user, string $pass, string $sessionName): bool
    {
        if ($sessionName === 'admin') {
            $jar = $this->cookieJar;
        } else {
            $jar = tempnam(sys_get_temp_dir(), 'e2e' . $sessionName);
            $this->extraSessions[$sessionName] = ['jar' => $jar, 'csrf' => ''];
        }

        // 1. GET the login page to seed the session cookie + login_csrf_token.
        //    cookie=>true is REQUIRED so curl persists the Set-Cookie into the jar;
        //    without it the POST below is a different (anonymous) session and the
        //    login_csrf_token check fails.
        [, , $html, $err] = $this->request('GET', '/index.php', ['cookie' => true, 'jar' => $jar]);
        if ($err !== '' || $html === '') {
            return false;
        }
        $csrf = $this->extractLoginCsrf($html);
        if ($csrf === '') {
            return false;
        }

        // 2. POST credentials using the same cookie jar so the session matches.
        $this->request('POST', '/index.php', [
            'cookie' => true,
            'jar'    => $jar,
            'post'   => http_build_query([
                'submit'     => 1,
                'user'       => $user,
                'pass'       => $pass,
                'csrf_token' => $csrf,
            ]),
        ]);

        // 3. Confirm the session is authenticated (cookie=>true sends the session).
        [$code] = $this->request('GET', '/api/v2/whoami', ['cookie' => true, 'jar' => $jar]);
        return $code === 200;
    }

    private function extractLoginCsrf(string $html): string
    {
        // The login form renders: <input type="hidden" name="csrf_token" value="..">
        // Accept either attribute order, fall back to any 64-hex value.
        if (preg_match('/name=["\']csrf_token["\'][^>]*value=["\']([0-9a-f]+)["\']/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/value=["\']([0-9a-f]{64})["\'][^>]*name=["\']csrf_token["\']/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/value=["\']([0-9a-f]{64})["\']/i', $html, $m)) {
            return $m[1];
        }
        return '';
    }

    /**
     * Perform an HTTP request against the in-container Apache.
     * Returns [code, contentType, body, curlError]. Does NOT follow redirects,
     * so a 302-to-login can never masquerade as a 200.
     *
     * When 'cookie' is true, the authenticated session cookie (in $this->cookieJar)
     * is sent AND any Set-Cookie is persisted back to the jar. Pass ['jar'=>path]
     * to override the cookie jar (used by the *As session helpers for a second,
     * named session); when omitted the admin $this->cookieJar is used.
     */
    protected function request(string $method, string $path, array $opts = []): array
    {
        $jar = $opts['jar'] ?? $this->cookieJar;
        $ch = curl_init('https://localhost' . $path);
        $curlopt = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // self-signed dev cert
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST  => $method,
        ];
        if (!empty($opts['cookie'])) {
            $curlopt[CURLOPT_COOKIEFILE] = $jar; // send
            $curlopt[CURLOPT_COOKIEJAR]  = $jar; // persist
        }
        if (isset($opts['post'])) {
            // CURLOPT_POST forces the POST verb, so only set it for actual POSTs;
            // for PATCH/PUT-with-body, CUSTOMREQUEST (set above) + POSTFIELDS sends
            // the body with the right verb.
            if ($method === 'POST') {
                $curlopt[CURLOPT_POST] = true;
            }
            $curlopt[CURLOPT_POSTFIELDS] = $opts['post'];
        }
        if (!empty($opts['headers'])) {
            $curlopt[CURLOPT_HTTPHEADER] = $opts['headers'];
        }
        curl_setopt_array($ch, $curlopt);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $err  = curl_error($ch);
        curl_close($ch);

        return [$code, $type, (string) $body, $err];
    }

    /**
     * Authenticated GET carrying the session cookie. Pass ['referer'=>'<url>'] to
     * pin a Referer (some GET handlers, e.g. overviewForm, render a tab template
     * that warns on a missing HTTP_REFERER); by default no Referer is sent.
     */
    protected function authedGet(string $path, array $opts = []): array
    {
        $requestOpts = ['cookie' => true];
        if (isset($opts['referer'])) {
            $requestOpts['headers'] = ['Referer: ' . $opts['referer']];
        }
        return $this->request('GET', $path, $requestOpts);
    }

    /** Unauthenticated GET (no cookie) — for auth-gate assertions. */
    protected function unauthedGet(string $path): array
    {
        return $this->request('GET', $path);
    }

    /** @var string cached csrf-magic token for the session (fetched lazily, reused across POSTs). */
    protected string $csrfTokenCache = '';

    /**
     * The session's csrf-magic token, fetched once from the submit-risk page and
     * cached for the test. Every authenticated /api/v2 request runs
     * is_session_authenticated() -> include_csrf_magic() -> csrf_init() ->
     * csrf_check() (csrf-magic.php:432), so every Slim POST must carry a valid
     * __csrf_magic value. The token is session-derived (sid:csrf_hash(session_id))
     * and injected into any rendered form, so it is valid for every route hit
     * with the same cookie jar.
     */
    protected function csrfToken(): string
    {
        if ($this->csrfTokenCache !== '') {
            return $this->csrfTokenCache;
        }
        [$code, , $html] = $this->authedGet('/management/index.php');
        if ($code === 200
            && (preg_match('/name=["\']__csrf_magic["\'][^>]*value=["\']([^"\']+)["\']/i', $html, $m)
                || preg_match('/value=["\']([^"\']+)["\'][^>]*name=["\']__csrf_magic["\']/i', $html, $m))
        ) {
            $this->csrfTokenCache = $m[1];
            return $this->csrfTokenCache;
        }
        // Fail fast with context: otherwise every authedPost sends an empty
        // token and fails downstream with no hint that extraction was the cause.
        self::fail(
            'Could not extract the __csrf_magic token from /management/index.php '
            . "(HTTP {$code}, " . strlen($html) . ' bytes) — the authenticated session may be degraded.'
        );
    }

    /**
     * Authenticated POST: session cookie + the csrf-magic token + a Referer
     * header. The Referer keeps getTabHtml()-style handlers from warning on a
     * missing server var, which under display_errors=on would prepend text and
     * corrupt the JSON. Pass ['referer' => '<url>'] to override the default
     * Referer, or ['no_referer' => true] to omit it.
     */
    protected function authedPost(string $path, array $post = [], array $opts = []): array
    {
        $post['__csrf_magic'] = $this->csrfToken();
        $requestOpts = ['cookie' => true, 'post' => http_build_query($post)];
        if (!empty($opts['no_referer'])) {
            // omit Referer entirely
        } else {
            $requestOpts['headers'] = ['Referer: ' . ($opts['referer'] ?? 'https://localhost/')];
        }
        return $this->request('POST', $path, $requestOpts);
    }

    /**
     * Authenticated DELETE. csrf_check() only validates POST (csrf-magic.php:166),
     * so no token is needed for DELETE routes — only the session cookie.
     */
    protected function authedDelete(string $path): array
    {
        return $this->request('DELETE', $path, ['cookie' => true]);
    }

    // ------------------------------------------------------------------
    // Named-session variants (for the restricted user / second session)
    // ------------------------------------------------------------------

    /** The cookie jar for a named session ('admin' resolves to $this->cookieJar). */
    protected function jarFor(string $sessionName): string
    {
        if ($sessionName === 'admin') {
            return $this->cookieJar;
        }
        return $this->extraSessions[$sessionName]['jar'] ?? $this->cookieJar;
    }

    /** request() bound to a named session's cookie jar. */
    protected function requestAs(string $sessionName, string $method, string $path, array $opts = []): array
    {
        $opts['jar'] = $this->jarFor($sessionName);
        return $this->request($method, $path, $opts);
    }

    /**
     * The session's csrf-magic token for a NAMED session, fetched+cached per
     * session. 'admin' delegates to the existing csrfToken() cache; other
     * sessions keep their own cache in $extraSessions[<name>]['csrf'] so the two
     * tokens (admin vs restricted) never collide.
     */
    protected function csrfTokenFor(string $sessionName): string
    {
        if ($sessionName === 'admin') {
            return $this->csrfToken();
        }
        if (!isset($this->extraSessions[$sessionName])) {
            self::fail("No session named '{$sessionName}' — call loginSession('{$sessionName}') first.");
        }
        if ($this->extraSessions[$sessionName]['csrf'] !== '') {
            return $this->extraSessions[$sessionName]['csrf'];
        }
        [, , $html] = $this->requestAs($sessionName, 'GET', '/management/index.php', ['cookie' => true]);
        if (preg_match('/name=["\']__csrf_magic["\'][^>]*value=["\']([^"\']+)["\']/i', $html, $m)
            || preg_match('/value=["\']([^"\']+)["\'][^>]*name=["\']__csrf_magic["\']/i', $html, $m)
        ) {
            $this->extraSessions[$sessionName]['csrf'] = $m[1];
            return $m[1];
        }
        self::fail("Could not extract the __csrf_magic token for session '{$sessionName}'.");
    }

    /** Authenticated GET from a named session (see authedGet for opts). */
    protected function authedGetAs(string $sessionName, string $path, array $opts = []): array
    {
        $requestOpts = ['cookie' => true];
        if (isset($opts['referer'])) {
            $requestOpts['headers'] = ['Referer: ' . $opts['referer']];
        }
        return $this->requestAs($sessionName, 'GET', $path, $requestOpts);
    }

    /** Authenticated POST from a named session (cookie + that session's csrf token + Referer). */
    protected function authedPostAs(string $sessionName, string $path, array $post = [], array $opts = []): array
    {
        $post['__csrf_magic'] = $this->csrfTokenFor($sessionName);
        $requestOpts = ['cookie' => true, 'post' => http_build_query($post)];
        if (!empty($opts['no_referer'])) {
            // omit Referer entirely
        } else {
            $requestOpts['headers'] = ['Referer: ' . ($opts['referer'] ?? 'https://localhost/')];
        }
        return $this->requestAs($sessionName, 'POST', $path, $requestOpts);
    }

    /** Decode a JSON body or fail the test with context. */
    protected function decodeJson(string $body, string $context = ''): array
    {
        self::assertJson($body, "expected JSON body ({$context})");
        return (array) json_decode($body, true);
    }

    /**
     * Top-level "shape" of a decoded JSON response:
     *  - 'object:<k1>,<k2>'   an object with exactly those keys
     *  - 'list'               a bare JSON array
     */
    protected function assertEnvelope(array $decoded, string $expected, string $context = ''): void
    {
        if ($expected === 'list') {
            self::assertIsArray($decoded, "expected a JSON array ({$context})");
            self::assertTrue(array_is_list($decoded) || $decoded === [],
                "expected a list envelope ({$context})");
            return;
        }
        if (str_starts_with($expected, 'object:')) {
            $keys = explode(',', substr($expected, 7));
            self::assertIsArray($decoded, "expected a JSON object ({$context})");
            self::assertSame(
                $keys,
                array_keys($decoded),
                "top-level envelope keys drifted ({$context}) — the EasyUI treegrid reads these by name"
            );
        }
    }
}
