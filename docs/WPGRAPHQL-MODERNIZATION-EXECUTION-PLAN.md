# HEC Media WPGraphQL modernization execution plan

Status: approved for planning and staging execution by Yomi, 2026-07-24.  
Production deployment: not authorized by this approval. It requires a separate
explicit approval after the staging evidence and rollback package are reviewed.

## Objective

Replace HEC Media's production-only WPGraphQL 0.4.0 fork with a supported
WPGraphQL stack, preserve HEC's custom GraphQL contract, update the headless
frontend for the modern schema, and prove the coordinated change in the
isolated worker-mba WordPress staging environment before production changes.

## Verified baseline

- Production Elastic Beanstalk environment: `hectv-wp-prod`, application
  `wordpress-beanstalk`, region `us-east-2`.
- Active production application version:
  `app-1fa0b-221111_100259934756`.
- Production platform: PHP 7.1 on 64-bit Amazon Linux 2.6.4.
- The production source bundle contains:
  - custom `WP GraphQL - YT Advisors` 0.4.0;
  - `WPGraphQL for Advanced Custom Fields - YT Advisors` 0.3.1;
  - custom meta-query and tax-query extensions 0.1.0;
  - HEC's `hectv` mu-plugin 0.7 and `hectv-basic` theme;
  - licensed ACF-related components and database-held field definitions.
- The isolated staging harness currently runs WordPress 6.8/PHP 8.2,
  WPGraphQL 2.18.0, MySQL 5.7, and the site-options fixtures.
- The current frontend expects modern menu fields such as
  `parentDatabaseId`; production's 0.4.0 schema does not expose them.

## Non-negotiable safety boundaries

1. Production is read-only until the final, separately approved rollout.
2. No production database or uploads are copied into the ordinary workspace,
   repository, backups, or S3.
3. Licensed plugin source is not committed or redistributed.
4. Secrets, `wp-config.php`, environment files, caches, and credentials are
   excluded from all extracted artifacts.
5. Every code change ships through branch, PR, cross-family review, green CI,
   and merge.
6. Frontend staging remains no-send and the WordPress staging endpoint remains
   read-only from the public Funnel.
7. Any production rollout must have both an Elastic Beanstalk application
   version rollback and a database snapshot restore procedure recorded and
   verified before deployment.

## Phase 1 — Reproducible staging baseline

1. Record immutable SHA-256 manifests for the allowlisted production code:
   HEC mu-plugin/theme and open-source GraphQL extensions.
2. Build a staging-only compatibility package from:
   - HEC-owned code recovered from the active production bundle;
   - supported upstream WPGraphQL releases;
   - local fixtures that reproduce required ACF field shapes without copying
     production records or licensed code.
3. Pin WordPress, PHP, MySQL, and plugin versions in the harness.
4. Seed deterministic pages, posts, menus, taxonomies, magazines, events,
   schedules, and site-option fixtures.

Exit gate:

- The harness starts from an empty local volume with one documented command.
- No secret-scan findings.
- A schema snapshot and fixture manifest are reproducible.

## Phase 2 — Modern GraphQL schema migration

1. Inventory the production schema by read-only introspection and save a
   normalized schema contract without data values.
2. Register HEC custom post types and taxonomies with modern WPGraphQL names.
3. Recreate required HEC fields with explicit, tested resolvers instead of
   relying on undocumented production drift.
4. Replace the custom meta/tax-query forks with supported APIs or narrowly
   maintained compatibility code.
5. Implement menu hierarchy fields and filters required by the frontend.
6. Keep mutations and all outward integrations disabled in staging.

Exit gate:

- Production-contract queries and modern frontend queries both pass against
  staging.
- Donation/payment code is covered with Stripe fully mocked.
- Public staging GraphQL rejects mutations.

## Phase 3 — Frontend migration

1. Update `ytadvisors/hecmedia` queries to the documented modern schema.
2. Keep optional content isolated so a missing editorial value cannot blank
   the site shell.
3. Run unit, lint, build, API-contract, route, and rendered-page checks.
4. Validate nested navigation, rail promotion, Trending Now, article image
   sizing, header CTAs, magazines, schedules, events, search, and forms.

Exit gate:

- All frontend unit and integration suites pass.
- `development.hecmedia.org` renders from the isolated staging WordPress.
- Forms remain no-send and WordPress writes remain blocked.

## Phase 4 — Production rehearsal and rollout package

Prepare, but do not execute:

1. Exact plugin and platform compatibility matrix.
2. Database migration assessment and pre-deploy backup commands.
3. New Elastic Beanstalk application-version build with checksums.
4. Smoke-test script covering GraphQL, REST, site rendering, authentication,
   donations in non-charging mode, and rollback health.
5. Rollback procedure:
   - restore application version
     `app-1fa0b-221111_100259934756`;
   - restore the pre-deploy database snapshot if schema/data migration ran;
   - invalidate caches and rerun the smoke suite.
6. Expected outage, responsible operator, observation window, and stop
   conditions.

Exit gate:

- Reviewed production diff, green staging evidence, verified backups, tested
  rollback commands, and Yomi's separate explicit production approval.

## Production stop conditions

Do not deploy if any of the following is true:

- staging schema or frontend integration is not fully green;
- licensed dependencies or their upgrade rights are unresolved;
- a required field exists only in production database state and has not been
  recreated deterministically;
- donation/payment behavior lacks passing mocked characterization tests;
- rollback has not been rehearsed;
- production approval is absent or ambiguous.

## Evidence record

Each phase records:

- source and dependency checksums;
- secret-scan results;
- test commands and run URLs;
- schema diff;
- staging screenshots and endpoint checks;
- reviewer identity and exact reviewed commit;
- deployment or rollback identifiers when applicable.

## Execution ownership and monitoring

Jerome owns implementation. Kronos monitors the program and holds its gates.
Planning approval does not transfer production-deploy authority.

| Work item | Owner | Kronos monitoring gate |
|---|---|---|
| Reproducible staging baseline | Jerome | Review manifest, secret scan, dependency/licensing boundary, and clean-start proof |
| Modern GraphQL schema migration | Jerome | Review schema diff, mutation blocking, mocked payment tests, and contract results |
| Frontend query migration | Jerome | Review PR/CI state, full integration results, and rendered staging evidence |
| Client-feed status update | Jerome | Review for accuracy, client-safe wording, and absence of premature completion claims |
| Production rollout package | Jerome | Review backup/rollback evidence and present a separate approval request to Yomi |

Jerome posts progress evidence and blockers to the queue task at each exit gate.
Kronos keeps the execution task open until the corresponding evidence is verified,
escalates missing authority or licensed dependencies, and prevents production work
from starting without Yomi's separate explicit approval.

### Client-feed update requirement

After this plan is approved, Jerome publishes a concise HEC Media client-feed
update that:

- reports discovery of the deprecated/custom WPGraphQL version;
- explains that production remains unchanged and operational;
- describes the staging-first backend and frontend upgrade;
- identifies compatibility testing and rollback preparation as safeguards;
- states the next client-visible milestone;
- omits credentials, private topology, internal agent mechanics, and unverified
  completion claims.

Jerome posts a second feed update when staging verification is complete or when
a material blocker changes the expected timeline.
