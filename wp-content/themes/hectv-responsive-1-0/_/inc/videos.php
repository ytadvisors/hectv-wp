<?php

function lb_video_scripts() {

	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_enqueue_script('my-upload');

}

function lb_video_admin_styles() {

	wp_enqueue_style('thickbox');

}

add_action( 'init', 'lb_video_init' );

function lb_video_init() {

	$labels = array(
		'name' => _x( 'Videos', 'post type general name' ), // Tip: _x('') is used for localization
		'singular_name' => _x( 'Video', 'post type singular name' ),
		'add_new' => _x( 'Add New', 'video' ),
		'add_new_item' => __( 'Add New Video' ),
		'edit_item' => __( 'Edit Video' ),
		'new_item' => __( 'New Video' ),
		'view_item' => __( 'View Video' ),
		'search_items' => __( 'Search Videos' ),
		'not_found' =>  __( 'No videos found' ),
		'not_found_in_trash' => __( 'No videos found in Trash' ),
		'parent_item_colon' => ''
	);

	$args = array( 'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'query_var' => true,
		'menu_icon' => get_bloginfo('template_directory') . '/_/graphics/ui/ui-video.png',
		'hierarchical' => true,
		'menu_position' => null,
		'has_archive' => true,
		'supports' => array( 'title', 'revisions', 'comments' ),
		'taxonomies' => array( 'topic' )
	);

	register_post_type( 'video', $args );

}

add_action( 'init', 'lb_create_program_taxonomies', 0 );

function lb_create_program_taxonomies() {

	$serieslabels = array(
		'name' => _x( 'Programs', 'taxonomy general name' ),
		'singular_name' => _x( 'Programs', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search Programs' ),
		'all_items' => __( 'All Programs' ),
		'parent_item' => __( 'Parent Programs' ),
		'parent_item_colon' => __( 'Parent Programs:' ),
		'edit_item' => __( 'Edit Program' ),
		'update_item' => __( 'Update Program' ),
		'add_new_item' => __( 'Add New Program' ),
		'new_item_name' => __( 'New Program Name' ),
	);

	register_taxonomy( 'program', array( 'video', 'lb_video' ), array(
		'hierarchical' => true,
		'labels' => $serieslabels,
		'show_ui' => true,
		'query_var' => true,
		'show_in_nav_menus' => true,
		'rewrite' => array( 'slug' => 'programs', 'with_front' => false, 'hierarchical' => false  ),
	));

}

?>