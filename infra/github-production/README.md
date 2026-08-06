# GitHub production deploy role

The production workflow assumes a dedicated OIDC role named
`hectv-wp-production-deploy`. Its trust policy accepts only the
`ytadvisors/hectv-wp` GitHub `production` environment. The environment itself
requires Yomi's review, prevents self-review and administrator bypass, and
allows deployments only from `main`.

The inline permissions policy can inspect staging and production, copy the
already-proven staging image manifest into the immutable production ECR
repository, register a production task definition using the two existing ECS
task roles, and update exactly one ECS service. It cannot create services,
change DNS, read production runtime secrets, or modify IAM.

Bootstrap this role once with an authenticated HEC administrator, then store
only its ARN as the `HECTV_PRODUCTION_AWS_ROLE_ARN` secret in the GitHub
`production` environment. Never store access keys in GitHub.
