#!/usr/bin/env bash
set -euo pipefail

: "${AWS_BIN:=/opt/homebrew/bin/aws}"
: "${AWS_PROFILE:=hecadmin}"
: "${AWS_REGION:=us-east-2}"
: "${ECS_CLUSTER:=hectv-wp-production}"
: "${ECS_SERVICE:=hectv-wp-production}"
: "${ORIGIN_HEALTH_URL:=https://prod-wp-ecs.hectv.org/healthz}"

if [[ "$ECS_CLUSTER" != "hectv-wp-production" ]] || [[ "$ECS_SERVICE" != "hectv-wp-production" ]]; then
  echo "Refusing non-production-migration ECS target." >&2
  exit 1
fi

task_definition="$(
  "$AWS_BIN" ecs describe-services \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --cluster "$ECS_CLUSTER" \
    --services "$ECS_SERVICE" \
    --query 'services[0].taskDefinition' \
    --output text
)"

validation_flags="$(
  "$AWS_BIN" ecs describe-task-definition \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --task-definition "$task_definition" \
    --query 'taskDefinition.containerDefinitions[0].environment[?name==`DISABLE_WP_CRON` || name==`HECTV_DISABLE_OUTBOUND` || name==`HECTV_DISABLE_PAYMENTS`].[name,value]' \
    --output text
)"
grep -q $'DISABLE_WP_CRON\t1' <<<"$validation_flags"
grep -q $'HECTV_DISABLE_OUTBOUND\t1' <<<"$validation_flags"
grep -q $'HECTV_DISABLE_PAYMENTS\t1' <<<"$validation_flags"

"$AWS_BIN" ecs update-service \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --cluster "$ECS_CLUSTER" \
  --service "$ECS_SERVICE" \
  --desired-count 1 >/dev/null
"$AWS_BIN" ecs wait services-stable \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --cluster "$ECS_CLUSTER" \
  --services "$ECS_SERVICE"

curl --fail --silent --show-error --retry 12 --retry-delay 5 "$ORIGIN_HEALTH_URL"
echo
echo "Parallel production origin is healthy with cron and outbound mail disabled."
