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

    public function setup_admin(){
        add_theme_support( 'post-thumbnails' );
    }

    public function get_comments( $object, $field_name, $request ) {
        return get_comments( array( 'post_id' => $object[ 'id' ] ) );
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

    public function init()
    {

        $this->api = [
            "events" => new Classes\HECTV_Events('event'),
            "videos" => new Classes\HECTV_Videos('lb_playlist'),
            "event_categories" => new Classes\HECTV_Taxonomy('event_type', "event", "Event Categories"),
            "keywords" => new Classes\HECTV_Taxonomy('keyword', "lb_playlist", "Keywords"),
            "topics" => new Classes\HECTV_Taxonomy('topic', "lb_playlist", "Topics", true),

        ];

        add_filter('rest_endpoints', array( $this, 'modify_rest_routes'));
        add_filter('rest_query_vars', array( $this, 'allow_meta_query' ));
        add_action( 'rest_api_init', array($this, 'register_rest_fields' ));
        add_action( 'after_setup_theme', array($this, "setup_admin") );
        add_action( 'wp_enqueue_scripts', array($this, 'load_dashicons') );
        add_filter( 'wp_headers', array($this, 'cors_headers' ), 11, 1 );
        
    }
}