#!/usr/bin/env bash
# Seed the dev stack with the demo journal (dev only; never run in production).
#   dev/seed.sh [base_url]
set -euo pipefail
BASE_URL="${1:-http://localhost:8080}"
HERE="$(cd "$(dirname "$0")" && pwd)"
DEPLOY="$HERE/../deploy"
COMPOSE=(docker compose -f "$DEPLOY/docker-compose.dev.yml" --env-file "$DEPLOY/.env.dev")
export NODE_PATH="${NODE_PATH:-$(npm root -g)}"

node "$HERE/seed/build.mjs"
node "$HERE/seed/seed.mjs" "$BASE_URL"
node "$HERE/seed/sql.mjs" "$BASE_URL"

echo "→ Importing demo issues (native XML)"
"${COMPOSE[@]}" cp "$HERE/out/demo-issues.xml" ojs:/tmp/demo-issues.xml
"${COMPOSE[@]}" exec -T ojs php tools/importExport.php NativeImportExportPlugin import /tmp/demo-issues.xml djas admin 2>&1 | grep -v "PHP Notice"
echo "→ Importing manuscripts still in the workflow"
"${COMPOSE[@]}" cp "$HERE/out/demo-inprogress.xml" ojs:/tmp/demo-inprogress.xml
"${COMPOSE[@]}" exec -T ojs php tools/importExport.php NativeImportExportPlugin import /tmp/demo-inprogress.xml djas admin 2>&1 | grep -v "PHP Notice"
# The default "Articles" section (abbrev ART) is reused by the import; rename it.
"${COMPOSE[@]}" exec -T db mariadb -uojs -pojs-dev-password ojs -e \
  "UPDATE section_settings ss JOIN section_settings a ON a.section_id = ss.section_id AND a.setting_name = 'abbrev' AND a.setting_value = 'ART'
   SET ss.setting_value = 'Original Research' WHERE ss.setting_name = 'title';"
echo "→ Demo editorial board and policy pages"
"${COMPOSE[@]}" exec -T db mariadb -uojs -pojs-dev-password ojs < "$HERE/out/demo.sql"
"${COMPOSE[@]}" cp "$HERE/out/profileImage-201.png" ojs:/var/www/html/public/site/profileImage-201.png
"${COMPOSE[@]}" exec -T ojs php tools/rebuildSearchIndex.php >/dev/null 2>&1 || true
echo "✓ Demo content ready at $BASE_URL/djas"
