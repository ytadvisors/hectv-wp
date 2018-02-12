<?php

if( $_POST['action'] == "interactive" ){

	add_action( 'init', 'interactive_tool', 5 );

}

function interactive_tool(){

	header("Content-type: application/json");

	switch( $_POST['type'] ){

		case "initialize":

			$userName        = sanitize_text_field( $_POST['user-name'] );
			$userEmail       = sanitize_text_field( $_POST['user-email'] );
			$userLocation    = sanitize_text_field( $_POST['user-location'] );

			if( !is_email( $userEmail ) ){


				$response['feedback'] = "invalid email";
				echo json_encode($response);

				die;

			}

			global $wpdb;

			$table_name = $wpdb->prefix . "interactive_users";

			$securityKey = md5( time() . $userEmail . $_SERVER['HTTP_USER_AGENT'] );

			if( $_POST['email-updates'] == 1 ){

				global $wpdb;

				$table_name = $wpdb->prefix . "sml";

				$wpdb->insert( $table_name, array( 'sml_name' => $userName, 'sml_email' => $userEmail ), array( '%s', '%s' ) );

			}

			$wpdb->insert(
				$table_name,
				array(
					'userSecurityKey' => $securityKey,
					'userName' => $userName,
					'userEmail' => $userEmail,
					'userLocation' => $userLocation,
					'userIP' => $_SERVER['REMOTE_ADDR'],
					'userBrowser' => $_SERVER['HTTP_USER_AGENT'],
					'userTimeJoined' => current_time("mysql"),
					'userLastAction' => current_time("mysql"),
					'userStatus' => 1,
					'userFlags' => 0,

				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d'
				)
			);

			if( $wpdb->insert_id ){

				$response['status']   = true;
				$response['user']     = $wpdb->insert_id;
				$response['security'] = $securityKey;

				global $wpdb;

				$table_name = $wpdb->prefix . "interactive_share";

				$shares = $wpdb->get_results("SELECT * FROM $table_name WHERE shareStatus >= '1' AND shareType <= 5", "OBJECT" );

				if( count( $shares ) > 0 ){

					$response['shares'] = $shares;

				}

			}else{

				$response['status'] = false;

			}

			echo json_encode($response);

		break;

		case "question":

			$security = sanitize_text_field( $_POST['key'] );
			$userid   = sanitize_text_field( $_POST['user'] );
			$question = sanitize_text_field( $_POST['user-question'] );

			global $wpdb;

			$table_name = $wpdb->prefix . "interactive_users";

			$user = $wpdb->get_results("SELECT * FROM $table_name WHERE userSecurityKey = '$security' AND id = '$userid'");

			if( count( $user ) == 0 ){

				$response['status'] = false;

			}else{

				global $wpdb;

				$table_name = $wpdb->prefix . "interactive_posts";

				$wpdb->insert(
					$table_name,
					array(
						'postAuthor' => $userid,
						'postContent' => $question,
						'postStatus' => 1
					),
					array(
						'%d',
						'%s',
						'%d'
					)
				);

				$response['status'] = true;
				$response['id'] = $wpdb->insert_id;
				$response['question'] = $question;
				$response['message'] = "<b>Thanks, we received your question.</b>";

			}

			echo json_encode($response);


		break;


		case "update":

			$response['live'] = ( get_option( "interactive_status" ) == 1 ) ? true : false;

			$security = sanitize_text_field( $_POST['key'] );
			$userid   = sanitize_text_field( $_POST['user'] );

			global $wpdb;

			$table_name = $wpdb->prefix . "interactive_users";

			$user = $wpdb->get_results("SELECT * FROM $table_name WHERE userSecurityKey = '$security' AND id = '$userid'");

			if( count( $user ) == 0 ){

				$response['status'] = false;
				echo json_encode($response);
				die;

			}

			global $wpdb;

			$table_name = $wpdb->prefix . "interactive_users";

			$wpdb->update(
				$table_name,
				array(
				'userLastAction' => current_time("mysql", true)
				),
				array(
				'userSecurityKey' => $security,
				'id' => $userid ),
				array(
				'%s'
				),
				array(
				'%s',
				'%d'
				)
			);

			global $wpdb;

			$table_name = $wpdb->prefix . "interactive_share";

			$shares     = $wpdb->get_results("SELECT * FROM $table_name WHERE shareStatus >= '1' AND shareType <= 5", "OBJECT" );


			global $wpdb;

			$messages   = $wpdb->get_results("SELECT * FROM $table_name WHERE shareType = '6' AND shareRecipient = '$userid' AND shareStatus = '1'", "OBJECT" );

			$response['shares']   = $shares;
			$response['messages'] = $messages;

			echo json_encode($response);

		break;

	}

	die;

}

?>