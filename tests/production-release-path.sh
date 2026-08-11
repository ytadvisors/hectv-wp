#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
workflow="$repo_root/.github/workflows/production-deploy.yml"
release="$repo_root/scripts/production/promote-release.sh"
media_contract="$repo_root/scripts/production/media-directory-contract.jq"

for file in "$workflow" "$release" "$media_contract"; do
  [[ -f "$file" ]] || { echo "Missing production release file: $file" >&2; exit 1; }
done

grep -Fq 'name: production' "$workflow"
grep -Fq 'cancel-in-progress: false' "$workflow"
grep -Fq 'test "$RELEASE_SHA" = "$WORKFLOW_SHA"' "$workflow"
grep -Fq 'REQUEST_TASK_ID' "$workflow"
grep -Fq 'DEPLOY HEC BACKEND PRODUCTION' "$workflow"
grep -Fq 'role/hectv-wp-production-deploy' "$workflow"
grep -Eq 'aws-actions/configure-aws-credentials@[0-9a-f]{40}' "$workflow"
grep -Eq 'actions/upload-artifact@[0-9a-f]{40}' "$workflow"
grep -Fq '.new_task_definition // "not registered"' "$workflow"
grep -Fq '.rollback_outcome // "not needed"' "$workflow"

grep -Fq 'governed production workflow_dispatch' "$release"
grep -Fq 'deploymentCircuitBreaker' "$release"
grep -Fq '[.enable,.rollback]' "$release"
grep -Fq 'restoring the recorded baseline task definition' "$release"
grep -Fq 'hectv-wp-staging-admin' "$release"
grep -Fq 'HECTV_RECAPTCHA_ALLOWED_HOSTS' "$release"
grep -Fq 'imageTag=$RELEASE_SHA' "$release"
grep -Fq 'ORIGIN_READYZ_URL' "$release"
grep -Fq 'readyz.php' "$release"
grep -Fq 'mode == "graphql"' "$release"
grep -Fq 'profile == "consumer-v1"' "$release"
grep -Fq 'shouldOutputInFlatList' "$release"
grep -Fq 'media-graphql-response.json' "$release"
grep -Fq 'posts(first: 100)' "$release"
grep -Fq 'mediaItemUrl' "$release"
grep -Fq 'sourceUrl is missing or disagrees with mediaItemUrl' "$release"
grep -Fq 'media-directory-contract.jq' "$release"
grep -Fq 'prd-hectv-wp-media.s3-us-east-2.amazonaws.com/wp-content/uploads/' "$release"
grep -Fq 's3-us-east-2.amazonaws.com/prd-hectv-wp-media/wp-content/uploads/' "$release"
grep -Fq 'prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/' "$release"
grep -Fq -- "--range 0-0" "$release"
grep -Fq '[[ "$media_type" == image/* ]]' "$release"
grep -Eq 'SKOPEO_IMAGE="quay\.io/skopeo/stable@sha256:[0-9a-f]{64}"' "$release"
grep -Fq -- '--preserve-digests' "$release"
grep -Fq -- '--authfile /auth.json' "$release"
grep -Fq 'export DOCKER_CONFIG="$docker_config_dir"' "$release"
grep -Fq "' \"\$task_release_source\" > \"\$register_file\"" "$release"
grep -Fq 'Final production task definition contains changes beyond the reviewed image digest.' "$release"
grep -Fq 'Refusing a production config migration containing changes beyond the two reviewed reCAPTCHA references.' "$release"

if grep -Eq 'TASK_DEFINITION_TEMPLATE|manifest-only|batch-get-image|docker pull "\$staging_image"|docker tag "\$staging_image"|docker push "\$production_tagged_image"' "$release"; then
  echo "Production release must copy image layers and derive from the live task definition." >&2
  exit 1
fi

if grep -Eq 'scripts/production/cutover\.sh|route53 change-resource-record-sets|--force-new-deployment' "$workflow" "$release"; then
  echo "Production release path contains a forbidden legacy cutover or blind redeploy operation." >&2
  exit 1
fi

if grep -Eq 'AWS_ACCESS_KEY_ID|AWS_SECRET_ACCESS_KEY' "$workflow"; then
  echo "Production workflow must use OIDC, not long-lived AWS keys." >&2
  exit 1
fi

bash -n "$release"
echo "Production release path safety test passed."
