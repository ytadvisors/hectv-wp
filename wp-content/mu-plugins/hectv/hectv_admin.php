<?php

namespace HECTV;

use HECTV\Classes;
use \Requests;

/**
 * Class HECTV_Admin
 * @package HECTV
 */
class HECTV_Admin {

    private $admin;

    public function __construct()
    {
        $this->init();
    }

    public function get_comments( $object, $field_name, $request ) {
        return get_comments( array( 'post_id' => $object[ 'id' ] ) );
    }


    public function get_thumbnail( $object, $field_name, $request ) {
        // ACF can be unavailable during plugin recovery or before activation.
        // REST responses must still succeed; the thumbnail field is optional.
        if ( ! function_exists( 'get_field' ) ) {
            return "";
        }

        if(\get_field("is_video", $object[ 'id' ]))
            $thumbnail_url = \get_field( "video_image", $object[ 'id' ] );
        else
            $thumbnail_url = \get_field( "post_header", $object[ 'id' ] );

        if(isset($thumbnail_url)){
            if(isset($thumbnail_url["sizes"]) && isset($thumbnail_url["sizes"]["large"]))
                return $thumbnail_url["sizes"]["large"];
            if(isset($thumbnail_url["url"]))
                return $thumbnail_url["url"];
        }
        return "";
    }

    public function get_articles($args, $request = null){
        if($request) {
            $parameter_value = $request->get_param("articles");
            if (!empty($parameter_value)) {
                $args["meta_query"] = array(
                    array(
                        'key' => 'is_video',
                        'value' => 0,
                        "compare" => "="
                    )
                );
            }
        }

        return $args;
    }

    public function format_duration($field){

        if($field["value"]){
            $value = $field["value"];
            $h = floor( $value / 3600 );
            $m = floor( ( $value % 3600 ) / 60);
            $s = $value - ( $h * 3600 ) - ( $m * 60 );

            $field["value"] = sprintf('%02d:%02d:%02d', $h, $m, $s);
            $field["disabled"] = true;
        }

        return $field;
    }


    // MAIN CALL BACKS

    public function allow_meta_query( $valid_vars ) {
        $valid_vars = array_merge( $valid_vars, array( 'meta_key', 'meta_value' ) );
        return $valid_vars;
    }

    public function register_rest_fields() {
        register_rest_field( 'post',
            'comments',
            array(
                'get_callback' 	  => array( $this, 'get_comments'),
                'update_callback' => null,
                'schema' 		  => null,
            ) );

        register_rest_route( 'hectv/v1',
            '/articles', array(
                'methods'   => \WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_articles' ),
            )
        );
        register_rest_field( 'post',
            'thumbnail',
            array(
                'get_callback' 	  => array( $this, 'get_thumbnail'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }

    function cors_headers( $headers ) {

        $headers['Access-Control-Allow-Origin']      = get_http_origin(); // Can't use wildcard origin for credentials requests, instead set it to the requesting origin

        // Access-Control headers are received during OPTIONS requests
        if ( 'OPTIONS' == $_SERVER['REQUEST_METHOD'] ) {

            if ( isset( $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] ) ) {
                $headers['Access-Control-Allow-Methods'] = 'GET, POST, PUT, OPTIONS';
            }

            if ( isset( $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ) ) {
                $headers['Access-Control-Allow-Headers'] = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'];
            }

        }

        return $headers;
    }

    function load_dashicons() {
        wp_enqueue_style( 'dashicons' );
    }

    /**
     * Metaboxes that are required for the post publishing workflow.
     *
     * @return array
     */
    function required_post_metaboxes() {
        $post_details_group = defined( 'HECTV_ACF_POST_DETAILS_KEY' )
            ? HECTV_ACF_POST_DETAILS_KEY
            : 'group_5a9bf131f2b91';

        return array( 'postexcerpt', 'acf-' . $post_details_group );
    }

    /**
     * Keep required post metaboxes visible even when a stale Screen Options
     * preference says they are hidden.
     *
     * The filter runs on the current request, so an editor who is already
     * signed in sees Post Details immediately after this code is deployed.
     *
     * @param array      $hidden Hidden metabox IDs.
     * @param \WP_Screen $screen Current admin screen.
     * @return array
     */
    function show_required_post_metaboxes( $hidden, $screen = null ) {
        if ( $screen && isset( $screen->id ) && 'post' !== $screen->id ) {
            return $hidden;
        }

        if ( ! is_array( $hidden ) ) {
            return array();
        }

        return array_values( array_diff( $hidden, $this->required_post_metaboxes() ) );
    }

    /**
     * Persist required post metaboxes as visible after login.
     *
     * WordPress stores hidden metabox IDs in user meta as an array. When the
     * key is missing or empty, get_user_meta(..., true) returns '' — and
     * array_search() fatals under typed PHP (critical-error page on wp-login).
     * The same user setting can also hide the ACF Post Details panel entirely.
     *
     * @param string   $user_login Login name.
     * @param \WP_User $user       Authenticated user.
     * @return true
     */
    function show_excerpt( $user_login, $user ) {
        $unchecked = get_user_meta( $user->ID, 'metaboxhidden_post', true );
        if ( ! is_array( $unchecked ) ) {
            return true;
        }
        $visible = $this->show_required_post_metaboxes( $unchecked );
        if ( $visible !== $unchecked ) {
            update_user_meta( $user->ID, 'metaboxhidden_post', $visible );
        }
        return true;
    }



    public function add_schedule($value, $post_id, $field)
    {
        $this->admin['schedule']->addSchedule($value, $post_id);
        return $value;
    }

    public function nullify_empty($value, $post_id, $field){

        if(empty($value)){
            return null;
        }

        return $value;
    }

    public function empty_space($value, $post_id, $field){

        if(empty($value)){
            return "";
        }

        return $value;
    }

    public function nullify_repeater($value, $post_id, $field){

        if(empty($value)){
            return null;
        } else{
            $keys = array_keys($value);
            $newValue = [];
            foreach($keys as $key){
                $val = array_filter($value[$key]);
                if(count($val) > 0)
                    $newValue[] = $val;
            }

            $newValue = array_filter($newValue);
            if(count($newValue) === 0)
                $newValue = null;
        }

        return $newValue;
    }

    public function nullify_array($value, $post_id, $field){

        if(empty($value)){
            return array();
        }

        return $value;
    }

    public function copy_empty_video($value, $post_id, $field){

        if(empty($value)){
            $value = get_field("video_image", $post_id);
        }

        if(empty($value)){
            return null;
        }

        return $value;
    }

    public function copy_empty_post($value, $post_id, $field){

        if(empty($value)){
            $value = get_field("post_header", $post_id);
        }

        if(empty($value)){
            return null;
        }

        return $value;
    }

    public function init()
    {
        $this->admin = [
            "events" => new Classes\HECTV_Events('event'),
            "donations" => new Classes\HECTV_Donations('donations'),
            "schedule" => new Classes\HECTV_Schedules('schedules'),
            'live_videos' => new Classes\HECTV_Live_Videos('livevideos'),
            "magazines" => new Classes\HECTV_Magazines('magazine'),
            "users" => new Classes\HECTV_Users('users'),
            "event_categories" => new Classes\HECTV_Taxonomy('event_category', "event", "Categories", true),
            "event_type" => new Classes\HECTV_Taxonomy('event_type', "event", "Tags"),
            "type" => new Classes\HECTV_Taxonomy('type', "magazine", "Type", true),
            "default" => new Classes\HECTV_Routes()
        ];
        add_filter("rest_post_query", array($this, "get_articles"), 10, 2);
        add_filter('rest_query_vars', array( $this, 'allow_meta_query' ));
        add_filter( 'wp_headers', array($this, 'cors_headers' ), 11, 1 );
        add_filter( 'acf/prepare_field/name=duration', array( $this, 'format_duration') );
        add_action( 'rest_api_init', array($this, 'register_rest_fields' ), 10, 2);
        add_action( 'wp_enqueue_scripts', array($this, 'load_dashicons') );
        add_action( 'wp_login', array($this, 'show_excerpt'), 10, 2 );
        add_filter( 'hidden_meta_boxes', array($this, 'show_required_post_metaboxes'), 10, 3 );
        register_nav_menu( 'primary', __( 'Navigation Menu', 'hectv' ) );
        add_action("acf/update_value/name=monthly_schedule", array($this, 'add_schedule'), 10, 3);
        add_filter('acf/format_value/type=image', array($this, 'nullify_empty'), 100, 3);
        add_filter('acf/format_value/type=relationship', array($this, 'nullify_empty'), 100, 3);
        add_filter('acf/format_value/type=gallery', array($this, 'nullify_empty'), 100, 3);
        add_filter('acf/format_value/type=text', array($this, 'nullify_empty'), 100, 3);
        add_filter('acf/format_value/type=tab', array($this, 'nullify_empty'), 100, 3);
        add_filter('acf/format_value/type=file', array($this, 'nullify_empty'), 100, 3);
        add_filter('acf/format_value/type=repeater', array($this, 'nullify_repeater'), 100, 3);
        add_filter('acf/format_value/type=post_object', array($this, 'nullify_empty'), 100, 3);
        add_filter('acf/format_value/name=embed_url', array($this, 'empty_space'), 100, 3);
        add_action("acf/format_value/name=post_header", array($this, 'copy_empty_video'), 10, 3); //need to fix

        $this->admin["default"]->setup_fields("page");
        $this->admin["default"]->setup_fields("post");
    }
}
