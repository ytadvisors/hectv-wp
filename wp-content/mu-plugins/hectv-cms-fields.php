<?php
/**
 * Plugin Name: HEC CMS Fields (git-canonical)
 * Description: Versioned ACF field groups (production export + Trending), site-wide
 *              Trending / For Educators settings, GraphQL exposure, and header
 *              action menu location (Support / Subscribe). Source of truth lives
 *              in this repo (see hectv-cms-fields/acf-field-groups.json).
 * Version: 1.3.2
 * Author: YT Advisors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HECTV_CMS_FIELDS_VERSION', '1.3.2' );
define( 'HECTV_CMS_FIELDS_DIR', __DIR__ . '/hectv-cms-fields' );

// Option keys (site-wide — not post meta).
define( 'HECTV_OPT_TRENDING_MAX_VIDEOS', 'hectv_trending_max_videos' );
define( 'HECTV_OPT_TRENDING_TITLE', 'hectv_trending_title' );
define( 'HECTV_OPT_SPOTLIGHT_TITLE', 'hectv_spotlight_title' );
define( 'HECTV_OPT_MOBILE_DISPLAY', 'hectv_mobile_display' );
define( 'HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED', 'hectv_newsletter_captcha_enabled' );
define( 'HECTV_OPT_EDUCATORS_LOGO_ID', 'hectv_educators_logo_id' );
define( 'HECTV_OPT_EDUCATORS_URL', 'hectv_educators_url' );
define( 'HECTV_OPT_EDUCATORS_LABEL', 'hectv_educators_label' );

// Post meta keys (Post Details).
define( 'HECTV_META_IS_TRENDING', 'is_trending' );
define( 'HECTV_META_TRENDING_ORDER', 'trending_order' );
define( 'HECTV_META_IS_VIDEO', 'is_video' );
define( 'HECTV_META_POST_HEADER', 'post_header' );
define( 'HECTV_META_POST_HERO', 'post_hero' );
define( 'HECTV_META_VIDEO_IMAGE', 'video_image' );
define( 'HECTV_META_YOUTUBE_ID', 'youtube_id' );
define( 'HECTV_META_VIMEO_ID', 'vimeo_id' );
define( 'HECTV_META_EMBED_URL', 'embed_url' );
define( 'HECTV_META_SHOW_PODCASTS', 'show_podcasts' );
define( 'HECTV_META_HIDE_PAGE_THUMBNAIL', 'hide_page_thumbnail' );
define( 'HECTV_META_POLL_FOR_UPDATES', 'poll_for_updates' );

// Nav menu location for Support / Subscribe (and other top-bar CTAs).
define( 'HECTV_MENU_HEADER_ACTIONS', 'header_actions' );

require_once HECTV_CMS_FIELDS_DIR . '/register-acf.php';
require_once HECTV_CMS_FIELDS_DIR . '/editor.php';
require_once HECTV_CMS_FIELDS_DIR . '/site-settings.php';
require_once HECTV_CMS_FIELDS_DIR . '/menus.php';
require_once HECTV_CMS_FIELDS_DIR . '/graphql.php';
