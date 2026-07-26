#!/usr/bin/env bash
set -euo pipefail

: "${AWS_BIN:=/opt/homebrew/bin/aws}"
: "${AWS_PROFILE:=hecadmin}"
: "${AWS_REGION:=us-east-2}"
: "${EB_APPLICATION:=wordpress-beanstalk}"
: "${EB_VERSION:?Set EB_VERSION to the exact deployed Elastic Beanstalk version label.}"
: "${EXPECTED_BUNDLE_SHA256:?Set EXPECTED_BUNDLE_SHA256 to the reviewed source-bundle checksum.}"
: "${ECR_REPOSITORY:=850335719356.dkr.ecr.us-east-2.amazonaws.com/hectv-wp-production}"
: "${ECS_CONFIG_REVISION:=952aa6897c26eee9d50cec53820d65857b5f4d25}"

for command in "$AWS_BIN" docker git jq shasum unzip; do
  command -v "$command" >/dev/null 2>&1 || {
    echo "Required command is not installed: $command" >&2
    exit 1
  }
done

repo_root="$(git rev-parse --show-toplevel)"
if ! git -C "$repo_root" cat-file -e "${ECS_CONFIG_REVISION}^{commit}"; then
  echo "ECS compatibility revision does not exist: $ECS_CONFIG_REVISION" >&2
  exit 1
fi

bundle_location="$(
  "$AWS_BIN" elasticbeanstalk describe-application-versions \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --application-name "$EB_APPLICATION" \
    --version-labels "$EB_VERSION" \
    --query 'ApplicationVersions[0].SourceBundle' \
    --output json
)"
bucket="$(jq -r '.S3Bucket // empty' <<<"$bundle_location")"
key="$(jq -r '.S3Key // empty' <<<"$bundle_location")"
if [[ -z "$bucket" || -z "$key" ]]; then
  echo "Elastic Beanstalk version has no source bundle: $EB_VERSION" >&2
  exit 1
fi

build_dir="$(mktemp -d "${TMPDIR:-/tmp}/hectv-eb-image.XXXXXX")"
cleanup() {
  rm -rf "$build_dir"
}
trap cleanup EXIT

bundle="$build_dir/application.zip"
context="$build_dir/context"
"$AWS_BIN" s3 cp --profile "$AWS_PROFILE" "s3://$bucket/$key" "$bundle" >/dev/null
actual_sha="$(shasum -a 256 "$bundle" | awk '{print $1}')"
if [[ "$actual_sha" != "$EXPECTED_BUNDLE_SHA256" ]]; then
  echo "Source-bundle checksum mismatch: expected $EXPECTED_BUNDLE_SHA256, got $actual_sha" >&2
  exit 1
fi

mkdir -p "$context/deploy/container" "$context/wp-content/mu-plugins/hectv/classes"
unzip -q "$bundle" -d "$context"

# Preserve the exact deployed WordPress, plugins, and themes while overlaying
# only the already-reviewed ECS runtime compatibility and safety controls.
git -C "$repo_root" show "${ECS_CONFIG_REVISION}:Dockerfile.production" >"$context/Dockerfile.production"
git -C "$repo_root" show "${ECS_CONFIG_REVISION}:Dockerfile.production.dockerignore" >"$context/Dockerfile.production.dockerignore"
git -C "$repo_root" show "${ECS_CONFIG_REVISION}:deploy/container/php-production.ini" >"$context/deploy/container/php-production.ini"
git -C "$repo_root" show "${ECS_CONFIG_REVISION}:deploy/container/entrypoint.sh" >"$context/deploy/container/entrypoint.sh"
git -C "$repo_root" show "${ECS_CONFIG_REVISION}:deploy/container/healthz" >"$context/deploy/container/healthz"
git -C "$repo_root" show "${ECS_CONFIG_REVISION}:wp-config.php" >"$context/wp-config.php"
git -C "$repo_root" show "${ECS_CONFIG_REVISION}:wp-content/mu-plugins/hectv/classes/hectv_payment.php" \
  >"$context/wp-content/mu-plugins/hectv/classes/hectv_payment.php"

short_bundle_sha="${actual_sha:0:12}"
short_config_revision="${ECS_CONFIG_REVISION:0:8}"
safe_version="$(tr -c 'A-Za-z0-9_.-' '-' <<<"$EB_VERSION" | sed 's/-$//')"
image_tag="eb-${safe_version}-${short_bundle_sha}-cfg${short_config_revision}"
image_uri="${ECR_REPOSITORY}:${image_tag}"

docker build --platform linux/arm64 -f "$context/Dockerfile.production" -t "$image_uri" "$context"
docker push "$image_uri"

echo "Built immutable production image: $image_uri"
echo "Elastic Beanstalk bundle SHA-256: $actual_sha"
