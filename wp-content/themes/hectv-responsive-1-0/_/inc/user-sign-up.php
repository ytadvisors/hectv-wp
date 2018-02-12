<?php 
	
require_once(ABSPATH . WPINC . '/registration.php');

add_action( 'init', 'lb_user_sign_up' );

function lb_user_sign_up() {
	
	if( $_POST['action'] !== 'user-sign-up' ){
		
		return;
		
	}
	
	global $wpdb;
	
	$first_name       = sanitize_text_field( $_POST['first-name'] );
	$last_name        = sanitize_text_field( $_POST['last-name'] );
	$username         = sanitize_text_field( $_POST['username'] );
	$email            = sanitize_text_field( $_POST['email'] );
	$password         = sanitize_text_field( $_POST['password'] );
	$confirm_password = sanitize_text_field( $_POST['confirm_password'] );	
	$user_id          = wp_create_user( $username, $password, $email );
	
	if( is_wp_error( $user_id ) ){
		
		$error_string = $user_id->get_error_message();
		
		$url = site_url('/user-sign-up/') . '?errors=' . urlencode($error_string);		
		wp_redirect( $url, 301 );
		
	} else {
	
		wp_update_user( array( 'ID' => $user_id, 'first_name' => $first_name, 'last_name' => $last_name, 'role' => 'subscriber' ) );
			
		$url = site_url('/user-profile/') . '?registered=true';					
		wp_redirect( $url, 301 );
				
		
	}

	die;
	
}

?>