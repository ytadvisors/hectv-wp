FROM wordpress:php8.2-apache@sha256:680df6fd52a1ec7948deb6ca5fa57f1bca0d5d062396ffd0c57b8b4f24adc23f

ARG APP_REVISION=unknown

RUN rm -rf /var/www/html/* \
    && cp -a /usr/src/wordpress/. /var/www/html/ \
    && a2enmod headers rewrite

COPY wp-content /var/www/html/wp-content
COPY vendor /var/www/html/vendor
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
