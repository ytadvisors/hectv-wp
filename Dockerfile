# The reviewed EB-derived staging image contains the licensed/legacy GraphQL
# plugins used by the existing HEC frontend. Keep this source immutable: the
# final runtime remains the PHP 8.2 image below.
FROM 850335719356.dkr.ecr.us-east-2.amazonaws.com/hectv-wp-staging@sha256:0d41405fcd2d1316b2965ec90494b35cc5a219aa3eb9ff576f042318f25c510c AS legacy-plugins

# Match the modern WPGraphQL version exercised by the staging harness. The
# reviewed legacy image above contains WPGraphQL 0.4.0, which throws internal
# resolver errors on PHP 8.2 for taxonomy, category, and nested-menu queries.
FROM debian:bookworm-slim AS wpgraphql
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates unzip \
    && rm -rf /var/lib/apt/lists/*
ADD --checksum=sha256:57746329270f71ca76cc89ddec37a50e15df9f8ab39c7717b133aac358c996bc \
    https://downloads.wordpress.org/plugin/wp-graphql.2.18.0.zip /tmp/wp-graphql.zip
RUN unzip -q /tmp/wp-graphql.zip -d /opt/plugins

FROM wordpress:php8.2-apache@sha256:680df6fd52a1ec7948deb6ca5fa57f1bca0d5d062396ffd0c57b8b4f24adc23f

ARG APP_REVISION=unknown

RUN rm -rf /var/www/html/* \
    && cp -a /usr/src/wordpress/. /var/www/html/ \
    && a2enmod headers rewrite

COPY wp-content /var/www/html/wp-content
COPY vendor /var/www/html/vendor
COPY --from=legacy-plugins /var/www/html/wp-content/plugins/advanced-cron-manager /var/www/html/wp-content/plugins/advanced-cron-manager
COPY --from=legacy-plugins /var/www/html/wp-content/plugins/classic-editor /var/www/html/wp-content/plugins/classic-editor
COPY --from=legacy-plugins /var/www/html/wp-content/plugins/wp-graphiql /var/www/html/wp-content/plugins/wp-graphiql
COPY --from=wpgraphql /opt/plugins/wp-graphql /var/www/html/wp-content/plugins/wp-graphql
COPY --from=legacy-plugins /var/www/html/wp-content/plugins/zapier /var/www/html/wp-content/plugins/zapier
# This owned compatibility layer replaces legacy wp-graphql-acf. Copying both
# lets the legacy plugin overwrite Post.postDetails with an incomplete type.
COPY staging-harness/mu-plugins/hectv-graphql-compat.php /var/www/html/wp-content/mu-plugins/hectv-graphql-compat.php
COPY wp-config.php .htaccess /var/www/html/
COPY deploy/container/php.ini /usr/local/etc/php/conf.d/hectv.ini
COPY deploy/container/entrypoint.sh /usr/local/bin/hectv-entrypoint
COPY deploy/container/healthz /var/www/html/healthz

RUN chmod 0755 /usr/local/bin/hectv-entrypoint \
    && mkdir -p /var/www/html/wp-content/uploads \
    && chown -R www-data:www-data /var/www/html/wp-content

LABEL org.opencontainers.image.revision="${APP_REVISION}"

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
  CMD curl --fail --silent http://127.0.0.1/healthz >/dev/null || exit 1

ENTRYPOINT ["hectv-entrypoint"]
CMD ["apache2-foreground"]
