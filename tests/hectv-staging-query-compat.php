<?php

define( 'ABSPATH', __DIR__ );

$filters = array();
function add_filter( $name, $callback ) {
	$GLOBALS['filters'][ $name ] = $callback;
}
function get_term_by() {
	return (object) array( 'term_id' => 14446 );
}
function is_wp_error() {
	return false;
}

putenv( 'HECTV_ENVIRONMENT=staging' );
putenv( 'HECTV_PUBLIC_READ_ONLY=1' );
require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-staging-query-compat.php';

if ( ! isset( $filters['option_active_plugins'] ) ) {
	fwrite( STDERR, "staging filter was not registered\n" );
	exit( 1 );
}

$result = $filters['option_active_plugins'](
	array( 'akismet/akismet.php', 'elasticpress/elasticpress.php', 'wp-graphql/wp-graphql.php' )
);

if ( $result !== array( 'akismet/akismet.php', 'wp-graphql/wp-graphql.php' ) ) {
	fwrite( STDERR, "ElasticPress was not removed from the staging plugin list\n" );
	exit( 1 );
}

echo "staging query compatibility test passed\n";

$locations = $filters['theme_mod_nav_menu_locations']( array() );
if ( $locations['primary'] !== 14446 ) {
	fwrite( STDERR, "Header menu was not exposed as PRIMARY\n" );
	exit( 1 );
}
