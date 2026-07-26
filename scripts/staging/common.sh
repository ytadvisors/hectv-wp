#!/usr/bin/env bash
set -euo pipefail

: "${AWS_PROFILE:=hecadmin}"
: "${AWS_REGION:=us-east-2}"
: "${ECS_CLUSTER:=hectv-wp}"
: "${ECS_SERVICE:=hectv-wp-staging}"
: "${STAGING_DB_NAME:=hectv_staging}"
: "${STAGING_URL:=https://staging-wp.hectv.org}"

export AWS_PROFILE AWS_REGION ECS_CLUSTER ECS_SERVICE STAGING_DB_NAME STAGING_URL

require_command() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "Required command is not installed: $1" >&2
    exit 1
  }
}

require_value() {
  local name="$1"
  if [[ -z "${!name:-}" ]]; then
    echo "Required environment variable is missing: $name" >&2
    exit 1
  fi
}

assert_staging_database() {
  if [[ ! "$STAGING_DB_NAME" =~ ^[A-Za-z0-9_]+_staging$ ]] || [[ "$STAGING_DB_NAME" == "hectv" ]]; then
    echo "Refusing unsafe staging database name: $STAGING_DB_NAME" >&2
    exit 1
  fi
}

assert_staging_url() {
  if [[ ! "$STAGING_URL" =~ ^https://[A-Za-z0-9.-]+$ ]]; then
    echo "Refusing unsafe staging URL: $STAGING_URL" >&2
    exit 1
  fi
}

service_desired_count() {
  aws ecs describe-services \
    --cluster "$ECS_CLUSTER" \
    --services "$ECS_SERVICE" \
    --query 'services[0].desiredCount' \
    --output text
}

service_running_count() {
  aws ecs describe-services \
    --cluster "$ECS_CLUSTER" \
    --services "$ECS_SERVICE" \
    --query 'services[0].runningCount' \
    --output text
}
