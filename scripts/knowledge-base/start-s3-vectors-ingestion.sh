#!/usr/bin/env bash
set -euo pipefail

: "${AWS_PROFILE:=hecadmin}"
: "${AWS_REGION:=us-east-1}"
: "${NEW_KB_ID:?Set NEW_KB_ID to the Terraform output.}"
: "${NEW_DATA_SOURCE_ID:?Set NEW_DATA_SOURCE_ID to the Terraform output.}"
: "${HEC_KB_MIGRATION_REQUEST_TASK_ID:?Set the positive production authorization receipt.}"
: "${CONFIRMATION:?Set the exact ingestion confirmation phrase.}"
: "${EVIDENCE_PATH:=knowledge-base-ingestion.json}"

[[ "$AWS_PROFILE" == "hecadmin" ]] || {
  echo "Refusing a non-HEC administrative profile." >&2
  exit 1
}
[[ "$AWS_REGION" == "us-east-1" ]] || {
  echo "Refusing a knowledge-base region other than us-east-1." >&2
  exit 1
}
[[ "$NEW_KB_ID" =~ ^[A-Z0-9]{10}$ ]] || {
  echo "NEW_KB_ID is invalid." >&2
  exit 1
}
[[ "$NEW_DATA_SOURCE_ID" =~ ^[A-Z0-9]{10}$ ]] || {
  echo "NEW_DATA_SOURCE_ID is invalid." >&2
  exit 1
}
[[ "$HEC_KB_MIGRATION_REQUEST_TASK_ID" =~ ^[1-9][0-9]*$ ]] || {
  echo "A positive HEC production queue-task receipt is required." >&2
  exit 1
}
[[ "$CONFIRMATION" == "INGEST HEC S3 VECTORS KNOWLEDGE BASE" ]] || {
  echo "The ingestion confirmation phrase does not match." >&2
  exit 1
}

AWS_BIN="/opt/homebrew/bin/aws"
command -v "$AWS_BIN" >/dev/null

account_id="$($AWS_BIN sts get-caller-identity --profile "$AWS_PROFILE" --query Account --output text)"
[[ "$account_id" == "850335719356" ]] || {
  echo "Refusing to mutate AWS account $account_id." >&2
  exit 1
}

kb_json="$($AWS_BIN bedrock-agent get-knowledge-base \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --knowledge-base-id "$NEW_KB_ID" \
  --output json)"
data_source_json="$($AWS_BIN bedrock-agent get-data-source \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --knowledge-base-id "$NEW_KB_ID" \
  --data-source-id "$NEW_DATA_SOURCE_ID" \
  --output json)"

jq -e '
  .knowledgeBase.status == "ACTIVE" and
  .knowledgeBase.name == "SRT-Knowledge-base-s3-vectors" and
  .knowledgeBase.knowledgeBaseConfiguration.vectorKnowledgeBaseConfiguration.embeddingModelArn == "arn:aws:bedrock:us-east-1::foundation-model/amazon.titan-embed-text-v1" and
  .knowledgeBase.storageConfiguration.type == "S3_VECTORS" and
  .knowledgeBase.storageConfiguration.s3VectorsConfiguration.indexArn == "arn:aws:s3vectors:us-east-1:850335719356:bucket/hecmedia-srt-vectors/index/srt-transcripts"
' <<<"$kb_json" >/dev/null || {
  echo "The replacement knowledge base failed its immutable configuration contract." >&2
  exit 1
}

jq -e '
  .dataSource.status == "AVAILABLE" and
  .dataSource.dataSourceConfiguration.type == "S3" and
  .dataSource.dataSourceConfiguration.s3Configuration.bucketArn == "arn:aws:s3:::srtlibrary-hecmedia" and
  .dataSource.dataSourceConfiguration.s3Configuration.inclusionPrefixes == ["vimeo_captions/"] and
  .dataSource.dataDeletionPolicy == "RETAIN" and
  .dataSource.vectorIngestionConfiguration.chunkingConfiguration.chunkingStrategy == "FIXED_SIZE" and
  .dataSource.vectorIngestionConfiguration.chunkingConfiguration.fixedSizeChunkingConfiguration.maxTokens == 300 and
  .dataSource.vectorIngestionConfiguration.chunkingConfiguration.fixedSizeChunkingConfiguration.overlapPercentage == 20
' <<<"$data_source_json" >/dev/null || {
  echo "The replacement data source failed its immutable configuration contract." >&2
  exit 1
}

active_jobs="$($AWS_BIN bedrock-agent list-ingestion-jobs \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --knowledge-base-id "$NEW_KB_ID" \
  --data-source-id "$NEW_DATA_SOURCE_ID" \
  --max-results 50 \
  --output json)"
jq -e '[.ingestionJobSummaries[] | select(.status == "STARTING" or .status == "IN_PROGRESS")] | length == 0' <<<"$active_jobs" >/dev/null || {
  echo "An ingestion job is already active; refusing a duplicate." >&2
  exit 1
}

job_json="$($AWS_BIN bedrock-agent start-ingestion-job \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --knowledge-base-id "$NEW_KB_ID" \
  --data-source-id "$NEW_DATA_SOURCE_ID" \
  --description "HEC S3 Vectors migration task ${HEC_KB_MIGRATION_REQUEST_TASK_ID}" \
  --output json)"

jq -n \
  --arg account_id "$account_id" \
  --arg request_task_id "$HEC_KB_MIGRATION_REQUEST_TASK_ID" \
  --arg generated_at "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
  --argjson knowledge_base "$kb_json" \
  --argjson data_source "$data_source_json" \
  --argjson ingestion "$job_json" '
  {
    account_id: $account_id,
    request_task_id: $request_task_id,
    generated_at: $generated_at,
    knowledge_base: $knowledge_base.knowledgeBase,
    data_source: $data_source.dataSource,
    ingestion_job: $ingestion.ingestionJob
  }
' > "$EVIDENCE_PATH"

jq '{knowledge_base_id:.knowledge_base.knowledgeBaseId,data_source_id:.data_source.dataSourceId,ingestion_job_id:.ingestion_job.ingestionJobId,status:.ingestion_job.status}' "$EVIDENCE_PATH"
