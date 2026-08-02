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
if [ "${HECTV_ENVIRONMENT:-}" != "production" ] && [ "${HECTV_PUBLIC_READ_ONLY:-0}" != "1" ]; then
  chown www-data:www-data /var/www/html/wp-content/uploads
fi

if [ "${HECTV_PUBLIC_READ_ONLY:-0}" = "1" ]; then
  case "$RDS_DB_NAME" in
    *_staging) ;;
    *) echo "Public read-only mode requires an _staging database." >&2; exit 1 ;;
  esac

  php <<'PHP'
<?php
$db = @new mysqli(
    getenv('RDS_HOSTNAME'),
    getenv('RDS_USERNAME'),
    getenv('RDS_PASSWORD'),
    getenv('RDS_DB_NAME')
);
if ($db->connect_errno) {
    fwrite(STDERR, "Could not verify public staging database grants.\n");
    exit(1);
}
$result = $db->query('SHOW GRANTS FOR CURRENT_USER');
$select_only = false;
while ($row = $result->fetch_row()) {
    $grant = $row[0];
    if (preg_match('/^GRANT USAGE ON \*\.\*/i', $grant)) {
        continue;
    }
    if (preg_match('/^GRANT SELECT ON `?' . preg_quote(getenv('RDS_DB_NAME'), '/') . '`?\.\*/i', $grant)) {
        $select_only = true;
        continue;
    }
    fwrite(STDERR, "Public staging database user has a non-read-only grant.\n");
    exit(1);
}
if (!$select_only) {
    fwrite(STDERR, "Public staging database user lacks its SELECT-only grant.\n");
    exit(1);
}
echo "Public staging database SELECT-only grant verified.\n";
PHP
fi

if [ "${HECTV_ENVIRONMENT:-}" = "production" ]; then
  uploads_root="/var/www/html/wp-content/uploads"
  probe_dir="$(
    find "$uploads_root" \
      -mindepth 2 -maxdepth 2 -type d \
      -path "$uploads_root/[0-9][0-9][0-9][0-9]/[0-9][0-9]" \
      -print 2>/dev/null |
      sort |
      tail -1
  )"
  if [ -z "$probe_dir" ]; then
    probe_dir="$uploads_root"
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

if [ "${HECTV_ENVIRONMENT:-}" = "staging" ] && [ "${HECTV_DISABLE_PAYMENTS:-0}" != "1" ]; then
  case "${STRIPE_KEY:-}" in
    pk_test_*) ;;
    *) echo "Staging requires a Stripe test publishable key." >&2; exit 1 ;;
  esac
  case "${STRIPE_SECRET_KEY:-}" in
    sk_test_*) ;;
    *) echo "Staging requires a Stripe test secret key." >&2; exit 1 ;;
  esac
fi

if [ "${HECTV_DISABLE_PAYMENTS:-0}" = "1" ]; then
  unset STRIPE_KEY STRIPE_SECRET_KEY
fi

exec "$@"
