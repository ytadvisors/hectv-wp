<?php
/*
  Plugin Name: Let's Build Marquee
  Version: 1.0
  Plugin URI: http://www.letsbuild.la
  Author: Let's Build
  Author URI: http://www.letsbuild.la
  Description: Manage marquee objects
 */

register_activation_hook( __FILE__, 'lb_marquee_activate' );

add_action('admin_menu', 'lb_marquee_add_pages');

/* Ajax Hooks */
add_action('wp_ajax_marquee_service', 'schedule_marquee_callback');

/* Modules */

require_once 'modules/service.php';

function lb_marquee_activate(){

	return true;

}


function lb_marquee_add_pages() {

	add_object_page( 'Marquee', 'Marquee', 'level_9', 'marquee_home', 'marquee_home', plugins_url('lets-build-marquee/ui/signage.png') );

}

function marquee_home() {

	if (!current_user_can('manage_options')) wp_die('You do not have sufficient permissions to access this page.');

	if ( $_POST['action'] == "save-promos" ) {

		$form_data = json_decode( stripslashes( $_POST['data'] ) );

		update_option( 'lb_marquee_homepage_slides_v2', $form_data );

	}

	wp_enqueue_media();

?>

	<link type="text/css" rel="stylesheet" href="<?php echo plugins_url('lets-build-marquee/style.css'); ?>?5=x" />
	<link type="text/css" rel="stylesheet" href="<?php echo plugins_url('lets-build-marquee/marquee.css'); ?>?5=x" />
	<script type="text/javascript" src="<?php echo plugins_url('lets-build-marquee/jquery-ui.min.js'); ?>?as5=x"></script>
 	<script type="text/javascript" src="<?php echo plugins_url('lets-build-marquee/marquee.js'); ?>?asd=x"></script>
	<div class="wrap">

	<div id="icon-options-general" class="icon32" style="background-position:0px 0px;background-size: 32px 32px;background-image:url(<?php echo plugins_url('lets-build-marquee/ui/signage-large.png'); ?>);"><br></div>
		<h2>Marquee</h2>
		<p>Images should be cropped to 1280 × 590 pixels.</p>

	<div id="content-picker" class="video">

		<form method="get" id="post-form" action="<?php echo get_admin_url(); ?>/admin-ajax.php">
			<input type="hidden" name="action" id="action" value="marquee_service">
			<input type="hidden" name="type" value="insert-post">
			<input type="hidden" name="parent" id="parent" value="">

			<div class="item" id="find-by-title">
				<label for="post-title">Search:</label>
				<input type="text" id="post-title">
				<input type="hidden" name="find-by-title-id" id="post-id" value="">
				<button class="unlink" type="button">x</button>
			</div>

			<div class="item" id="or">
				<span>or</span>
			</div>

			<div class="item" id="most-recent">
				<label for="post-recent">Recent Videos:</label>

				<?php $stories = get_posts( array( "orderby" => "date", "order" => "DESC", "post_type" => array( "lb_video", "lb_playlist" ) , "posts_per_page" => 20 ) ); ?>

				<select name="recent-post-id" id="post-recent">
					<?php foreach( $stories as $x => $story ){ ?>
						<?php $type = array( "lb_video" => "Video", "lb_playlist" => "Playlist" ); ?>
						<option value="<?php echo $story->ID; ?>"><?php echo $story->post_title; ?> (<?php echo $type[$story->post_type]; ?>)</option>
					<?php } ?>
					<option value="custom">Custom Slide</option>
				</select>
			</div>

			<div class="item" id="submit">
				<input type="submit" id="post-submit" value="Add" class="button-primary">
			</div>

		</form>

		<div class="clearfix"></div>

	</div>

	<form method="POST" action="admin.php?page=marquee_home" id="save-set">

		<ul id="promos">

			<li class="add-promo-group" id="add-promo">
				<span>Add Page</span>
			</li>

			<?php $slides = get_option('lb_marquee_homepage_slides_v2'); ?>

			<?php if( is_array( $slides ) && count( $slides ) > 0 ){ ?>

				<?php $count = 0; ?>

				<script type="text/javascript">var promos = <?php echo json_encode( $slides ); ?>;</script>

				<?php foreach( $slides as $index => $items ){ ?>

					<?php $index++; ?>
					<?php $x_index = 1 ;?>

					<?php $slide_count = count($items); ?>

					<?php ksort( $items ); ?>

					<li class="promo-group contracted" id="slide-<?php echo $index; ?>" data-store='<?php echo json_encode($items); ?>'>

						<div class="handle option"></div>
						<div class="delete option"></div>
						<div class="photo option"></div>
						<div class="expand option"></div>

						<div class="inner">

							<div class="slide one"></div>

						</div>

						<div class="clearfix"></div>

					</li>

					<?php $count++ ;?>

				<?php } ?>

			<?php }else{ ?>

				<li class="promo-group contracted" id="slide-1" data-store="[]">

					<div class="handle option"></div>
					<div class="delete option"></div>
					<div class="photo option"></div>
					<div class="expand option"></div>

					<div class="inner">

						<div class="slide one"></div>

					</div>

					<div class="clearfix"></div>

				</li>

			<?php } ?>

			<li class="contracted template" id="template" data-store="[]">

				<div class="handle option"></div>
				<div class="delete option"></div>
				<div class="photo option"></div>
				<div class="expand option"></div>

				<div class="inner">

					<div class="slide"></div>

				</div>

				<div class="clearfix"></div>

			</li>

			<li class="clearfix"></li>

			<input type="hidden" name="action" value="save-promos">
			<input type="hidden" name="data" id="data" value="">
			<input type="submit" name="lb-marquee-submit" value="Save Promo Sets">

		</ul>

	</form>

	<div class="clearfix"></div>

	</div>

<?php
}
?>