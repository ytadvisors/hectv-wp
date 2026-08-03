#!/usr/bin/env bash
set -euo pipefail

violations=()

while IFS= read -r -d '' tracked_file; do
  case "$tracked_file" in
    .env|*/.env|.env.*|*/.env.*|*.sql|*.sql.gz|*.dump)
      case "$tracked_file" in
        .env.example|*/.env.example)
          ;;
        *)
          violations+=("$tracked_file")
          ;;
      esac
      ;;
  esac
done < <(git ls-files -z)

if (( ${#violations[@]} > 0 )); then
  printf 'Refusing tracked secret/data artifacts:\n' >&2
  printf '  %s\n' "${violations[@]}" >&2
  exit 1
fi

echo "No tracked .env files or database exports found."
