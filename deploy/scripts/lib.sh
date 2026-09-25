#!/usr/bin/env bash
# Shared helpers for deploy scripts. Source it: . "$(dirname "$0")/lib.sh"
set -euo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DEPLOY_DIR"

if [[ ! -f .env ]]; then
  echo "Missing $DEPLOY_DIR/.env — copy .env.example and fill it in." >&2
  exit 1
fi

# Load .env without executing it
while IFS='=' read -r key value; do
  [[ -z "$key" || "$key" =~ ^[[:space:]]*# ]] && continue
  value="${value%\"}"; value="${value#\"}"
  export "$key=$value"
done < .env

compose() { docker compose -f "$DEPLOY_DIR/docker-compose.yml" --env-file "$DEPLOY_DIR/.env" "$@"; }

# Set KEY=VALUE in .env (replaces an existing line or appends one)
set_env() {
  local key="$1" value="$2"
  if grep -q "^${key}=" .env; then
    local escaped
    escaped=$(printf '%s' "$value" | sed -e 's/[\/&|]/\\&/g')
    sed -i "s|^${key}=.*|${key}=${escaped}|" .env
  else
    printf '%s=%s\n' "$key" "$value" >> .env
  fi
}

info() { printf '\033[1m→ %s\033[0m\n' "$*"; }
ok() { printf '\033[32m✓ %s\033[0m\n' "$*"; }
die() { printf '\033[31m✗ %s\033[0m\n' "$*" >&2; exit 1; }
