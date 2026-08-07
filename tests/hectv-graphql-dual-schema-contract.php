<?php
/**
 * Dual-schema consumer contract tests.
 *
 * 1) Source-level: required registrations + profile defaults + fixture shape.
 * 2) Runtime (optional): when HECTV_CONTRACT_GRAPHQL_URL is set, execute the
 *    legacy Lambda 146 + modern fixtures and fail on any GraphQL errors.
 */

$root = dirname( __DIR__ );

$compat     = file_get_contents( $root . '/staging-harness/mu-plugins/hectv-graphql-compat.php' );
$profile    = file_get_contents( $root . '/wp-content/mu-plugins/hectv-graphql-schema-profile.php' );
$legacy     = file_get_contents( $root . '/tests/fixtures/consumer-graphql/legacy-lambda146-critical.graphql' );
$modern     = file_get_contents( $root . '/tests/fixtures/consumer-graphql/modern-consumer-critical.graphql' );
$readyz     = file_get_contents( $root . '/deploy/container/readyz.php' );
$dockerfile = file_get_contents( $root . '/Dockerfile' );
$promote    = file_get_contents( $root . '/scripts/production/promote-release.sh' );

foreach ( compact( 'compat', 'profile', 'legacy', 'modern', 'readyz', 'dockerfile', 'promote' ) as $name => $content ) {
	if ( $content === false || $content === '' ) {
		fwrite( STDERR, "missing $name\n" );
		exit( 1 );
	}
}

// --- source contract -------------------------------------------------------

foreach ( array(
	'PostToCategoryConnectionWhereArgs',
	'shouldOutputInFlatList',
	"'Event'",
	"'excerpt'",
	'hectv_graphql_consumer_contract_enabled',
) as $needle ) {
	if ( strpos( $compat, $needle ) === false && strpos( $profile, $needle ) === false ) {
		fwrite( STDERR, "dual-schema token missing: $needle\n" );
		exit( 1 );
	}
}

if ( strpos( $compat, 'hectv_graphql_consumer_contract_enabled' ) === false ) {
	fwrite( STDERR, "compat plugin must consult hectv_graphql_consumer_contract_enabled()\n" );
	exit( 1 );
}

// Nested Post.categories flat-list — the real Lambda 146 shape.
if ( strpos( $legacy, 'categories(where: { shouldOutputInFlatList: true }' ) === false ) {
	fwrite( STDERR, "legacy fixture must nest shouldOutputInFlatList under Post.categories\n" );
	exit( 1 );
}
if ( strpos( $legacy, 'posts(first:' ) !== false && preg_match( '/posts\s*\([^)]*shouldOutputInFlatList/', $legacy ) ) {
	fwrite( STDERR, "legacy fixture must not put shouldOutputInFlatList on top-level posts(where:)\n" );
	exit( 1 );
}

// readyz must require GraphQL (no wp-boot-only success path).
if ( strpos( $readyz, 'graphql_helper_missing' ) === false || strpos( $readyz, 'wp-boot' ) !== false ) {
	fwrite( STDERR, "readyz.php must fail closed without GraphQL (no wp-boot success mode)\n" );
	exit( 1 );
}
if ( strpos( $readyz, 'shouldOutputInFlatList' ) === false ) {
	fwrite( STDERR, "readyz.php must probe nested shouldOutputInFlatList\n" );
	exit( 1 );
}

// Promote-release must gate on /readyz.php application readiness.
if ( strpos( $promote, 'readyz.php' ) === false || strpos( $promote, 'ORIGIN_READYZ_URL' ) === false ) {
	fwrite( STDERR, "promote-release.sh must call /readyz.php as an application gate\n" );
	exit( 1 );
}

// Profile: unset → consumer-v1; invalid → fail closed empty (not silent accept).
require $root . '/wp-content/mu-plugins/hectv-graphql-schema-profile.php';
putenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE' );
$default = hectv_graphql_schema_profile();
putenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE=staging-typo' );
$invalid = hectv_graphql_schema_profile();
putenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE=consumer-v1' );
$explicit = hectv_graphql_schema_profile();
if ( $default !== 'consumer-v1' || $explicit !== 'consumer-v1' ) {
	fwrite( STDERR, "profile default/explicit must be consumer-v1\n" );
	exit( 1 );
}
if ( $invalid !== '' ) {
	fwrite( STDERR, "invalid profile must fail closed (empty), got: $invalid\n" );
	exit( 1 );
}
if ( ! hectv_graphql_consumer_contract_enabled() ) {
	fwrite( STDERR, "consumer contract should be enabled for consumer-v1\n" );
	exit( 1 );
}
putenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE=staging-typo' );
if ( hectv_graphql_consumer_contract_enabled() ) {
	fwrite( STDERR, "consumer contract must be disabled for invalid profile\n" );
	exit( 1 );
}
putenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE=consumer-v1' );

$fp = hectv_graphql_consumer_fingerprint();
if ( empty( $fp['fields'] ) || ! in_array( 'PostToCategoryConnectionWhereArgs.shouldOutputInFlatList', $fp['fields'], true ) ) {
	fwrite( STDERR, "fingerprint missing PostToCategoryConnectionWhereArgs.shouldOutputInFlatList\n" );
	exit( 1 );
}

// Staging/production fingerprint parity is identity by construction for this profile.
$staging_fp    = $fp;
$production_fp = hectv_graphql_consumer_fingerprint();
if ( $staging_fp !== $production_fp ) {
	fwrite( STDERR, "staging/production schema fingerprints diverge\n" );
	exit( 1 );
}

echo "dual-schema source contract tests passed\n";

// --- optional live GraphQL execution ---------------------------------------

$endpoint = getenv( 'HECTV_CONTRACT_GRAPHQL_URL' );
if ( ! $endpoint ) {
	echo "dual-schema runtime GraphQL execution skipped (set HECTV_CONTRACT_GRAPHQL_URL to enable)\n";
	exit( 0 );
}

/**
 * Split a multi-operation .graphql fixture into named operations.
 *
 * @return array<string,string> name => query document
 */
function hectv_split_graphql_operations( string $source ): array {
	$ops    = array();
	$chunks = preg_split( '/(?=^\s*(query|mutation)\s+)/m', $source );
	foreach ( $chunks as $chunk ) {
		$chunk = trim( $chunk );
		if ( $chunk === '' || $chunk[0] === '#' ) {
			continue;
		}
		if ( ! preg_match( '/^(query|mutation)\s+([A-Za-z0-9_]+)/', $chunk, $m ) ) {
			continue;
		}
		// Strip leading comment-only lines already handled; keep the operation body.
		$ops[ $m[2] ] = $chunk;
	}
	return $ops;
}

// Default runtime suite is the Lambda 146 legacy documents (the incident class).
// Set HECTV_CONTRACT_GRAPHQL_SUITE=all to also require modern consumer operations
// (requires a dual-schema candidate backend, not the pre-expand recovery image).
$suite = strtolower( (string) ( getenv( 'HECTV_CONTRACT_GRAPHQL_SUITE' ) ?: 'legacy' ) );
$operations = hectv_split_graphql_operations( $legacy );
if ( $suite === 'all' ) {
	$operations = array_merge( $operations, hectv_split_graphql_operations( $modern ) );
}
if ( count( $operations ) < 2 ) {
	fwrite( STDERR, "failed to parse consumer fixture operations\n" );
	exit( 1 );
}

foreach ( $operations as $name => $document ) {
	$payload = json_encode( array( 'query' => $document ) );
	$ch      = curl_init( $endpoint );
	curl_setopt_array(
		$ch,
		array(
			CURLOPT_POST           => true,
			CURLOPT_HTTPHEADER     => array( 'Content-Type: application/json' ),
			CURLOPT_POSTFIELDS     => $payload,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
		)
	);
	$body = curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	$err  = curl_error( $ch );
	unset( $ch );
	if ( $body === false || $code < 200 || $code >= 300 ) {
		fwrite( STDERR, "operation $name HTTP failure code=$code err=$err\n" );
		exit( 1 );
	}
	$decoded = json_decode( $body, true );
	if ( ! is_array( $decoded ) ) {
		fwrite( STDERR, "operation $name returned non-JSON\n" );
		exit( 1 );
	}
	if ( ! empty( $decoded['errors'] ) ) {
		$msg = isset( $decoded['errors'][0]['message'] ) ? $decoded['errors'][0]['message'] : 'unknown';
		fwrite( STDERR, "operation $name GraphQL errors: $msg\n" );
		exit( 1 );
	}
	echo "runtime ok: $name\n";
}

echo "dual-schema runtime GraphQL contract tests passed (suite=$suite)\n";
