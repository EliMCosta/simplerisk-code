<?php
/* Dev-only: seed the Custom Authentication (LDAP) extra's connection settings
 * into the core `settings` table so a freshly-installed dev stack comes up
 * already pointed at the demo OpenLDAP container — no manual Admin →
 * Authentication step needed after every `podman compose down -v` reinstall.
 *
 * Idempotent: INSERT IGNORE only fills ABSENT keys, so any value an admin has
 * set via Admin → Authentication is preserved.
 *
 * The DEFAULT ROLE is intentionally NOT seeded here. Create it yourself in
 * Admin → Role Management and mark it as the default role; provision.php falls
 * back to get_default_role_id() (the role flagged `default`) when
 * LDAP_DEFAULT_ROLE_ID is unset, so that one flag is all the wiring needed.
 *
 * *** DEV-ONLY THROWAWAY CREDENTIALS — never ship this in a production image. ***
 * The bind DN/allow-plaintext here, the bind password "admin" in the gitignored
 * .env (LDAP_BIND_PASSWORD), and the demo users in ldap-seed.ldif
 * all match the isolated compose network only.
 *
 * Invoked by simplerisk-entrypoint.sh in a background loop that retries until
 * the installer has finished. Exit codes drive that retry:
 *   0  = done (connection settings present)
 *   2  = includes/config.php missing (app not installed yet)
 *   3  = database not reachable yet
 *   4  = `settings` table missing (install not complete)
 */

$CFG = '/var/www/simplerisk/includes/config.php';
if (!is_file($CFG)) {
    exit(2); // Not installed yet — the entrypoint loop will retry.
}

// config.php is pure define()s (DB creds, salt, base URL) with no bootstrap side
// effects, so requiring it here just exposes the DB_* constants.
require $CFG;

$dsn = 'mysql:charset=UTF8;dbname=' . DB_DATABASE . ';host=' . DB_HOSTNAME . ';port=' . DB_PORT;
try {
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    exit(3); // DB not up yet — retry.
}

// Wait until the installer has created the settings table.
try {
    $pdo->query('SELECT 1 FROM settings LIMIT 1');
} catch (Throwable $e) {
    exit(4);
}

// Defaults that point the extra at the dev OpenLDAP container and turn on
// auto-provisioning. Notes:
//  - custom_auth='1' enables the extra itself (no license required).
//  - AUTHENTICATION_ADD_NEW_USERS must be '1', NOT 'true': core's
//    is_valid_user() gates auto-provision with `get_setting(...) == 1`, and
//    (int)'true' === 0, so 'true' would silently disable provisioning.
//  - LDAP_USER_DN_TEMPLATE / LDAP_MANAGER_ATTRIBUTE are left unset: the
//    service-account bind+search path (LDAP_BIND_DN set) resolves users, and
//    no manager attribute is mapped in the demo directory.
//  - LDAP_DEFAULT_ROLE_ID is intentionally omitted: provision.php falls back
//    to get_default_role_id() (the role flagged `default` in the UI).
$settings = [
    'custom_auth'                    => '1',
    'LDAP_HOST'                      => 'host.containers.internal',
    'LDAP_PORT'                      => '1389',
    'LDAP_TLS_MODE'                  => 'none',
    'LDAP_ALLOW_PLAINTEXT'           => '1',
    'LDAP_BASE_DN'                   => 'dc=simplerisk,dc=local',
    'LDAP_BIND_DN'                   => 'cn=admin,dc=simplerisk,dc=local',
    // LDAP_BIND_PASSWORD is intentionally NOT seeded: the bind password is read
    // from the LDAP_BIND_PASSWORD env var in .env (see ldapauth_bind_password()),
    // so the secret stays out of the settings table entirely.
    'LDAP_USER_FILTER'               => '(uid=%s)',
    'LDAP_NAME_ATTRIBUTE'            => 'cn',
    'LDAP_EMAIL_ATTRIBUTE'           => 'mail',
    'AUTHENTICATION_ADD_NEW_USERS'   => '1',
    'UPDATE_USER_WITH_DATA_FROM_IDP' => '1', // sync name/email on later logins
];

$insert = $pdo->prepare('INSERT IGNORE INTO settings (`name`, `value`) VALUES (?, ?)');
$added = 0;
foreach ($settings as $name => $value) {
    $insert->execute([$name, $value]);
    $added += $insert->rowCount();
}

if ($added > 0) {
    fwrite(STDERR, sprintf("[seed-ldap] %d connection setting(s) seeded.\n", $added));
}

exit(0);
