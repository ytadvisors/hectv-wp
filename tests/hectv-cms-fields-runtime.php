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
$registered_settings = array();
$existing_acf_groups = array(
	array( 'key' => 'group_legacy_post_details', 'title' => 'Post Details' ),
	array( 'key' => 'group_legacy_about', 'title' => 'About' ),
	array( 'key' => 'group_legacy_contact', 'title' => 'Contact' ),
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

function register_setting( $group, $name, $config ) {
	global $registered_settings;
	$registered_settings[ $name ] = array(
		'group'  => $group,
		'config' => $config,
	);
}

function acf_get_field_groups() {
	global $existing_acf_groups;
	return $existing_acf_groups;
}

function acf_add_local_field_group( $group ) {
	global $acf_groups;
	$acf_groups[] = $group;
}

function acf_get_local_field_group( $key ) {
	global $acf_groups;
	foreach ( array_reverse( $acf_groups ) as $group ) {
		if ( isset( $group['key'] ) && $group['key'] === $key ) {
			return $group;
		}
	}
	return false;
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
	public $post_date_gmt = '';
	public function __construct( $id, $post_date_gmt = '' ) {
		$this->ID = (int) $id;
		$this->post_date_gmt = $post_date_gmt;
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

foreach ( $actions['admin_init'] as $callbacks ) {
	foreach ( $callbacks as $callback ) {
		$callback();
	}
}

foreach ( array( HECTV_OPT_TRENDING_TITLE, HECTV_OPT_SPOTLIGHT_TITLE, HECTV_OPT_MOBILE_DISPLAY, HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED ) as $option_name ) {
	expect_true( isset( $registered_settings[ $option_name ] ), "Site Settings registers $option_name." );
}
$mobile_sanitize = $registered_settings[ HECTV_OPT_MOBILE_DISPLAY ]['config']['sanitize_callback'];
expect_same( 'content-menu', $mobile_sanitize( 'content-menu' ), 'Mobile display accepts content-menu.' );
expect_same( 'menu-content', $mobile_sanitize( 'menu-content' ), 'Mobile display accepts menu-content.' );
expect_same( 'content-menu', $mobile_sanitize( 'unexpected' ), 'Mobile display rejects unknown values to content-first default.' );
$captcha_sanitize = $registered_settings[ HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED ]['config']['sanitize_callback'];
expect_same( true, $captcha_sanitize( '1' ), 'CAPTCHA setting accepts enabled.' );
expect_same( false, $captcha_sanitize( '0' ), 'CAPTCHA setting accepts disabled.' );

expect_same( false, $filters['acf/settings/show_admin'][99][0](), 'Git-canonical ACF hides the Custom Fields schema menu by default.' );

foreach ( $actions['graphql_register_types'] as $callbacks ) {
	foreach ( $callbacks as $callback ) {
		$callback();
	}
}

expect_same( true, isset( $graphql_types['HectvForEducators'] ), 'Staging educator type should remain registered.' );
expect_same( true, isset( $graphql_types['HectvForEducatorsCard'] ), 'CMS educator card must use a distinct type.' );
expect_same( 'HectvForEducatorsCard', $graphql_fields['RootQuery']['forEducators']['type'], 'forEducators should use the collision-free type.' );
expect_same( true, isset( $graphql_types['HectvNewsletterSettings'] ), 'Newsletter settings GraphQL type registered.' );

$trending_settings = $graphql_fields['RootQuery']['trendingSettings']['resolve']();
expect_same( 5, $trending_settings['maxVideos'], 'Trending settings use the default max.' );
expect_same( 'Trending Now', $trending_settings['trendingTitle'], 'Trending heading defaults safely.' );
expect_same( 'Spotlight STL', $trending_settings['spotlightTitle'], 'Spotlight heading defaults safely.' );
expect_same( 'content-menu', $trending_settings['mobileDisplay'], 'Mobile display defaults to content before menu.' );
expect_same( true, $graphql_fields['RootQuery']['newsletterSettings']['resolve']()['captchaEnabled'], 'Newsletter CAPTCHA defaults on.' );

$options = array(
	HECTV_OPT_TRENDING_TITLE             => 'What is popular',
	HECTV_OPT_SPOTLIGHT_TITLE            => 'Community Spotlight',
	HECTV_OPT_MOBILE_DISPLAY             => 'content-menu',
	HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED => '0',
);
$custom_settings = $graphql_fields['RootQuery']['trendingSettings']['resolve']();
expect_same( 'What is popular', $custom_settings['trendingTitle'], 'Trending heading reads Site Settings.' );
expect_same( 'Community Spotlight', $custom_settings['spotlightTitle'], 'Spotlight heading reads Site Settings.' );
expect_same( 'content-menu', $custom_settings['mobileDisplay'], 'Mobile display reads Site Settings.' );
expect_same( false, $graphql_fields['RootQuery']['newsletterSettings']['resolve']()['captchaEnabled'], 'Newsletter CAPTCHA can be disabled in Site Settings.' );
$options = array();

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
expect_same( -1, $wp_query_log[0]['posts_per_page'], 'Trending query loads all flagged posts before applying editor order.' );
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

// Per-post order wins over publish date; positive positions precede unset rows.
$GLOBALS['hectv_test_meta'] = array(
	101 => array( HECTV_META_TRENDING_ORDER => '3' ),
	102 => array( HECTV_META_TRENDING_ORDER => '1' ),
	103 => array( HECTV_META_TRENDING_ORDER => '' ),
	104 => array( HECTV_META_TRENDING_ORDER => '2' ),
);
$wp_query_log     = array();
$wp_query_fixture = array(
	'trending' => array(
		new WP_Post( 101, '2026-08-05 12:00:00' ),
		new WP_Post( 102, '2026-07-01 12:00:00' ),
		new WP_Post( 103, '2026-08-06 12:00:00' ),
		new WP_Post( 104, '2026-06-01 12:00:00' ),
	),
	'fill'     => array(),
);
$ordered = hectv_cms_query_trending_posts( 4 );
expect_same( array( 102, 104, 101, 103 ), array_map( static function ( $post ) { return (int) $post->ID; }, $ordered ), 'Trending posts follow their per-post numeric order before unordered posts.' );

// Config default size when first omitted (options stub → default 5).
$GLOBALS['hectv_test_meta'] = array();
$wp_query_log     = array();
$wp_query_fixture = array( 'trending' => array(), 'fill' => array() );
hectv_cms_query_trending_posts( null );
expect_same( -1, $wp_query_log[0]['posts_per_page'], 'Default-sized rail still loads all flagged posts for ordering.' );

// postDetails GraphQL type + field must be registered with integrated ACF fields.
expect_same( true, isset( $graphql_types['HecPostDetails'] ), 'HecPostDetails type registered.' );
expect_same( true, isset( $graphql_fields['Post']['postDetails'] ), 'Post.postDetails field registered.' );
expect_same( true, isset( $graphql_fields['Post']['isTrending'] ), 'Post.isTrending field registered.' );
expect_same( true, isset( $graphql_fields['Post']['trendingOrder'] ), 'Post.trendingOrder field registered.' );
$pd_fields = $graphql_types['HecPostDetails']['fields'];
foreach ( array( 'youtubeId', 'vimeoId', 'embedUrl', 'isVideo', 'isTrending', 'trendingOrder', 'videoImage', 'postHeader', 'postHero', 'showPodcasts', 'hidePageThumbnail', 'pollForUpdates', 'relatedPosts', 'postEvents', 'broadcastLocation', 'internalId', 'duration' ) as $fname ) {
	expect_true( isset( $pd_fields[ $fname ] ), "HecPostDetails includes $fname" );
}

// Resolver returns integrated meta keys from post meta.
$GLOBALS['hectv_test_meta'] = array(
	7 => array(
		'is_video'       => '1',
		'is_trending'    => '1',
		'trending_order' => '2',
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
expect_same( 2, $details['trendingOrder'], 'postDetails.trendingOrder from meta' );
expect_same( 'yt-abc', $details['youtubeId'], 'postDetails.youtubeId from meta' );
expect_same( 'vim-9', $details['vimeoId'], 'postDetails.vimeoId from meta' );
expect_same( 'https://example.test/embed', $details['embedUrl'], 'postDetails.embedUrl from meta' );
expect_same( '/media/file.mp4', $details['broadcastLocation'], 'postDetails.broadcastLocation from meta' );
// Media keys must always be present on the resolve payload (GraphQL type registration alone
// is insufficient — a forgotten key silently nulls the advertised field).
foreach ( array( 'postHero', 'postHeader', 'videoImage' ) as $media_key ) {
	expect_true( array_key_exists( $media_key, $details ), "postDetails resolve emits $media_key" );
	expect_same( null, $details[ $media_key ], "$media_key is null when its meta is empty" );
}
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

// --- Existing DB path: overlay one same-key complete Post Details local group.
// A lone local Trending child shadows DB-owned children in affected ACF versions.
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
expect_same( 1, count( $post_details_clones ), 'Existing Post Details should have one complete local overlay.' );
$existing_pd = $post_details_clones[0];
expect_same( 'group_legacy_post_details', $existing_pd['key'], 'Local overlay must reuse the active database group key.' );

$existing_names = array();
$hero_overlay_fields = array();
foreach ( (array) $existing_pd['fields'] as $field ) {
	if ( ! empty( $field['name'] ) ) {
		$existing_names[] = $field['name'];
	}
	if ( isset( $field['name'] ) && $field['name'] === HECTV_META_POST_HERO ) {
		$hero_overlay_fields[] = $field;
	}
}
// Require the complete same-key overlay to ship post_hero as its own ACF child with a
// main-page-facing label — not a lone GraphQL registration or a bare name list check.
expect_same( 1, count( $hero_overlay_fields ), 'Post Details overlay registers exactly one post_hero child' );
expect_true(
	isset( $hero_overlay_fields[0]['label'] )
		&& stripos( (string) $hero_overlay_fields[0]['label'], 'hero' ) !== false,
	'post_hero overlay field label identifies the main-page hero'
);
expect_true(
	isset( $hero_overlay_fields[0]['key'] )
		&& $hero_overlay_fields[0]['key'] === 'field_hectv_post_hero',
	'post_hero overlay field uses the owned field key'
);
// Required children must all be present (legacy + trending + hero). Avoid a brittle
// hard-coded total that drifts whenever a single field is added.
foreach ( array( 'is_video', 'poll_for_updates', 'related_posts', HECTV_META_POST_HERO, HECTV_META_IS_TRENDING, HECTV_META_TRENDING_ORDER ) as $need ) {
	expect_true( in_array( $need, $existing_names, true ), "Existing Post Details overlay includes $need" );
}
expect_same( count( $existing_names ), count( array_unique( $existing_names ) ), 'overlay field names are unique' );
expect_same( array(), $acf_fields, 'Complete Post Details overlay must not add a lone local child.' );

// ACF 5.6.9 keeps a same-key database row ahead of a local group. A disabled
// DB copy must be replaced by the complete active canonical definition so the
// Post Details metabox is actually registered on post edit screens.
$group_overlay_filter = $filters['acf/get_field_groups'][30][0];
$filtered_groups      = $group_overlay_filter(
	array(
		array(
			'key'      => 'group_legacy_post_details',
			'title'    => 'Post Details',
			'active'   => false,
			'location' => array(),
		),
		array(
			'key'    => 'group_unrelated_plugin',
			'title'  => 'Unrelated plugin group',
			'active' => true,
		),
	)
);
expect_same( 'group_legacy_post_details', $filtered_groups[0]['key'], 'Same-key canonical group keeps its production identity.' );
expect_same( true, $filtered_groups[0]['active'], 'Canonical Post Details replaces a disabled same-key DB row.' );
expect_same( 'post_type', $filtered_groups[0]['location'][0][0]['param'], 'Canonical Post Details restores the post edit location.' );
expect_same( 'post', $filtered_groups[0]['location'][0][0]['value'], 'Canonical Post Details targets posts.' );
expect_true( count( $filtered_groups[0]['fields'] ) > 0, 'Canonical Post Details replacement includes its fields.' );
expect_same( 'group_unrelated_plugin', $filtered_groups[1]['key'], 'Unrelated plugin groups remain unchanged.' );

// Every export group is a complete local overlay. Same-title database groups
// reuse their active group keys so the editor never shows duplicate sections.
$registered_titles = array_map(
	static function ( $g ) {
		return isset( $g['title'] ) ? $g['title'] : '';
	},
	$acf_groups
);
expect_true( in_array( 'About', $registered_titles, true ), 'About group should register from the canonical export.' );
expect_same( 1, count( array_keys( $registered_titles, 'Post Details', true ) ), 'Post Details local overlay must be registered exactly once.' );
$about_overlays = array_values(
	array_filter(
		$acf_groups,
		static function ( $g ) {
			return isset( $g['title'] ) && $g['title'] === 'About';
		}
	)
);
$contact_overlays = array_values(
	array_filter(
		$acf_groups,
		static function ( $g ) {
			return isset( $g['title'] ) && $g['title'] === 'Contact';
		}
	)
);
expect_same( 1, count( $about_overlays ), 'About has exactly one canonical overlay.' );
expect_same( 'group_legacy_about', $about_overlays[0]['key'], 'About overlay reuses same-title database key.' );
expect_same( 'page_template', $about_overlays[0]['location'][0][0]['param'], 'About overlay uses template location, not every Page.' );
expect_same( 'template-1.php', $about_overlays[0]['location'][0][0]['value'], 'About overlay targets template-1.php.' );
expect_same( 1, count( $contact_overlays ), 'Contact has exactly one canonical overlay.' );
expect_same( 'group_legacy_contact', $contact_overlays[0]['key'], 'Contact overlay reuses same-title database key.' );
expect_same( 'page_template', $contact_overlays[0]['location'][0][0]['param'], 'Contact overlay uses template location, not every Page.' );
expect_same( 'template-3.php', $contact_overlays[0]['location'][0][0]['value'], 'Contact overlay targets template-3.php.' );

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
expect_true( in_array( HECTV_META_TRENDING_ORDER, $names, true ), 'Clean Post Details includes git-owned trending_order.' );

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

$order_attaches = array_values(
	array_filter(
		$acf_fields,
		static function ( $f ) {
			return isset( $f['name'] ) && $f['name'] === HECTV_META_TRENDING_ORDER;
		}
	)
);
expect_same( array(), $order_attaches, 'trending_order should be nested in Post Details fields, not double-attached.' );

echo "HEC CMS fields runtime contracts passed.\n";
