#!/usr/bin/env bash
# Obtain the first Let's Encrypt certificate (HTTP-01, standalone).
# Port 80 must be free, so run this before starting the web service.
#   ./scripts/init-ssl.sh            # production certificate
#   ./scripts/init-ssl.sh --staging  # test against the staging CA first
. "$(dirname "$0")/lib.sh"

[[ -n "${JOURNAL_DOMAIN:-}" && -n "${LETSENCRYPT_EMAIL:-}" ]] || die "Set JOURNAL_DOMAIN and LETSENCRYPT_EMAIL in .env"

extra=()
[[ "${1:-}" == "--staging" ]] && extra+=(--staging)

compose stop web >/dev/null 2>&1 || true
info "Requesting a certificate for $JOURNAL_DOMAIN"
compose run --rm --no-deps -p 80:80 --entrypoint certbot certbot certonly \
  --standalone --non-interactive --agree-tos --no-eff-email \
  --email "$LETSENCRYPT_EMAIL" -d "$JOURNAL_DOMAIN" "${extra[@]}"
ok "Certificate stored; renewals run automatically in the certbot service"
