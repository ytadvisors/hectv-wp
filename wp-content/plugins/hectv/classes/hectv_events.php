<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Events extends HECTV_Routes  implements HECTV_Custom_Post_Interface {
    public $post_type;
    public $param_list;

    function __construct($post_type){

        $this->post_type = $post_type;
        $this->param_list = [];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }

    /*
     *  SETUP ADMINISTRATIVE PANEL
     */

    /**
     * Callback function loads css and js files for the admin
     */
    public function load_scripts() {
        wp_register_style( 'event-styles',  plugins_url() . '/hectv/assets/css/events.css' );
        wp_register_script( 'event-scripts',  plugins_url() . '/hectv/assets/js/events.js', 'jquery' );
        wp_enqueue_style( 'event-styles' );
        wp_enqueue_script( 'event-scripts' );
    }

    /**
     * Callback function registers the event post type
     */
    public function register_post_type()
    {
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

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => 'events', 'with_front' => false ),
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-calendar',
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array( 'title', 'editor', 'thumbnail' ),
            'show_in_rest' => true,
            'rest_base' => $this->post_type,
        );

        register_post_type( $this->post_type, $args );
    }


    public function init()
    {
        add_filter( "init", array( $this, 'register_post_type' ), 0 );
        add_action( "manage_{$this->post_type}_custom_column", 'manage_columns', 10, 2 );
        add_filter( "manage_edit-{$this->post_type}_sortable_columns", array( $this, 'get_sortable_columns'), 1 );
        add_filter( "manage_edit-{$this->post_type}_columns", array( $this, 'edit_columns') ) ;
        add_filter( "parse_query", array( $this, 'filter_posts') );
        add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ));
        add_action( "save_post_{$this->post_type}", array( $this, 'save_post'));
    }
}