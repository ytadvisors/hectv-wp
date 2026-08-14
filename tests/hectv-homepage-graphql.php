<?php
/**
 * Focused runtime contract for canonical Home-page ACF GraphQL resolvers.
 * Run: php tests/hectv-homepage-graphql.php
 */

define( 'ABSPATH', __DIR__ );

$actions        = array();
$filters        = array();
$graphql_fields = array();
$post_meta      = array();
$posts          = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['actions'][ $hook ][ $priority ][] = $callback;
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['filters'][ $hook ][ $priority ][] = $callback;
}

function register_graphql_object_type() {}
function register_graphql_enum_type() {}
function register_graphql_input_type() {}

function register_graphql_field( $type, $name, $config ) {
	$GLOBALS['graphql_fields'][ $type ][ $name ] = $config;
}

function metadata_exists( $meta_type, $post_id, $key ) {
	return $meta_type === 'post' && array_key_exists( $key, $GLOBALS['post_meta'][ $post_id ] ?? array() );
}

function get_post_meta( $post_id, $key, $single = true ) {
	return $GLOBALS['post_meta'][ $post_id ][ $key ] ?? '';
}

function get_post( $post_id ) {
	return $GLOBALS['posts'][ $post_id ] ?? null;
}

function get_posts() {
	throw new RuntimeException( 'Required Posts must never masquerade latest posts as editorial pins.' );
}

class WP_Post {
	public $ID;
	public $post_status;
	public $post_type = 'post';

	public function __construct( $id, $status = 'publish' ) {
		$this->ID          = (int) $id;
		$this->post_status = $status;
	}
}

function expect_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__ ) . '/staging-harness/mu-plugins/hectv-graphql-compat.php';

foreach ( $actions['graphql_register_types'][10] as $callback ) {
	$callback();
}

$required_posts = $graphql_fields['Page']['requiredPosts']['resolve'];
$feed_design    = $graphql_fields['Page']['feedDesign']['resolve'];

foreach ( array( 201, 202, 204, 205 ) as $post_id ) {
	$posts[ $post_id ] = new WP_Post( $post_id );
}
$posts[203] = new WP_Post( 203, 'draft' );

// Canonical production ACF storage must win over conflicting staging fixtures.
$post_meta[31155] = array(
	'post_list'                    => '3',
	'post_list_0_post'             => '202',
	'post_list_1_post'             => '201',
	'post_list_2_post'             => '203',
	'required_posts'               => '204,205',
	'new_row_layout'               => '2',
	'new_row_layout_0_row_layout'  => 'Featured',
	'new_row_layout_0_display_type' => 'Post',
	'new_row_layout_1_row_layout'  => '3 Columns',
	'new_row_layout_1_display_type' => 'Wallpaper',
	'feed_design_rows'             => '[{"rowLayout":"Single Column","displayType":"Post"}]',
	'default_display_type'         => 'Post',
	'default_row_layout'           => 'Single Column',
);

$resolved = $required_posts( (object) array( 'databaseId' => 31155 ) );
$ids      = array_map(
	static function ( $row ) {
		return $row['post']->ID;
	},
	$resolved['postList']
);
expect_same( array( 202, 201 ), $ids, 'Canonical pins should preserve order and omit unpublished posts.' );

$design = $feed_design( (object) array( 'databaseId' => 31155 ) );
expect_same(
	array(
		array( 'rowLayout' => 'Featured', 'displayType' => 'Post' ),
		array( 'rowLayout' => '3 Columns', 'displayType' => 'Wallpaper' ),
	),
	$design['newRowLayout'],
	'Canonical Feed Design rows should win over the legacy JSON fixture.'
);
expect_same( 'Post', $design['defaultDisplayType'], 'Feed Design should expose the production display default.' );
expect_same( 'Single Column', $design['defaultRowLayout'], 'Feed Design should expose the production row default.' );

// An explicitly empty canonical repeater must stay empty, even if stale fixture
// meta remains on the page.
$post_meta[32623] = array(
	'post_list'        => '0',
	'required_posts'   => '204,205',
	'new_row_layout'   => '0',
	'feed_design_rows' => '[{"rowLayout":"Featured","displayType":"Post"}]',
);
expect_same(
	array( 'postList' => array() ),
	$required_posts( (object) array( 'databaseId' => 32623 ) ),
	'Explicitly empty pins should not fall back to fixture or latest posts.'
);
expect_same(
	array(),
	$feed_design( (object) array( 'databaseId' => 32623 ) )['newRowLayout'],
	'Explicitly empty layout rows should not fall back to fixture rows.'
);

// The isolated staging harness has only legacy fixture keys and remains supported.
$post_meta[900] = array(
	'required_posts'   => '204,205',
	'feed_design_rows' => '[{"rowLayout":"Featured","displayType":"Post"}]',
);
$fixture_ids = array_map(
	static function ( $row ) {
		return $row['post']->ID;
	},
	$required_posts( (object) array( 'databaseId' => 900 ) )['postList']
);
expect_same( array( 204, 205 ), $fixture_ids, 'Legacy staging pin fixtures should remain supported.' );
expect_same(
	array( array( 'rowLayout' => 'Featured', 'displayType' => 'Post' ) ),
	$feed_design( (object) array( 'databaseId' => 900 ) )['newRowLayout'],
	'Legacy staging layout fixtures should remain supported.'
);

// Missing editorial pins are intentionally empty; get_posts() above throws if
// a latest-post fallback is reintroduced.
expect_same(
	array( 'postList' => array() ),
	$required_posts( (object) array( 'databaseId' => 901 ) ),
	'Missing pins should resolve to an intentional empty list.'
);

echo "HEC homepage GraphQL contract passed.\n";
