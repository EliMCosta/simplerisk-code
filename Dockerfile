# SimpleRisk app image — mirrors the official simplerisk/simplerisk-minimal pattern
# (github.com/simplerisk/docker /simplerisk-minimal): app at /var/www/simplerisk,
# non-root `simplerisk` user, supervisord running nginx+php-fpm+cron+rsyslog, setcap for
# low ports, self-signed SSL on 443, logrotate. Liveness probe lives in
# compose.app.yml (HEALTHCHECK is not supported in OCI image format).
#
# Differences from the upstream image (forced by our dev/customization model):
#   * PHP 8.4 on the `php-fpm` base (ARG php_version=8.4), running Nginx + PHP-FPM under
#     supervisord instead of upstream's `php-apache` (mod_php). Extras were originally
#     validated on 8.2; the 8.4 bump is gated by the full test suite.
#   * ./simplerisk is the vendored CORE checkout only. The app source is COPY'd from
#     it (instead of downloaded from the SimpleRisk S3 bundle at build time).
#   * Custom extras live in ../extras (outside this repo). compose.app.yml bind-mounts
#     that path over /var/www/simplerisk/extras for live editing — see entrypoint/compose.
#     The image only gets an empty placeholder dir here; runtime content comes from the mount.
#   * /var/www/simplerisk is NOT a VOLUME. The upstream image persists it across image
#     upgrades; we rebuild on every core edit, so a volume there would shadow freshly-
#     COPY'd code. Runtime state that must survive rebuilds (extras config.php/data,
#     TLS material) is bind-mounted from the host instead — see compose.app.yml.
#     config.php is regenerated from env vars each boot, so no app-volume persistence
#     is needed for the core config.
#   * default-mysql-client (Debian) instead of MySQL's apt repo + GPG key — needed
#     for DB_SETUP=automatic (the entrypoint shells out to the `mysql` CLI).
#   * Extra PHP extensions our customizations/job-runner need (bcmath, gettext,
#     soap, pcntl, posix, shmop, sysv*) on top of the upstream set.
#   * Our own simplerisk-limits.ini (64M uploads) + simplerisk-opcache.ini instead
#     of the upstream 5M upload defaults.
#
# Build:  podman compose -f compose.app.yml build simplerisk

ARG php_version=8.4

FROM php:${php_version}-fpm

LABEL maintainer="SimpleRisk"

# Matches simplerisk/includes/version.php APP_VERSION, so DB_SETUP=automatic
# downloads the version-aligned core schema from EliMCosta/simplerisk-database
# (simplerisk-en-$version.sql; override via DB_SCHEMA_REPO / DB_BRANCH).
ENV version=20260519-001

WORKDIR /var/www

# System libraries + tooling. default-mysql-client provides the `mysql` CLI used
# by the entrypoint's db_setup()/apply_mail_setting() (compatible with MariaDB).
# supervisor/cron/rsyslog/logrotate run the process tree. libcap2-bin is only
# needed for the setcap step below and is removed right after.
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libldap2-dev \
        libicu-dev \
        libcap2-bin \
        libcurl4-gnutls-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libxml2-dev \
        supervisor \
        cron \
        ca-certificates \
        rsyslog \
        logrotate \
        curl \
        default-mysql-client \
        unzip \
        nginx \
    && apt-get -y autoremove \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions: upstream set (ldap/mysqli/pdo_mysql/curl/zip/gd/intl) plus ours.
# gd is configured with freetype+jpeg (image/CAPTCHA features in our customizations).
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-configure ldap --with-libdir=lib/$(dpkg-architecture -qDEB_HOST_MULTIARCH) && \
    docker-php-ext-install -j"$(nproc)" \
        ldap \
        mysqli \
        pdo_mysql \
        curl \
        zip \
        gd \
        intl \
        bcmath \
        gettext \
        soap \
        opcache \
        pcntl \
        posix \
        shmop \
        sysvmsg \
        sysvsem \
        sysvshm

# pcov: fast code-coverage driver for the test suite (`bin/test --coverage`).
# Loaded but DISABLED by default so it never instruments the running web app or
# normal test runs; tests/bin/phpunit enables it per-run via -d pcov.enabled=1.
RUN pecl install pcov && docker-php-ext-enable pcov && \
    echo 'pcov.enabled=0' > /usr/local/etc/php/conf.d/simplerisk-pcov.ini

# Bind 80/443 without root and allow cron setgid. NB: libcap2-bin is intentionally
# NOT removed (the upstream apache image did) — the trixie `nginx` package Depends on
# iproute2, whose dependency chain keeps libcap2-bin, so `apt remove libcap2-bin` would
# cascade and uninstall nginx itself. The tool is tiny, so we just keep it.
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/sbin/nginx && \
    chmod gu+s /usr/sbin/cron

# Daily logrotate via cron (upstream).
RUN echo "0 0 * * * root /usr/sbin/logrotate /etc/logrotate.d/simplerisk.conf > /dev/null 2>&1" >> /etc/cron.d/logrotate-cron && \
    chmod 0644 /etc/cron.d/logrotate-cron

# Our PHP tuning: upload/memory limits (spreadsheet/attachment imports) + OPcache.
# validate_timestamps=1 so bind-mounted extras (../extras) are picked up live without a
# restart/rebuild; core is still COPY'd, so core edits still require a rebuild.
COPY simplerisk-limits.ini  /usr/local/etc/php/conf.d/
COPY simplerisk-opcache.ini /usr/local/etc/php/conf.d/

# SAPI polyfill (apache_request_headers/getallheaders under FPM) + its auto_prepend_file
# wiring + the FPM www-pool override. These target the image's /usr/local/etc scan dirs
# (NOT /etc, where `COPY common/ /` below would otherwise land them unused).
COPY docker/sapi_compat.php                     /docker/sapi_compat.php
COPY common/etc/php/fpm/simplerisk-pool.conf    /usr/local/etc/php-fpm.d/zz-simplerisk.conf
COPY common/etc/php/conf.d/simplerisk-sapi.ini  /usr/local/etc/php/conf.d/simplerisk-sapi.ini

# Support files: supervisord, nginx (global+vhost+snippet), rsyslog, logrotate.
# `COPY common/ /` lays common/etc/{nginx,supervisor,rsyslog.d,logrotate.d} into /etc/...
COPY common/ /

# The app source — our customized core checkout, not an upstream download.
COPY simplerisk/ /var/www/simplerisk/

# Placeholder for custom extras — compose.app.yml bind-mounts ../extras here at runtime.
RUN mkdir -p /var/www/simplerisk/extras

# Opt-in first-admin seeder (runs only when ADMIN_USERNAME is set) and the dev-only
# LDAP connection seeder (invoked by the entrypoint after db_setup).
COPY configure-admin.php   /docker/configure-admin.php
COPY seed-ldap-settings.php /usr/local/bin/seed-ldap-settings.php
COPY entrypoint.sh /entrypoint.sh

# SSL/security hardening now lives in the nginx config (common/etc/nginx/nginx.conf:
# server_tokens off; and the security headers in common/etc/nginx/conf.d/simplerisk.conf),
# replacing the old a2enmod/a2enconf + ssl.conf/security.conf sed edits. The self-signed
# CA + server cert is NOT generated here — entrypoint.sh's generate_ssl_certs() creates
# it at runtime into the bind-mounted /etc/nginx/ssl, so it persists across rebuilds and
# can be replaced by dropping files into ./certs. (update-ca-certificates stays dropped:
# it needs root + the CA, and nothing here requires the container to trust its own
# self-signed server cert; DB SSL uses a separate cert.)

# Create the non-root run user, lay out log/run/nginx-temp dirs, and set ownerships so
# the `simplerisk` user (which runs both nginx and the FPM workers) can read/write the
# app, config.php, and the bind-mounted certs. /var/www/html is the base default and
# unused (the app lives under /var/www/simplerisk).
RUN rm -rf /var/www/html && \
    useradd -G www-data simplerisk && \
    mkdir -p /var/log/simplerisk /var/log/supervisor /var/run/supervisor \
             /var/lib/nginx/body /var/lib/nginx/proxy /var/lib/nginx/fastcgi \
             /var/lib/nginx/uwsgi /var/lib/nginx/scgi && \
    chmod -R 700 /var/log/simplerisk /var/run/ /var/www/simplerisk && \
    chmod 755 /entrypoint.sh /docker/sapi_compat.php && \
    chmod +x /usr/local/bin/seed-ldap-settings.php && \
    chown -R simplerisk:www-data /etc/nginx /var/lib/nginx /var/log/simplerisk /var/log/supervisor /var/run/ /var/www/simplerisk

# Persist logs only. /etc/nginx/ssl is bind-mounted from ./certs (see compose.app.yml);
# /var/www/simplerisk is intentionally NOT a volume — see the header comment.
VOLUME [ "/var/log" ]

USER simplerisk

ENTRYPOINT [ "/entrypoint.sh" ]

EXPOSE 80
EXPOSE 443

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]

# PID 1 here is supervisord. SIGTERM is what it actually drains nginx/php-fpm/rsyslog/cron
# on (it stops its managed programs on SIGTERM), so set it explicitly — the php:*-fpm base
# defaults to SIGQUIT (php-fpm's graceful-stop), which supervisord does not forward as a
# clean shutdown, and that forced teardown would race podman-compose's
# `--force-recreate` network cleanup ("network is being used").
STOPSIGNAL SIGTERM
