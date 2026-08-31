#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
module="$repo_root/infra/knowledge-base"
main="$module/main.tf"
readme="$module/README.md"
ingest="$repo_root/scripts/knowledge-base/start-s3-vectors-ingestion.sh"
compare="$repo_root/scripts/knowledge-base/compare-retrieval.sh"
workflow="$repo_root/.github/workflows/knowledge-base-contract.yml"

for file in "$module/versions.tf" "$module/variables.tf" "$main" "$module/outputs.tf" "$module/backend.hcl.example" "$module/terraform.tfvars.example" "$readme" "$ingest" "$compare" "$workflow"; do
  [[ -f "$file" ]] || {
    echo "Missing knowledge-base migration file: $file" >&2
    exit 1
  }
done

grep -Fq 'allowed_account_ids = [local.account_id]' "$module/versions.tf"
grep -Fq 'account_id         = "850335719356"' "$main"
grep -Fq 'embedding_model_id = "amazon.titan-embed-text-v1"' "$main"
grep -Fq 'dimension          = 1536' "$main"
grep -Fq 'distance_metric    = "euclidean"' "$main"
grep -Fq '"AMAZON_BEDROCK_TEXT"' "$main"
grep -Fq '"AMAZON_BEDROCK_METADATA"' "$main"
grep -Fq 'max_tokens         = 300' "$main"
grep -Fq 'overlap_percentage = 20' "$main"
grep -Fq 'data_deletion_policy = "RETAIN"' "$main"
grep -Fq 'prevent_destroy = true' "$main"
grep -Fq 'ZKA5J7Y0WL' "$readme"
grep -Fq 'Do not delete the legacy data source' "$readme"
grep -Fq 'INGEST HEC S3 VECTORS KNOWLEDGE BASE' "$ingest"
grep -Fq 'minimum_source_overlap_ratio: 0.30' "$compare"
grep -Eq 'actions/checkout@[0-9a-f]{40}' "$workflow"
grep -Eq 'hashicorp/setup-terraform@[0-9a-f]{40}' "$workflow"
grep -Fq 'terraform -chdir=infra/knowledge-base init -backend=false -input=false' "$workflow"

if grep -Eq 'AWS_ACCESS_KEY_ID|AWS_SECRET_ACCESS_KEY|id-token: write' "$workflow"; then
  echo "Knowledge-base contract CI must be credential-free." >&2
  exit 1
fi

# GitHub-hosted Ubuntu runners do not guarantee ripgrep, so keep this guard
# dependency-free alongside the other POSIX tooling used by this contract.
if grep -R --exclude-dir=.terraform -n -E 'delete-(knowledge-base|data-source|collection)|aws_opensearchserverless_collection' "$module" "$ingest" "$compare"; then
  echo "The replacement rollout must not contain legacy deletion operations." >&2
  exit 1
fi

bash -n "$ingest" "$compare"
terraform -chdir="$module" fmt -check -recursive

echo "HEC S3 Vectors knowledge-base contracts passed."
