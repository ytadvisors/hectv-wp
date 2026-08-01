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

function expect_true( $cond, $message ) {
	if ( ! $cond ) {
		fwrite( STDERR, "$message\n" );
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

// --- Production path: Post Details already exists → attach Trending only; no Post Details clone.
$acf_groups = array();
$acf_fields = array();
$acf_callback();

$post_details_clones = array_values(
	array_filter(
		$acf_groups,
		static function ( $g ) {
			return isset( $g['title'] ) && $g['title'] === 'Post Details';
		}
	)
);
expect_same( array(), $post_details_clones, 'Existing Post Details must not be duplicated.' );
expect_true( count( $acf_fields ) >= 1, 'Trending field should be attached when Post Details exists.' );
expect_same( 'group_legacy_post_details', $acf_fields[0]['parent'], 'Trending should attach to the production Post Details key.' );
expect_same( HECTV_META_IS_TRENDING, $acf_fields[0]['name'], 'Attached field must be is_trending.' );

// Other export groups that are missing should still register (About, Contact, …).
$registered_titles = array_map(
	static function ( $g ) {
		return isset( $g['title'] ) ? $g['title'] : '';
	},
	$acf_groups
);
expect_true( in_array( 'About', $registered_titles, true ), 'Missing About group should register from export.' );
expect_true( ! in_array( 'Post Details', $registered_titles, true ), 'Post Details title must not appear in local register when DB owns it.' );

// --- Clean install path: no existing groups → full export including Post Details + baked-in Trending.
$existing_acf_groups = array();
$acf_groups          = array();
$acf_fields          = array();
$acf_callback();

$pd = null;
foreach ( $acf_groups as $g ) {
	if ( isset( $g['title'] ) && $g['title'] === 'Post Details' ) {
		$pd = $g;
		break;
	}
}
expect_true( is_array( $pd ), 'Clean installs should register Post Details from export.' );
expect_same( HECTV_ACF_POST_DETAILS_KEY, $pd['key'], 'Clean-install Post Details must use the production group key.' );

$names = array();
foreach ( (array) $pd['fields'] as $field ) {
	if ( ! empty( $field['name'] ) ) {
		$names[] = $field['name'];
	}
}
expect_true( in_array( 'is_video', $names, true ), 'Clean Post Details includes legacy is_video.' );
expect_true( in_array( 'youtube_id', $names, true ), 'Clean Post Details includes legacy youtube_id.' );
expect_true( in_array( HECTV_META_IS_TRENDING, $names, true ), 'Clean Post Details includes git-owned is_trending.' );

// When Post Details is registered with nested is_trending, no separate acf_add_local_field is needed.
$trending_attaches = array_values(
	array_filter(
		$acf_fields,
		static function ( $f ) {
			return isset( $f['name'] ) && $f['name'] === HECTV_META_IS_TRENDING;
		}
	)
);
expect_same( array(), $trending_attaches, 'is_trending should be nested in Post Details fields, not double-attached.' );

echo "HEC CMS fields runtime contracts passed.\n";
