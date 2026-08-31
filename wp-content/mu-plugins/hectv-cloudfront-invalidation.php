<?php
/**
 * Plugin Name: HEC Media CloudFront invalidation
 * Description: Purges the public HEC Media edge cache after published content changes.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $GLOBALS['hectv_cloudfront_invalidation_paths'] ) ) {
	$GLOBALS['hectv_cloudfront_invalidation_paths'] = array();
}

/**
 * Return the one production distribution configured on the ECS task.
 *
 * @return string
 */
function hectv_cloudfront_distribution_id() {
	$value = getenv( 'HECTV_CLOUDFRONT_DISTRIBUTION_ID' );
	return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Invalidation is deliberately disabled outside the production ECS runtime.
 *
 * @return bool
 */
function hectv_cloudfront_invalidation_enabled() {
	$environment     = getenv( 'HECTV_ENVIRONMENT' );
	$distribution_id = hectv_cloudfront_distribution_id();

	return 'production' === $environment
		&& 1 === preg_match( '/^E[A-Z0-9]{10,20}$/', $distribution_id );
}

/**
 * Queue one global invalidation for the current request.
 *
 * CloudFront counts a trailing-wildcard path as one invalidation path. Using
 * /* also clears cached query-string variants and Next.js data responses, so
 * the editor cannot update an article while leaving its home/archive cards
 * stale. Multiple WordPress hooks in one request are coalesced at shutdown.
 *
 * @return void
 */
function hectv_cloudfront_queue_global_invalidation() {
	if ( ! hectv_cloudfront_invalidation_enabled() ) {
		return;
	}

	$GLOBALS['hectv_cloudfront_invalidation_paths']['/*'] = true;
}

/**
 * Queue invalidation when published content changes or leaves publication.
 *
 * @param string   $new_status New WordPress post status.
 * @param string   $old_status Previous WordPress post status.
 * @param \WP_Post $post       Saved post object.
 * @return void
 */
function hectv_cloudfront_queue_post_invalidation( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
		return;
	}

	if ( ! is_object( $post ) || empty( $post->ID ) ) {
		return;
	}

	if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post->ID ) ) {
		return;
	}

	if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post->ID ) ) {
		return;
	}

	hectv_cloudfront_queue_global_invalidation();
}

/**
 * Queue invalidation for global ACF options, which feed the public site shell.
 *
 * @param mixed $post_id ACF save identifier.
 * @return void
 */
function hectv_cloudfront_queue_acf_options_invalidation( $post_id ) {
	if ( 'options' === $post_id || 'option' === $post_id ) {
		hectv_cloudfront_queue_global_invalidation();
	}
}

/**
 * Register invalidation hooks for the exact Settings API options rendered on
 * the public frontend. The CMS fields plugin loads after this MU plugin, so
 * these stable option names are intentionally used without relying on its
 * constants being defined yet.
 *
 * @return void
 */
function hectv_cloudfront_register_public_option_hooks() {
	$option_names = array(
		'hectv_trending_max_videos',
		'hectv_trending_title',
		'hectv_spotlight_title',
		'hectv_mobile_display',
		'hectv_newsletter_captcha_enabled',
		'hectv_educators_logo_id',
		'hectv_educators_url',
		'hectv_educators_label',
	);

	foreach ( $option_names as $option_name ) {
		add_action( 'add_option_' . $option_name, 'hectv_cloudfront_queue_global_invalidation', 99, 0 );
		add_action( 'update_option_' . $option_name, 'hectv_cloudfront_queue_global_invalidation', 99, 0 );
	}
}

/**
 * Load the existing scoped SDK and return a CloudFront client.
 *
 * The ECS task-role provider is explicit. The production container still has
 * legacy AWS_* environment variables for media compatibility; those must
 * never be used for CloudFront mutations.
 *
 * Tests can inject a fake with the hectv_cloudfront_invalidation_client
 * filter without loading the SDK.
 *
 * @return object
 * @throws \RuntimeException When the production SDK or task credentials are unavailable.
 */
function hectv_cloudfront_invalidation_client() {
	$filtered = apply_filters( 'hectv_cloudfront_invalidation_client', null );
	if ( is_object( $filtered ) ) {
		return $filtered;
	}

	if ( ! getenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' ) ) {
		throw new \RuntimeException( 'ECS task-role credentials are unavailable.' );
	}

	$sdk_root   = dirname( __DIR__ ) . '/plugins/amazon-s3-and-cloudfront/vendor/Aws3';
	$autoloader = $sdk_root . '/aws-autoloader.php';
	$compat     = __DIR__ . '/hectv-cloudfront-invalidation/aws-cloudfront-client.php';

	if ( ! is_readable( $autoloader ) || ! is_readable( $compat ) ) {
		throw new \RuntimeException( 'The bundled CloudFront client is unavailable.' );
	}

	require_once $autoloader;

	$client_class = 'DeliciousBrains\\WP_Offload_Media\\Aws3\\Aws\\CloudFront\\CloudFrontClient';
	if ( ! class_exists( $client_class, false ) ) {
		require_once $compat;
	}

	$provider_class = 'DeliciousBrains\\WP_Offload_Media\\Aws3\\Aws\\Credentials\\CredentialProvider';
	$credentials    = call_user_func( array( $provider_class, 'ecsCredentials' ), array( 'timeout' => 1.0 ) );

	return new $client_class(
		array(
			'version'     => '2017-10-30',
			'region'      => 'us-east-1',
			'credentials' => $credentials,
			'http'        => array(
				'connect_timeout' => 1.0,
				'timeout'         => 4.0,
			),
		)
	);
}

/**
 * Return a unique, bounded CloudFront caller reference.
 *
 * @return string
 */
function hectv_cloudfront_caller_reference() {
	if ( function_exists( 'wp_generate_uuid4' ) ) {
		$entropy = wp_generate_uuid4();
	} else {
		$entropy = bin2hex( random_bytes( 8 ) );
	}

	return 'hectv-publish-' . gmdate( 'Ymd-His' ) . '-' . $entropy;
}

/**
 * Submit all paths accumulated during the WordPress request.
 *
 * Failures are logged but never break an editor save. The five-minute cache
 * lifetime remains the bounded fallback if CloudFront cannot accept a purge.
 *
 * @return bool True when no work was needed or CloudFront accepted the request.
 */
function hectv_cloudfront_flush_invalidation() {
	$paths = array_keys( $GLOBALS['hectv_cloudfront_invalidation_paths'] );
	$GLOBALS['hectv_cloudfront_invalidation_paths'] = array();

	if ( empty( $paths ) || ! hectv_cloudfront_invalidation_enabled() ) {
		return true;
	}

	try {
		$client = hectv_cloudfront_invalidation_client();
		$client->createInvalidation(
			array(
				'DistributionId'  => hectv_cloudfront_distribution_id(),
				'InvalidationBatch' => array(
					'CallerReference' => hectv_cloudfront_caller_reference(),
					'Paths'           => array(
						'Quantity' => count( $paths ),
						'Items'    => $paths,
					),
				),
			)
		);
		return true;
	} catch ( \Throwable $exception ) {
		error_log(
			sprintf(
				'HECTV CloudFront invalidation failed (%s): %s',
				get_class( $exception ),
				$exception->getMessage()
			)
		);
		return false;
	}
}

add_action( 'transition_post_status', 'hectv_cloudfront_queue_post_invalidation', 99, 3 );
add_action( 'wp_update_nav_menu', 'hectv_cloudfront_queue_global_invalidation', 99, 0 );
add_action( 'created_term', 'hectv_cloudfront_queue_global_invalidation', 99, 0 );
add_action( 'edited_term', 'hectv_cloudfront_queue_global_invalidation', 99, 0 );
add_action( 'delete_term', 'hectv_cloudfront_queue_global_invalidation', 99, 0 );
add_action( 'acf/save_post', 'hectv_cloudfront_queue_acf_options_invalidation', 99, 1 );
add_action( 'shutdown', 'hectv_cloudfront_flush_invalidation', 99, 0 );
hectv_cloudfront_register_public_option_hooks();
