<?

/*
Plugin Name: HEC-TV Interactive
Version: 1.1
Plugin URI:
Author: Kameron Zach (Let's Build LLC.)
Author URI: http://www.letsbuild.la
Description: Interactive Homepage moderation tool for HEC-TV.
*/


register_activation_hook( __FILE__, 'hectv_interactive_activate' );

add_action('admin_menu', 'hectv_interactive_add_pages');

/* Ajax Hooks */
add_action('wp_ajax_interactive_service', 'hectv_interactive_callback');

/* Modules */

require_once 'modules/service.php';
require_once 'modules/user-service.php';

function hectv_interactive_activate(){

	global $wpdb;

	$table_name = $wpdb->prefix . "interactive_posts";

	$sql = "CREATE TABLE $table_name (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	postAuthor mediumint(9) NOT NULL,
	postStatus int(2),
	postContent text NOT NULL,
	posttime timestamp ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY id (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	dbDelta($sql);

	$sql = "ALTER TABLE $table_name ADD INDEX (id)";

	dbDelta($sql);

	$table_name = $wpdb->prefix . "interactive_share";

	$sql = "CREATE TABLE $table_name (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	shareType mediumint(2) NOT NULL,
	shareRecipient mediumint(6) NOT NULL,
	shareTitle text NOT NULL,
	shareAddress text NOT NULL,
	shareStatus mediumint(2) NOT NULL,
	shareTime mediumint(11) NOT NULL,
	sharePosition mediumint(3) NOT NULL,
	UNIQUE KEY id (id)
	);";

	dbDelta($sql);

	$sql = "ALTER TABLE $table_name ADD INDEX (id)";

	dbDelta($sql);

	$table_name = $wpdb->prefix . "interactive_users";

	$sql = "CREATE TABLE $table_name (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	userSecurityKey text NOT NULL,
	userLastAction timestamp ON UPDATE CURRENT_TIMESTAMP,
	userTimeJoined timestamp NOT NULL,
	userFlags mediumint(11) NOT NULL,
	userName text NOT NULL,
	userEmail text NOT NULL,
	userLocation text NOT NULL,
	userStatus mediumint(2) NOT NULL,
	userIP text NOT NULL,
	userBrowser text NOT NULL,
	UNIQUE KEY id (id)
	);";

	dbDelta($sql);

	$sql = "ALTER TABLE $table_name ADD INDEX (id)";

	dbDelta($sql);



}


function hectv_interactive_add_pages() {

	add_object_page( 'Interactive', 'Interactive', 'level_9', 'interactive_home', 'interactive_home', plugins_url('hectv-interactive/ui/im.png') );

}

function interactive_home() {

?>

	<link type="text/css" rel="stylesheet" href="<? echo plugins_url('hectv-interactive/style.css'); ?>" />
	<script type="text/javascript" src="<? echo plugins_url('hectv-interactive/interactive.js'); ?>"></script>
	<div class="wrap">

	<div id="icon-options-general" class="icon32" style="background-position:0px 0px;background-size: 32px 32px;background-image:url(<? echo plugins_url('hectv-interactive/ui/im-large.png'); ?>);"><br></div><h2>Interactive</h2>


	<p>A web producer can respond to questions or comments, launch polls and share media all from this interactive web panel. Multiple producers can be logged into this panel from multiple computers.</p>
	<p>Event properties will automatically save changes as you make them, while player embed and show data options must be committed by clicking "save"</p>
	<p>Settings can be accessed <a id="settings" href="#">here</a>.</p>

	<table id="event-settings" class="form-table" style="margin-bottom:15px;">
		<form method="post" id="interactive-settings" action="/wp-admin/admin-ajax.php">
		<input type="hidden" name="action" id="action" value="interactive_service">
		<input type="hidden" name="type" value="settings">
		<tbody>
			<tr valign="top">
				<th scope="row"><label for="eventSeries">Series</label></th>
				<td><input name="eventSeries" class="live-update" type="text" style="width: 290px;" id="eventSeries" value="<? echo get_option( "interactive_series" ); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="eventTitle">Title</label></th>
				<td><input name="eventTitle" class="live-update" type="text" style="width: 290px;" id="eventTitle" value="<? echo get_option( "interactive_title" ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row">Description</th>
				<td><label for="eventDescription"><textarea name="eventDescription" class="live-update" type="checkbox" id="eventDescription" row="5" cols="90"><? echo stripslashes( get_option( "interactive_description" ) ); ?></textarea></label><br><i>Description copy listed above the video embed.</i></td>
			</tr>
			<tr>
				<th scope="row">Display</th>
				<td><label for="eventTakeover"><input name="eventTakeover" class="live-update" type="checkbox" id="eventTakeover" value="1" <? echo ( get_option( "interactive_status" ) == 1 ) ? 'checked=checked':''; ?>> Replace marquee on homepage with event module</label></td>
			</tr>
			<tr>
				<th scope="row">Status</th>
				<td><label for="eventEnable"><input name="eventEnable" class="live-update" type="checkbox" id="eventEnable" value="1" <? echo ( get_option( "interactive_enable" ) == 1 ) ? 'checked=checked':''; ?>> Enable live questions and share features</label></td>
			</tr>
			<tr>
				<th scope="row">Video Embed</th>
				<td><label for="eventEmbed"><textarea name="eventEmbed" class="live-update" type="checkbox" id="eventEmbed" row="5" cols="90"><? echo stripslashes( get_option( "interactive_embed" ) ); ?></textarea></label><br><i>Player embed should be responsive 100% width.</i></td>
			</tr>
			<tr>
				<th scope="row">Clear Data</th>
				<td><label for="eventClear"><input name="eventClear" class="live-update" type="checkbox" id="eventClear" value="1"> Erase all posts, questions and user history.</label></td>
			</tr>
			<tr>
				<th scope="row">&nbsp;</th>
				<td><input type="submit" id="eventEmbedSave" name="eventEmbedSave" class="button-primary" value="Save" style="margin-top:5px;"><input type="button" id="eventCancel" name="eventCancel" class="button-secondary" value="Cancel" style="margin-top:5px;margin-left:5px;"></td>
			</tr>

		</tbody>
		</form>
	</table>
</div>
<div id="event-body">

	<ul id="eventQuestions">
	</ul>
	<ul id="eventUsers">

	</ul>
	<div class="clear"></div>
	<div id="event-composer" class="item">
		<form method="post" id="share-composer" action="/wp-admin/admin-ajax.php">
		<input type="hidden" name="action" id="action" value="interactive_service">
		<input type="hidden" name="type" value="composer">
			<div id="shareItem">
				<label for="shareType">Share: </label>
				<select name="shareType" id="shareType">
					<option value="1">Link</option>
					<option value="2">Video</option>
					<option value="3">Audio</option>
					<option value="4">File</option>
					<option value="5">Question</option>
<!-- 					<option value="Poll">Poll</option> -->
				</select>
				<div class="clear"></div>
			</div>
			<div id="itemDialog">

					<label for="shareAddress"><span id="shareAddressCopy">Link Address:</span></label>
					<input type="text" name="shareAddress" id="shareAddress">
					<label for="shareDescription" id="shareDescriptionLabel">Description: </label>
					<input type="text" name="shareDescription" id="shareDescription">
					<input type="submit" value="Share" class="button-primary" id="shareSubmit" name="shareSubmit">
			</div>

		</form>

			<div id="pollDialog">
				<div>
				<label for="pollQuestion" id="pollQuestionLabel">Prompt: </label>
				<textarea id="pollQuestion" name="pollQuestion"></textarea>
				</div>
				<div>
				</div>
				<ul id="pollOptions">
					<li><input type="text" name="pollResponse[]" value="Poll response here..." class="pollResponse query" original="Poll response here…">&nbsp;<a class="addPollResponse" href="#">add</a></li>
					<div class="clearfix"></div>
				</ul>
				<div id="pollResponseOptions">
					<input type="checkbox" value="1" name="pollType" id="pollType">&nbsp;
					<label for="pollType">Allow multiple answers</label>
				</div>
			</div>

			<div id="postDialog">
				<label for="sharePost"><span id="sharePostLabel">Post:</span></label>
				<input type="text" name="sharePost" id="sharePost">
			</div>

			<div class="clear"></div>

	</div>

	<ul id="eventShareItems">
	</ul>


	<div class="clearfix"></div>

	</div>

<?
}
?>