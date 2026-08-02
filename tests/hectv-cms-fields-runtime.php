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

function get_post_meta( $post_id, $key, $single = true ) {
	global $hectv_test_meta;
	if ( isset( $hectv_test_meta[ $post_id ][ $key ] ) ) {
		return $hectv_test_meta[ $post_id ][ $key ];
	}
	// Default used by earlier trendingPosts / isTrending checks.
	if ( (int) $post_id === 7 && $key === 'is_trending' ) {
		return '1';
	}
	return '';
}

function get_field( $key, $post_id = false ) {
	// Prefer empty so resolvers exercise get_post_meta fallback.
	return null;
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

class WP_Post {
	public $ID;
	public $post_status = 'publish';
	public $post_type = 'post';
	public function __construct( $id ) {
		$this->ID = (int) $id;
	}
}

// Fixture posts returned by the stub WP_Query depending on args.
$wp_query_log = array();
$wp_query_fixture = array(
	// Default empty; tests reconfigure per scenario.
	'trending' => array(),
	'fill'     => array(),
);

class WP_Query {
	public $posts = array();
	public function __construct( $args ) {
		global $last_wp_query_args, $wp_query_log, $wp_query_fixture;
		$last_wp_query_args = $args;
		$wp_query_log[]     = $args;

		$is_trending_query = isset( $args['meta_query'][0]['key'] )
			&& $args['meta_query'][0]['key'] === HECTV_META_IS_TRENDING;

		if ( $is_trending_query ) {
			$this->posts = $wp_query_fixture['trending'];
			return;
		}
		$this->posts = $wp_query_fixture['fill'];
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

// Provide Model\Post so GraphQL resolve can map WP posts.
if ( ! class_exists( '\\WPGraphQL\\Model\\Post', false ) ) {
	eval( 'namespace WPGraphQL\\Model; class Post { public $ID; public function __construct( $p ) { $this->ID = is_object($p) && isset($p->ID) ? (int)$p->ID : (int)$p; } }' );
}

// Empty DB: trending query + fill query both empty → empty list.
$wp_query_log     = array();
$wp_query_fixture = array( 'trending' => array(), 'fill' => array() );
$trending         = $graphql_fields['RootQuery']['trendingPosts']['resolve']( null, array( 'first' => 3 ) );
expect_same( array(), $trending, 'Empty query results should return an empty GraphQL list.' );
expect_same( 2, count( $wp_query_log ), 'Empty rail runs trending query then backfill query.' );
expect_same( HECTV_META_IS_TRENDING, $wp_query_log[0]['meta_query'][0]['key'], 'First query filters is_trending.' );
expect_same( 3, $wp_query_log[0]['posts_per_page'], 'Trending query uses requested limit.' );
expect_true( empty( $wp_query_log[1]['meta_query'] ), 'Backfill query is most-recent without is_trending filter.' );
expect_same( 3, $wp_query_log[1]['posts_per_page'], 'Backfill asks for remaining slots (full limit when none trending).' );

// Partial trending: 1 flagged + fill to requested size.
$wp_query_log     = array();
$wp_query_fixture = array(
	'trending' => array( new WP_Post( 101 ) ),
	'fill'     => array( new WP_Post( 201 ), new WP_Post( 202 ) ),
);
$partial = hectv_cms_query_trending_posts( 3 );
expect_same( 3, count( $partial ), 'Partial trending backfills to requested size.' );
expect_same( 101, (int) $partial[0]->ID, 'Flagged trending post comes first.' );
expect_same( 201, (int) $partial[1]->ID, 'Backfill uses most recent after trending.' );
expect_same( array( 101 ), $wp_query_log[1]['post__not_in'], 'Backfill excludes already-selected trending IDs.' );

// GraphQL resolve maps models when data exists.
$wp_query_log     = array();
$wp_query_fixture = array(
	'trending' => array( new WP_Post( 101 ) ),
	'fill'     => array( new WP_Post( 201 ) ),
);
$resolved = $graphql_fields['RootQuery']['trendingPosts']['resolve']( null, array( 'first' => 2 ) );
expect_same( 2, count( $resolved ), 'GraphQL resolve returns filled list as models.' );
expect_same( 101, (int) $resolved[0]->ID, 'GraphQL order keeps trending first.' );

// Full trending set: no need to over-fill beyond limit.
$wp_query_log     = array();
$wp_query_fixture = array(
	'trending' => array( new WP_Post( 1 ), new WP_Post( 2 ), new WP_Post( 3 ) ),
	'fill'     => array( new WP_Post( 9 ) ),
);
$full = hectv_cms_query_trending_posts( 3 );
expect_same( 3, count( $full ), 'When enough trending posts exist, return exactly the limit.' );
expect_same( 1, count( $wp_query_log ), 'No backfill query when trending set already full.' );

// Config default size when first omitted (options stub → default 5).
$wp_query_log     = array();
$wp_query_fixture = array( 'trending' => array(), 'fill' => array() );
hectv_cms_query_trending_posts( null );
expect_same( 5, $wp_query_log[0]['posts_per_page'], 'Default limit comes from trending max config (5).' );

// postDetails GraphQL type + field must be registered with integrated ACF fields.
expect_same( true, isset( $graphql_types['HecPostDetails'] ), 'HecPostDetails type registered.' );
expect_same( true, isset( $graphql_fields['Post']['postDetails'] ), 'Post.postDetails field registered.' );
expect_same( true, isset( $graphql_fields['Post']['isTrending'] ), 'Post.isTrending field registered.' );
$pd_fields = $graphql_types['HecPostDetails']['fields'];
foreach ( array( 'youtubeId', 'vimeoId', 'embedUrl', 'isVideo', 'isTrending', 'videoImage', 'postHeader', 'showPodcasts', 'hidePageThumbnail', 'pollForUpdates', 'relatedPosts', 'postEvents', 'broadcastLocation', 'internalId', 'duration' ) as $fname ) {
	expect_true( isset( $pd_fields[ $fname ] ), "HecPostDetails includes $fname" );
}

// Resolver returns integrated meta keys from post meta.
$GLOBALS['hectv_test_meta'] = array(
	7 => array(
		'is_video'       => '1',
		'is_trending'    => '1',
		'youtube_id'     => 'yt-abc',
		'vimeo_id'       => 'vim-9',
		'embed_url'      => 'https://example.test/embed',
		'show_podcasts'  => '1',
		'poll_for_updates' => '30',
		'broadcast_location' => '/media/file.mp4',
		'internal_id'    => 'INT-1',
		'duration'       => '12:34',
	),
);
$details = $graphql_fields['Post']['postDetails']['resolve']( (object) array( 'databaseId' => 7 ) );
expect_same( true, $details['isVideo'], 'postDetails.isVideo from meta' );
expect_same( true, $details['isTrending'], 'postDetails.isTrending from meta' );
expect_same( 'yt-abc', $details['youtubeId'], 'postDetails.youtubeId from meta' );
expect_same( 'vim-9', $details['vimeoId'], 'postDetails.vimeoId from meta' );
expect_same( 'https://example.test/embed', $details['embedUrl'], 'postDetails.embedUrl from meta' );
expect_same( '/media/file.mp4', $details['broadcastLocation'], 'postDetails.broadcastLocation from meta' );
// P1 regression: pollForUpdates must remain a numeric interval (seconds), not bool.
// Frontend does pollInterval: pollForUpdates * 1000 — true*1000 === 1000 (1s) is wrong.
expect_true( is_float( $details['pollForUpdates'] ) || is_int( $details['pollForUpdates'] ), 'pollForUpdates is numeric' );
expect_true( (float) $details['pollForUpdates'] > 1, 'pollForUpdates preserves interval > 1 (not coerced to true)' );
expect_same( 30.0, (float) $details['pollForUpdates'], 'pollForUpdates returns 30 seconds unchanged' );
expect_same( 'Float', $pd_fields['pollForUpdates']['type'], 'pollForUpdates GraphQL type is Float' );

// Unit-level: hectv_cms_gql_float must not bool-coerce.
expect_same( 30.0, hectv_cms_gql_float( '30' ), 'gql_float keeps 30' );
expect_same( null, hectv_cms_gql_float( true ), 'gql_float refuses bool true' );
expect_same( null, hectv_cms_gql_float( '' ), 'gql_float empty → null' );

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
