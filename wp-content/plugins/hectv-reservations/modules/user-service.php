<?php

	if( $_POST['action'] == "start_rsvp" ){

		add_action( 'init', 'hectv_rsvp_submit', 5 );

	}

	function hectv_rsvp_submit(){

		$userName        = sanitize_text_field( $_POST['rsvp-name'] );
		$userEmail       = sanitize_text_field( $_POST['rsvp-email'] );
		$userSchool      = sanitize_text_field( $_POST['rsvp-school'] );
		$userTimeSelect  = sanitize_text_field( $_POST['rsvp-time'] );
		$userDate        = sanitize_text_field( $_POST['rsvp-date'] );
		$userAttendees   = sanitize_text_field( $_POST['rsvp-attendees'] );
		$episodeID       = sanitize_text_field( $_POST['episodeID'] );
		$seriesID        = sanitize_text_field( $_POST['seriesID'] );

		if( !empty( $userName ) && !empty( $userEmail ) ){

			global $wpdb;
			
			if( $_POST['rsvp-subscribe'] ){
	
				$table_name = $wpdb->prefix . "sml";
	
				$wpdb->insert( $table_name, array( 'sml_name' => $userName, 'sml_email' => $userEmail ), array( '%s', '%s' ) );
	
			}

			$table_name = $wpdb->prefix . "reservations";

			$wpdb->insert( $table_name, array( 'name' => $userName, 'emailAddress' => $userEmail, 'school' => $userSchool, 'attendTime' => $userTimeSelect, 'episodeID' => $episodeID, 'attendees' => $userAttendees, 'state' => 1, 'seriesID' => $seriesID ), array( '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ) );

		}

		if( $wpdb->insert_id ){

			$response['status']  = true;
			$response['header']  = "Reservation Received";
			$response['message'] = "We received your reservation, we'll see you at $userTimeSelect on $userDate.";


		}else{

			$response['status']  = false;
			$response['header']  = "Something went wrong...";
			$response['message'] = "The reservation couldn't be confirmed. <a href=\"mailto:info@hectv.org\">Contact us</a> if this is in error.";

		}
        
        notify_admin();
		
        header("Content Type: application/json");

		echo json_encode($response);

		die;

	}

    function notify_admin() {
        $userName        = sanitize_text_field( $_POST['rsvp-name'] );
		$userEmail       = sanitize_text_field( $_POST['rsvp-email'] );
		$userSchool      = sanitize_text_field( $_POST['rsvp-school'] );
		$userTimeSelect  = sanitize_text_field( $_POST['rsvp-time'] );
		$userDate        = sanitize_text_field( $_POST['rsvp-date'] );
		$userAttendees   = sanitize_text_field( $_POST['rsvp-attendees'] );
		$episodeID       = sanitize_text_field( $_POST['episodeID'] );
		$seriesID        = sanitize_text_field( $_POST['seriesID'] );
        
        $to = 'live@hectv.org';
        $subject = 'New reservation for event: '.$episodeID;
        $headers = array('From: live@hectv.org', 'Content-type: text/html;charset=utf-8');
        $message = 'Hi!<br><br>
        The user '.$userName.' (<a href="mailto:'.$userEmail.'">'.$userEmail.'</a>) has just made a reservation for an event on HECTV.org for '.$userDate.' at '.$userTimeSelect.'<br><br>
        <h3><u>Details:</u></h3>
        <b>Name:</b>'.$userName.'<br>
        <b>Episode:</b> #'.$episodeID.' <i><a href="'.get_the_permalink( $episodeID ).'">'.get_the_title( $episodeID ).'</a></i> <br>
        <b>Series ID:</b> #'.$seriesID.'<br>
        <b>Time and Date of Reservation:</b> '.$userTimeSelect.' on '.$userDate;
        $message .= ( $userSchool!=='' )?'<br><b>School:</b> '.$userSchool:'';
        $message .= ( $userAttendees!=='' )?'<br><b>Number of Attendees:</b> '.$userAttendees:'';

                    
        wp_mail ( $to, $subject, $message, $headers );
    }

?>