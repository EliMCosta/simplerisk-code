# SimpleRisk app image — mirrors the official simplerisk/simplerisk-minimal pattern
# (github.com/simplerisk/docker /simplerisk-minimal): app at /var/www/simplerisk,
# non-root `simplerisk` user, supervisord running Apache+cron+rsyslog, setcap for
# low ports, self-signed SSL on 443, logrotate. Liveness probe lives in
# compose.app.yml (HEALTHCHECK is not supported in OCI image format).
#
# Differences from the upstream image (forced by our dev/customization model):
#   * PHP 8.2 (ARG php_version=8.2) — our extras were tested on 8.2; bump to 8.4
#     only as a deliberate, tested change. The upstream default is 8.4.
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

ARG php_version=8.2

FROM php:${php_version}-apache

LABEL maintainer="SimpleRisk"

# Matches simplerisk/includes/version.php APP_VERSION, so DB_SETUP=automatic
# downloads the version-aligned core schema (simplerisk-en-$version.sql).
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

# Bind 80/443 without root and allow cron setgid, then drop the cap helper.
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/sbin/apache2 && \
    chmod gu+s /usr/sbin/cron && \
    apt-get -y remove libcap2-bin && \
    apt-get -y autoremove

# Daily logrotate via cron (upstream).
RUN echo "0 0 * * * root /usr/sbin/logrotate /etc/logrotate.d/simplerisk.conf > /dev/null 2>&1" >> /etc/cron.d/logrotate-cron && \
    chmod 0644 /etc/cron.d/logrotate-cron

# Our PHP tuning: upload/memory limits (spreadsheet/attachment imports) + OPcache.
# validate_timestamps=1 so bind-mounted extras (../extras) are picked up live without a
# restart/rebuild; core is still COPY'd, so core edits still require a rebuild.
COPY simplerisk-limits.ini  /usr/local/etc/php/conf.d/
COPY simplerisk-opcache.ini /usr/local/etc/php/conf.d/

# Support files: supervisord, apache envvars/foreground/vhosts, logrotate, rsyslog.
# `COPY common/ /` lays them into /etc/... exactly as upstream.
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

# Harden Apache ssl/security config and enable the needed modules. The self-signed
# CA + server cert is NOT generated here anymore — entrypoint.sh's generate_ssl_certs()
# creates it at runtime into the bind-mounted /etc/apache2/ssl, so it persists across
# rebuilds and can be replaced by dropping files into ./certs. update-ca-certificates is
# dropped too: it needs root + the CA, and nothing here requires the container to trust
# its own self-signed server cert (healthcheck is HTTP; DB SSL uses a separate cert).
RUN a2enmod headers rewrite ssl && \
    a2enconf security && \
    sed -i 's/\(SSLProtocol\) all -SSLv3/\1 TLSv1.2/g' /etc/apache2/mods-enabled/ssl.conf && \
    sed -i 's/#\(SSLHonorCipherOrder on\)/\1/g' /etc/apache2/mods-enabled/ssl.conf && \
    sed -i 's/\(ServerTokens\) OS/\1 Prod/g' /etc/apache2/conf-enabled/security.conf && \
    sed -i 's/#\(ServerSignature\) On/\1 Off/g' /etc/apache2/conf-enabled/security.conf

# Create the non-root run user, lay out log/run dirs, and set ownerships so the
# `simplerisk` Apache worker can read/write the app and config.php.
RUN rm -rf /var/www/html && \
    useradd -G www-data simplerisk && \
    mkdir -p /var/log/simplerisk /var/log/supervisor /var/run/supervisor && \
    chmod -R 700 /etc/apache2 /var/log/simplerisk /var/run/ /var/www/simplerisk && \
    chmod 755 /entrypoint.sh /etc/apache2/foreground.sh && \
    chmod +x /usr/local/bin/seed-ldap-settings.php && \
    chown -R simplerisk:www-data /etc/apache2 /var/log/apache2 /var/log/simplerisk /var/log/supervisor /var/run/ /var/www/simplerisk

# Persist logs only. /etc/apache2/ssl is bind-mounted from ./certs (see compose.app.yml);
# /var/www/simplerisk is intentionally NOT a volume — see the header comment.
VOLUME [ "/var/log" ]

USER simplerisk

ENTRYPOINT [ "/entrypoint.sh" ]

EXPOSE 80
EXPOSE 443

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]

# PID 1 here is supervisord, NOT apache. The php:*-apache base sets
# `STOPSIGNAL SIGWINCH` (httpd's graceful-stop signal), which supervisord does
# NOT handle — so `podman stop` would send SIGWINCH, supervisord ignores it,
# the 10s grace elapses, and podman SIGKILLs the container. That forced teardown
# also races podman-compose's `--force-recreate` network cleanup ("network is
# being used"). SIGTERM is what supervisord actually drains apache/rsyslog/cron
# on, so overrides the inherited SIGWINCH here.
STOPSIGNAL SIGTERM
