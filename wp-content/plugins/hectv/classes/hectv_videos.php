<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Videos extends HECTV_Routes  implements HECTV_Custom_Post_Interface {
    public $post_type;

    function __construct($post_type){
        $this->post_type = $post_type;
        $this->param_list = [];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
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

    public function register_post_type()
    {
        $labels = array(
            'name' => _x( 'Videos', 'post type general name' ), // Tip: _x('') is used for localization
            'singular_name' => _x( 'Video', 'post type singular name' ),
            'add_new' => _x( 'Add New', 'playlist' ),
            'add_new_item' => __( 'Add New Video' ),
            'edit_item' => __( 'Edit Video' ),
            'new_item' => __( 'New Video' ),
            'view_item' => __( 'View Video' ),
            'search_items' => __( 'Search Videos' ),
            'not_found' =>  __( 'No Videos found' ),
            'not_found_in_trash' => __( 'No Videos found in Trash' ),
            'parent_item_colon' => ''
        );

        $args = array( 'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => false,
            'menu_icon' => 'dashicons-playlist-video',
            'hierarchical' => true,
            'has_archive' => true,
            'capability' => array( 'edit_playlist' ),
            'supports' => array( 'title', 'editor', 'excerpt', 'revisions'),
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'exclude_from_search'   => false,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base' => $this->post_type,
        );

        register_post_type( $this->post_type, $args );
    }


    public function init()
    {
        add_filter( 'init', array( $this, 'register_post_type' ), 0 );
        add_filter( 'acf/prepare_field/name=duration', array( $this, 'format_duration') );
    }
}