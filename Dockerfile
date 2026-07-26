FROM php:8.2-apache

ARG APP_REVISION=unknown

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 gd mysqli zip \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html
COPY deploy/container/php.ini /usr/local/etc/php/conf.d/hectv.ini
COPY deploy/container/entrypoint.sh /usr/local/bin/hectv-entrypoint

RUN chmod 0755 /usr/local/bin/hectv-entrypoint \
    && mkdir -p /var/www/html/wp-content/uploads \
    && chown -R www-data:www-data /var/www/html/wp-content

LABEL org.opencontainers.image.revision="${APP_REVISION}"

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
  CMD curl --fail --silent http://127.0.0.1/wp-json/ >/dev/null || exit 1

ENTRYPOINT ["hectv-entrypoint"]
CMD ["apache2-foreground"]
