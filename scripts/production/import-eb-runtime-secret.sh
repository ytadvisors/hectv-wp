#!/usr/bin/env bash
set -euo pipefail

: "${AWS_BIN:=/opt/homebrew/bin/aws}"
: "${AWS_PROFILE:=hecadmin}"
: "${AWS_REGION:=us-east-2}"
: "${EB_APPLICATION:=wordpress-beanstalk}"
: "${EB_ENVIRONMENT:=hectv-wp-prod}"
: "${SECRET_NAME:=hectv-wp/production-runtime}"
: "${LEGACY_CONFIG_REF:=0aa2975}"
: "${DRY_RUN:=0}"

for command in "$AWS_BIN" git python3; do
  command -v "$command" >/dev/null 2>&1 || {
    echo "Required command is not installed: $command" >&2
    exit 1
  }
done

settings_file="$(mktemp "${TMPDIR:-/tmp}/hectv-eb-settings.XXXXXX.json")"
legacy_file="$(mktemp "${TMPDIR:-/tmp}/hectv-legacy-config.XXXXXX.php")"
secret_file="$(mktemp "${TMPDIR:-/tmp}/hectv-runtime-secret.XXXXXX.json")"
cleanup() {
  rm -f "$settings_file" "$legacy_file" "$secret_file"
}
trap cleanup EXIT
chmod 600 "$settings_file" "$legacy_file" "$secret_file"

"$AWS_BIN" elasticbeanstalk describe-configuration-settings \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --application-name "$EB_APPLICATION" \
  --environment-name "$EB_ENVIRONMENT" \
  --output json >"$settings_file"

git show "${LEGACY_CONFIG_REF}:wp-config.php" >"$legacy_file"

python3 - "$settings_file" "$legacy_file" "$secret_file" <<'PY'
import ast
import json
import re
import sys

settings_path, legacy_path, output_path = sys.argv[1:]
settings = json.load(open(settings_path))
legacy = open(legacy_path).read()

runtime = {}
for setting in settings["ConfigurationSettings"][0]["OptionSettings"]:
    if setting.get("Namespace") == "aws:elasticbeanstalk:application:environment":
        value = setting.get("Value")
        if value:
            runtime[setting["OptionName"]] = value

def legacy_constant(name):
    pattern = (
        r"define\(\s*(['\"])" + re.escape(name) + r"\1\s*,\s*"
        r"(['\"])((?:\\.|(?!\2).)*)\2\s*\)"
    )
    match = re.search(pattern, legacy, re.DOTALL)
    if not match:
        raise SystemExit("Legacy constant is missing: " + name)
    return ast.literal_eval(match.group(2) + match.group(3) + match.group(2))

runtime.setdefault("LOGGED_IN_SALT", "")
runtime.setdefault("JWT_AUTH_SECRET_KEY", legacy_constant("JWT_AUTH_SECRET_KEY"))
runtime.setdefault("STRIPE_KEY", "")
runtime.setdefault("STRIPE_SECRET_KEY", "")

required = {
    "API_KEY", "API_URL", "AUTH_KEY", "AUTH_SALT", "AWS_ACCESS_KEY_ID",
    "AWS_SECRET_ACCESS_KEY",
    "JWT_AUTH_SECRET_KEY", "LOGGED_IN_KEY", "LOGGED_IN_SALT", "NONCE_KEY",
    "NONCE_SALT", "RDS_DB_NAME", "RDS_HOSTNAME", "RDS_PASSWORD",
    "RDS_USERNAME", "SECURE_AUTH_KEY", "SECURE_AUTH_SALT", "STRIPE_KEY",
    "STRIPE_SECRET_KEY",
}
missing = sorted(key for key in required if not runtime.get(key))
missing = [key for key in missing if key not in {"LOGGED_IN_SALT", "STRIPE_KEY", "STRIPE_SECRET_KEY"}]
if missing:
    raise SystemExit("Refusing incomplete production secret; missing: " + ", ".join(missing))

with open(output_path, "w") as output:
    json.dump({key: runtime[key] for key in sorted(required)}, output)
PY

if [[ "$DRY_RUN" == "1" ]]; then
  echo "Production runtime secret validation passed; no AWS secret was written."
  exit 0
fi

if "$AWS_BIN" secretsmanager describe-secret \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --secret-id "$SECRET_NAME" >/dev/null 2>&1; then
  "$AWS_BIN" secretsmanager put-secret-value \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --secret-id "$SECRET_NAME" \
    --secret-string "file://$secret_file" >/dev/null
else
  "$AWS_BIN" secretsmanager create-secret \
    --profile "$AWS_PROFILE" \
    --region "$AWS_REGION" \
    --name "$SECRET_NAME" \
    --description "HECTV production WordPress ECS runtime migrated from Elastic Beanstalk" \
    --secret-string "file://$secret_file" >/dev/null
fi

"$AWS_BIN" secretsmanager describe-secret \
  --profile "$AWS_PROFILE" \
  --region "$AWS_REGION" \
  --secret-id "$SECRET_NAME" \
  --query ARN \
  --output text
