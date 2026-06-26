# SimpleRisk Performance Tuning

Slow SimpleRisk pages are almost never a web-server problem. They are a **PHP** and **database** problem. Swapping Apache for nginx buys you *concurrency* (more simultaneous users per GB of RAM) — it does **not** make an individual page render faster, and for a small-team Community Edition install it is not worth the migration.

This guide covers the two highest-leverage, low-risk changes: **PHP OPcache tuning** and **MariaDB `innodb_buffer_pool_size` / slow-query-log tuning**. It shows how to apply them to **this repo's dev stack** (concrete file edits) and to a **production server** (generic paths).

> These are *how-to* instructions. They do not change any files for you — make the edits yourself.

---

## Where the configuration lives

| Target | PHP (OPcache) | MariaDB |
|---|---|---|
| **Dev stack (this repo)** | `simplerisk-opcache.ini` (COPY'd into the image under `/usr/local/etc/php/conf.d/` by `Dockerfile`) | `compose.support.yml` — the `db:` service `command:` line |
| **Production** | A drop-in `.ini` in `/etc/php/<ver>/.../conf.d/` (Debian/Ubuntu) or `/etc/php.d/` (RHEL) — for mod_php use the `apache2` dir, for PHP-FPM use the `fpm` dir | `/etc/mysql/mariadb.conf.d/50-server.cnf` (Debian/Ubuntu) or `/etc/my.cnf` (RHEL) |

> After any change: **dev** = `podman compose -f compose.app.yml build simplerisk && podman compose -f compose.app.yml up -d` (PHP) / `podman compose -f compose.support.yml up -d --force-recreate db` (MariaDB). **Production** = `systemctl restart apache2` (or `php<fpm>`) and `systemctl restart mariadb`.

---

## 1. PHP OPcache tuning

OPcache caches the compiled bytecode of PHP files so they are not recompiled on every request. SimpleRisk is a large, include-heavy app (the `vendor/` autoload plus big files like `header.php`), so opcache is the single biggest PHP-side lever.

The extension is **already installed** in the dev image (`opcache` in `Dockerfile`) and enabled by default, but with stock values. The settings below raise its memory and file limits and — for production — stop it from `stat()`-ing every PHP file on every request.

### Dev stack

The repo already ships `simplerisk-opcache.ini` (COPY'd into the image by `Dockerfile`); edit it directly:

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=2
opcache.validate_timestamps=0
```

### Production

Drop the same keys into a new file, e.g. `/etc/php/8.2/fpm/conf.d/10-opcache.ini` (or the `apache2` variant for mod_php):

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=2
opcache.validate_timestamps=0
```

> **`validate_timestamps=0` tradeoff:** with this off, PHP never re-checks file modification times, so updated code will **not** take effect until OPcache is cleared (a PHP/PHP-FPM/Apache restart, or `opcache_reset()`). This is correct for production. It is also fine for **this dev stack**, because code changes are applied via `podman compose build` — the container restart clears the cache anyway. If you ever switch to bind-mounting the source for live editing, set it back to `1` so edits show up immediately.

---

## 2. MariaDB tuning

SimpleRisk is query-heavy (permissions, risk scoring, sidebar counts on every page). The default `innodb_buffer_pool_size` is **128 MB**; raising it keeps your tables and indexes in RAM instead of hitting disk on every query — the single biggest database win on a production instance with real data.

> On the dev stack the database is small (~174 MB) and already fits in the default buffer pool, so you will see little gain there. This setting matters on **production**, where the dataset is larger.

### Dev stack

Extend the existing `command:` on the `db:` service in `compose.support.yml` (keep the required `--sql-mode`, which SimpleRisk needs):

```yaml
  db:
    image: docker.io/library/mariadb:11.4
    container_name: simplerisk-db
    environment:
      MARIADB_ROOT_PASSWORD: simplerisk_root_pw
      MARIADB_ROOT_HOST: "%"
    command: >-
      --sql-mode=ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION
      --innodb-buffer-pool-size=512M
      --innodb-log-file-size=256M
      --innodb-flush-method=O_DIRECT
      --slow-query-log=ON
      --long-query-time=1
```

### Production

Add the equivalent to your MariaDB config file (`/etc/mysql/mariadb.conf.d/50-server.cnf` or `/etc/my.cnf`):

```ini
[mariadb]
innodb_buffer_pool_size = 2G          # ~50–70% of the machine's RAM — adjust to your box
innodb_log_file_size    = 512M
innodb_flush_method     = O_DIRECT
slow_query_log          = ON
long_query_time         = 1
```

> **Optional write-throughput boost — `innodb_flush_log_at_trx_commit = 2`:** roughly doubles write speed, but can lose up to ~1 second of transactions on an OS crash (a durability tradeoff). **Skip it** for a risk-tracking app unless write throughput is provably your bottleneck. The default (`1`) is fully durable.

---

## 3. Diagnosing slow pages

Before tuning blindly, find where the time actually goes. Measure **Time To First Byte (TTFB)** on a *logged-in* page:

```bash
# Dev stack (replace the path with a real authenticated page). -k = self-signed cert.
curl -sk -o /dev/null -w 'ttfb=%{time_starttransfer}s  total=%{time_total}s  connect=%{time_connect}s\n' \
  https://localhost:8443/management/index.php
```

In a browser: **DevTools → Network → reload the page → click the document request → read "Waiting (TTFB)"**.

### Interpreting the numbers

| Observation | Likely cause | Where to look |
|---|---|---|
| **TTFB high** (> ~0.5–1 s on a simple page) | Server-side processing | OPcache off/untuned, buffer pool too small, swap, slow disk, or a remote DB |
| **connect/DNS high** | Network / proxy / remote database | Reverse proxy, DB on another host (co-locate it) |
| **TTFB low, but page still slow** | Payload / assets | Large responses, many sub-requests, missing HTTP/2 |

### Slow-query log

With `slow_query_log=ON` and `long_query_time=1` (set above), MariaDB logs queries slower than 1 second. Find the log file and summarize it:

```bash
# Where is it writing?
podman exec simplerisk-db mariadb -uroot -psimplerisk_root_pw -e "SHOW VARIABLES LIKE 'slow_query_log_file';"
# Summarize the worst offenders:
podman exec simplerisk-db sh -c 'mysqldumpslow -s t /var/lib/mysql/*-slow.log | head -20'
```

---

## 4. Verifying the changes

Confirm each setting actually took effect after a restart.

```bash
# OPcache enabled?
podman exec simplerisk-app php -i | grep opcache.enable

# Buffer pool raised?
podman exec simplerisk-db mariadb -uroot -psimplerisk_root_pw \
  -e "SHOW VARIABLES WHERE Variable_name IN ('innodb_buffer_pool_size','innodb_log_file_size','slow_query_log','long_query_time');"
```

On production, run the same `php -i` / `SHOW VARIABLES` against the server directly (no `podman exec`).

Finally, re-measure TTFB before and after — that is the honest proof the change helped.

---

## 5. Troubleshooting: "every page is slow"

When *every* page lags regardless of which one you open, work this checklist top to bottom:

1. **OPcache off or untuned** — confirm `opcache.enable=1`; apply the production profile above. This is the most common cause of universally-slow SimpleRisk.
2. **`innodb_buffer_pool_size` still at the 128 MB default** — raise it to 50–70% of RAM (production).
3. **Underpowered VM / swap / slow disk** — run `free -h` and watch for swap usage; cheap VPS disk I/O makes every query pay.
4. **Remote database** — if the DB is on another host, every per-page query pays network latency (SimpleRisk issues many). Co-locate app and DB.
5. **A per-request remote call** — a registration/license/health check that times out on every request. In this stack those are admin-action-only (not per-request), but a misconfigured extra or custom code can introduce one; check the PHP/Apache error log for repeated connection failures.

For each, the TTFB measurement in section 3 tells you whether the cost is server-side (1–3, 5) or network (4).
