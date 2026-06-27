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
        // 1. GET the login page to seed the session cookie + login_csrf_token.
        //    cookie=>true is REQUIRED so curl persists the Set-Cookie into the jar;
        //    without it the POST below is a different (anonymous) session and the
        //    login_csrf_token check fails.
        [, , $html, $err] = $this->request('GET', '/index.php', ['cookie' => true]);
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
            'post'   => http_build_query([
                'submit'     => 1,
                'user'       => self::TEST_USER,
                'pass'       => self::TEST_PASS,
                'csrf_token' => $csrf,
            ]),
        ]);

        // 3. Confirm the session is authenticated (cookie=>true sends the session).
        [$code] = $this->request('GET', '/api/v2/whoami', ['cookie' => true]);
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
     * is sent AND any Set-Cookie is persisted back to the jar.
     */
    protected function request(string $method, string $path, array $opts = []): array
    {
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
            $curlopt[CURLOPT_COOKIEFILE] = $this->cookieJar; // send
            $curlopt[CURLOPT_COOKIEJAR]  = $this->cookieJar; // persist
        }
        if (isset($opts['post'])) {
            $curlopt[CURLOPT_POST]       = true;
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

    /** Authenticated GET carrying the session cookie. */
    protected function authedGet(string $path): array
    {
        return $this->request('GET', $path, ['cookie' => true]);
    }

    /** Unauthenticated GET (no cookie) — for auth-gate assertions. */
    protected function unauthedGet(string $path): array
    {
        return $this->request('GET', $path);
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
