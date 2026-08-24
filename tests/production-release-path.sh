#!/usr/bin/env bash
# Exact-source grep assertions intentionally use single-quoted shell literals.
# shellcheck disable=SC2016
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
workflow="$repo_root/.github/workflows/production-deploy.yml"
release="$repo_root/scripts/production/promote-release.sh"
media_contract="$repo_root/scripts/production/media-directory-contract.jq"
dockerfile="$repo_root/Dockerfile"
iam_policy="$repo_root/infra/github-production/permissions-policy.json"

for file in "$workflow" "$release" "$media_contract" "$dockerfile" "$iam_policy"; do
  [[ -f "$file" ]] || { echo "Missing production release file: $file" >&2; exit 1; }
done

grep -Fq 'name: production' "$workflow"
grep -Fq 'cancel-in-progress: false' "$workflow"
grep -Fq 'test "$RELEASE_SHA" = "$WORKFLOW_SHA"' "$workflow"
grep -Fq 'REQUEST_TASK_ID' "$workflow"
grep -Fq 'DEPLOY HEC BACKEND PRODUCTION' "$workflow"
grep -Fq 'role/hectv-wp-production-deploy' "$workflow"
grep -Eq 'aws-actions/configure-aws-credentials@[0-9a-f]{40}' "$workflow"
grep -Fq 'shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240' "$workflow"
grep -Fq "php-version: '8.2'" "$workflow"
php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' | grep -qxE '[0-9]+\.[0-9]+'
grep -Fq 'test "$(php -r '\''echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;'\'')" = "8.2"' "$workflow"
grep -Eq 'docker/setup-qemu-action@[0-9a-f]{40}' "$workflow"
grep -Eq 'docker/setup-buildx-action@[0-9a-f]{40}' "$workflow"
grep -Fq 'id: production_builder' "$workflow"
grep -Fq 'driver: docker-container' "$workflow"
grep -Fq 'BUILDX_BUILDER: ${{ steps.production_builder.outputs.name }}' "$workflow"
grep -Eq 'image: docker\.io/tonistiigi/binfmt@sha256:[0-9a-f]{64}' "$workflow"
grep -Eq 'actions/upload-artifact@[0-9a-f]{40}' "$workflow"
grep -Fq 'bash tests/local-staging-only.sh' "$workflow"
grep -Fq 'php tests/hectv-production-runtime-dependencies.php' "$workflow"
grep -Fq 'php tests/hectv-home-editor.php' "$workflow"
grep -Fq 'php tests/hectv-homepage-graphql.php' "$workflow"
grep -Fq 'bash tests/staging-graphql-image-contract.sh' "$workflow"
grep -Fq 'bash tests/production-media-directory-contract.sh' "$workflow"
grep -Fq '.new_task_definition // "not registered"' "$workflow"
grep -Fq '.rollback_outcome // "not needed"' "$workflow"

grep -Fq 'governed production workflow_dispatch' "$release"
grep -Fq 'deploymentCircuitBreaker' "$release"
grep -Fq '[.enable,.rollback]' "$release"
grep -Fq 'restoring the recorded baseline task definition' "$release"
grep -Fq 'HECTV_RECAPTCHA_ALLOWED_HOSTS' "$release"
grep -Fq 'BUILDX_BUILDER:?Set BUILDX_BUILDER' "$release"
grep -Fq 'docker buildx build' "$release"
grep -Fq 'docker buildx inspect "$BUILDX_BUILDER" --bootstrap' "$release"
grep -Fq "grep -Eq '^Driver:[[:space:]]+docker-container[[:space:]]*\$'" "$release"
grep -Fq -- '--builder "$BUILDX_BUILDER"' "$release"
grep -Fq -- '--platform linux/arm64' "$release"
grep -Fq -- '--build-arg "APP_REVISION=$RELEASE_SHA"' "$release"
grep -Fq -- '--annotation "index:org.opencontainers.image.revision=$RELEASE_SHA"' "$release"
grep -Fq -- '--provenance=mode=max' "$release"
grep -Fq -- '--sbom=true' "$release"
grep -Fq -- '--metadata-file "$release_metadata"' "$release"
grep -Fq -- '--push' "$release"
grep -Fq 'imageTagMutability' "$release"
grep -Fq 'docker buildx imagetools inspect' "$release"
grep -Fq '.platform.architecture == "arm64"' "$release"
grep -Fq 'org.opencontainers.image.revision' "$release"
grep -Fq 'ORIGIN_READYZ_URL' "$release"
grep -Fq 'readyz.php' "$release"
grep -Fq 'mode == "graphql"' "$release"
grep -Fq 'profile == "consumer-v1"' "$release"
grep -Fq 'shouldOutputInFlatList' "$release"
grep -Fq 'media-graphql-response.json' "$release"
grep -Fq 'posts(first: 100)' "$release"
grep -Fq 'mediaItemUrl' "$release"
grep -Fq 'sourceUrl is missing or disagrees with mediaItemUrl when present' "$release"
grep -Fq 'media-directory-contract.jq' "$release"
grep -Fq 'prd-hectv-wp-media.s3-us-east-2.amazonaws.com/wp-content/uploads/' "$release"
grep -Fq 's3-us-east-2.amazonaws.com/prd-hectv-wp-media/wp-content/uploads/' "$release"
grep -Fq 'prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/' "$release"
grep -Fq -- "--range 0-0" "$release"
grep -Fq '[[ "$media_type" == image/* ]]' "$release"
grep -Fq "' \"\$task_release_source\" > \"\$register_file\"" "$release"
grep -Fq 'Final production task definition contains changes beyond the reviewed image digest.' "$release"
grep -Fq 'Refusing a production config migration containing changes beyond the two reviewed reCAPTCHA references.' "$release"

grep -Eq '^FROM 850335719356\.dkr\.ecr\.us-east-2\.amazonaws\.com/hectv-wp-production@sha256:[0-9a-f]{64} AS legacy-plugins$' "$dockerfile"
grep -Eq '^FROM debian:bookworm-slim@sha256:[0-9a-f]{64} AS wpgraphql$' "$dockerfile"

jq -e '
  [.Statement[]
    | select(.Resource == "arn:aws:ecr:us-east-2:850335719356:repository/hectv-wp-production")
    | .Action[]
  ] as $actions
  | ($actions | index("ecr:BatchGetImage")) != null
  and ($actions | index("ecr:GetDownloadUrlForLayer")) != null
  and ($actions | index("ecr:PutImage")) != null
  and ($actions | index("ecr:DescribeRepositories")) != null
' "$iam_policy" >/dev/null

if grep -Eqi 'hectv-wp-staging|STAGING_(CLUSTER|SERVICE|ECR)|skopeo' "$workflow" "$release" "$dockerfile" "$iam_policy"; then
  echo "Production release path must not depend on AWS staging." >&2
  exit 1
fi

if grep -Eq 'TASK_DEFINITION_TEMPLATE|manifest-only|batch-get-image|docker pull |docker tag |docker push ' "$release"; then
  echo "Production release must build merged source and derive from the live task definition." >&2
  exit 1
fi

if grep -Fq 'export DOCKER_CONFIG=' "$release"; then
  echo "Production release must preserve setup-buildx-action's builder state." >&2
  exit 1
fi

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
