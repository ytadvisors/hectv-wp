# HEC CMS fields (git-canonical)

Source of truth for custom fields and site-wide content settings used by the
headless HECMedia app. **Do not leave field definitions only in the production
WordPress database.**

| Surface | Location in repo |
|---------|------------------|
| MU-plugin loader | `wp-content/mu-plugins/hectv-cms-fields.php` |
| ACF Post Details (PHP) | `wp-content/mu-plugins/hectv-cms-fields/register-acf.php` |
| ACF Local JSON | `wp-content/mu-plugins/hectv-cms-fields/acf-json/` |
| Site settings admin | `…/site-settings.php` → **Settings → HEC Site Settings** |
| Header Actions menu | `…/menus.php` → **Appearance → Menus** |
| GraphQL | `…/graphql.php` |
| Local harness seed | `staging-harness/seed.sh` |

---

## 1. Post Details (per post)

ACF field group **Post Details** on `post`. Meta keys match existing production
consumers (`get_field( 'is_video' )`, etc.).

| Field label | Meta key | Type | Notes |
|-------------|----------|------|--------|
| Is Video | `is_video` | boolean | Existing |
| **Trending** | `is_trending` | boolean | **New** — include in Trending Now |
| YouTube ID | `youtube_id` | text | |
| Vimeo ID | `vimeo_id` | text | |
| Embed URL | `embed_url` | url | |
| Post Header | `post_header` | image | |
| Video Image | `video_image` | image | |
| Show Podcasts | `show_podcasts` | boolean | |
| Hide Page Thumbnail | `hide_page_thumbnail` | boolean | |
| Poll For Updates | `poll_for_updates` | boolean | |

### Editorial process — Trending videos

1. Edit a post → **Post Details**.
2. Enable **Is Video** (if it is a video) and **Trending**.
3. Save. The post becomes eligible for `trendingPosts` / `postDetails.isTrending`.

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

- `topbarCtas { label url style }` — resolved from this menu (staging option list wins if set)
- Or standard `menus(where: { slug: "header-actions" }) { … }`

### Editorial process

1. **Appearance → Menus**.
2. Select **Header Actions** (or create it and assign location **Header Actions (Support / Subscribe)**).
3. Add custom links: **Subscribe**, **Support**.
4. Save. No deploy required.

Local/staging seed creates Subscribe + Support when the location is empty
(`HECTV_ENVIRONMENT=local|staging` or `HECTV_CMS_SEED_MENUS`).

---

## 4. GraphQL summary

| Field | Type |
|-------|------|
| `Post.isTrending` | Boolean |
| `Post.postDetails.isTrending` | Boolean (when HecPostDetails present) |
| `RootQuery.trendingSettings.maxVideos` | Int |
| `RootQuery.forEducators` | `{ label, url, image }` |
| `RootQuery.trendingPosts(first: Int)` | `[Post]` — meta `is_trending`, capped by max |
| `RootQuery.topbarCtas` | `[{ label, url, style }]` |

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

1. Edit `register-acf.php` **and** `acf-json/group_hectv_post_details.json`.
2. Extend `graphql.php` if the frontend contract changes.
3. Ship branch → PR → merge (same as other hectv-wp changes).
4. Deploy/restart WordPress so the MU-plugin reloads.

Never rely on hand-editing ACF field groups only in production admin without
exporting back to this package.
