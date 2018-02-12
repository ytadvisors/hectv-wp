<?

/*
Plugin Name: HEC-TV Scheduler
Version: 2.0
Plugin URI:
Author: Kameron Zach (Love/Hate Inc)
Author URI: http://www.lovehatecreative.com
Description: Create and manage on-air schedule for HEC-TV.
*/


register_activation_hook( __FILE__, 'hectv_schedule_activate' );

add_action('admin_menu', 'hectv_schedule_add_pages');

/* Ajax Hooks */
add_action('wp_ajax_schedule_service', 'schedule_service_callback');

/* Modules */

require_once 'modules/service.php';

function hectv_schedule_activate(){

	global $wpdb;

	$table_name = $wpdb->prefix . "schedule";

	$sql = "CREATE TABLE $table_name (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	time int(11) NOT NULL,
	seriesID mediumint(5) NOT NULL,
	episodeID mediumint(5) NOT NULL,
	UNIQUE KEY id (id, time)
	);";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	dbDelta($sql);

	$sql = "ALTER TABLE $table_name ADD INDEX (id, time)";

	dbDelta($sql);

}


function hectv_schedule_add_pages() {

	add_object_page( 'On Air Schedule', 'On Air Schedule', 'level_9', 'schduler_home', 'schduler_home', plugins_url('hectv-scheduler/ui/television.png') );

}

function schduler_home() {

	$week_number = ( empty( $_GET['week'] ) ) ? date( "W", time() ) : $_GET['week'];
	$year_number = ( empty( $_GET['year'] ) ) ? date( "Y", time() ) : $_GET['year'];

	$week_start  = strtotime( $year_number . "-W" . $week_number . "-0" );

	$start_time  = "7:00AM";
	$end_time    = "11:00PM";

?>

	<link type="text/css" rel="stylesheet" href="<? echo plugins_url('hectv-scheduler/style.css'); ?>" />
	<script type="text/javascript" src="<? echo plugins_url('hectv-scheduler/schedule.js'); ?>"></script>
	<div class="wrap">

	<ul id="colorpicker">
		<li class="yellow" rel="#fdf3cb"></li>
		<li class="green" rel="#cfe3b9"></li>
		<li class="clear" rel="#f9f9f9"></li>
	</ul>

	<div id="schedule-inspector" class="module small">
		<h2 class="widget" alt="Double Click to Show/Hide" title="Double Click to Show/Hide">Inspector<input alt="Operations" title="Operations" type="button" id="operations" value=""></h2>
		<ul class="settings">
			<form id="inspector-operations" method="get" action="/wp-admin/admin-ajax.php">
				<input type="hidden" name="action" id="action" value="schedule_service">
				<input type="hidden" name="type" id="operations.type" value="duplicate">
				<input type="hidden" name="current_week" value="<? echo $week_number; ?>">
				<input type="hidden" name="current_year" value="<? echo $year_number; ?>">
				<li class="duplicate">
					<label for="">Duplicate week from:</label><br/>
					<select name="request_year" id="year" disabled="disabled">
					<? for( $x = date("Y", time())-1; $x <= date("Y", time())+1; $x++ ){ ?>
						<? if( ( $x == $_GET['year'] ) || ( empty( $_GET['year'] ) && $x == date("Y", time() ) ) ){ ?>
						<option value="<? echo $x; ?>" selected="selected"><? echo $x; ?></option>
						<? }else{ ?>
						<option value="<? echo $x; ?>"><? echo $x; ?></option>
						<? } ?>
					<? } ?>
				</select>
				<select name="request_week" id="week">

					<? for( $x = 1; $x <= 52; $x++ ){ ?>
						<? $lead = ( $x <= 9 ) ? 0 : ""; ?>
						<? if( $week_number == $lead.$x ){ ?>
							<option selected="selected"><? echo $lead.$x; ?></option>
						<? }else{ ?>
							<option><? echo $lead.$x; ?></option>
						<? } ?>
					<? } ?>

				</select>
				<input type="submit" value="Copy" class="button-primary">
				</li>
			</form>

			<form id="inspector-operations-approve" method="get" action="/wp-admin/admin-ajax.php">
				<input type="hidden" name="action" id="action" value="schedule_service">
				<input type="hidden" name="type" id="operations-approve-type" value="approve">
				<input type="hidden" name="current_week" value="<? echo $week_number; ?>">
				<input type="hidden" name="current_year" value="<? echo $year_number; ?>">
				<li class="approval">
				<div class="instructions">Approve this week to display it on the site. Please note that approvals are per schedule item so as you make changes you will need to re-approve.</div>
				<input type="submit" value="Approve" class="button-primary">
				</li>
			</form>

			<li>
				<div class="instructions" style="text-align:center;margin-bottom:5px;">Export the current schedule to CSV... You will need to format this file in Excel.</div>
				<center>
					<a href="/wp-admin/admin-ajax.php?action=schedule_service&type=export-csv&week=<? echo $week_number; ?>&year=<? echo $year_number; ?>" class="button-primary">Export to CSV</a>
				</center>
			</li>

			<li>
				<div class="instructions" style="text-align:center;margin-bottom:5px;">Export the current schedule to Softron Text File...</div>
				<center>
					<a href="/wp-admin/admin-ajax.php?action=schedule_service&type=export-stf&week=<? echo $week_number; ?>&year=<? echo $year_number; ?>" class="button-primary">Export to Text File</a>
				</center>
			</li>

			<li>
				<div class="instructions" style="text-align:center;margin-bottom:5px;">Export the current schedule to CSV... You will need to format this file in Excel formatted for TV Guide.</div>
				<center>
					<a href="/wp-admin/admin-ajax.php?action=schedule_service&type=export-tvguide&week=<? echo $week_number; ?>&year=<? echo $year_number; ?>" class="button-primary">Export to TV Guide</a>
				</center>
			</li>

		</ul>
		<ul class="content">
			<form id="inspector-form" method="get" action="/wp-admin/admin-ajax.php">
				<input type="hidden" name="action" id="action" value="schedule_service">
				<input type="hidden" name="type" id="type" value="add">
				<input type="hidden" name="series-id" id="series-id" value="">
				<input type="hidden" name="time-id" id="time-id" value="">
				<li class="datetime"><span class="label">Date:</span><span id="date-display" class="value-display">05/11/2013</span></li>
				<li class="datetime"><span class="label">Time:</span><span id="time-display" class="value-display">7:30AM</span></li>
				<li><span class="label">Series:</span><input type="text" class="field-display" id="series-picker" disabled="disabled"></li>
				<li><span class="label">Episode:</span><select id="episode-picker" name="episode-id" class="field-display"></select></li>
				<li class="actions"><input type="button" name="event" value="Delete" id="delete" class="button-secondary"><a href="#" id="open-episode" class="button-secondary" target="_blank" style="display:none;margin-right:5px;">Open</a><input type="submit" name="event" value="Add" id="commit" class="button-primary"></li>
			</form>
		</ul>
	</div>

	<div id="icon-options-general" class="icon32" style="background-position:0px 0px;background-size: 32px 32px;background-image:url(<? echo plugins_url('hectv-scheduler/ui/date.png'); ?>);"><br></div><h2>On Air Scheduler</h2>

	<div id="time-selection">
		<form method="get" action="">
			<input type="hidden" name="page" value="schduler_home">
			<label for="year">Year</label>
			<select name="year" id="year">
				<? for( $x = date("Y", time())-1; $x <= date("Y", time())+1; $x++ ){ ?>
					<? if( ( $x == $_GET['year'] ) || ( empty( $_GET['year'] ) && $x == date("Y", time() ) ) ){ ?>
					<option value="<? echo $x; ?>" selected="selected"><? echo $x; ?></option>
					<? }else{ ?>
					<option value="<? echo $x; ?>"><? echo $x; ?></option>
					<? } ?>
				<? } ?>
			</select>
			<label for="week">Week</label>
			<select name="week" id="week">

				<? for( $x = 1; $x <= 52; $x++ ){ ?>
					<? $lead = ( $x <= 9 ) ? 0 : ""; ?>
					<? if( $week_number == $lead.$x ){ ?>
						<option selected="selected"><? echo $lead.$x; ?></option>
					<? }else{ ?>
						<option><? echo $lead.$x; ?></option>
					<? } ?>
				<? } ?>
				<option value="53">53*</option>
			</select>
			<input type="submit" value="Load">
		</form>
	</div>

	<table class="widefat post schedule" cellspacing="0" id="schedule-table" style="margin-top:15px;">
		<thead>
		<tr>
			<th scope="col" id="cb" class="selectall time" style="width:10%;"></th>
			<? for( $x = 0; $x < 7; $x++ ){ ?>
				<? $y[$x] = strtotime( $year_number . "-W" . $week_number . "-" . $x ); ?>
				<th scope="col" id="c<? echo $x; ?>" class="day label"><? echo date( "D", $y[$x] ); ?> (<? echo date( "n/j", $y[$x] ); ?>)</th>
			<? } ?>
		</tr>
		</thead>

		<tbody>

		<? $time_string = "7:00AM"; ?>
		<? $unapproved = 0; ?>
		<? $total = 0; ?>
		<? for( $x = 0; $x < 33; $x++ ){ ?>

			<tr class="schedule" valign="middle">
				<td class="time center" valign="middle"><? echo $time_string; ?></td>

				<? for( $z = 0; $z < 7; $z++ ){ ?>

					<? $date_string = date( "j F Y", $y[$z] ); ?>
					<? $unix_time_string = strtotime( $date_string . " " . $time_string ); ?>

					<? global $wpdb; ?>
					<? $item = $wpdb->get_row( "SELECT * FROM wp_schedule WHERE time = $unix_time_string" ); ?>

					<? if( count($item) > 0 || isset( $children[$unix_time_string]["parent"] ) ){ ?>

						<? $episode = get_post( $item->episodeID ); ?>

						<? $episode_data = get_post_custom( $item->episodeID ); ?>
						<? $duration = ceil( $episode_data["duration"][0] / 1800 ); ?>

						<? $series_data = get_post( $item->seriesID ); ?>

						<? $data_string  = "data-series-title=\"".$series_data->post_title."\" "; ?>
						<? $data_string .= "data-episode-title=\"".$episode->post_title."\" "; ?>
						<? $data_string .= "data-episode-id=\"".$episode->ID."\" "; ?>
						<? $data_string .= "data-series-id=\"".$series_data->ID."\" "; ?>
						<? $data_string .= "data-time-id=\"".$unix_time_string."\" "; ?>
						<? $data_string .= "data-duration=\"".$episode_data["duration"][0]."\" "; ?>

						<? $status = ( $item->approved == 1 )?"app":"napp"; ?>
						<? $unapproved += ( $item->approved == 1 )?0:1; ?>
						<? $total++; ?>

						<? $data_string .= "data-approved=\"".$status."\" "; ?>

						<? if( !empty($item->color) && $item->color != "#f9f9f9" ){ ?>

						<? $colorHTML = 'style="background-color:'.$item->color.';"'; ?>

						<? }else{ ?>

						<? $colorHTML = ''; ?>

						<? } ?>


						<? if( $duration > 1 ){ ?>

							<? for( $w = 1; $w < $duration; $w++ ){ ?>

							<? $child                                     = $unix_time_string + ( 1800 * $w ); ?>
							<? $children[$child]["parent"]                = $unix_time_string; ?>
							<? $children[$child]["html"]                  = $colorHTML; ?>
							<? $children[$child]["series"]                = $series_data->post_title; ?>
							<? $children[$child]["episode"]               = $episode->post_title; ?>
							<? $children[$child]["data"]                  = $data_string; ?>
							<? $children[$child]["status"]                = $status; ?>
							<? $children[$child]["series_title_disable"]  = $episode_data["series_title_disable"][0]; ?>

							<? } ?>

						<? } ?>



						<? if( !isset( $children[$unix_time_string]["parent"] ) ){ ?>

							<? if( !empty( $episode->post_title ) && $episode_data["series_title_disable"][0] == 1 ){ ?>

							<td class="field active <? echo $status; ?>" <? echo $colorHTML; ?> <? echo $data_string; ?> id="<? echo $unix_time_string; ?>"><? echo $episode->post_title; ?></td>

							<? }else if( !empty( $episode->post_title ) ){ ?>

							<td class="field active <? echo $status; ?>" <? echo $colorHTML; ?> <? echo $data_string; ?> id="<? echo $unix_time_string; ?>"><? echo $series_data->post_title; ?>: <? echo $episode->post_title; ?></td>

							<? }else{ ?>
							<td class="field active fatal <? echo $status; ?>" <? echo $colorHTML; ?> <? echo $data_string; ?> id="<? echo $unix_time_string; ?>">ERROR: VIDEO ASSET MISSING</td>
							<? } ?>

						<? }else{ ?>

						<? if( $children[$unix_time_string]["series_title_disable"] == 1 ){ ?>

							<td class="field active <? echo $children[$unix_time_string]["status"]; ?>" <? echo $children[$unix_time_string]["html"]; ?> <? echo $children[$unix_time_string]["data"]; ?> id="<? echo $unix_time_string; ?>" parent="<? echo $children[$unix_time_string]["parent"]; ?>"><? echo $children[$unix_time_string]["episode"]; ?></td>

						<? }else{ ?>

							<td class="field active <? echo $children[$unix_time_string]["status"]; ?>" <? echo $children[$unix_time_string]["html"]; ?> <? echo $children[$unix_time_string]["data"]; ?> id="<? echo $unix_time_string; ?>" parent="<? echo $children[$unix_time_string]["parent"]; ?>"><? echo $children[$unix_time_string]["series"]; ?>:&nbsp;<? echo $children[$unix_time_string]["episode"]; ?></td>

						<? } ?>

						<? } ?>

					<? }else{ ?>

						<td class="field" id="<? echo $unix_time_string; ?>"></td>

					<? } /* end conditional */ ?>


				<? } /* end hour loop */ ?>

			</tr>

			<? $time = strtotime( $time_string . " +30 minutes" ); ?>
			<? $time_string = date( "g:iA", $time ); ?>
		<? } ?>
		</tbody>
		</table>

		<div class="clearfix"></div>

	</div>

<?
}
?>