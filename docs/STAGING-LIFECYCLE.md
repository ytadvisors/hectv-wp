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
export PROD_DB_NAME='<production database name loaded from the approved secret>'
export DB_HOST='Aurora writer endpoint'
export DB_ADMIN_USER='runtime-loaded admin user'
export DB_ADMIN_PASSWORD='runtime-loaded admin password'
export STAGING_HEALTH_URL='https://staging-wp.hectv.org/wp-json/'
```

## Refresh and start

The refresh script requires staging desired count zero. It creates an Aurora
snapshot, takes a transaction-consistent dump, recreates only the `_staging`
database, imports it, and verifies that tables exist.

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

## Safety properties

- Refresh refuses a target without an `_staging` suffix.
- Refresh refuses to run while the staging service is active.
- Source and target database names must differ.
- A manual Aurora snapshot must become available before staging is recreated.
- The dump is mode `0600`, removed on exit, and never written into the repository.
- Passwords are passed through `MYSQL_PWD`, not command arguments or logs.
- Production DNS and the production ECS/EB service are not modified.
