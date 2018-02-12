<?php 

add_action( 'init', 'lb_user_sign_up' );

function lb_user_sign_up() {
	
	if( $_POST['action'] !== 'user-sign-up' ){
		
		return;
		
	}
	
	global $wpdb;
	
  	if (isset( $_POST["lb_user_sign_up"] ) && wp_verify_nonce($_POST['hectv_register_nonce'], 'hectv-register-nonce')) {
		$first_name       = sanitize_text_field( $_POST['first-name'] );
		$last_name        = sanitize_text_field( $_POST['last-name'] );
		$username         = sanitize_text_field( $_POST['username'] );
		$email            = sanitize_text_field( $_POST['email'] );
		$password         = sanitize_text_field( $_POST['password'] );
		$confirm_password = sanitize_text_field( $_POST['confirm_password'] );
 
		// this is required for username checks
		require_once(ABSPATH . WPINC . '/registration.php');
 
		if( username_exists( $user_login ) ) {
			// Username already registered
			errors()->add('username_unavailable', __('Username already taken'));
		}
		if( !validate_username( $username ) ) {
			// invalid username
			errors()->add('username_invalid', __('Invalid username'));
		}
		if( $username == '' ) {
			// empty username
			errors()->add('username_empty', __('Please enter a username'));
		}
		if( !is_email( $email ) ) {
			//invalid email
			errors()->add('email_invalid', __('Invalid email'));
		}
		if( email_exists( $email ) ) {
			//Email address already registered
			errors()->add('email_used', __('Email already registered'));
		}
		if( $password == '' ) {
			// passwords do not match
			errors()->add('password_empty', __('Please enter a password'));
		}
		if( $password != $confirm_password ) {
			// passwords do not match
			errors()->add('password_mismatch', __('Passwords do not match'));
		}
 
		$errors = errors()->get_error_messages();
 
		// only create the user in if there are no errors
		if( empty($errors) ) {
			
			echo 'SUCCESS WOW';
 
			$new_user_id = wp_insert_user(array(
					'user_login'		=> $username,
					'user_pass'	 		=> $password,
					'user_email'		=> $email,
					'first_name'		=> $first_name,
					'last_name'			=> $last_name,
					'user_registered'	=> date('Y-m-d H:i:s'),
					'role'				=> 'hectv-user'
				)
			);
			
			if($new_user_id) {
				// send an email to the admin alerting them of the registration
				wp_new_user_notification($new_user_id);
 
				// log the new user in
				wp_setcookie($username, $password, true);
				wp_set_current_user($new_user_id, $username);	
				do_action('wp_login', $username);
 
				// send the newly created user to the home page after logging them in
// 				wp_redirect(home_url()); exit;
			}
 
		}
 
	}
	
	function errors(){
		static $wp_error; // Will hold global variable safely
		return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
	}
	
	die;
	
}
	
	
		

?>