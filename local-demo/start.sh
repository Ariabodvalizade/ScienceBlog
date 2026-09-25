#!/usr/bin/env bash
# Run the demo journal locally with Docker only.
#   ./start.sh            → http://localhost:8080/djas
# First run: builds the OJS image, restores the demo database and files.
# Later runs: just starts the containers (your changes are kept).
set -euo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$HERE/.." && pwd)"
COMPOSE=(docker compose -f "$ROOT/deploy/docker-compose.dev.yml" --env-file "$HERE/snapshot/env.local")
URL="http://localhost:8080/djas"

docker info >/dev/null 2>&1 || { echo "✗ Docker is not running. Start Docker Desktop and try again." >&2; exit 1; }

echo "→ Building the OJS image (first run downloads ~1 GB)"
"${COMPOSE[@]}" build ojs

echo "→ Starting the database"
"${COMPOSE[@]}" up -d db mailpit
for _ in $(seq 1 90); do
  [[ "$("${COMPOSE[@]}" ps db --format '{{.Health}}' 2>/dev/null)" == "healthy" ]] && break
  sleep 2
done

tables="$("${COMPOSE[@]}" exec -T db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = \"$MARIADB_DATABASE\""')"
if [[ "${tables//[^0-9]/}" == "0" ]]; then
  echo "→ First run: restoring the demo database and files"
  gunzip -c "$HERE/snapshot/db.sql.gz" | "${COMPOSE[@]}" exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"'
  "${COMPOSE[@]}" run --rm --no-deps --user root -v "$HERE/snapshot:/snapshot:ro" --entrypoint sh ojs -c \
    'tar xzf /snapshot/files.tar.gz -C /var/www && tar xzf /snapshot/public.tar.gz -C /var/www/html && chown -R www-data:www-data /var/www/files /var/www/html/public'
fi

echo "→ Starting OJS"
"${COMPOSE[@]}" up -d ojs
for _ in $(seq 1 90); do
  [[ "$(curl -s -o /dev/null -w '%{http_code}' "$URL" || true)" == "200" ]] && break
  sleep 2
done

cat <<INFO

✓ The demo journal is running

  Website        $URL
  Admin login    http://localhost:8080/index/login   user: admin   password: admin-dev-Password1
  Emails (test)  http://localhost:8025

  Stop:   ./stop.sh      Reset to the original demo:   ./reset.sh
INFO
