<?

add_action( "init", "lb_create_event_taxonomies", 0 );

function lb_create_event_taxonomies() {

	$eventlabels = array(
		'name' => _x( 'Event Categories', 'taxonomy general name' ),
		'singular_name' => _x( 'Event Categories', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search Event Categories' ),
		'all_items' => __( 'All Event Categories' ),
		'parent_item' => __( 'Parent Event Category' ),
		'parent_item_colon' => __( 'Parent Event Categories:' ),
		'edit_item' => __( 'Edit Event Category' ),
		'update_item' => __( 'Update Event Category' ),
		'add_new_item' => __( 'Add New Event Category' ),
		'new_item_name' => __( 'New Event Category' ),
	);

	register_taxonomy( 'event_type', array( 'event' ), array(
		'hierarchical' => true,
		'labels' => $eventlabels,
		'show_ui' => true,
		'query_var' => true,
		'show_in_nav_menus' => true,
		'rewrite' => array( 'slug' => 'events/type', 'with_front' => false, 'hierarchical' => true  ),
	));

}

add_action( 'init', 'lb_event_init' );

function lb_event_init() {
	$labels = array(
		'name' => _x( 'Events', 'post type general name' ), // Tip: _x('') is used for localization
		'singular_name' => _x( 'Event', 'post type singular name' ),
		'add_new' => _x( 'Add New', 'video' ),
		'add_new_item' => __( 'Add New Event' ),
		'edit_item' => __( 'Edit Event' ),
		'new_item' => __( 'New Event' ),
		'view_item' => __( 'View Event' ),
		'search_items' => __( 'Search Events' ),
		'not_found' =>  __( 'No events found' ),
		'not_found_in_trash' => __( 'No events found in Trash' ),
		'parent_item_colon' => ''
	);

	$args = array( 'labels' => $labels,
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'events', 'with_front' => false ),
		'capability_type' => 'post',
		'menu_icon' => get_bloginfo('template_directory') . '/_/graphics/ui/ui-event.png',
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title', 'editor', 'thumbnail' )
	);

	register_post_type( 'event', $args ); /* Register it and move on */

}

add_action("admin_init", "add_event_options");

function add_event_options(){

	add_meta_box("event_details", "Event Details", "event_details", "event", "normal", "core");

}

function event_details(){

	date_default_timezone_set('UTC');

	global $post;
	$data = get_post_custom( $post->ID );
	$event                   = (object) array();
	$event->venue            = $data["venue"][0];
	$event->web_address      = $data["web_address"][0];
	$event->user_submitted_date = $data["user_submitted_date"][0];
	$event->user_submitted_file = $data["user_submitted_file"][0];
	$event->start_time       = $data["start_time"][0];
	$event->end_time         = $data["end_time"][0];
	$event->event_time       = $data["event_time"][0];
	$event->month            = date( "n", $event->start_time );
	$event->day              = date( "j", $event->start_time );
	$event->start_hour       = date( "g", $event->start_time );
	$event->start_minute     = date( "i", $event->start_time );
	$event->start_convention = date( "A", $event->start_time );
	$event->start_year       = date( "Y", $event->start_time );
	$event->end_hour         = date( "g", $event->end_time );
	$event->end_minute       = date( "i", $event->end_time );
	$event->end_convention   = date( "A", $event->end_time );

	$event->end_disable      = $data["end_disable"][0];
	$event->start_disable    = $data["start_disable"][0];

?>

<style type="text/css">
	div.event-detail { margin-bottom: 10px; }
	div.event-detail div.label-wrap { float: left; width: 180px; margin-right: 10px; position: relative; }
	div.event-detail div.label-wrap label { position: relative; top: 3px; }

	input.texterror { background-color: #F5ABAB; }

</style>

<?

$current_month = date( "n", time() );
$current_year  = date( "Y", time() );

for ($x = 0; $x <= 11; $x++){

	$current_month = date( "n", strtotime("+$x month") );
	$current_year  = date( "Y", strtotime("+$x month") );

	$month["month"][] = date( "F", strtotime("+$x month") );
	$month["month_num"][] = date( "n", strtotime("+$x month") );
	$month["year"][] = $current_year;
	$month["days"][] = cal_days_in_month( CAL_GREGORIAN, $current_month, $current_year );


}

?>

<script type="text/javascript">

jQuery(document).ready(function(){

	jQuery("#edButtonHTML").remove();

	jQuery("select#event_month").change(function(){

		var select_index = jQuery(this).find("option:selected").index();

		jQuery("span#year-label").html( month_data.year[ select_index ] );
		jQuery("span#year").val( month_data.year[ select_index ] );
		jQuery("select#day").find("option").remove();

		for( var y = 1; y <= month_data.days[ select_index ]; y++ ){

			jQuery("select#day").append('<option value="'+ y +'">'+ y +'</option>');


		}

	});

	jQuery("input.link-url").change(function(){

		var linkurl = jQuery(this).val();

		if( linkurl.slice(0,7) != "http://" ){

			jQuery(this).addClass("texterror");

		}else{

			jQuery(this).removeClass("texterror");

		}

	});

	jQuery("input#end_disable").change(function(){

		if( jQuery(this).is(":checked") ){
/* 			jQuery("select#end_hour, select#end_minute, select#end_convention, span#end_divider").attr("disabled", "disabled"); */
			jQuery("select#end_hour, select#end_minute, select#end_convention, span#end_divider").fadeTo("slow", 0.0);
		}else{
			jQuery("select#end_hour, select#end_minute, select#end_convention, span#end_divider").fadeTo("slow", 1.0);
/* 			jQuery("select#end_hour, select#end_minute, select#end_convention, span#end_divider").removeAttr("disabled"); */
		}

	});

	jQuery("input#start_disable").change(function(){

		if( jQuery(this).is(":checked") ){
/* 			jQuery("select#start_hour, select#start_minute, select#start_convention, span#start_divider").attr("disabled", "disabled"); */
			jQuery("select#start_hour, select#start_minute, select#start_convention, span#start_divider").fadeTo("slow", 0.0);
		}else{
			jQuery("select#start_hour, select#start_minute, select#start_convention, span#start_divider").fadeTo("slow", 1.0);
/* 			jQuery("select#start_hour, select#start_minute, select#start_convention, span#start_divider").removeAttr("disabled"); */
		}

	});

});

var month_data = <?php echo json_encode($month); ?>;

</script>


<div class="event-detail">
	<div class="label-wrap"><label for="duration">Event Venue:</label></div><textarea name="venue" style="width:270px;" /><?php echo $event->venue; ?></textarea> <span></span>
</div>

<div class="event-detail">
	<div class="label-wrap"><label for="address">Event Link:</label></div><input type="text" name="web_address" value="<?php echo $event->web_address; ?>" autocomplete="off" style="width:270px;" class="link-url <?php echo ( substr( $event->web_address, 0, 7) != "http://" && !empty( $event->web_address ) )?"texterror":""; ?>" /> <span>Optional web address.</span>
</div>

<div class="event-detail">
	<div class="label-wrap"><label for="duration">Date:</label></div>
	<select name="month" id="event_month">

		<option value="1" <?php echo ( $event->month == 1 ) ? "selected":""; ?>>January</option>
		<option value="2" <?php echo ( $event->month == 2 ) ? "selected":""; ?>>February</option>
		<option value="3" <?php echo ( $event->month == 3 ) ? "selected":""; ?>>March</option>
		<option value="4" <?php echo ( $event->month == 4 ) ? "selected":""; ?>>April</option>
		<option value="5" <?php echo ( $event->month == 5 ) ? "selected":""; ?>>May</option>
		<option value="6" <?php echo ( $event->month == 6 ) ? "selected":""; ?>>June</option>
		<option value="7" <?php echo ( $event->month == 7 ) ? "selected":""; ?>>July</option>
		<option value="8" <?php echo ( $event->month == 8 ) ? "selected":""; ?>>August</option>
		<option value="9" <?php echo ( $event->month == 9 ) ? "selected":""; ?>>September</option>
		<option value="10" <?php echo ( $event->month == 10 ) ? "selected":""; ?>>October</option>
		<option value="11" <?php echo ( $event->month == 11 ) ? "selected":""; ?>>November</option>
		<option value="12" <?php echo ( $event->month == 12 ) ? "selected":""; ?>>December</option>

	</select>
	<select name="day" id="day">
		<?
		for( $y = 1; $y <= $month["days"][0]; $y++ ){
		?>
			<option value="<?php echo $y; ?>" <?php echo ($event->day == $y)?'selected="selected"':''; ?>><?php echo $y; ?></option>
		<?
		}
		?>
	</select>
	<select name="year" id="year">
		<option value="2015" <?php echo ($event->start_year == 2015)?"selected":""; ?>>2015</option>
		<option value="2016" <?php echo ($event->start_year == 2016)?"selected":""; ?>>2016</option>
		<option value="2017" <?php echo ($event->start_year == 2017)?"selected":""; ?>>2017</option>
		<option value="2018" <?php echo ($event->start_year == 2018)?"selected":""; ?>>2018</option>
	</select>
</div>

<div class="event-detail">
	<div class="label-wrap"><label for="duration">Submitted Date:</label></div>
	<input type="text" style="width:270px;"name="user_submitted_date" value="<?php echo $event->user_submitted_date; ?>">&nbsp;<span>Please enter this date into the selectboxes above.</span>
</div>

<div class="event-detail">
	<div class="label-wrap"><label for="duration">Time:</label></div>
	<input type="text" style="width:270px;"name="event_time" value="<?php echo $event->event_time; ?>">&nbsp;<span>Example: 3:00 - 6:00PM.</span>
</div>

<div class="event-detail">
	<div class="label-wrap"><label for="duration">Submitted File:</label></div>
	<input type="text" style="width:270px;"name="user_submitted_file" value="<?php echo $event->user_submitted_file; ?>">&nbsp;<span>User submitted file URL.</span>
</div>

<?

}

add_action('save_post', 'update_event_meta');

function update_event_meta(){

	global $post;

	//date_default_timezone_set('UTC');

	if( $post->post_type == "event" && ( isset($_POST['save']) || isset($_POST['publish']) ) ){

		$start_hour   = ( empty( $_POST['start_hour'] ) )?12:$_POST['start_hour'];
		$start_minute = ( empty( $_POST['start_minute'] ) )?1:$_POST['start_minute'];
		$end_hour     = ( empty( $_POST['end_hour'] ) )?12:$_POST['end_hour'];
		$end_minute   = ( empty( $_POST['end_minute'] ) )?1:$_POST['end_minute'];
		$start_conv   = $_POST['start_convention'];
		$end_conv     = $_POST['end_convention'];
		$event_time   = $_POST['event_time'];

		$date = $_POST['month'] . "/" . $_POST['day'] . "/" . $_POST['year'];

		$start_time = $_POST['start_hour'] . ":" . $_POST['start_minute'] . " " . $start_conv;
		$end_time = $_POST['end_hour'] . ":" . $_POST['end_minute'] . " " . $end_conv;


		if( $_POST['start_disable'] ){
			$start_disable = "true";
		}

		if( $_POST['end_disable'] ){
			$end_disable = "true";
		}

		$start_time = strtotime( $date );
		$end_time = strtotime( $date );

		$event_time = strtoupper( str_replace( "-", " - ", $_POST["event_time"] ) );

		update_post_meta( $post->ID, "venue", $_POST["venue"] );
		update_post_meta( $post->ID, "web_address", $_POST["web_address"] );
		update_post_meta( $post->ID, "user_submitted_date", $_POST["user_submitted_date"] );
		update_post_meta( $post->ID, "user_submitted_file", $_POST["user_submitted_file"] );

		update_post_meta( $post->ID, "end_disable", $end_disable );

		update_post_meta( $post->ID, "end_disable", $end_disable );
		update_post_meta( $post->ID, "start_disable", $start_disable );
		update_post_meta( $post->ID, "end_time", $end_time );
		update_post_meta( $post->ID, "start_time", $start_time );
		
		update_post_meta( $post->ID, "event_time", $event_time );
		
		

	}

}

add_filter( 'manage_edit-event_columns', 'edit_event_columns' ) ;

function edit_event_columns( $columns ) {

	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __( 'Title' ),
		'event_date' => __( 'Date' ),
		'date' => __( 'Published' )
	);

	return $columns;

}

add_action( 'manage_event_posts_custom_column', 'manage_event_columns', 10, 2 );

function manage_event_columns( $column, $post_id ) {

	global $post;

	date_default_timezone_set('UTC');

	switch( $column ) {

		case "venue":

			$venue       = get_post_meta( $post_id, 'venue', true );
			$web_address = get_post_meta( $post_id, 'web_address', true );

			if ( empty( $venue ) ){

				echo __( 'Unknown' );

			}else{

				if( !empty( $web_address ) ){

					echo '<a target="new" href="'.$web_address.'">'.ucfirst( $venue ).'</a>';

				}else{

					echo ucfirst( $venue );

				}

			}

		break;

		case "event_date":

			$date       = get_post_meta( $post_id, 'start_time', true );
			$event_time = get_post_meta( $post_id, 'event_time', true );

			if( @date( "F j, Y, g:ia", $date ) ){

				echo date( 'F j, Y', $date );

				if( !empty($event_time) ){

					$preposition = ( strpos( $event_time, "-" ) )?"from":"at";

					echo " " . $preposition . " " . $event_time;

				}

			}else{

				echo __( 'Unknown' );

			}

		break;

	}

}


add_filter( 'parse_query', 'event_posts_filter' );

function event_posts_filter( $query ){
    global $pagenow;
    $type = 'event';
    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }
    if ( 'event' == $type && is_admin() && $pagenow=='edit.php' && isset($_GET['event_filter']) && $_GET['event_filter'] != '') {
        $query->query_vars['meta_key'] = 'calendar';
        $query->query_vars['meta_value'] = $_GET['event_filter'];
    }
}


add_filter( 'manage_edit-event_sortable_columns', 'event_sortable_columns' );

function event_sortable_columns( $columns ) {

	$columns['event_date'] = 'event_date';

	return $columns;
}

?>