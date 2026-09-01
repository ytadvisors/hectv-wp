<?php
/**
 * Plugin Name: HEC Public Media Origin
 * Description: Returns synchronized production media URLs while preserving local-only staging uploads.
 * Version: 1.4.2
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

	$host          = strtolower( (string) parse_url( $url, PHP_URL_HOST ) );
	$path          = (string) parse_url( $url, PHP_URL_PATH );
	$bucket        = 'prd-hectv-wp-media';
	$region        = 'us-east-2';
	$virtual_hosts = array(
		"{$bucket}.s3.{$region}.amazonaws.com",
		"{$bucket}.s3-{$region}.amazonaws.com",
		"{$bucket}.s3.amazonaws.com",
	);

	if ( in_array( $host, $virtual_hosts, true ) ) {
		return strpos( $path, '/wp-content/uploads/' ) === 0;
	}

	$path_hosts = array(
		"s3-{$region}.amazonaws.com",
		"s3.{$region}.amazonaws.com",
	);

	return in_array( $host, $path_hosts, true ) &&
		strpos( $path, "/{$bucket}/wp-content/uploads/" ) === 0;
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

/**
 * Read an attachment ID from a rendered WordPress image tag.
 *
 * Gutenberg emits `data-id`, while older core image blocks expose only the
 * `wp-image-{id}` class. Only numeric IDs are accepted.
 *
 * @param string $tag Rendered img tag.
 * @return int
 */
function hectv_public_media_attachment_id_from_tag( $tag ) {
	if ( preg_match( '/\sdata-id\s*=\s*(["\'])([1-9][0-9]*)\1/i', $tag, $matches ) ) {
		return (int) $matches[2];
	}

	if ( preg_match( '/\sclass\s*=\s*(["\'])[^"\']*\bwp-image-([1-9][0-9]*)\b[^"\']*\1/i', $tag, $matches ) ) {
		return (int) $matches[2];
	}

	return 0;
}

/**
 * Set or add a quoted HTML attribute on a single tag.
 *
 * @param string $tag   HTML tag.
 * @param string $name  Attribute name.
 * @param string $value Escaped attribute value.
 * @return string
 */
function hectv_public_media_set_tag_attribute( $tag, $name, $value ) {
	$pattern = '/(\s' . preg_quote( $name, '/' ) . '\s*=\s*)(["\']).*?\2/i';
	if ( preg_match( $pattern, $tag ) ) {
		return preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $value ) {
				return $matches[1] . $matches[2] . $value . $matches[2];
			},
			$tag,
			1
		);
	}

	$closing_length = substr( $tag, -2 ) === '/>' ? 2 : 1;
	return substr( $tag, 0, -$closing_length ) . ' ' . $name . '="' . $value . '"' . substr( $tag, -$closing_length );
}

/**
 * Remove a quoted HTML attribute from a single tag.
 *
 * @param string $tag  HTML tag.
 * @param string $name Attribute name.
 * @return string
 */
function hectv_public_media_remove_tag_attribute( $tag, $name ) {
	return preg_replace( '/\s' . preg_quote( $name, '/' ) . '\s*=\s*(["\']).*?\1/i', '', $tag, 1 );
}

/**
 * Test whether a single tag contains a quoted HTML attribute.
 *
 * @param string $tag  HTML tag.
 * @param string $name Attribute name.
 * @return bool
 */
function hectv_public_media_tag_has_attribute( $tag, $name ) {
	return preg_match( '/\s' . preg_quote( $name, '/' ) . '\s*=\s*(["\']).*?\1/i', $tag ) === 1;
}

/**
 * Test whether an inline style contains one CSS property.
 *
 * @param string $tag      HTML tag.
 * @param string $property CSS property name.
 * @return bool
 */
function hectv_public_media_tag_has_inline_property( $tag, $property ) {
	if ( ! preg_match( '/\sstyle\s*=\s*(["\'])(.*?)\1/i', $tag, $matches ) ) {
		return false;
	}

	return preg_match( '/(?:^|;)\s*' . preg_quote( $property, '/' ) . '\s*:/i', $matches[2] ) === 1;
}

/**
 * Remove width/height declarations from an inline style attribute.
 *
 * Other declarations keep their original bytes. If width and height were the
 * only declarations, remove the now-empty style attribute as well.
 *
 * @param string $tag           HTML tag.
 * @param bool   $remove_width  Whether to remove the width declaration.
 * @param bool   $remove_height Whether to remove the height declaration.
 * @return string
 */
function hectv_public_media_remove_inline_dimensions( $tag, $remove_width = true, $remove_height = true ) {
	if ( ! $remove_width && ! $remove_height ) {
		return $tag;
	}

	return preg_replace_callback(
		'/(\sstyle\s*=\s*)(["\'])(.*?)\2/i',
		function ( $matches ) use ( $remove_width, $remove_height ) {
			$kept_declarations = array();
			foreach ( explode( ';', $matches[3] ) as $declaration ) {
				$is_width  = preg_match( '/^\s*width\s*:/i', $declaration ) === 1;
				$is_height = preg_match( '/^\s*height\s*:/i', $declaration ) === 1;
				if ( ( $remove_width && $is_width ) || ( $remove_height && $is_height ) ) {
					continue;
				}
				$kept_declarations[] = $declaration;
			}
			$style = implode( ';', $kept_declarations );

			if ( trim( $style, " \t\n\r\0\x0B;" ) === '' ) {
				return '';
			}

			return $matches[1] . $matches[2] . $style . $matches[2];
		},
		$tag,
		1
	);
}

/**
 * Add one CSS class to a tag without disturbing its existing classes.
 *
 * @param string $tag        HTML tag.
 * @param string $class_name Class name controlled by this plugin.
 * @return string
 */
function hectv_public_media_add_tag_class( $tag, $class_name ) {
	$pattern = '/(\sclass\s*=\s*)(["\'])(.*?)\2/i';
	if ( preg_match( $pattern, $tag ) ) {
		return preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $class_name ) {
				$classes = preg_split( '/\s+/', trim( $matches[3] ) );
				if ( in_array( $class_name, $classes, true ) ) {
					return $matches[0];
				}

				$classes[] = $class_name;
				return $matches[1] . $matches[2] . implode( ' ', array_filter( $classes ) ) . $matches[2];
			},
			$tag,
			1
		);
	}

	return hectv_public_media_set_tag_attribute( $tag, 'class', $class_name );
}

/**
 * Resolve a rendered image tag from current attachment metadata.
 *
 * Stored Gutenberg markup can retain a pre-offload filename even after WP
 * Offload Media assigns a collision-safe object key. The current attachment
 * APIs know the healthy URL, responsive candidates, and dimensions, so use
 * them whenever the tag has a valid WordPress attachment ID.
 *
 * @param string $tag Rendered img tag.
 * @return string
 */
function hectv_public_media_rewrite_attachment_image_tag( $tag ) {
	$attachment_id = hectv_public_media_attachment_id_from_tag( $tag );
	if ( $attachment_id === 0 || ! function_exists( 'wp_get_attachment_image_src' ) ) {
		return $tag;
	}

	$image = wp_get_attachment_image_src( $attachment_id, 'large' );
	if ( ! is_array( $image ) || empty( $image[0] ) || ! is_string( $image[0] ) ) {
		return $tag;
	}

	$tag = hectv_public_media_set_tag_attribute( $tag, 'src', esc_url( $image[0] ) );
	if ( ! empty( $image[1] ) ) {
		$tag = hectv_public_media_set_tag_attribute( $tag, 'width', (string) (int) $image[1] );
	}
	if ( ! empty( $image[2] ) ) {
		$tag = hectv_public_media_set_tag_attribute( $tag, 'height', (string) (int) $image[2] );
	}

	$srcset = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $attachment_id, 'large' ) : false;
	if ( is_string( $srcset ) && $srcset !== '' ) {
		$tag   = hectv_public_media_set_tag_attribute( $tag, 'srcset', esc_attr( $srcset ) );
		$sizes = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $attachment_id, 'large' ) : false;
		if ( is_string( $sizes ) && $sizes !== '' ) {
			$tag = hectv_public_media_set_tag_attribute( $tag, 'sizes', esc_attr( $sizes ) );
		}
	} else {
		$tag = hectv_public_media_remove_tag_attribute( $tag, 'srcset' );
		$tag = hectv_public_media_remove_tag_attribute( $tag, 'sizes' );
	}

	return $tag;
}

/**
 * Rewrite legacy WordPress upload origins without changing block structure.
 *
 * Historical blocks keep their original `src` and `srcset` strings instead of
 * resolving them through the attachment URL filters above. Those ECS/origin
 * URLs can 404 even though the synchronized object exists in the public media
 * bucket, which makes images disappear in the block editor. Keep this rewrite
 * host-and-path scoped so links outside the approved uploads origins pass
 * through unchanged.
 *
 * @param mixed $content Post content.
 * @return mixed
 */
function hectv_public_media_rewrite_legacy_origins( $content ) {
	if ( ! is_string( $content ) || $content === '' ) {
		return $content;
	}

	return preg_replace(
		'#https?://(?:staging-wp|prod-wp|prod-wp-ecs)\.hectv\.org/wp-content/uploads/#i',
		rtrim( HECTV_STAGING_MEDIA_FALLBACK_BASE_URL, '/' ) . '/',
		$content
	);
}

/**
 * Restore the two classes required by the current core/image serializer.
 *
 * HEC has image blocks saved by an older editor that declared an attachment
 * ID in the block comment but omitted `wp-image-{id}` from the image and
 * `wp-element-caption` from its caption. Current Gutenberg treats those blocks
 * as invalid even though their text, links, and images still render publicly.
 * Limit the compatibility repair to a core/image block with a positive ID and
 * leave every other block and tag byte-for-byte unchanged.
 *
 * @param mixed $content Post content.
 * @return mixed
 */
function hectv_public_media_normalize_legacy_image_blocks( $content ) {
	if ( ! is_string( $content ) || $content === '' ) {
		return $content;
	}

	return preg_replace_callback(
		'#(<!--\s+wp:image(?:\s+(\{.*?\}))?\s+-->)(.*?)(<!--\s+/wp:image\s+-->)#is',
		function ( $matches ) {
			$attributes    = isset( $matches[2] ) ? json_decode( $matches[2], true ) : null;
			$attachment_id = is_array( $attributes ) && isset( $attributes['id'] ) ? $attributes['id'] : null;
			if ( ! is_int( $attachment_id ) || $attachment_id <= 0 ) {
				return $matches[0];
			}

			$inner_html = preg_replace_callback(
				'/<img\b[^>]*>/i',
				function ( $image_matches ) use ( $attachment_id ) {
					return hectv_public_media_add_tag_class( $image_matches[0], 'wp-image-' . $attachment_id );
				},
				$matches[3],
				1
			);

			$inner_html = preg_replace_callback(
				'/<figcaption\b[^>]*>/i',
				function ( $caption_matches ) {
					return hectv_public_media_add_tag_class( $caption_matches[0], 'wp-element-caption' );
				},
				$inner_html,
				1
			);

			return $matches[1] . $inner_html . $matches[4];
		},
		$content
	);
}

/**
 * Remove rendered-only attachment attributes persisted by plugin version 1.3.
 *
 * Version 1.3 applied the rendered attachment repair to editor raw content and
 * to content_save_pre. Pages saved during that window can therefore contain a
 * width/height/srcset/sizes quartet that core/image never serializes. Strip
 * only that complete plugin signature, only from the matching attachment image
 * inside a core/image block. An earlier cleanup pass can leave the old inline
 * dimensions behind after removing the quartet, so also remove that orphaned
 * pair when an is-resized figure has no serializer-owned dimensions in its
 * block comment. A legitimate current resize is represented by block-comment
 * width/height attributes and an inline style; preserve that style. Retain
 * every unrelated inline declaration.
 *
 * @param mixed $content Post content.
 * @return mixed
 */
function hectv_public_media_remove_persisted_rendered_image_attributes( $content ) {
	if ( ! is_string( $content ) || $content === '' ) {
		return $content;
	}

	return preg_replace_callback(
		'#(<!--\s+wp:image(?:\s+(\{.*?\}))?\s+-->)(.*?)(<!--\s+/wp:image\s+-->)#is',
		function ( $matches ) {
			$attributes    = isset( $matches[2] ) ? json_decode( $matches[2], true ) : null;
			$attachment_id = is_array( $attributes ) && isset( $attributes['id'] ) ? $attributes['id'] : null;
			if ( ! is_int( $attachment_id ) || $attachment_id <= 0 ) {
				return $matches[0];
			}

			$has_serialized_width  = isset( $attributes['width'] ) && $attributes['width'] !== '';
			$has_serialized_height = isset( $attributes['height'] ) && $attributes['height'] !== '';
			$has_resized_figure     = preg_match(
				'/<figure\b[^>]*\sclass\s*=\s*(["\'])[^"\']*\bis-resized\b[^"\']*\1/i',
				$matches[3]
			) === 1;
			$inner_html            = preg_replace_callback(
				'/<img\b[^>]*>/i',
				function ( $image_matches ) use ( $attachment_id, $has_serialized_width, $has_serialized_height, $has_resized_figure ) {
					$tag = $image_matches[0];
					if ( hectv_public_media_attachment_id_from_tag( $tag ) !== $attachment_id ) {
						return $tag;
					}

					$has_complete_rendered_quartet = true;
					foreach ( array( 'width', 'height', 'srcset', 'sizes' ) as $attribute_name ) {
						if ( ! hectv_public_media_tag_has_attribute( $tag, $attribute_name ) ) {
							$has_complete_rendered_quartet = false;
							break;
						}
					}

					if ( $has_complete_rendered_quartet ) {
						foreach ( array( 'width', 'height', 'srcset', 'sizes' ) as $attribute_name ) {
							$tag = hectv_public_media_remove_tag_attribute( $tag, $attribute_name );
						}

						return hectv_public_media_remove_inline_dimensions( $tag, ! $has_serialized_width, ! $has_serialized_height );
					}

					$has_orphaned_dimensions = $has_resized_figure &&
						! $has_serialized_width &&
						! $has_serialized_height &&
						hectv_public_media_tag_has_inline_property( $tag, 'width' ) &&
						hectv_public_media_tag_has_inline_property( $tag, 'height' );

					return $has_orphaned_dimensions ? hectv_public_media_remove_inline_dimensions( $tag ) : $tag;
				},
				$matches[3],
				1
			);

			return $matches[1] . $inner_html . $matches[4];
		},
		$content
	);
}

/**
 * Prepare raw block markup for Gutenberg without injecting rendered attributes.
 *
 * Responsive width, height, srcset, and sizes attributes are valid in rendered
 * HTML but are not emitted by the core/image save function. Adding them to
 * `content.raw` makes Gutenberg reject an otherwise valid block. The editor
 * path therefore rewrites only approved origins and the two legacy classes.
 *
 * @param mixed $content Post content.
 * @return mixed
 */
function hectv_public_media_prepare_editor_content( $content ) {
	$content = hectv_public_media_rewrite_legacy_origins( $content );
	$content = hectv_public_media_remove_persisted_rendered_image_attributes( $content );
	return hectv_public_media_normalize_legacy_image_blocks( $content );
}

/**
 * Rewrite rendered content using current attachment metadata.
 *
 * @param mixed $content Post content.
 * @return mixed
 */
function hectv_public_media_rewrite_content( $content ) {
	$content = hectv_public_media_rewrite_legacy_origins( $content );
	if ( ! is_string( $content ) || $content === '' ) {
		return $content;
	}

	return preg_replace_callback(
		'/<img\b[^>]*>/i',
		function ( $matches ) {
			return hectv_public_media_rewrite_attachment_image_tag( $matches[0] );
		},
		$content
	);
}

/**
 * Ensure Gutenberg receives canonical media URLs for existing raw content.
 *
 * The editor consumes `content.raw` from the REST response. Rewriting only
 * `the_content` fixes public rendering but leaves the editing canvas broken.
 * `content_save_pre` then persists the canonical URLs on the next real edit.
 *
 * @param mixed $response REST response object.
 * @return mixed
 */
function hectv_public_media_rewrite_rest_content( $response ) {
	if ( ! is_object( $response ) || ! method_exists( $response, 'get_data' ) || ! method_exists( $response, 'set_data' ) ) {
		return $response;
	}

	$data = $response->get_data();
	if ( ! is_array( $data ) || empty( $data['content'] ) || ! is_array( $data['content'] ) ) {
		return $response;
	}

	if ( isset( $data['content']['raw'] ) ) {
		$data['content']['raw'] = hectv_public_media_prepare_editor_content( $data['content']['raw'] );
	}
	if ( isset( $data['content']['rendered'] ) ) {
		$data['content']['rendered'] = hectv_public_media_rewrite_content( $data['content']['rendered'] );
	}

	$response->set_data( $data );
	return $response;
}

add_filter( 'wp_get_attachment_url', 'hectv_staging_media_fallback_url', 20, 2 );
add_filter( 'wp_get_attachment_image_src', 'hectv_staging_media_fallback_image_src', 120, 4 );
add_filter( 'content_edit_pre', 'hectv_public_media_prepare_editor_content', 20, 1 );
add_filter( 'content_save_pre', 'hectv_public_media_prepare_editor_content', 20, 1 );
add_filter( 'the_content', 'hectv_public_media_rewrite_content', 20, 1 );
add_filter( 'rest_prepare_post', 'hectv_public_media_rewrite_rest_content', 20, 3 );
add_filter( 'rest_prepare_page', 'hectv_public_media_rewrite_rest_content', 20, 3 );
