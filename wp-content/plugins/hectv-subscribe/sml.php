<?php
/*
Plugin Name: HEC-TV Subscribers
Plugin URI: http://www.lovehatecreative.com
Description: Manage e-mail subscribers for HEC-TV.
Version: 1.0
Author: Kameron Zach (Love/Hate Inc)
Author URI: http://www.lovehatecreative.com
*/

function sml_install(){

    global $wpdb;
    $table = $wpdb->prefix."sml";
    $structure = "CREATE TABLE $table (
        id INT(9) NOT NULL AUTO_INCREMENT,
        sml_name VARCHAR(200) NOT NULL,
        sml_email VARCHAR(200) NOT NULL,
	UNIQUE KEY id (id)
    );";
    $wpdb->query($structure);

}

register_activation_hook( __FILE__, 'sml_install' );

function register_sml_menu() {

	add_menu_page('Subscribers', 'Subscribers', 'add_users', dirname(__FILE__).'/index.php', '',   plugins_url('sml-admin-icon.png', __FILE__), 58);

}

add_action('admin_menu', 'register_sml_menu');

add_filter( 'init', 'addSubscriber' );

function addSubscriberInternal($name, $email){

	$name  = sanitize_text_field( $_POST['name'] );
	$email = sanitize_text_field( $_POST['email'] );


	if (is_email($email)) {

		global $wpdb;

		$table_name = $wpdb->prefix . "sml";

		$wpdb->insert( $table_name, array( 'sml_name' => $name, 'sml_email' => $email ), array( '%s', '%s' ) );


		if( $wpdb->insert_id ){

			return true;
			
			$email_to          = 'info@hectv.org';
			$email_subject     = 'New Mailing List Subscriber';
			$email_message     = "Hello,\n\n A new subscriber has been added.\n\n\nDetails:\n$name\n$email";
			
			wp_mail( $email_to, $email_subject, $email_message );

		}else{

			return false;

		}

	}

}


function addSubscriber(){

	if( $_POST['action'] == "email-subscribe" ) {

		global $wpdb;

		$name  = sanitize_text_field( $_POST['name'] );
		$email = sanitize_text_field( $_POST['email'] );

		if (is_email($email)) {

			global $wpdb;
			
			$table_name = $wpdb->prefix . "sml";

			$wpdb->insert( $table_name, array( 'sml_name' => $name, 'sml_email' => $email ), array( '%s', '%s' ) );

			if( $wpdb->insert_id ){

				$response['status'] = true;
				$response['header'] = "E-Mail Added";
				$response['message'] = "You'll start receiving e-mail communications from Higher Education Channel.";
				
				$email_to          = 'info@hectv.org';
				$email_subject     = 'New Mailing List Subscriber';
				$email_message     = "Hello,\n\n A new subscriber has been added.\n\n\nDetails:\n$name\n$email";
				
				wp_mail( $email_to, $email_subject, $email_message );

			}else{

				$response['status'] = false;
				$response['header'] = "Something went wrong...";
				$response['message'] = "Looks like we already have an email on file for you. <a href=\"mailto:info@hectv.org\">Contact us</a> if this is in error.";

			}

		}else{

			$response['status'] = false;
			$response['header'] = "Something went wrong...";
			$response['message'] = "The email you provided was incorrect. <a href=\"mailto:info@hectv.org\">Contact us</a> if this is in error.";

		}

		echo json_encode($response);

		die;

	}

}


?>