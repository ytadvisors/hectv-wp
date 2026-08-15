# HEC TV — WordPress CMS (`hectv-wp`)

Headless WordPress backend for **[HEC Media / HEC TV](https://hecmedia.org)** — the content system that powers program schedules, articles, video metadata, menus, and site settings consumed by the Next.js frontend (`hecmedia`).

Built and modernized by **[YT Advisors](https://ytadvisors.com)** for the HEC client engagement.

---

## Temporary public visibility

> **This repository is temporarily public** so HEC stakeholders can review engineering work.
>
> It will return to **private** when that review window closes. Do not treat this clone as a long-term open-source product or as a place to file unsolicited issues.
>
> **Do not commit secrets or data exports.** Runtime credentials must load from environment / AWS Secrets Manager. Database backups, generated dumps, and live configuration do not belong in this repository.

---

## What this system is

| Layer | Role |
|-------|------|
| **WordPress (this repo)** | CMS of record — posts, custom post types, ACF field groups, menus, site settings |
| **WPGraphQL + owned compatibility** | GraphQL API contract used by the headless frontend |
| **Next.js frontend** (`hecmedia`, separate repo) | Public site UX; queries this CMS over GraphQL |
| **Local staging** | Fixture-only Docker Compose harness bound to `127.0.0.1`; no AWS staging resources |
| **AWS production** | ECS/Fargate WordPress origin behind the production ALB and CloudFront |

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
- `infra/staging` — historical AWS staging definitions retained as decommission evidence; do not apply
- `infra/production` & `scripts/production` — production infrastructure and guarded release/health tooling

See `docs/` for the full contract and runbooks.

---

## Engineering highlights (for reviewers & portfolio readers)

1. **GraphQL contract discipline**  
   Frontend operations inventory, ACF-shaped object groups, and staging disposition are documented in `docs/WPGRAPHQL-SCHEMA-CONTRACT.md`. Compatibility is **owned code**, not an unmaintained fork of licensed plugins.

2. **CMS fields in git, not only in the live DB**  
   Production ACF groups are versioned as export + PHP registration (`docs/CMS-FIELDS.md`) so field definitions survive environments and reviews.

3. **Local, cost-free staging**
   Docker Compose runs fixture-only WordPress and MySQL on loopback, with no production credentials or AWS staging resources. Lifecycle: `docs/STAGING-LIFECYCLE.md`.

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

Requires Docker, `jq`, and `curl`. Do **not** point this harness at production credentials.

```bash
cd staging-harness
cp .env.example .env   # fill local-only values
docker compose up -d --build
# seed + contract checks — see staging-harness/RUNBOOK.md
./seed.sh
./scripts/contract-test.sh
```

The local-only staging policy and teardown commands are documented in `docs/STAGING-LIFECYCLE.md`. Never point the harness at production credentials or production data.

---

## Production releases

Production releases use `.github/workflows/production-deploy.yml` and the
protected GitHub `production` environment. After local Docker acceptance, the
workflow builds the exact merged `main` commit directly into the immutable
production ECR repository, verifies its release annotation and ARM64 manifest,
checks the live production baseline before its first service update, deploys
through the existing ECS circuit breaker, and automatically restores the
recorded task definition if any post-update verification fails. See
`infra/github-production/README.md` for the least-privilege OIDC role contract.

---

## Documentation map

| Doc | Purpose |
|-----|---------|
| [`docs/CMS-FIELDS.md`](docs/CMS-FIELDS.md) | Git-canonical ACF / site settings / menus |
| [`docs/NEWSLETTER-INTEGRATION.md`](docs/NEWSLETTER-INTEGRATION.md) | CAPTCHA-protected React-to-WordPress Mailchimp subscription bridge |
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
| Production origin | ECS/Fargate production service behind the production ALB |
| Staging | Local Docker Compose at `http://127.0.0.1:8092` |

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

Default branch: `main`. Changes ship via branch → PR → review → merge.
