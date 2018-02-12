<?php
	
add_action('template_redirect', 'lb_logged_out_redirect');

function lb_logged_out_redirect(){
	
	if ( !is_user_logged_in() && is_page('user-profile') ){
		
		$url = site_url('/user-log-in/') . '?logged-in=false';	
		wp_redirect( $url, 301 );
	
	} 
	
}

?>