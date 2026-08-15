# HEC WordPress local staging lifecycle

HEC staging is local Docker only. AWS contains the production WordPress runtime;
there is no AWS staging cluster, service, load balancer, database, secret,
CloudFront distribution, or ECR repository to start or recreate.

The historical `infra/staging/` and `scripts/staging/` files are retained only as
decommission evidence until their final repository cleanup. They are not an
operational staging path and must not be applied or invoked.

## Safety boundary

- Docker publishes WordPress and MySQL only on `127.0.0.1`.
- The database and uploads are fixture-only named Docker volumes.
- No production database export, AWS secret, payment key, or client content is
  loaded into the harness.
- Local credentials are `devadmin / devadmin` and are never valid outside the
  loopback harness.
- Destroying the harness removes only local fixture containers and volumes.
- A production deployment remains a separate, explicit, protected GitHub
  workflow action.

## Start from a clean fixture

Requires Docker, Docker Compose, `jq`, and `curl`.
Use `docker-compose` in place of `docker compose` when Compose is installed as
the standalone Homebrew binary.

```bash
cd staging-harness
cp -n .env.example .env
docker compose down -v
docker compose up -d --build
./seed.sh
./scripts/contract-test.sh
```

Endpoints:

- WordPress: `http://127.0.0.1:8092`
- Admin: `http://127.0.0.1:8092/wp-admin`
- GraphQL: `http://127.0.0.1:8092/graphql`
- MySQL: `127.0.0.1:13308`

The seed creates Home as post ID `31155`, activates the repository's legacy ACF
version and repeater add-on, and writes canonical `post_list` fixtures. This lets the local
block editor exercise the same Home-page ACF bridge and homepage resolver used
by production without using production data.

## Admin acceptance test

1. Sign in locally as `devadmin / devadmin`.
2. Open `wp-admin/post.php?post=31155&action=edit`.
3. Confirm the block editor remains active.
4. Confirm the two seeded Required Posts rows render.
5. Add a row, type in the Post search control, choose a fixture post, and verify
   the control retains the selection.
6. Do not use production credentials or copy production content into the test.

Run the repository contracts as well:

```bash
bash tests/staging-graphql-image-contract.sh
bash tests/production-release-path.sh
```

## Stop and remove local staging

```bash
cd staging-harness
docker compose down -v
```

Confirm no HEC local staging containers remain:

```bash
docker ps -a --filter name=hectv-wp-staging
```

## Production handoff

After the exact merged commit passes local Docker acceptance, dispatch
`.github/workflows/production-deploy.yml`. The protected workflow builds that
commit directly into the immutable production ECR repository, verifies the
ARM64 manifest and release annotation, confirms the current ECS baseline, and
performs a rolling deployment with circuit-breaker rollback. It does not call or
create any AWS staging resource.
