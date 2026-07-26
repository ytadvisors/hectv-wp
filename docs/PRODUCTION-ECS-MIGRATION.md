# Production ECS lift-and-shift

This stack migrates the existing `hectv-wp-prod` Elastic Beanstalk origin to a
parallel ECS/Fargate origin without combining the hosting cutover with a
WordPress or PHP upgrade.

## Compatibility boundary

Cutover 1 deliberately keeps the repository's WordPress 4.9.8 core and the
digest-pinned PHP 7.1 Apache runtime. This is temporary compatibility debt, but
it prevents the hosting migration from upgrading production database tables or
removing the legacy `wp-all-import-pro` plugin. PHP/WordPress/plugin
modernization is a separate, client-validated release after the hosting
migration is stable.

## Safety gates

- Terraform uses encrypted remote state with locking.
- The ECS service starts at desired count zero.
- The new origin uses `prod-wp-ecs.hectv.org`; `prod-wp.hectv.org` is not
  changed by Terraform.
- The existing Elastic Beanstalk environment is never modified or terminated.
- Production uploads mount the existing EFS root.
- Production database and EFS security groups grant only the ECS task security
  group.
- Runtime credentials are read from a dedicated Secrets Manager object.
- The production JWT, Stripe keys, and missing legacy salts must be migrated
  before the service starts.
- DNS cutover and rollback are explicit scripts with target verification.

## Provisioning order

1. Bootstrap the Terraform state bucket and lock table.
2. Create the production runtime secret with
   `scripts/production/import-eb-runtime-secret.sh`.
3. Initialize Terraform and apply only the ECR repository target.
4. Build and push `Dockerfile.production` with the current commit as an
   immutable tag.
5. Run a complete Terraform plan with `desired_count = 0`.
6. Apply the reviewed plan.
7. Confirm ALB health endpoint and DNS for `prod-wp-ecs.hectv.org`.
8. Take an Aurora snapshot.
9. Start one ECS task with WordPress cron disabled for direct-origin validation.
10. Perform the cutover only after the validation checklist passes.

## Cutover rollback

`scripts/production/cutover.sh` records the previous Route 53 record before
changing `prod-wp.hectv.org`. `scripts/production/rollback.sh` restores that
record and scales ECS to zero after the Elastic Beanstalk origin is healthy.
Never terminate Elastic Beanstalk during the rollback window.
