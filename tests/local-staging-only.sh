#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose="$repo_root/staging-harness/docker-compose.yml"
dockerfile="$repo_root/staging-harness/Dockerfile"
seed="$repo_root/staging-harness/seed.sh"
lifecycle="$repo_root/docs/STAGING-LIFECYCLE.md"

for file in "$compose" "$dockerfile" "$seed" "$lifecycle"; do
  [[ -f "$file" ]] || { echo "Missing local staging file: $file" >&2; exit 1; }
done

grep -Fq '"127.0.0.1:8092:80"' "$compose"
grep -Fq '"127.0.0.1:13308:3306"' "$compose"
grep -Fq 'HECTV_PUBLIC_READ_ONLY: 1' "$compose"
grep -Fq "define('WP_DEBUG_DISPLAY', false);" "$compose"
grep -Eq '^FROM wordpress:6\.8-php8\.2-apache@sha256:[0-9a-f]{64}$' "$dockerfile"
grep -Fq 'COPY wp-content/plugins/advanced-custom-fields ' "$dockerfile"
grep -Fq 'COPY wp-content/plugins/acf-repeater ' "$dockerfile"
grep -Fq 'COPY wp-content/mu-plugins/hectv-cms-fields.php ' "$dockerfile"
grep -Fq 'wpcli plugin activate advanced-custom-fields' "$seed"
grep -Fq 'wpcli plugin activate acf-repeater' "$seed"
grep -Fq '"31155"' "$seed"
grep -Fq 'post_list_0_post' "$seed"
grep -Fq 'HEC staging is local Docker only.' "$lifecycle"

for retired_script in common.sh start.sh stop.sh refresh-db.sh; do
  if [[ -e "$repo_root/scripts/staging/$retired_script" ]]; then
    echo "Retired AWS staging script is still executable source: scripts/staging/$retired_script" >&2
    exit 1
  fi
done

if grep -Eqi 'aws |amazonaws\.com|staging-wp\.hectv\.org|hectv-wp-staging@' "$compose" "$dockerfile" "$seed"; then
  echo "Local staging must not reference an AWS runtime." >&2
  exit 1
fi

echo "Local-only HEC staging contract passed."
