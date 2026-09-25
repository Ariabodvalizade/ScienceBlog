#!/usr/bin/env bash
# Export the seeded dev stack (database + uploaded/public files) into
# local-demo/snapshot/, so the demo can run anywhere with Docker only
# (see local-demo/README.md). Dev/demo data only — never production.
#   dev/export-snapshot.sh
set -euo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT="$HERE/.."
DEPLOY="$ROOT/deploy"
OUT="$ROOT/local-demo/snapshot"
COMPOSE=(docker compose -f "$DEPLOY/docker-compose.dev.yml" --env-file "$DEPLOY/.env.dev")

mkdir -p "$OUT"

echo "→ Database"
"${COMPOSE[@]}" exec -T db sh -c 'exec mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --skip-dump-date --routines --triggers --ignore-table-data="$MARIADB_DATABASE.sessions" "$MARIADB_DATABASE"' \
  | gzip -9n > "$OUT/db.sql.gz"

echo "→ Uploaded files and public files"
"${COMPOSE[@]}" exec -T --user root ojs sh -c 'tar czf - -C /var/www files' > "$OUT/files.tar.gz"
"${COMPOSE[@]}" exec -T --user root ojs sh -c 'tar czf - -C /var/www/html public' > "$OUT/public.tar.gz"

echo "→ Settings"
app_key="$(grep '^OJS_APP_KEY=' "$DEPLOY/.env.dev" | cut -d= -f2- | tr -d '"')"
cat > "$OUT/env.local" <<ENV
# Demo settings for local-demo (dev only). The app key must match the database dump.
OJS_INSTALLED=On
OJS_APP_KEY="$app_key"
ENV

ls -lh "$OUT"
echo "✓ Snapshot written to local-demo/snapshot/"
