<?php
/**
 * Dual-schema consumer contract (source-level).
 */

$root = dirname( __DIR__ );

$compat = file_get_contents( $root . '/staging-harness/mu-plugins/hectv-graphql-compat.php' );
$profile = file_get_contents( $root . '/wp-content/mu-plugins/hectv-graphql-schema-profile.php' );
$fixture = file_get_contents( $root . '/tests/fixtures/consumer-graphql/legacy-lambda146-critical.graphql' );
$readyz = file_get_contents( $root . '/deploy/container/readyz.php' );
$dockerfile = file_get_contents( $root . '/Dockerfile' );

foreach ( array(
	'compat' => $compat,
	'profile' => $profile,
	'fixture' => $fixture,
	'readyz' => $readyz,
	'dockerfile' => $dockerfile,
) as $name => $content ) {
	if ( $content === false || $content === '' ) {
		fwrite( STDERR, "missing $name\n" );
		exit( 1 );
	}
}

foreach ( array(
	'PostToCategoryConnectionWhereArgs',
	'shouldOutputInFlatList',
	"'Event'",
	"'excerpt'",
) as $needle ) {
	if ( strpos( $compat, $needle ) === false ) {
		fwrite( STDERR, "graphql compat missing dual-schema token: $needle\n" );
		exit( 1 );
	}
}

if ( strpos( $profile, 'consumer-v1' ) === false || strpos( $profile, "return 'consumer-v1'" ) === false ) {
	fwrite( STDERR, "schema profile must default to consumer-v1\n" );
	exit( 1 );
}

if ( preg_match( '/HECTV_ENVIRONMENT\s*\)\s*!==\s*\'staging\'|HECTV_ENVIRONMENT\s*===?\s*\'staging\'/', $profile ) ) {
	fwrite( STDERR, "schema profile must not key off HECTV_ENVIRONMENT\n" );
	exit( 1 );
}

if ( strpos( $fixture, 'shouldOutputInFlatList' ) === false || strpos( $fixture, 'excerpt' ) === false ) {
	fwrite( STDERR, "legacy fixture missing critical fields\n" );
	exit( 1 );
}

if ( strpos( $readyz, 'wp-load.php' ) === false || strpos( $readyz, 'graphql' ) === false ) {
	fwrite( STDERR, "readyz.php must boot WordPress and exercise GraphQL\n" );
	exit( 1 );
}

if ( strpos( $dockerfile, 'readyz.php' ) === false ) {
	fwrite( STDERR, "Dockerfile must copy readyz.php\n" );
	exit( 1 );
}

// Profile helpers resolve identically when env is unset, staging, or production.
putenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE' );
require $root . '/wp-content/mu-plugins/hectv-graphql-schema-profile.php';
$default = hectv_graphql_schema_profile();
putenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE=staging-typo' );
$invalid = hectv_graphql_schema_profile();
putenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE=consumer-v1' );
$explicit = hectv_graphql_schema_profile();
if ( $default !== 'consumer-v1' || $invalid !== 'consumer-v1' || $explicit !== 'consumer-v1' ) {
	fwrite( STDERR, "schema profile resolution is not production/staging identical\n" );
	exit( 1 );
}

echo "dual-schema consumer contract source tests passed\n";
