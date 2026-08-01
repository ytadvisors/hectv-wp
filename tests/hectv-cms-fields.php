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
foreach ( array( 'register-acf.php', 'site-settings.php', 'menus.php', 'graphql.php', 'acf-field-groups.json' ) as $f ) {
	assert_true( file_exists( "$pkg/$f" ), "package file $f" );
}

$export = json_decode( file_get_contents( $pkg . '/acf-field-groups.json' ), true );
assert_true( is_array( $export ) && count( $export ) >= 1, 'acf export is a non-empty list' );
$titles = array();
$post_details = null;
foreach ( $export as $group ) {
	if ( ! empty( $group['title'] ) ) {
		$titles[] = $group['title'];
	}
	if ( isset( $group['title'] ) && $group['title'] === 'Post Details' ) {
		$post_details = $group;
	}
}
assert_true( in_array( 'Post Details', $titles, true ), 'export includes Post Details' );
assert_true( in_array( 'About', $titles, true ), 'export includes About' );
assert_true( is_array( $post_details ), 'Post Details group present' );
assert_true( isset( $post_details['key'] ) && $post_details['key'] === 'group_5a9bf131f2b91', 'Post Details keeps production key' );

$pd_names = array();
foreach ( (array) $post_details['fields'] as $field ) {
	if ( ! empty( $field['name'] ) ) {
		$pd_names[] = $field['name'];
	}
}
foreach ( array( 'is_video', 'youtube_id', 'vimeo_id', 'embed_url', 'post_header', 'video_image' ) as $legacy ) {
	assert_true( in_array( $legacy, $pd_names, true ), "Post Details export has legacy field $legacy" );
}

$src = file_get_contents( $pkg . '/register-acf.php' );
assert_true( strpos( $src, 'is_trending' ) !== false || strpos( $src, 'HECTV_META_IS_TRENDING' ) !== false, 'PHP registers is_trending' );
assert_true( strpos( $src, 'acf-field-groups.json' ) !== false, 'PHP loads acf-field-groups.json' );
assert_true( strpos( $src, 'acf/settings/save_json' ) === false, 'does not hijack global ACF JSON saves' );
assert_true( strpos( $src, 'group_5a9bf131f2b91' ) !== false, 'references production Post Details key' );
// Must not force-register a second Post Details title when one already exists.
assert_true( strpos( $src, 'already owns this group' ) !== false || strpos( $src, 'Skip when production' ) !== false, 'skips duplicate group registration' );

$gql = file_get_contents( $pkg . '/graphql.php' );
assert_true( strpos( $gql, 'trendingSettings' ) !== false, 'GraphQL trendingSettings' );
assert_true( strpos( $gql, 'forEducators' ) !== false, 'GraphQL forEducators' );
assert_true( strpos( $gql, 'trendingPosts' ) !== false, 'GraphQL trendingPosts' );
assert_true( strpos( $gql, 'isTrending' ) !== false, 'GraphQL isTrending' );
assert_true( strpos( $gql, 'topbarCtas' ) !== false, 'GraphQL topbarCtas' );
assert_true( strpos( $gql, 'HectvForEducatorsCard' ) !== false, 'GraphQL educator type is collision-free' );
assert_true( strpos( $gql, 'HecPostDetails' ) !== false, 'GraphQL HecPostDetails type' );
assert_true( strpos( $gql, 'postDetails' ) !== false, 'GraphQL postDetails field' );
assert_true( strpos( $gql, 'youtubeId' ) !== false, 'GraphQL youtubeId' );
assert_true( strpos( $gql, 'vimeoId' ) !== false, 'GraphQL vimeoId' );
assert_true( strpos( $gql, 'embedUrl' ) !== false, 'GraphQL embedUrl' );
assert_true( strpos( $gql, 'isVideo' ) !== false, 'GraphQL isVideo' );
assert_true( strpos( $gql, 'videoImage' ) !== false, 'GraphQL videoImage' );
assert_true( strpos( $gql, 'postHeader' ) !== false, 'GraphQL postHeader' );
assert_true( strpos( $gql, 'relatedPosts' ) !== false, 'GraphQL relatedPosts' );
assert_true( strpos( $gql, 'hectv_cms_resolve_post_details' ) !== false, 'GraphQL postDetails resolver' );
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
assert_true( strpos( $settings, 'hectv_educators_logo_id' ) !== false, 'educators logo option' );
assert_true( strpos( $settings, 'hectv_educators_url' ) !== false, 'educators url option' );

$compat = file_get_contents( $root . '/staging-harness/mu-plugins/hectv-graphql-compat.php' );
assert_true( strpos( $compat, 'isTrending' ) !== false, 'staging compat exposes isTrending' );
assert_true( strpos( $compat, 'is_trending' ) !== false, 'staging compat reads is_trending meta' );

$seed = file_get_contents( $root . '/staging-harness/seed.sh' );
assert_true( strpos( $seed, 'is_trending' ) !== false, 'seed sets is_trending' );
assert_true( strpos( $seed, 'Header Actions' ) !== false, 'seed creates Header Actions menu' );
assert_true( strpos( $seed, 'hectv_trending_max_videos' ) !== false, 'seed sets max videos' );

$compose = file_get_contents( $root . '/staging-harness/docker-compose.yml' );
assert_true( strpos( $compose, 'hectv-cms-fields' ) !== false, 'compose mounts cms fields package' );

echo $fail === 0 ? "\nAll structural checks passed.\n" : "\n$fail check(s) failed.\n";
exit( $fail === 0 ? 0 : 1 );
