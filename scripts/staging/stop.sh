#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"

require_command aws
assert_staging_service

aws ecs update-service \
  --cluster "$ECS_CLUSTER" \
  --service "$ECS_SERVICE" \
  --desired-count 0 >/dev/null

for _ in {1..60}; do
  running="$(
    aws ecs describe-services \
      --cluster "$ECS_CLUSTER" \
      --services "$ECS_SERVICE" \
      --query 'services[0].runningCount' \
      --output text
  )"
  if [[ "$running" == "0" ]]; then
    echo "Staging is stopped; database storage remains available."
    exit 0
  fi
  sleep 5
done

echo "Timed out waiting for staging tasks to stop." >&2
exit 1
