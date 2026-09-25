#!/usr/bin/env bash
# First-time OJS installation, non-interactive. Run once, after
#   docker compose up -d db ojs web
# It posts the OJS installer form, then stores the generated app key in .env,
# marks OJS as installed and starts the remaining services.
. "$(dirname "$0")/lib.sh"

[[ "${OJS_INSTALLED:-Off}" == "On" ]] && die "OJS_INSTALLED=On in .env — already installed."
[[ -n "${OJS_ADMIN_PASSWORD:-}" ]] || die "Set OJS_ADMIN_PASSWORD (./scripts/generate-secrets.sh)"

url="https://${JOURNAL_DOMAIN}"
# CURL_EXTRA="-k" allows testing with a self-signed certificate
curl_opts=(${CURL_EXTRA:-})
info "Waiting for $url"
for _ in $(seq 1 60); do
  curl -fsS "${curl_opts[@]}" -o /dev/null "$url/index/en/install" && break
  sleep 2
done

info "Running the OJS installer"
out="$(mktemp)"
curl -fsS "${curl_opts[@]}" "$url/index/en/install/install" \
  --data-urlencode "installing=0" \
  --data-urlencode "locale=en" \
  --data-urlencode "timeZone=${OJS_TIME_ZONE:-UTC}" \
  --data-urlencode "filesDir=/var/www/files" \
  --data-urlencode "adminUsername=${OJS_ADMIN_USER:-admin}" \
  --data-urlencode "adminPassword=${OJS_ADMIN_PASSWORD}" \
  --data-urlencode "adminPassword2=${OJS_ADMIN_PASSWORD}" \
  --data-urlencode "adminEmail=${OJS_ADMIN_EMAIL}" \
  --data-urlencode "databaseDriver=mysqli" \
  --data-urlencode "databaseHost=db" \
  --data-urlencode "databaseUsername=${OJS_DB_USER:-ojs}" \
  --data-urlencode "databasePassword=${OJS_DB_PASSWORD}" \
  --data-urlencode "databaseName=${OJS_DB_NAME:-ojs}" \
  --data-urlencode "oaiRepositoryId=${JOURNAL_DOMAIN}" \
  --data-urlencode "enableBeacon=0" \
  -o "$out"
grep -q "has completed successfully" "$out" || die "Installer did not report success; see $out"

app_key="$(compose exec -T ojs sh -c "grep '^app_key' config.inc.php | cut -d= -f2- | tr -d ' \"'")"
[[ -n "$app_key" ]] || die "Could not read the generated app_key"
set_env OJS_APP_KEY "$app_key"
set_env OJS_INSTALLED On

info "Restarting with installed = On"
compose up -d
for _ in $(seq 1 30); do
  compose exec -T ojs test -f config.inc.php 2>/dev/null && break
  sleep 2
done
info "Registering the theme and plugins"
register_plugins
ok "OJS installed. Log in at $url/index/login as ${OJS_ADMIN_USER:-admin}."
echo "Now remove OJS_ADMIN_PASSWORD from .env and follow docs/ojs-configuration.md."
