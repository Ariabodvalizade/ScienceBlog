#!/usr/bin/env bash
# Deploy the latest theme/plugin code from git (no OJS version change).
. "$(dirname "$0")/lib.sh"

info "Pulling latest code"
git -C "$DEPLOY_DIR/.." pull --ff-only

info "Rebuilding and restarting"
compose up -d --build ojs worker scheduler web
register_plugins
"$DEPLOY_DIR/scripts/clear-cache.sh"
ok "Deployed $(git -C "$DEPLOY_DIR/.." rev-parse --short HEAD)"
