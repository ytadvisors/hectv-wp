#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
entrypoint="$repo_root/deploy/container/entrypoint.sh"

if ! grep -Fq -- '-path "$uploads_root/[0-9][0-9][0-9][0-9]/[0-9][0-9]"' "$entrypoint"; then
  echo "Production EFS probe is not restricted to WordPress YYYY/MM directories." >&2
  exit 1
fi

fixture_root="$(mktemp -d "${TMPDIR:-/tmp}/hectv-efs-probe.XXXXXX")"
cleanup() {
  rm -rf "$fixture_root"
}
trap cleanup EXIT

uploads_root="$fixture_root/uploads"
mkdir -p \
  "$uploads_root/2025/12" \
  "$uploads_root/2026/08" \
  "$uploads_root/staging-uploads/2099"

probe_dir="$(
  find "$uploads_root" \
    -mindepth 2 -maxdepth 2 -type d \
    -path "$uploads_root/[0-9][0-9][0-9][0-9]/[0-9][0-9]" \
    -print 2>/dev/null |
    sort |
    tail -1
)"

if [[ "$probe_dir" != "$uploads_root/2026/08" ]]; then
  echo "Production EFS probe selected an unsafe path: $probe_dir" >&2
  exit 1
fi

probe_file="$probe_dir/.hectv-ecs-write-probe-test"
(umask 077 && : >"$probe_file")
rm -f "$probe_file"

echo "Production EFS probe path test passed."
