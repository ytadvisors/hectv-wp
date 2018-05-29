<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Events extends HECTV_Routes  implements HECTV_Custom_Post_Interface {

    private $db;

    public $post_type;
    public $param_list;

    function __construct($post_type){

        global $wpdb;
        $this->db = $wpdb;

        $this->post_type = $post_type;
        $this->param_list = [ "day" ];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }


    public function param_day($args, $request)
    {
        $parameter_name = "day";
        $parameter_value   = $request->get_param( $parameter_name );
        if ( ! empty( $parameter_value )  ) {
            $date = strtotime($parameter_value);
            $day = date("Y-m-d ", $date);
            $matches = [];
            if($day != "") {

                //Get the posts where the start_time is greater
                $r = $this->db->get_results( $this->db->prepare( "
                    SELECT pm.post_id, pm.meta_key 
                    FROM {$this->db->postmeta} pm
                    WHERE pm.meta_key LIKE 'event_dates_%%_end_time'
                    AND STR_TO_DATE(meta_value, '%%Y-%%m-%%d') > '%s' ", $day
                ));

                //Make sure the start_time is less than the end time
                $query = "SELECT pm.post_id FROM {$this->db->postmeta} pm
                          WHERE STR_TO_DATE(meta_value, '%%Y-%%m-%%d') < '%s' ";
                foreach($r as $entry){
                    $end_time = str_replace("end_time", "start_time", $entry->meta_key);
                    array_push($matches, "pm.meta_key = '$end_time' AND pm.post_id = $entry->post_id");
                }
                $query .= "AND ((" . implode(") OR (", $matches) . "))";

                $values = $this->db->get_col( $this->db->prepare($query, $day));
                $args["post__in"] = $values;
            }
        }
        return $args;
    }

    public function my_posts_where( $where ) {
        $where = str_replace(
            "meta_key = 'event_dates_%",
            "meta_key LIKE 'event_dates_%",
            $this->db->remove_placeholder_escape($where)
        );

        return $where;
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
            'supports' => array( 'title', 'editor', 'thumbnail' ),
            'show_in_rest' => true,
            'menu_position' => 4,
            'rest_base' => $this->post_type,
        );

        register_post_type( $this->post_type, $args );
    }


    public function init()
    {
        add_filter( "init", array( $this, 'register_post_type' ), 0 );
        add_filter("posts_where", array( $this, "my_posts_where"));
    }
}