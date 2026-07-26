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

if [ "${HECTV_ENVIRONMENT:-}" = "staging" ] && [ -z "${LOGGED_IN_SALT:-}" ]; then
  echo "Required staging environment variable is missing: LOGGED_IN_SALT" >&2
  exit 1
fi

mkdir -p /var/www/html/wp-content/uploads
if [ "${HECTV_ENVIRONMENT:-}" != "production" ]; then
  chown www-data:www-data /var/www/html/wp-content/uploads
fi

if [ "${HECTV_ENVIRONMENT:-}" = "production" ]; then
  probe_dir="$(
    find /var/www/html/wp-content/uploads \
      -mindepth 2 -maxdepth 2 -type d -print 2>/dev/null |
      sort |
      tail -1
  )"
  if [ -z "$probe_dir" ]; then
    probe_dir="/var/www/html/wp-content/uploads"
  fi
  probe_file="$probe_dir/.hectv-ecs-write-probe-$$"
  if ! (umask 077 && : >"$probe_file"); then
    echo "Production EFS write probe failed in: $probe_dir" >&2
    exit 1
  fi
  rm -f "$probe_file"
  echo "Production EFS write probe passed in: $probe_dir"
fi

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
