# SimpleRisk Install & Setup

> This is a **dev/test** walkthrough — the two-stack, host-gateway, self-signed,
> auto-provisioning layout described below is built for fast local iteration, not
> for exposing to the internet. See [Production deployment](#production-deployment)
> before pointing this at anything beyond your own machine.

Local dev/test stack split into **two** Podman Compose projects:

- `compose.support.yml` — MariaDB + the demo OpenLDAP (+ optional SMTP).
- `compose.app.yml` — the SimpleRisk app (built from `Dockerfile`).

The two stacks share **no container network**. The support services publish
their ports on `0.0.0.0`; the app reaches them at `host.containers.internal`
via Podman's host-gateway (required under rootless Podman, where a container
cannot reach a host port bound to `127.0.0.1`).

The app **auto-provisions its database** (`DB_SETUP=automatic`): on first boot
the entrypoint creates the `simplerisk` DB + `simplerisk`@`%` user + core schema
using `DB_SETUP_PASS` (which must equal `MARIADB_ROOT_PASSWORD`). There is no
web installer step.

## Prerequisites

- Podman 5.x+ (rootless), podman-compose 1.5.x
- A `.env` (copy from `.env.example`) — the **app-stack** secrets. Set at least
  `SIMPLERISK_DB_PASSWORD`, `SIMPLERISK_ENCRYPTION_KEY`, `LDAP_BIND_PASSWORD`,
  and `DB_SETUP_PASS`.
- A `.env.support` (copy from `.env.support.example`) — the **support-stack**
  vars (MariaDB/OpenLDAP/SMTP). Set at least `MARIADB_ROOT_PASSWORD` and
  `LDAP_ADMIN_PASSWORD`.
- `DB_SETUP_PASS` (in `.env`) **must equal** `MARIADB_ROOT_PASSWORD` (in
  `.env.support`): the app bootstraps the database as root. The two stacks have
  separate env files, so this match is the operator's responsibility.
- `./certs/` exists (the TLS bind target) — run `mkdir -p certs` before the first
  `up`. It starts empty; the entrypoint generates a self-signed cert there on first
  boot and reuses it thereafter. Drop in your own `ca/ca.crt` +
  `simplerisk/simplerisk.{crt,key}` to use a real certificate.

## Clean previous install (optional)

Wipe both stacks' volumes so you start from scratch:

```bash
podman compose -f compose.app.yml down
podman compose -f compose.support.yml down -v   # wipes db + ldap volumes
```

`down -v` on the support stack removes `simplerisk-db`, `simplerisk-ldap`, and
`simplerisk-ldap-config`. The app stack uses host bind mounts (`./extras`, `./certs`),
not named volumes — `down` leaves their contents on the host.

## Start the stack

Bring the **support stack up first**, then wait for `simplerisk-db` to report
`(healthy)`:

```bash
podman compose -f compose.support.yml up -d
podman ps --format 'table {{.Names}}\t{{.Status}}'
# expect: simplerisk-db (healthy), simplerisk-ldap Up
```

Then build + start the **app stack**:

```bash
podman compose -f compose.app.yml up -d --build
```

## Watch the auto-provision (no web installer)

The entrypoint provisions the DB and writes `includes/config.php` from the
`SIMPLERISK_DB_*` env vars. Watch for success:

```bash
podman logs simplerisk-app 2>&1 | grep -E "Setup has been applied|seed-ldap|already provisioned"
# First boot:  "Setup has been applied successfully!" then "[seed-ldap] N connection setting(s) seeded."
# Later boots: "Database already provisioned, skipping db_setup."  (idempotent)
```

If you see `Was not able to apply settings on database`, either the DB wasn't
ready in time (raise `DB_SETUP_WAIT`, default 30) or `DB_SETUP_PASS` ≠
`MARIADB_ROOT_PASSWORD`.

Confirm `config.php` was generated (placeholders substituted):

```bash
podman exec simplerisk-app grep define /var/www/simplerisk/includes/config.php
# define('DB_HOSTNAME', 'host.containers.internal'); ...
```

## Open the app

Open `https://localhost:8443/` (self-signed — accept the browser cert warning).
The app is published on HTTPS only: rootless Podman can't bind privileged host
80/443, so the app's internal `:80→:443` rewrite can't be exposed 1:1 (an http
port would redirect to a dead host `:443`).

> There is **no DB-local admin** by default — `DB_SETUP=automatic` loads only
> the core schema, and login is via LDAP auto-provision (below). To seed a
> local admin instead, set `ADMIN_USERNAME`/`ADMIN_PASSWORD`/`ADMIN_EMAIL` in
> `.env` and re-provision.

## LDAP demo users are auto-configured after install

The Custom Authentication (LDAP) extra is wired so a fresh install comes up
already pointed at the demo OpenLDAP container — **no Admin → Authentication
step needed**. After `db_setup`, the entrypoint runs `seed-ldap-settings.php`
once, writing the LDAP **connection** and auto-provisioning settings into the
`settings` table with `INSERT IGNORE` (never overwrites anything you later
change by hand). Confirm it ran:

```bash
podman logs simplerisk-app 2>&1 | grep seed-ldap
# [seed-ldap] 13 connection setting(s) seeded.
```

**The default role is deliberately not seeded** — you create it yourself. In
Admin → Role Management, add a role (e.g. `Default`) with the permissions you
want and **mark it as the default role**. SimpleRisk's `provision.php` falls back
to `get_default_role_id()` (the role flagged `default`) when
`LDAP_DEFAULT_ROLE_ID` is unset, so that one flag is all the role wiring needed —
the seed never touches the role. Until you create + flag a default role, LDAP
users can authenticate but get no role/permissions.

Then log in at `https://localhost:8443` as any demo directory user
(auto-provisioned on first login with your default role):

| Username | Password |
|---|---|
| `jdoe` | `jdoePass123!` |
| `asmith` | `asmithPass123!` |
| `admin1` | `admin1Pass123!` |

These are throwaway dev credentials from `ldap-seed.ldif` (repo root). The
seed itself is dev-only (plaintext bind, bind password `admin`) — see
`seed-ldap-settings.php`. To point SimpleRisk at a real directory or pick a
specific default role, edit Admin → Authentication; the connection settings are
only filled if absent.

## Stop / teardown

```bash
podman compose -f compose.app.yml down            # stop app, keep DB
podman compose -f compose.support.yml down        # stop support, keep data
podman compose -f compose.support.yml down -v     # stop support + wipe db/ldap data
```

The two stacks are independent — you can rebuild/restart the app without
touching the database.

## Editing code

- **Extras** (`./extras`) are bind-mounted into the container and OPcache revalidates
  timestamps, so extra edits are **live** — edit on the host and refresh the browser
  (no rebuild, no restart). Per-extra `config.php` and `data/` written at runtime live
  on the host and persist across rebuilds.
- **Core** (`./simplerisk`) is `COPY`'d into the image, so core edits still need a
  rebuild. podman-compose 1.5.0 does **not** recreate a container when only the image
  ID changes (the image *name* is unchanged), so add `--force-recreate`:

```bash
podman compose -f compose.app.yml build simplerisk
podman compose -f compose.app.yml up -d --force-recreate simplerisk
```

TLS material in `./certs` is bind-mounted, so it survives a rebuild — the entrypoint
regenerates it only on first boot. The install survives the recreate too: `config.php`
is regenerated from the `SIMPLERISK_DB_*` env vars on every boot, and `db_setup` is
idempotent (it skips when the DB is already provisioned), so the container comes back
already installed — no re-provisioning.

## Services

| Container | Image | Host port |
|---|---|---|
| `simplerisk-db` | `mariadb:11.4` | 3306 |
| `simplerisk-ldap` | `osixia/openldap:1.4.0` | 1389 → 389 |
| `simplerisk-smtp` | `namshi/smtp` (optional, `--profile mail`) | 1025 → 25 |
| `simplerisk-app` | `simplerisk-local:dev` | 8443 (https) |

All support ports are published on `0.0.0.0` so the app container (a separate
stack) can reach them via the host-gateway. LDAP and SMTP publish on high host
ports (1389, 1025) because rootless Podman cannot bind privileged ports (<1024).
The app uses these host port numbers (`SIMPLERISK_DB_PORT=3306`, LDAP `1389`).
Tighten with a host firewall if you don't want them exposed beyond the machine.

## Production deployment

In production this is a **single app service** dropped into an environment that
already provides the database, Active Directory, network firewall, and WAF. The
dev support stack (`compose.support.yml` — MariaDB, the demo OpenLDAP, SMTP),
`.env.support`, and the two-stack host-gateway wiring are **not used in
production** at all. The app connects to your existing DB and AD and sits behind
your existing reverse proxy / firewall / WAF; the steps below cover only what
you must do on the app side.

### Configuration files to set

| File | Controls | What to do in prod |
|---|---|---|
| `.env` (copy from `.env.example`) | App secrets, DB credentials, mail, first admin | Replace every blank/dev value with strong unique ones; set `SEED_LDAP_SETTINGS=0`; set `SIMPLERISK_DB_SSL_CERT_PATH` for DB TLS; set `DB_SETUP=manual` (your DBA pre-provisions the DB) |
| `compose.app.yml` | Network, published port, bind mounts — **and `SIMPLERISK_DB_HOSTNAME` / `SIMPLERISK_DB_PORT`, which are hardcoded here, not in `.env`** — plus the dev `extra_hosts` host-gateway line | This is the file you edit to point the app at your DB and remove the dev host-gateway wiring |
| `./extras/<extra>/config.php` | Per-extra runtime config (API key, LDAP connection, etc.) | Prefer the Admin UI — most writes go to the DB `settings` table; `LDAP_BIND_PASSWORD` stays in `.env` |
| `simplerisk-opcache.ini` (baked into image) | OPcache | Set `opcache.validate_timestamps=0` for prod (dev ships `1` for live extra edits); requires a rebuild |

**Not used in production:** `.env.support` and `./certs/` — the DB and directory
already exist, and TLS is terminated by your existing reverse proxy.

**Generated — do not hand-edit.** `includes/config.php` is rewritten from the
`SIMPLERISK_DB_*` env vars on **every boot** (entrypoint `set_config()`). To
change DB host/port/credentials, edit `.env` / `compose.app.yml` and recreate the
container — anything typed into `config.php` directly is overwritten on the next
start.

### 1. Point the app at your existing database

In `compose.app.yml`, set `SIMPLERISK_DB_HOSTNAME` to your DB server's resolvable
name (and `SIMPLERISK_DB_PORT` if it isn't 3306) and **remove the
`host.containers.internal` `extra_hosts` line** — it exists only to reach the
dev support stack's host-published ports. Ensure the app container's network
routes to the DB (attach the right compose `networks:` / bridge / VLAN).

- **Provisioning.** Your DBA provides an empty `simplerisk` database + an app
  user with least privilege. You won't have root, so `DB_SETUP=automatic` (which
  creates the DB + user + schema as root) does not apply. Set `DB_SETUP=manual`
  (or leave it unset) so the entrypoint only writes `config.php` and connects,
  then load the version-matched core schema yourself —
  `https://github.com/simplerisk/database/raw/master/simplerisk-en-$version.sql`,
  where `$version` is the `Dockerfile`'s `ENV version=`. The dev
  `DB_SETUP_PASS = MARIADB_ROOT_PASSWORD` constraint is gone entirely.
- **TLS to the DB.** Over the internal network you usually want this: set
  `SIMPLERISK_DB_SSL_CERT_PATH` in `.env` to a CA bundle and bind-mount that
  bundle into the container.

### 2. Point authentication at Active Directory

The Custom Authentication extra speaks LDAP, so it targets AD directly. Configure
the connection under Admin → Authentication (host, port, base DN, bind DN / service
account) and set a **default role** under Admin → Role Management (or
`LDAP_DEFAULT_ROLE_ID`) so auto-provisioned users land with least privilege.

- Set `SEED_LDAP_SETTINGS=0` in `.env` so the **dev-only** demo-OpenLDAP seeder
  (`seed-ldap-settings.php`, plaintext bind, password `admin`) never runs. It
  only fills absent settings (`INSERT IGNORE`), but it has no place in prod.
- Put the bind password in `LDAP_BIND_PASSWORD` (`.env`) — it's read from the env
  before the DB, keeping the secret out of the `settings` table.
- Enable TLS to AD (`LDAP_TLS=true`), typically LDAPS on 636 or STARTTLS on 389 —
  not the dev plaintext bind.

### 3. Outbound mail (SMTP relay)

SimpleRisk sends notifications (risk assignments, reviews) via PHPMailer — point
it at your corporate SMTP relay, another existing service like the DB and AD.
Configure it in `.env`, not the Admin UI:

| Var | Value |
|---|---|
| `MAIL_TRANSPORT` | `smtp` |
| `MAIL_HOST` / `MAIL_PORT` | your relay's host and port |
| `MAIL_SMTPAUTH` | `true` (with a service-account login) |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | relay credentials |
| `MAIL_ENCRYPTION` | `tls` (STARTTLS) or `ssl` (implicit TLS) |
| `MAIL_FROM_EMAIL` / `MAIL_FROM_NAME` | the sender address and name SimpleRisk sends as |

The entrypoint writes these to the `settings` table on **every boot**
(`set_mail_settings`), so `.env` is the source of truth: a blank var is left
untouched, but a set var is re-applied on each restart and overwrites any change
you make in Admin → Configure → Email — set them in `.env`. Every `MAIL_*` var
reaches the container (via `env_file`), even ones not repeated in
`compose.app.yml`'s `environment:` block. The `namshi/smtp` relay in
`compose.support.yml` is dev-only — use your real relay in prod.

### 4. Sit behind the existing reverse proxy, firewall, and WAF

Your reverse proxy, firewall, and WAF already exist. Only three things fall on
the app side:

- **Don't expose `8443` publicly.** Bind it loopback or to an internal interface
  so only the reverse proxy can reach it; the firewall handles the rest. Forward
  to the container's **`443`** (the published `8443`), not its `80` — the app's
  internal `:80→:443` rewrite would otherwise 301-redirect the proxy to a dead
  `host:443`. TLS is terminated by the proxy, so you normally don't put a cert in
  `./certs/`.
- **Restore the real client IP.** Behind the proxy, `REMOTE_ADDR` is the proxy,
  which breaks the per-IP auth **lockout** and the API Extra's `REMOTE_ADDR`
  allowlist. Enable Apache **`mod_remoteip`** in the image (`a2enmod remoteip` +
  a conf with `RemoteIPTrustedProxy <proxy-ip>` + `RemoteIPHeader X-Forwarded-For`)
  and rebuild; have the proxy set a sanitized `X-Forwarded-For`. Only list proxies
  you control — an untrusted header lets an attacker spoof an IP past
  lockout/allowlists.
- **WAF tuning.** SimpleRisk sends large POST bodies — risk submissions,
  spreadsheet/CSV imports up to the 64M upload limit, and JSON-RPC/MCP API
  payloads. Coordinate body-size limits and rule exclusions for the API and
  upload paths with the WAF team, or it will silently block imports and the API
  Extra.

### 5. Secrets & the encryption key

`.env` holds the DB password, the LDAP bind password, and
**`SIMPLERISK_ENCRYPTION_KEY`** (`.env.support` is dev-only and unused). In
production:

- Keep `.env` `chmod 600` and out of git (it already is — see `.gitignore`);
  prefer a secrets manager or Podman/Kubernetes **secrets** over plaintext env.
- **Back up `SIMPLERISK_ENCRYPTION_KEY` somewhere durable and separate.** Risks,
  assets, frameworks, projects, and audit-log entries are encrypted with it; lose
  the key and that data is unrecoverable. (App-wide `XXXX` where values should be
  means the key is missing.) Rotating it is a dedicated re-encrypt pass, not an
  env-var swap.
- With `DB_SETUP=manual` there is **no** bootstrap root credential in the env —
  only the app user's password.

### 6. Backups

| What | Where it lives | Notes |
|---|---|---|
| App data (risks, assets, settings) | **your existing DB server** | already covered by the org's DB backups — confirm schedule + retention with the DBA |
| Extra config & data | host bind mount `./extras` | not in a named volume — back up the dir |
| Secrets | `.env` | **esp. `SIMPLERISK_ENCRYPTION_KEY`** |

`/var/www/simplerisk` is intentionally **not** a volume (see the `Dockerfile`
header), so there's no app-code state to back up; a restore is DB + `./extras` +
`.env` + a rebuild. Test a restore into a fresh instance at least once.

### 7. Resource limits, restart policy, pinning

- Add **resource limits** to the app service (`deploy.resources.limits` —
  memory/CPU) so a runaway import can't OOM the box. `restart: unless-stopped` is
  already set.
- **Pin everything.** The image pins PHP `8.2` (`ARG php_version`) and the
  SimpleRisk core version (`ENV version=…`, which drives the version-aligned
  schema). Keep it — the extras were tested on PHP 8.2, and a casual bump is an
  untested change. Plan core upgrades as a deliberate rebuild + schema migration.
- Forward logs off the box: the image declares `VOLUME ["/var/log"]`; ship Apache
  and supervisord logs to your aggregator. The `HEALTHCHECK` (HTTP on the
  internal `:80`) gives a liveness signal for monitoring.

### Pre-flight checklist

Before exposing the app:

- [ ] App points at the existing DB via `SIMPLERISK_DB_HOSTNAME` in
      `compose.app.yml`; dev `extra_hosts` host-gateway line removed; `config.php`
      left to auto-generate
- [ ] `DB_SETUP=manual`/unset; schema + least-privilege app user created on the DB;
      DB TLS on via `SIMPLERISK_DB_SSL_CERT_PATH`
- [ ] Authentication pointed at AD; `SEED_LDAP_SETTINGS=0`; bind password in
      `LDAP_BIND_PASSWORD`; AD TLS on; default role set
- [ ] Outbound mail points at the corporate SMTP relay via the `MAIL_*` vars in
      `.env` (source of truth — overwrites UI changes on restart)
- [ ] `8443` bound loopback/internal behind the existing proxy; `mod_remoteip`
      configured so lockout/API allowlist see the real client IP
- [ ] Existing WAF tuned for SimpleRisk's large POST bodies / API / upload paths
- [ ] `.env` is `600`, not in git; `SIMPLERISK_ENCRYPTION_KEY` backed up separately
- [ ] `./extras` backed up; restore tested
- [ ] Resource limits set; PHP/core versions pinned; logs forwarded

## Troubleshooting

### "Was not able to apply settings on database" (db_setup failed)

The app entrypoint couldn't bootstrap the DB. Causes:

- **Support stack not up / not healthy** — bring `compose.support.yml` up first
  and wait for `simplerisk-db (healthy)` before starting the app.
- **`DB_SETUP_PASS` ≠ `MARIADB_ROOT_PASSWORD`** — the app uses the root password
  to create the DB/user. `DB_SETUP_PASS` lives in `.env` and `MARIADB_ROOT_PASSWORD`
  lives in `.env.support`; set them to the **same** value (the two stacks have
  separate env files, so this match is on you).
- **DB too slow to accept connections** — raise `DB_SETUP_WAIT` (default 30s).
- **DB already had a partial state** — `down -v` the support stack and retry.

### Can't reach the DB from the app (connection refused / timeout)

Confirm the app resolves and reaches the host-gateway:

```bash
podman exec simplerisk-app getent hosts host.containers.internal
podman exec simplerisk-app mysql -usimplerisk -p"$SIMPLERISK_DB_PASSWORD" \
  -h host.containers.internal -P 3306 simplerisk -e "SELECT 1;"
```

If `getent` fails, the `extra_hosts: host.containers.internal:host-gateway` line
in `compose.app.yml` isn't taking effect (older Podman). If `mysql` fails but
`getent` works, the support stack's `db` port isn't published on `0.0.0.0`.

## Notes on podman-compose 1.5.0

`podman compose down -v` works correctly with this stack (tested with podman
5.8.2 / podman-compose 1.5.0). A separate 1.5.0 quirk that **does** bite:
`podman compose up -d` does not recreate a container whose image ID changed
under the same image name — after rebuilding the app image you must pass
`--force-recreate` (see "Rebuild after code edits" above).
