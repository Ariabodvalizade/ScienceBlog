#!/usr/bin/env bash
# Clear OJS compiled CSS and template caches in the dev stack (after LESS/template edits).
set -euo pipefail
DEPLOY="$(cd "$(dirname "$0")/../deploy" && pwd)"
docker compose -f "$DEPLOY/docker-compose.dev.yml" --env-file "$DEPLOY/.env.dev" exec -T ojs \
  sh -c 'rm -f cache/*.css; rm -rf cache/t_compile/* cache/t_cache/* cache/scholarlyReader'
