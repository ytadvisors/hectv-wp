<?php 

add_action( 'init', 'lb_user_login' );

function lb_user_login() {
	
	if( $_POST['action'] !== 'user-login' ){
		
		return;
		
	}

	$username = sanitize_text_field( $_POST['username']);
	$password = sanitize_text_field( $_POST['password']);
// 	$user     = get_user_by( 'login', $username );
	
	$creds             = array('user_login' => $username, 'user_password' => $password, 'remember' => true );
	$user_signon       = wp_signon( $creds, false );
			
	if( is_wp_error( $user_signon ) ){
		
		$url = site_url('/user-log-in/') . '?log-in=failed';	
		wp_redirect( $url, 301 );

		
	}else {
		
		$url = site_url('/user-profile/') . '?logged-in=true';					
		wp_redirect( $url, 301 );
		
	}
		
	die;
		
}	

?>