<?php
/**
 * Application readiness probe — boots WordPress and runs a read-only GraphQL check.
 *
 * Unlike static /healthz, this proves WordPress boots, DB options are readable, and
 * GraphQL (when available) returns generalSettings.title.
 *
 * Never sends mail, charges payments, or writes content.
 */

header( 'Content-Type: application/json; charset=utf-8' );
header( 'Cache-Control: no-store' );

$respond = static function ( bool $ok, array $payload, int $code ) {
	http_response_code( $code );
	$payload['ok'] = $ok;
	echo json_encode( $payload );
	exit;
};

if ( ! defined( 'ABSPATH' ) ) {
	// Container image path: this file is /var/www/html/readyz.php
	$candidates = array(
		dirname( __FILE__ ) . '/wp-load.php',
		'/var/www/html/wp-load.php',
	);
	$wp_load = null;
	foreach ( $candidates as $candidate ) {
		if ( is_readable( $candidate ) ) {
			$wp_load = $candidate;
			break;
		}
	}
	if ( ! $wp_load ) {
		$respond( false, array( 'reason' => 'wp-load missing' ), 503 );
	}
	define( 'WP_USE_THEMES', false );
	require $wp_load;
}

$profile = 'consumer-v1';
if ( function_exists( 'hectv_graphql_schema_profile' ) ) {
	$profile = hectv_graphql_schema_profile();
} elseif ( getenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE' ) ) {
	$profile = strtolower( trim( (string) getenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE' ) ) );
}

$title = '';
$mode  = 'wp-boot';

if ( function_exists( 'graphql' ) ) {
	$result = graphql(
		array(
			'query' => 'query HectvReadyz { generalSettings { title } }',
		)
	);
	if ( ! is_array( $result ) || ! empty( $result['errors'] ) ) {
		$respond(
			false,
			array(
				'reason'  => 'graphql_errors',
				'profile' => $profile,
			),
			503
		);
	}
	if ( isset( $result['data']['generalSettings']['title'] ) ) {
		$title = (string) $result['data']['generalSettings']['title'];
	}
	$mode = 'graphql';
} elseif ( function_exists( 'get_option' ) ) {
	$title = (string) get_option( 'blogname', '' );
} else {
	$respond( false, array( 'reason' => 'wordpress_helpers_missing', 'profile' => $profile ), 503 );
}

if ( $title === '' ) {
	$respond( false, array( 'reason' => 'empty_title', 'profile' => $profile, 'mode' => $mode ), 503 );
}

$respond(
	true,
	array(
		'profile' => $profile,
		'title'   => $title,
		'mode'    => $mode,
	),
	200
);
