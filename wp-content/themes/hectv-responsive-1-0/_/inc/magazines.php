<?php

add_action( 'init', 'lb_magazine_init' );

function lb_magazine_init() {
	$labels = array(
		'name' => _x( 'Magazines', 'post type general name' ), // Tip: _x('') is used for localization
		'singular_name' => _x( 'Magazine', 'post type singular name' ),
		'add_new' => _x( 'Add New', 'video' ),
		'add_new_item' => __( 'Add New Issue' ),
		'edit_item' => __( 'Edit Issue' ),
		'new_item' => __( 'New Issue' ),
		'view_item' => __( 'View Issue' ),
		'search_items' => __( 'Search Issues' ),
		'not_found' =>  __( 'No issues found' ),
		'not_found_in_trash' => __( 'No issues found in Trash' ),
		'parent_item_colon' => ''
	);

	$args = array( 'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'magazine', 'with_front' => false ),
		'capability_type' => 'post',
		'menu_icon' => get_bloginfo('template_directory') . '/_/graphics/ui/ui-magazine.png',
		'hierarchical' => false,
		'menu_position' => null,
		'has_archive' => true,
		'supports' => array( 'title', 'editor' )
	);

	register_post_type( 'magazine', $args );

}

add_action( 'init', 'lb_magazine_taxonomies', 0 );

function lb_magazine_taxonomies() {

	$serieslabels = array(
		'name' => _x( 'Type', 'taxonomy general name' ),
		'singular_name' => _x( 'Types', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search Types' ),
		'all_items' => __( 'All Types' ),
		'parent_item' => __( 'Parent Type' ),
		'parent_item_colon' => __( 'Parent Types:' ),
		'edit_item' => __( 'Edit Type' ),
		'update_item' => __( 'Update Type' ),
		'add_new_item' => __( 'Add New Type' ),
		'new_item_name' => __( 'New Type Name' ),
	);

	register_taxonomy( 'type', array( 'magazine' ), array(
		'hierarchical' => true,
		'labels' => $serieslabels,
		'show_ui' => true,
		'query_var' => true,
		'show_in_nav_menus' => true,
		'rewrite' => array( 'slug' => 'magazine/type', 'with_front' => false, 'hierarchical' => false  ),
	));

}

add_filter('pre_option_posts_per_page', 'lb_limit_posts_per_page');

function lb_limit_posts_per_page() {
	
	$queried_object = get_queried_object();
	
	return 16;
		
}


?>