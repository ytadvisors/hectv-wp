<?php
/**
 * Focused contract for the page-scoped Home editor recovery.
 * Run: php tests/hectv-home-editor.php
 */

define( 'ABSPATH', __DIR__ );

$filters = array();

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['filters'][ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

function expect_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-cms-fields/editor.php';

expect_same( 1, count( $filters['use_block_editor_for_post'] ), 'Exactly one page-scoped editor filter should register.' );

$registration = $filters['use_block_editor_for_post'][0];
expect_same( 100, $registration['priority'], 'Editor recovery should run after default editor selection.' );
expect_same( 2, $registration['accepted_args'], 'Editor recovery needs the post object.' );

$filter = $registration['callback'];
$home   = (object) array( 'ID' => HECTV_HOME_PAGE_ID, 'post_type' => 'page' );
$page   = (object) array( 'ID' => 32623, 'post_type' => 'page' );
$post   = (object) array( 'ID' => HECTV_HOME_PAGE_ID, 'post_type' => 'post' );

expect_same( false, $filter( true, $home ), 'Home should use Classic Editor.' );
expect_same( false, $filter( false, $home ), 'Home should preserve an already-disabled block editor.' );
expect_same( true, $filter( true, $page ), 'Other pages should retain the block editor.' );
expect_same( true, $filter( true, $post ), 'A post sharing the numeric ID should retain the block editor.' );
expect_same( false, $filter( false, $page ), 'Other pages should preserve an upstream Classic Editor decision.' );
expect_same( true, $filter( true, null ), 'Missing post context should preserve the upstream decision.' );

echo "HEC Home editor contract passed.\n";
