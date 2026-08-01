<?php
/**
 * Plugin Name: HEC TV Staging Query Compatibility
 * Description: Keeps the public read-only staging runtime on WordPress SQL queries.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( getenv( 'HECTV_ENVIRONMENT' ) !== 'staging' || getenv( 'HECTV_PUBLIC_READ_ONLY' ) !== '1' ) {
	return;
}

/**
 * ElasticPress assumes it can refresh transients and may route taxonomy queries
 * through an index that is not part of the isolated staging contract.  The
 * public staging database user is deliberately SELECT-only, so keep those
 * reads on canonical WordPress SQL instead.
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

// The imported database predates registered menu locations. Expose the
// existing Header menu through WPGraphQL's stable root menuItems connection,
// avoiding the legacy nested Menu.menuItems resolver that fails on its
// three-level tree.
add_filter(
	'theme_mod_nav_menu_locations',
	static function ( $locations ) {
		$locations = is_array( $locations ) ? $locations : array();
		if ( empty( $locations['primary'] ) ) {
			$menu = get_term_by( 'slug', 'header', 'nav_menu' );
			if ( $menu && ! is_wp_error( $menu ) ) {
				$locations['primary'] = (int) $menu->term_id;
			}
		}
		return $locations;
	},
	1
);
