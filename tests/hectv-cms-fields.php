<?php
/**
 * Lightweight structural tests for hectv-cms-fields (no full WP bootstrap).
 * Run: php tests/hectv-cms-fields.php
 */

$root = dirname( __DIR__ );
$fail = 0;

function assert_true( $cond, $msg ) {
	global $fail;
	if ( $cond ) {
		echo "OK  $msg\n";
	} else {
		echo "FAIL $msg\n";
		$fail++;
	}
}

$loader = $root . '/wp-content/mu-plugins/hectv-cms-fields.php';
assert_true( file_exists( $loader ), 'loader exists' );

$pkg = $root . '/wp-content/mu-plugins/hectv-cms-fields';
foreach ( array( 'register-acf.php', 'editor.php', 'site-settings.php', 'menus.php', 'graphql.php', 'acf-field-groups.json' ) as $f ) {
	assert_true( file_exists( "$pkg/$f" ), "package file $f" );
}

$export = json_decode( file_get_contents( $pkg . '/acf-field-groups.json' ), true );
assert_true( is_array( $export ) && count( $export ) >= 1, 'acf export is a non-empty list' );
$titles = array();
$post_details = null;
$about = null;
$contact = null;
foreach ( $export as $group ) {
	if ( ! empty( $group['title'] ) ) {
		$titles[] = $group['title'];
	}
	if ( isset( $group['title'] ) && $group['title'] === 'Post Details' ) {
		$post_details = $group;
	}
	if ( isset( $group['title'] ) && $group['title'] === 'About' ) {
		$about = $group;
	}
	if ( isset( $group['title'] ) && $group['title'] === 'Contact' ) {
		$contact = $group;
	}
}
assert_true( in_array( 'Post Details', $titles, true ), 'export includes Post Details' );
assert_true( in_array( 'About', $titles, true ), 'export includes About' );
assert_true( is_array( $post_details ), 'Post Details group present' );
assert_true( is_array( $about ), 'About group present' );
assert_true( is_array( $contact ), 'Contact group present' );
assert_true( isset( $post_details['key'] ) && $post_details['key'] === 'group_5a9bf131f2b91', 'Post Details keeps production key' );
assert_true( $about['location'][0][0]['param'] === 'page_template' && $about['location'][0][0]['value'] === 'template-1.php', 'About fields are scoped only to template-1.php' );
assert_true( $contact['location'][0][0]['param'] === 'page_template' && $contact['location'][0][0]['value'] === 'template-3.php', 'Contact fields are scoped only to template-3.php' );

$pd_names = array();
foreach ( (array) $post_details['fields'] as $field ) {
	if ( ! empty( $field['name'] ) ) {
		$pd_names[] = $field['name'];
	}
}
foreach ( array( 'is_video', 'youtube_id', 'vimeo_id', 'embed_url', 'post_header', 'post_hero', 'video_image' ) as $legacy ) {
	assert_true( in_array( $legacy, $pd_names, true ), "Post Details export has legacy field $legacy" );
}

$src = file_get_contents( $pkg . '/register-acf.php' );
assert_true( strpos( $src, 'is_trending' ) !== false || strpos( $src, 'HECTV_META_IS_TRENDING' ) !== false, 'PHP registers is_trending' );
assert_true( strpos( $src, 'trending_order' ) !== false || strpos( $src, 'HECTV_META_TRENDING_ORDER' ) !== false, 'PHP registers per-post trending_order' );
assert_true( strpos( $src, 'acf-field-groups.json' ) !== false, 'PHP loads acf-field-groups.json' );
assert_true( strpos( $src, 'acf/settings/show_admin' ) !== false, 'PHP hides the ACF schema-definition menu' );
assert_true( strpos( $src, 'HECTV_ALLOW_ACF_SCHEMA_ADMIN' ) !== false, 'PHP provides an explicit schema-admin break-glass flag' );
assert_true( strpos( $src, 'acf/settings/save_json' ) === false, 'does not hijack global ACF JSON saves' );
assert_true( strpos( $src, 'group_5a9bf131f2b91' ) !== false, 'references production Post Details key' );
assert_true( strpos( $src, 'Register every exported group as one complete same-key local group' ) !== false, 'registers complete same-key overlays for every exported group' );
assert_true( strpos( $src, 'Skip other groups when production' ) === false, 'does not leave database groups authoritative over git' );
assert_true( strpos( $src, 'acf_add_local_field_group( $local )' ) !== false, 'registers full local Post Details fields' );

$editor = file_get_contents( $pkg . '/editor.php' );
assert_true( strpos( $editor, 'HECTV_HOME_PAGE_ID' ) !== false, 'editor recovery identifies the production Home page' );
assert_true( strpos( $editor, 'use_block_editor_for_post' ) !== false, 'editor recovery uses the per-post WordPress filter' );
assert_true( strpos( $editor, 'use_block_editor_for_post_type' ) === false, 'editor recovery does not disable the block editor for every page' );
assert_true( strpos( $editor, "'wp-hooks'" ) !== false, 'editor recovery orders modern WordPress hooks before legacy ACF' );
assert_true( strpos( $editor, 'legacyHooks.storage' ) !== false, 'editor recovery preserves ACF 5.6.9 hook callbacks' );
assert_true( strpos( $editor, 'window.wp.hooks = coreHooks' ) !== false, 'editor recovery restores the modern WordPress hook registry' );

$gql = file_get_contents( $pkg . '/graphql.php' );
assert_true( strpos( $gql, 'trendingSettings' ) !== false, 'GraphQL trendingSettings' );
assert_true( strpos( $gql, 'forEducators' ) !== false, 'GraphQL forEducators' );
assert_true( strpos( $gql, 'trendingPosts' ) !== false, 'GraphQL trendingPosts' );
assert_true( strpos( $gql, 'isTrending' ) !== false, 'GraphQL isTrending' );
assert_true( strpos( $gql, 'trendingOrder' ) !== false, 'GraphQL trendingOrder' );
assert_true( strpos( $gql, 'newsletterSettings' ) !== false, 'GraphQL newsletterSettings' );
assert_true( strpos( $gql, 'topbarCtas' ) !== false, 'GraphQL topbarCtas' );
assert_true( strpos( $gql, 'HectvForEducatorsCard' ) !== false, 'GraphQL educator type is collision-free' );
assert_true( strpos( $gql, 'HecPostDetails' ) !== false, 'GraphQL HecPostDetails type' );
assert_true( strpos( $gql, 'postDetails' ) !== false, 'GraphQL postDetails field' );
assert_true( strpos( $gql, 'hectv_cms_resolve_about' ) !== false, 'GraphQL canonical About resolver' );
assert_true( strpos( $gql, 'hectv_cms_resolve_contact' ) !== false, 'GraphQL canonical Contact resolver' );
foreach ( array( 'partnerLogos', 'publicSchoolPartners', 'higherEducationPartners', 'boardOfDirectors', 'contactSubjects' ) as $field ) {
	assert_true( strpos( $gql, $field ) !== false, "GraphQL complete About/Contact contract includes $field" );
}
assert_true( strpos( $gql, 'youtubeId' ) !== false, 'GraphQL youtubeId' );
assert_true( strpos( $gql, 'vimeoId' ) !== false, 'GraphQL vimeoId' );
assert_true( strpos( $gql, 'embedUrl' ) !== false, 'GraphQL embedUrl' );
assert_true( strpos( $gql, 'isVideo' ) !== false, 'GraphQL isVideo' );
assert_true( strpos( $gql, 'videoImage' ) !== false, 'GraphQL videoImage' );
assert_true( strpos( $gql, 'postHeader' ) !== false, 'GraphQL postHeader' );
assert_true( strpos( $gql, 'postHero' ) !== false, 'GraphQL postHero' );
assert_true( strpos( $gql, "HECTV_META_POST_HERO" ) !== false || strpos( file_get_contents( dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-cms-fields.php' ), 'HECTV_META_POST_HERO' ) !== false, 'POST_HERO meta constant' );
assert_true( strpos( $gql, 'relatedPosts' ) !== false, 'GraphQL relatedPosts' );
assert_true( strpos( $gql, 'hectv_cms_resolve_post_details' ) !== false, 'GraphQL postDetails resolver' );
assert_true( strpos( $gql, "'pollForUpdates'    => 'Float'" ) !== false || strpos( $gql, "'type'        => 'Float'" ) !== false, 'pollForUpdates GraphQL type is Float' );
assert_true( strpos( $gql, 'hectv_cms_gql_float' ) !== false, 'uses numeric float helper for poll interval' );
assert_true( strpos( $gql, "pollForUpdates'     => hectv_cms_gql_bool" ) === false, 'pollForUpdates is not bool-coerced' );
assert_true( strpos( $gql, 'hectv_cms_query_trending_posts' ) !== false, 'GraphQL trending query helper' );
assert_true( strpos( $gql, 'post__not_in' ) !== false, 'Trending backfill excludes selected IDs' );
assert_true( strpos( $gql, 'backfill' ) !== false || strpos( $gql, 'Backfill' ) !== false, 'Trending documents backfill behavior' );

$menus = file_get_contents( $pkg . '/menus.php' );
assert_true( strpos( $menus, 'header_actions' ) !== false, 'menu location header_actions' );
assert_true( strpos( $menus, 'Subscribe' ) !== false, 'default Subscribe item' );
assert_true( strpos( $menus, 'Support' ) !== false, 'default Support item' );
assert_true( strpos( $menus, "defined( 'HECTV_CMS_SEED_MENUS' ) && HECTV_CMS_SEED_MENUS" ) !== false, 'menu seed requires truthy constant' );

$settings = file_get_contents( $pkg . '/site-settings.php' );
assert_true( strpos( $settings, 'hectv_trending_max_videos' ) !== false, 'max videos option' );
assert_true( strpos( $settings, 'hectv_trending_title' ) !== false, 'trending heading option' );
assert_true( strpos( $settings, 'hectv_spotlight_title' ) !== false, 'spotlight heading option' );
assert_true( strpos( $settings, 'hectv_mobile_display' ) !== false, 'mobile display option' );
assert_true( strpos( $settings, 'HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED' ) !== false, 'newsletter CAPTCHA option' );
foreach ( array( 'Trending heading', 'Spotlight heading', 'Mobile display', 'Content, then menu', 'Menu, then content', 'Require CAPTCHA for newsletter signup' ) as $label ) {
	assert_true( strpos( $settings, $label ) !== false, "Site Settings UI renders $label" );
}
assert_true( strpos( $settings, 'hectv_educators_logo_id' ) !== false, 'educators logo option' );
assert_true( strpos( $settings, 'hectv_educators_url' ) !== false, 'educators url option' );

$compat = file_get_contents( $root . '/staging-harness/mu-plugins/hectv-graphql-compat.php' );
assert_true( strpos( $compat, 'isTrending' ) !== false, 'staging compat exposes isTrending' );
assert_true( strpos( $compat, 'is_trending' ) !== false, 'staging compat reads is_trending meta' );
assert_true( strpos( $compat, "'HecAbout'" ) === false, 'staging compat does not duplicate canonical About GraphQL ownership' );
assert_true( strpos( $compat, "'HecContact'" ) === false, 'staging compat does not duplicate canonical Contact GraphQL ownership' );

$seed = file_get_contents( $root . '/staging-harness/seed.sh' );
assert_true( strpos( $seed, 'is_trending' ) !== false, 'seed sets is_trending' );
assert_true( strpos( $seed, 'Header Actions' ) !== false, 'seed creates Header Actions menu' );
assert_true( strpos( $seed, 'hectv_trending_max_videos' ) !== false, 'seed sets max videos' );

$compose = file_get_contents( $root . '/staging-harness/docker-compose.yml' );
assert_true( strpos( $compose, 'hectv-cms-fields' ) !== false, 'compose mounts cms fields package' );

$compat_hero = file_get_contents( $root . '/staging-harness/mu-plugins/hectv-graphql-compat.php' );
assert_true( strpos( $compat_hero, "'postHero'" ) !== false, 'staging compat registers postHero field' );
assert_true( strpos( $compat_hero, "post_hero" ) !== false, 'staging compat reads post_hero meta' );
assert_true( strpos( $compat_hero, 'Post( $post_hero )' ) !== false, 'staging compat wraps post_hero as WPGraphQL Model Post' );
assert_true( strpos( $compat_hero, "'postHero'          => \$post_hero" ) !== false, 'staging compat returns postHero in postDetails payload' );

echo $fail === 0 ? "\nAll structural checks passed.\n" : "\n$fail check(s) failed.\n";
exit( $fail === 0 ? 0 : 1 );
