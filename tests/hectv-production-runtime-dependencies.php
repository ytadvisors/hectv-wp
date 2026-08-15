<?php
/**
 * Contract test for the production runtime dependency removals.
 *
 * Run: php tests/hectv-production-runtime-dependencies.php
 */

define( 'ABSPATH', __DIR__ );

$filters = array();
function add_filter( $name, $callback ) {
	$GLOBALS['filters'][ $name ] = $callback;
}

putenv( 'HECTV_ENVIRONMENT=production' );
require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-production-query-compat.php';

if ( ! isset( $filters['option_active_plugins'] ) ) {
	fwrite( STDERR, "production plugin filter was not registered\n" );
	exit( 1 );
}

$active_plugins = $filters['option_active_plugins'](
	array( 'akismet/akismet.php', 'elasticpress/elasticpress.php', 'wp-graphql/wp-graphql.php' )
);
if ( $active_plugins !== array( 'akismet/akismet.php', 'wp-graphql/wp-graphql.php' ) ) {
	fwrite( STDERR, "ElasticPress was not removed from production plugins\n" );
	exit( 1 );
}

if ( ! isset( $filters['site_option_active_sitewide_plugins'] ) ) {
	fwrite( STDERR, "production network plugin filter was not registered\n" );
	exit( 1 );
}

$network_plugins = $filters['site_option_active_sitewide_plugins'](
	array(
		'elasticpress/elasticpress.php' => 1,
		'wp-graphql/wp-graphql.php'     => 2,
	)
);
if ( $network_plugins !== array( 'wp-graphql/wp-graphql.php' => 2 ) ) {
	fwrite( STDERR, "ElasticPress was not removed from production network plugins\n" );
	exit( 1 );
}

$admin_source = file_get_contents( dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv/hectv_admin.php' );
foreach ( array( 'run_build', 'BUILD_URL', 'BUILD_TOKEN' ) as $legacy_build_reference ) {
	if ( strpos( $admin_source, $legacy_build_reference ) !== false ) {
		fwrite( STDERR, "legacy Jenkins build reference remains: {$legacy_build_reference}\n" );
		exit( 1 );
	}
}

foreach (
	array(
		'/infra/production/main.tf',
		'/scripts/production/import-eb-runtime-secret.sh',
	) as $production_config
) {
	$config_source = file_get_contents( dirname( __DIR__ ) . $production_config );
	foreach ( array( 'BUILD_URL', 'BUILD_TOKEN' ) as $legacy_build_reference ) {
		if ( strpos( $config_source, $legacy_build_reference ) !== false ) {
			fwrite( STDERR, "legacy Jenkins secret remains in {$production_config}: {$legacy_build_reference}\n" );
			exit( 1 );
		}
	}
}

echo "production runtime dependency test passed\n";
