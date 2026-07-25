# WPGraphQL staging modernization (task #83360)

Implements Phase 1–2 of `docs/WPGRAPHQL-MODERNIZATION-EXECUTION-PLAN.md` (PR #9 head
`a9a451d2536db79def93e74696da57a90f0dfbd1`) for the **isolated staging harness only**.

## What shipped

- `staging-harness/` — Docker WordPress 6.8 / PHP 8.2 / MySQL 5.7, loopback-only
- Pinned modern upstream WPGraphQL via `seed.sh` (not the production 0.4.0 fork)
- Owned compatibility mu-plugin registering every frontend-required field/type
- Fixture seed covering posts, pages, menus, magazines, events, schedules, videos
- GraphQL mutation blocking
- Contract test script aligned to `hecmedia/lib/graphql.js`
- Schema contract inventory: `docs/WPGRAPHQL-SCHEMA-CONTRACT.md`

## Explicit non-goals

- No production Elastic Beanstalk, RDS, or plugin mutation
- No redistribution of licensed ACF or production GraphQL forks
- No client communication
- Frontend `ytadvisors/hecmedia` query migration is a follow-on phase

## Operate

See `staging-harness/RUNBOOK.md`.
