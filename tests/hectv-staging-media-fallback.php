<?php
/**
 * Focused contracts for the staging-only cloned-media fallback.
 * Run: php tests/hectv-staging-media-fallback.php
 */

define( 'ABSPATH', __DIR__ );
putenv( 'HECTV_ENVIRONMENT=staging' );

$filters             = array();
$attachment_files    = array();
$test_upload_basedir = '/definitely-missing-hectv-staging-uploads';

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

expect_true( isset( $filters['wp_get_attachment_url'][20][0] ), 'Attachment URL filter should register in staging.' );
$callback = $filters['wp_get_attachment_url'][20][0]['callback'];
expect_same( 2, $filters['wp_get_attachment_url'][20][0]['accepted_args'], 'Attachment URL filter must receive the attachment ID.' );

$original_url        = 'https://staging-wp.hectv.org/wp-content/uploads/2026/07/example.jpg';
$attachment_files[1] = '2026/07/Sweeney Todd #1.jpg';
expect_same(
	'https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/07/Sweeney%20Todd%20%231.jpg',
	$callback( $original_url, 1 ),
	'Missing cloned media should use the encoded public production-media URL.'
);

$test_upload_basedir = '/';
$attachment_files[2] = 'etc/hosts';
expect_same( $original_url, $callback( $original_url, 2 ), 'Existing staging files must keep their staging URL.' );

$test_upload_basedir = '/definitely-missing-hectv-staging-uploads';
$attachment_files[3] = '../etc/passwd';
expect_same( $original_url, $callback( $original_url, 3 ), 'Unsafe relative paths must not be rewritten.' );

$attachment_files[4] = '';
expect_same( $original_url, $callback( $original_url, 4 ), 'Attachments without a file path must remain unchanged.' );

$source = file_get_contents( dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-staging-media-fallback.php' );
expect_true( strpos( $source, "getenv( 'HECTV_ENVIRONMENT' ) !== 'staging'" ) !== false, 'Production environment gate must remain fail-closed.' );
expect_true( strpos( $source, 'is_file( $local_path )' ) !== false, 'Local staging media must take precedence.' );
expect_true( strpos( $source, 'prd-hectv-wp-media.s3.us-east-2.amazonaws.com' ) !== false, 'Fallback must use the public media bucket.' );

echo "HEC staging media fallback contracts passed.\n";
