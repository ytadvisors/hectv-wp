<?php

define( 'ABSPATH', __DIR__ );

$actions = array();
$filters = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['actions'][ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['filters'][ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

function post_type_exists() {
	return true;
}

function taxonomy_exists() {
	return true;
}

$wp_post_types = array();
foreach ( array( 'magazine', 'event', 'schedule', 'video' ) as $post_type ) {
	$wp_post_types[ $post_type ] = (object) array(
		'show_in_graphql'     => false,
		'graphql_single_name' => null,
		'graphql_plural_name' => null,
	);
}

$wp_taxonomies = array(
	'event_category' => (object) array(
		'show_in_graphql'     => false,
		'graphql_single_name' => null,
		'graphql_plural_name' => null,
	),
);

require dirname( __DIR__ ) . '/staging-harness/mu-plugins/hectv-graphql-compat.php';

$post_type_filters = isset( $filters['register_post_type_args'] ) ? $filters['register_post_type_args'] : array();
if (
	count( $post_type_filters ) !== 1 ||
	$post_type_filters[0]['priority'] !== 100 ||
	$post_type_filters[0]['accepted_args'] !== 2
) {
	fwrite( STDERR, "Modern GraphQL metadata must filter post-type registration arguments.\n" );
	exit( 1 );
}

$event_args = $post_type_filters[0]['callback']( array( 'public' => false ), 'event' );
if (
	! $event_args['show_in_graphql'] ||
	$event_args['graphql_single_name'] !== 'Event' ||
	$event_args['graphql_plural_name'] !== 'events'
) {
	fwrite( STDERR, "GraphQL metadata was not applied during event registration.\n" );
	exit( 1 );
}

$post_args = $post_type_filters[0]['callback']( array( 'public' => true ), 'post' );
if ( $post_args !== array( 'public' => true ) ) {
	fwrite( STDERR, "Unowned post-type registration arguments were modified.\n" );
	exit( 1 );
}

$taxonomy_filters = isset( $filters['register_taxonomy_args'] ) ? $filters['register_taxonomy_args'] : array();
if (
	count( $taxonomy_filters ) !== 1 ||
	$taxonomy_filters[0]['priority'] !== 100 ||
	$taxonomy_filters[0]['accepted_args'] !== 2
) {
	fwrite( STDERR, "Modern GraphQL metadata must filter taxonomy registration arguments.\n" );
	exit( 1 );
}

$event_category_args = $taxonomy_filters[0]['callback']( array( 'public' => false ), 'event_category' );
if (
	! $event_category_args['show_in_graphql'] ||
	$event_category_args['graphql_single_name'] !== 'EventCategory' ||
	$event_category_args['graphql_plural_name'] !== 'eventCategories'
) {
	fwrite( STDERR, "GraphQL metadata was not applied during event-category registration.\n" );
	exit( 1 );
}

$init_actions = isset( $actions['init'] ) ? $actions['init'] : array();
if ( count( $init_actions ) !== 1 || $init_actions[0]['priority'] !== 100 ) {
	fwrite( STDERR, "Modern GraphQL exposure must run after the imported theme registers its content types.\n" );
	exit( 1 );
}

$init_actions[0]['callback']();

$expected = array(
	'magazine' => array( 'Magazine', 'magazines' ),
	'event'    => array( 'Event', 'events' ),
	'schedule' => array( 'Schedule', 'schedules' ),
	'video'    => array( 'Video', 'videos' ),
);

foreach ( $expected as $post_type => $names ) {
	$object = $wp_post_types[ $post_type ];
	if (
		! $object->show_in_graphql ||
		$object->graphql_single_name !== $names[0] ||
		$object->graphql_plural_name !== $names[1]
	) {
		fwrite( STDERR, "GraphQL exposure failed for {$post_type}.\n" );
		exit( 1 );
	}
}

$event_category = $wp_taxonomies['event_category'];
if (
	! $event_category->show_in_graphql ||
	$event_category->graphql_single_name !== 'EventCategory' ||
	$event_category->graphql_plural_name !== 'eventCategories'
) {
	fwrite( STDERR, "GraphQL exposure failed for event_category.\n" );
	exit( 1 );
}

echo "modern CPT GraphQL exposure test passed\n";
