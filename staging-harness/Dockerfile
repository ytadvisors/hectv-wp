FROM wordpress:6.8-php8.2-apache@sha256:1e6215749283955d5c9ffea6c297651ed23cdfdbb91677ad7abd705b2682f2cf

# Fixture-only local staging image. Copy the exact repository code into the
# WordPress seed tree so the official entrypoint initializes a fresh named
# volume without nested read-only bind mounts.
COPY wp-content/plugins/advanced-custom-fields /usr/src/wordpress/wp-content/plugins/advanced-custom-fields
COPY wp-content/plugins/acf-repeater /usr/src/wordpress/wp-content/plugins/acf-repeater
COPY staging-harness/mu-plugins /usr/src/wordpress/wp-content/mu-plugins
COPY wp-content/mu-plugins/hectv-cms-fields.php /usr/src/wordpress/wp-content/mu-plugins/hectv-cms-fields.php
COPY wp-content/mu-plugins/hectv-cms-fields /usr/src/wordpress/wp-content/mu-plugins/hectv-cms-fields
