<?php
/**
 * Focused contracts for canonical production media URLs.
 * Run: php tests/hectv-production-media-origin.php
 */

define( 'ABSPATH', __DIR__ );
putenv( 'HECTV_ENVIRONMENT=production' );

$filters             = array();
$attachment_files    = array();
$test_upload_basedir = '/';

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $filters;
	$filters[ $hook ][ $priority ][] = array(
		'callback'      => $callback,
		'accepted_args' => $accepted_args,
	);
}

function get_post_meta( $post_id, $key, $single = true ) {
	global $attachment_files;
	if ( $key !== '_wp_attached_file' ) {
		return '';
	}
	return isset( $attachment_files[ $post_id ] ) ? $attachment_files[ $post_id ] : '';
}

function wp_get_upload_dir() {
	global $test_upload_basedir;
	return array( 'basedir' => $test_upload_basedir );
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

require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-staging-media-fallback.php';

expect_true( isset( $filters['wp_get_attachment_url'][20][0] ), 'Attachment URL filter should register in production.' );
expect_true( isset( $filters['wp_get_attachment_image_src'][120][0] ), 'Image source filter should register in production.' );

$callback       = $filters['wp_get_attachment_url'][20][0]['callback'];
$image_callback = $filters['wp_get_attachment_image_src'][120][0]['callback'];
$origin_url     = 'https://prod-wp.hectv.org/wp-content/uploads/2026/07/example.jpg';

$attachment_files[1] = '2026/07/Charles Calotta #1.jpg';
expect_same(
	'https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/07/Charles%20Calotta%20%231.jpg',
	$callback( $origin_url, 1 ),
	'Production attachment URLs must use the synchronized public media origin even when a local file exists.'
);
expect_same(
	array( 'https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/07/Charles%20Calotta%20%231-300x169.jpg', 300, 169, true ),
	$image_callback(
		array( 'https://prod-wp.hectv.org/wp-content/uploads/2026/07/Charles%20Calotta%20%231-300x169.jpg', 300, 169, true ),
		1,
		'medium',
		false
	),
	'Production sized image URLs must use the synchronized public media origin.'
);

$offloaded_url = 'https://s3-us-east-2.amazonaws.com/prd-hectv-wp-media/wp-content/uploads/2026/08/11055800/The-Worlds-Fair.jpg';
$attachment_files[4] = '2026/08/The-Worlds-Fair.jpg';
expect_same(
	$offloaded_url,
	$callback( $offloaded_url, 4 ),
	'Production must preserve an offload-plugin object key that contains a collision-avoidance prefix.'
);
expect_same(
	array( $offloaded_url, 1920, 1080, false ),
	$image_callback( array( $offloaded_url, 1920, 1080, false ), 4, 'full', false ),
	'Production image sources must preserve a valid offload-plugin URL instead of rebuilding it from _wp_attached_file.'
);

$virtual_hosted_url = 'https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/08/11055800/The-Worlds-Fair.jpg';
expect_same(
	$virtual_hosted_url,
	$callback( $virtual_hosted_url, 4 ),
	'Production must preserve virtual-hosted URLs for the public media bucket.'
);

$attachment_files[2] = '../etc/passwd';
expect_same( $origin_url, $callback( $origin_url, 2 ), 'Unsafe relative paths must not be rewritten.' );

$attachment_files[3] = '';
expect_same( $origin_url, $callback( $origin_url, 3 ), 'Attachments without a file path must remain unchanged.' );
expect_same( false, $image_callback( false, 3, 'thumbnail', false ), 'Missing image source data must remain unchanged.' );

echo "HEC production media origin contracts passed.\n";
