#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TF="$ROOT/infra/staging/main.tf"
DOC="$ROOT/docs/STAGING-LIFECYCLE.md"

grep -Fq 'resource "aws_lb_listener_rule" "allow_staging_admin_rest_authenticated_reads"' "$TF"
grep -Fq 'priority     = 15' "$TF"
grep -Fq 'resource "aws_lb_listener_rule" "allow_staging_admin_rest_writes"' "$TF"
grep -Fq 'priority     = 16' "$TF"
test "$(grep -Fc '"*wordpress_logged_in_*"' "$TF")" -eq 2
test "$(grep -Fc '"*wordpress_sec_*"' "$TF")" -eq 2
grep -Fq 'priority     = 20' "$TF"
grep -Fq 'Priorities 15 and 16 must remain ahead of the anonymous REST rule at 20.' "$DOC"

if grep -Fq 'allow_staging_admin_assets' "$TF"; then
  echo "Broad admin asset routing must not return." >&2
  exit 1
fi

echo "Staging ALB routing test passed."
