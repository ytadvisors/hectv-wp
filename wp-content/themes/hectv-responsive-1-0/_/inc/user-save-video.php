<?php 

add_action( 'init', 'lb_user_save_video' );

function lb_user_save_video(){

	if( $_REQUEST['action'] != 'save-video' || $_REQUEST['action'] != 'remove-saved-video' && !isset( $_REQUEST['videoID'] ) || !is_numeric( $_REQUEST['videoID'] ) ){

		return;

	}
	

	$user_ID = get_current_user_id();
	
	if( $user_ID == 0 ){
		
		return;
		
	}
	
	$video_ID = $_REQUEST['videoID'];
	
	$saved_videos = get_user_meta( $user_ID, 'saved_videos', true );
	
	if ( !is_array( $saved_videos ) ){
		
		$saved_videos = array();
		
	}
	
	if ( array_search( $video_ID, $saved_videos ) !== false ){
		
		$response = array( 'saved' => false, 'errors' => 'Video already saved!' );
		
		echo json_encode( $response );
	
		die;
		
	}
	
	$saved_videos[] = $video_ID;
	$save_video     = update_user_meta( $user_ID, 'saved_videos', $saved_videos );
	
	if( $save_video == true ){
		
		$response = array( 'saved' => true, 'videos' => $saved_videos );
		
	}else{
		
		$response = array( 'saved' => false );
		
	}

	echo json_encode( $response );

	die;

}

?>