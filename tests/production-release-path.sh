#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
workflow="$repo_root/.github/workflows/production-deploy.yml"
release="$repo_root/scripts/production/promote-release.sh"

for file in "$workflow" "$release"; do
  [[ -f "$file" ]] || { echo "Missing production release file: $file" >&2; exit 1; }
done

grep -Fq 'name: production' "$workflow"
grep -Fq 'cancel-in-progress: false' "$workflow"
grep -Fq 'test "$RELEASE_SHA" = "$WORKFLOW_SHA"' "$workflow"
grep -Fq 'REQUEST_TASK_ID' "$workflow"
grep -Fq 'DEPLOY HEC BACKEND PRODUCTION' "$workflow"
grep -Fq 'HECTV_PRODUCTION_AWS_ROLE_ARN' "$workflow"

grep -Fq 'governed production workflow_dispatch' "$release"
grep -Fq 'deploymentCircuitBreaker' "$release"
grep -Fq '[.enable,.rollback]' "$release"
grep -Fq 'restoring the recorded baseline task definition' "$release"
grep -Fq 'hectv-wp-staging-admin' "$release"
grep -Fq 'HECTV_RECAPTCHA_ALLOWED_HOSTS' "$release"
grep -Fq 'imageTag=$RELEASE_SHA' "$release"

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
