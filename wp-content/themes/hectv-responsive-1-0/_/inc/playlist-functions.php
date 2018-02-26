<?php
function hectv_create_segment_html( $segments, $current = 0 ){

	if( empty( $segments ) ){

		return false;

	}

	if( is_array( $segments ) && count( $segments ) > 0 ){

		if( !empty( $segments[0] ) ){

			$loaded = array();
			foreach( $segments as $index => $segment ){

				$segment_obj     = get_post( $segment );
				$segment_data    = get_post_custom( $segment );

				$image_url       = wp_get_attachment_image_src( $segment_data['video_image'][0], "video-thumb" );
				$thumbnail       = ( $image_url ) ? $image_url[0] : get_bloginfo('template_directory') . "/_/graphics/ui-no-image.jpg";
                
                $segment_files = array();
                
                if( $segment_data['media_type'][0] == "sbr" ){ 
                    
                    $segment_files[]  = "http://hectv.bc.cdn.bitgravity.com/" . $playlist['sbr_file'][0]; 
                
                }else if( $segment_data['smil_file'][0] ){
                    
                    $segmentVideo = unserialize( $segment_data["segment_files"][0] ); 
                    
                    $segment_files[]  = "http://hectv.bc.cdn.bitgravity.com/" . $segmentVideo['location'][1];
                }


                
				if( $segment_obj->post_status == "publish" || is_user_logged_in()){

					$active = ( $index == $current ) ? 'current':'';
					$html   = '<div class="video-clip ' . $active . '" rel="' . $index . '" id="clip-' . $segment . '" data-segment-youtube-id="' . $segment_data['youtube_id'][0] .'" data-segment-vimeo-id="' . $segment_data['vimeo_id'][0] .'" data-segment-file="' . $segment_files[0] .'">';

		        		$html .= '<div class="img-wrap">';

							$html .= '<a href="' . get_permalink( $segment ) . '">';

// 			        			$html .= '<div class="play-wrap">';
			        				$html .= '<img class="play" src="' . get_bloginfo('template_directory') . '/_/graphics/play-button.png">';
// 			        			$html .= '</div>';

			            		$html .= '<img src="' . $thumbnail . '">';

							$html .= '</a>';
// 							$html .= '<div class="notifier"></div>';
		        		$html .= '</div>';

		        		$html .= '<div class="video-clip-info">';
			        	$html .= '<a href="' . get_permalink( $segment ) . '">';
				            $html .= '<h3 class="show-title">' . get_the_title( $segment ) . '</h3>';
			        		$html .= '</a>';
							$html .= '<span class="time">' . hectv_formatDuration( $segment_data['duration'][0] ) . '</span>';
						$html .= '</div>';

					$html .= '</div>';

					echo $html;

				}

			}

		}

	}

}


add_filter('post_type_link', 'lb_playlist_post_permalink', 10, 4);

function lb_playlist_post_permalink($post_link, $post, $leavename, $sample) {

	if ( $post->post_type === 'lb_playlist' ) {

		if( $post->post_parent ) {

			$post_link  = site_url("/") . "watch" . "/" ;
			$post_link .= get_post_field( "post_name", $post->post_parent ) . "/";
			$post_link .= $post->post_name . "/";
			$post_link .= $post->ID . "/";

		}else{

			$name       = sanitize_title( $post->post_name ? $post->post_name : $post->post_title, $post->ID );

			$post_link  = site_url("/") . "watch" . "/" ;
			$post_link .= "uncategorized" . "/" ;
			$post_link .= $name . "/";
			$post_link .= $post->ID . "/";

		}

	}elseif( $post->post_type === 'lb_video' ) {

		if( $post->post_parent ) {

			$post_link  = get_permalink( $post->post_parent );
			$post_link .= $post->post_name . "/";

		} else {



		}

	}

	return $post_link;

}


function lb_playlist_segment_edit_button($wp_admin_bar){

	global $post;

	if( $post->post_type == "lb_video" && !empty( $post ) ){

		$args = array(
			'id' => 'edit-playlist',
			'title' => 'Edit Playlist',
			'href' => '/wp-admin/post.php?post=' . $post->post_parent . '&action=edit',
			'meta' => array(
				'class' => 'custom-button-class'
				)
		);

		$wp_admin_bar->add_node($args);

	}

}

add_action('admin_bar_menu', 'lb_playlist_segment_edit_button', 99);

/*
add_action( 'init', 'lb_create_topic_taxonomies', 0 );
function lb_create_topic_taxonomies() {

	$topicslabels = array(
		'name' => _x( 'Topics', 'taxonomy general name' ),
		'singular_name' => _x( 'Topic', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search Topics' ),
		'all_items' => __( 'All Topics' ),
		'parent_item' => __( 'Parent Topic' ),
		'parent_item_colon' => __( 'Parent Topic:' ),
		'edit_item' => __( 'Edit Topic' ),
		'update_item' => __( 'Update Topic' ),
		'add_new_item' => __( 'Add New Topic' ),
		'new_item_name' => __( 'New Topic Name' ),
	);

	register_taxonomy( 'topic', array( 'lb_playlist', 'lb_video' ), array(
		'hierarchical' => true,
		'labels' => $topicslabels,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'watch/topic', 'with_front' => false ),
	));

}

add_action( 'init', 'lb_create_video_keyword_taxonomies', 0 );

function lb_create_video_keyword_taxonomies() {

	$topicslabels = array(
		'name' => _x( 'Keywords', 'taxonomy general name' ),
		'singular_name' => _x( 'Keyword', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search Keywords' ),
		'all_items' => __( 'All Keywords' ),
		'parent_item' => __( 'Parent Keyword' ),
		'parent_item_colon' => __( 'Parent Keyword:' ),
		'edit_item' => __( 'Edit Keyword' ),
		'update_item' => __( 'Update Keyword' ),
		'add_new_item' => __( 'Add New Keyword' ),
		'new_item_name' => __( 'New Keyword Name' ),
	);

	register_taxonomy( 'keyword', array( 'lb_video', 'lb_playlist' ), array(
		'labels' => $topicslabels,
		'show_ui' => true,
		'query_var' => true,
		'show_in_nav_menus' => true,
		'rewrite' => array( 'slug' => 'watch/keyword', 'with_front' => false ),
	));

}*/

add_action( 'restrict_manage_posts', 'lb_add_series_selection_to_admin' );

function lb_add_series_selection_to_admin(){

    if( $_GET['post_type'] == "lb_playlist" ) {

		wp_dropdown_pages( array( 'child_of' => 16789, 'name' => 'series_parent', 'depth' => 0, 'selected' => $_GET['series_parent'], 'show_option_none' => 'All Series' ) );

    }

    if( $_GET['post_type'] == "page" ) {

		wp_dropdown_pages( array( 'child_of' => 16789, 'name' => 'page_parent', 'depth' => 0, 'selected' => $_GET['page_parent'], 'show_option_none' => 'All Series' ) );

    }



}


add_filter( 'parse_query', 'lb_add_series_selection_to_admin_query' );

function lb_add_series_selection_to_admin_query( $query ){

    global $pagenow;
    $type = 'lb_playlist';

    if( isset( $_GET['post_type'] ) ) {
        $type = $_GET['post_type'];
    }

    if ( 'lb_playlist' == $type && is_admin() && $pagenow == 'edit.php' && isset( $_GET['series_parent'] ) && $_GET['series_parent'] != '') {

        $query->query_vars['post_parent'] = $_GET['series_parent'];

    }

    if ( 'page' == $type && is_admin() && $pagenow == 'edit.php' && isset( $_GET['page_parent'] ) && $_GET['page_parent'] != '') {

        $query->query_vars['post_parent'] = $_GET['page_parent'];

    }

}

?>