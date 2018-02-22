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
        array_push( $routes['/wp/v2/edvideos'][0]['args']['orderby']['enum'], 'meta_value' );
        array_push( $routes['/wp/v2/edplans'][0]['args']['orderby']['enum'], 'meta_value' );
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
        /*$this->api = [
            "vid_cat" => new Classes\HECTV_Taxonomy('vid_cat', "edvideos", "Video Category"),
            "hec_kw" => new Classes\HECTV_Taxonomy('hec_kw', "edvideos", "HEC Keyword"),
            "edvideos" => new Classes\HECTV_Videos('edvideos'),
            "livevideos" =>  new Classes\HECTV_Live_Videos('livevideos'),
            "edtestimonial" => new Classes\HECTV_Testimonials('edtestimonial'),
            "edplans" => new Classes\HECTV_Plans('edplans'),
            "tool" => new Classes\HECTV_Tools('tool'),
            "edschedule" => new Classes\HECTV_Schedule('edschedule'),
            "edplaylist" => new Classes\HECTV_Playlist('edplaylist'),
            "edusers" => new Classes\HECTV_Users('edusers')
        ];*/

        $this->api = [
            "event_categories" => new Classes\HECTV_Taxonomy('event_type', "event", "Event Categories"),
            "events" => new Classes\HECTV_Events('event')
        ];
        add_filter('rest_endpoints', array( $this, 'modify_rest_routes'));
        add_filter('rest_query_vars', array( $this, 'allow_meta_query' ));
        add_action( 'rest_api_init', array($this, 'register_rest_fields' ));
        add_action( 'after_setup_theme', array($this, "setup_admin") );
        add_filter( 'wp_headers', array($this, 'cors_headers' ), 11, 1 );
        add_action( 'wp_enqueue_scripts', array($this, 'load_dashicons') );
        
    }
}