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
- The EFS access point enforces the Elastic Beanstalk `webapp` identity
  (`uid=500`, `gid=500`) for compatibility with existing upload directories.
- Every production task must pass a write-and-remove probe in the newest
  existing uploads year/month directory before Apache starts.
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
4. Build and push `Dockerfile.production` for `linux/arm64` with the current
   commit as an immutable tag:

   ```bash
   docker buildx build \
     --platform linux/arm64 \
     -f Dockerfile.production \
     --build-arg APP_REVISION="$(git rev-parse HEAD)" \
     --tag "$ECR_REPOSITORY:$(git rev-parse HEAD)" \
     --push .
   ```

5. Run a complete Terraform plan with `desired_count = 0`.
6. Apply the reviewed plan.
7. Confirm the ACM certificate covers `prod-wp-ecs.hectv.org`, then confirm
   the ALB health endpoint and DNS.
8. Take an Aurora snapshot.
9. Keep `validation_mode=true`, restrict `allowed_ipv4_cidrs` to the approved
   office/VPN addresses, and start one ECS task for direct-origin validation.
   Validation mode disables WordPress cron, PHP mail, and Stripe credentials.
10. Perform the cutover only after the validation checklist passes.

## Validation rules

Validation uses the live production database and uploads because this is a
lift-and-shift origin test. The origin must never be public during this phase.
Use read-mostly probes and do not submit checkout/payment flows, edit content,
upload media, or exercise administrative writes. Confirm the task logs contain
`Production EFS write probe passed` for an existing uploads year/month path.

Before running `cutover.sh`:

1. Set `validation_mode=false`.
2. Change `allowed_ipv4_cidrs` to the approved production ingress, including
   `0.0.0.0/0` for the current public origin behavior.
3. Apply the reviewed Terraform changes to register the normal-runtime task
   definition.
4. Scale the ECS service to desired count two.
5. Wait for two running tasks and two healthy ALB targets.
6. Re-run production smoke tests.

`cutover.sh` independently verifies two healthy targets, cron/mail/payments
enabled, public HTTPS ingress, the expected Elastic Beanstalk DNS target, and a
successful origin health check before taking the snapshot or changing DNS.

## Cutover rollback

`scripts/production/cutover.sh` records the previous Route 53 record before
changing `prod-wp.hectv.org`. `scripts/production/rollback.sh` restores that
record and scales ECS to zero after the Elastic Beanstalk origin is healthy.
Never terminate Elastic Beanstalk during the rollback window.
