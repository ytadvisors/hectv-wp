#!/usr/bin/env bash
set -euo pipefail

: "${AWS_BIN:=/opt/homebrew/bin/aws}"
: "${AWS_PROFILE:=hecadmin}"
: "${AWS_REGION:=us-east-2}"
: "${ECS_CLUSTER:=hectv-wp-production}"
: "${ECS_SERVICE:=hectv-wp-production}"
: "${HOSTED_ZONE_ID:=Z292F4Q328R4CJ}"
: "${PRODUCTION_HOSTNAME:=prod-wp.hectv.org}"
: "${EXPECTED_EB_CNAME:=hectv-wp-prod.us-east-2.elasticbeanstalk.com}"
: "${ROLLBACK_STATE_FILE:?Set ROLLBACK_STATE_FILE to the cutover backup file.}"
: "${ROLLBACK_APPROVED:=NO}"

if [[ "$ROLLBACK_APPROVED" != "YES" ]]; then
  echo "Set ROLLBACK_APPROVED=YES to restore Elastic Beanstalk DNS." >&2
  exit 1
fi
if [[ ! -f "$ROLLBACK_STATE_FILE" ]]; then
  echo "Rollback state does not exist: $ROLLBACK_STATE_FILE" >&2
  exit 1
fi

name="$(jq -r '.Name' "$ROLLBACK_STATE_FILE")"
type="$(jq -r '.Type' "$ROLLBACK_STATE_FILE")"
target="$(jq -r '.ResourceRecords[0].Value' "$ROLLBACK_STATE_FILE")"
if [[ "$name" != "${PRODUCTION_HOSTNAME}." ]] || [[ "$type" != "CNAME" ]] || [[ "$target" != "$EXPECTED_EB_CNAME" ]]; then
  echo "Rollback state does not match the approved Elastic Beanstalk target." >&2
  exit 1
fi

change_file="$(mktemp "${TMPDIR:-/tmp}/hectv-rollback.XXXXXX.json")"
trap 'rm -f "$change_file"' EXIT
jq -n \
  --slurpfile record "$ROLLBACK_STATE_FILE" \
  '{Changes:[{Action:"UPSERT",ResourceRecordSet:$record[0]}]}' >"$change_file"

change_id="$(
  "$AWS_BIN" route53 change-resource-record-sets \
    --profile "$AWS_PROFILE" \
    --hosted-zone-id "$HOSTED_ZONE_ID" \
    --change-batch "file://$change_file" \
    --query 'ChangeInfo.Id' \
    --output text
)"
"$AWS_BIN" route53 wait resource-record-sets-changed \
  --profile "$AWS_PROFILE" \
  --id "$change_id"

curl --fail --silent --show-error --retry 12 --retry-delay 5 \
  "https://${PRODUCTION_HOSTNAME}/wp-json/" >/dev/null

"$AWS_BIN" ecs update-service \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --cluster "$ECS_CLUSTER" \
  --service "$ECS_SERVICE" \
  --desired-count 0 >/dev/null

echo "Elastic Beanstalk DNS restored and ECS scale-down requested."
