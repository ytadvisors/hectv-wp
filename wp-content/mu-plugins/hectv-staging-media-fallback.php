<?php
/**
 * Plugin Name: HEC Staging Media Fallback
 * Description: Serves cloned attachments from the public production media bucket when their isolated staging-EFS copy is missing.
 * Version: 1.0.0
 * Author: YT Advisors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This compatibility bridge must never alter production attachment URLs.
if ( getenv( 'HECTV_ENVIRONMENT' ) !== 'staging' ) {
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
 * Use the public production-media object only when staging has no local copy.
 *
 * New files uploaded during staging review remain on isolated staging EFS and
 * keep their staging URL. The fallback performs no S3 API calls and requires
 * no production credentials.
 *
 * @param string $url           WordPress attachment URL.
 * @param int    $attachment_id Attachment post ID.
 * @return string
 */
function hectv_staging_media_fallback_url( $url, $attachment_id ) {
	$relative_path = get_post_meta( $attachment_id, '_wp_attached_file', true );
	$encoded_path  = hectv_staging_media_fallback_path( $relative_path );
	if ( $encoded_path === null ) {
		return $url;
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
