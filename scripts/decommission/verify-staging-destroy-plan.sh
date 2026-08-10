#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
config="$repo_root/infra/staging/main.tf"
plan_json="${1:-}"

if [[ -z "$plan_json" ]] || [[ ! -f "$plan_json" ]]; then
  echo "Usage: $0 <terraform-show-json-path>" >&2
  exit 2
fi

command -v jq >/dev/null 2>&1 || {
  echo "jq is required to verify a staging destroy plan." >&2
  exit 2
}

jq -e '.resource_changes | type == "array"' "$plan_json" >/dev/null || {
  echo "Input is not Terraform plan JSON with resource_changes." >&2
  exit 1
}

allowed="$(mktemp "${TMPDIR:-/tmp}/hectv-staging-allowed.XXXXXX")"
deletions="$(mktemp "${TMPDIR:-/tmp}/hectv-staging-deletions.XXXXXX")"
cleanup() {
  rm -f "$allowed" "$deletions"
}
trap cleanup EXIT

sed -En 's/^resource "([^"]+)" "([^"]+)".*/\1.\2/p' "$config" | sort -u >"$allowed"

bad_actions="$({
  jq -r '
    .resource_changes[]
    | select((.mode // "managed") == "managed")
    | select(.change.actions != ["delete"])
    | "\(.address):\(.change.actions | join(","))"
  ' "$plan_json"
} || true)"

if [[ -n "$bad_actions" ]]; then
  echo "Destroy plan contains a managed action other than delete:" >&2
  printf '%s\n' "$bad_actions" >&2
  exit 1
fi

protected_deletions="$({
  jq -r '
    def resource_type: (.type // (.address | split(".")[0]));
    def resource_id: (.change.before.id? // .change.after.id? // "");
    .resource_changes[]
    | select((.mode // "managed") == "managed")
    | select(.change.actions == ["delete"])
    | resource_type as $type
    | resource_id as $id
    | select(
        ([
          "aws_efs_file_system",
          "aws_efs_mount_target",
          "aws_rds_cluster",
          "aws_db_instance",
          "aws_acm_certificate"
        ] | index($type)) != null
        or ([
          "fs-4243883b",
          "sg-26c1f14c",
          "fsmt-994a81e0",
          "fsmt-a74a81de",
          "fsmt-a44a81dd"
        ] | index($id)) != null
      )
    | "\(.address) [type=\($type), id=\($id)]"
  ' "$plan_json"
} || true)"

if [[ -n "$protected_deletions" ]]; then
  echo "Destroy plan includes a protected shared/production resource:" >&2
  printf '%s\n' "$protected_deletions" >&2
  exit 1
fi

jq -r '
  .resource_changes[]
  | select((.mode // "managed") == "managed")
  | select(.change.actions == ["delete"])
  | .address
  | sub("\\[.*$"; "")
' "$plan_json" | sort -u >"$deletions"

if [[ ! -s "$deletions" ]]; then
  echo "Destroy plan contains no managed deletions." >&2
  exit 1
fi

while IFS= read -r address; do
  if ! grep -Fxq "$address" "$allowed"; then
    echo "Unexpected resource in staging destroy plan: $address" >&2
    exit 1
  fi
done <"$deletions"

required_addresses=(
  aws_ecr_repository.wordpress
  aws_ecs_service.wordpress
  aws_ecs_service.admin
  aws_efs_access_point.staging
  aws_lb.wordpress
  aws_route53_record.staging
  aws_security_group_rule.efs_from_staging
  aws_security_group_rule.aurora_from_staging
)

for address in "${required_addresses[@]}"; do
  if ! grep -Fxq "$address" "$deletions"; then
    echo "Destroy plan is missing required staging resource: $address" >&2
    exit 1
  fi
done

echo "Staging destroy plan is delete-only and matches the reviewed infra/staging resource boundary."
