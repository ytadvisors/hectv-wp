<?php
/**
 * Contracts for production publish-triggered CloudFront invalidation.
 * Run: php tests/hectv-cloudfront-invalidation.php
 */

define( 'ABSPATH', __DIR__ );

$actions = array();
$filters = array();
$logs    = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $actions;
	$actions[ $hook ][ $priority ][] = array(
		'callback'      => $callback,
		'accepted_args' => $accepted_args,
	);
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $filters;
	$filters[ $hook ][ $priority ][] = array(
		'callback'      => $callback,
		'accepted_args' => $accepted_args,
	);
}

function apply_filters( $hook, $value ) {
	global $filters;
	if ( empty( $filters[ $hook ] ) ) {
		return $value;
	}

	ksort( $filters[ $hook ] );
	foreach ( $filters[ $hook ] as $callbacks ) {
		foreach ( $callbacks as $registered ) {
			$value = call_user_func( $registered['callback'], $value );
		}
	}
	return $value;
}

function wp_is_post_revision( $post_id ) {
	return 700 === $post_id;
}

function wp_is_post_autosave( $post_id ) {
	return 701 === $post_id;
}

function wp_generate_uuid4() {
	return '11111111-2222-4333-8444-555555555555';
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

class Hectv_CloudFront_Fake_Client {
	public $calls = array();
	public $failure;

	public function createInvalidation( $request ) {
		$this->calls[] = $request;
		if ( $this->failure ) {
			throw $this->failure;
		}
		return array( 'Invalidation' => array( 'Id' => 'TEST' ) );
	}
}

require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-cloudfront-invalidation.php';

expect_true( isset( $actions['transition_post_status'][99][0] ), 'Published-post transition hook must register.' );
expect_true( isset( $actions['shutdown'][99][0] ), 'One shutdown flusher must register.' );
expect_true( isset( $actions['wp_update_nav_menu'][99][0] ), 'Menu changes must purge the shared site shell.' );
expect_true( isset( $actions['wp_delete_nav_menu'][99][0] ), 'Deleted menus must purge the shared site shell.' );
expect_true( isset( $actions['edited_term'][99][0] ), 'Taxonomy edits must purge public archives.' );
expect_true( isset( $actions['acf/save_post'][99][0] ), 'Global ACF settings must purge the shared site shell.' );
expect_true( isset( $actions['update_option_hectv_trending_title'][99][0] ), 'HEC Site Settings updates must purge the shared site shell.' );
expect_true( isset( $actions['add_option_hectv_educators_url'][99][0] ), 'First-time HEC Site Settings saves must purge the shared site shell.' );
expect_true( isset( $actions['delete_option_hectv_educators_url'][99][0] ), 'Deleted HEC Site Settings must purge the shared site shell.' );

putenv( 'HECTV_ENVIRONMENT=staging' );
putenv( 'HECTV_CLOUDFRONT_DISTRIBUTION_ID=E2QXRSF2W55RTS' );
hectv_cloudfront_queue_post_invalidation( 'publish', 'draft', (object) array( 'ID' => 100 ) );
expect_same( array(), $GLOBALS['hectv_cloudfront_invalidation_paths'], 'Non-production saves must never queue a purge.' );

putenv( 'HECTV_ENVIRONMENT=production' );
hectv_cloudfront_queue_post_invalidation( 'draft', 'draft', (object) array( 'ID' => 100 ) );
hectv_cloudfront_queue_post_invalidation( 'publish', 'draft', (object) array( 'ID' => 700 ) );
hectv_cloudfront_queue_post_invalidation( 'publish', 'draft', (object) array( 'ID' => 701 ) );
expect_same( array(), $GLOBALS['hectv_cloudfront_invalidation_paths'], 'Drafts, revisions, and autosaves must not purge public content.' );

$client = new Hectv_CloudFront_Fake_Client();
add_filter(
	'hectv_cloudfront_invalidation_client',
	function () use ( $client ) {
		return $client;
	}
);

hectv_cloudfront_queue_post_invalidation( 'publish', 'draft', (object) array( 'ID' => 101 ) );
hectv_cloudfront_queue_post_invalidation( 'publish', 'publish', (object) array( 'ID' => 101 ) );
hectv_cloudfront_queue_global_invalidation();
expect_same( array( '/*' => true ), $GLOBALS['hectv_cloudfront_invalidation_paths'], 'Duplicate publish hooks must coalesce to one wildcard path.' );
expect_same( true, hectv_cloudfront_flush_invalidation(), 'A valid production purge should succeed.' );
expect_same( 1, count( $client->calls ), 'One WordPress request must create one CloudFront invalidation.' );
expect_same( 'E2QXRSF2W55RTS', $client->calls[0]['DistributionId'], 'Invalidation must target only the HEC Media distribution.' );
expect_same(
	array( 'Quantity' => 1, 'Items' => array( '/*' ) ),
	$client->calls[0]['InvalidationBatch']['Paths'],
	'One wildcard must clear HTML, Next data, archives, and query-string variants.'
);
expect_true(
	0 === strpos( $client->calls[0]['InvalidationBatch']['CallerReference'], 'hectv-publish-' ),
	'Caller reference must identify the publish workflow.'
);
expect_same( array(), $GLOBALS['hectv_cloudfront_invalidation_paths'], 'The queue must drain after a submitted purge.' );

$failing_client          = new Hectv_CloudFront_Fake_Client();
$failing_client->failure = new RuntimeException( 'denied-for-test' );
$filters['hectv_cloudfront_invalidation_client'] = array(
	10 => array(
		array(
			'callback'      => function () use ( $failing_client ) {
				return $failing_client;
			},
			'accepted_args' => 1,
		),
	),
);

hectv_cloudfront_queue_global_invalidation();
expect_same( false, hectv_cloudfront_flush_invalidation(), 'CloudFront errors must be contained instead of breaking editor saves.' );
expect_same( array(), $GLOBALS['hectv_cloudfront_invalidation_paths'], 'A failed request must still drain the in-memory queue.' );

putenv( 'HECTV_CLOUDFRONT_DISTRIBUTION_ID=wrong' );
hectv_cloudfront_queue_global_invalidation();
expect_same( array(), $GLOBALS['hectv_cloudfront_invalidation_paths'], 'An unexpected distribution ID must fail closed.' );

echo "HEC CloudFront invalidation contracts passed.\n";
