<?php

require_once('write-actions.php');

function lb_playlist_admin_scripts() {

	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_enqueue_script('my-upload');

}

function lb_playlist_admin_styles() {

	wp_enqueue_style('thickbox');

}

add_action( 'init', 'lb_playlist_init' );

function lb_playlist_init() {

	$labels = array(
		'name' => _x( 'Episodes', 'post type general name' ), // Tip: _x('') is used for localization
		'singular_name' => _x( 'Episode', 'post type singular name' ),
		'add_new' => _x( 'Add New', 'playlist' ),
		'add_new_item' => __( 'Add New Episode' ),
		'edit_item' => __( 'Edit Episode' ),
		'new_item' => __( 'New Episode' ),
		'view_item' => __( 'View Episode' ),
		'search_items' => __( 'Search Episodes' ),
		'not_found' =>  __( 'No episodes found' ),
		'not_found_in_trash' => __( 'No episodes found in Trash' ),
		'parent_item_colon' => ''
	);

	$args = array( 'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => false,
		'menu_icon' => get_bloginfo('template_directory') . '/_/graphics/ui/ui-playlist.png',
		'hierarchical' => true,
		'menu_position' => null,
		'has_archive' => true,
		'capability' => array( 'edit_playlist' ),
		'supports' => array( 'title', 'revisions'/* , 'page-attributes' */ )
	);

	register_post_type( 'lb_playlist', $args );

}

add_filter('rewrite_rules_array', 'lb_playlist_rewrite_rules');

function lb_playlist_rewrite_rules( $rules ) {

    $newRules                      = array();

	// Rule for series pages
	$newRules['watch/(.+?)/?$'] = 'index.php?post_type=page&pagename=$matches[1]';

	// Rule for OLD series playlists: /video/show-title/playlist-title/ID
	$newRules['watch/(.+)/(.+)/([0-9]+)/?$'] = 'index.php?p=$matches[3]&post_type=lb_playlist&playlistquery=true';

	// Rule for series playlists with segment selected: /video/show-title/playlist-title/ID/segment-title/
	$newRules['watch/(.+)/(.+)/([0-9]+)/(.+)/?$'] = 'index.php?child_of=$matches[3]&name=$matches[4]&post_type=lb_video&segmentquery=true&parent_id=$matches[3]';

    return array_merge($newRules, $rules);

}

add_action('admin_menu', function() {

	remove_meta_box('pageparentdiv', 'lb_playlist', 'normal');

});

add_action('add_meta_boxes', function() {

	add_meta_box('playlist-parent', 'Series Association', 'lb_playlist_add_meta_box', 'lb_playlist', 'side', 'default');

});

function lb_playlist_add_meta_box( $post ) {

	$post_type_object = get_post_type_object($post->post_type);
	
	if ( $post_type_object->hierarchical && $post->post_type == 'lb_playlist' ) {

		$pages = wp_dropdown_pages( array( 'post_type' => 'page', 'selected' => $post->post_parent, 'name' => 'parent_id', 'show_option_none' => 'No Series Association', 'sort_column'=> 'menu_order, post_title', 'echo' => 0, 'child_of' => 16789, 'post_status' => array('publish','private','draft') ) );

		if ( ! empty($pages) ) {

			echo $pages;

		}

	}

}

add_action("admin_init", "lb_add_playlist_options");

function lb_add_playlist_options(){

	add_meta_box("lb_playlist_options", "Playlist Details", "lb_playlist_options", "lb_playlist", "normal", "high");

}

function lb_playlist_options(){

	require_once('playlist-editor.php');

}

add_action( 'save_post', 'lb_playlist_store' );

function lb_playlist_store(){

	global $post;

	if( $post->post_type == "lb_playlist" && ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) ){

		update_post_meta( $post->ID, "long_description", $_POST['long_description'] );
		update_post_meta( $post->ID, "meta_description", $_POST['meta_description'] );
		update_post_meta( $post->ID, "links", $_POST["link"] );
		update_post_meta( $post->ID, "video_image", $_POST["video_image"] );
		update_post_meta( $post->ID, "duration", hectv_convertDuration( $_POST["duration"] ) );
		update_post_meta( $post->ID, "legacy_media_id", $_POST["legacy_media_id"] );
		update_post_meta( $post->ID, "broadcast_location", $_POST["broadcast_location"] );
		update_post_meta( $post->ID, "internal_id", $_POST["internal_id"] );
		
		update_post_meta( $post->ID, "youtube_id", $_POST["youtube_id"] );
		update_post_meta( $post->ID, "vimeo_id", $_POST["vimeo_id"] );
		
		update_post_meta( $post->ID, "video_files", $_POST["video_files"] );
		update_post_meta( $post->ID, "sbr_file", $_POST["sbr_file"] );
		update_post_meta( $post->ID, "media_type", $_POST["media_type"] );
		
		update_post_meta( $post->ID, "ga_adjust", $_POST["ga_adjust"] );

		if( $_POST["media_type"] == "sbr" && $_POST['playlist_type'] == 1 ){

			update_post_meta( $post->ID, "smil_file", "" );

		}

		if( is_array( $_POST["video_files"]['location'] ) && count( $_POST["video_files"]['location'] ) > 1 ){

			$url  = $_POST["video_files"]['location'][0];
			$path = str_replace( basename( $url ), "", $url );

			$fileName = basename( $_POST["video_files"]['location'][0] );
			$fileName = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $fileName );
			$fileName = str_replace( $_POST["video_files"]['bitrate'][0] , "", $fileName );
			$fileName = trim( $fileName, "_");

			$smilFile = hectv_createSmilFile( $_POST["video_files"], $fileName, $path );

			update_post_meta( $post->ID, "smil_file", $smilFile );

		}

		/* Segment / Link Processing */

		$segmentReady = array();

		foreach( $_POST["segment"]["title"] as $sindex => $segment ){

			$segmentReady["title"][$sindex] = utf8_encode( $_POST["segment"]["title"][$sindex] );
			$segmentReady["inpoint"][$sindex] = hectv_convertDuration( $_POST["segment"]["in-point"][$sindex] );
			$segmentReady["description"][$sindex] = utf8_encode( $_POST["segment"]["description"][$sindex] );
			$segmentReady["slug"][$sindex] = strtolower( utf8_encode( preg_replace( "/[^a-zA-Z0-9-\s]/", "", str_replace( " ", "-", $_POST["segment"]["title"][$sindex] ) ) ) );

		}

		update_post_meta( $post->ID, "segments", $segmentReady );

		/* Education Processing */
		update_post_meta( $post->ID, "objectives", $_POST["objective"] );
		update_post_meta( $post->ID, "education_page_id", $_POST["education_page_id"] );
		update_post_meta( $post->ID, "education_page_copy", $_POST["education_page_copy"] );

		/* New Segment Processing */

		update_post_meta( $post->ID, "segment_child", $_POST["segment_child"] );
		
		/* Admin Options ONLY */
		update_post_meta( $post->ID, "series_title_disable", $_POST['series_title_disable'] );
		update_post_meta( $post->ID, "video_embed", $_POST['video_embed'] );
		update_post_meta( $post->ID, "playlist_type", $_POST['playlist_type'] );
		update_post_meta( $post->ID, "media_type", $_POST['media_type'] );


	}

}

add_filter( 'wp_unique_post_slug_is_bad_hierarchical_slug', 'lb_playlist_is_bad_hierarchical_slug', 1, 4 );

function lb_playlist_is_bad_hierarchical_slug( $is_bad_hierarchical_slug, $slug, $post_type, $post_parent ) {

	if ( $post_type == 'lb_playlist' ){

		return false;

	}else{

	    return $is_bad_hierarchical_slug;

    }

}

add_filter( 'manage_lb_playlist_posts_columns', 'lb_playlist_columns' ) ;

function lb_playlist_columns( $columns ) {

	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __( 'Video' ),
// 		'id' => __( 'ID' ),
// 		'series' => __( 'Series' ),
		'duration' => __( 'Duration' ),
		'status' => __( 'Status' ),
		'date' => __( 'Date' )
	);

	return $columns;

}

add_action( 'manage_lb_playlist_posts_custom_column', 'lb_playlist_manage_columns', 10, 2 );

function lb_playlist_manage_columns( $column, $post_id ) {

	global $post;

	switch( $column ) {

		case "id":

		break;

	}

}

?>