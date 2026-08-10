<?php
/**
 * Structural + pure-logic regression for HECTV_Admin::show_excerpt login fatal.
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
assert_true( strpos( $src, "add_action( 'wp_login'" ) !== false || strpos( $src, 'add_action( "wp_login"' ) !== false, 'hooks wp_login' );
assert_true( strpos( $src, 'metaboxhidden_post' ) !== false, 'reads metaboxhidden_post meta' );
assert_true(
	(bool) preg_match( '/is_array\s*\(\s*\$unchecked\s*\)/', $src ),
	'guards non-array metaboxhidden_post before array_search'
);
assert_true(
	(bool) preg_match( '/array_search\s*\(\s*[\'"]postexcerpt[\'"]\s*,\s*\$unchecked/', $src ),
	'searches for postexcerpt in hidden list'
);
// Guard must appear before array_search within show_excerpt().
$start = strpos( $src, 'function show_excerpt' );
$end   = strpos( $src, 'function add_schedule', $start !== false ? $start : 0 );
assert_true( $start !== false && $end !== false && $end > $start, 'located show_excerpt block' );
if ( $start !== false && $end !== false ) {
	$fn = substr( $src, $start, $end - $start );
	$search_pos = strpos( $fn, 'array_search' );
	$guard_pos  = strpos( $fn, 'is_array' );
	assert_true( $search_pos !== false && $guard_pos !== false && $guard_pos < $search_pos, 'is_array guard appears before array_search' );
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
	$key = array_search( 'postexcerpt', $unchecked, true );
	if ( false !== $key ) {
		array_splice( $unchecked, $key, 1 );
		return array( 'ok' => true, 'updated' => $unchecked );
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

$r = hectv_show_excerpt_apply( array( 'postexcerpt', 'slugdiv' ) );
assert_true( is_array( $r['updated'] ) && $r['updated'] === array( 'slugdiv' ), 'removes postexcerpt from hidden list' );

$r = hectv_show_excerpt_apply( array( 'slugdiv' ) );
assert_true( $r['updated'] === null, 'no update when postexcerpt not hidden' );

exit( $fail > 0 ? 1 : 0 );
