<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Tools extends HECTV_Routes  implements HECTV_Custom_Post_Interface {
    public $post_type;
    public $param_list;

    function __construct($post_type){
        $this->post_type = $post_type;
        $this->param_list = [ "student_or_teacher" ];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }

    public function param_default( $args, $request ){
        return $args;
    }

    public function param_student_or_teacher( $args, $request ){  //Was type
        return $this->helper_param_compare_values('student_or_teacher', "=", $args, $request);
    }

    public function register_post_type()
    {
        $labels = array(
            'name'                  => _x( 'Tools', 'Post Type General Name', 'hectv' ),
            'singular_name'         => _x( 'Tool', 'Post Type Singular Name', 'hectv' ),
            'menu_name'             => __( 'Tools', 'hectv' ),
            'name_admin_bar'        => __( 'Tool', 'hectv' ),
            'archives'              => __( 'Tool Archives', 'hectv' ),
            'attributes'            => __( 'Tool Attributes', 'hectv' ),
            'parent_item_colon'     => __( 'Parent Tool:', 'hectv' ),
            'all_items'             => __( 'All Tools', 'hectv' ),
            'add_new_item'          => __( 'Add New Tool', 'hectv' ),
            'add_new'               => __( 'Add New', 'hectv' ),
            'new_item'              => __( 'New Tool', 'hectv' ),
            'edit_item'             => __( 'Edit Tool', 'hectv' ),
            'update_item'           => __( 'Update Tool', 'hectv' ),
            'view_item'             => __( 'View Tool', 'hectv' ),
            'view_items'            => __( 'View Tools', 'hectv' ),
            'search_items'          => __( 'Search Tools', 'hectv' ),
            'not_found'             => __( 'Not found', 'hectv' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'hectv' ),
            'featured_image'        => __( 'Featured Image', 'hectv' ),
            'set_featured_image'    => __( 'Set featured image', 'hectv' ),
            'remove_featured_image' => __( 'Remove featured image', 'hectv' ),
            'use_featured_image'    => __( 'Use as featured image', 'hectv' ),
            'insert_into_item'      => __( 'Insert into tool', 'hectv' ),
            'uploaded_to_this_item' => __( 'Uploaded to this tool', 'hectv' ),
            'items_list'            => __( 'Tool list', 'hectv' ),
            'items_list_navigation' => __( 'Tool list navigation', 'hectv' ),
            'filter_items_list'     => __( 'Filter tools list', 'hectv' ),
        );
        $args = array(
            'label'                 => __( 'Educational Tools', 'hectv' ),
            'description'           => __( 'Educational Tool Post Type', 'hectv' ),
            'labels'                => $labels,
            'supports'              => array( 'title'),
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
            'rest_base'             => "tools", //was plural
        );

        register_post_type( $this->post_type, $args );
    }

    public function init()
    {

        add_filter( 'init', array( $this, 'register_post_type' ), 0 );
    }
}