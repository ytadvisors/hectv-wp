#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"

require_command aws
require_command mysql
require_command mysqldump
require_value DB_HOST
require_value PROD_DB_NAME
require_value DB_ADMIN_USER
require_value DB_ADMIN_PASSWORD
assert_staging_database

if [[ "$PROD_DB_NAME" == "$STAGING_DB_NAME" ]]; then
  echo "Source and staging database names must differ." >&2
  exit 1
fi

desired="$(service_desired_count)"
if [[ "$desired" != "0" ]]; then
  echo "Refusing refresh while staging service desiredCount=$desired; stop staging first." >&2
  exit 1
fi

snapshot_id="hectv-pre-staging-refresh-$(date -u +%Y%m%dT%H%M%SZ)"
cluster_id="${AURORA_CLUSTER_ID:-hectv-db-cluster}"

echo "Creating Aurora safety snapshot: $snapshot_id"
aws rds create-db-cluster-snapshot \
  --db-cluster-identifier "$cluster_id" \
  --db-cluster-snapshot-identifier "$snapshot_id" >/dev/null
aws rds wait db-cluster-snapshot-available \
  --db-cluster-snapshot-identifier "$snapshot_id"

dump_file="$(mktemp "${TMPDIR:-/tmp}/hectv-staging.XXXXXX.sql")"
cleanup() {
  rm -f "$dump_file"
}
trap cleanup EXIT
chmod 600 "$dump_file"

echo "Taking a transaction-consistent production dump."
MYSQL_PWD="$DB_ADMIN_PASSWORD" mysqldump \
  --host="$DB_HOST" \
  --user="$DB_ADMIN_USER" \
  --single-transaction \
  --quick \
  --skip-lock-tables \
  --routines \
  --triggers \
  --events \
  --default-character-set=utf8 \
  "$PROD_DB_NAME" >"$dump_file"

echo "Recreating isolated staging database: $STAGING_DB_NAME"
MYSQL_PWD="$DB_ADMIN_PASSWORD" mysql \
  --host="$DB_HOST" \
  --user="$DB_ADMIN_USER" \
  --execute="DROP DATABASE IF EXISTS \`$STAGING_DB_NAME\`; CREATE DATABASE \`$STAGING_DB_NAME\` CHARACTER SET utf8 COLLATE utf8_general_ci;"

MYSQL_PWD="$DB_ADMIN_PASSWORD" mysql \
  --host="$DB_HOST" \
  --user="$DB_ADMIN_USER" \
  "$STAGING_DB_NAME" <"$dump_file"

table_count="$(
  MYSQL_PWD="$DB_ADMIN_PASSWORD" mysql \
    --host="$DB_HOST" \
    --user="$DB_ADMIN_USER" \
    --batch --skip-column-names \
    --execute="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$STAGING_DB_NAME';"
)"

if [[ ! "$table_count" =~ ^[0-9]+$ ]] || (( table_count == 0 )); then
  echo "Staging refresh verification failed: no tables found." >&2
  exit 1
fi

echo "Staging database refresh complete: $table_count tables."
echo "Safety snapshot: $snapshot_id"

