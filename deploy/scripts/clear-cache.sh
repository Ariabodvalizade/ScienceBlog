#!/usr/bin/env bash
# Clear OJS template, CSS, locale and full-text caches (after deploys/upgrades).
. "$(dirname "$0")/lib.sh"
compose exec -T ojs sh -c 'rm -rf cache/t_compile/* cache/t_cache/* cache/*.css cache/opcache/* cache/scholarlyReader'
ok "Caches cleared"
