<?

/*
Plugin Name: HEC-TV Reservations
Version: 1.0
Plugin URI:
Author: Kameron Zach (Love/Hate Inc)
Author URI: http://www.lovehatecreative.com
Description: Create and manage episode reservations.
*/

register_activation_hook( __FILE__, 'hectv_reservations_activate' );

add_action('admin_menu', 'hectv_reservations_add_pages');

/* Ajax Hooks */
add_action('wp_ajax_reservations_service', 'reservations_service_callback');

/* Modules */

require_once 'modules/service.php';
require_once 'modules/user-service.php';

function hectv_reservations_activate(){

	global $wpdb;

	$table_name = $wpdb->prefix . "reservations";

	$sql = "CREATE TABLE $table_name (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	episodeID mediumint(5) NOT NULL,
	seriesID mediumint(5) NOT NULL,
	name varchar(50),
	school varchar(100),
	attendees mediumint(2),
	emailAddress varchar(100),
	attendTime varchar(10),
	created datetime,
	state mediumint(2),
	UNIQUE KEY id (id)
	);";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	dbDelta($sql);

	$sql = "ALTER TABLE $table_name ADD INDEX (id, time)";

	dbDelta($sql);

}


function hectv_reservations_add_pages() {

	add_object_page( 'Reservations', 'Reservations', 'level_9', 'reservations_home', 'reservations_home', plugins_url('hectv-reservations/ui/chalkboard-sm.png') );

}

function reservations_home() {

	wp_enqueue_script( 'autocomplete', '/wp-content/themes/hectv-3-0/_/js/autocomplete.js' );

	if( isset($_POST['doaction']) && $_POST['doaction'] == "Delete" ){

		$delete_ids = $_POST['select'];

		global $wpdb;

		foreach( $delete_ids as $delete_id ){

			//$wpdb->delete( $wpdb->prefix . "reservations",  array( 'id' => $delete_id ), array( '%d' ) );

			$wpdb->update( $wpdb->prefix . "reservations", array( 'state' => '0' ), array( 'id' => $delete_id ), array( '%d' ) );



		}

		$ess = (count($delete_ids)>1)?'s':'';

		$alert = '<div id="message" class="updated below-h2"><p>'.count($delete_ids).' reservation'.$ess.' moved to the Trash. <a href="?doaction=untrash&ids='.implode(",",$delete_ids).'&page=reservations_home">Undo</a></p></div>';

	}

	if( isset($_GET['doaction']) && $_GET['doaction'] == "untrash" ){


		$undelete_ids = explode( ",", $_GET['ids'] );

		global $wpdb;

		foreach( $undelete_ids as $undelete_id ){

			$wpdb->update( $wpdb->prefix . "reservations", array( 'state' => '1' ), array( 'id' => $undelete_id ), array( '%d' ) );



		}

		$ess = (count($undelete_ids)>1)?'s':'';

		$alert = '<div id="message" class="updated below-h2"><p>'.count($undelete_ids).' reservation'.$ess.' removed to the Trash.</p></div>';


	}

	if( isset( $_POST['doaction'] ) && $_POST['doaction'] == "Filter By Name" ){

		$series_filter = $_POST['program'];

		$term = get_term( $_POST['program'], "program" );

	}

?>

	<link type="text/css" rel="stylesheet" href="<? echo plugins_url('hectv-reservations/style.css'); ?>" />
	<div class="wrap" id="reservations">

		<div id="icon-options-general" class="icon32" style="background-position:0px 0px;background-size: 32px 32px;background-image:url(<? echo plugins_url('hectv-reservations/ui/chalkboard-lg.png'); ?>);"><br></div><h2>Reservations</h2>

		<?php echo $alert; ?>

		<div id="menu">

			<form action="" method="post">

<!-- 	        <input type="text" id="series_picker" name="program-title" value="<? echo $term->name; ?>"> -->
<!-- 	        <input type="hidden" id="series_picker_id" name="program" value="<? echo $term->term_id; ?>"> -->
<!-- 			<input type="submit" name="doaction" id="doaction" class="button" value="Filter By Name" style="margin-right:0px;"> -->

			<?php if($series_filter){ ?>
				<a href="admin.php?page=reservations_home" class="button">Clear Filter</a>
			<?php } ?>

			<input type="submit" name="doaction" id="doaction" class="button action" value="Delete" style="margin-left:10px;">

		</div>

		<div class="clearfix"></div>

		<table class="wp-list-table widefat fixed posts" cellspacing="0" style="margin-top:15px;">
			<thead>
			<tr>
				<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" id="selectall" name="selectall" value="1"></th>
				<th scope="col" id="title" class="" style=""><span>Name</span></th>
				<th scope="col" id="email" class="" style=""><span>Email Address</span></th>
				<th scope="col" id="show" class="" style=""><span>Show</span></th>
				<th scope="col" id="time" class="" style=""><span>Time</span></th>
			</tr>
			</thead>

			<tbody id="the-list">

				<?php global $wpdb; ?>

				<?php if($series_filter){ ?>

					<?php $results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."reservations WHERE state = '1' AND seriesid = '$series_filter' ORDER BY episodeID, attendTime DESC" ); ?>

				<?php }else{ ?>

					<?php $results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."reservations WHERE state = '1' ORDER BY episodeID, attendTime DESC" ); ?>

				<?php } ?>



				<?php $x = 0; ?>
				<?php foreach($results as $result){ ?>

				<?php $count = $wpdb->get_results( "SELECT attendTime, SUM(attendees) as total FROM ".$wpdb->prefix."reservations WHERE episodeID = $result->episodeID AND state = '1' GROUP BY attendTime DESC" ); ?>

				<?php foreach($count as $item){ ?>

				<?php if( $item->attendTime == $result->attendTime ) { ?>

				<?php $total = $item->total; ?>

				<?php } ?>

				<?php } ?>

				<tr id="post-12673" class="<?php echo ($x%2)?'alternate':''; ?>" valign="top">
					<th scope="row" class="check-column"><input type="checkbox" name="select[]" value="<?php echo $result->id; ?>"></th>
					<td class="colum"><b><?php echo $result->name; ?></b><br><?php echo stripcslashes( stripslashes( $result->school ) ); ?></td>
					<td class="colum"><a href="mailto:<?php echo $result->emailAddress; ?>"><?php echo $result->emailAddress; ?></a></td>
					<td class="colum"><?php echo get_the_title($result->episodeID); ?></td>
					<td class="colum" title="" alt=""><?php echo $result->attendTime; ?></td>
				</tr>

				<?php $x++; ?>
				<?php } ?>

			</tbody>
		</table>
		</form>

	</div>

<?
}


function reservations_widget($episodeID){

	if($episodeID){

	?>

		<?php global $wpdb; ?>
		<?php $results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."reservations WHERE state = '1' AND episodeID = '$episodeID' ORDER BY episodeID, attendTime DESC" ); ?>

		<?php if( count($results) > 0 ){ ?>

	<div class="video-detail section-top">

		<div class="label-wrap" style="margin-bottom:10px;"><label for="">Reservations:</label></div>

		<div class="clearfix"></div>

		<ul id="reservations">

		<?php foreach($results as $result){ ?>

		<li><span class="name"><?php echo stripcslashes( $result->name ); ?></span><span class="time"><?php echo $result->attendTime; ?></span><span class="attending"><?php echo $result->attendees; ?> attending</span><span class="email"><a href="mailto:<?php echo $result->emailAddress; ?>">Email</a></span></li>

		<?php $attendeeInfo[$result->attendTime] = $attendeeInfo[$result->attendTime] + $result->attendees; ?>

		<?php } ?>

		<li class="totals">

		<span>Totals:</span>

		<?php foreach($attendeeInfo as $key => $set){ ?>

		<span><b><?php echo $key; ?>:</b> <?php echo $set; ?></span>

		<?php } ?>

		</li>

		</ul>

	</div>

	<?php } ?>

	<?

	}

}

?>