<?php
/**
 * Plugin Name: HEC TV Production Query Compatibility
 * Description: Keeps production reads on canonical WordPress SQL after retiring ElasticPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( getenv( 'HECTV_ENVIRONMENT' ) !== 'production' ) {
	return;
}

/**
 * Production no longer maintains an Elasticsearch index. Remove ElasticPress
 * from both regular and network plugin activation lists before WordPress loads
 * plugins so native WordPress SQL remains the only query path.
 */
add_filter(
	'option_active_plugins',
	static function ( $plugins ) {
		if ( ! is_array( $plugins ) ) {
			return $plugins;
		}

		return array_values(
			array_filter(
				$plugins,
				static function ( $plugin ) {
					return $plugin !== 'elasticpress/elasticpress.php';
				}
			)
		);
	},
	1
);

add_filter(
	'site_option_active_sitewide_plugins',
	static function ( $plugins ) {
		if ( ! is_array( $plugins ) ) {
			return $plugins;
		}

		unset( $plugins['elasticpress/elasticpress.php'] );
		return $plugins;
	},
	1
);
