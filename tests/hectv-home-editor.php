<?php
/**
 * Focused contract for the page-scoped Home editor recovery.
 * Run: php tests/hectv-home-editor.php
 */

define( 'ABSPATH', __DIR__ );

$filters = array();
$actions = array();
$inline_scripts = array();
$script_registry = (object) array(
	'registered' => array(
		'acf-input' => (object) array( 'deps' => array( 'jquery' ) ),
	),
);

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['filters'][ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['actions'][ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_unslash( $value ) {
	return $value;
}

function wp_scripts() {
	return $GLOBALS['script_registry'];
}

function wp_add_inline_script( $handle, $data, $position = 'after' ) {
	$GLOBALS['inline_scripts'][] = array(
		'handle'   => $handle,
		'data'     => $data,
		'position' => $position,
	);
	return true;
}

function expect_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-cms-fields/editor.php';

expect_same( array(), $filters, 'The compatibility fix should not change the configured editor.' );

expect_same( 1, count( $actions['admin_enqueue_scripts'] ), 'Exactly one Home editor asset bridge should register.' );
$asset_registration = $actions['admin_enqueue_scripts'][0];
expect_same( 100, $asset_registration['priority'], 'The ACF bridge should run after plugins enqueue their scripts.' );

$bridge = $asset_registration['callback'];
$_GET['post'] = '32623';
$bridge( 'post.php' );
expect_same( array(), $inline_scripts, 'Other pages should not receive the legacy ACF bridge.' );
expect_same( array( 'jquery' ), $script_registry->registered['acf-input']->deps, 'Other pages should keep the original ACF script graph.' );

$_GET['post'] = (string) HECTV_HOME_PAGE_ID;
$bridge( 'post-new.php' );
expect_same( array(), $inline_scripts, 'Non-edit admin screens should not receive the legacy ACF bridge.' );

$bridge( 'post.php' );
expect_same( array( 'jquery', 'wp-hooks' ), $script_registry->registered['acf-input']->deps, 'Home should load WordPress hooks before legacy ACF.' );
expect_same( 2, count( $inline_scripts ), 'Home should capture and restore the two hook registries around ACF.' );
expect_same( 'acf-input', $inline_scripts[0]['handle'], 'Core-hook capture should attach to ACF input.' );
expect_same( 'before', $inline_scripts[0]['position'], 'Core hooks should be captured before ACF replaces the global.' );
expect_same( 'after', $inline_scripts[1]['position'], 'The ACF bridge should install after ACF registers its callbacks.' );
expect_same( true, strpos( $inline_scripts[1]['data'], "typeof legacyHooks.storage !== 'function'" ) !== false, 'The bridge should identify ACF 5.6.9\'s private hook manager.' );
expect_same( true, strpos( $inline_scripts[1]['data'], 'acf.add_action' ) !== false, 'The bridge should keep later ACF actions on the private registry.' );
expect_same( true, strpos( $inline_scripts[1]['data'], 'window.wp.hooks = coreHooks' ) !== false, 'The bridge should restore WordPress\'s modern global hooks.' );

echo "HEC Home editor contract passed.\n";
