# Schema diff: core-only harness → modernized staging (task #83360)

## Before (hecmedia-dev-wp core-only, WPGraphQL default)

Typical query roots only (no HEC CPTs / owned field groups):
- posts, pages, categories, tags, menus, mediaItems, generalSettings, users, comments, ...

Custom frontend fields such as `postDetails`, `feedDesign`, `requiredPosts`,
`magazines`, `events`, `videos`, `schedules`, `eventCategories`, `metaQuery`,
`taxQuery` were **absent** — all four `tests/e2e/graphql/*.e2e.test.js` suites
failed against that instance by design.

## After (hectv-wp-staging, WPGraphQL 2.18.0 + hectv-graphql-compat 0.1.0)

Added / exposed query roots (see `staging-query-root-fields.txt`):
- `magazine`, `magazines`
- `event`, `events`
- `schedule`, `schedules`
- `video`, `videos`
- `eventCategory`, `eventCategories`

Owned object fields registered on core/CPT types:
- Post.`postDetails`
- Page.`feedDesign`, `requiredPosts`, `pageTemplate`, `contact`, `about`
- Magazine.`magazineDetail`
- Event.`eventDetails`
- Schedule.`scheduleDetails`
- Video.`temporaryLink`

Owned where-args (production enum names):
- `metaQuery` / `taxQuery` on post/event/video/magazine connections

Mutations: blocked with `STAGING_MUTATIONS_DISABLED`.

Contract suite: 16/16 PASS (2026-07-25) — see `contract-results-2026-07-25.txt`.
