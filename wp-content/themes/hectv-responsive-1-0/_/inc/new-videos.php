<?php

function lb_video_new_scripts() {

	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_enqueue_script('my-upload');

}

function lb_video_new_admin_styles() {

	wp_enqueue_style('thickbox');

}

add_action( 'init', 'lb_video_new_init' );

function lb_video_new_init() {

	$labels = array(
		'name' => _x( 'Segments', 'post type general name' ), // Tip: _x('') is used for localization
		'singular_name' => _x( 'Segment', 'segment' ),
		'add_new' => _x( 'Add New', 'segment' ),
		'add_new_item' => __( 'Add New Segment' ),
		'edit_item' => __( 'Edit Segment' ),
		'new_item' => __( 'New Segment' ),
		'view_item' => __( 'View Segment' ),
		'search_items' => __( 'Search Segments' ),
		'not_found' =>  __( 'No segments found' ),
		'not_found_in_trash' => __( 'No segments found in Trash' ),
		'parent_item_colon' => ''
	);

	$args = array( 'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'query_var' => false,
		'rewrite' => false,
// 		'capability_type' => 'page',
		'hierarchical' => true,
		'menu_icon' => get_bloginfo('template_directory') . '/_/graphics/ui/ui-video.png',
		'hierarchical' => true,
		'menu_position' => null,
		'has_archive' => true,
		'supports' => array( 'title', 'revisions', 'content', 'comments'/* , 'page-attributes' */ )
	);

	register_post_type( 'lb_video', $args );

}


add_action('admin_menu', function() {

	remove_meta_box('pageparentdiv', 'lb_video', 'normal');

});

add_action('add_meta_boxes', function() {

	add_meta_box('playlist-parent', 'Playlist Association', 'lb_video_add_meta_box', 'lb_video', 'side', 'default');

});

function lb_video_add_meta_box( $post ) {

	$post_type_object = get_post_type_object($post->post_type);

	if ( $post_type_object->hierarchical && $post->post_type == 'lb_playlist' ) {


		$segments = get_posts( array( "post_type" => "lb_playlist", "post_status" => "publish", "posts_per_page" => -1 ) );

		echo '<select name="parent_id">';

		foreach( $segments as $index => $segment ){

			if( $segment->ID ){

				$selected = ( $post->post_parent == $segment->ID ) ? 'selected="selected"':'';

				echo "<option value=\"$segment->ID\" $selected>$segment->post_title</option>";

			}

		}

		echo '</select>';

	} // end hierarchical check.

}



?>