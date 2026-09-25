#!/usr/bin/env bash
# Stop the local demo (data is kept for the next ./start.sh).
set -euo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
docker compose -f "$HERE/../deploy/docker-compose.dev.yml" --env-file "$HERE/snapshot/env.local" down
echo "✓ Stopped. Start again with ./start.sh"
