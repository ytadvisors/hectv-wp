<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Testimonials extends HECTV_Routes  implements HECTV_Custom_Post_Interface {
    public $post_type;

    function __construct($post_type){
        $this->post_type = $post_type;
        $this->init();
    }


    public function param_default( $args, $request ){
        return $args;
    }

    public function register_post_type()
    {
        $labels = array(
            'name'                  => _x( 'Testimonial', 'Post Type General Name', 'hectv' ),
            'singular_name'         => _x( 'Testimonial', 'Post Type Singular Name', 'hectv' ),
            'menu_name'             => __( 'Testimonials', 'hectv' ),
            'name_admin_bar'        => __( 'Testimonial', 'hectv' ),
            'archives'              => __( 'Testimonial Archives', 'hectv' ),
            'attributes'            => __( 'Testimonial Attributes', 'hectv' ),
            'parent_item_colon'     => __( 'Parent Testimonial:', 'hectv' ),
            'all_items'             => __( 'All Testimonials', 'hectv' ),
            'add_new_item'          => __( 'Add New Testimonial', 'hectv' ),
            'add_new'               => __( 'Add New', 'hectv' ),
            'new_item'              => __( 'New Testimonial', 'hectv' ),
            'edit_item'             => __( 'Edit Testimonial', 'hectv' ),
            'update_item'           => __( 'Update Testimonial', 'hectv' ),
            'view_item'             => __( 'View Testimonial', 'hectv' ),
            'view_items'            => __( 'View Testimonials', 'hectv' ),
            'search_items'          => __( 'Search Testimonials', 'hectv' ),
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
            'label'                 => __( 'Testimonials', 'hectv' ),
            'description'           => __( 'Testimonial Post Type', 'hectv' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'excerpt', 'author' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 6,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base'             => 'testimonial',
        );
        register_post_type( $this->post_type, $args );
    }

    public function init()
    {
        add_filter( 'init', array( $this, 'register_post_type' ), 0 );
    }
}