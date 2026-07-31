<?php
/** Ensure an explicitly false seed flag never mutates menus. */

define( 'ABSPATH', __DIR__ );
define( 'HECTV_MENU_HEADER_ACTIONS', 'header_actions' );
define( 'HECTV_CMS_SEED_MENUS', false );
putenv( 'HECTV_ENVIRONMENT=production' );

$init_callback = null;
$created       = 0;

function add_action( $hook, $callback ) {
	global $init_callback;
	if ( $hook === 'init' ) {
		$init_callback = $callback;
	}
}
function __( $value ) { return $value; }
function register_nav_menu() {}
function get_option() { return false; }
function get_nav_menu_locations() { return array(); }
function wp_get_nav_menu_object() { return false; }
function wp_create_nav_menu() { global $created; $created++; return 5; }
function is_wp_error() { return false; }
function wp_get_nav_menu_items() { return array(); }
function home_url( $path ) { return $path; }
function wp_update_nav_menu_item() {}
function set_theme_mod() {}
function update_option() {}

require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-cms-fields/menus.php';
$init_callback();

if ( $created !== 0 ) {
	fwrite( STDERR, "HECTV_CMS_SEED_MENUS=false unexpectedly created a menu.\n" );
	exit( 1 );
}

echo "HEC disabled menu seed contract passed.\n";
