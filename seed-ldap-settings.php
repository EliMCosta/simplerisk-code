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
//  - LDAP_DEFAULT_ROLE_ID is NOT seeded as a plain INSERT IGNORE here: it is
//    wired to the read-only "LDAP Auditor" role created below, and only when no
//    default role is configured yet (so an admin's explicit choice in
//    Admin → Authentication is never overridden). See the role block below.
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

// ---------------------------------------------------------------------------
// Read-only default role for auto-provisioned LDAP users.
//
// The dev stack ships only the Administrator role (every permission). Without a
// sensible default, provision.php creates directory users with role_id=0 — they
// authenticate but can do nothing. Create a non-admin "LDAP Auditor" role with
// read-only access (the read menus + commenting + viewing exceptions; NO
// submit/modify/close/review/delete) and use it as LDAP_DEFAULT_ROLE_ID when no
// default role is configured yet.
//
// Idempotent: INSERT IGNORE on the UNIQUE role name and the composite
// role_responsibilities PK. Permission ids are looked up by key (not hardcoded),
// so this is stable across installs. Admins can still edit the role from
// Admin → Role Management, or pick a different default in Admin → Authentication.
// ---------------------------------------------------------------------------
$role_name = 'LDAP Auditor';
$pdo->prepare('INSERT IGNORE INTO role (`name`, `admin`) VALUES (?, 0)')
    ->execute([$role_name]);

$roleStmt = $pdo->prepare('SELECT value FROM role WHERE `name` = ?');
$roleStmt->execute([$role_name]);
$role_id = (int) $roleStmt->fetchColumn();

if ($role_id > 0) {
    $read_only = [
        'governance', 'riskmanagement', 'compliance', 'asset', 'assessments',
        'comment_risk_management', 'comment_compliance', 'view_exception',
    ];
    $in = implode(',', array_fill(0, count($read_only), '?'));
    $permStmt = $pdo->prepare("SELECT id FROM permissions WHERE `key` IN ({$in})");
    $permStmt->execute($read_only);
    $grant = $pdo->prepare('INSERT IGNORE INTO role_responsibilities (role_id, permission_id) VALUES (?, ?)');
    $granted = 0;
    foreach ($permStmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
        $grant->execute([$role_id, (int) $pid]);
        $granted += $grant->rowCount();
    }
    if ($granted > 0) {
        fwrite(STDERR, sprintf("[seed-ldap] granted %d read-only permission(s) to '%s'.\n", $granted, $role_name));
    }

    // Wire it as the LDAP default role ONLY when none is configured, so an
    // explicit admin choice is never overridden.
    $cur = $pdo->prepare("SELECT value FROM settings WHERE name = 'LDAP_DEFAULT_ROLE_ID'");
    $cur->execute();
    $curval = $cur->fetchColumn();
    if ($curval === false || $curval === null || $curval === '' || $curval === '0') {
        $pdo->prepare('INSERT INTO settings (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)')
            ->execute(['LDAP_DEFAULT_ROLE_ID', (string) $role_id]);
        fwrite(STDERR, sprintf("[seed-ldap] default role set to '%s' (id %d).\n", $role_name, $role_id));
    }
}

exit(0);
