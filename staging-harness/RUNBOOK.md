# HEC WPGraphQL staging harness (task #83360)

Isolated, fixture-only WordPress + modern WPGraphQL for HEC Media staging modernization.

## Safety

- No production AWS, dumps, credentials, or licensed ACF bridge.
- Ports bind to `127.0.0.1` only.
- GraphQL mutations are blocked in the staging mu-plugin.
- Production remains untouched and requires a separate explicit approval.

## Components

| Path | Role |
|---|---|
| `docker-compose.yml` | WordPress 6.8 / PHP 8.2 / MySQL 5.7 |
| `seed.sh` | Installs pinned WPGraphQL + wp-api-menus; seeds fixtures |
| `mu-plugins/hectv-graphql-compat.php` | **Owned** CPT + GraphQL field registrations (frontend contract) |
| `mu-plugins/hectv-v1-stub.php` | **Stub boundary** for `hectv/v1` REST (not production auth/video) |
| `scripts/contract-test.sh` | GraphQL contract suite mirroring `hecmedia/lib/graphql.js` |

## Deploy to worker-mba

```bash
rsync -a --delete staging-harness/ worker-mba:~/hectv-wp-staging/
ssh worker-mba 'cd ~/hectv-wp-staging && cp -n .env.example .env && docker compose down -v && docker compose up -d && ./seed.sh'
```

Endpoints on worker-mba loopback:

- WordPress / GraphQL: `http://127.0.0.1:8092` / `http://127.0.0.1:8092/graphql`
- MySQL: `127.0.0.1:13308`

Tunnel from another host:

```bash
ssh -L 8092:localhost:8092 worker-mba
```

## Contract tests

```bash
ssh worker-mba 'cd ~/hectv-wp-staging && GRAPHQL_URL=http://localhost:8092/graphql ./scripts/contract-test.sh'
```

## Pin versions

Override at seed time:

```bash
WPGRAPHQL_VERSION=2.1.1 ./seed.sh
```

Record the active versions with `docker compose run --rm wpcli plugin list`.

## Rollback (staging only)

```bash
cd ~/hectv-wp-staging
docker compose down -v   # destroys fixture volumes only
# restore previous rsync tree if needed, then:
docker compose up -d && ./seed.sh
```

## Relation to hecmedia `dev-infra/wordpress`

The older core-only harness (`hecmedia/dev-infra/wordpress`, port 8091) remains for REST-focused app work. This harness (port 8092) is the WPGraphQL modernization target used by task #83360 evidence.
