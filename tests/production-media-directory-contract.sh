#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
contract="$repo_root/scripts/production/media-directory-contract.jq"

[[ -f "$contract" ]] || { echo "Missing production media directory contract." >&2; exit 1; }

jq -e -f "$contract" >/dev/null <<'JSON'
{
  "data": {
    "posts": {
      "nodes": [{
        "postDetails": {
          "postHeader": {
            "mediaItemUrl": "https://s3-us-east-2.amazonaws.com/prd-hectv-wp-media/wp-content/uploads/2026/08/11055800/example.jpg",
            "medium": "https://prd-hectv-wp-media.s3-us-east-2.amazonaws.com/wp-content/uploads/2026/08/11055800/example-300x168.jpg",
            "large": "https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/08/11055800/example.jpg"
          },
          "videoImage": null,
          "postHero": null
        }
      }]
    }
  }
}
JSON

if jq -e -f "$contract" >/dev/null <<'JSON'
{
  "data": {
    "posts": {
      "nodes": [{
        "postDetails": {
          "postHeader": {
            "mediaItemUrl": "https://s3-us-east-2.amazonaws.com/prd-hectv-wp-media/wp-content/uploads/2026/08/11055800/example.jpg",
            "medium": "https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/08/example-300x168.jpg",
            "large": "https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/08/example.jpg"
          }
        }
      }]
    }
  }
}
JSON
then
  echo "Media directory contract accepted a sourceUrl that dropped the offload prefix." >&2
  exit 1
fi

jq -e -f "$contract" >/dev/null <<'JSON'
{
  "data": {
    "posts": {
      "nodes": [{
        "postDetails": {
          "postHeader": {
            "mediaItemUrl": null,
            "medium": "https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/08/example-300x168.jpg",
            "large": "https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/08/example.jpg"
          }
        }
      }]
    }
  }
}
JSON

if jq -e -f "$contract" >/dev/null <<'JSON'
{
  "data": {
    "posts": {
      "nodes": [{
        "postDetails": {
          "postHeader": {
            "mediaItemUrl": null,
            "medium": null,
            "large": null
          }
        }
      }]
    }
  }
}
JSON
then
  echo "Media directory contract accepted an image without a sourceUrl derivative." >&2
  exit 1
fi

echo "Production media directory contract passed."
