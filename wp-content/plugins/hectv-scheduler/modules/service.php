<?php

function schedule_service_callback(){

	switch( $_GET['type'] ){

		case "series":

			header('Content-type: application/json');

			//$series = get_terms( array( "program" ), array( "search" => $_GET['query'] , "orderby" => "name", "order" => "ASC", "hide_empty" => false ) );

			$args = array( "post_type" => "page", "nopaging" => true, "s" => $_GET['query'], "post_parent" => 16789 );

			$series = new WP_Query( $args );

			$service = array( "query" => stripslashes( $_GET['query'] ), "suggestions" => array(), "slug" => array(), "data" => array() );

			foreach($series->posts as $program){

				array_push( $service['suggestions'], $program->post_title );
				array_push( $service['slug'], $program->post_name );
				array_push( $service['data'], $program->ID );

			}

			echo json_encode($service);

		break;

		case "episode":

			header('Content-type: application/json');

			$args = array( "post_type" => "lb_playlist", "nopaging" => true, "orderby" => "meta_value_num", "meta_key" => "internal_id",  "order" => "DESC", "post_parent" => $_GET['query'], "post_status" => array("publish", "pending", "draft", "auto-draft", "future", "private", "inherit") );

			$episodes = new WP_Query( $args );

			$service = array( "episodes" => array() );

			foreach( $episodes->posts as $index => $episode ){

				$note = ( $episode->post_status != "publish" ) ? " (" . ucfirst( $episode->post_status ) . ")" : "";
				$service["episodes"][$index]["id"]    = $episode->ID;
				$service["episodes"][$index]["title"] = html_entity_decode( $episode->post_title . $note , ENT_QUOTES );

			}

			echo json_encode($service);

		break;

		case "add":

			header('Content-type: application/json');

			global $wpdb;

			$table_name = $wpdb->prefix . "schedule";

			$wpdb->insert( $table_name, array( 'time' => $_GET['time-id'], 'seriesID' => $_GET['series-id'], 'episodeID' => $_GET['episode-id'] ), array( '%d', '%d', '%d' ) );

			$service = array();

			$episode = get_post( $_GET['episode-id'] );

			$episode_data = get_post_custom( $_GET['episode-id'] );

			$series_data = get_page( $_GET['series-id'] );

			$service['insert_id']                 = $wpdb->insert_id;
			$service['time_id']                   = $_GET['time-id'];
			$service['episode_title']             = $episode->post_title;
			$service['series_title']              = $series_data->post_title;
			$service['episode_id']                = $episode->ID;
			$service['series_id']                 = $series_data->ID;
			$service['blocks']                    = ceil( $episode_data['duration'][0] / 1800 );
			$service['duration']                  = $episode_data['duration'][0];
			$service['series_title_disable']      = ( $episode_data['series_title_disable'][0] == 1 ) ? true : false ;

			echo json_encode($service);

		break;

		case "edit":

			header('Content-type: application/json');

			global $wpdb;

			$table_name = $wpdb->prefix . "schedule";

			$wpdb->update( $table_name, array( 'seriesID' => $_GET['series-id'], 'episodeID' => $_GET['episode-id'] ), array( 'time' => $_GET['time-id'] ), array( '%d', '%d' ), array( '%d' ) );

			$episode = get_post( $_GET['episode-id'] );

			$episode_data = get_post_custom( $_GET['episode-id'] );

			$series_data = get_page( $_GET['series-id'] );


			$service['action']        = "update";
			$service['time_id']       = $_GET['time-id'];
			$service['episode_title'] = $episode->post_title;
			$service['series_title']  = $series_data->post_title;
			$service['episode_id']    = $episode->ID;
			$service['series_id']     = $series_data->ID;
			$service['blocks']        = ceil( $episode_data['duration'][0] / 1800 );
			$service['duration']      = $episode_data['duration'][0];

			echo json_encode($service);

		break;

		case "delete":

			header('Content-type: application/json');

			global $wpdb;

			$table_name = $wpdb->prefix . "schedule";

			$delete_query = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE time = %d", $_GET['time-id'] ) );

			if( $delete_query !== false ){

				$service['action']  = "delete";
				$service['time_id'] = $_GET['time-id'];

				echo json_encode($service);

			}

		break;

		case "duplicate":

			header('Content-type: application/json');

			$current_year = $_GET['current_year'];
			$current_week = $_GET['current_week'];

			$current_week_start  = strtotime( $current_year . "-W" . $current_week . "-0" );
			$current_week_end    = ( strtotime( date( "c", $current_week_start ) . "+1 week" ) - 1 );

			$request_year = ( empty( $_GET['request_year'] ) )?$_GET['current_year']:$_GET['request_year'];
			$request_week = $_GET['request_week'];

			$request_week_start  = strtotime( $request_year . "-W" . $request_week . "-0" );
			$request_week_end    = ( strtotime( date( "c", $request_week_start ) . "+1 week" ) - 1 );

			$adjustInt = ( intval($current_week) - intval($request_week) );

			/* Grab Current Week */

			global $wpdb;

			$table_name = $wpdb->prefix . "schedule";

			$schedule_items = $wpdb->get_results("SELECT * FROM $table_name WHERE time BETWEEN $current_week_start AND $current_week_end");

			foreach( $schedule_items as $item ){

				$response["remove"][] = $item->time;

			}

			/* Delete Current Week */

			global $wpdb;

			$table_name = $wpdb->prefix . "schedule";

			$delete_query = $wpdb->query( "DELETE FROM $table_name WHERE time BETWEEN $current_week_start AND $current_week_end" );

			/* Grab Request Items */

			global $wpdb;

			$table_name = $wpdb->prefix . "schedule";

			$request_items = $wpdb->get_results("SELECT * FROM $table_name WHERE time BETWEEN $request_week_start AND $request_week_end");

			foreach( $request_items as $index => $item ){

				$episode = get_post( $item->episodeID );

				$episode_data = get_post_custom( $item->episodeID );

				$series_data = get_page( $item->seriesID );

				$adjusted_time = strtotime( date( "c", $item->time ) . $adjustInt . " week" );

				$response["add"][$index]['time_id']       = $adjusted_time;
				$response["add"][$index]['episode_title'] = $episode->post_title;
				$response["add"][$index]['series_title']  = $series_data->post_title;
				$response["add"][$index]['episode_id']    = $item->episodeID;
				$response["add"][$index]['series_id']     = $item->seriesID;
				$response["add"][$index]['color']         = $item->color;
				$response["add"][$index]['blocks']        = ceil( $episode_data['duration'][0] / 1800 );
				$response["add"][$index]['duration']      = $episode_data['duration'][0];

				global $wpdb;

				$table_name = $wpdb->prefix . "schedule";

				$wpdb->insert( $table_name, array( 'time' => $adjusted_time, 'seriesID' => $item->seriesID, 'episodeID' => $item->episodeID, 'color' => $item->color ), array( '%d', '%d', '%d', '%s' ) );

			}

			echo json_encode( $response );

		break;

		case "series-listing":

			header('Content-type: application/json');

			$series = get_terms( array( "program" ), array( "search" => $_GET['query'] , "orderby" => "name", "order" => "ASC", "hide_empty" => false ) );

			$service = array( "query" => stripslashes($_GET['query']), "suggestions" => array(), "data" => array() );

			foreach($series as $program){

				array_push( $service['suggestions'], $program->name );
				array_push( $service['data'], $program->slug );

			}

			echo json_encode($service);

		break;

		case "approve":

			header('Content-type: application/json');

			$current_year = $_GET['current_year'];
			$current_week = $_GET['current_week'];

			$current_week_start  = strtotime( $current_year . "-W" . $current_week . "-0" );
			$current_week_end    = ( strtotime( date( "c", $current_week_start ) . "+1 week" ) - 1 );

			global $wpdb;

			$table_name = $wpdb->prefix . "schedule";

			$wpdb->query( "UPDATE $table_name SET approved = 1 WHERE time BETWEEN $current_week_start AND $current_week_end" );

			$service['action']  = "approve";
			$service['time_id_start'] = $current_week_start;
			$service['time_id_end'] = $current_week_end;

			echo json_encode($service);

		break;

		case "colorize":

			global $wpdb;

			$table_name = $wpdb->prefix . "schedule";

			$wpdb->update( $table_name, array( 'color' => $_GET['color'] ), array( 'time' => $_GET['time'] ), array( '%s' ), array( '%d' ) );

			$service['action']    = "colorize";
			$service['requested'] = $_GET['color'];

			echo json_encode($service);

		break;

		case "export-csv":

			if( empty( $_GET['week'] ) || empty( $_GET['year'] ) ){

				die;

			}

			$year_number = $_GET['year'];
			$week_number = $_GET['week'];

			$file = "Schedule - $week_number - $year_number.xls";

			header("Content-Type: application/vnd.ms-excel");
			header("Content-Disposition: attachment; filename=$file");

			$date_headers = "\t";

			for( $x = 0; $x < 7; $x++ ){

				$y[$x] = strtotime( $year_number . "-W" . $week_number . "-" . $x );
				$date_headers .= strtoupper( date( "l", $y[$x] ) . " (" .  date( "n/j", $y[$x] ) . ")" );

				if( $x == 6 ){
					$date_headers .= "\n";
				}else{
					$date_headers .= "\t";
				}

			}

			$time_string = "7:00AM";

			for( $x = 0; $x < 33; $x++ ){

				$info .= $time_string . "\t";

				for( $z = 0; $z < 7; $z++ ){

					$date_string = date( "j F Y", $y[$z] );

					$unix_time_string = strtotime( $date_string . " " . $time_string );

					global $wpdb;

					$item = $wpdb->get_row( "SELECT * FROM wp_schedule WHERE time = $unix_time_string" );

					$episode = get_post( $item->episodeID );

					$episode_data = get_post_custom( $item->episodeID );

					$duration = ceil( $episode_data["duration"][0] / 1800 );

					$series_data = get_page( $item->seriesID );

					if( $duration > 1 ){

						for( $w = 1; $w < $duration; $w++ ){

						$child                       = $unix_time_string + ( 1800 * $w );
						$children[$child]["parent"]  = $unix_time_string;
						$children[$child]["series"]  = $series_data->post_title;
						$children[$child]["episode"] = $episode->post_title;
						$children[$child]["data"]    = $data_string;
						$children[$child]["status"]  = $status;

						}

					}

					if( !isset( $children[$unix_time_string]["parent"] ) ){

						$episode_ID = ( !empty( $episode_data["internal_id"][0] ) ) ? " (".$episode_data["internal_id"][0].")" : "";

						if( !empty( $series_data->post_title ) || !empty( $episode->post_title ) ){

							if( $episode_data["series_title_disable"][0] == 1 ){

								$info .= str_replace( array("\n", "\t"), "", html_entity_decode( $episode->post_title, ENT_QUOTES ) );

							}else{

								$info .= str_replace( array("\n", "\t"), "",  html_entity_decode( $series_data->post_title, ENT_QUOTES ) . ": " . html_entity_decode( $episode->post_title, ENT_QUOTES ) . $episode_ID );

							}

						}

					}else{

						$info .= "";

					}


					if( $z == 6 ){
						$info .= "\n";
					}else{
						$info .= "\t";
					}

				}

				$time = strtotime( $time_string . " +30 minutes" );
				$time_string = date( "g:iA", $time );

			}


			print $date_headers;
			print $info;

		break;


		case "export-stf":

			if( empty( $_GET['week'] ) || empty( $_GET['year'] ) ){

				die;

			}

			$year_number = $_GET['year'];
			$week_number = $_GET['week'];

			$file = "Softron Schedule - $week_number - $year_number.txt";

			header("Content-Type: text/plain");
			header("Content-Disposition: attachment; filename=$file");

			$start_time = strtotime( $year_number . "-W" . $week_number );
			$end_time = strtotime( $year_number . "-W" . $week_number . "+1 week" );

			$included = array();

			global $wpdb;

			$schedule_items = $wpdb->get_results( "SELECT * FROM wp_schedule WHERE time >= $start_time AND time <= $end_time" );

			$row_data = "";

			foreach( $schedule_items as $item ){

				if( !in_array( $item->time, $included ) ){

					$episode_data = get_post_custom( $item->episodeID );

					$row_data .= date( "Y-m-d", $item->time ) . "\t";
					$row_data .= date( "H:i:s", $item->time ) . "\t";
					$row_data .= trim( $episode_data['broadcast_location'][0] ) . "\t";
					$row_data .= "0";
					$row_data .= "\n";

					$included[] = $item->time;

				}

			}

			print $row_data;

		break;


		case "export-tvguide":

			if( empty( $_GET['week'] ) || empty( $_GET['year'] ) ){

				die;

			}

			$year_number = $_GET['year'];
			$week_number = $_GET['week'];

			$included    = array();

			$file = "Schedule TVG - $week_number - $year_number.xls";

			header("Content-Type: application/vnd.ms-excel");
			header("Content-Disposition: attachment; filename=$file");

			$columns = array("Station ID", "Program Start Date", "Program Start Time", "Program End Time", "Duration", "Program Title", "Episode Number", "Episode Title", "");

			$column_headers = implode("\t", $columns) . "\n";

			$start_time = strtotime( $year_number . "-W" . $week_number );
			$end_time = strtotime( $year_number . "-W" . $week_number . "+1 week" );

			global $wpdb;

			$schedule_items = $wpdb->get_results( "SELECT * FROM wp_schedule WHERE time >= $start_time AND time <= $end_time" );

			foreach( $schedule_items as $item ){

				if( !in_array( $item->time, $included ) ){

					$episode = get_post( $item->episodeID );

					$episode_data = get_post_custom( $item->episodeID );

					$series_data = get_page( $item->seriesID );

					$duration = ceil( $episode_data["duration"][0] / 1800 );

					$row_data .= "HEC-TV\t";
					$row_data .= date( "m/d/y", $item->time ) . "\t";
					$row_data .= date( "H:i", $item->time ) . "\t";
					$row_data .= date( "H:i", $item->time + $episode_data["duration"][0] ) . "\t";
					$row_data .= ceil( $episode_data["duration"][0] / 60 ). "\t";
					$row_data .= html_entity_decode( $series_data->post_title, ENT_QUOTES ) . "\t";
					$row_data .= $episode_data["internal_id"][0] . "\t";

					if( $episode_data["series_title_disable"][0] != 1 ){

						$row_data .= html_entity_decode( $episode->post_title, ENT_QUOTES ) . "\t";

					}

					$row_data .= "\n";

					$included[] = $item->time;

				}

			}

			print $column_headers;
			print $row_data;

			//print_r($schedule_items);
			//print $info;

		break;

	}

	die;

}


?>