#!/usr/bin/env bash
# Delete all local demo data and start again from the original snapshot.
set -euo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
docker compose -f "$HERE/../deploy/docker-compose.dev.yml" --env-file "$HERE/snapshot/env.local" down -v
exec "$HERE/start.sh"
