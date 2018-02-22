<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Plans extends HECTV_Routes  implements HECTV_Custom_Post_Interface {
    public $post_type;

    function __construct($post_type){
        $this->post_type = $post_type;
        $this->param_list = [
            'plan_id',
            'plan_name',
            'plan_price',
            'order_by',
            'order'
        ];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }


    public function param_default( $args, $request ){
        return $args;
    }


    public function param_order_by( $args, $request ){
        $orderby = $request->get_param( "order_by" );
        if ( ! empty( $orderby ) ) {
            $args["orderby"] = "meta_value_num";
            $args["meta_key"] = $orderby;
            $args["order"] = "desc";
        }
        return $this->getNewMetaQuery($args, array());
    }

    public function param_order( $args, $request ){
        $order = $request->get_param( "order" );
        if ( ! empty( $order ) ) {
            $args["order"] = $order;
        }
        return $this->getNewMetaQuery($args, array());
    }


    public function param_plan_id($args, $request)
    {
        return $this->helper_param_compare_values('plan_id', "=", $args, $request);
    }


    public function param_plan_name($args, $request)
    {
        return $this->helper_param_compare_values('plan_name', "=", $args, $request);
    }


    public function param_plan_price($args, $request)
    {
        return $this->helper_param_compare_values('plan_price', "=", $args, $request);
    }

    public function register_post_type()
    {
        $labels = array(
            'name'                  => _x( 'Plans', 'Post Type General Name', 'hectv' ),
            'singular_name'         => _x( 'Plans', 'Post Type Singular Name', 'hectv' ),
            'menu_name'             => __( 'Plans', 'hectv' ),
            'name_admin_bar'        => __( 'Plans', 'hectv' ),
            'archives'              => __( 'Plans Archives', 'hectv' ),
            'attributes'            => __( 'Plans Attributes', 'hectv' ),
            'parent_item_colon'     => __( 'Parent Plans:', 'hectv' ),
            'all_items'             => __( 'All Plans', 'hectv' ),
            'add_new_item'          => __( 'Add New Plan', 'hectv' ),
            'add_new'               => __( 'Add New', 'hectv' ),
            'new_item'              => __( 'New Plans', 'hectv' ),
            'edit_item'             => __( 'Edit Plans', 'hectv' ),
            'update_item'           => __( 'Update Plans', 'hectv' ),
            'view_item'             => __( 'View Plans', 'hectv' ),
            'view_items'            => __( 'View Plans', 'hectv' ),
            'search_items'          => __( 'Search Plans', 'hectv' ),
            'not_found'             => __( 'Not found', 'hectv' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'hectv' ),
            'featured_image'        => __( 'Featured Image', 'hectv' ),
            'set_featured_image'    => __( 'Set featured image', 'hectv' ),
            'remove_featured_image' => __( 'Remove featured image', 'hectv' ),
            'use_featured_image'    => __( 'Use as featured image', 'hectv' ),
            'insert_into_item'      => __( 'Insert into plan', 'hectv' ),
            'uploaded_to_this_item' => __( 'Uploaded to this plan', 'hectv' ),
            'items_list'            => __( 'Video list', 'hectv' ),
            'items_list_navigation' => __( 'Video list navigation', 'hectv' ),
            'filter_items_list'     => __( 'Filter videos list', 'hectv' ),
        );
        $args = array(
            'label'                 => __( 'Plans', 'hectv' ),
            'description'           => __( 'Plans Post Type', 'hectv' ),
            'labels'                => $labels,
            'supports'              => array( 'title'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 7,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base'             => 'edplans',
        );
        register_post_type( $this->post_type, $args );
    }

    public function init()
    {
        add_filter( 'init', array( $this, 'register_post_type' ), 0 );
    }
}