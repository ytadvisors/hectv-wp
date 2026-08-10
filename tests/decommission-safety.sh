#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
eb_efs="$repo_root/.ebextensions/efs-create.config"
production_tf="$repo_root/infra/production/main.tf"
ownership_tf="$repo_root/infra/shared-efs-ownership/main.tf"
ownership_versions="$repo_root/infra/shared-efs-ownership/versions.tf"
verifier="$repo_root/scripts/decommission/verify-staging-destroy-plan.sh"

test "$(grep -Fc 'DeletionPolicy: Retain' "$eb_efs")" -eq 5
test "$(grep -Fc 'UpdateReplacePolicy: Retain' "$eb_efs")" -eq 5
grep -Fq 'resource "aws_efs_access_point" "production"' "$production_tf"
grep -A5 -F 'resource "aws_efs_access_point" "production"' "$production_tf" |
  grep -Fq 'prevent_destroy = true'

grep -Fq 'required_version = ">= 1.5"' "$ownership_versions"
test "$(grep -Fc 'import {' "$ownership_tf")" -eq 5
test "$(grep -Ec 'for_each[[:space:]]*=[[:space:]]*local\.mount_targets' "$ownership_tf")" -eq 1
test "$(grep -Fc 'prevent_destroy = true' "$ownership_tf")" -eq 3
grep -Fq 'fs-4243883b' "$ownership_tf"
grep -Fq 'sg-26c1f14c' "$ownership_tf"
for mount_target in fsmt-994a81e0 fsmt-a74a81de fsmt-a44a81dd; do
  grep -Fq "$mount_target" "$ownership_tf"
done
grep -Fq 'local.evidence_gate_passed' "$ownership_tf"
grep -Fq 'IMPORT RETAINED HEC EFS' "$ownership_tf"
grep -Fq 'data.aws_caller_identity.current.account_id == local.account_id' "$ownership_tf"

if grep -Eq 'terraform[[:space:]]+apply|delete-file-system|delete-mount-target|terminate-environment' "$repo_root/scripts/decommission"/*.sh; then
  echo "Decommission helper scripts must remain plan/verification-only." >&2
  exit 1
fi

bash "$verifier" \
  "$repo_root/tests/fixtures/decommission/staging-destroy-good.json"

if bash "$verifier" \
  "$repo_root/tests/fixtures/decommission/staging-destroy-unexpected-production.json" >/dev/null 2>&1; then
  echo "Destroy-plan verifier accepted a production EFS deletion." >&2
  exit 1
fi

bash -n "$verifier"
echo "HEC decommission safety tests passed."
