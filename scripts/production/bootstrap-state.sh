#!/usr/bin/env bash
set -euo pipefail

: "${AWS_BIN:=/opt/homebrew/bin/aws}"
: "${AWS_PROFILE:=hecadmin}"
: "${AWS_REGION:=us-east-2}"
: "${AWS_ACCOUNT_ID:=850335719356}"
: "${STATE_BUCKET:=hectv-terraform-state-${AWS_ACCOUNT_ID}}"
: "${LOCK_TABLE:=hectv-terraform-locks}"

if ! "$AWS_BIN" s3api head-bucket --profile "$AWS_PROFILE" --bucket "$STATE_BUCKET" 2>/dev/null; then
  "$AWS_BIN" s3api create-bucket \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --bucket "$STATE_BUCKET" \
    --create-bucket-configuration "LocationConstraint=$AWS_REGION" >/dev/null
fi

"$AWS_BIN" s3api put-bucket-versioning \
  --profile "$AWS_PROFILE" \
  --bucket "$STATE_BUCKET" \
  --versioning-configuration Status=Enabled
"$AWS_BIN" s3api put-bucket-encryption \
  --profile "$AWS_PROFILE" \
  --bucket "$STATE_BUCKET" \
  --server-side-encryption-configuration \
  '{"Rules":[{"ApplyServerSideEncryptionByDefault":{"SSEAlgorithm":"AES256"},"BucketKeyEnabled":true}]}'
"$AWS_BIN" s3api put-public-access-block \
  --profile "$AWS_PROFILE" \
  --bucket "$STATE_BUCKET" \
  --public-access-block-configuration \
  BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true

if ! "$AWS_BIN" dynamodb describe-table \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --table-name "$LOCK_TABLE" >/dev/null 2>&1; then
  "$AWS_BIN" dynamodb create-table \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --table-name "$LOCK_TABLE" \
    --billing-mode PAY_PER_REQUEST \
    --attribute-definitions AttributeName=LockID,AttributeType=S \
    --key-schema AttributeName=LockID,KeyType=HASH >/dev/null
  "$AWS_BIN" dynamodb wait table-exists \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --table-name "$LOCK_TABLE"
fi

echo "Remote Terraform state is ready: s3://$STATE_BUCKET"
