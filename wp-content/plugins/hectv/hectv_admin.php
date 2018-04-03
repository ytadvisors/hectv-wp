<?php

namespace HECTV;

use HECTV\Classes;

/**
 * Class HECTV_Admin
 * @package HECTV
 */
class HECTV_Admin {

    private $api;

    public function __construct()
    {
        $this->init();
    }

    public function get_comments( $object, $field_name, $request ) {
        return get_comments( array( 'post_id' => $object[ 'id' ] ) );
    }


    public function get_categories( $object, $field_name, $request ) {
        return get_the_category( $object[ 'id' ] );
    }

    public function get_articles($args, $request){
        $parameter_value  = $request->get_param( "articles" );
        if ( ! empty( $parameter_value ) ) {
            return array("meta_query" => array(array(
                    'key' => 'is_video',
                    'value' => 0,
                    "compare" => "="
                )));
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

    public function modify_rest_routes( $routes ) {
        if(is_array($routes['/wp/v2/lb_playlist'][0]['args']['orderby']['enum']))
            array_push( $routes['/wp/v2/lb_playlist'][0]['args']['orderby']['enum'], 'meta_value' );
        return $routes;
    }

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
                'methods' => 'GET',
                'callback' => array( $this, 'get_articles' ),
            )
        );
        register_rest_field( 'post',
            'categories',
            array(
                'get_callback' 	  => array( $this, 'get_categories'),
                'update_callback' => null,
                'schema' 		  => null,
            ) );


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

    function show_excerpt( $user_login, $user ) {
        $unchecked = get_user_meta( $user->ID, 'metaboxhidden_post', true );
        $key = array_search( 'postexcerpt', $unchecked );
        if ( FALSE !== $key ) {
            array_splice( $unchecked, $key, 1 );
            update_user_meta( $user->ID, 'metaboxhidden_post', $unchecked );
        }
        return true;
    }



    public function init()
    {

        $this->api = [
            "events" => new Classes\HECTV_Events('event'),
            'live_videos' => new Classes\HECTV_Live_Videos('livevideos'),
            "magazines" => new Classes\HECTV_Magazines('magazine'),
            "event_categories" => new Classes\HECTV_Taxonomy('event_type', "event", "Event Categories"),
            "keywords" => new Classes\HECTV_Taxonomy('keyword', "lb_playlist", "Keywords"),
            "topics" => new Classes\HECTV_Taxonomy('topic', "lb_playlist", "Topics", true),
            "type" => new Classes\HECTV_Taxonomy('type', "magazine", "Type", true)

        ];
        add_filter("rest_post_query", array($this, "get_articles"), 10, 2);
        add_filter('rest_endpoints', array( $this, 'modify_rest_routes'));
        add_filter('rest_query_vars', array( $this, 'allow_meta_query' ));
        add_filter( 'wp_headers', array($this, 'cors_headers' ), 11, 1 );
        add_filter( 'acf/prepare_field/name=duration', array( $this, 'format_duration') );
        add_action( 'rest_api_init', array($this, 'register_rest_fields' ));
        add_action( 'wp_enqueue_scripts', array($this, 'load_dashicons') );
        add_action( 'wp_login', array($this, 'show_excerpt'), 10, 2 );
        register_nav_menu( 'primary', __( 'Navigation Menu', 'hectv' ) );
        
    }
}