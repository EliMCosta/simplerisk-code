<?php
/**
 * SAPI compatibility shim for Nginx + PHP-FPM.
 *
 * SimpleRisk core and the bundled csrf-magic library call the Apache-only request-
 * header functions unguarded:
 *   - includes/functions.php csrf_startup()  -> apache_request_headers()  (every request)
 *   - includes/services.php                  -> getallheaders()
 *   - vendor/simplerisk/csrf-magic csrf_check() -> apache_request_headers() (every POST)
 *
 * Under the fpm-fcgi SAPI BOTH apache_request_headers() and getallheaders() are
 * UNDEFINED (they are registered only by the apache2handler SAPI; PHP Bug #62596).
 * Without this shim every CSRF-gated request fatal-errors with
 * "Call to undefined function apache_request_headers()".
 *
 * Loaded via auto_prepend_file (see simplerisk-sapi.ini), so it runs before any
 * SimpleRisk/csrf-magic code. auto_prepend_file applies to EVERY SAPI, including CLI
 * (cron, the test suite) — it is NOT ignored under CLI. This is web-only in EFFECT,
 * not in loading: under the cli SAPI $_SERVER has no HTTP_* keys, so the polyfilled
 * functions are defined (they don't exist under cli) but return an empty array — a
 * harmless no-op that never affects tests.
 *
 * The function_exists() guards make this a true no-op on any SAPI that already
 * provides the functions (e.g. if the app is ever run under mod_php again). Zero
 * edits to core or vendor.
 *
 * Nginx catch-all `server_name _` passes SERVER_NAME literally as `_`. SimpleRisk
 * builds simplerisk_base_url from SERVER_NAME + SERVER_PORT, so AJAX BASE_URL
 * becomes `https://_` and cross-origin API calls fail. Mirror the client Host
 * header (see simplerisk.conf fastcgi_param overrides).
 */

if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
    if (!isset($_SERVER['SERVER_NAME']) || $_SERVER['SERVER_NAME'] === '_') {
        $_SERVER['SERVER_NAME'] = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);
    }
    if (preg_match('/:(\d+)$/', $_SERVER['HTTP_HOST'], $host_port)) {
        $_SERVER['SERVER_PORT'] = $host_port[1];
    }
}

if (!function_exists('simplerisk_getallheaders_polyfill')) {
    function simplerisk_getallheaders_polyfill() {
        $headers = array();
        // FastCGI exposes request headers as HTTP_<NAME> server vars.
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                // HTTP_USER_AGENT -> User-Agent
                $header = str_replace(
                    ' ',
                    '-',
                    ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                );
                $headers[$header] = $value;
            }
        }
        // Content-Type / Content-Length are exposed unprefixed under FastCGI.
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        return $headers;
    }
}

if (!function_exists('apache_request_headers')) {
    function apache_request_headers() {
        return simplerisk_getallheaders_polyfill();
    }
}

if (!function_exists('getallheaders')) {
    function getallheaders() {
        return simplerisk_getallheaders_polyfill();
    }
}
