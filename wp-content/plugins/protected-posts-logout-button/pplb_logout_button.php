<?php 
/*
	Plugin Name: Protected Posts Logout Button
	Plugin URI: http://omfgitsnater.com/protected-posts-logout-button/
	Description: A plugin built to add a logout button automatically to protected posts.
	Version: 1.4.1
	Author: Nate Reist
	Author URI: http://omfgitsnater.com
*/

if ( !defined( 'PPLB_TEMPLATE_PATH' ) ) {
	define( 'PPLB_TEMPLATE_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) . 'templates/' );
}

if ( !class_exists( 'PPLB_Plugin' ) ) {
	class PPLB_Plugin {

		function __construct(){
			add_action('init', array( $this, 'add_pplb_filter' ), 10, 1);						// add the filter on load.

			add_action('admin_menu', array( $this, 'pplb_add_admin' ) );
			add_action('wp_enqueue_scripts', array( $this, 'pplb_logout_js' ) ); 					// adds the script to the header.
			add_action('wp_ajax_nopriv_pplb_logout', array( $this, 'pplb_protected_logout' ) ); 	// logout for non-logged in wp users
			add_action('wp_ajax_pplb_logout', array( $this, 'pplb_protected_logout' ) ); 		// logout for logged in wp users
			
			add_shortcode('logout_btn', array( $this, 'pplb_logout_button' ) );					// adds the shortcode.
			
			add_filter( 'post_password_expires', array( $this, 'pplb_change_postpass_expires' ), 10, 1 );
			
			add_action( 'admin_init',  array( $this, 'pplb_options_save' ) );
		}
		
		/*
			Add the logout button to posts which require a password and the password has been provided.
		*/
		
		function pplb_logout_filter( $content ){
			global $post;
			$html = '';
			
			//Check if the post has a password and we are inside the loop.
			if ( !empty( $post->post_password) && in_the_loop() ){
				//Check to see if the password has been provided.
				if ( !post_password_required( get_the_ID() ) ) {
					//add the logout button to the output.
					$options = get_option('pplb_options');
					$class = ( array_key_exists('pplb_button_class', $options) ) ? $options['pplb_button_class'] : '';
					$text = (array_key_exists('pplb_button_text', $options)) ? $options['pplb_button_text'] : 'logout';
					if ( empty( $text ) ) {
						$text = 'logout';
					}
					$html .= ' <input type="button" class="button logout '.esc_attr($class).'" value="'.esc_attr( $text ).'">';
				}
			}
			return $html.$content;
			
		}
		
		/* 
			Adds for use in wordpress shortcode or php.
		*/
		
		function pplb_logout_button(){
			$qid = get_queried_object_id();
			$qpost = get_post($qid);
			$html = '';
			// Check if the post has a password
			if ( !empty( $qpost->post_password ) ) {
				// Check to see if the password has been provided.
				if(!post_password_required($qid)){
					$options = get_option('pplb_options');
					$class = (array_key_exists('pplb_button_class', $options)) ? $options['pplb_button_class'] : '';
					$text = (array_key_exists('pplb_button_text', $options)) ? $options['pplb_button_text'] : 'logout';
					if ( empty( $text ) ) {
						$text = 'logout';
					}
					$html = ' <input type="button" class="button logout '.esc_attr($class).'" value="'.esc_attr( $text ).'">';
				}
			}
			return $html;
			
		}
		
		/*
			Ajax function to reset the cookie in wordpress.
		*/
		
		function pplb_protected_logout(){
			// Set the cookie to expire ten days ago... instantly logged out.
			setcookie( 'wp-postpass_' . COOKIEHASH, stripslashes( '' ), time() - 864000, COOKIEPATH );
			$options = get_option('pplb_options');
			$pplb_alert = (array_key_exists('pplb_alert', $options)) ? $options['pplb_alert'] : 'no';
			$log = isset( $options['pplb_debug'] ) ? $options['pplb_debug'] : 0;
			
			$response = array(
				'status' 	=> 0,
				'message' 	=> '',
				'log'           => $log
			);
			
			if ( $pplb_alert == 'yes' ) {
				$response['status'] = 1;
				$response['message'] = stripslashes( $options['pplb_message'] );
			}
			else {
				$response['status'] = 0;
				$response['message'] = '';
			}
			wp_send_json( $response );
		}
		
		/*
			Enqueue the scripts.
		*/
		function pplb_logout_js(){
			wp_register_script( 'pplb_logout_js', plugins_url( '/logout.js', __FILE__ ), array('jquery'), null, true );
			wp_enqueue_script( 'pplb_logout_js' );
			wp_localize_script( 'pplb_logout_js', 'pplb_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}
		
		/*
			Filter the expiration time if necessary based upon the option.
		*/
		function pplb_change_postpass_expires( $expire ){
			$new_expire = get_option( 'pplb_pass_expires', false );
			if ( $new_expire !== false && is_numeric( $new_expire ) ) {
				return time() + $new_expire;
			}
			else {
				return $expire;
			}
		}
		
		/*
		        Save on admin init
		*/
		function pplb_options_save(){
		        if ( isset( $_POST['pplb_action'] ) ) {
				//update the option.
				$options = array();
				$options['pplb_alert'] = ( array_key_exists('pplb_alert', $_POST) ) ? $_POST['pplb_alert']: 'no';
				$options['pplb_message'] = esc_js( $_POST['pplb_message'] );
				$options['pplb_debug'] = ( array_key_exists('pplb_debug', $_POST) ) ? $_POST['pplb_debug']: 0;
				$options['pplb_button_class'] = esc_attr($_POST['pplb_button_class']);
				$options['pplb_button_text'] = !empty($_POST['pplb_button_text']) ? esc_attr($_POST['pplb_button_text']) : 'logout';
				
				
				update_option('pplb_options', $options);
				
				$expire = ( isset( $_POST['pplb_pass_expires'] ) && !empty( $_POST['pplb_pass_expires'] ) ) ? $_POST['pplb_pass_expires']: false;
				update_option('pplb_pass_expires', $expire );
				
				$filter = isset($_POST['pplb_button_filter']) ? $_POST['pplb_button_filter']: 'yes';
				update_option('pplb_button_filter', $filter);
				$redirect = add_query_arg( array( 'message' => 1 ) );
				wp_redirect( $redirect );
				exit();
			}
		}
		/*
			The settings page in admin
		*/
		function pplb_settings_page(){
			include ( PPLB_TEMPLATE_PATH . 'pplb-options.php' );
		}
		
		/*
			Add the admin page
		*/
		function pplb_add_admin(){
			add_options_page('Protected Post Logout Settings', 'Protected Post Logout', 'manage_options', 'pplb-settings-page', array( $this, 'pplb_settings_page' ) );
		}
		
		/*
			Activation hook to install the options if they haven't been installed before.
		*/
		function install_pplb_options(){
			if ( !get_option('pplb_options') ) {
				$options = array(
					'pplb_alert' => 'no',
					'pplb_message' => 'Successfully logged out.',
					'pplb_button_class' => ''
				);
				update_option('pplb_options', $options);
			}
			if ( !get_option('pplb_button_filter') ) {
				update_option('pplb_button_filter', 'yes');
			}
		}
		
		/* 
			Only add the filter if the option declares it
		*/
		function add_pplb_filter(){
			if ( !get_option( 'pplb_button_filter' ) ) {
				// if the option isn't set, assume we want it there.
				update_option('pplb_button_filter', 'yes');
			}
			$add_filter = get_option('pplb_button_filter');
			if ( $add_filter == 'yes' ) {
				add_filter('the_content', array( $this, 'pplb_logout_filter' ), 9999, 1); 	// adds the button.
			}
		}
	}
}

function pplb_logout_button() {
	return do_shortcode( '[logout_btn]' );
}

function init_pplb(){
	new PPLB_Plugin();
}

add_action( 'init', 'init_pplb', 9, 1 );

register_activation_hook( __FILE__ , array( 'PPLB_Plugin', 'install_pplb_options' ) );		// set up options
?>