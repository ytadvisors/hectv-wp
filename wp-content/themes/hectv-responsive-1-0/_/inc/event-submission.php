<?php 

add_action( 'init', 'lb_add_event_submission' );

function lb_add_event_submission() {
	
	if( $_POST['action'] !== 'event-submission' ){
		
		return;
		
	}
		
	global $wpdb;
	
// 	print_r($_POST);
	
	$name              = sanitize_text_field( $_POST['event-name'] );
	$time              = sanitize_text_field( $_POST['time'] );
	$date              = sanitize_text_field( $_POST['date'] );
	$location          = sanitize_text_field( $_POST['location'] );
	$url               = sanitize_text_field( $_POST['url'] );
	$description       = sanitize_text_field( $_POST['description'] );
	$file              = sanitize_text_field( $_POST['event-form-upload'] );
								
	$new_event_id      = wp_insert_post( array( 'post_title' => $name, 'post_type' => 'event', 'post_content' => $description ) );
	$response          = array();
	
	if ( $new_event_id !== false ){
			
		$email_to          = 'info@hectv.org';
		$email_subject     = 'New Event Submitted';
		$email_link        = get_edit_post_link( $new_event_id ); 
		$email_message     = "Hello,\n\n a new event has been drafted in the hectv.org admin. Please review to publish.\n\n" . $email_link;
		
		update_post_meta( $new_event_id, 'venue', $location );
		update_post_meta( $new_event_id, 'web_address', $url );
		update_post_meta( $new_event_id, 'event_time', $time );
		update_post_meta( $new_event_id, 'user_submitted_date', $date );
		
		wp_mail( $email_to, $email_subject, $email_message );
		
		if ( !empty( $_FILES['event-form-upload']['name'] ) ){
		
			if ( ! function_exists( 'wp_handle_upload' ) ) {
			    require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}
			
			$uploaded_file    = $_FILES['event-form-upload'];
			$upload_overrides = array( 'test_form' => false );
			$move_file        = wp_handle_upload( $uploaded_file, $upload_overrides );
			$allowed_items    = array('doc', 'docx', 'pdf');
			$ext              = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION); 
			
			update_post_meta( $new_event_id, 'user_submitted_file', $move_file['url'] );
			
			if ( $move_file && !isset( $move_file['error'] ) && in_array( $ext, $allowed_items ) ) {
				
				$url = site_url('/event-submission/') . '?success=true';
				
				wp_redirect( $url, 301 );
			   
			} else {
				
				$message = ( in_array( $ext, $allowed_items ) ) ? 'An unknown error occurred. Contact us.' : 'Only .pdf and .doc files allowed.';
				$message = urlencode( $message );
					    
			    $url = site_url('/event-submission/') . '?errors=true&message='. $message;
				
				wp_redirect( $url, 301 );
			    
			}	
			
		} else {
			
			$url = site_url('/event-submission/') . '?success=true';
				
			wp_redirect( $url, 301 );
			
		}
		
	} else {
		
		$message = 'An unknown error occurred. Contact us.';
		$message = urlencode( $message );
				    
	    $url = site_url('/event-submission/') . '?errors=true&message='. $message;
		
		wp_redirect( $url, 301 );
		
	}
	
	die;
	
}	

?>