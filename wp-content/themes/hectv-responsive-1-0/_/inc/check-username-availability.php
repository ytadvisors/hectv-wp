<?php 

add_action( 'init', 'lb_check_username_availability' );

function lb_check_username_availability(){

	if( $_POST['action'] != 'check-username' ){

		return;

	}

	$username = $_POST['username'];

	if( username_exists( $username ) ){
		
		//display errors
		$response = array( 'available' => false );
		
	} else {
				
		//username available
		$response = array( 'available' => true );

	}
	
	echo json_encode( $response );

	die;

}

?>