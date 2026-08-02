# HEC frontend GraphQL contract inventory

Source of truth: `ytadvisors/hecmedia` `lib/graphql.js` and `tests/e2e/graphql/*.e2e.test.js`.  
Inventory date: 2026-07-25. Staging modernization task: #83360.  
No production data values are recorded here — shapes and field names only.

## Operations

| Operation | Frontend export | Required roots / types |
|---|---|---|
| HomePageInfo | `GET_HOME_PAGE` | `pageBy`, `posts`, `requiredPosts`, `feedDesign`, `postDetails` |
| PageLayout | `GET_LAYOUT` + `GET_HEADER_MENU` | `posts`+`categoryName`, root `menuItems(where:{location})`, footer/social `menus` |
| ScheduleLayout | `GET_SCHEDULE` | `scheduleBy`, `scheduleDetails.schedulePrograms` |
| AllCategories | `GET_ALL_PAGE_CATEGORY` | `categories`, `children`, `pageInfo` |
| PageCategory | `GET_PAGE_CATEGORY` | `posts(categoryIn)`, `postDetails` |
| CategoryIdInfo | `GET_CATEGORY_ID` | `categories(where.slug)`, `categoryId` |
| CategoryInfo | `GET_CATEGORY_INFO` | `posts`+`categoryName` |
| CurrentPost | `GET_PAGE_INFO` | `postBy`, `postDetails`, `relatedPosts`, `postEvents`/`Event` |
| ArticlesInfo | `GET_ARTICLES` | `posts`+`metaQuery(is_video)` |
| LiveVideos | `GET_LIVE_VIDEOS` | `videos`+`metaQuery`, `temporaryLink` |
| SearchResults | `GET_SEARCH_RESULTS` | `posts(where.search)` |
| PageTemplate | `GET_PAGE_TEMPLATE` | `pageBy`, `pageTemplate`, `contact`, `about` |

## Custom object groups (production ACF-shaped)

Registered in staging by **owned** code in
`staging-harness/mu-plugins/hectv-graphql-compat.php` (not the 0.4.0 fork, not
licensed WPGraphQL-for-ACF):

- `postDetails` → videoImage, postHeader, postHero, isVideo, youtubeId, vimeoId, embedUrl,
  showPodcasts, hidePageThumbnail, pollForUpdates, relatedPosts, postEvents
- `feedDesign` → newRowLayout{rowLayout,displayType}, defaultDisplayType, defaultRowLayout
- `requiredPosts` → postList{post}
- `magazineDetail` → coverImage, magazinePost{post}
- `eventDetails` → eventDates{startTime,endTime}, eventImage, venue, webAddress,
  eventPrice, externalImage, eventPosts
- `scheduleDetails` → schedulePrograms{programTitle,programStartTime,programEndTime,programStartDate}
- `temporaryLink` → url, endDate, displayDate, startDate, showTime, bannerTitle,
  bannerBackground, bannerTextColor
- `contact` / `about` page groups

## Custom post types / taxonomies

| WP slug | GraphQL single | GraphQL plural |
|---|---|---|
| magazine | Magazine | magazines |
| event | Event | events |
| schedule | Schedule | schedules |
| video | Video | videos |
| event_category (taxonomy) | EventCategory | eventCategories |

`magazine`, `event`, and `event_category` are deprecated site features. Their
plural GraphQL roots remain registered temporarily so an older frontend can run
during the staggered deployment, but the compatibility layer forces all three
connections to return empty lists. New frontend code must not query them.

## Filters / args

| Arg | Purpose | Staging disposition |
|---|---|---|
| `where.categoryName` | Spotlight + category feeds | Native modern WPGraphQL argument |
| `where.taxQuery` | Non-category HEC compatibility cases | Owned registration + WP_Query mapper |
| `where.metaQuery` | is_video, event/video date windows | Owned registration + WP_Query mapper |
| `shouldOutputInFlatList` | Legacy contract only; no longer sent by hecmedia | Accepted no-op during the phased migration |
| `parentDatabaseId` (menus) | Modern menu hierarchy | Provided by upstream WPGraphQL and requested from the root `menuItems` connection |

## Stub boundary (not GraphQL)

`hectv/v1` REST (`livevideos/live`, `token/*`, `users/*`) remains a **fixture stub**
in `hectv-v1-stub.php`. Production auth/video logic is not present and is not
claimed. Frontend GraphQL `videos` CPT is the modernization path for live banners.

## Licensing provenance

| Component | Disposition |
|---|---|
| Upstream WPGraphQL (pinned in seed) | Open source; installed at seed time, not vendored as a fork |
| wp-api-menus | Open source; installed at seed time |
| hectv-graphql-compat.php | Newly authored YT Advisors / HEC-owned compatibility code |
| hectv-v1-stub.php | Newly authored fixture stub; not production plugin |
| Production WPGraphQL 0.4.0 YT fork | **Not** redistributed |
| Production WPGraphQL-for-ACF 0.3.1 fork | **Not** redistributed |
| Licensed ACF Pro / field exports | **Not** copied; field shapes recreated via meta + owned resolvers |
| Production DB / uploads | **Not** copied |

## Modern vs production schema notes

- Production runs custom WPGraphQL 0.4.0 + ACF bridge; staging runs modern
  upstream WPGraphQL + owned field registrations.
- Image fields may be null in fixtures (no media library seeds required for
  structural contract pass).
- Header navigation uses the modern root `menuItems(where:{location:PRIMARY})`
  connection. Footer and social menus remain slug-addressed menu connections.
- Mutations are disabled in staging.
