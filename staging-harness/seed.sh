#!/usr/bin/env bash
# Seeds the isolated HEC WPGraphQL staging harness (task #83360).
# Deterministic fixtures only — NOT a production export.
set -euo pipefail
cd "$(dirname "$0")"

# Pin modern supported WPGraphQL. Recorded in evidence deliverable.
# 2.18.0 is current wordpress.org stable (matches execution-plan baseline note).
WPGRAPHQL_VERSION="${WPGRAPHQL_VERSION:-2.18.0}"
WP_API_MENUS_VERSION="${WP_API_MENUS_VERSION:-}"

wpcli() { docker compose run --rm wpcli "$@"; }

echo "== waiting for wordpress health =="
for i in $(seq 1 40); do
  status=$(docker inspect -f '{{.State.Health.Status}}' hectv-wp-staging-wp 2>/dev/null || echo "starting")
  [ "$status" = "healthy" ] && break
  sleep 5
done
status=$(docker inspect -f '{{.State.Health.Status}}' hectv-wp-staging-wp 2>/dev/null || echo "missing")
if [ "$status" != "healthy" ]; then
  echo "ERROR: wordpress container not healthy (status=$status)" >&2
  exit 1
fi

echo "== core install (idempotent) =="
if ! wpcli core is-installed 2>/dev/null; then
  wpcli core install \
    --url="http://localhost:8092" \
    --title="HEC WPGraphQL Staging" \
    --admin_user="devadmin" \
    --admin_password="devadmin" \
    --admin_email="dev@example.com" \
    --skip-email
fi

# Force local URLs so a recycled volume never redirects off-host.
wpcli option update home 'http://localhost:8092'
wpcli option update siteurl 'http://localhost:8092'
wpcli option update blogname 'HEC WPGraphQL Staging'

echo "== plugins: WPGraphQL ${WPGRAPHQL_VERSION}, wp-api-menus =="
wpcli plugin install "wp-graphql" --version="${WPGRAPHQL_VERSION}" --force --activate
if [ -n "${WP_API_MENUS_VERSION}" ]; then
  wpcli plugin install "wp-api-menus" --version="${WP_API_MENUS_VERSION}" --force --activate || \
    wpcli plugin install "wp-api-menus" --force --activate
else
  wpcli plugin install "wp-api-menus" --force --activate
fi

echo "== plugin versions =="
wpcli plugin list --format=table

echo "== permalinks =="
wpcli rewrite structure '/%postname%/' --hard
wpcli rewrite flush --hard

echo "== menus (header/footer/social/podcasts) =="
for slug in header footer social podcasts; do
  wpcli menu create "$slug" || true
  # Clear existing items for determinism.
  for item_id in $(wpcli menu item list "$slug" --format=ids 2>/dev/null || true); do
    wpcli menu item delete "$item_id" || true
  done
done
wpcli menu item add-custom header "Home" "http://localhost:8092/" || true
wpcli menu item add-custom header "Programs" "http://localhost:8092/programs" || true
wpcli menu item add-custom header "About" "http://localhost:8092/about-us" || true
wpcli menu item add-custom footer "About" "http://localhost:8092/about-us" || true
wpcli menu item add-custom footer "Contact" "http://localhost:8092/contact" || true
wpcli menu item add-custom social "Facebook" "https://facebook.com/hectv" || true
wpcli menu item add-custom podcasts "Main Feed" "https://example.com/podcast" || true
# Assign locations if theme supports them (harmless if not).
wpcli menu location assign header primary 2>/dev/null || true

echo "== header actions menu (Support / Subscribe) =="
# Menu location registered by hectv-cms-fields (Header Actions).
wpcli menu create "Header Actions" 2>/dev/null || true
# Clear prior items for determinism.
for item_id in $(wpcli menu item list "Header Actions" --format=ids 2>/dev/null || true); do
  wpcli menu item delete "$item_id" || true
done
wpcli menu item add-custom "Header Actions" "Subscribe" "http://127.0.0.1:8092/newsletter" || true
wpcli menu item add-custom "Header Actions" "Support" "http://127.0.0.1:8092/support" || true
wpcli menu location assign "Header Actions" header_actions 2>/dev/null || true

echo "== taxonomies + categories =="
wpcli term create category "Programs" --slug=programs || true
wpcli term create category "Events" --slug=events || true
wpcli term create category "Arts" --slug=arts || true
wpcli term create category "Spotlight" --slug=spotlight || true
wpcli term create event_category "Arts" --slug=arts || true
wpcli term create event_category "Community" --slug=community || true

echo "== sample posts =="
# Delete prior seed posts by slug for idempotent reseed of known fixtures.
for slug in staging-seed-post-1 staging-seed-post-2 staging-seed-video staging-spotlight-1; do
  id=$(wpcli post list --post_type=post --name="$slug" --field=ID 2>/dev/null || true)
  if [ -n "${id:-}" ]; then
    wpcli post delete "$id" --force || true
  fi
done

post1=$(wpcli post create \
  --post_type=post \
  --post_name="staging-seed-post-1" \
  --post_title="Staging Seed Post 1" \
  --post_status=publish \
  --post_content="Fixture content for GraphQL contract tests." \
  --porcelain)
wpcli post term set "$post1" category arts programs || true
wpcli post meta update "$post1" is_video "0"
wpcli post meta update "$post1" youtube_id ""
wpcli post meta update "$post1" show_podcasts "0"

post2=$(wpcli post create \
  --post_type=post \
  --post_name="staging-seed-post-2" \
  --post_title="Staging Seed Post 2" \
  --post_status=publish \
  --post_content="Second fixture article." \
  --porcelain)
wpcli post term set "$post2" category programs || true
wpcli post meta update "$post2" is_video "0"

video_post=$(wpcli post create \
  --post_type=post \
  --post_name="staging-seed-video" \
  --post_title="Staging Seed Video" \
  --post_status=publish \
  --post_content="Video fixture." \
  --porcelain)
wpcli post term set "$video_post" category programs || true
wpcli post meta update "$video_post" is_video "1"
wpcli post meta update "$video_post" is_trending "1"
wpcli post meta update "$video_post" youtube_id "dQw4w9WgXcQ"

spot=$(wpcli post create \
  --post_type=post \
  --post_name="staging-spotlight-1" \
  --post_title="Staging Spotlight" \
  --post_status=publish \
  --post_content="Spotlight fixture." \
  --porcelain)
wpcli post term set "$spot" category spotlight || true
wpcli post meta update "$spot" is_video "0"
wpcli post meta update "$spot" is_trending "0"

echo "== extra trending video fixtures =="
for i in 1 2 3; do
  slug="staging-trending-video-$i"
  id=$(wpcli post list --post_type=post --name="$slug" --field=ID 2>/dev/null || true)
  if [ -n "${id:-}" ]; then
    wpcli post delete "$id" --force || true
  fi
  tid=$(wpcli post create \
    --post_type=post \
    --post_name="$slug" \
    --post_title="Trending Video $i" \
    --post_status=publish \
    --post_content="Trending fixture $i." \
    --porcelain)
  wpcli post term set "$tid" category programs || true
  wpcli post meta update "$tid" is_video "1"
  wpcli post meta update "$tid" is_trending "1"
  wpcli post meta update "$tid" youtube_id "jfKfPfyJRdk"
done

# Link related posts.
wpcli post meta update "$post1" related_posts "$post2"

echo "== pages: home, about-us, magazines, events =="
ensure_page() {
  local slug="$1" title="$2" content="$3"
  local id
  id=$(wpcli post list --post_type=page --name="$slug" --field=ID 2>/dev/null || true)
  if [ -z "${id:-}" ]; then
    id=$(wpcli post create \
      --post_type=page \
      --post_name="$slug" \
      --post_title="$title" \
      --post_status=publish \
      --post_content="$content" \
      --porcelain)
  fi
  echo "$id"
}

home_id=$(ensure_page "home" "Home" "Fixture home page.")
wpcli post meta update "$home_id" required_posts "${post1},${post2}"
wpcli post meta update "$home_id" default_display_type "grid"
wpcli post meta update "$home_id" default_row_layout "standard"
wpcli post meta update "$home_id" feed_design_rows '[{"rowLayout":"standard","displayType":"grid"}]'

about_id=$(ensure_page "about-us" "About Us" "About page fixture.")
wpcli post meta update "$about_id" about_phone "555-0100"
wpcli post meta update "$about_id" about_address "123 Staging St"
wpcli post meta update "$about_id" about_team '[{"name":"Dev User","email":"dev@example.com","position":"Engineer"}]'
wpcli post meta update "$about_id" about_tv_providers '[{"provider":"CableCo","channel":"12"}]'

mag_page=$(ensure_page "magazines" "Magazines" "Magazines index.")
wpcli post meta update "$mag_page" default_display_type "grid"
wpcli post meta update "$mag_page" default_row_layout "standard"
wpcli post meta update "$mag_page" feed_design_rows '[{"rowLayout":"standard","displayType":"grid"}]'

events_page=$(ensure_page "events" "Events" "Events index.")
wpcli post meta update "$events_page" default_display_type "list"
wpcli post meta update "$events_page" default_row_layout "standard"

echo "== magazines CPT =="
for slug in staging-mag-1; do
  id=$(wpcli post list --post_type=magazine --name="$slug" --field=ID 2>/dev/null || true)
  [ -n "${id:-}" ] && wpcli post delete "$id" --force || true
done
mag1=$(wpcli post create \
  --post_type=magazine \
  --post_name="staging-mag-1" \
  --post_title="Staging Magazine 1" \
  --post_status=publish \
  --post_content="Magazine fixture body." \
  --porcelain)
wpcli post meta update "$mag1" magazine_posts "$post1,$post2"

echo "== events CPT =="
for slug in staging-event-1; do
  id=$(wpcli post list --post_type=event --name="$slug" --field=ID 2>/dev/null || true)
  [ -n "${id:-}" ] && wpcli post delete "$id" --force || true
done
# Far-future dates so "upcoming" meta queries match.
start=$(date -u -v+7d +"%Y-%m-%d 10:00:00" 2>/dev/null || date -u -d "+7 days" +"%Y-%m-%d 10:00:00")
end=$(date -u -v+7d +"%Y-%m-%d 12:00:00" 2>/dev/null || date -u -d "+7 days" +"%Y-%m-%d 12:00:00")
ev1=$(wpcli post create \
  --post_type=event \
  --post_name="staging-event-1" \
  --post_title="Staging Event 1" \
  --post_status=publish \
  --post_content="Event fixture." \
  --post_excerpt="Event excerpt." \
  --porcelain)
wpcli post term set "$ev1" event_category arts || true
wpcli post meta update "$ev1" event_dates "[{\"startTime\":\"$start\",\"endTime\":\"$end\"}]"
wpcli post meta update "$ev1" 'event_dates_$_end_time' "$end"
wpcli post meta update "$ev1" 'event_dates_$_start_time' "$start"
wpcli post meta update "$ev1" event_dates_count "1"
wpcli post meta update "$ev1" venue "Staging Hall"
wpcli post meta update "$ev1" web_address "https://example.com/event"
wpcli post meta update "$ev1" event_price "Free"
wpcli post meta update "$ev1" event_posts "$post1"

echo "== schedule CPT =="
month_slug=$(date +"%B-%Y" | tr '[:upper:]' '[:lower:]')
for slug in "$month_slug"; do
  id=$(wpcli post list --post_type=schedule --name="$slug" --field=ID 2>/dev/null || true)
  [ -n "${id:-}" ] && wpcli post delete "$id" --force || true
done
sched=$(wpcli post create \
  --post_type=schedule \
  --post_name="$month_slug" \
  --post_title="Schedule $month_slug" \
  --post_status=publish \
  --porcelain)
wpcli post meta update "$sched" schedule_programs \
  "[{\"programTitle\":\"Morning Show\",\"programStartTime\":\"08:00\",\"programEndTime\":\"09:00\",\"programStartDate\":\"$(date +%Y-%m-%d)\"}]"

echo "== video CPT (live banner window) =="
for slug in staging-live-video; do
  id=$(wpcli post list --post_type=video --name="$slug" --field=ID 2>/dev/null || true)
  [ -n "${id:-}" ] && wpcli post delete "$id" --force || true
done
now=$(date +"%Y-%m-%d %H:%M:%S")
later=$(date -u -v+2H +"%Y-%m-%d %H:%M:%S" 2>/dev/null || date -u -d "+2 hours" +"%Y-%m-%d %H:%M:%S")
earlier=$(date -u -v-1H +"%Y-%m-%d %H:%M:%S" 2>/dev/null || date -u -d "-1 hour" +"%Y-%m-%d %H:%M:%S")
vid=$(wpcli post create \
  --post_type=video \
  --post_name="staging-live-video" \
  --post_title="Staging Live Video" \
  --post_status=publish \
  --post_content="Live video fixture." \
  --porcelain)
wpcli post meta update "$vid" display_date "$earlier"
wpcli post meta update "$vid" end_date "$later"
wpcli post meta update "$vid" start_date "$earlier"
wpcli post meta update "$vid" temp_url "https://example.com/live"
wpcli post meta update "$vid" banner_title "Now Live"
wpcli post meta update "$vid" banner_background "#000000"
wpcli post meta update "$vid" banner_text_color "#ffffff"

echo "== site settings (Trending max + For Educators) =="
wpcli option update hectv_trending_max_videos "5"
wpcli option update hectv_educators_url "/spotlight"
wpcli option update hectv_educators_label "For Educators"
# Logo id left 0 unless media import ran; optional attachment id override:
# wpcli option update hectv_educators_logo_id "<attachment_id>"

echo "== flush rewrite / graphql =="
wpcli rewrite flush --hard
wpcli graphql clear-schema-cache 2>/dev/null || true

echo "== seed complete =="
echo "GraphQL:  http://localhost:8092/graphql"
echo "REST:     http://localhost:8092/wp-json/"
echo "Admin:    http://localhost:8092/wp-admin  (devadmin / devadmin — local fixture only)"
echo "Site:     Settings → HEC Site Settings (max videos, educators logo)"
echo "Menus:    Appearance → Menus → Header Actions (Subscribe / Support)"
wpcli plugin list --status=active --format=table
