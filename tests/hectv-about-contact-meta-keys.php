<?php
/**
 * Structural + behavioral contracts for About/Contact meta mapping.
 *
 * 1) Structural: Page about/contact resolvers use live ACF field names and a
 *    scoped ACF helper (not a shared get_field override in hectv_gql_meta).
 * 2) Behavioral: with ACF get_field present, raw media meta stays raw so
 *    hectv_gql_media() keeps the real attachment ID; About/Contact helpers
 *    still accept ACF repeater arrays and legacy JSON strings.
 *
 * Run: php tests/hectv-about-contact-meta-keys.php
 */

// ── Structural ──────────────────────────────────────────────────────────────
$src = file_get_contents( dirname( __DIR__ ) . '/staging-harness/mu-plugins/hectv-graphql-compat.php' );
if ( $src === false ) {
	fwrite( STDERR, "missing hectv-graphql-compat.php\n" );
	exit( 1 );
}

$required = array(
	"'phone_number'",
	"'fax_number'",
	"'video_id'",
	"'tv_providers'",
	"'directions'",
	"'opportunities'",
	"\$pick( \$source, 'address'",
	"\$pick( \$source, 'team'",
	'function hectv_gql_acf_field',
	'hectv_gql_acf_field( $source',
);

foreach ( $required as $needle ) {
	if ( strpos( $src, $needle ) === false ) {
		fwrite( STDERR, "FAIL missing: $needle\n" );
		exit( 1 );
	}
}

// Shared helper must stay raw-meta-only (no get_field inside hectv_gql_meta body).
if ( ! preg_match( '/function hectv_gql_meta\s*\([^)]*\)\s*\{(.*?)\nfunction /s', $src, $m ) ) {
	fwrite( STDERR, "FAIL could not isolate hectv_gql_meta body\n" );
	exit( 1 );
}
if ( strpos( $m[1], 'get_field' ) !== false ) {
	fwrite( STDERR, "FAIL hectv_gql_meta must not call get_field (breaks image return_format=array)\n" );
	exit( 1 );
}
if ( strpos( $src, 'function hectv_gql_acf_field' ) === false || strpos( $src, "function_exists( 'get_field' )" ) === false ) {
	fwrite( STDERR, "FAIL ACF lookup must live in hectv_gql_acf_field only\n" );
	exit( 1 );
}

echo "OK structural: about/contact use scoped ACF helper; hectv_gql_meta stays raw\n";

// ── Behavioral ──────────────────────────────────────────────────────────────
define( 'ABSPATH', __DIR__ );

$GLOBALS['actions'] = array();
$GLOBALS['filters'] = array();
$GLOBALS['post_meta'] = array(
	// Raw attachment ID as WordPress stores it for ACF image fields.
	42 => array(
		'post_header'  => '99',
		'post_hero'    => '99',
		'video_image'  => '99',
		// Legacy about seed shape (JSON string).
		'about_team'   => '[{"name":"Legacy","email":"l@example.com","position":"Editor"}]',
		'about_tv_providers' => '[{"provider":"LegacyCable","channel":"3"}]',
		// Live ACF keys as raw meta (JSON) when get_field is absent for that key.
		'phone_number' => '555-0100',
	),
);
$GLOBALS['posts'] = array(
	99 => (object) array(
		'ID'          => 99,
		'post_type'   => 'attachment',
		'post_status' => 'inherit',
	),
	42 => (object) array(
		'ID'          => 42,
		'post_type'   => 'page',
		'post_status' => 'publish',
	),
);
// ACF returns formatted image array for media fields — must NOT leak through hectv_gql_meta.
$GLOBALS['acf_fields'] = array(
	42 => array(
		'post_header'  => array(
			'ID'  => 99,
			'url' => 'https://example.test/header.jpg',
			// Cast of this array to int is 1 — the regression the CR called out.
			'id'  => 99,
		),
		'team'         => array(
			array( 'name' => 'Dev User', 'email' => 'dev@example.com', 'position' => 'Engineer' ),
		),
		'tv_providers' => array(
			array( 'provider' => 'CableCo', 'channel' => '12' ),
		),
		'address'      => '123 Staging St',
	),
);

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['actions'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['filters'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
}

function get_post_meta( $post_id, $key, $single = true ) {
	if ( ! isset( $GLOBALS['post_meta'][ $post_id ][ $key ] ) ) {
		return $single ? '' : array();
	}
	return $GLOBALS['post_meta'][ $post_id ][ $key ];
}

function get_post( $post_id ) {
	return isset( $GLOBALS['posts'][ $post_id ] ) ? $GLOBALS['posts'][ $post_id ] : null;
}

function get_field( $key, $post_id ) {
	if ( ! isset( $GLOBALS['acf_fields'][ $post_id ][ $key ] ) ) {
		return null;
	}
	return $GLOBALS['acf_fields'][ $post_id ][ $key ];
}

function expect_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, "$message\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

function expect_true( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "$message\n" );
		exit( 1 );
	}
}

require dirname( __DIR__ ) . '/staging-harness/mu-plugins/hectv-graphql-compat.php';

$page = (object) array( 'ID' => 42 );

// Shared helper must stay raw even when get_field would return an image array.
$raw_header = hectv_gql_meta( $page, 'post_header', null );
expect_same( '99', $raw_header, 'hectv_gql_meta must return raw attachment id, not ACF image array' );
expect_true( ! is_array( $raw_header ), 'hectv_gql_meta must not surface ACF array for image fields' );

$media = hectv_gql_media( $raw_header );
expect_true( is_object( $media ) && (int) $media->ID === 99, 'hectv_gql_media must resolve the real attachment id 99, not cast-array id 1' );

// About/Contact scoped helper accepts ACF repeater arrays.
$team = hectv_gql_acf_field( $page, 'team', null );
expect_true( is_array( $team ) && isset( $team[0]['name'] ) && $team[0]['name'] === 'Dev User', 'hectv_gql_acf_field must return ACF team repeater arrays' );

$providers = hectv_gql_acf_field( $page, 'tv_providers', null );
expect_true( is_array( $providers ) && $providers[0]['provider'] === 'CableCo', 'hectv_gql_acf_field must return ACF tv_providers repeater arrays' );

// Legacy JSON fallback when ACF has no primary key but post_meta has the prefixed seed key.
unset( $GLOBALS['acf_fields'][42]['team'] );
$legacy_team = hectv_gql_acf_field( $page, 'about_team', null );
expect_true( is_string( $legacy_team ) && strpos( $legacy_team, 'Legacy' ) !== false, 'legacy about_team JSON string must still be readable via acf helper fallback to post_meta' );

// Scalar ACF string still works for contact address.
$address = hectv_gql_acf_field( $page, 'address', null );
expect_same( '123 Staging St', $address, 'hectv_gql_acf_field returns ACF scalar strings' );

// Raw meta path for phone when only post_meta is set (no ACF entry).
$phone = hectv_gql_acf_field( $page, 'phone_number', null );
expect_same( '555-0100', $phone, 'hectv_gql_acf_field falls back to raw post_meta for scalars' );

echo "OK behavioral: raw media meta intact under ACF; about/contact ACF + JSON paths work\n";
