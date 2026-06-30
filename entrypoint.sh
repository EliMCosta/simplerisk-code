#!/bin/bash

set -eo pipefail

print_log(){
	echo "$(date -u +"[%a %b %e %X.%6N %Y]") [$1] $2"
}

exec_cmd(){
	exec_cmd_nobail "$1" || fatal_error "$2"
}

exec_cmd_nobail() {
	bash -c "$1"
}

generate_random_password() {
	# shellcheck disable=SC2005
	echo "$(< /dev/urandom tr -dc _A-Z-a-z-0-9 | head -c21)"
}

# Escape a value for use as the replacement in a sed s|...|VALUE|... command.
# Escapes \, &, and | so they aren't interpreted as sed metacharacters.
# Required for any env-var-derived value that may contain / (which would
# otherwise terminate the / delimiter the sed substitution used to use).
sed_escape() {
	printf '%s' "$1" | sed -e 's/[\\&|]/\\&/g'
}

fatal_error(){
	print_log "error" "$1"
	exit 1
}

set_db_password(){
	if [[ "${DB_SETUP:-}" = automatic* ]]; then
		# shellcheck disable=SC2015
		[ -z "${SIMPLERISK_DB_PASSWORD:-}" ] && SIMPLERISK_DB_PASSWORD=$(generate_random_password) && print_log "initial_setup:warn" "As no password was provided and this is a first time setup, a random password has been generated ($SIMPLERISK_DB_PASSWORD)"
	else
		SIMPLERISK_DB_PASSWORD=${SIMPLERISK_DB_PASSWORD:-simplerisk}
	fi
	sed -i "s|\('DB_PASSWORD', '\).*\(');\)|\1$(sed_escape "$SIMPLERISK_DB_PASSWORD")\2|g" "$CONFIG_PATH"
}

validate_db_setup(){
	case "${DB_SETUP:-}" in
		automatic)
			print_log "initial_info:setup" "Setting database through the automatic process";;
		automatic-only)
			print_log "initial_info:setup" "Setting database through the automatic process and removing container";;
		manual)
			print_log "initial_info:setup" "Database will be set manually";;
		delete)
			print_log "initial_info:setup" "Perform deletion of database";;
		"")
			print_log "initial_info:setup" "Database is already set";;
		*)
			fatal_error "The provided option for DB_SETUP is invalid. It must be automatic, automatic-only or manual.";;
	esac
}

set_config(){
	local CONFIG_PATH='/var/www/simplerisk/includes/config.php'
	local CONFIG_SAMPLE_PATH='/var/www/simplerisk/includes/config.sample.php'

	# Copy the sample config into place. The new SimpleRisk release ships
	# config.sample.php; the entrypoint creates config.php from it before
	# substituting env-var-driven values. For users upgrading from an older
	# image on a persisted /var/www/simplerisk volume, config.sample.php
	# won't be present — fall back to reusing the existing config.php, which
	# the subsequent sed substitutions will rewrite in place.
	if [ -f "$CONFIG_SAMPLE_PATH" ]; then
		cp "$CONFIG_SAMPLE_PATH" "$CONFIG_PATH"
	elif [ ! -f "$CONFIG_PATH" ]; then
		fatal_error "Neither $CONFIG_SAMPLE_PATH nor $CONFIG_PATH is present. The /var/www/simplerisk volume appears to be in an inconsistent state."
	else
		print_log "initial_setup:info" "$CONFIG_SAMPLE_PATH not found; reusing existing $CONFIG_PATH (likely upgrading from an older image on a persisted volume)."
	fi

	# Replacing config variables. Values are run through sed_escape so paths
	# or other inputs containing \, &, or | don't break the substitution.
	SIMPLERISK_DB_HOSTNAME=${SIMPLERISK_DB_HOSTNAME:-localhost}
	sed -i "s|\('DB_HOSTNAME', '\).*\(');\)|\1$(sed_escape "$SIMPLERISK_DB_HOSTNAME")\2|g" "$CONFIG_PATH"

	SIMPLERISK_DB_PORT=${SIMPLERISK_DB_PORT:-3306}
	sed -i "s|\('DB_PORT', '\).*\(');\)|\1$(sed_escape "$SIMPLERISK_DB_PORT")\2|g" "$CONFIG_PATH"

	SIMPLERISK_DB_USERNAME=${SIMPLERISK_DB_USERNAME:-simplerisk}
	sed -i "s|\('DB_USERNAME', '\).*\(');\)|\1$(sed_escape "$SIMPLERISK_DB_USERNAME")\2|g" "$CONFIG_PATH"

	set_db_password

	SIMPLERISK_DB_DATABASE=${SIMPLERISK_DB_DATABASE:-simplerisk}
	sed -i "s|\('DB_DATABASE', '\).*\(');\)|\1$(sed_escape "$SIMPLERISK_DB_DATABASE")\2|g" "$CONFIG_PATH"

	SIMPLERISK_DB_FOR_SESSIONS=${SIMPLERISK_DB_FOR_SESSIONS:-true}
	sed -i "s|\('USE_DATABASE_FOR_SESSIONS', '\).*\(');\)|\1$(sed_escape "$SIMPLERISK_DB_FOR_SESSIONS")\2|g" "$CONFIG_PATH"

	if [ -n "${SIMPLERISK_DB_SSL_CERT_PATH:-}" ]; then
		# The sample ships DB_SSL_CERTIFICATE_PATH as a commented-out line so
		# that operators who don't set the env var don't end up requesting SSL
		# against a non-existent cert path. When a path is supplied, rewrite
		# the whole line (commented or not) as an active define.
		escaped_ssl_path=$(sed_escape "$SIMPLERISK_DB_SSL_CERT_PATH")
		sed -i "s|^[[:space:]]*\(//[[:space:]]*\)\{0,1\}define('DB_SSL_CERTIFICATE_PATH', '[^']*');|define('DB_SSL_CERTIFICATE_PATH', '${escaped_ssl_path}');|" "$CONFIG_PATH"
	fi
}

set_csrf_secret(){
	CSRF_SECRET_PATH='/var/www/simplerisk/vendor/simplerisk/csrf-magic/csrf-secret.php'

	# If a SIMPLERISK_CSRF_SECRET value was specified create the csrf-secret.php file with that value
	[ -n "${SIMPLERISK_CSRF_SECRET:-}" ] && echo "<?php \$secret = \"${SIMPLERISK_CSRF_SECRET}\"; ?>" > "$CSRF_SECRET_PATH";
}

set_cron(){
	# If SIMPLERISK_CRON_SETUP was passed and it is set to disabled
	if [[ -n "${SIMPLERISK_CRON_SETUP:-}" && "${SIMPLERISK_CRON_SETUP:-}" = disabled* ]]; then
		print_log "cron_setup" "SimpleRisk cron setup is disabled."
	else
		print_log "cron_setup" "SimpleRisk cron setup is enabled."

		CRON_PATH='/tmp/backup-cron'

		# Create the cron file
		exec_cmd "echo '* * * * * /usr/local/bin/php -f /var/www/simplerisk/cron/cron.php > /dev/null 2>&1' >> $CRON_PATH" "Failed to write cron file. Exiting."
		exec_cmd "chmod 0644 $CRON_PATH" "Failed to chmod cron file. Exiting."
		exec_cmd_nobail "crontab $CRON_PATH" || print_log "cron_setup:warn" "crontab installation failed — cron may not run. Set SIMPLERISK_CRON_SETUP=disabled if cron is managed externally."
	fi
}

# Generate the self-signed CA + server cert referenced by the SSL vhost, but only when
# it isn't already present. /etc/nginx/ssl is bind-mounted from ./certs on the host,
# so the generated material persists across rebuilds and an operator can replace it by
# dropping files into ./certs (then this is a no-op). Mirrors the openssl invocations
# the Dockerfile used to run at build time. Runs as the non-root simplerisk user, which
# can write the bind mount thanks to userns: keep-id in compose.app.yml.
generate_ssl_certs(){
	local SSL_DIR='/etc/nginx/ssl'
	local CA_DIR="$SSL_DIR/ca"
	local SRV_DIR="$SSL_DIR/simplerisk"
	local CA_KEY="$CA_DIR/ca.key"
	local CA_CRT="$CA_DIR/ca.crt"
	local SRV_KEY="$SRV_DIR/simplerisk.key"
	local SRV_CSR="$SRV_DIR/simplerisk.csr"
	local SRV_CRT="$SRV_DIR/simplerisk.crt"

	if [ -f "$SRV_CRT" ] && [ -f "$SRV_KEY" ] && [ -f "$CA_CRT" ]; then
		print_log "ssl_setup:info" "SSL certificates already present in $SSL_DIR; keeping existing."
		return 0
	fi

	print_log "ssl_setup:info" "Generating self-signed CA + server certificate in $SSL_DIR..."
	exec_cmd "mkdir -p \"$CA_DIR\" \"$SRV_DIR\"" "Failed to create SSL directories. Exiting."
	exec_cmd "openssl genrsa -out \"$CA_KEY\" 4096" "Failed to generate CA key. Exiting."
	exec_cmd "openssl req -x509 -new -nodes -key \"$CA_KEY\" -sha256 -days 3650 -out \"$CA_CRT\" -subj \"/CN=SimpleRisk CA\"" "Failed to generate CA certificate. Exiting."
	exec_cmd "openssl genrsa -out \"$SRV_KEY\" 2048" "Failed to generate server key. Exiting."
	exec_cmd "openssl req -new -key \"$SRV_KEY\" -out \"$SRV_CSR\" -subj \"/CN=localhost\" -addext \"subjectAltName=DNS:localhost,DNS:simplerisk,IP:127.0.0.1,IP:0.0.0.0\"" "Failed to generate server CSR. Exiting."
	exec_cmd "openssl x509 -req -days 365 -in \"$SRV_CSR\" -CA \"$CA_CRT\" -CAkey \"$CA_KEY\" -CAcreateserial -out \"$SRV_CRT\" -copy_extensions copyall" "Failed to sign server certificate. Exiting."
	print_log "ssl_setup:info" "SSL certificate generation complete."
}

apply_mail_setting(){
	local db_key="$1" value="$2"
	# Escape backslashes then single quotes for a MySQL single-quoted string literal
	local escaped
	escaped=$(printf '%s' "$value" | sed 's/\\/\\\\/g' | sed "s/'/\\\\'/g")
	mysql -u "$SIMPLERISK_DB_USERNAME" \
	      -p"$SIMPLERISK_DB_PASSWORD" \
	      -h "$SIMPLERISK_DB_HOSTNAME" \
	      -P "$SIMPLERISK_DB_PORT" \
	      --skip-ssl \
	      "$SIMPLERISK_DB_DATABASE" \
	      -e "UPDATE settings SET value='${escaped}' WHERE name='${db_key}';" \
	    || print_log "mail_settings:warn" "Failed to update ${db_key}"
}

repair_simplerisk_base_url(){
	# Nginx catch-all `server_name _` caused first-boot installs to persist
	# simplerisk_base_url as `https://_`. Repair on every boot (idempotent).
	local public_url="${SIMPLERISK_PUBLIC_URL:-https://localhost:8443}"
	local escaped
	escaped=$(printf '%s' "$public_url" | sed 's/\\/\\\\/g' | sed "s/'/\\\\'/g")
	mysql -u "$SIMPLERISK_DB_USERNAME" \
	      -p"$SIMPLERISK_DB_PASSWORD" \
	      -h "$SIMPLERISK_DB_HOSTNAME" \
	      -P "$SIMPLERISK_DB_PORT" \
	      --skip-ssl \
	      "$SIMPLERISK_DB_DATABASE" \
	      -e "UPDATE settings SET value='${escaped}' WHERE name='simplerisk_base_url' AND value REGEXP '^https?://_';" \
	    || print_log "base_url:warn" "Failed to repair simplerisk_base_url"
}

set_mail_settings(){
	local email_regex='^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+$'

	# transport: smtp or sendmail
	if [ -n "${MAIL_TRANSPORT:-}" ]; then
		case "${MAIL_TRANSPORT}" in
			smtp|sendmail) apply_mail_setting phpmailer_transport "$MAIL_TRANSPORT" ;;
			*) print_log "mail_settings:warn" "MAIL_TRANSPORT='${MAIL_TRANSPORT}' must be 'smtp' or 'sendmail' — skipping" ;;
		esac
	fi

	# email addresses: validate regex
	if [ -n "${MAIL_FROM_EMAIL:-}" ]; then
		if [[ "${MAIL_FROM_EMAIL}" =~ $email_regex ]]; then
			apply_mail_setting phpmailer_from_email "$MAIL_FROM_EMAIL"
		else
			print_log "mail_settings:warn" "MAIL_FROM_EMAIL is not a valid email address — skipping"
		fi
	fi

	if [ -n "${MAIL_REPLYTO_EMAIL:-}" ]; then
		if [[ "${MAIL_REPLYTO_EMAIL}" =~ $email_regex ]]; then
			apply_mail_setting phpmailer_replyto_email "$MAIL_REPLYTO_EMAIL"
		else
			print_log "mail_settings:warn" "MAIL_REPLYTO_EMAIL is not a valid email address — skipping"
		fi
	fi

	# free-form strings
	# shellcheck disable=SC2015
	[ -n "${MAIL_FROM_NAME:-}" ]    && apply_mail_setting phpmailer_from_name    "$MAIL_FROM_NAME"    || true
	# shellcheck disable=SC2015
	[ -n "${MAIL_REPLYTO_NAME:-}" ] && apply_mail_setting phpmailer_replyto_name "$MAIL_REPLYTO_NAME" || true
	# shellcheck disable=SC2015
	[ -n "${MAIL_HOST:-}" ]         && apply_mail_setting phpmailer_host         "$MAIL_HOST"         || true
	# shellcheck disable=SC2015
	[ -n "${MAIL_USERNAME:-}" ]     && apply_mail_setting phpmailer_username     "$MAIL_USERNAME"     || true
	# shellcheck disable=SC2015
	[ -n "${MAIL_PREPEND:-}" ]      && apply_mail_setting phpmailer_prepend      "$MAIL_PREPEND"      || true

	# booleans: true or false
	if [ -n "${MAIL_SMTPAUTOTLS:-}" ]; then
		case "${MAIL_SMTPAUTOTLS}" in
			true|false) apply_mail_setting phpmailer_smtpautotls "$MAIL_SMTPAUTOTLS" ;;
			*) print_log "mail_settings:warn" "MAIL_SMTPAUTOTLS must be 'true' or 'false' — skipping" ;;
		esac
	fi

	if [ -n "${MAIL_SMTPAUTH:-}" ]; then
		case "${MAIL_SMTPAUTH}" in
			true|false) apply_mail_setting phpmailer_smtpauth "$MAIL_SMTPAUTH" ;;
			*) print_log "mail_settings:warn" "MAIL_SMTPAUTH must be 'true' or 'false' — skipping" ;;
		esac
	fi

	# smtpsecure: none, tls, or ssl
	if [ -n "${MAIL_ENCRYPTION:-}" ]; then
		case "${MAIL_ENCRYPTION}" in
			none|tls|ssl) apply_mail_setting phpmailer_smtpsecure "$MAIL_ENCRYPTION" ;;
			*) print_log "mail_settings:warn" "MAIL_ENCRYPTION must be 'none', 'tls', or 'ssl' — skipping" ;;
		esac
	fi

	# port: numeric only
	if [ -n "${MAIL_PORT:-}" ]; then
		if [[ "${MAIL_PORT}" =~ ^[0-9]+$ ]]; then
			apply_mail_setting phpmailer_port "$MAIL_PORT"
		else
			print_log "mail_settings:warn" "MAIL_PORT must be numeric — skipping"
		fi
	fi

	# password: only applied when non-empty
	# shellcheck disable=SC2015
	[ -n "${MAIL_PASSWORD:-}" ] && apply_mail_setting phpmailer_password "$MAIL_PASSWORD" || true
}

# Deviation from upstream (idempotent provisioning): returns 0 (true) if the DB
# is already provisioned — detected by the presence of the core `settings` table
# in the target database. Uses the bootstrap root credential. If the DB is not
# reachable yet, returns 1 (not provisioned) so the caller falls through to the
# wait-and-provision path. `local` masks the mysql exit code so a connection
# failure does not trip `set -e`.
db_already_provisioned(){
	export MYSQL_PWD="$DB_SETUP_PASS"
	local exists
	exists=$(mysql -u "$DB_SETUP_USER" -h "$SIMPLERISK_DB_HOSTNAME" -P "$SIMPLERISK_DB_PORT" \
		--skip-column-names --batch \
		-e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${SIMPLERISK_DB_DATABASE}' AND table_name='settings';" 2>/dev/null)
	unset MYSQL_PWD
	[ "${exists:-0}" -ge 1 ]
}

# Idempotent post-provision hooks, safe to run on every boot. configure-admin
# skips when users already exist; seed-ldap-settings uses INSERT IGNORE.
db_setup_post_hooks(){
	if [ -n "${ADMIN_USERNAME:-}" ]; then
		exec_cmd_nobail "php /docker/configure-admin.php" || print_log "initial_setup:warn" "Admin user creation failed; check output above"
	fi
	if [ "${SEED_LDAP_SETTINGS:-1}" = "1" ]; then
		exec_cmd_nobail "php /usr/local/bin/seed-ldap-settings.php" || print_log "initial_setup:warn" "LDAP connection seed failed; see seed-ldap-settings output"
	fi
}

delete_db(){
	print_log "db_deletion: prepare" "Performing database deletion"

	# Pass password via env var to avoid shell interpretation of special characters in the value
	export MYSQL_PWD="$DB_SETUP_PASS"
	# Needed to separate the GRANT statement from the rest because it was providing a syntax error
	exec_cmd "mysql -u $DB_SETUP_USER -h$SIMPLERISK_DB_HOSTNAME -P$SIMPLERISK_DB_PORT <<EOSQL
	SET sql_mode = 'ANSI_QUOTES';
	DROP DATABASE \"${SIMPLERISK_DB_DATABASE}\";
	USE mysql;
	DROP USER '${SIMPLERISK_DB_USERNAME}'@'%';
	FLUSH PRIVILEGES;
EOSQL" "Was not able to apply settings on database. Check error above. Exiting."
	unset MYSQL_PWD

	print_log "db_deletion:done" "Database deletion performed. Exiting."
	exit 0
}

db_setup(){
	# Fast path: if the DB is up and already provisioned, skip the whole setup
	# (including the wait). Makes restarts/rebuilds of an installed stack instant.
	if db_already_provisioned; then
		print_log "initial_setup:info" "Database already provisioned, skipping db_setup."
		db_setup_post_hooks
		return 0
	fi

	print_log "initial_setup:info" "First time setup. Will wait..."
	exec_cmd "sleep ${DB_SETUP_WAIT:-20}s > /dev/null 2>&1" "DB_SETUP_WAIT variable is set incorrectly. Exiting."

	# Re-check after the wait: the first check may have failed only because the
	# DB was still starting (cold start of both stacks together).
	if db_already_provisioned; then
		print_log "initial_setup:info" "Database already provisioned (after wait), skipping db_setup."
		db_setup_post_hooks
		return 0
	fi

	print_log "initial_setup:info" "Starting database set up"

	if [ "$version" == "testing" ]; then
		print_log "initial_setup:info" "Testing version detected. Looking for SQL script (simplerisk.sql) at /var/www/simplerisk/..."
		SCHEMA_FILE='/var/www/simplerisk/simplerisk.sql'
		exec_cmd "[ -f $SCHEMA_FILE ]" "SQL script not found. Exiting."
	else
		print_log "initial_setup:info" "Downloading schema..."
		SCHEMA_FILE='/tmp/simplerisk.sql'
		DB_SCHEMA_REPO="${DB_SCHEMA_REPO:-EliMCosta/simplerisk-database}"
		DB_BRANCH="${DB_BRANCH:-master}"
		exec_cmd "curl -sL https://raw.githubusercontent.com/${DB_SCHEMA_REPO}/${DB_BRANCH}/simplerisk-en-$version.sql > $SCHEMA_FILE" "Could not download schema from Github. Exiting."
	fi

	print_log "initial_setup:info" "Applying changes to MySQL database... (MySQL error will be printed to console as guidance)"
	# Pass password via env var to avoid shell interpretation of special characters in the value
	export MYSQL_PWD="$DB_SETUP_PASS"
	# Using sql_mode = ANSI_QUOTES to avoid using backticks.
	# CREATE ... IF NOT EXISTS defends against a partial state; the schema source
	# only runs here because both provisioning checks above said "not provisioned".
	exec_cmd "mysql -u $DB_SETUP_USER -h$SIMPLERISK_DB_HOSTNAME -P$SIMPLERISK_DB_PORT <<EOSQL
SET sql_mode = 'ANSI_QUOTES';
CREATE DATABASE IF NOT EXISTS \"${SIMPLERISK_DB_DATABASE}\";
USE \"${SIMPLERISK_DB_DATABASE}\";
\. ${SCHEMA_FILE}
CREATE USER IF NOT EXISTS \"${SIMPLERISK_DB_USERNAME}\"@\"%\" IDENTIFIED BY \"${SIMPLERISK_DB_PASSWORD}\";
EOSQL" "Was not able to apply settings on database. Check error above. Exiting."
	# Needed to separate the GRANT statement from the rest because it was providing a syntax error
	exec_cmd "mysql -u $DB_SETUP_USER -h$SIMPLERISK_DB_HOSTNAME -P$SIMPLERISK_DB_PORT <<EOSQL
SET sql_mode = 'ANSI_QUOTES';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, REFERENCES, INDEX, ALTER ON \"${SIMPLERISK_DB_DATABASE}\".* TO \"${SIMPLERISK_DB_USERNAME}\"@\"%\";
EOSQL" "Was not able to apply settings on database. Check error above. Exiting."
	unset MYSQL_PWD

	print_log "initial_setup:info" "Setup has been applied successfully!"
	print_log "initial_setup:info" "Removing schema file..."
	exec_cmd "rm ${SCHEMA_FILE}"

	db_setup_post_hooks

	# shellcheck disable=SC2015
	[ "${DB_SETUP:-}" = "automatic-only" ] && print_log "initial_setup:info" "Running setup only (automatic-only). Container will be discarded." && exit 0 || true
}

unset_variables() {
	unset DB_SETUP
	unset DB_SETUP_USER
	unset DB_SETUP_PASS
	unset DB_SETUP_WAIT
	unset SIMPLERISK_DB_HOSTNAME
	unset SIMPLERISK_DB_PORT
	unset SIMPLERISK_DB_USERNAME
	unset SIMPLERISK_DB_PASSWORD
	unset SIMPLERISK_DB_DATABASE
	unset SIMPLERISK_DB_FOR_SESSIONS
	unset SIMPLERISK_DB_SSL_CERT_PATH
	unset SIMPLERISK_CSRF_SECRET
	unset SIMPLERISK_CRON_SETUP
	unset MAIL_TRANSPORT
	unset MAIL_FROM_EMAIL
	unset MAIL_FROM_NAME
	unset MAIL_REPLYTO_EMAIL
	unset MAIL_REPLYTO_NAME
	unset MAIL_HOST
	unset MAIL_SMTPAUTOTLS
	unset MAIL_SMTPAUTH
	unset MAIL_USERNAME
	unset MAIL_PASSWORD
	unset MAIL_ENCRYPTION
	unset MAIL_PORT
	unset MAIL_PREPEND
	unset ADMIN_USERNAME
	unset ADMIN_PASSWORD
	unset ADMIN_EMAIL
	unset ADMIN_NAME
	unset SIMPLERISK_PUBLIC_URL
}

_main() {
	# Detect whether the operator has opted into Docker-managed config
	# provisioning. If no DB env vars are set, leave config.php absent so
	# SimpleRisk's web installer runs on first request.
	local docker_managed_config=false
	if [ -n "${DB_SETUP:-}" ] \
	    || [ -n "${SIMPLERISK_DB_HOSTNAME:-}" ] \
	    || [ -n "${SIMPLERISK_DB_PORT:-}" ] \
	    || [ -n "${SIMPLERISK_DB_USERNAME:-}" ] \
	    || [ -n "${SIMPLERISK_DB_PASSWORD:-}" ] \
	    || [ -n "${SIMPLERISK_DB_DATABASE:-}" ] \
	    || [ -n "${SIMPLERISK_DB_FOR_SESSIONS:-}" ] \
	    || [ -n "${SIMPLERISK_DB_SSL_CERT_PATH:-}" ]; then
		docker_managed_config=true
	fi

	if [ "$docker_managed_config" = true ]; then
		validate_db_setup
		set_config
	else
		print_log "initial_setup:info" "No DB env vars provided; config.php will not be written. The SimpleRisk web installer will run at first request."
	fi

	set_cron

	# Ensure TLS material exists before nginx starts. Idempotent: a no-op once ./certs
	# has been populated (see generate_ssl_certs).
	generate_ssl_certs

	if [[ -n ${DB_SETUP:-} ]]; then
	  DB_SETUP_USER="${DB_SETUP_USER:-root}"
	  DB_SETUP_PASS="${DB_SETUP_PASS:-root}"
	fi

	if [[ -n ${SIMPLERISK_CSRF_SECRET:-} ]]; then
	  set_csrf_secret
	fi

	if [ "$docker_managed_config" = true ]; then
		# shellcheck disable=SC2015
		[[ "${DB_SETUP:-}" == "delete" ]] && delete_db || true
		# shellcheck disable=SC2015
		[[ "${DB_SETUP:-}" = automatic* ]] && db_setup || true
		set_mail_settings
		if db_already_provisioned; then
			repair_simplerisk_base_url
		fi
	fi

	unset_variables
	exec "$@"
}

_main "$@"
