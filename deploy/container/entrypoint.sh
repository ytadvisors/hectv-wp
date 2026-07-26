#!/bin/sh
set -eu

required_vars="
RDS_DB_NAME
RDS_USERNAME
RDS_PASSWORD
RDS_HOSTNAME
AUTH_KEY
SECURE_AUTH_KEY
LOGGED_IN_KEY
NONCE_KEY
AUTH_SALT
SECURE_AUTH_SALT
LOGGED_IN_SALT
NONCE_SALT
JWT_AUTH_SECRET_KEY
"

for name in $required_vars; do
  eval "value=\${$name:-}"
  if [ -z "$value" ]; then
    echo "Required environment variable is missing: $name" >&2
    exit 1
  fi
done

mkdir -p /var/www/html/wp-content/uploads
chown www-data:www-data /var/www/html/wp-content/uploads

if [ "${HECTV_DISABLE_OUTBOUND:-0}" = "1" ]; then
  cat >/usr/local/etc/php/conf.d/hectv-no-mail.ini <<'EOF'
sendmail_path = "/bin/true"
mail.add_x_header = Off
EOF
fi

if [ "${HECTV_ENVIRONMENT:-}" = "staging" ]; then
  case "${STRIPE_KEY:-}" in
    pk_test_*) ;;
    *) echo "Staging requires a Stripe test publishable key." >&2; exit 1 ;;
  esac
  case "${STRIPE_SECRET_KEY:-}" in
    sk_test_*) ;;
    *) echo "Staging requires a Stripe test secret key." >&2; exit 1 ;;
  esac
fi

exec "$@"
