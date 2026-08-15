# HEC WPGraphQL staging harness (task #83360)

Isolated, fixture-only WordPress + modern WPGraphQL for HEC Media local staging.

## Safety

- No production AWS, dumps, credentials, or production data.
- Ports bind to `127.0.0.1` only.
- GraphQL mutations are blocked in the staging mu-plugin.
- Production remains untouched and requires a separate explicit approval.

## Components

| Path | Role |
|---|---|
| `docker-compose.yml` | WordPress 6.8 / PHP 8.2 / MySQL 5.7; builds git-canonical ACF, repeater, and CMS fields into the local image |
| `seed.sh` | Activates ACF + its repository-owned repeater add-on, installs pinned WPGraphQL + wp-api-menus, and seeds canonical Home pins |
| `mu-plugins/hectv-graphql-compat.php` | **Owned** CPT + GraphQL field registrations (frontend contract) |
| `mu-plugins/hectv-v1-stub.php` | **Stub boundary** for `hectv/v1` REST (not production auth/video) |
| `../wp-content/mu-plugins/hectv-cms-fields*` | **Git-canonical** Post Details ACF, site settings, header_actions menu |
| `scripts/contract-test.sh` | GraphQL contract suite mirroring `hecmedia/lib/graphql.js` |

See also: `docs/CMS-FIELDS.md` (Trending, max videos, For Educators logo, Support/Subscribe).

## Run locally

Use `docker-compose` in place of `docker compose` when Compose is installed as
the standalone Homebrew binary.

```bash
cd staging-harness
cp -n .env.example .env
docker compose down -v
docker compose up -d --build
./seed.sh
```

Endpoints on loopback:

- WordPress / GraphQL: `http://127.0.0.1:8092` / `http://127.0.0.1:8092/graphql`
- MySQL: `127.0.0.1:13308`

## Contract tests

```bash
GRAPHQL_URL=http://localhost:8092/graphql ./scripts/contract-test.sh
```

## Pin versions

Override at seed time:

```bash
WPGRAPHQL_VERSION=2.1.1 ./seed.sh
```

Record the active versions with `docker compose run --rm wpcli plugin list`.

## Rollback (staging only)

```bash
cd staging-harness
docker compose down -v   # destroys fixture volumes only
docker compose up -d && ./seed.sh
```

## Relation to hecmedia `dev-infra/wordpress`

The older core-only harness (`hecmedia/dev-infra/wordpress`, port 8091) remains for REST-focused app work. This harness (port 8092) is the WPGraphQL modernization target used by task #83360 evidence.
