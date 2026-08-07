#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
dockerfile="$repo_root/Dockerfile"

if ! grep -Fq "wp-graphql.2.18.0.zip" "$dockerfile"; then
  echo "Staging image does not pin modern WPGraphQL 2.18.0." >&2
  exit 1
fi

if ! grep -Fq "COPY --from=wpgraphql /opt/plugins/wp-graphql " "$dockerfile"; then
  echo "Staging image omits the modern WPGraphQL build stage." >&2
  exit 1
fi

for plugin in wp-graphql wp-graphql-meta-query wp-graphql-tax-query; do
  if grep -Fq "COPY --from=legacy-plugins /var/www/html/wp-content/plugins/$plugin " "$dockerfile"; then
    echo "Staging image still copies incompatible legacy plugin: $plugin" >&2
    exit 1
  fi
done

if grep -Fq \
  "COPY --from=legacy-plugins /var/www/html/wp-content/plugins/wp-graphql-acf " \
  "$dockerfile"; then
  echo "Staging image includes obsolete wp-graphql-acf schema registration." >&2
  exit 1
fi

if ! grep -Fq \
  "COPY staging-harness/mu-plugins/hectv-graphql-compat.php /var/www/html/wp-content/mu-plugins/hectv-graphql-compat.php" \
  "$dockerfile"; then
  echo "Staging image omits the HEC frontend GraphQL compatibility contract." >&2
  exit 1
fi

for field in videoImage postHeader postHero isVideo; do
  if ! grep -Fq "'$field'" "$repo_root/staging-harness/mu-plugins/hectv-graphql-compat.php"; then
    echo "GraphQL compatibility contract omits required field: $field" >&2
    exit 1
  fi
done

php "$repo_root/tests/hectv-modern-cpt-graphql-exposure.php"
php "$repo_root/tests/hectv-graphql-dual-schema-contract.php"

echo "Staging GraphQL image contract test passed."
