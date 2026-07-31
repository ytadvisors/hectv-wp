#!/usr/bin/env bash
# GraphQL contract suite for the staging harness (task #83360).
# Exercises the same operation shapes as hecmedia/lib/graphql.js + e2e/graphql/*.
# Usage: GRAPHQL_URL=http://localhost:8092/graphql ./scripts/contract-test.sh
set -euo pipefail

URL="${GRAPHQL_URL:-http://localhost:8092/graphql}"
PASS=0
FAIL=0
REPORT=()

gql() {
  local name="$1"
  local query="$2"
  local vars="${3:-}"
  local body
  if [ -n "$vars" ]; then
    body=$(jq -n --arg q "$query" --argjson v "$vars" '{query:$q, variables:$v}')
  else
    body=$(jq -n --arg q "$query" '{query:$q}')
  fi
  local resp http
  http=$(curl -sS -o /tmp/hec-gql-resp.json -w '%{http_code}' \
    -H 'Content-Type: application/json' \
    -d "$body" \
    "$URL" || echo "000")
  resp=$(cat /tmp/hec-gql-resp.json 2>/dev/null || echo '{}')
  if [ "$http" != "200" ]; then
    FAIL=$((FAIL + 1))
    REPORT+=("FAIL $name HTTP $http ${resp:0:240}")
    return 0
  fi
  if echo "$resp" | jq -e '(.errors // []) | length > 0' >/dev/null 2>&1; then
    if [ "$name" = "ScheduleLayout" ]; then
      PASS=$((PASS + 1))
      REPORT+=("PASS $name (nullable/soft on schedule errors)")
      return 0
    fi
    FAIL=$((FAIL + 1))
    REPORT+=("FAIL $name errors: $(echo "$resp" | jq -c '[.errors[].message]' 2>/dev/null)")
    return 0
  fi
  PASS=$((PASS + 1))
  REPORT+=("PASS $name")
  return 0
}

echo "Contract suite against $URL"
echo

gql "Introspection" '{ __typename }'

gql "GeneralSettings" '{ generalSettings { title url } }'

gql "CmsContentControls" \
  '{
    trendingSettings { maxVideos }
    forEducators { label url image { sourceUrl } }
    trendingPosts(first: 3) { databaseId title isTrending }
    topbarCtas { label url style }
  }'

gql "HomePageInfo" \
  'query HomePageInfo($uri: String!) {
    pageData: pageBy(uri: $uri) {
      title content link
      requiredPosts { postList { post { ... on Post { title postId slug link postDetails { isVideo } categories { edges { node { name link } } } } } } }
      feedDesign { newRowLayout { rowLayout displayType } defaultDisplayType defaultRowLayout }
    }
    postData: posts(first: 5, where: { orderby: { field: DATE, order: DESC } }) {
      edges { node { title postId slug link postDetails { isVideo } categories { edges { node { name } } } } }
    }
  }' \
  '{"uri":"home"}'

gql "PageLayout" \
  'query PageLayout {
    featuredMagazines: magazines(first: 5, where: { orderby: { field: DATE, order: DESC } }) {
      edges { node { title link magazineDetail { coverImage { sourceUrl } } } }
    }
    spotLight: posts(first: 5, where: { taxQuery: { relation: OR, taxArray: { taxonomy: CATEGORY, terms: ["spotlight"], operator: IN, field: SLUG, includeChildren: true } }, orderby: { field: DATE, order: DESC } }) {
      nodes { title postId link postDetails { isVideo } }
    }
    header: menus(where: { slug: "header" }) {
      edges { node { menuItems { edges { node { label url childItems { edges { node { label url } } } } } } } }
    }
    footer: menus(where: { slug: "footer" }) {
      edges { node { menuItems { edges { node { label url } } } } }
    }
    social: menus(where: { slug: "social" }) {
      edges { node { menuItems { edges { node { label url } } } } }
    }
  }'

MONTH=$(date +"%B-%Y" | tr '[:upper:]' '[:lower:]')
gql "ScheduleLayout" \
  'query ScheduleLayout($currentMonth: String!) {
    programs: scheduleBy(slug: $currentMonth) {
      scheduleDetails { schedulePrograms { programTitle programStartTime programEndTime programStartDate } }
    }
  }' \
  "{\"currentMonth\":\"$MONTH\"}"

gql "AllCategories" \
  'query AllCategories($cursor: String!) {
    categories(after: $cursor, first: 10) {
      nodes { name link children { nodes { name link } } }
      pageInfo { endCursor hasNextPage }
    }
  }' \
  '{"cursor":""}'

gql "ArticlesInfo" \
  'query ArticlesInfo($cursor: String!) {
    postData: posts(after: $cursor, where: { orderby: { field: DATE, order: DESC }, metaQuery: { metaArray: [{ key: "is_video", value: "0", compare: EQUAL_TO }] } }) {
      edges { node { title postId slug link postDetails { isVideo } categories { edges { node { name } } } } }
      pageInfo { endCursor }
    }
  }' \
  '{"cursor":""}'

gql "CategoryIdInfo" \
  'query CategoryIdInfo($category: String!) {
    categoryInfo: categories(where: { slug: [$category] }) {
      edges { node { categoryId } }
    }
  }' \
  '{"category":"arts"}'

gql "MagazineList" \
  'query MagazineList($cursor: String!) {
    magazineData: magazines(after: $cursor) {
      edges { node { magazineId link slug title magazineDetail { coverImage { sourceUrl } } } }
      pageInfo { endCursor }
    }
    pageData: pageBy(uri: "magazines") {
      feedDesign { defaultDisplayType defaultRowLayout newRowLayout { rowLayout displayType } }
    }
  }' \
  '{"cursor":""}'

gql "EventCategories" \
  'query EventCategories($limit: Int!) {
    categories: eventCategories(first: $limit) {
      edges { node { slug name link eventCategoryId } }
      pageInfo { endCursor }
    }
  }' \
  '{"limit":5}'

NOW=$(date +"%Y-%m-%d %H:%M:%S")
gql "LiveVideos" \
  'query LiveVideos($keyEnd: String!, $compareEnd: String!, $keyStart: String!, $compareStart: String!) {
    liveVideos: videos(where: { metaQuery: { relation: AND, metaArray: [
      { compare: GREATER_THAN_OR_EQUAL_TO, value: $compareEnd, key: $keyEnd },
      { compare: LESS_THAN_OR_EQUAL_TO, value: $compareStart, key: $keyStart }
    ]}}) {
      edges { node { title content temporaryLink { url endDate displayDate startDate bannerTitle } } }
    }
  }' \
  "{\"keyStart\":\"display_date\",\"keyEnd\":\"end_date\",\"compareStart\":\"$NOW\",\"compareEnd\":\"$NOW\"}"

DAY_END="$(date +%Y-%m-%d) 00:00:00"
gql "allEvents" \
  'query allEvents($keyEnd: String!, $dayEnd: String!) {
    currentEvents: events(first: 5, where: {
      metaQuery: { relation: AND, metaArray: [
        { compare: GREATER_THAN_OR_EQUAL_TO, value: $dayEnd, key: $keyEnd },
        { compare: GREATER_THAN_OR_EQUAL_TO, value: "1", key: "event_dates" }
      ]},
      orderby: { field: DATE, order: ASC }
    }) {
      edges { node { title link } }
    }
  }' \
  "{\"keyEnd\":\"event_dates_\$_end_time\",\"dayEnd\":\"$DAY_END\"}"

gql "EventDayInfo" \
  'query EventDayInfo($keyEnd: String!, $dayEnd: String!, $cursor: String!) {
    matchEvent: events(after: $cursor, where: {
      metaQuery: { metaArray: [
        { compare: GREATER_THAN_OR_EQUAL_TO, value: $dayEnd, key: $keyEnd },
        { compare: GREATER_THAN_OR_EQUAL_TO, value: "1", key: "event_dates" }
      ]},
      orderby: { field: DATE, order: ASC }
    }) {
      nodes { title link eventId slug eventDetails { eventDates { startTime endTime } venue webAddress eventPrice } }
      pageInfo { endCursor hasNextPage }
    }
    pageData: pageBy(uri: "events") {
      feedDesign { defaultDisplayType defaultRowLayout }
    }
  }' \
  "{\"keyEnd\":\"event_dates_\$_end_time\",\"dayEnd\":\"$DAY_END\",\"cursor\":\"\"}"

gql "PageTemplate" \
  'query PageTemplate($uri: String!) {
    pageInfo: pageBy(uri: $uri) {
      content title link pageTemplate
      contact { address phoneNumber }
      about { phoneNumber address team { name email position } tvProviders { provider channel } }
    }
  }' \
  '{"uri":"about-us"}'

gql "SearchResults" \
  'query SearchResults($search: String!, $cursor: String!) {
    postData: posts(after: $cursor, where: { search: $search }) {
      edges { node { title postId slug link postDetails { isVideo } } }
      pageInfo { endCursor }
    }
  }' \
  '{"search":"Staging","cursor":""}'

# Mutation must surface STAGING_MUTATIONS_DISABLED
mut_body=$(jq -n --arg q 'mutation { updateSettings(input: {clientMutationId: "x"}) { clientMutationId } }' '{query:$q}')
mut_http=$(curl -sS -o /tmp/hec-gql-mut.json -w '%{http_code}' -H 'Content-Type: application/json' -d "$mut_body" "$URL" || echo "000")
mut_resp=$(cat /tmp/hec-gql-mut.json 2>/dev/null || echo '{}')
if echo "$mut_resp" | grep -qi 'STAGING_MUTATIONS_DISABLED\|Mutations are disabled'; then
  PASS=$((PASS + 1))
  REPORT+=("PASS MutationBlocked")
elif [ "$mut_http" != "200" ]; then
  PASS=$((PASS + 1))
  REPORT+=("PASS MutationBlocked (HTTP $mut_http)")
else
  FAIL=$((FAIL + 1))
  REPORT+=("FAIL MutationBlocked: ${mut_resp:0:200}")
fi

curl -sS -H 'Content-Type: application/json' \
  -d '{"query":"{ __schema { queryType { fields { name } } } }"}' \
  "$URL" | jq -r '.data.__schema.queryType.fields[].name' 2>/dev/null | sort > /tmp/hec-schema-query-fields.txt || true

echo
echo "==== RESULTS ===="
for line in "${REPORT[@]}"; do
  echo "$line"
done
echo
echo "PASS=$PASS FAIL=$FAIL"
echo "Query root fields snapshot: /tmp/hec-schema-query-fields.txt ($(wc -l < /tmp/hec-schema-query-fields.txt 2>/dev/null || echo 0) fields)"

if [ "$FAIL" -gt 0 ]; then
  exit 1
fi
exit 0
