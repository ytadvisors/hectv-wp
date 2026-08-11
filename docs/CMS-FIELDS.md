# HEC CMS fields (git-canonical)

Source of truth for custom fields and site-wide content settings used by the
headless HECMedia app. **Do not leave field definitions only in the production
WordPress database.**

| Surface | Location in repo |
|---------|------------------|
| MU-plugin loader | `wp-content/mu-plugins/hectv-cms-fields.php` |
| ACF field groups (export) | `…/hectv-cms-fields/acf-field-groups.json` |
| ACF registration (PHP) | `…/hectv-cms-fields/register-acf.php` |
| Site settings admin | `…/site-settings.php` → **Settings → HEC Site Settings** |
| Header Actions menu | `…/menus.php` → **Appearance → Menus** |
| GraphQL | `…/graphql.php` |
| Local harness seed | `staging-harness/seed.sh` |

---

## 1. ACF field groups (export + PHP)

`acf-field-groups.json` is the production ACF Tools export (2026-08-01). It
includes **19 groups** with their original production keys (e.g. Post Details
`group_5a9bf131f2b91`, field keys `field_5…`). Using those keys preserves all
existing post meta.

### Registration rules (`register-acf.php`)

1. **Load the complete export** and register every group as a local overlay.
2. Reuse the production group key (or an existing same-title database key) so
   the overlay replaces the database definition instead of creating a duplicate
   admin panel. Original field keys remain unchanged, preserving stored values.
3. **Always ensure** the git-owned **Trending** field (`is_trending`, key
   `field_hectv_is_trending`) is available on Post Details:
   - The complete Post Details overlay nests Trending after `is_video`.
   - If the export is unavailable, the fallback group attaches Trending alone.
4. If the export file is missing, fall back to a minimal **HEC Post Controls**
   group containing only Trending (last resort).

About and Contact intentionally reuse field names such as `address`,
`phone_number`, and `fax_number`. Their locations therefore must stay mutually
exclusive: **About → `template-1.php`**, **Contact → `template-3.php`**. Never
scope either group to all pages.

The ACF **Custom Fields** schema-definition menu is hidden by default because
Git owns these definitions. This does not hide the content panels from normal
post/page editors. For a deliberate full export, temporarily define
`HECTV_ALLOW_ACF_SCHEMA_ADMIN` as `true` in `wp-config.php`, export all changed
groups, commit the export and resolver changes together, then remove the flag.

Do **not** enable ACF Local JSON `save_json` from this package — it would capture
unrelated admin-managed groups into the repo.

### Post Details (per post)

| Field label | Meta key | Type | Notes |
|-------------|----------|------|--------|
| Is Video | `is_video` | boolean | Legacy (export) |
| **Trending** | `is_trending` | boolean | **Git-owned** — Trending Now rail |
| Show Podcasts | `show_podcasts` | boolean | Legacy |
| Hide Page Thumbnail | `hide_page_thumbnail` | boolean | Legacy |
| Post Header | `post_header` | image | Shared thumbnail (cards/search/Trending). Do not crop for main page only. |
| **Post page hero** | `post_hero` | image | **Main post page only** — safe to crop/edit without affecting cards. |
| Video Thumbnail | `video_image` | image | Shared video thumbnail (cards/search). Prefer post_hero for main-page crops. |
| Broadcast File Location | `broadcast_location` | text | Legacy |
| Internal ID | `internal_id` | text | Legacy |
| YouTube ID | `youtube_id` | text | Legacy |
| Vimeo ID | `vimeo_id` | text | Legacy |
| Embed URL | `embed_url` | text | Legacy |
| Duration | `duration` | text | Legacy |
| Poll For Updates | `poll_for_updates` | number | Legacy |
| Post Events | `post_events` | repeater | Legacy |
| Related Posts | `related_posts` | repeater | Legacy |

The **YouTube ID** and **Vimeo ID** inputs stay visible even before **Is Video**
is enabled. ACF 5.6.9 does not reliably reveal conditionally hidden fields from
the git-owned overlay in the block editor. Use these inputs only for video
posts, and enter the video ID rather than the full URL.


### Editorial process — main post image vs thumbnails

Production media URLs supplied by WP Offload Media are preserved as-is. Some
uploads include a collision-avoidance directory in the S3 object key that is
not present in WordPress's `_wp_attached_file` value; reconstructing the URL
from that core value would point the article page at a nonexistent object.

WordPress media edits (crop/rotate) apply to the **attachment file globally**. If you
crop the image selected under **Post Header** or **Video Thumbnail**, search results
and cards that reuse that file will change too.

1. Keep **Post Header** / **Video Thumbnail** as the shared list/card image (full frame).
2. For a cropped or different image on the **main article page only**, set **Post page hero**.
3. Prefer uploading a second file or Media Library **Save as copy** before cropping, then
   assign that copy to **Post page hero**.
4. If **Post page hero** is empty, the frontend falls back to Post Header / Video Thumbnail.

All other export groups (About, Contact, Audio Tracks, Schedule Details, Event
Details, Site Options, …) follow the same complete local-overlay rule.

### Editorial process — Trending videos

1. **Settings → HEC Site Settings** → set **Max videos to show** (config size for the rail).
2. Edit a post → **Post Details** → enable **Trending** (and **Is Video** when it is a video).
3. Save. `trendingPosts` returns up to the config count:
   - first: posts with **Trending** checked (newest first)
   - then: most recent published posts to fill any shortfall

---

## 2. Site settings (whole site — not a post field)

**Settings → HEC Site Settings**

| Setting | Option key | GraphQL |
|---------|------------|---------|
| Max videos to show | `hectv_trending_max_videos` | `trendingSettings { maxVideos }` |
| For Educators logo image | `hectv_educators_logo_id` | `forEducators { image { sourceUrl } }` |
| For Educators link / source | `hectv_educators_url` | `forEducators { url }` |
| For Educators label | `hectv_educators_label` | `forEducators { label }` |

### Editorial process — For Educators logo

1. Open **Settings → HEC Site Settings**.
2. **Select image** from the media library (upload a new asset if needed).
3. Set **Link / source URL** (e.g. `/spotlight` or an absolute URL).
4. Optionally change the label (default `For Educators`).
5. **Save site settings**.

The frontend should query:

```graphql
query SiteChrome {
  forEducators {
    label
    url
    image {
      sourceUrl
      mediaItemUrl
    }
  }
  trendingSettings {
    maxVideos
  }
  trendingPosts {
    title
    link
    isTrending
    postDetails {
      isTrending
      isVideo
      postHeader {
        sourceUrl
      }
      videoImage {
        sourceUrl
      }
    }
  }
  topbarCtas {
    label
    url
    style
  }
}
```

---

## 3. Support & Subscribe (menu)

**Appearance → Menus → Header Actions (Support / Subscribe)**

| Item | Suggested URL |
|------|----------------|
| Subscribe | `/newsletter` |
| Support | `/support` (or donate URL) |

- Menu location slug: `header_actions`
- Menu name/slug: `Header Actions` / `header-actions`
- Optional CSS class on a menu item: `primary` | `secondary` | `tertiary` (maps to CTA style)

GraphQL:

- `topbarCtas { label url style }` — resolved from staging's saved CTA option when it
  contains rows; an empty or never-saved option falls back to this menu.
- Or standard `menus(where: { slug: "header-actions" }) { … }`

### Editorial process

1. **Appearance → Menus**.
2. Select **Header Actions** (or create it and assign location **Header Actions (Support / Subscribe)**).
3. Add custom links: **Subscribe**, **Support**.
4. Save. No deploy required.

Local/staging seed creates Subscribe + Support once when the location is empty
(`HECTV_ENVIRONMENT=local|staging` or `HECTV_CMS_SEED_MENUS=true`). Defining
`HECTV_CMS_SEED_MENUS` as `false` does not enable seeding.

---

## 4. GraphQL summary

Owned by `graphql.php` (does not depend on wp-graphql-acf):

| Field | Type |
|-------|------|
| `Post.isTrending` | Boolean |
| `Page.about` | Complete `HecAbout` contract from the About ACF export |
| `Page.contact` | Complete `HecContact` contract from the Contact ACF export |
| `Post.postDetails` | `HecPostDetails` |
| `Post.postDetails.isVideo` | Boolean |
| `Post.postDetails.isTrending` | Boolean |
| `Post.postDetails.youtubeId` | String |
| `Post.postDetails.vimeoId` | String |
| `Post.postDetails.embedUrl` | String |
| `Post.postDetails.postHeader` | MediaItem |
| `Post.postDetails.postHero` | MediaItem |
| `Post.postDetails.videoImage` | MediaItem |
| `Post.postDetails.showPodcasts` | Boolean |
| `Post.postDetails.hidePageThumbnail` | Boolean |
| `Post.postDetails.pollForUpdates` | Float (seconds; ACF number — not Boolean) |
| `Post.postDetails.broadcastLocation` | String |
| `Post.postDetails.internalId` | String |
| `Post.postDetails.duration` | String |
| `Post.postDetails.relatedPosts` | `[HecRelatedPostRow]` |
| `Post.postDetails.postEvents` | `[HecRelatedEventRow]` |
| `RootQuery.trendingSettings.maxVideos` | Int |
| `RootQuery.forEducators` | `{ label, url, image }` |
| `RootQuery.trendingPosts(first: Int)` | `[Post]` — size from `trendingSettings.maxVideos` (or `first`); **is_trending first** (newest), then **backfill most recent** until full |
| `RootQuery.topbarCtas` | `[{ label, url, style }]` |

Example:

```graphql
query PostWithDetails($slug: String!) {
  postBy(slug: $slug) {
    title
    isTrending
    postDetails {
      isVideo
      isTrending
      youtubeId
      vimeoId
      embedUrl
      showPodcasts
      hidePageThumbnail
      pollForUpdates
      broadcastLocation
      internalId
      duration
      # Main article page only. Frontend preference: postHero, then postHeader / videoImage.
      postHero { sourceUrl }
      postHeader { sourceUrl }
      videoImage { sourceUrl }
      relatedPosts { relatedPost { title link } }
      postEvents { relatedEvent { title } }
    }
  }
}
```

---

## 5. Local staging harness

```bash
cd staging-harness
docker compose up -d
./seed.sh
```

Compose mounts the git-canonical `hectv-cms-fields` package into the container
mu-plugins directory. Seed sets trending meta, max videos, educators URL, and
Support/Subscribe menu items.

---

## 6. Changing field definitions

1. Temporarily enable the schema UI with
   `HECTV_ALLOW_ACF_SCHEMA_ADMIN=true`, export the complete group from the
   canonical WordPress ACF admin, and replace `acf-field-groups.json` (never
   recreate partial PHP fields by hand; keep production group and field keys).
2. Keep git-owned fields (currently `is_trending`) injected in
   `register-acf.php` so they survive re-exports that omit them.
3. Extend the canonical resolvers in `graphql.php` if the frontend contract
   changes. Do not duplicate ACF resolvers in `hectv-graphql-compat.php`.
4. Ship branch → PR → merge (same as other hectv-wp changes).
5. Deploy/restart WordPress so the MU-plugin reloads.
6. Remove the break-glass flag so the Custom Fields definition menu is hidden
   again.

### Verify

```bash
php tests/hectv-cms-fields.php
php tests/hectv-cms-fields-runtime.php
```
