<?php
/**
 * Application readiness probe — boots WordPress and requires GraphQL success.
 *
 * Unlike static /healthz (liveness only), this fails closed unless WPGraphQL
 * answers a dual-schema consumer probe without errors.
 *
 * Never sends mail, charges payments, or writes content.
 */

header( 'Content-Type: application/json; charset=utf-8' );
header( 'Cache-Control: no-store' );

$respond = static function ( bool $ok, array $payload, int $code ): void {
	http_response_code( $code );
	$payload['ok'] = $ok;
	echo json_encode( $payload );
	exit;
};

if ( ! defined( 'ABSPATH' ) ) {
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

if ( $profile !== 'consumer-v1' ) {
	$respond(
		false,
		array(
			'reason'  => 'unexpected_schema_profile',
			'profile' => $profile,
		),
		503
	);
}

// GraphQL is mandatory. A WordPress-only boot is not application readiness.
if ( ! function_exists( 'graphql' ) ) {
	$respond(
		false,
		array(
			'reason'  => 'graphql_helper_missing',
			'profile' => $profile,
		),
		503
	);
}

// Dual-schema probe: general settings + nested Post.categories flat-list arg
// (PostToCategoryConnectionWhereArgs — the 2026-08-06 failure mode).
$query = <<<'GQL'
query HectvReadyz {
  generalSettings { title }
  posts(first: 1) {
    nodes {
      title
      categories(where: { shouldOutputInFlatList: true }, first: 1) {
        nodes { name }
      }
    }
  }
}
GQL;

$result = graphql( array( 'query' => $query ) );
if ( ! is_array( $result ) ) {
	$respond( false, array( 'reason' => 'graphql_non_array', 'profile' => $profile ), 503 );
}
if ( ! empty( $result['errors'] ) ) {
	$messages = array();
	foreach ( (array) $result['errors'] as $error ) {
		if ( is_array( $error ) && isset( $error['message'] ) ) {
			$messages[] = (string) $error['message'];
		}
	}
	$respond(
		false,
		array(
			'reason'  => 'graphql_errors',
			'profile' => $profile,
			'errors'  => array_slice( $messages, 0, 5 ),
		),
		503
	);
}

$title = '';
if ( isset( $result['data']['generalSettings']['title'] ) ) {
	$title = (string) $result['data']['generalSettings']['title'];
}
if ( $title === '' ) {
	$respond( false, array( 'reason' => 'empty_title', 'profile' => $profile ), 503 );
}

$respond(
	true,
	array(
		'profile' => $profile,
		'title'   => $title,
		'mode'    => 'graphql',
	),
	200
);
