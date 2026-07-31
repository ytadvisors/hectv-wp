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
foreach ( array( 'register-acf.php', 'site-settings.php', 'menus.php', 'graphql.php' ) as $f ) {
	assert_true( file_exists( "$pkg/$f" ), "package file $f" );
}

$json = $pkg . '/acf-json/group_hectv_post_details.json';
assert_true( file_exists( $json ), 'acf-json group present' );
$data = json_decode( file_get_contents( $json ), true );
assert_true( is_array( $data ) && ( $data['key'] ?? '' ) === 'group_hectv_post_details', 'acf group key' );

$names = array();
foreach ( $data['fields'] as $field ) {
	$names[] = $field['name'];
}
assert_true( in_array( 'is_trending', $names, true ), 'is_trending in ACF JSON' );
assert_true( in_array( 'is_video', $names, true ), 'is_video in ACF JSON' );

$src = file_get_contents( $pkg . '/register-acf.php' );
assert_true( strpos( $src, "name'          => 'is_trending'" ) !== false || strpos( $src, "'is_trending'" ) !== false, 'PHP registers is_trending' );

$gql = file_get_contents( $pkg . '/graphql.php' );
assert_true( strpos( $gql, 'trendingSettings' ) !== false, 'GraphQL trendingSettings' );
assert_true( strpos( $gql, 'forEducators' ) !== false, 'GraphQL forEducators' );
assert_true( strpos( $gql, 'trendingPosts' ) !== false, 'GraphQL trendingPosts' );
assert_true( strpos( $gql, 'isTrending' ) !== false, 'GraphQL isTrending' );
assert_true( strpos( $gql, 'topbarCtas' ) !== false, 'GraphQL topbarCtas' );

$menus = file_get_contents( $pkg . '/menus.php' );
assert_true( strpos( $menus, 'header_actions' ) !== false, 'menu location header_actions' );
assert_true( strpos( $menus, 'Subscribe' ) !== false, 'default Subscribe item' );
assert_true( strpos( $menus, 'Support' ) !== false, 'default Support item' );

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
