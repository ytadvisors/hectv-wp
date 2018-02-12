<?php


function schedule_marquee_callback(){

	header('Content-type: application/json');

	switch( $_GET['type'] ){

		case "find-post":

			$args = array( "post_type" => array( "lb_video", "lb_playlist" ), "posts_per_page" => 30, "post_status" => "publish", "s" => $_GET['query'], "orderby" => "date", "order" => "DESC" );

			$p_results = new WP_Query( $args );

			$service = array( "query" => stripslashes( $_GET['query'] ), "suggestions" => array() );

			foreach( $p_results->posts as $p_result ){

				array_push( $service['suggestions'], array( "value" => $p_result->post_title, "data" => $p_result->ID ) );

			}

			echo json_encode($service);

		break;

		case "insert-post":

			$query_id = ( empty( $_GET['find-by-title-id'] ) ) ? $_GET['recent-post-id'] : $_GET['find-by-title-id'];

			if( empty( $_GET['parent'] ) || empty( $query_id ) ){

				$service['action'] = false;

			}

			$post                = get_post( $query_id );

			$service['action']   = "add";
			$service['parent']   = $_GET['parent'];
			$service['post']     = $query_id;
			$service['headline'] = get_the_title( $query_id );
			$service['cta']      = "Watch Now";

			switch( $post->post_type ){

				case "lb_video":
					$service['photo']  = current( get_post_custom_values( "video_image", $query_id ) );
					$service['imageID']  = current( get_post_custom_values( "video_image", $query_id ) );
					$service['imageURL'] = current( wp_get_attachment_image_src( current( get_post_custom_values( "video_image", $query_id ) ), "full" ) );
					$service['excerpt']  = current( get_post_custom_values( "meta_description", $query_id ) );
					$service['series']   = get_the_title( $post->post_parent );
				break;
				case "lb_playlist":
					$service['photo']  = current( get_post_custom_values( "video_image", $query_id ) );
					$service['imageID']  = current( get_post_custom_values( "video_image", $query_id ) );
					$service['imageURL'] = current( wp_get_attachment_image_src( current( get_post_custom_values( "video_image", $query_id ) ), "full" ) );
					$service['excerpt']  = current( get_post_custom_values( "meta_description", $query_id ) );
					$service['series']   = get_the_title( $post->post_parent );
				break;
				case "post":
					$service['photo']  = current( get_post_custom_values( "video_image", $query_id ) );
					$service['imageID']  = current( get_post_custom_values( "video_image", $query_id ) );
					$service['imageURL'] = current( wp_get_attachment_image_src( current( get_post_custom_values( "post_thumbnail", $query_id ) ), "full" ) );
					$service['excerpt']  = get_the_excerpt( $query_id );
				break;

			}

			echo json_encode($service);

		break;

	}

	die;

}

?>