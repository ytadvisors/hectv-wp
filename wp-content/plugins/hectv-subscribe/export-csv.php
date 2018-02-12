<?php 
require_once '../../../wp-load.php';
if (current_user_can('manage_options')) {
	header("Content-type: application/force-download"); 
	header('Content-Disposition: inline; filename="subscribers'.date('YmdHis').'.csv"');  
	$results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."sml");
	if (count($results))  {
		foreach($results as $row) echo $row->sml_email.','.$row->sml_name."\r\n";
	}
}
?>