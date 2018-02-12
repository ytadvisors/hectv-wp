<?php
/*
 *  Author: Todd Motto | @toddmotto
 *  URL: html5blank.com | @html5blank
 *  Custom functions, support, custom post types and more.
 */

/*------------------------------------*\
	External Modules/Files
\*------------------------------------*/

// require_once('_/inc/videos.php'); /* Video Custom Post Type Declaration */
require_once('_/inc/new-videos.php'); /* New Video Custom Post Type Declaration */
require_once('_/inc/playlists.php'); /* Playlist Custom Post Type Declaration */
require_once('_/inc/magazines.php'); /* Magazine Custom Post Type Declaration */
require_once('_/inc/querytools.php'); /* Query Tools */
require_once('_/inc/education-tools.php'); /* Education Taxonomies */
require_once('_/inc/gallery.php'); /* Gallery HTML */
require_once('_/inc/mail-handler.php'); /* Mail Handler */
require_once('_/inc/event-submission.php'); /* Mail Handler */
require_once('_/inc/user-login.php'); /* User Login */
require_once('_/inc/user-sign-up.php'); /* User Sign-up */
require_once('_/inc/user-roles.php'); /* User Roles */
require_once('_/inc/check-username-availability.php');

require_once('module-ad-slat.php'); /* Ad Unit */
require_once('module-marquee.php'); /* Marquee HTML */
require_once('module-interactive-takeover.php'); /* Interactive Takeover HTML */
require_once('module-tag-cloud.php'); /* Tag Cloud HTML */
require_once('module-archive-list.php'); /* Archive List HTML */
require_once('module-newsletter-signup.php'); /* News Letter Signup HTML */
require_once('module-facebook-comments.php'); /* Facebook Comments HTML */
require_once('module-media-html.php'); /* Media Slat Post Object / Function */
require_once('module-media-slat.php'); /* Media Slat HTML / Function */
require_once('module-rsvp-event.php'); /* RSVP Slat / Function */
require_once('module-all-episodes.php'); /* All Episodes / Function */
require_once('module-series-episodes-list.php'); /* Episode Series List */
require_once('module-submit-event-cta.php');

require_once('module-tv-schdedule-slat.php'); /* TV Schedule HTML / Function */
require_once('module-partners.php'); /* Partners HTML / Function */
require_once('module-trending.php'); /* Trending HTML / Function */
require_once('module-blog-recent.php'); /* Blog Recent / Function */
require_once('module-blog-post.php'); /* Blog Post / Function */
require_once('module-featured-live.php'); /* Featured Live Post / Function */

require_once('module-theater-magazine.php'); /* Theater magazine / Function */
require_once('module-right-flex.php'); /* Right Flex Module */

require_once('_/inc/playlist-functions.php'); /* Education Taxonomies */
require_once('_/inc/series-page-meta.php'); /* Series Page Meta */

require_once('_/inc/logged-out-redirect.php');
require_once('_/inc/logged-in-redirect.php');

require_once('_/inc/preferred-recent-episode-meta.php'); /* Metabox for preferred recent episodes */
require_once('_/inc/logged-out-redirect.php'); /* User Roles */

require_once('_/inc/user-meta-fields.php');
require_once('_/inc/user-save-video.php');
require_once('_/inc/remove-saved-video.php');



//require_once('_/inc/events.php'); /* Events Tool -- To Do: Convert to WP Plugin........ */


if( $_GET['doit'] == 'now' ){

	add_action('init', function(){
		
		return;
		
		$videos = get_posts( array( 'post_type' => array( 'lb_playlist', 'lb_video' ), 'posts_per_page' => -1 ) );
		
		$out = '';
		
		foreach( $videos as $video ){
			
			$return = array();
			$video_data = get_post_custom( $video->ID );
			
			$video_files = unserialize( $video_data['video_files'][0] );
			$video_files_string = ( is_array( $video_files ) ) ? implode( ', ', $video_files['location'] ) : 'null'; 
			
			$return[] = '"' . $video->ID . '"';
			$return[] = '"' . $video->post_title . '"';
			$return[] = '"' . get_the_title( $video->post_parent ) . '"';
			$return[] = '"' . $video_files_string . '"';
			$return[] = '"' . '** NEW YOUTUBE **' . '"';
			$return[] = '"' . '** NEW VIMEO **' . '"';
			
			$out .= implode(",", $return) . "\r\n";
			
		}
		
		print $out;
		
		die;
		
	});

}


if( $_GET['doit'] == 'again' ){
	
	$csv = array_map('str_getcsv', file( __DIR__ . '/_/episode_dump.csv'));
	$edited = 0;
	foreach( $csv as $item ){
				
		if( $item[0] != '' ){
		
			update_post_meta( $item[0], 'broadcast_location', $item[1] );
			$edited++;
		
		}
		
	}
	
	echo $edited;
	
	die;
	
}

/*------------------------------------*\
	Important Definitions
\*------------------------------------*/

define( "BITRATES", "300, 800, 1500, 3000" );

/*------------------------------------*\
	Theme Support
\*------------------------------------*/

if ( !isset($content_width) ) {

    $content_width = 1460;

}

if (function_exists('add_theme_support')) {

    // Add Menu Support
    add_theme_support('menus');

    // Add Thumbnail Theme Support
    add_theme_support('post-thumbnails');
    add_image_size('large', 700, '', true); // Large Thumbnail
    add_image_size('medium', 250, '', true); // Medium Thumbnail
    add_image_size('small', 120, '', true); // Small Thumbnail
	add_image_size('video-thumb', 610, 360, true); // Large Thumbnail

	add_image_size('event-thumb', 420, 246, true); // Events Thumbnail

	add_image_size('media-medium', 520, 294, true); // Media Medium Thumbnail
	add_image_size('marquee-large', 1024, 576, true); // Marquee Large Thumbnail

    // Enables post and comment RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Localisation Support
    load_theme_textdomain('hectv-responsive', get_template_directory() . '/languages');

}

/*------------------------------------*\
	Functions
\*------------------------------------*/

// HEC-TV navigation
function hectv_nav() {

	wp_nav_menu(
	array(
		'theme_location'  => 'header-menu',
		'menu'            => '',
		'container'       => 'div',
		'container_class' => 'menu-{menu slug}-container',
		'container_id'    => '',
		'menu_class'      => 'menu',
		'menu_id'         => '',
		'echo'            => true,
		'fallback_cb'     => 'wp_page_menu',
		'before'          => '',
		'after'           => '',
		'link_before'     => '',
		'link_after'      => '',
		'items_wrap'      => '<ul>%3$s</ul>',
		'depth'           => 0,
		'walker'          => ''
		)
	);

}

// Load HEC-TV Responsive scripts (header.php)
function hectv_header_scripts() {

    if ($GLOBALS['pagenow'] != 'wp-login.php' && !is_admin()) {

    	wp_register_script('conditionizr', get_template_directory_uri() . '/_/js/lib/conditionizr-4.3.0.min.js', array(), '4.3.0'); // Conditionizr
        wp_enqueue_script('conditionizr');

        wp_register_script('modernizr', get_template_directory_uri() . '/_/js/lib/modernizr-2.7.1.min.js', array(), '2.7.1'); // Modernizr
        wp_enqueue_script('modernizr');

        wp_register_script('primary-functions', get_template_directory_uri() . '/_/js/functions.js', array('jquery'), '1.2.6'); // Custom scripts
        wp_enqueue_script('primary-functions');
    }

}


// Load HEC-TV Responsive styles
function hectv_styles() {

    wp_register_style('normalize', get_template_directory_uri() . '/_/css/normalize.min.css', array(), '1.0', 'all');
    wp_enqueue_style('normalize'); // Enqueue it!

	wp_register_style('owl-carousel-css', get_template_directory_uri() . '/_/css/owl.carousel.css', array(), '1.0', 'all');
    wp_enqueue_style('owl-carousel-css'); // Enqueue it!

    wp_register_style('font-awesome', get_template_directory_uri() . '/_/css/font-awesome.min.css', array(), '4.3.0', 'all');
    wp_enqueue_style('font-awesome'); // Enqueue it!

    wp_register_style('fancy-box', get_template_directory_uri() . '/_/css/fancy-box.css', array(), '2.1.5', 'all');
    wp_enqueue_style('fancy-box'); // Enqueue it!

    wp_register_style('primary-css', get_template_directory_uri() . '/style.css', array(), '1.2.9', 'all');
    wp_enqueue_style('primary-css'); // Enqueue it!

}

// Register HEC-TV Responsive Navigation
function register_hectv_menu() {

    register_nav_menus(array(
        'header-menu' => __('Header Menu', 'hectv-responsive'),
        'sidebar-menu' => __('Sidebar Menu', 'hectv-responsive'),
        'footer-menu' => __('Footer Menu', 'hectv-responsive'),
        'social-menu' => __('Social Menu', 'hectv-responsive'),
        'mobile-menu' => __('Mobile Menu', 'hectv-responsive')
    ));

}

// Remove the <div> surrounding the dynamic navigation to cleanup markup
function hectv_menu_args($args = '') {

    $args['container'] = false;
    return $args;

}

// Remove Injected classes, ID's and Page ID's from Navigation <li> items
function my_css_attributes_filter($var) {

    return is_array($var) ? array() : '';

}

// Remove invalid rel attribute values in the categorylist
function remove_category_rel_from_category_list($thelist) {

    return str_replace('rel="category tag"', 'rel="tag"', $thelist);

}

// Add page slug to body class, love this - Credit: Starkers Wordpress Theme
function add_slug_to_body_class($classes) {

    global $post;
    if (is_home()) {
        $key = array_search('blog', $classes);
        if ($key > -1) {
            unset($classes[$key]);
        }
    } elseif (is_page()) {
        $classes[] = sanitize_html_class($post->post_name);
    } elseif (is_singular()) {
        $classes[] = sanitize_html_class($post->post_name);
    }

    return $classes;

}

// Custom Excerpts
// Create 20 Word Callback for Index page Excerpts, call using hectv_excerpt('hectv_index');
function hectv_index($length) {

    return 20;

}

// Create 40 Word Callback for Custom Post Excerpts, call using hectv_excerpt('hectv_custom_post');
function hectv_custom_post($length) {

    return 40;

}

// Create the Custom Excerpts callback
function hectv_excerpt($length_callback = '', $more_callback = '') {

    global $post;
    if (function_exists($length_callback)) {
        add_filter('excerpt_length', $length_callback);
    }
    if (function_exists($more_callback)) {
        add_filter('excerpt_more', $more_callback);
    }
    $output = get_the_excerpt(); // Hello 1234
    $output = apply_filters('wptexturize', $output); // 'Hello 1234'
    $output = apply_filters('convert_chars', $output); // 'Jello 1234'
    $output = str_replace("&nbsp; ", "", $output);
    $output = '<p>' . $output . '</p>'; // '<p>Jello 1234</p>'
    echo $output; // Prints '<p>Jello 1234</p>'

}

// Custom View Article link to Post
function hectv_article($more) {

    global $post;
    return '... <a class="view-article" href="' . get_permalink($post->ID) . '">' . __('See More.', 'html5blank') . '</a>';

}

// Remove Admin bar
function remove_admin_bar() {

    return false;

}

// Remove 'text/css' from our enqueued stylesheet
function hectv_style_remove($tag) {

    return preg_replace('~\s+type=["\'][^"\']++["\']~', '', $tag);

}

// Remove thumbnail width and height dimensions that prevent fluid images in the_thumbnail
function remove_thumbnail_dimensions( $html ) {

    $html = preg_replace('/(width|height)=\"\d*\"\s/', "", $html);
    return $html;

}

// Custom Gravatar in Settings > Discussion
function html5blankgravatar ($avatar_defaults) {

    $myavatar = get_template_directory_uri() . '/img/gravatar.jpg';
    $avatar_defaults[$myavatar] = "Custom Gravatar";
    return $avatar_defaults;

}

// Threaded Comments
function enable_threaded_comments() {

    if (!is_admin()) {
        if (is_singular() AND comments_open() AND (get_option('thread_comments') == 1)) {
            wp_enqueue_script('comment-reply');
        }
    }

}

function hectv_get_excerpt_by_id( $post_id, $excerpt_length = 35 ){

    $the_post    = get_post( $post_id );
    $the_excerpt = $the_post->post_content;
    $the_excerpt = strip_tags( strip_shortcodes( $the_excerpt ) );
    $words       = explode(' ', $the_excerpt, $excerpt_length + 1);

    if( count( $words ) > $excerpt_length ){

		array_pop($words);
		array_push($words, '…');
		$the_excerpt = implode(' ', $words);

    }

    $the_excerpt = '<p>' . str_replace( "&nbsp;", "", $the_excerpt ) . '</p>';

    return $the_excerpt;

}

function hectv_formatDuration( $value ){

	$h = floor( $value / 3600 );
	$m = floor( ( $value % 3600 ) / 60);
	$s = $value - ( $h * 3600 ) - ( $m * 60 );

	return sprintf('%02d:%02d:%02d', $h, $m, $s);

}

function hectv_convertDuration( $value ){

	list( $durationHours, $durationMinutes, $durationSeconds ) = explode( ":", $value );
	$duration = ($durationHours * 3600) + ($durationMinutes * 60) + $durationSeconds;

	return $duration;

}

add_action( 'init', 'hectv_custom_permalinks' );

function hectv_custom_permalinks() {

    add_rewrite_rule(
		'watch/topic/hec-tv-live/?$',
		'index.php?taxonomy=topic&tag_ID=10572&post_type=lb_playlist',
		'top'
	);
    
	add_rewrite_rule(
		'hectv-magazine/archive/([^/]+)/?',
		'index.php?pagename=hectv-magazine&magazinearchive=$matches[1]',
		'top'
	);

	add_rewrite_rule(
		'events/([0-9]+)/([^/]+)/?',
		'index.php?pagename=events&calendaryear=$matches[1]&calendarmonth=$matches[2]',
		'top'
	);

	add_rewrite_rule(
		'smil/(.+)/?',
		'index.php?p=18296&assetid=$matches[1]',
		'top'
	);

	add_rewrite_rule(
		'embed/([^/]+)/?',
		'index.php?pagename=embed&video_id=$matches[1]',
		'top'
	);

}

add_filter( 'query_vars', 'hectv_custom_query_vars' );

function hectv_custom_query_vars( $query_vars ) {

	$query_vars[] = 'calendarmonth';
	$query_vars[] = 'calendaryear';
	$query_vars[] = 'seriespage';
	$query_vars[] = 'assetid';
	$query_vars[] = 'video_id';

    return $query_vars;

}

function hectv_normalize_embeds_adjust_posts( $html ) {

	return remove_thumbnail_dimensions( $html );

}


function hectv_get_education_image_by_id( $postID ) {

	$postData       = get_post_custom_values( "education_content_0_episode_relationship", $postID );

	$firstEpisodeID = unserialize( $postData[0] );

	$recentImgID    = get_post_custom_values( "video_image", $firstEpisodeID[0] );

	$recentImg      = wp_get_attachment_image_src( $recentImgID[0], 'media-medium' );

	return $recentImg[0];


}

function hectv_get_series_image_by_id( $postID ){

	if( empty( $postID ) ){

		return "No ID provided.";

	}

	$video = get_post( $postID );

	$recent      = get_posts( array( 'post_parent' => $video->post_parent, 'post_type' => 'lb_playlist', 'order' => 'post_date', 'orderby' => 'DESC', 'posts_per_page' => 1 ) );
	$thumbnailID = get_post_custom_values( 'video_image', $recent[0]->ID );

	if( !is_array( $thumbnailID ) ){

		return false;

	}

	$recentImg   = wp_get_attachment_image_src( $thumbnailID[0], 'media-medium' );

	return $recentImg[0];

}

function hectv_get_content_by_id( $post_id = 0, $more_link_text = null, $stripteaser = false ){

    global $post;
    $post = &get_post($post_id);
    setup_postdata( $post, $more_link_text, $stripteaser );
    the_content();
    wp_reset_postdata( $post );

}


function hectv_add_http_to_url($url) {

    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {

	    $url = "http://" . $url;

    }

    return $url;

}

add_filter( 'embed_oembed_html', 'lb_custom_youtube_oembed' );

function lb_custom_youtube_oembed( $code ){
	
    if( stripos( $code, 'youtube.com' ) !== FALSE && stripos( $code, 'iframe' ) !== FALSE ){
        
        $code = str_replace( '<iframe', '<iframe class="youtube-player" type="text/html" ', $code );
        
	}
    return $code;
}


function hectv_video_shortcode( $data ){

	if( empty( $data['id'] ) ){

		return false;

	}

	if( $data['segment'] ){

		$hash  = "#!/" . urlencode( $data['segment'] ) . "/";

	}

	$video     = get_post( $data['id'] );
	$videoData = get_post_custom( $video->ID );

	if( $video->post_type != "lb_playlist" && $video->post_type != "lb_video" ) {

		return "Invalid Post Type";

	}

	$thumb     = wp_get_attachment_image_src( $videoData['video_image'][0], 'media-medium' );

	$html  = '<div class="video-shortcode">';
	$html .= '<a href="' . get_permalink( $video->ID ) . $hash . '">';
	$html .= '<div class="video-wrap clearfix" title="' . get_the_title( $video->ID ) . '">';
	$html .= '<div class="play-wrap clearfix">';
	$html .= '<img class="play" src="' . get_template_directory_uri() . '/_/graphics/play-button.png">';
	$html .= '</div>';
	$html .= '<img style="float:left;" src="' . $thumb[0] . '">';
	$html .= '</a>';
	$html .= '</div>';

	if( !empty( $data['description'] ) ){

		$html .= '<p>' . $data['description'] . '</p>';

	}

	$html .= '</div>';

	return $html;

}

add_shortcode( 'hectv_video', 'hectv_video_shortcode' );

add_filter( 'the_content', 'hectv_normalize_embeds_adjust_posts', 99 );

function hectv_custom_login_logo() {

	echo '<style type="text/css">.login h1 a { background-image: url("'.get_bloginfo('template_directory').'/_/graphics/hec-logo-head.png") !important; width: 326px !important; height: 170px !important; background-size: auto auto !important; background-position: center center !important; margin-bottom: 15px; } </style>';

}

add_action('login_head', 'hectv_custom_login_logo');

add_filter('login_headertitle', create_function(false,"return 'Higher Education Channel';"));

add_filter('login_headerurl', create_function(false,"return 'http://www.hectv.org';"));


/*------------------------------------*\
	Actions + Filters + ShortCodes
\*------------------------------------*/

// Add Actions
add_action('init', 'hectv_header_scripts'); // Add Custom Scripts to wp_head
// add_action('wp_print_scripts', 'hectv_conditional_scripts'); // Add Conditional Page Scripts
add_action('get_header', 'enable_threaded_comments'); // Enable Threaded Comments
add_action('wp_enqueue_scripts', 'hectv_styles'); // Add Theme Stylesheet
add_action('init', 'register_hectv_menu'); // Add HTML5 Blank Menu
//add_action('init', 'create_post_type_html5'); // Add our HTML5 Blank Custom Post Type

// Remove Actions
remove_action('wp_head', 'feed_links_extra', 3); // Display the links to the extra feeds such as category feeds
remove_action('wp_head', 'feed_links', 2); // Display the links to the general feeds: Post and Comment Feed
remove_action('wp_head', 'rsd_link'); // Display the link to the Really Simple Discovery service endpoint, EditURI link
remove_action('wp_head', 'wlwmanifest_link'); // Display the link to the Windows Live Writer manifest file.
remove_action('wp_head', 'index_rel_link'); // Index link
remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Prev link
remove_action('wp_head', 'start_post_rel_link', 10, 0); // Start link
remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // Display relational links for the posts adjacent to the current post.
remove_action('wp_head', 'wp_generator'); // Display the XHTML generator that is generated on the wp_head hook, WP version
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
remove_action('wp_head', 'rel_canonical');
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);

// Add Filters
add_filter('avatar_defaults', 'html5blankgravatar'); // Custom Gravatar in Settings > Discussion
add_filter('body_class', 'add_slug_to_body_class'); // Add slug to body class (Starkers build)
add_filter('widget_text', 'do_shortcode'); // Allow shortcodes in Dynamic Sidebar
add_filter('widget_text', 'shortcode_unautop'); // Remove <p> tags in Dynamic Sidebars (better!)
add_filter('wp_nav_menu_args', 'hectv_menu_args'); // Remove surrounding <div> from WP Navigation
// add_filter('nav_menu_css_class', 'my_css_attributes_filter', 100, 1); // Remove Navigation <li> injected classes (Commented out by default)
// add_filter('nav_menu_item_id', 'my_css_attributes_filter', 100, 1); // Remove Navigation <li> injected ID (Commented out by default)
// add_filter('page_css_class', 'my_css_attributes_filter', 100, 1); // Remove Navigation <li> Page ID's (Commented out by default)
add_filter('the_category', 'remove_category_rel_from_category_list'); // Remove invalid rel attribute
add_filter('the_excerpt', 'shortcode_unautop'); // Remove auto <p> tags in Excerpt (Manual Excerpts only)
add_filter('the_excerpt', 'do_shortcode'); // Allows Shortcodes to be executed in Excerpt (Manual Excerpts only)
add_filter('excerpt_more', 'hectv_article'); // Add 'View Article' button instead of [...] for Excerpts
// add_filter('show_admin_bar', 'remove_admin_bar'); // Remove Admin bar
add_filter('style_loader_tag', 'hectv_style_remove'); // Remove 'text/css' from enqueued stylesheet
add_filter('post_thumbnail_html', 'remove_thumbnail_dimensions', 10); // Remove width and height dynamic attributes to thumbnails
add_filter('image_send_to_editor', 'remove_thumbnail_dimensions', 10); // Remove width and height dynamic attributes to post images

// Remove Filters
remove_filter('the_excerpt', 'wpautop'); // Remove <p> tags from Excerpt altogether


/* Allow drafted pages to be parents! */

add_filter('page_attributes_dropdown_pages_args', 'lb_attributes_dropdown_pages_args', 1, 1);

function lb_attributes_dropdown_pages_args($dropdown_args) {

    $dropdown_args['post_status'] = array('publish','draft','private');

    return $dropdown_args;
    
}

add_action( 'init', 'lb_add_hidden_post_status' );

function lb_add_hidden_post_status(){
	register_post_status( 'hidden', array(
		'label'                     => 'Hidden',
		'public'                    => true,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Hidden <span class="count">(%s)</span>', 'Hidden <span class="count">(%s)</span>' )
	) );
}

add_action('admin_footer-post.php', 'lb_append_post_status_list');

function lb_append_post_status_list(){
	
     global $post;
     $complete = '';
     $label = '';
     if($post->post_type == 'lb_playlist' || $post->post_type == 'lb_video' ){
          if($post->post_status == 'hidden'){
               $complete = ' selected=\'selected\'';
               $label = '<span id=\'post-status-display\'> Hidden</span>';
          }
          echo '
          <script>
          jQuery(document).ready(function($){
               $("select#post_status").append("<option value=\'hidden\' '.$complete.'>Hidden</option>");
               $(".misc-pub-section label").append("'.$label.'");
          });
          </script>
          ';
     }
     
}



?>
