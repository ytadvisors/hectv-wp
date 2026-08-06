#!/usr/bin/env bash
set -euo pipefail

: "${RELEASE_SHA:?Set RELEASE_SHA to the exact merged main commit.}"
: "${ARTIFACT_DIGEST:?Set ARTIFACT_DIGEST to the exact staging ECR digest.}"
: "${EXPECTED_CURRENT_TASK_DEFINITION:?Set the full production task-definition ARN observed immediately before dispatch.}"
: "${EXPECTED_CURRENT_IMAGE_DIGEST:?Set the exact production image digest observed immediately before dispatch.}"
: "${REQUEST_TASK_ID:?Set REQUEST_TASK_ID to the positive HEC production authorization receipt.}"
: "${PRODUCTION_CONFIRMATION:?Set PRODUCTION_CONFIRMATION to the exact production confirmation phrase.}"

: "${AWS_REGION:=us-east-2}"
: "${AWS_ACCOUNT_ID:=850335719356}"
: "${ECS_CLUSTER:=hectv-wp-production}"
: "${ECS_SERVICE:=hectv-wp-production}"
: "${STAGING_CLUSTER:=hectv-wp}"
: "${STAGING_PUBLIC_SERVICE:=hectv-wp-staging}"
: "${STAGING_ADMIN_SERVICE:=hectv-wp-staging-admin}"
: "${STAGING_ECR_REPOSITORY:=hectv-wp-staging}"
: "${PRODUCTION_ECR_REPOSITORY:=hectv-wp-production}"
: "${TASK_DEFINITION_TEMPLATE:=arn:aws:ecs:us-east-2:850335719356:task-definition/hectv-wp-production:11}"
: "${ORIGIN_HEALTH_URL:=https://prod-wp-ecs.hectv.org/healthz}"
: "${PUBLIC_HEALTH_URL:=https://prod-wp.hectv.org/healthz}"
: "${GRAPHQL_URL:=https://prod-wp.hectv.org/graphql}"
: "${NEWSLETTER_URL:=https://prod-wp.hectv.org/wp-json/hectv/v1/newsletter/subscribe}"
: "${EVIDENCE_PATH:=production-deploy-evidence.json}"

readonly EXPECTED_WORKFLOW="/.github/workflows/production-deploy.yml@"
readonly CONFIRMATION_PHRASE="DEPLOY HEC BACKEND PRODUCTION"
readonly PRODUCTION_REPOSITORY_URI="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com/${PRODUCTION_ECR_REPOSITORY}"

for command in aws curl jq mktemp; do
  command -v "$command" >/dev/null 2>&1 || {
    echo "Required command is not installed: $command" >&2
    exit 1
  }
done

case "${GITHUB_ACTOR:-}" in
  ytwguru|yt-agent-tom|yt-agent-tom-gpt|yt-agent-tom-grok) ;;
  *) echo "GitHub actor is not approved for HEC production publishing." >&2; exit 1 ;;
esac
if [[ "${GITHUB_ACTIONS:-}" != "true" ]] ||
   [[ "${GITHUB_EVENT_NAME:-}" != "workflow_dispatch" ]] ||
   [[ "${GITHUB_WORKFLOW_REF:-}" != *"$EXPECTED_WORKFLOW"* ]]; then
  echo "Production mutation is allowed only through the governed production workflow_dispatch." >&2
  exit 1
fi
[[ "$RELEASE_SHA" =~ ^[0-9a-f]{40}$ ]] || { echo "RELEASE_SHA is not an exact commit SHA." >&2; exit 1; }
[[ "$ARTIFACT_DIGEST" =~ ^sha256:[0-9a-f]{64}$ ]] || { echo "ARTIFACT_DIGEST is invalid." >&2; exit 1; }
[[ "$EXPECTED_CURRENT_IMAGE_DIGEST" =~ ^sha256:[0-9a-f]{64}$ ]] || { echo "EXPECTED_CURRENT_IMAGE_DIGEST is invalid." >&2; exit 1; }
[[ "$REQUEST_TASK_ID" =~ ^[1-9][0-9]*$ ]] || { echo "REQUEST_TASK_ID must be positive." >&2; exit 1; }
[[ "$PRODUCTION_CONFIRMATION" == "$CONFIRMATION_PHRASE" ]] || { echo "Production confirmation phrase does not match." >&2; exit 1; }
[[ "$ECS_CLUSTER" == "hectv-wp-production" && "$ECS_SERVICE" == "hectv-wp-production" ]] || {
  echo "Refusing a non-production HEC target." >&2
  exit 1
}

release_dir="$(mktemp -d "${RUNNER_TEMP:-/tmp}/hectv-production-release.XXXXXX")"
service_before="$release_dir/service-before.json"
task_before="$release_dir/task-before.json"
template_file="$release_dir/task-template.json"
register_file="$release_dir/task-register.json"
manifest_response="$release_dir/manifest-response.json"
manifest_file="$release_dir/manifest.json"
graphql_response="$release_dir/graphql-response.json"
newsletter_response="$release_dir/newsletter-response.json"

deploy_started=0
deploy_succeeded=0
new_task_definition=""
rollback_outcome="not-needed"

write_evidence() {
  local outcome="$1"
  jq -n \
    --arg target "https://prod-wp.hectv.org" \
    --arg outcome "$outcome" \
    --arg release_sha "$RELEASE_SHA" \
    --arg artifact_digest "$ARTIFACT_DIGEST" \
    --arg request_task_id "$REQUEST_TASK_ID" \
    --arg actor "${GITHUB_ACTOR:-unknown}" \
    --arg baseline_task_definition "$EXPECTED_CURRENT_TASK_DEFINITION" \
    --arg baseline_image_digest "$EXPECTED_CURRENT_IMAGE_DIGEST" \
    --arg new_task_definition "$new_task_definition" \
    --arg rollback_outcome "$rollback_outcome" \
    --arg generated_at "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
    '{target:$target,outcome:$outcome,release_sha:$release_sha,artifact_digest:$artifact_digest,request_task_id:$request_task_id,dispatch_actor:$actor,baseline_task_definition:$baseline_task_definition,baseline_image_digest:$baseline_image_digest,new_task_definition:(if ($new_task_definition|length)>0 then $new_task_definition else null end),rollback_outcome:$rollback_outcome,generated_at:$generated_at}' \
    > "$EVIDENCE_PATH"
}

finish() {
  local exit_code="$?"
  trap - EXIT
  if [[ "$exit_code" -ne 0 && "$deploy_started" -eq 1 && "$deploy_succeeded" -eq 0 ]]; then
    echo "Deployment failed after the service update; restoring the recorded baseline task definition." >&2
    set +e
    aws ecs update-service \
      --region "$AWS_REGION" \
      --cluster "$ECS_CLUSTER" \
      --service "$ECS_SERVICE" \
      --task-definition "$EXPECTED_CURRENT_TASK_DEFINITION" >/dev/null
    aws ecs wait services-stable \
      --region "$AWS_REGION" \
      --cluster "$ECS_CLUSTER" \
      --services "$ECS_SERVICE"
    rollback_code="$?"
    set -e
    if [[ "$rollback_code" -eq 0 ]]; then rollback_outcome="restored-baseline"; else rollback_outcome="rollback-failed"; fi
  elif [[ "$exit_code" -ne 0 ]]; then
    rollback_outcome="not-started"
  fi

  if [[ "$exit_code" -eq 0 ]]; then write_evidence "success"; else write_evidence "failed"; fi
  rm -rf "$release_dir"
  exit "$exit_code"
}
trap finish EXIT

aws ecs describe-services \
  --region "$AWS_REGION" \
  --cluster "$ECS_CLUSTER" \
  --services "$ECS_SERVICE" \
  --output json > "$service_before"

current_task_definition="$(jq -r '.services[0].taskDefinition // empty' "$service_before")"
current_desired="$(jq -r '.services[0].desiredCount // -1' "$service_before")"
current_running="$(jq -r '.services[0].runningCount // -1' "$service_before")"
current_pending="$(jq -r '.services[0].pendingCount // -1' "$service_before")"
current_rollout="$(jq -r '.services[0].deployments[] | select(.status == "PRIMARY") | .rolloutState // empty' "$service_before")"
circuit_breaker="$(jq -r '.services[0].deploymentConfiguration.deploymentCircuitBreaker | [.enable,.rollback] | @tsv' "$service_before")"

[[ "$current_task_definition" == "$EXPECTED_CURRENT_TASK_DEFINITION" ]] || {
  echo "Production task definition changed after authorization; refusing release." >&2
  exit 1
}
[[ "$current_desired" -gt 0 && "$current_running" -eq "$current_desired" && "$current_pending" -eq 0 && "$current_rollout" == "COMPLETED" ]] || {
  echo "Production ECS service is not at a stable baseline." >&2
  exit 1
}
[[ "$circuit_breaker" == $'true\ttrue' ]] || {
  echo "Production ECS deployment circuit breaker with rollback is not enabled." >&2
  exit 1
}

aws ecs describe-task-definition \
  --region "$AWS_REGION" \
  --task-definition "$current_task_definition" \
  --output json > "$task_before"
current_image="$(jq -r '.taskDefinition.containerDefinitions[0].image // empty' "$task_before")"
current_image_digest="${current_image##*@}"
[[ "$current_image_digest" == "$EXPECTED_CURRENT_IMAGE_DIGEST" ]] || {
  echo "Production image digest changed after authorization; refusing release." >&2
  exit 1
}

for service in "$STAGING_PUBLIC_SERVICE" "$STAGING_ADMIN_SERVICE"; do
  staging_state="$(aws ecs describe-services --region "$AWS_REGION" --cluster "$STAGING_CLUSTER" --services "$service" --query 'services[0].[desiredCount,runningCount,pendingCount,taskDefinition,deployments[?status==`PRIMARY`].rolloutState|[0]]' --output text)"
  read -r staging_desired staging_running staging_pending staging_task_definition staging_rollout <<<"$staging_state"
  [[ "$staging_desired" -gt 0 && "$staging_running" -eq "$staging_desired" && "$staging_pending" -eq 0 && "$staging_rollout" == "COMPLETED" ]] || {
    echo "Staging service $service is not stable." >&2
    exit 1
  }
  staging_image="$(aws ecs describe-task-definition --region "$AWS_REGION" --task-definition "$staging_task_definition" --query 'taskDefinition.containerDefinitions[0].image' --output text)"
  [[ "${staging_image##*@}" == "$ARTIFACT_DIGEST" ]] || {
    echo "Staging service $service is not running the authorized digest." >&2
    exit 1
  }
done

tagged_digest="$(aws ecr describe-images --region "$AWS_REGION" --repository-name "$STAGING_ECR_REPOSITORY" --image-ids "imageTag=$RELEASE_SHA" --query 'imageDetails[0].imageDigest' --output text)"
[[ "$tagged_digest" == "$ARTIFACT_DIGEST" ]] || {
  echo "The staging release tag does not resolve to the authorized digest." >&2
  exit 1
}

destination_tag="release-${RELEASE_SHA}"
destination_digest="$(aws ecr describe-images --region "$AWS_REGION" --repository-name "$PRODUCTION_ECR_REPOSITORY" --image-ids "imageTag=$destination_tag" --query 'imageDetails[0].imageDigest' --output text 2>/dev/null || true)"
if [[ -n "$destination_digest" && "$destination_digest" != "None" ]]; then
  [[ "$destination_digest" == "$ARTIFACT_DIGEST" ]] || {
    echo "Immutable production tag already exists with a different digest." >&2
    exit 1
  }
else
  aws ecr batch-get-image \
    --region "$AWS_REGION" \
    --repository-name "$STAGING_ECR_REPOSITORY" \
    --image-ids "imageDigest=$ARTIFACT_DIGEST" \
    --accepted-media-types \
      application/vnd.oci.image.index.v1+json \
      application/vnd.oci.image.manifest.v1+json \
      application/vnd.docker.distribution.manifest.list.v2+json \
      application/vnd.docker.distribution.manifest.v2+json \
    --output json > "$manifest_response"
  jq -e '.failures | length == 0' "$manifest_response" >/dev/null
  jq -er '.images[0].imageManifest' "$manifest_response" > "$manifest_file"
  manifest_media_type="$(jq -r '.images[0].imageManifestMediaType // empty' "$manifest_response")"
  aws ecr put-image \
    --region "$AWS_REGION" \
    --repository-name "$PRODUCTION_ECR_REPOSITORY" \
    --image-tag "$destination_tag" \
    --image-manifest "file://$manifest_file" \
    --image-manifest-media-type "$manifest_media_type" >/dev/null
  destination_digest="$(aws ecr describe-images --region "$AWS_REGION" --repository-name "$PRODUCTION_ECR_REPOSITORY" --image-ids "imageTag=$destination_tag" --query 'imageDetails[0].imageDigest' --output text)"
  [[ "$destination_digest" == "$ARTIFACT_DIGEST" ]] || {
    echo "Promoted production image digest does not match staging." >&2
    exit 1
  }
fi

aws ecs describe-task-definition \
  --region "$AWS_REGION" \
  --task-definition "$TASK_DEFINITION_TEMPLATE" \
  --output json > "$template_file"

jq -e '
  .taskDefinition.family == "hectv-wp-production" and
  .taskDefinition.runtimePlatform.cpuArchitecture == "ARM64" and
  ([.taskDefinition.containerDefinitions[0].environment[] | {(.name):.value}] | add) as $env |
  $env.HECTV_ENVIRONMENT == "production" and
  $env.DISABLE_WP_CRON == "0" and
  $env.HECTV_DISABLE_OUTBOUND == "0" and
  $env.HECTV_DISABLE_PAYMENTS == "0" and
  ([.taskDefinition.containerDefinitions[0].secrets[].name] | index("HECTV_RECAPTCHA_SECRET_KEY")) != null and
  ([.taskDefinition.containerDefinitions[0].secrets[].name] | index("HECTV_RECAPTCHA_ALLOWED_HOSTS")) != null
' "$template_file" >/dev/null || {
  echo "Production task-definition template failed its runtime safety contract." >&2
  exit 1
}

production_image="${PRODUCTION_REPOSITORY_URI}@${ARTIFACT_DIGEST}"
jq --arg image "$production_image" '
  .taskDefinition
  | del(.taskDefinitionArn,.revision,.status,.requiresAttributes,.compatibilities,.registeredAt,.registeredBy,.deregisteredAt)
  | .family = "hectv-wp-production"
  | .containerDefinitions[0].image = $image
' "$template_file" > "$register_file"

new_task_definition="$(aws ecs register-task-definition \
  --region "$AWS_REGION" \
  --cli-input-json "file://$register_file" \
  --tags "key=ReleaseSha,value=$RELEASE_SHA" "key=RequestTaskId,value=$REQUEST_TASK_ID" \
  --query 'taskDefinition.taskDefinitionArn' \
  --output text)"
[[ "$new_task_definition" =~ ^arn:aws:ecs:us-east-2:850335719356:task-definition/hectv-wp-production:[1-9][0-9]*$ ]] || {
  echo "ECS returned an unexpected task-definition ARN." >&2
  exit 1
}

deploy_started=1
aws ecs update-service \
  --region "$AWS_REGION" \
  --cluster "$ECS_CLUSTER" \
  --service "$ECS_SERVICE" \
  --task-definition "$new_task_definition" >/dev/null
aws ecs wait services-stable \
  --region "$AWS_REGION" \
  --cluster "$ECS_CLUSTER" \
  --services "$ECS_SERVICE"

service_after="$(aws ecs describe-services --region "$AWS_REGION" --cluster "$ECS_CLUSTER" --services "$ECS_SERVICE" --output json)"
after_task_definition="$(jq -r '.services[0].taskDefinition // empty' <<<"$service_after")"
after_desired="$(jq -r '.services[0].desiredCount // -1' <<<"$service_after")"
after_running="$(jq -r '.services[0].runningCount // -1' <<<"$service_after")"
after_pending="$(jq -r '.services[0].pendingCount // -1' <<<"$service_after")"
after_rollout="$(jq -r '.services[0].deployments[] | select(.status == "PRIMARY") | .rolloutState // empty' <<<"$service_after")"
[[ "$after_task_definition" == "$new_task_definition" && "$after_running" -eq "$after_desired" && "$after_pending" -eq 0 && "$after_rollout" == "COMPLETED" ]] || {
  echo "Production ECS service did not converge on the new task definition." >&2
  exit 1
}

curl --fail --silent --show-error --retry 12 --retry-delay 5 "$ORIGIN_HEALTH_URL" >/dev/null
curl --fail --silent --show-error --retry 12 --retry-delay 5 "$PUBLIC_HEALTH_URL" >/dev/null

jq -n --arg query '{ generalSettings { title url } trendingSettings { maxVideos } topbarCtas { label url style } }' '{query:$query}' |
  curl --fail --silent --show-error --retry 5 --retry-delay 3 \
    --header 'Content-Type: application/json' \
    --data @- \
    "$GRAPHQL_URL" > "$graphql_response"
jq -e '((.errors // []) | length == 0) and (.data.generalSettings.title | type == "string" and length > 0) and (.data.trendingSettings != null) and (.data.topbarCtas != null)' "$graphql_response" >/dev/null

newsletter_status="$(curl --silent --show-error --request POST \
  --header 'Content-Type: application/json' \
  --data '{}' \
  --output "$newsletter_response" \
  --write-out '%{http_code}' \
  "$NEWSLETTER_URL")"
[[ "$newsletter_status" == "400" ]] || {
  echo "Newsletter bridge returned HTTP $newsletter_status instead of failing closed with 400." >&2
  exit 1
}
jq -e '.code == "hectv_newsletter_invalid_request" or .data.status == 400' "$newsletter_response" >/dev/null

deploy_succeeded=1
echo "HEC WordPress production release verified: $new_task_definition"
