<?php

function reservations_service_callback(){

	switch( $_GET['type'] ){
		
		case "series-listing":
		
			header('Content-type: application/json');
		
			$series = get_terms( array( "program" ), array( "search" => $_GET['query'] , "orderby" => "name", "order" => "ASC", "hide_empty" => false ) );
			
			$service = array( "query" => stripslashes($_GET['query']), "suggestions" => array(), "data" => array() );
			
			foreach($series as $program){
			
				array_push( $service['suggestions'], $program->name );
				array_push( $service['data'], $program->term_id );
			
			}
			
			echo json_encode($service);
		
		break;
	
	}

	die;

}


?>