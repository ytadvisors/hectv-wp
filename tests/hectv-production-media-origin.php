<?php
/**
 * Focused contracts for canonical production media URLs.
 * Run: php tests/hectv-production-media-origin.php
 */

define( 'ABSPATH', __DIR__ );
putenv( 'HECTV_ENVIRONMENT=production' );

$filters             = array();
$attachment_files    = array();
$attachment_images   = array();
$attachment_srcsets  = array();
$attachment_sizes    = array();
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

function wp_get_attachment_image_src( $attachment_id, $size ) {
	global $attachment_images;
	return isset( $attachment_images[ $attachment_id ] ) ? $attachment_images[ $attachment_id ] : false;
}

function wp_get_attachment_image_srcset( $attachment_id, $size ) {
	global $attachment_srcsets;
	return isset( $attachment_srcsets[ $attachment_id ] ) ? $attachment_srcsets[ $attachment_id ] : false;
}

function wp_get_attachment_image_sizes( $attachment_id, $size ) {
	global $attachment_sizes;
	return isset( $attachment_sizes[ $attachment_id ] ) ? $attachment_sizes[ $attachment_id ] : false;
}

function esc_url( $url ) {
	return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
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
expect_true( isset( $filters['content_edit_pre'][20][0] ), 'Editor content must use the public media origin.' );
expect_true( isset( $filters['content_save_pre'][20][0] ), 'Edited content must persist the public media origin.' );
expect_true( isset( $filters['the_content'][20][0] ), 'Public rendered content must use the public media origin.' );
expect_true( isset( $filters['rest_prepare_post'][20][0] ), 'Post REST responses must repair legacy editor image URLs.' );
expect_true( isset( $filters['rest_prepare_page'][20][0] ), 'Page REST responses must repair legacy editor image URLs.' );

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

$offload_subdomain_url = 'https://prd-hectv-wp-media.s3-us-east-2.amazonaws.com/wp-content/uploads/2026/08/11055800/The-Worlds-Fair.jpg';
expect_same(
	$offload_subdomain_url,
	$callback( $offload_subdomain_url, 4 ),
	'Production must preserve WP Offload Media regional subdomain URLs.'
);
expect_same(
	array( $offload_subdomain_url, 1920, 1080, false ),
	$image_callback( array( $offload_subdomain_url, 1920, 1080, false ), 4, 'full', false ),
	'Production image sources must preserve WP Offload Media regional subdomain URLs.'
);

$untrusted_bucket_url = 'https://other-bucket.s3-us-east-2.amazonaws.com/wp-content/uploads/2026/08/11055800/The-Worlds-Fair.jpg';
expect_same( false, hectv_staging_media_is_public_url( $untrusted_bucket_url ), 'Production must reject S3 URLs outside the approved media bucket.' );
expect_same(
	'https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/08/The-Worlds-Fair.jpg',
	$callback( $untrusted_bucket_url, 4 ),
	'Production must not preserve an untrusted S3 bucket URL.'
);

$attachment_files[2] = '../etc/passwd';
expect_same( $origin_url, $callback( $origin_url, 2 ), 'Unsafe relative paths must not be rewritten.' );

$attachment_files[3] = '';
expect_same( $origin_url, $callback( $origin_url, 3 ), 'Attachments without a file path must remain unchanged.' );
expect_same( false, $image_callback( false, 3, 'thumbnail', false ), 'Missing image source data must remain unchanged.' );

$legacy_content = '<figure><img src="https://prod-wp.hectv.org/wp-content/uploads/2026/03/The-Who.jpg" srcset="http://prod-wp-ecs.hectv.org/wp-content/uploads/2026/03/The-Who-300x169.jpg 300w"><a href="https://example.org/wp-content/uploads/keep.jpg">Keep</a></figure>';
$rewritten_content = hectv_public_media_rewrite_content( $legacy_content );
expect_true(
	strpos( $rewritten_content, 'https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/03/The-Who.jpg' ) !== false,
	'Legacy image src must use the public media bucket in the editor.'
);
expect_true(
	strpos( $rewritten_content, 'https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2026/03/The-Who-300x169.jpg 300w' ) !== false,
	'Legacy srcset candidates must use the public media bucket in the editor.'
);
expect_true(
	strpos( $rewritten_content, 'https://example.org/wp-content/uploads/keep.jpg' ) !== false,
	'Unapproved origins must remain unchanged.'
);

$attachment_images[68596]  = array( 'https://s3-us-east-2.amazonaws.com/prd-hectv-wp-media/wp-content/uploads/2024/08/21091638/Dig-Production-1-scaled-e1787597477349-1024x577.jpg', 1024, 577, true );
$attachment_srcsets[68596] = 'https://s3-us-east-2.amazonaws.com/prd-hectv-wp-media/wp-content/uploads/2024/08/21091638/Dig-Production-1-scaled-e1787597477349-300x169.jpg 300w, https://s3-us-east-2.amazonaws.com/prd-hectv-wp-media/wp-content/uploads/2024/08/21091638/Dig-Production-1-scaled-e1787597477349-1024x577.jpg 1024w';
$attachment_sizes[68596]   = '(max-width: 1024px) 100vw, 1024px';
$stale_attachment_content  = '<figure><img decoding="async" data-id="68596" src="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2024/08/Dig-Production-1-1024x768.jpg" class="wp-image-68596"></figure>';
$repaired_attachment       = hectv_public_media_rewrite_content( $stale_attachment_content );

expect_true(
	strpos( $repaired_attachment, $attachment_images[68596][0] ) !== false,
	'Rendered attachment src must use the current healthy media derivative.'
);
expect_true(
	strpos( $repaired_attachment, 'width="1024"' ) !== false && strpos( $repaired_attachment, 'height="577"' ) !== false,
	'Rendered attachment dimensions must match current media metadata.'
);
expect_true(
	strpos( $repaired_attachment, esc_attr( $attachment_srcsets[68596] ) ) !== false,
	'Rendered attachment srcset must use current responsive media metadata.'
);
expect_true(
	strpos( $repaired_attachment, esc_attr( $attachment_sizes[68596] ) ) !== false,
	'Rendered attachment sizes must accompany the responsive source set.'
);
expect_same(
	$repaired_attachment,
	hectv_public_media_rewrite_content( $repaired_attachment ),
	'Attachment-aware content repair must be idempotent.'
);

$external_image = '<img src="https://images.example.org/external.jpg" alt="External">';
expect_same(
	$external_image,
	hectv_public_media_rewrite_content( $external_image ),
	'External images without a WordPress attachment ID must remain unchanged.'
);

$legacy_image_block = '<!-- wp:image {"id":64780,"sizeSlug":"large","linkDestination":"custom"} -->' . "\n"
	. '<figure class="wp-block-image size-large"><a href="https://www.youtube.com/@spotlightstl"><img src="https://prod-wp.hectv.org/wp-content/uploads/2025/07/Spotlight-STL-Banner-1024x169.png" alt="" /></a><figcaption>Public caption text</figcaption></figure>' . "\n"
	. '<!-- /wp:image -->';
$prepared_legacy_block = hectv_public_media_prepare_editor_content( $legacy_image_block );
expect_true(
	strpos( $prepared_legacy_block, 'class="wp-image-64780"' ) !== false,
	'Legacy core/image markup must receive the attachment class expected by Gutenberg.'
);
expect_true(
	strpos( $prepared_legacy_block, '<figcaption class="wp-element-caption">Public caption text</figcaption>' ) !== false,
	'Legacy image captions must receive the class expected by Gutenberg.'
);
expect_true(
	strpos( $prepared_legacy_block, 'prd-hectv-wp-media.s3.us-east-2.amazonaws.com' ) !== false,
	'Editor content must continue to receive the canonical public media origin.'
);
expect_true(
	strpos( $prepared_legacy_block, ' width=' ) === false && strpos( $prepared_legacy_block, ' srcset=' ) === false && strpos( $prepared_legacy_block, ' sizes=' ) === false,
	'Editor content must not receive rendered-only responsive attributes.'
);
expect_same(
	$prepared_legacy_block,
	hectv_public_media_prepare_editor_content( $prepared_legacy_block ),
	'Legacy Gutenberg normalization must be idempotent.'
);

$current_image_block = '<!-- wp:image {"id":68596,"sizeSlug":"large"} -->' . "\n"
	. '<figure class="wp-block-image size-large"><img src="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2024/08/Dig-Production-1-1024x768.jpg" alt="" class="wp-image-68596"/><figcaption class="wp-element-caption">Keep every word</figcaption></figure>' . "\n"
	. '<!-- /wp:image -->';
$prepared_current_block = hectv_public_media_prepare_editor_content( $current_image_block );
expect_same(
	$current_image_block,
	$prepared_current_block,
	'Valid Gutenberg image blocks must remain byte-for-byte unchanged in the editor.'
);

$persisted_rendered_block = '<!-- wp:image {"id":68596,"sizeSlug":"large"} -->' . "\n"
	. '<figure class="wp-block-image size-large"><img src="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2024/08/Dig-Production-1-1024x768.jpg" alt="" class="wp-image-68596" width="1024" height="577" srcset="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2024/08/Dig-Production-1-300x169.jpg 300w, https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2024/08/Dig-Production-1-1024x577.jpg 1024w" sizes="(max-width: 1024px) 100vw, 1024px"/><figcaption class="wp-element-caption">Keep every word</figcaption></figure>' . "\n"
	. '<!-- /wp:image -->';
expect_same(
	$current_image_block,
	hectv_public_media_prepare_editor_content( $persisted_rendered_block ),
	'Editor content must remove the complete rendered-attribute quartet persisted by plugin version 1.3.'
);

$persisted_legacy_resize = '<!-- wp:image {"id":64676,"sizeSlug":"large","linkDestination":"custom","align":"center"} -->' . "\n"
	. '<figure class="wp-block-image aligncenter size-large is-resized"><a href="https://youtube.com/@aspireandcelebrate"><img src="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-1024x169.png" alt="" class="wp-image-64676" style="width:840px;height:138px" width="1024" height="169" srcset="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-300x50.png 300w, https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-1024x169.png 1024w" sizes="(max-width: 1024px) 100vw, 1024px"/></a></figure>' . "\n"
	. '<!-- /wp:image -->';
$expected_legacy_resize = '<!-- wp:image {"id":64676,"sizeSlug":"large","linkDestination":"custom","align":"center"} -->' . "\n"
	. '<figure class="wp-block-image aligncenter size-large is-resized"><a href="https://youtube.com/@aspireandcelebrate"><img src="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-1024x169.png" alt="" class="wp-image-64676"/></a></figure>' . "\n"
	. '<!-- /wp:image -->';
expect_same(
	$expected_legacy_resize,
	hectv_public_media_prepare_editor_content( $persisted_legacy_resize ),
	'Legacy inline dimensions must be removed when the block comment does not serialize a current resize.'
);

$current_resized_block = '<!-- wp:image {"id":64676,"width":"840px","height":"138px","sizeSlug":"large"} -->' . "\n"
	. '<figure class="wp-block-image size-large is-resized"><img src="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-1024x169.png" alt="" class="wp-image-64676" style="width:840px;height:138px" width="1024" height="169" srcset="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-300x50.png 300w, https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-1024x169.png 1024w" sizes="(max-width: 1024px) 100vw, 1024px"/></figure>' . "\n"
	. '<!-- /wp:image -->';
$expected_current_resize = '<!-- wp:image {"id":64676,"width":"840px","height":"138px","sizeSlug":"large"} -->' . "\n"
	. '<figure class="wp-block-image size-large is-resized"><img src="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-1024x169.png" alt="" class="wp-image-64676" style="width:840px;height:138px"/></figure>' . "\n"
	. '<!-- /wp:image -->';
expect_same(
	$expected_current_resize,
	hectv_public_media_prepare_editor_content( $current_resized_block ),
	'Current block-comment dimensions must preserve the serializer-owned inline resize while rendered attributes are removed.'
);

$current_width_only_block = '<!-- wp:image {"id":64676,"width":"840px","sizeSlug":"large"} -->' . "\n"
	. '<figure class="wp-block-image size-large is-resized"><img src="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-1024x169.png" alt="" class="wp-image-64676" style="width:840px;height:138px;object-fit:cover" width="1024" height="169" srcset="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-300x50.png 300w, https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-1024x169.png 1024w" sizes="(max-width: 1024px) 100vw, 1024px"/></figure>' . "\n"
	. '<!-- /wp:image -->';
$expected_width_only_block = '<!-- wp:image {"id":64676,"width":"840px","sizeSlug":"large"} -->' . "\n"
	. '<figure class="wp-block-image size-large is-resized"><img src="https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/2025/07/Aspire-Banner-Image-1024x169.png" alt="" class="wp-image-64676" style="width:840px;object-fit:cover"/></figure>' . "\n"
	. '<!-- /wp:image -->';
expect_same(
	$expected_width_only_block,
	hectv_public_media_prepare_editor_content( $current_width_only_block ),
	'One serializer-owned dimension and unrelated inline styles must remain while only the stale dimension is removed.'
);

$partial_image_attributes = '<!-- wp:image {"id":68596,"sizeSlug":"large"} -->' . "\n"
	. '<figure class="wp-block-image size-large"><img src="https://example.org/image.jpg" class="wp-image-68596" width="640" height="360"/></figure>' . "\n"
	. '<!-- /wp:image -->';
expect_same(
	$partial_image_attributes,
	hectv_public_media_prepare_editor_content( $partial_image_attributes ),
	'Partial author-provided dimensions without the plugin responsive signature must remain unchanged.'
);
expect_same(
	$expected_legacy_resize,
	hectv_public_media_prepare_editor_content( hectv_public_media_prepare_editor_content( $persisted_legacy_resize ) ),
	'Persisted rendered-attribute cleanup must be idempotent.'
);
expect_true(
	strpos( hectv_public_media_rewrite_content( $current_image_block ), 'width="1024"' ) !== false,
	'Rendered content must retain attachment-aware dimensions.'
);

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

$rest_response = new Hectv_Test_REST_Response(
	array(
		'content' => array(
			'raw'      => $current_image_block,
			'rendered' => $current_image_block,
		),
	)
);
hectv_public_media_rewrite_rest_content( $rest_response );
$rest_content = $rest_response->get_data();
expect_same(
	$current_image_block,
	$rest_content['content']['raw'],
	'Gutenberg content.raw must not receive rendered-only attachment attributes.'
);
expect_true(
	strpos( $rest_content['content']['rendered'], 'width="1024"' ) !== false && strpos( $rest_content['content']['rendered'], 'srcset=' ) !== false,
	'REST rendered content must receive current attachment dimensions and responsive sources.'
);

echo "HEC production media origin contracts passed.\n";
