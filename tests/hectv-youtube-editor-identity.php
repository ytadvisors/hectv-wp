<?php
/**
 * Contracts for YouTube client identity in the WordPress editor.
 * Run: php tests/hectv-youtube-editor-identity.php
 */

define( 'ABSPATH', __DIR__ );

$filters = array();

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $filters;
	$filters[ $hook ][ $priority ][] = array(
		'callback'      => $callback,
		'accepted_args' => $accepted_args,
	);
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

class Hectv_Test_REST_Request {
	private $route;

	public function __construct( $route ) {
		$this->route = $route;
	}

	public function get_route() {
		return $this->route;
	}
}

class Hectv_Test_REST_Response {
	private $data;

	public function __construct( $data ) {
		$this->data = $data;
	}

	public function get_data() {
		return $this->data;
	}

	public function set_data( $data ) {
		$this->data = $data;
	}
}

require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-youtube-editor-identity.php';

expect_true( isset( $filters['oembed_result'][20][0] ), 'Fresh oEmbed results must receive YouTube identity.' );
expect_true( isset( $filters['embed_oembed_html'][20][0] ), 'Cached classic embeds must receive YouTube identity.' );
expect_true( isset( $filters['rest_post_dispatch'][20][0] ), 'Cached Gutenberg REST responses must receive YouTube identity.' );
expect_same( 3, $filters['rest_post_dispatch'][20][0]['accepted_args'], 'REST response filter must receive the request route.' );

$youtube = '<iframe width="560" height="315" src="https://www.youtube.com/embed/abc123?feature=oembed" frameborder="0"></iframe>';
$identified = hectv_youtube_add_editor_identity( $youtube );
expect_true( strpos( $identified, 'referrerpolicy="strict-origin-when-cross-origin"' ) !== false, 'YouTube iframe must use the recommended referrer policy.' );
expect_true( strpos( $identified, 'widget_referrer=https%3A%2F%2Fhecmedia.org%2F' ) !== false, 'Nested editor embed must identify the public HEC site.' );
expect_same( 1, substr_count( $identified, 'widget_referrer=' ), 'YouTube identity must not be duplicated.' );
expect_same( $identified, hectv_youtube_add_editor_identity( $identified ), 'YouTube identity rewrite must be idempotent.' );

$suppressed = '<iframe referrerpolicy="no-referrer" src="https://www.youtube-nocookie.com/embed/xyz789"></iframe>';
$repaired = hectv_youtube_add_editor_identity( $suppressed );
expect_true( strpos( $repaired, 'no-referrer' ) === false, 'A suppressing policy must be replaced for YouTube.' );
expect_true( strpos( $repaired, 'strict-origin-when-cross-origin' ) !== false, 'YouTube privacy-domain embeds must be repaired.' );

$vimeo = '<iframe src="https://player.vimeo.com/video/123"></iframe>';
expect_same( $vimeo, hectv_youtube_add_editor_identity( $vimeo ), 'Other oEmbed providers must remain unchanged.' );

$response = new Hectv_Test_REST_Response( array( 'html' => $youtube, 'provider_name' => 'YouTube' ) );
hectv_youtube_identify_oembed_rest_response(
	$response,
	null,
	new Hectv_Test_REST_Request( '/oembed/1.0/proxy' )
);
$response_data = $response->get_data();
expect_true( strpos( $response_data['html'], 'strict-origin-when-cross-origin' ) !== false, 'Cached Gutenberg proxy HTML must be repaired.' );

$unrelated = new Hectv_Test_REST_Response( array( 'html' => $youtube ) );
hectv_youtube_identify_oembed_rest_response(
	$unrelated,
	null,
	new Hectv_Test_REST_Request( '/wp/v2/posts' )
);
expect_same( $youtube, $unrelated->get_data()['html'], 'Unrelated REST routes must remain unchanged.' );

echo "HEC YouTube editor identity contracts passed.\n";
