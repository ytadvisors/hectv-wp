<?php

header('Content-type: application/json');

function hectv_interactive_callback(){

	switch( $_REQUEST['type'] ){

		case "composer":

			global $wpdb;

			$table_name = $wpdb->prefix . "interactive_share";

			$wpdb->insert( $table_name, array( 'shareType' => $_POST['shareType'], 'shareTitle' => $_POST['shareDescription'], 'shareAddress' => $_POST['shareAddress'], 'shareStatus' => 1 ), array( '%d', '%s', '%s', '%d' ) );

			$response['id'] = $wpdb->insert_id;
			$response['success'] = true;
			$response['title'] = $_POST['shareDescription'];
			$response['address'] = $_POST['shareAddress'];

			echo json_encode($response);

		break;


		case "settings":

			update_option( "interactive_series", $_POST['eventSeries'] );
			update_option( "interactive_title", $_POST['eventTitle'] );
			update_option( "interactive_description", $_POST['eventDescription'] );
			update_option( "interactive_status", $_POST['eventTakeover'] );
			update_option( "interactive_enable", $_POST['eventEnable'] );
			update_option( "interactive_embed", $_POST['eventEmbed'] );

			//purgeWPEvarnish( "http://www.hectv.org/" );

			$response['response'] = "success";

			if( $_REQUEST['eventClear'] == 1 ){

				global $wpdb;

				$table_name = $wpdb->prefix . "interactive_posts";

				$wpdb->update( $table_name, array( 'postStatus' => 0 ), array( 'postStatus' => 1 ), array( '%d' ),  array( '%d' ) );

				global $wpdb;

				$table_name = $wpdb->prefix . "interactive_users";

				$wpdb->update( $table_name, array( 'userStatus' => 0 ), array( 'userStatus' => 1 ), array( '%d' ),  array( '%d' ) );

				global $wpdb;

				$table_name = $wpdb->prefix . "interactive_share";

				$wpdb->update( $table_name, array( 'shareStatus' => 0 ), array( 'shareStatus' => 1 ), array( '%d' ),  array( '%d' ) );

				$response['clear'] = true;

			}

			echo json_encode($response);

		break;

		case "feed":

			global $wpdb;

			$posts_table = $wpdb->prefix . "interactive_posts";
			$users_table = $wpdb->prefix . "interactive_users";

			$posts = $wpdb->get_results("SELECT $posts_table.*, $users_table.* FROM $posts_table, $users_table WHERE $posts_table.postAuthor = $users_table.id AND postStatus = '1'");

			foreach( $posts as $index => $post ){

				$posts[$index]->postContent = stripslashes( stripslashes( $post->postContent ) );

			}

			global $wpdb;

			$users = $wpdb->get_results("SELECT * FROM $users_table WHERE userStatus = '1' ORDER BY id DESC");

			foreach( $users as $index => $user ){

				$unix_last_action = strtotime( $user->userLastAction );

				$users[$index]->state = ( abs( $unix_last_action - current_time("timestamp", true) ) > 120 ) ? "inactive" : "active";

			}

			$response['posts'] = $posts;
			$response['users'] = $users;

			echo json_encode($response);

		break;

		case "pm":

			global $wpdb;

			$table_name = $wpdb->prefix . "interactive_share";

			$wpdb->insert( $table_name, array( 'shareType' => 6, 'shareRecipient' => $_POST['destination'], 'shareContent' => $_POST['message'], 'shareStatus' => 1 ), array( '%d', '%d', '%s', '%d' ) );

			$response['success'] = true;

			echo json_encode($response);

		break;

	}

	die;

}

?>