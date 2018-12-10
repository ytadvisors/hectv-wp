<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Live_Videos extends HECTV_Routes  implements HECTV_Custom_Post_Interface {
    public $post_type;
    public $param_list;
    private $db;

    function __construct($post_type){

        global $wpdb;
        $this->db = $wpdb;

        $this->post_type = $post_type;
        $this->param_list = [];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }

    function get_live_video(){
        $date_now = date("Y-m-d H:i:s");
        $posts = get_posts(
            array(
                'post_type'         => $this->post_type,
                'posts_per_page'    => -1,
                'meta_query'        => array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'display_date',
                        'value'   => $date_now,
                        'compare' => '<=',
                        "type" => "DATETIME"
                    ),
                    array(
                        'key'     => 'end_date',
                        'value' => $date_now,
                        'compare' => '>=',
                        "type" => "DATETIME"
                    )
                ),
                'orderby'           => 'meta_value_num',
                'order'             => 'ASC')
        );

        for($x = 0; $x < count($posts); $x++) {
            $posts[$x]->acf = get_fields($posts[$x]->ID);
        }
        return $posts;
    }

    function add_custom_live_api(){
        register_rest_route( "hectv/v1", "$this->post_type/live", array(
            'methods' => 'GET',
            'callback' => array($this, 'get_live_video'),
        ));
    }
    /**
     * Callback function registers the event post type
     */
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
            'not_found' =>  __( 'No videos found' ),
            'not_found_in_trash' => __( 'No events found in Trash' ),
            'parent_item_colon' => ''
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'query_var' => true,
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-video-alt3',
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
        add_action( 'rest_api_init', array( $this, 'add_custom_live_api'), 0);
    }
}
