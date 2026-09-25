#!/usr/bin/env bash
# Run the OJS web installer non-interactively against the dev stack.
#   dev/install.sh [base_url]
# Afterwards it persists the generated app_key and marks OJS as installed
# (written to deploy/.env.dev, which docker-compose.dev.yml reads).
set -euo pipefail

BASE_URL="${1:-http://localhost:8080}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin-dev-Password1}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.org}"
HERE="$(cd "$(dirname "$0")" && pwd)"
DEPLOY="$HERE/../deploy"
COMPOSE=(docker compose -f "$DEPLOY/docker-compose.dev.yml" --env-file "$DEPLOY/.env.dev")

touch "$DEPLOY/.env.dev"

echo "→ Running installer at $BASE_URL"
curl -fsS "$BASE_URL/index/en/install/install" \
  --data-urlencode "installing=0" \
  --data-urlencode "locale=en" \
  --data-urlencode "timeZone=UTC" \
  --data-urlencode "filesDir=/var/www/files" \
  --data-urlencode "adminUsername=$ADMIN_USER" \
  --data-urlencode "adminPassword=$ADMIN_PASSWORD" \
  --data-urlencode "adminPassword2=$ADMIN_PASSWORD" \
  --data-urlencode "adminEmail=$ADMIN_EMAIL" \
  --data-urlencode "databaseDriver=mysqli" \
  --data-urlencode "databaseHost=db" \
  --data-urlencode "databaseUsername=ojs" \
  --data-urlencode "databasePassword=ojs-dev-password" \
  --data-urlencode "databaseName=ojs" \
  --data-urlencode "oaiRepositoryId=localhost" \
  --data-urlencode "enableBeacon=0" \
  -o /tmp/ojs-install.html

if ! grep -q "installer.installationComplete\|Installation of OJS has completed successfully\|has completed successfully" /tmp/ojs-install.html; then
  echo "Installer did not report success; see /tmp/ojs-install.html" >&2
  exit 1
fi

APP_KEY="$("${COMPOSE[@]}" exec -T ojs sh -c "grep '^app_key' config.inc.php | cut -d= -f2- | tr -d ' '")"
{
  echo "OJS_INSTALLED=On"
  echo "OJS_APP_KEY=$APP_KEY"
} > "$DEPLOY/.env.dev"

echo "→ Recreating the OJS container with installed = On"
"${COMPOSE[@]}" up -d ojs
echo "✓ Installed. Log in at $BASE_URL/index/login as $ADMIN_USER"
