<?php

add_action('init', 'hectv_mail_handler');


function hectv_mail_handler(){

	if( $_POST['action'] == "contact" ){

		$verify = hectv_mail_check_captcha( array( "response" => $_REQUEST['g-recaptcha-response'], "remoteip" => $_SERVER['REMOTE_ADDR'] ) );

		if( $verify ){

			if( empty( $_POST['name'] ) ||
				empty( $_POST['subject'] ) ||
				empty( $_POST['email'] ) ||
				empty( $_POST['comments'] ) ||
				!is_numeric( $_POST['subject'] ) ){

				$errors = true;

			}

			if( get_field('contact_subjects', 17077 ) ){

				$x = 1;
				while( has_sub_field('contact_subjects', 17077 ) ){

					$destinations[$x] = get_sub_field('e-mail');
					$subjects[$x]      = get_sub_field('subject');
					$x++;

				}

			}

			if( $_POST['email-updates'] ){

				$subscribe_status  = addSubscriberInternal( $_POST['name'], $_POST['email'] );

				$subscribe_message = ( $subscribe_status ) ? "We've added you to our mailing list." : "";

			}

			if( !$errors ){

				$headers   = 'MIME-Version: 1.0' . "\r\n";
				$headers  .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers  .= 'From: ' . get_bloginfo('admin_email') . "\r\n" .'Reply-To: ' . get_bloginfo('admin_email');
				$datetime  = date( "g:iA m-d-Y", current_time("timestamp", false ) );
				$body      = '';
				$subject   = 'Message from HECTV.org - ' . $subjects[$_POST['subject']];

				$body     .= '<b>Name:</b> ' . stripcslashes( $_POST['name'] ) . '</br>';
				$body     .= '<b>E-mail:</b> ' . stripcslashes( $_POST['email'] ) . '</br>';
				$body     .= '<b>School:</b> ' . stripcslashes( $_POST['school'] ) . '</br>';
				$body     .= '<b>Address:</b> ' . stripcslashes( $_POST['address'] ) . '</br>';
				$body     .= '<b>City:</b> ' . stripcslashes( $_POST['city'] ) . '</br>';
				$body     .= '<b>State:</b> ' . stripcslashes( $_POST['state'] ) . '</br>';
				$body     .= '<b>Zipcode:</b> ' . stripcslashes( $_POST['zip'] ) . '</br>';
				$body     .= '<b>Message:</b> ' . stripcslashes( $_POST['comments'] ) . '</br>';

				$mailAction = wp_mail( $destinations[$_POST['subject']], $subject, $body, $headers, false );

				if( $mailAction ){

					$response['status']  = true;
					$response['header']  = "Message sent.";
					$response['message'] = "Thanks for reaching out. $subscribe_message We'll be in touch at our earliest.";

				}else{

					$response['status']  = false;
					$response['header']  = "Something went wrong.";
					$response['message'] = "We were unable to send your message due to a technical error. Feel free to give us a call 314-531-4455.";

				}


			}else{

				$response['status']  = false;
				$response['header']  = "Something went wrong.";
				$response['message'] = "We were unable to send your message due to a technical error. Feel free to give us a call 314-531-4455.";

			}

			header("Content Type: application/json");

			echo json_encode( $response );

			die;


		}

		die;

	}

}

function hectv_mail_check_captcha( $input ) {

	if( !is_array( $input ) ){

		return false;

	}

	$googleUrl = "https://www.google.com/recaptcha/api/siteverify/";
	$secretKey = "6Ldk2P0SAAAAAN71N0wz2d2qSSLml4of4mMw52Bt";

	$formData  = array( "secret" => $secretKey, "response" => $input['response'], "remoteip" => $input['remoteip'] );

	$options = array(
		'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query( $formData ),
		),
	);

	return true;

}

?>