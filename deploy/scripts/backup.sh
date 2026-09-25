#!/usr/bin/env bash
# Daily backup: database dump, uploaded files, public files and an encrypted
# copy of .env. Keeps BACKUP_RETENTION_DAYS days; optional off-site copy.
#   crontab: 30 2 * * * cd ~/journal/deploy && ./scripts/backup.sh >> ~/backup.log 2>&1
. "$(dirname "$0")/lib.sh"

[[ -n "${BACKUP_GPG_PASSPHRASE:-}" ]] || die "Set BACKUP_GPG_PASSPHRASE in .env"
root="${BACKUP_DIR:-./backups}"
dest="$root/$(date +%Y-%m-%d_%H%M)"
mkdir -p "$dest"
dest="$(cd "$dest" && pwd)"

info "Database → $dest/db.sql.gz"
compose exec -T db sh -c 'exec mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --quick --routines --triggers "$MARIADB_DATABASE"' | gzip -9 > "$dest/db.sql.gz"

info "Files → $dest/files.tar.gz, public.tar.gz"
compose run --rm --no-deps --user root -v "$dest:/backup" --entrypoint sh ojs -c \
  'tar czf /backup/files.tar.gz -C /var/www files && tar czf /backup/public.tar.gz -C /var/www/html public'

info "Configuration → $dest/env.tar.gz.gpg (encrypted)"
tar czf - .env | gpg --batch --yes --symmetric --cipher-algo AES256 --passphrase "$BACKUP_GPG_PASSPHRASE" -o "$dest/env.tar.gz.gpg"

( cd "$dest" && sha256sum ./* > SHA256SUMS )

find "$root" -mindepth 1 -maxdepth 1 -type d -mtime +"${BACKUP_RETENTION_DAYS:-14}" -exec rm -rf {} +

if [[ -n "${RCLONE_REMOTE:-}" ]]; then
  info "Off-site copy → $RCLONE_REMOTE"
  rclone copy "$dest" "$RCLONE_REMOTE/$(basename "$dest")"
fi
ok "Backup complete: $dest ($(du -sh "$dest" | cut -f1))"
