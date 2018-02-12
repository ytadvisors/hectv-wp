<?php
	
add_action('template_redirect', 'lb_logged_in_redirect');

function lb_logged_in_redirect(){
	
	if ( is_user_logged_in() && is_page('user-log-in') ){
		
		$url = site_url('/user-profile/');	
		wp_redirect( $url, 301 );
	
	} 
	
}

?>