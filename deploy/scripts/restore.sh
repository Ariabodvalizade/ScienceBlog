#!/usr/bin/env bash
# Restore a backup made by backup.sh. DESTRUCTIVE: replaces the database and files.
#   ./scripts/restore.sh backups/2026-09-25_0230
. "$(dirname "$0")/lib.sh"

src="${1:-}"
[[ -d "$src" ]] || die "Usage: $0 <backup directory>"
src="$(cd "$src" && pwd)"
( cd "$src" && sha256sum -c SHA256SUMS --quiet ) || die "Checksum mismatch in $src"

read -r -p "This replaces the current database and files with $src. Type RESTORE to continue: " answer
[[ "$answer" == "RESTORE" ]] || die "Aborted"

info "Stopping the application"
compose stop ojs worker scheduler

info "Restoring the database"
gunzip -c "$src/db.sql.gz" | compose exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"'

info "Restoring files"
compose run --rm --no-deps --user root -v "$src:/backup:ro" --entrypoint sh ojs -c \
  'rm -rf /var/www/files/* /var/www/html/public/* \
   && tar xzf /backup/files.tar.gz -C /var/www \
   && tar xzf /backup/public.tar.gz -C /var/www/html \
   && chown -R www-data:www-data /var/www/files /var/www/html/public'

compose up -d
ok "Restore complete. Clear caches in Administration if pages look stale."
