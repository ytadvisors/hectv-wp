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

1. **Load the export** and register any group that is **not** already present
   in ACF (matched by **key or title**).
2. **Never re-register** a group production already owns — that duplicates admin
   panels.
3. **Always ensure** the git-owned **Trending** field (`is_trending`, key
   `field_hectv_is_trending`) is available on Post Details:
   - Production already has Post Details → **attach** Trending via
     `acf_add_local_field` (previous support path).
   - Clean install → register full Post Details from the export with Trending
     **nested** after `is_video`.
4. If the export file is missing, fall back to a minimal **HEC Post Controls**
   group containing only Trending (last resort).

Do **not** enable ACF Local JSON `save_json` from this package — it would capture
unrelated admin-managed groups into the repo.

### Post Details (per post)

| Field label | Meta key | Type | Notes |
|-------------|----------|------|--------|
| Is Video | `is_video` | boolean | Legacy (export) |
| **Trending** | `is_trending` | boolean | **Git-owned** — Trending Now rail |
| Show Podcasts | `show_podcasts` | boolean | Legacy |
| Hide Page Thumbnail | `hide_page_thumbnail` | boolean | Legacy |
| Post Header | `post_header` | image | Legacy |
| Video Thumbnail | `video_image` | image | Legacy |
| Broadcast File Location | `broadcast_location` | text | Legacy |
| Internal ID | `internal_id` | text | Legacy |
| YouTube ID | `youtube_id` | text | Legacy |
| Vimeo ID | `vimeo_id` | text | Legacy |
| Embed URL | `embed_url` | text | Legacy |
| Duration | `duration` | text | Legacy |
| Poll For Updates | `poll_for_updates` | number | Legacy |
| Post Events | `post_events` | repeater | Legacy |
| Related Posts | `related_posts` | repeater | Legacy |

Other export groups (About, Contact, Audio Tracks, Schedule Details, Event
Details, Site Options, …) follow the same missing-only registration rule.

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
| `Post.postDetails` | `HecPostDetails` |
| `Post.postDetails.isVideo` | Boolean |
| `Post.postDetails.isTrending` | Boolean |
| `Post.postDetails.youtubeId` | String |
| `Post.postDetails.vimeoId` | String |
| `Post.postDetails.embedUrl` | String |
| `Post.postDetails.postHeader` | MediaItem |
| `Post.postDetails.videoImage` | MediaItem |
| `Post.postDetails.showPodcasts` | Boolean |
| `Post.postDetails.hidePageThumbnail` | Boolean |
| `Post.postDetails.pollForUpdates` | Boolean |
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

1. Prefer re-export from production ACF admin → replace
   `acf-field-groups.json` (keeps production keys).
2. Keep git-owned fields (currently `is_trending`) injected in
   `register-acf.php` so they survive re-exports that omit them.
3. Extend `graphql.php` if the frontend contract changes.
4. Ship branch → PR → merge (same as other hectv-wp changes).
5. Deploy/restart WordPress so the MU-plugin reloads.

### Verify

```bash
php tests/hectv-cms-fields.php
php tests/hectv-cms-fields-runtime.php
```
