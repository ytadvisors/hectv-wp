<?php
/**
 * Plugin Name: HEC YouTube Editor Identity
 * Description: Keeps YouTube oEmbed previews identifiable inside the WordPress editor canvas.
 * Version: 1.0.0
 * Author: YT Advisors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'HECTV_PUBLIC_SITE_URL' ) ) {
	define( 'HECTV_PUBLIC_SITE_URL', 'https://hecmedia.org/' );
}

/**
 * Add the client identity required by current YouTube embedded players.
 *
 * Gutenberg renders provider HTML inside a nested editor canvas. Some browser
 * and editor combinations suppress the iframe request referrer, which YouTube
 * reports as player error 153. YouTube recommends strict-origin-when-cross-
 * origin and widget_referrer for nested embeds. Keep the change scoped to
 * YouTube iframe tags so all other oEmbed providers remain byte-for-byte
 * unchanged.
 *
 * @param mixed $html Provider HTML.
 * @return mixed
 */
function hectv_youtube_add_editor_identity( $html ) {
	if ( ! is_string( $html ) || $html === '' || stripos( $html, '<iframe' ) === false ) {
		return $html;
	}

	return preg_replace_callback(
		'#<iframe\b[^>]*>#i',
		function ( $matches ) {
			$iframe = $matches[0];
			if ( ! preg_match( '#\bsrc=(["\'])([^"\']+)\1#i', $iframe, $src_match ) ) {
				return $iframe;
			}

			$src = html_entity_decode( $src_match[2], ENT_QUOTES, 'UTF-8' );
			$host = strtolower( (string) parse_url( $src, PHP_URL_HOST ) );
			if ( ! in_array( $host, array( 'youtube.com', 'www.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com' ), true ) ) {
				return $iframe;
			}
			if ( strpos( (string) parse_url( $src, PHP_URL_PATH ), '/embed/' ) !== 0 ) {
				return $iframe;
			}

			if ( ! preg_match( '#(?:^|[?&])widget_referrer=#i', $src ) ) {
				$separator = strpos( $src, '?' ) === false ? '?' : '&';
				$src      .= $separator . 'widget_referrer=' . rawurlencode( HECTV_PUBLIC_SITE_URL );
			}
			$encoded_src = str_replace( '&', '&amp;', $src );
			$iframe      = str_replace( $src_match[0], 'src=' . $src_match[1] . $encoded_src . $src_match[1], $iframe );

			if ( preg_match( '#\sreferrerpolicy=(["\'])[^"\']*\1#i', $iframe ) ) {
				$iframe = preg_replace(
					'#\sreferrerpolicy=(["\'])[^"\']*\1#i',
					' referrerpolicy="strict-origin-when-cross-origin"',
					$iframe,
					1
				);
			} else {
				$iframe = preg_replace(
					'#\s*(/?)>$#',
					' referrerpolicy="strict-origin-when-cross-origin"$1>',
					$iframe,
					1
				);
			}

			return $iframe;
		},
		$html
	);
}

/**
 * Repair cached Gutenberg oEmbed proxy responses as they leave the REST API.
 *
 * Core returns cached proxy data before provider-result filters run, so this
 * final response filter is required for already-authored Support-page embeds.
 *
 * @param mixed $response REST response.
 * @param mixed $server   REST server.
 * @param mixed $request  REST request.
 * @return mixed
 */
function hectv_youtube_identify_oembed_rest_response( $response, $server, $request ) {
	if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) || $request->get_route() !== '/oembed/1.0/proxy' ) {
		return $response;
	}
	if ( ! is_object( $response ) || ! method_exists( $response, 'get_data' ) || ! method_exists( $response, 'set_data' ) ) {
		return $response;
	}

	$data = $response->get_data();
	if ( is_array( $data ) && isset( $data['html'] ) ) {
		$data['html'] = hectv_youtube_add_editor_identity( $data['html'] );
		$response->set_data( $data );
	} elseif ( is_object( $data ) && isset( $data->html ) ) {
		$data->html = hectv_youtube_add_editor_identity( $data->html );
		$response->set_data( $data );
	}

	return $response;
}

add_filter( 'oembed_result', 'hectv_youtube_add_editor_identity', 20, 1 );
add_filter( 'embed_oembed_html', 'hectv_youtube_add_editor_identity', 20, 1 );
add_filter( 'rest_post_dispatch', 'hectv_youtube_identify_oembed_rest_response', 20, 3 );
