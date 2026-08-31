#!/usr/bin/env bash
set -euo pipefail

: "${AWS_PROFILE:=hecadmin}"
: "${AWS_REGION:=us-east-1}"
: "${OLD_KB_ID:=ZKA5J7Y0WL}"
: "${NEW_KB_ID:?Set NEW_KB_ID to the replacement knowledge base ID.}"

output_path="${1:-retrieval-parity.json}"
AWS_BIN="/opt/homebrew/bin/aws"
command -v "$AWS_BIN" >/dev/null

[[ "$AWS_PROFILE" == "hecadmin" ]] || {
  echo "Refusing a non-HEC administrative profile." >&2
  exit 1
}
[[ "$AWS_REGION" == "us-east-1" ]] || {
  echo "Refusing a knowledge-base region other than us-east-1." >&2
  exit 1
}
[[ "$OLD_KB_ID" == "ZKA5J7Y0WL" ]] || {
  echo "OLD_KB_ID must remain the recorded HEC legacy knowledge base." >&2
  exit 1
}
[[ "$NEW_KB_ID" =~ ^[A-Z0-9]{10}$ && "$NEW_KB_ID" != "$OLD_KB_ID" ]] || {
  echo "NEW_KB_ID is invalid or still names the legacy knowledge base." >&2
  exit 1
}

account_id="$($AWS_BIN sts get-caller-identity --profile "$AWS_PROFILE" --query Account --output text)"
[[ "$account_id" == "850335719356" ]] || {
  echo "Refusing to query AWS account $account_id." >&2
  exit 1
}

queries=(
  "racial and cultural awareness campaign"
  "arts education programs in St. Louis"
  "mental health support in local communities"
  "small business and entrepreneurship"
  "science technology and innovation"
)

work_dir="$(mktemp -d "${TMPDIR:-/tmp}/hec-kb-parity.XXXXXX")"
trap 'rm -rf "$work_dir"' EXIT

records="$work_dir/records.jsonl"
: > "$records"

for query in "${queries[@]}"; do
  query_json="$(jq -cn --arg text "$query" '{text:$text}')"
  retrieval_config='{"vectorSearchConfiguration":{"numberOfResults":10,"overrideSearchType":"SEMANTIC"}}'

  old_result="$($AWS_BIN bedrock-agent-runtime retrieve \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --knowledge-base-id "$OLD_KB_ID" \
    --retrieval-query "$query_json" \
    --retrieval-configuration "$retrieval_config" \
    --output json)"
  new_result="$($AWS_BIN bedrock-agent-runtime retrieve \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --knowledge-base-id "$NEW_KB_ID" \
    --retrieval-query "$query_json" \
    --retrieval-configuration "$retrieval_config" \
    --output json)"

  jq -cn \
    --arg query "$query" \
    --argjson old "$old_result" \
    --argjson new "$new_result" '
    def sources($response):
      [$response.retrievalResults[]?.location.s3Location.uri] | unique;
    (sources($old)) as $old_sources |
    (sources($new)) as $new_sources |
    ($old_sources - ($old_sources - $new_sources)) as $overlap |
    {
      query: $query,
      old_result_count: ($old.retrievalResults | length),
      new_result_count: ($new.retrievalResults | length),
      old_sources: $old_sources,
      new_sources: $new_sources,
      overlapping_sources: $overlap,
      source_overlap_ratio: (
        if ($old_sources | length) == 0 then 0
        else (($overlap | length) / ($old_sources | length))
        end
      ),
      old_top_score: ($old.retrievalResults[0].score // 0),
      new_top_score: ($new.retrievalResults[0].score // 0)
    }
  ' >> "$records"
done

jq -s \
  --arg account_id "$account_id" \
  --arg old_kb_id "$OLD_KB_ID" \
  --arg new_kb_id "$NEW_KB_ID" \
  --arg generated_at "$(date -u +%Y-%m-%dT%H:%M:%SZ)" '
  {
    account_id: $account_id,
    old_knowledge_base_id: $old_kb_id,
    new_knowledge_base_id: $new_kb_id,
    generated_at: $generated_at,
    minimum_source_overlap_ratio: 0.30,
    queries: .,
    passed: (
      length == 5 and
      all(.[]; .old_result_count > 0 and .new_result_count > 0 and .source_overlap_ratio >= 0.30)
    )
  }
' "$records" > "$output_path"

jq '{passed,minimum_source_overlap_ratio,queries:[.queries[]|{query,new_result_count,source_overlap_ratio}]}' "$output_path"
jq -e '.passed == true' "$output_path" >/dev/null
