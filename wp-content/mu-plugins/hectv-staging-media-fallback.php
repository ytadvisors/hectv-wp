<?php
/**
 * Plugin Name: HEC Public Media Origin
 * Description: Returns synchronized production media URLs while preserving local-only staging uploads.
 * Version: 1.1.1
 * Author: YT Advisors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hectv_media_environment = getenv( 'HECTV_ENVIRONMENT' );
if ( ! in_array( $hectv_media_environment, array( 'staging', 'production' ), true ) ) {
	return;
}

if ( ! defined( 'HECTV_STAGING_MEDIA_FALLBACK_BASE_URL' ) ) {
	define(
		'HECTV_STAGING_MEDIA_FALLBACK_BASE_URL',
		'https://prd-hectv-wp-media.s3.us-east-2.amazonaws.com/wp-content/uploads/'
	);
}

/**
 * Encode a safe WordPress uploads-relative path for use in the fallback URL.
 *
 * @param string $relative_path Attachment `_wp_attached_file` value.
 * @return string|null
 */
function hectv_staging_media_fallback_path( $relative_path ) {
	if ( ! is_string( $relative_path ) || $relative_path === '' ) {
		return null;
	}

	$relative_path = str_replace( '\\', '/', ltrim( $relative_path, '/' ) );
	$segments      = explode( '/', $relative_path );
	$encoded       = array();

	foreach ( $segments as $segment ) {
		if ( $segment === '' || $segment === '.' || $segment === '..' ) {
			return null;
		}
		$encoded[] = rawurlencode( $segment );
	}

	return implode( '/', $encoded );
}

/**
 * Determine whether a URL already points at the production media bucket.
 *
 * WP Offload Media can store an object-key prefix that is not present in
 * `_wp_attached_file` (for example, a timestamp directory used to avoid name
 * collisions). Rebuilding that URL from core attachment metadata drops the
 * prefix and points GraphQL at an object that does not exist. Trust the
 * plugin-owned URL when it is already scoped to the production bucket.
 *
 * @param string $url Candidate attachment URL.
 * @return bool
 */
function hectv_staging_media_is_public_url( $url ) {
	if ( ! is_string( $url ) || $url === '' ) {
		return false;
	}

	$host = strtolower( (string) parse_url( $url, PHP_URL_HOST ) );
	$path = (string) parse_url( $url, PHP_URL_PATH );

	if (
		$host === 'prd-hectv-wp-media.s3.us-east-2.amazonaws.com' ||
		$host === 'prd-hectv-wp-media.s3.amazonaws.com'
	) {
		return strpos( $path, '/wp-content/uploads/' ) === 0;
	}

	return $host === 's3-us-east-2.amazonaws.com' &&
		strpos( $path, '/prd-hectv-wp-media/wp-content/uploads/' ) === 0;
}

/**
 * Return the canonical public-media object for production attachments.
 *
 * Production uploads are synchronized to the public media bucket, so GraphQL
 * and REST consumers must not depend on an individual ECS/EFS origin retaining
 * every historical object. Staging keeps a local URL only when its isolated EFS
 * contains the file; cloned attachments fall back to the public media origin.
 * This filter performs no S3 API calls and requires no production credentials.
 *
 * @param string $url           WordPress attachment URL.
 * @param int    $attachment_id Attachment post ID.
 * @return string
 */
function hectv_staging_media_fallback_url( $url, $attachment_id ) {
	if ( hectv_staging_media_is_public_url( $url ) ) {
		return $url;
	}

	$relative_path = get_post_meta( $attachment_id, '_wp_attached_file', true );
	$encoded_path  = hectv_staging_media_fallback_path( $relative_path );
	if ( $encoded_path === null ) {
		return $url;
	}

	if ( getenv( 'HECTV_ENVIRONMENT' ) === 'production' ) {
		return rtrim( HECTV_STAGING_MEDIA_FALLBACK_BASE_URL, '/' ) . '/' . $encoded_path;
	}

	$uploads = wp_get_upload_dir();
	if ( ! empty( $uploads['basedir'] ) ) {
		$local_path = rtrim( $uploads['basedir'], '/\\' ) . '/' . str_replace( '\\', '/', ltrim( $relative_path, '/\\' ) );
		if ( is_file( $local_path ) ) {
			return $url;
		}
	}

	return rtrim( HECTV_STAGING_MEDIA_FALLBACK_BASE_URL, '/' ) . '/' . $encoded_path;
}

/**
 * Rewrite image-source arrays after media plugins have finished filtering them.
 *
 * ACF renders its image-field preview with wp_get_attachment_image_src(). Some
 * media plugins filter that result after wp_get_attachment_url(), so the final
 * array needs the same missing-file fallback. Preserve the requested image-size
 * filename so WordPress can use an S3-hosted thumbnail when one is available.
 *
 * @param array|false  $image         Image source data.
 * @param int          $attachment_id Attachment post ID.
 * @param string|array $size          Requested image size.
 * @param bool         $icon          Whether an icon was requested.
 * @return array|false
 */
function hectv_staging_media_fallback_image_src( $image, $attachment_id, $size, $icon ) {
	if ( ! is_array( $image ) || empty( $image[0] ) || ! is_string( $image[0] ) ) {
		return $image;
	}

	$fallback_url = hectv_staging_media_fallback_url( $image[0], $attachment_id );
	if ( $fallback_url === $image[0] ) {
		return $image;
	}

	$relative_path = get_post_meta( $attachment_id, '_wp_attached_file', true );
	$source_path   = parse_url( $image[0], PHP_URL_PATH );
	$source_file   = is_string( $source_path ) ? rawurldecode( basename( $source_path ) ) : '';
	$relative_dir  = is_string( $relative_path ) ? dirname( str_replace( '\\', '/', $relative_path ) ) : '.';
	$size_path     = $relative_dir === '.' ? $source_file : $relative_dir . '/' . $source_file;
	$encoded_path  = hectv_staging_media_fallback_path( $size_path );

	if ( $encoded_path === null ) {
		return $image;
	}

	$image[0] = rtrim( HECTV_STAGING_MEDIA_FALLBACK_BASE_URL, '/' ) . '/' . $encoded_path;
	return $image;
}

add_filter( 'wp_get_attachment_url', 'hectv_staging_media_fallback_url', 20, 2 );
add_filter( 'wp_get_attachment_image_src', 'hectv_staging_media_fallback_image_src', 120, 4 );
