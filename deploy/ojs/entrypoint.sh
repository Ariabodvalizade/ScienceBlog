#!/bin/sh
# Render config.inc.php from environment variables, then hand over to the
# official PKP entrypoint/command.
set -e
php /usr/local/bin/journal-configure.php
exec docker-php-entrypoint "$@"
