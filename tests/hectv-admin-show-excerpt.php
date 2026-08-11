<?php
/**
 * Structural + pure-logic regressions for required post metabox visibility.
 * Run: php tests/hectv-admin-show-excerpt.php
 *
 * Production stack (2026-08-10): array_search('postexcerpt', '') on empty
 * metaboxhidden_post user meta during wp_login for gkowarski.
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

$admin = $root . '/wp-content/mu-plugins/hectv/hectv_admin.php';
assert_true( file_exists( $admin ), 'hectv_admin.php exists' );

$src = file_get_contents( $admin );
assert_true( strpos( $src, 'function show_excerpt' ) !== false, 'show_excerpt defined' );
assert_true( strpos( $src, 'function show_required_post_metaboxes' ) !== false, 'required metabox filter defined' );
assert_true( strpos( $src, "add_action( 'wp_login'" ) !== false || strpos( $src, 'add_action( "wp_login"' ) !== false, 'hooks wp_login' );
assert_true( strpos( $src, "add_filter( 'hidden_meta_boxes'" ) !== false, 'filters hidden metaboxes on every post edit request' );
assert_true( strpos( $src, 'metaboxhidden_post' ) !== false, 'reads metaboxhidden_post meta' );
assert_true(
	(bool) preg_match( '/is_array\s*\(\s*\$unchecked\s*\)/', $src ),
	'guards non-array metaboxhidden_post'
);
assert_true(
	strpos( $src, "'acf-' . \$post_details_group" ) !== false,
	'uses the real ACF Post Details metabox ID'
);
assert_true( strpos( $src, "'postexcerpt'" ) !== false, 'keeps Excerpt in the required metabox set' );

// Guard must appear before the required-metabox filter within show_excerpt().
$start = strpos( $src, 'function show_excerpt' );
$end   = strpos( $src, 'function add_schedule', $start !== false ? $start : 0 );
assert_true( $start !== false && $end !== false && $end > $start, 'located show_excerpt block' );
if ( $start !== false && $end !== false ) {
	$fn = substr( $src, $start, $end - $start );
	$filter_pos = strpos( $fn, 'show_required_post_metaboxes' );
	$guard_pos  = strpos( $fn, 'is_array' );
	assert_true( $filter_pos !== false && $guard_pos !== false && $guard_pos < $filter_pos, 'is_array guard appears before filtering' );
}

/**
 * Pure replica of the guarded logic for behavioral cases without WP bootstrap.
 *
 * @param mixed $unchecked get_user_meta shape.
 * @return array{ok:bool, updated:?array}
 */
function hectv_show_excerpt_apply( $unchecked ) {
	if ( ! is_array( $unchecked ) ) {
		return array( 'ok' => true, 'updated' => null );
	}
	$required = array( 'postexcerpt', 'acf-group_5a9bf131f2b91' );
	$visible  = array_values( array_diff( $unchecked, $required ) );
	if ( $visible !== $unchecked ) {
		return array( 'ok' => true, 'updated' => $visible );
	}
	return array( 'ok' => true, 'updated' => null );
}

// Empty string — the production fatal case — must not throw.
try {
	$r = hectv_show_excerpt_apply( '' );
	assert_true( $r['ok'] === true && $r['updated'] === null, 'empty string meta is a no-op' );
} catch ( TypeError $e ) {
	assert_true( false, 'empty string must not TypeError: ' . $e->getMessage() );
} catch ( Exception $e ) {
	assert_true( false, 'empty string must not throw: ' . $e->getMessage() );
}

$r = hectv_show_excerpt_apply( null );
assert_true( $r['ok'] === true && $r['updated'] === null, 'null meta is a no-op' );

$r = hectv_show_excerpt_apply( array( 'postexcerpt', 'acf-group_5a9bf131f2b91', 'slugdiv' ) );
assert_true( is_array( $r['updated'] ) && $r['updated'] === array( 'slugdiv' ), 'unhides Excerpt and Post Details while preserving other preferences' );

$r = hectv_show_excerpt_apply( array( 'acf-group_5a9bf131f2b91' ) );
assert_true( $r['updated'] === array(), 'unhides Post Details when it is the only hidden metabox' );

$r = hectv_show_excerpt_apply( array( 'slugdiv' ) );
assert_true( $r['updated'] === null, 'no update when required metaboxes are already visible' );

exit( $fail > 0 ? 1 : 0 );
