<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Live_Videos extends HECTV_Routes  implements HECTV_Custom_Post_Interface {
    public $post_type;
    public $param_list;

    function __construct($post_type){

        $this->post_type = $post_type;
        $this->param_list = ["start"];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }

    public function param_start( $args, $request ){
        $current_time   = time();
        $meta_query = array();
        if ( ! empty( $current_time  ) ) {
            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key'     => 'start',
                    'value'   => $current_time,
                    'compare' => '<',
                ),
                array(
                    'key'     => 'end',
                    'value'   => $current_time,
                    'compare' => '>',
                ),
            );
        }
        return $this->getNewMetaQuery($args, $meta_query);
    }

    public function register_post_type()
    {
        $labels = array(
            'name' => _x( 'Live Videos', 'post type general name' ), // Tip: _x('') is used for localization
            'singular_name' => _x( 'Live Video', 'post type singular name' ),
            'add_new' => _x( 'Add New', 'video' ),
            'add_new_item' => __( 'Add New Live Video' ),
            'edit_item' => __( 'Edit Live Video' ),
            'new_item' => __( 'New Live Video' ),
            'view_item' => __( 'View Live Video' ),
            'search_items' => __( 'Search Live Videos' ),
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
            'capability_type' => 'page',
            'menu_icon' => 'dashicons-video-alt3',
            'hierarchical' => false,
            'supports' => array( 'title', 'editor','comments', 'author' ),
            'show_in_rest' => true,
            'menu_position' => 7,
            'rest_base' => $this->post_type,
        );
        register_post_type( $this->post_type, $args );
    }


    public function init()
    {
        add_filter( 'init', array( $this, 'register_post_type' ), 0 );
    }
}