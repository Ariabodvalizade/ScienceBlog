#!/usr/bin/env bash
# Upgrade OJS to the version in OJS_VERSION (.env): backup, build, migrate, restart.
. "$(dirname "$0")/lib.sh"

info "Backing up first"
"$DEPLOY_DIR/scripts/backup.sh"

info "Building OJS ${OJS_VERSION}"
compose build ojs

info "Running the OJS upgrade"
compose stop worker scheduler
compose run --rm --no-deps ojs php tools/upgrade.php check
compose run --rm --no-deps ojs php tools/upgrade.php upgrade

compose up -d
"$DEPLOY_DIR/scripts/clear-cache.sh"
ok "Upgrade complete. Run the smoke test in docs/deployment-vps.md."
