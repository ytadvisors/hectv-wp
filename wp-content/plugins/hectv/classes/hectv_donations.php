<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Donations extends HECTV_Routes  implements HECTV_Custom_Post_Interface {
    public $post_type;
    public $param_list;

    function __construct($post_type){

        $this->post_type = $post_type;
        $this->param_list = [];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }

    /**
     * Callback function registers the event post type
     */
    public function register_post_type()
    {
        $labels = array(
            'name' => _x( 'Donations', 'post type general name' ), // Tip: _x('') is used for localization
            'singular_name' => _x( 'Donation', 'post type singular name' ),
            'add_new' => _x( 'Add New', 'video' ),
            'add_new_item' => __( 'Add New Donation' ),
            'edit_item' => __( 'Edit Donation' ),
            'new_item' => __( 'New Donation' ),
            'view_item' => __( 'View Donation' ),
            'search_items' => __( 'Search Donations' ),
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
            'rewrite' => array( 'slug' => 'magazine', 'with_front' => false ),
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-heart',
            'hierarchical' => false,
            'supports' => array( 'title', 'editor', 'thumbnail' ),
            'show_in_rest' => true,
            'menu_position' => 5,
            'rest_base' => $this->post_type,
        );

        register_post_type( $this->post_type, $args );
    }


    public function init()
    {
        add_filter( "init", array( $this, 'register_post_type' ), 0 );
    }
}