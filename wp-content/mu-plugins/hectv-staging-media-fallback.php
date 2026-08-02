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

add_filter( 'wp_get_attachment_url', 'hectv_staging_media_fallback_url', 20, 2 );
