<?php 

add_action( 'init', 'lb_user_remove_saved_video' );

function lb_user_remove_saved_video(){
	
	if( $_REQUEST['action'] != 'remove-saved-video' || $_REQUEST['action'] != 'save-video' && !isset( $_REQUEST['videoID'] ) || !is_numeric( $_REQUEST['videoID'] ) ){

		return;

	}
	
	$user_ID 		  = get_current_user_id();
	$saved_videos     = get_user_meta( $user_ID, 'saved_videos', true );
	$video_ID         = $_REQUEST['videoID'];
	
	$search_index     = array_search( $video_ID, $saved_videos );
	
	echo $index;
	
	if( $search_index !== false ){
		
		unset( $saved_videos[$search_index] );
		
		$remove_video = update_user_meta( $user_ID, 'saved_videos', $saved_videos );
		
		if ( $remove_video == true ){
			
			$response = array( 'removed' => true, 'videos' => $saved_videos );

		}else {
			
			$response = array( 'removed' => false );

		}
		
	}
	
	echo json_encode( $response );
	
	die;
	
}

?>
