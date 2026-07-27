#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
builder="$repo_root/scripts/production/build-eb-artifact-image.sh"
plugin_path="wp-content/mu-plugins/hectv-staging-content-controls.php"

if ! grep -Fq '${ECS_CONFIG_REVISION}:'"$plugin_path" "$builder"; then
  echo "Staging content controls are missing from the immutable image overlay." >&2
  exit 1
fi

if ! grep -Fq "\$context/$plugin_path" "$builder"; then
  echo "Staging content controls have no destination in the image context." >&2
  exit 1
fi

echo "Staging image overlay test passed."
