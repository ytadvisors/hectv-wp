# On-demand HEC WordPress staging

Staging uses a separate logical database, `hectv_staging`, on the existing Aurora
cluster. It never aliases or writes production tables. The ECS staging service is
normally set to desired count zero, so application compute is billed only while
staging is in use.

## Required environment

Do not commit values. Load them from the approved credential/secret mechanism:

```bash
export AWS_PROFILE=hecadmin
export AWS_REGION=us-east-2
export ECS_CLUSTER=hectv-wp
export ECS_SERVICE=hectv-wp-staging
export STAGING_DB_NAME=hectv_staging
export STAGING_URL=https://staging-wp.hectv.org
export PROD_DB_NAME='<production database name loaded from the approved secret>'
export DB_HOST='Aurora writer endpoint'
export DB_ADMIN_USER='runtime-loaded admin user'
export DB_ADMIN_PASSWORD='runtime-loaded admin password'
export STAGING_HEALTH_URL='https://staging-wp.hectv.org/wp-json/wp/v2/types'
```

The staging Secrets Manager object must contain staging-only salts and API
credentials. Stripe keys must be `pk_test_*` and `sk_test_*`; the container
refuses to start with live keys. Do not copy production payment, email, or
campaign credentials into the staging secret.

The legacy `wp-all-import-pro` bundle is deliberately excluded from the PHP 8.2
staging image. Its vendored PHPExcel code uses syntax removed in PHP 8 and the
plugin is not required for content-review or release-validation workflows.
Production source and the existing EB runtime are not modified by that image
exclusion. Reintroducing imports requires a separately licensed, PHP
8-compatible plugin update and its own validation.

The staging image also takes WordPress core from the digest-pinned official
WordPress PHP 8.2 image instead of copying the repository's WordPress 4.9.8 core.
Only HEC's `wp-content`, Composer vendor tree, configuration, and rewrite rules
are layered onto that core. Database upgrades therefore occur only in
`hectv_staging`; production Aurora tables are never the target.

The lifecycle scripts are hard-pinned to ECS cluster `hectv-wp` and service
`hectv-wp-staging`. They refuse any other cluster/service pair before making an
AWS call, even if environment variables try to override the target.

`JWT_AUTH_SECRET_KEY` must be present in the staging runtime secret. For
backward-compatible production startup, `wp-config.php` falls back to the
existing `AUTH_KEY` when the dedicated JWT value is absent. Before a production
deployment, pre-seed `JWT_AUTH_SECRET_KEY` with the production JWT secret to
preserve already-issued tokens; otherwise users with old tokens must sign in
again once, while newly issued tokens remain stable through the `AUTH_KEY`
fallback.

## Refresh and start

The refresh script requires staging desired count zero. It creates an Aurora
snapshot, takes a transaction-consistent dump, recreates only the `_staging`
database, performs a serialization-safe URL rewrite with WP-CLI, clears
WordPress cron state, and verifies that tables exist. The service also sets
`DISABLE_WP_CRON=1`, disables PHP mail delivery, and sets
`HECTV_DISABLE_PAYMENTS=1`. Payment credentials are removed from the PHP
process before Apache starts, and payment operations return a 503 response.

```bash
bash scripts/staging/stop.sh
bash scripts/staging/refresh-db.sh
bash scripts/staging/start.sh
```

After HEC validation or a production release:

```bash
bash scripts/staging/stop.sh
```

Stopping ECS does not delete the logical database. Its incremental cost is
storage, backup storage, and any I/O it generated while used. Refresh it before
the next release cycle.

## Public frontend integration

`development.hecmedia.org` uses Lambda@Edge and GitHub Actions runners whose
egress addresses are not stable office/VPN CIDRs. To let that frontend read
WordPress, set `public_read_only_mode=true` and allow HTTPS from
`0.0.0.0/0` only after changing the `hectv_staging_app` database grants to
`SELECT` on `hectv_staging.*`. The container refuses to start unless it verifies
that this is the runtime user's only database grant. Public read-only mode makes
the EFS uploads mount read-only and changes the ALB to an allowlist: GraphQL
GET/POST, selected REST GET namespaces, and uploads are forwarded;
every other request returns 403. This also closes WordPress's `rest_route`
query-string bypass because neither `/` nor the REST discovery root is
forwarded. A staging-only must-use plugin also rejects authentication and every
non-read REST method before WordPress dispatch, and rejects GraphQL mutation
operations before resolver execution. The staging JWT secret is unique, so
production tokens are invalid here. Cron, mail, and payments remain disabled.

Client editing uses the separate `hectv-wp-staging-admin` ECS service and
`staging_admin_secret_arn`. The ALB sends `/wp-admin`, `/wp-login.php`, REST
writes, and authenticated REST reads to that service. Anonymous REST reads and
all static assets remain on the public service, so the public site does not
depend on an editor service that is normally scaled to zero. Its database user
is `hectv_staging_editor`, which has DML privileges only on
`hectv_staging.*`; its EFS mount is writable. The public GraphQL/REST service
uses `hectv_staging_app`, which must retain only `USAGE` plus `SELECT` on
`hectv_staging.*`, and a read-only EFS mount. The services never share a
database credential or task role.

Never enable public read-only mode while the public runtime database user has
write privileges. Refresh requires temporary admin credentials and must return
the public runtime user to SELECT-only before restarting staging. Start the
writable admin service only for an explicit client review window.

## Safety properties

- Refresh refuses a target without an `_staging` suffix.
- Refresh refuses to run while the staging service is active.
- Refresh requires both ECS desired and running task counts to be zero.
- Source and target database names must differ.
- The staging task disables payment operations even if Stripe keys are present
  in the runtime secret.
- A manual Aurora snapshot must become available before staging is recreated.
- Pre-refresh snapshots are retained for recovery and must be pruned under the
  approved backup-retention policy, never automatically by the refresh.
- The dump is mode `0600`, removed on exit, and never written into the repository.
- Passwords are passed through `MYSQL_PWD`, not command arguments or logs.
- Staging uploads use the `/staging-uploads` EFS Access Point, never the
  production uploads root.
- Public ALB ingress requires explicit `public_read_only_mode=true`, sensitive
  public routes are blocked at the listener, and the public runtime database
  user must be SELECT-only.
- The writable admin service uses a distinct Secrets Manager secret, task role,
  target group, and database user; its routes never reach the public target
  group.
- WordPress pins and validates the staging host instead of trusting `Host`.
- Unique staging salts and Stripe test keys are mandatory.
- Production DNS and the production ECS/EB service are not modified.

## Terraform state

Shared security-group and EFS changes require remote state and locking. Bootstrap
the dedicated encrypted S3 state bucket and lock table once, then initialize:

```bash
terraform -chdir=infra/staging init \
  -backend-config=backend.hcl
```

Use `backend.hcl.example` as the non-secret template. Never apply staging from
local state.
