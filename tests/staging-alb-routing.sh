#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TF="$ROOT/infra/staging/main.tf"
DOC="$ROOT/docs/STAGING-LIFECYCLE.md"

grep -Fq 'resource "aws_lb_listener_rule" "allow_staging_admin_rest_authenticated_reads"' "$TF"
grep -Fq 'priority     = 15' "$TF"
grep -Fq 'resource "aws_lb_listener_rule" "allow_staging_admin_rest_writes_post_put"' "$TF"
grep -Fq 'priority     = 16' "$TF"
grep -Fq 'values = ["POST", "PUT"]' "$TF"
grep -Fq 'resource "aws_lb_listener_rule" "allow_staging_admin_rest_writes_patch_delete"' "$TF"
grep -Fq 'priority     = 17' "$TF"
grep -Fq 'values = ["PATCH", "DELETE"]' "$TF"
test "$(grep -Fc '"*wordpress_logged_in_*"' "$TF")" -eq 3
test "$(grep -Fc '"*wordpress_sec_*"' "$TF")" -eq 3
grep -Fq 'priority     = 20' "$TF"
grep -Fq 'resource "aws_lb_listener_rule" "allow_staging_public_assets"' "$TF"
grep -Fq 'for_each     = var.public_read_only_mode ? local.public_asset_groups : {}' "$TF"
grep -Fq 'target_group_arn = aws_lb_target_group.wordpress.arn' "$TF"
test "$(grep -Ec 'priority = (18|19)' "$TF")" -eq 2
grep -Fq 'regex    = "^/wp-includes/.*\\.(js|css|png|gif|svg|jpg|jpeg|webp|woff|woff2|ttf|eot)$"' "$TF"
grep -Fq 'regex    = "^/wp-content/.*\\.(js|css|png|gif|svg|jpg|jpeg|webp|woff|woff2|ttf|eot)$"' "$TF"
grep -Fq 'regex_values = [each.value.regex]' "$TF"
grep -Fq 'Priorities 15–17 must remain ahead of the anonymous REST rule at 20.' "$DOC"
grep -Fq 'at most five match values per listener rule' "$DOC"
grep -Fq 'target the public read-only service' "$DOC"
grep -Fq 'not match every nested editor path' "$DOC"
grep -Fq '`PathPatternConfig.RegexValues` is supported by the AWS ELBv2 API' "$DOC"
grep -Fq 'must run both `terraform validate` and a remote-state `terraform plan`' "$DOC"

if grep -Fq 'allow_staging_admin_assets' "$TF"; then
  echo "Broad admin asset routing must not return." >&2
  exit 1
fi

echo "Staging ALB routing test passed."
