#!/usr/bin/env bash
# Fill empty secrets in .env with strong random values. Existing values are kept.
. "$(dirname "$0")/lib.sh"

rand() { openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | head -c "${1:-40}"; }

for key in OJS_SALT OJS_API_KEY_SECRET OJS_ALTCHA_HMACKEY OJS_DB_PASSWORD MARIADB_ROOT_PASSWORD BACKUP_GPG_PASSPHRASE OJS_ADMIN_PASSWORD; do
  if [[ -z "${!key:-}" ]]; then
    set_env "$key" "$(rand 40)"
    ok "$key generated"
  fi
done
echo "Store OJS_ADMIN_PASSWORD and BACKUP_GPG_PASSPHRASE in your password manager."
