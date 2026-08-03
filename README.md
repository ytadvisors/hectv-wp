# HEC TV — WordPress CMS (`hectv-wp`)

Headless WordPress backend for **[HEC Media / HEC TV](https://hecmedia.org)** — the content system that powers program schedules, articles, video metadata, menus, and site settings consumed by the Next.js frontend (`hecmedia`).

Built and modernized by **[YT Advisors](https://ytadvisors.com)** for the HEC client engagement.

---

## Temporary public visibility

> **This repository is temporarily public** so HEC stakeholders can review engineering work.
>
> It will return to **private** when that review window closes. Do not treat this clone as a long-term open-source product or as a place to file unsolicited issues.
>
> **Do not commit secrets.** Runtime credentials load from environment / AWS Secrets Manager. Example Terraform vars and env templates are intentional; live values are not.

---

## What this system is

| Layer | Role |
|-------|------|
| **WordPress (this repo)** | CMS of record — posts, custom post types, ACF field groups, menus, site settings |
| **WPGraphQL + owned compatibility** | GraphQL API contract used by the headless frontend |
| **Next.js frontend** (`hecmedia`, separate repo) | Public site UX; queries this CMS over GraphQL |
| **AWS staging** | On-demand ECS staging (`staging-wp.hectv.org`) with an isolated `hectv_staging` database |
| **AWS production path** | Elastic Beanstalk origin; parallel **ECS/Fargate lift-and-shift** (PHP/WP upgrade deliberately separate) |

This is **not** a greenfield marketing site. It is a production CMS under active modernization: safer staging, a durable GraphQL contract, git-canonical field definitions, and a hosting cutover that does not bundle a risky PHP/WordPress upgrade into the same release.

---

## Architecture (high level)

```
Editors ──► WordPress admin (ACF / menus / site settings)
                 │
                 ▼
         WPGraphQL API  ◄── owned compatibility layer
                 │              (metaQuery, taxQuery, ACF object groups)
                 ▼
         hecmedia (Next.js) ──► public readers
```

**Owned HEC packages in-tree (highlights):**

- `wp-content/mu-plugins/hectv/` — core HEC TV plugin (admin, domain logic)
- `wp-content/mu-plugins/hectv-cms-fields/` — **git-canonical** ACF export + PHP registration, site settings, menus, GraphQL exposure
- `wp-content/mu-plugins/hectv-staging-query-compat.php` — GraphQL query compatibility for modern WPGraphQL
- `wp-content/mu-plugins/hectv-public-read-only.php` / staging content controls — staging safety rails
- `staging-harness/` — local Docker harness + seed + contract tests
- `infra/staging` & `infra/production` — Terraform for ECS paths
- `scripts/staging` & `scripts/production` — lifecycle / cutover / health probes

See `docs/` for the full contract and runbooks.

---

## Engineering highlights (for reviewers & portfolio readers)

1. **GraphQL contract discipline**  
   Frontend operations inventory, ACF-shaped object groups, and staging disposition are documented in `docs/WPGRAPHQL-SCHEMA-CONTRACT.md`. Compatibility is **owned code**, not an unmaintained fork of licensed plugins.

2. **CMS fields in git, not only in the live DB**  
   Production ACF groups are versioned as export + PHP registration (`docs/CMS-FIELDS.md`) so field definitions survive environments and reviews.

3. **Isolated, on-demand staging**  
   Separate logical DB (`hectv_staging`), ECS desired-count-zero when idle, payment keys forced to test mode, public-read-only rails. Lifecycle: `docs/STAGING-LIFECYCLE.md`.

4. **Production hosting migration without a stack bomb**  
   Cutover 1: same app bundle + digest-pinned PHP 7.1 runtime onto ECS/Fargate, new origin hostname, EB left intact, EFS identity/probe gates, Secrets Manager runtime. PHP 8.2 / core modernization is a **later**, separately validated track. See `docs/PRODUCTION-ECS-MIGRATION.md`.

5. **Safety posture**  
   Staging refuses live Stripe keys; production scripts hard-pin cluster/service names; Terraform remote state + locking; no production table aliases from staging refresh.

---

## Repository layout

```
hectv-wp/
├── wp-content/
│   ├── mu-plugins/          # HEC-owned MU plugins + vendor plugins
│   ├── plugins/             # Installed WordPress plugins
│   └── themes/
├── staging-harness/         # Local Docker WordPress + seed + contract tests
├── docs/                    # Contracts, staging, production migration
├── infra/                   # Terraform (staging + production ECS)
├── scripts/                 # Ops lifecycle scripts
├── deploy/container/        # Image entrypoint, PHP ini, healthz
├── Dockerfile*              # Production (7.1) and staging (8.2-oriented) images
├── tests/                   # PHP + shell contract / CMS tests
└── composer.json            # PHP deps (Stripe, logging, installers, …)
```

WordPress core is present for the EB/ECS compatibility path; staging images prefer digest-pinned official WordPress PHP 8.2 core with HEC `wp-content` layered on top (see staging docs).

---

## Quick start (local staging harness)

Requires Docker. Do **not** point this harness at production credentials.

```bash
cd staging-harness
cp .env.example .env   # fill local-only values
docker compose up -d
# seed + contract checks — see staging-harness/RUNBOOK.md
./seed.sh
./scripts/contract-test.sh
```

Full staging lifecycle against AWS (refresh from snapshot, start/stop ECS service, health checks) lives under `scripts/staging/` and is documented in `docs/STAGING-LIFECYCLE.md`. Use the approved AWS profile and secrets mechanism; never commit real values.

---

## Documentation map

| Doc | Purpose |
|-----|---------|
| [`docs/CMS-FIELDS.md`](docs/CMS-FIELDS.md) | Git-canonical ACF / site settings / menus |
| [`docs/WPGRAPHQL-SCHEMA-CONTRACT.md`](docs/WPGRAPHQL-SCHEMA-CONTRACT.md) | Frontend GraphQL operations & types |
| [`docs/WPGRAPHQL-STAGING-MODERNIZATION.md`](docs/WPGRAPHQL-STAGING-MODERNIZATION.md) | Staging modernization approach |
| [`docs/WPGRAPHQL-MODERNIZATION-EXECUTION-PLAN.md`](docs/WPGRAPHQL-MODERNIZATION-EXECUTION-PLAN.md) | Execution plan for GraphQL modernization |
| [`docs/STAGING-LIFECYCLE.md`](docs/STAGING-LIFECYCLE.md) | On-demand staging ops |
| [`docs/PRODUCTION-ECS-MIGRATION.md`](docs/PRODUCTION-ECS-MIGRATION.md) | EB → ECS cutover 1 |
| [`staging-harness/RUNBOOK.md`](staging-harness/RUNBOOK.md) | Local harness |

---

## Related systems

| Repo / surface | Relationship |
|----------------|--------------|
| `ytadvisors/hecmedia` | Next.js public site; GraphQL consumer of this CMS |
| Production origin | Historically Elastic Beanstalk; ECS parallel origin for cutover |
| Staging origin | `https://staging-wp.hectv.org` (when service is scaled up) |

---

## License & ownership

- WordPress core and third-party plugins retain their upstream licenses (typically GPL-compatible).
- HEC-owned MU plugins and compatibility code are authored for the **HEC TV** engagement by **YT Advisors**.
- `composer.json` declares project metadata for the HEC TV WordPress surface.

This repository contains **client work product**. Temporary public access does not grant rights to reuse HEC branding, content, production data, or non-public credentials.

---

## Maintainers

**YT Advisors** — engineering partner for HEC Media / HEC TV.  
Primary contact: [ytadvisors.com](https://ytadvisors.com)

Default branch: `develop`. Changes ship via branch → PR → review → merge.
