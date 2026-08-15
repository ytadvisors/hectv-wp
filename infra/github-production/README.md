# GitHub production deploy role

The production workflow assumes a dedicated OIDC role named
`hectv-wp-production-deploy`. Its trust policy accepts only the
`ytadvisors/hectv-wp` GitHub `production` environment. The environment itself
requires Yomi's review, prevents self-review and administrator bypass, and
allows deployments only from `main`.

The inline permissions policy can inspect the production service, pull the
digest-pinned legacy-plugin source from the existing production ECR repository,
and push one exact-SHA release image back to that immutable repository. It can
then register production task definitions derived from the authorized live
definition and update exactly one ECS service. It cannot create services,
change DNS, read production runtime secrets, or modify IAM.

HEC staging is the repository's local Docker harness; there is no AWS staging
runtime or staging ECR dependency. The protected production job checks out the
exact merged `main` commit, builds a single ARM64 OCI image with BuildKit, records
its source revision as an index annotation, and verifies that annotation,
architecture, immutable tag, and digest before it can register or deploy a task
definition.

An AWS administrator bootstraps this role once after the release-path pull
request merges. Do not run these commands with the fleet read-only profile or
bypass a local mutation gate.

```sh
aws iam create-role \
  --role-name hectv-wp-production-deploy \
  --max-session-duration 7200 \
  --assume-role-policy-document file://infra/github-production/trust-policy.json

aws iam put-role-policy \
  --role-name hectv-wp-production-deploy \
  --policy-name hectv-wp-production-deploy \
  --policy-document file://infra/github-production/permissions-policy.json
```

The protected workflow uses the role's non-secret, exact ARN. Never store access
keys in GitHub. The positive queue-task ID is an audit receipt; the independent
GitHub `production` environment review is the human authorization gate.
