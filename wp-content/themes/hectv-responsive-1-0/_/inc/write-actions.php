<?php

	add_action( 'wp_ajax_lb_segment_action', 'lb_do_segment_action' );

	function lb_do_segment_action() {

		if( empty( $_GET['post_title'] ) ){

			$response = array( "result" => false, "message" => "Critical form data was missing from this request." );

		}

		$arguments = array(
			'post_title'    => $_POST['segment_title'],
			'post_content'  => $_POST['segment_long_description'],
			'post_status'   => $_POST['segment_status'],
			'post_excerpt'  => $_POST['segment_meta_description'],
			'post_type'     => 'lb_video',
			'post_parent'   => $_POST['segment_parent']
		);

		if( $_POST['segment_child'][0] ){

			$subjectID       = $_POST['segment_child'][0];
			$arguments['ID'] = $subjectID;

			wp_update_post( $arguments );

			$verb            = "updated";

		}else{

			$subjectID       = wp_insert_post( $arguments );
			$verb            = "created";

		}

		$seriesID            = wp_get_post_parent_id( $_POST['segment_parent'] );

		if( $subjectID ){

			if( is_array( $_POST["segment_files"]['location'] ) && count( $_POST["segment_files"]['location'] ) > 1 ){

				$url  = $_POST["segment_files"]['location'][0];
				$path = str_replace( basename( $url ), "", $url );

				$fileName = basename( $_POST["segment_files"]['location'][0] );
				$fileName = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $fileName );
				$fileName = str_replace( $_POST["segment_files"]['bitrate'][0] , "", $fileName );
				$fileName = trim( $fileName, "_");

				$smilFile = hectv_createSmilFile( $_POST["segment_files"], $fileName, $path );

			}

			wp_set_post_terms( $subjectID, $_POST["segment_categories"], "topic" );

			update_post_meta( $subjectID, "long_description", $_POST['segment_post_content'] );
			update_post_meta( $subjectID, "meta_description", $_POST['segment_meta_description'] );
			update_post_meta( $subjectID, "video_image", $_POST['segment_thumbnail_id'] );
			update_post_meta( $subjectID, "video_files", $_POST["video_files"] );
			update_post_meta( $subjectID, "segment_files", $_POST["segment_files"] );
			update_post_meta( $subjectID, "video_embed", $_POST["segment_embed"] );

			update_post_meta( $subjectID, "youtube_id", $_POST["segment_youtube_id"] );
			update_post_meta( $subjectID, "vimeo_id", $_POST["segment_vimeo_id"] );

			update_post_meta( $subjectID, "smil_file", $smilFile );
			update_post_meta( $subjectID, "duration", hectv_convertDuration( $_POST["segment_duration"] ) );

			update_post_meta( $subjectID, "keywords", $_POST["keyword"] );

			$response = array( "result" => true, "message" => "Post successfully " . $verb . "...", "postID" => $subjectID, "post_title" => $_POST['segment_title'], "post_status" => $_POST['segment_status'], "post_parent" => $_POST['segment_parent'], "smilFile" => $smilFile );

		}else{

			$response = array( "result" => false, "message" => "The post could not be created." );

		}

		echo json_encode( $response );

		die;

	}

	function hectv_createSmilFile( $video, $title, $remotePath ){

		if( !is_array( $video ) && !empty( $remotePath ) ){

			return false;

		}

		//$uploadDir = wp_upload_dir();

		$directory   = get_template_directory() . "/_";


		$remotePath .= ( substr( $remotePath, -1 ) !== "/" ) ? "/" : "";

		$file      = $directory . "/smil/$remotePath$title.smil";

		if ( !file_exists( $directory . "/smil/$remotePath" ) ) {

			mkdir( $directory . "/smil/$remotePath", 0777, true);

		}

		$smilFile  = fopen( $file, "w+" ) or die("Unable to open file!");
		$contents  = "<smil>\n";
		$contents .= "\t<head>\n";
		$contents .= "\t\t<meta base=\"rtmp://hectv.bc-s.cdn.bitgravity.com/cdn/_definst_/hectv/$remotePath\" />\n";
		$contents .= "\t</head>\n";
		$contents .= "\t<body>\n";
		$contents .= "\t\t<switch>\n";


		foreach( $video['location'] as $index => $entry ){

			$bitrate   = $video['bitrate'][$index] * 1000;
			$location  = basename( $video['location'][$index] );
			$contents .= "\t\t\t<video src=\"$location\" system-bitrate=\"$bitrate\"/>\n";

		}

		$contents .= "\t\t</switch>\n";
		$contents .= "\t</body>\n";
		$contents .= "</smil>\n";

		if( @fwrite( $smilFile, $contents ) ){

			if( @chmod($file, 0777) ){

				return $remotePath . $title . ".smil";

			}

		}


	}


	function hectv_moveSmilFile( $file, $remotePath ){

		if( empty( $file ) && empty( $remotePath ) ){

			return false;

		}

		$fileName   = basename( $file );

		$remotePath = trim( $remotePath, "/" );

		$ftpServer = "ftp.bitgravity.com";
		$ftpConn   = ftp_connect( $ftpServer ) or die( "Could not connect to $ftp_server" );
		$login     = ftp_login( $ftpConn, "creative@hectv.org", "pickupboyd989" );

		if( ftp_put( $ftpConn, "$remotePath/$fileName", $file, FTP_ASCII ) ){

			return $file;

		} else {

			return false;

		}

		ftp_close($ftpConn);

	}

?>