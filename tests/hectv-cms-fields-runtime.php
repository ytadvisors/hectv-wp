<?php
/**
 * Runtime contract tests for the CMS fields MU-plugin using focused WordPress
 * and WPGraphQL stubs. Run: php tests/hectv-cms-fields-runtime.php
 */

define( 'ABSPATH', __DIR__ );
putenv( 'HECTV_ENVIRONMENT=staging' );

$actions             = array();
$filters             = array();
$graphql_types       = array();
$graphql_fields      = array();
$acf_groups          = array();
$acf_fields          = array();
$existing_acf_groups = array(
	array( 'key' => 'group_legacy_post_details', 'title' => 'Post Details' ),
);
$options             = array();
$last_wp_query_args  = array();

function add_action( $hook, $callback, $priority = 10 ) {
	global $actions;
	$actions[ $hook ][ $priority ][] = $callback;
}

function add_filter( $hook, $callback, $priority = 10 ) {
	global $filters;
	$filters[ $hook ][ $priority ][] = $callback;
}

function register_graphql_object_type( $name, $config ) {
	global $graphql_types;
	if ( isset( $graphql_types[ $name ] ) ) {
		throw new RuntimeException( "Duplicate GraphQL type: $name" );
	}
	$graphql_types[ $name ] = $config;
}

function register_graphql_field( $type, $name, $config ) {
	global $graphql_fields;
	$graphql_fields[ $type ][ $name ] = $config;
}

function acf_get_field_groups() {
	global $existing_acf_groups;
	return $existing_acf_groups;
}

function acf_add_local_field_group( $group ) {
	global $acf_groups;
	$acf_groups[] = $group;
}

function acf_get_fields() {
	return array();
}

function acf_add_local_field( $field ) {
	global $acf_fields;
	$acf_fields[] = $field;
}

function get_option( $key, $default = false ) {
	global $options;
	return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
}

function get_nav_menu_locations() {
	return array( HECTV_MENU_HEADER_ACTIONS => 55 );
}

function wp_get_nav_menu_items() {
	return array(
		(object) array(
			'menu_item_parent' => 0,
			'title'            => 'Subscribe',
			'url'              => '/newsletter',
			'classes'          => array( 'primary' ),
		),
	);
}

function get_post_meta( $post_id, $key ) {
	return $post_id === 7 && $key === 'is_trending' ? '1' : '';
}

function get_post() {
	return null;
}

function __( $value ) {
	return $value;
}

function register_nav_menu() {}
function register_post_meta() {}
function current_user_can() { return true; }
function wp_get_nav_menu_object() { return false; }
function wp_create_nav_menu() { return 55; }
function is_wp_error() { return false; }
function wp_update_nav_menu_item() {}
function set_theme_mod() {}
function update_option() {}
function home_url( $path ) { return $path; }

class WP_Query {
	public $posts = array();
	public function __construct( $args ) {
		global $last_wp_query_args;
		$last_wp_query_args = $args;
	}
}

function expect_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, "$message\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-staging-content-controls.php';
require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-cms-fields.php';

foreach ( $actions['graphql_register_types'] as $callbacks ) {
	foreach ( $callbacks as $callback ) {
		$callback();
	}
}

expect_same( true, isset( $graphql_types['HectvForEducators'] ), 'Staging educator type should remain registered.' );
expect_same( true, isset( $graphql_types['HectvForEducatorsCard'] ), 'CMS educator card must use a distinct type.' );
expect_same( 'HectvForEducatorsCard', $graphql_fields['RootQuery']['forEducators']['type'], 'forEducators should use the collision-free type.' );

$root_fields = array(
	'topbarCtas' => $graphql_fields['RootQuery']['topbarCtas'],
);
foreach ( $filters['graphql_RootQuery_fields'][20] as $callback ) {
	$root_fields = $callback( $root_fields );
}
$topbar_rows = $root_fields['topbarCtas']['resolve']( null, array(), null, null );
expect_same( 'Subscribe', $topbar_rows[0]['label'], 'Empty staging options should fall back to the Header Actions menu.' );

$trending = $graphql_fields['RootQuery']['trendingPosts']['resolve']( null, array( 'first' => 3 ) );
expect_same( array(), $trending, 'Empty query results should return an empty GraphQL list.' );
expect_same( 3, $last_wp_query_args['posts_per_page'], 'trendingPosts should honor the requested limit.' );
expect_same( HECTV_META_IS_TRENDING, $last_wp_query_args['meta_query'][0]['key'], 'trendingPosts should filter on is_trending.' );

$acf_callback = $actions['acf/init'][10][0];
$acf_callback();
expect_same( array(), $acf_groups, 'Existing Post Details must not be duplicated.' );
expect_same( 'group_legacy_post_details', $acf_fields[0]['parent'], 'Trending should attach to the production Post Details key.' );

$existing_acf_groups = array();
$acf_groups          = array();
$acf_fields          = array();
$acf_callback();
expect_same( 'HEC Post Controls', $acf_groups[0]['title'], 'Clean installs should get a separate fallback group.' );
expect_same( 'group_hectv_post_controls', $acf_fields[0]['parent'], 'Fallback Trending field should use the fallback group.' );

echo "HEC CMS fields runtime contracts passed.\n";
