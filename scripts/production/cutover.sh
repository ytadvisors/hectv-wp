#!/usr/bin/env bash
set -euo pipefail

: "${AWS_BIN:=/opt/homebrew/bin/aws}"
: "${AWS_PROFILE:=hecadmin}"
: "${AWS_REGION:=us-east-2}"
: "${ECS_CLUSTER:=hectv-wp-production}"
: "${ECS_SERVICE:=hectv-wp-production}"
: "${HOSTED_ZONE_ID:=Z292F4Q328R4CJ}"
: "${PRODUCTION_HOSTNAME:=prod-wp.hectv.org}"
: "${ORIGIN_HOSTNAME:=prod-wp-ecs.hectv.org}"
: "${EXPECTED_EB_CNAME:=hectv-wp-prod.us-east-2.elasticbeanstalk.com}"
: "${AURORA_CLUSTER_ID:=hectv-db-cluster}"
: "${ROLLBACK_STATE_FILE:?Set ROLLBACK_STATE_FILE to an absolute deliverables path.}"
: "${CUTOVER_APPROVED:=NO}"

if [[ "$CUTOVER_APPROVED" != "YES" ]]; then
  echo "Set CUTOVER_APPROVED=YES only after the production origin checklist passes." >&2
  exit 1
fi
if [[ "$ECS_CLUSTER" != "hectv-wp-production" ]] || [[ "$ECS_SERVICE" != "hectv-wp-production" ]]; then
  echo "Refusing an unexpected ECS target." >&2
  exit 1
fi
if [[ "$ROLLBACK_STATE_FILE" != /* ]]; then
  echo "ROLLBACK_STATE_FILE must be an absolute path." >&2
  exit 1
fi

service_json="$(
  "$AWS_BIN" ecs describe-services \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --cluster "$ECS_CLUSTER" \
    --services "$ECS_SERVICE" \
    --output json
)"
desired="$(jq -r '.services[0].desiredCount' <<<"$service_json")"
running="$(jq -r '.services[0].runningCount' <<<"$service_json")"
task_definition="$(jq -r '.services[0].taskDefinition' <<<"$service_json")"
target_group="$(jq -r '.services[0].loadBalancers[0].targetGroupArn' <<<"$service_json")"
if (( desired < 2 )) || [[ "$running" != "$desired" ]]; then
  echo "Production ECS must have at least two healthy running tasks; desired=$desired running=$running." >&2
  exit 1
fi

runtime_flags="$(
  "$AWS_BIN" ecs describe-task-definition \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --task-definition "$task_definition" \
    --query 'taskDefinition.containerDefinitions[0].environment[?name==`DISABLE_WP_CRON` || name==`HECTV_DISABLE_OUTBOUND` || name==`HECTV_DISABLE_PAYMENTS`].[name,value]' \
    --output text
)"
grep -q $'DISABLE_WP_CRON\t0' <<<"$runtime_flags"
grep -q $'HECTV_DISABLE_OUTBOUND\t0' <<<"$runtime_flags"
grep -q $'HECTV_DISABLE_PAYMENTS\t0' <<<"$runtime_flags"

healthy="$(
  "$AWS_BIN" elbv2 describe-target-health \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --target-group-arn "$target_group" \
    --query 'length(TargetHealthDescriptions[?TargetHealth.State==`healthy`])' \
    --output text
)"
if (( healthy < 2 )); then
  echo "Refusing cutover with only $healthy healthy ALB targets." >&2
  exit 1
fi

load_balancer_arn="$(
  "$AWS_BIN" elbv2 describe-target-groups \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --target-group-arns "$target_group" \
    --query 'TargetGroups[0].LoadBalancerArns[0]' \
    --output text
)"
alb_security_group="$(
  "$AWS_BIN" elbv2 describe-load-balancers \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --load-balancer-arns "$load_balancer_arn" \
    --query 'LoadBalancers[0].SecurityGroups[0]' \
    --output text
)"
alb_security_group_json="$(
  "$AWS_BIN" ec2 describe-security-groups \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --group-ids "$alb_security_group" \
    --output json
)"
public_https="$(
  jq '[
    .SecurityGroups[0].IpPermissions[]
    | select(.IpProtocol == "tcp" and .FromPort == 443 and .ToPort == 443)
    | .IpRanges[]?
    | select(.CidrIp == "0.0.0.0/0")
  ] | length' <<<"$alb_security_group_json"
)"
if [[ "$public_https" == "0" ]]; then
  echo "Refusing cutover before production HTTPS ingress is enabled." >&2
  exit 1
fi

curl --fail --silent --show-error "https://${ORIGIN_HOSTNAME}/healthz" >/dev/null

record_json="$(
  "$AWS_BIN" route53 list-resource-record-sets \
    --profile "$AWS_PROFILE" \
    --hosted-zone-id "$HOSTED_ZONE_ID" \
    --query "ResourceRecordSets[?Name==\`${PRODUCTION_HOSTNAME}.\` && Type==\`CNAME\`] | [0]" \
    --output json
)"
current_target="$(jq -r '.ResourceRecords[0].Value // empty' <<<"$record_json")"
if [[ "$current_target" != "$EXPECTED_EB_CNAME" ]]; then
  echo "Refusing unexpected current production DNS target: $current_target" >&2
  exit 1
fi

mkdir -p "$(dirname "$ROLLBACK_STATE_FILE")"
umask 077
printf '%s\n' "$record_json" >"$ROLLBACK_STATE_FILE"

snapshot_id="hectv-pre-ecs-cutover-$(date -u +%Y%m%dT%H%M%SZ)"
"$AWS_BIN" rds create-db-cluster-snapshot \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --db-cluster-identifier "$AURORA_CLUSTER_ID" \
  --db-cluster-snapshot-identifier "$snapshot_id" >/dev/null
"$AWS_BIN" rds wait db-cluster-snapshot-available \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --db-cluster-snapshot-identifier "$snapshot_id"

change_file="$(mktemp "${TMPDIR:-/tmp}/hectv-cutover.XXXXXX.json")"
trap 'rm -f "$change_file"' EXIT
jq -n \
  --arg name "$PRODUCTION_HOSTNAME" \
  --arg target "$ORIGIN_HOSTNAME" \
  '{Changes:[{Action:"UPSERT",ResourceRecordSet:{Name:$name,Type:"CNAME",TTL:60,ResourceRecords:[{Value:$target}]}}]}' \
  >"$change_file"

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
  "https://${PRODUCTION_HOSTNAME}/healthz" >/dev/null

echo "Cutover complete. Snapshot: $snapshot_id"
echo "Rollback state: $ROLLBACK_STATE_FILE"
