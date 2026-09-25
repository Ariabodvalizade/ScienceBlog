# OJS image for the journal: official PKP image + our theme and plugin.
# Build context is the repository root (see docker-compose files).
ARG OJS_VERSION=3_5_0-5
FROM pkpofficial/ojs:${OJS_VERSION}

USER root

COPY deploy/ojs/php.ini /usr/local/etc/php/conf.d/zz-journal.ini
COPY deploy/ojs/apache-proxy.conf /etc/apache2/conf-enabled/zz-journal-proxy.conf
COPY deploy/ojs/configure.php /usr/local/bin/journal-configure.php
COPY deploy/ojs/entrypoint.sh /usr/local/bin/journal-entrypoint
RUN chmod 0755 /usr/local/bin/journal-entrypoint

COPY --chown=www-data:www-data plugins/themes/meridian /var/www/html/plugins/themes/meridian
COPY --chown=www-data:www-data plugins/generic/scholarlyReader /var/www/html/plugins/generic/scholarlyReader
COPY --chown=www-data:www-data plugins/generic/authorPages /var/www/html/plugins/generic/authorPages

USER www-data

ENTRYPOINT ["journal-entrypoint"]
CMD ["/bin/sh", "-c", "\"${PKP_CMD}\""]
