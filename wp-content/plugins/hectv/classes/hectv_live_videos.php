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
            'name'                  => _x( 'Live Videos', 'Post Type General Name', 'hectv' ),
            'singular_name'         => _x( 'Live Video', 'Post Type Singular Name', 'hectv' ),
            'menu_name'             => __( 'Live Videos', 'hectv' ),
            'name_admin_bar'        => __( 'Live Video', 'hectv' ),
            'archives'              => __( 'Video Archives', 'hectv' ),
            'attributes'            => __( 'Video Attributes', 'hectv' ),
            'parent_item_colon'     => __( 'Parent Video:', 'hectv' ),
            'all_items'             => __( 'All Videos', 'hectv' ),
            'add_new_item'          => __( 'Add New Video', 'hectv' ),
            'add_new'               => __( 'Add New', 'hectv' ),
            'new_item'              => __( 'New Video', 'hectv' ),
            'edit_item'             => __( 'Edit Video', 'hectv' ),
            'update_item'           => __( 'Update Video', 'hectv' ),
            'view_item'             => __( 'View Video', 'hectv' ),
            'view_items'            => __( 'View Videos', 'hectv' ),
            'search_items'          => __( 'Search Videos', 'hectv' ),
            'not_found'             => __( 'Not found', 'hectv' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'hectv' ),
            'featured_image'        => __( 'Featured Image', 'hectv' ),
            'set_featured_image'    => __( 'Set featured image', 'hectv' ),
            'remove_featured_image' => __( 'Remove featured image', 'hectv' ),
            'use_featured_image'    => __( 'Use as featured image', 'hectv' ),
            'insert_into_item'      => __( 'Insert into video', 'hectv' ),
            'uploaded_to_this_item' => __( 'Uploaded to this video', 'hectv' ),
            'items_list'            => __( 'Video list', 'hectv' ),
            'items_list_navigation' => __( 'Video list navigation', 'hectv' ),
            'filter_items_list'     => __( 'Filter videos list', 'hectv' ),
        );
        $args = array(
            'label'                 => __( 'Live Video', 'hectv' ),
            'description'           => __( 'Live Video Post Type', 'hectv' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor','comments', 'author' ),
            'taxonomies'            => array( 'hec_kw', 'vid_cat' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base'             => $this->post_type,
        );
        register_post_type( $this->post_type, $args );
    }


    public function init()
    {
        add_filter( 'init', array( $this, 'register_post_type' ), 0 );
    }
}