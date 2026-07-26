#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"

require_command aws
require_command curl
assert_staging_database
: "${STAGING_DESIRED_COUNT:=1}"
require_value STAGING_HEALTH_URL

if [[ ! "$STAGING_DESIRED_COUNT" =~ ^[12]$ ]]; then
  echo "STAGING_DESIRED_COUNT must be 1 or 2." >&2
  exit 1
fi

aws ecs update-service \
  --cluster "$ECS_CLUSTER" \
  --service "$ECS_SERVICE" \
  --desired-count "$STAGING_DESIRED_COUNT" >/dev/null
aws ecs wait services-stable \
  --cluster "$ECS_CLUSTER" \
  --services "$ECS_SERVICE"

curl --fail --silent --show-error \
  --retry 12 \
  --retry-delay 5 \
  "$STAGING_HEALTH_URL" >/dev/null

echo "Staging is running and healthy at $STAGING_HEALTH_URL"

