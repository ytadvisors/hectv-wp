<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Schedules extends HECTV_Routes  implements HECTV_Custom_Post_Interface {
    public $post_type;
    public $param_list;

    private static $CDN = "http://cdn.hectv.org";
    private static $ACF_SECTION_ID = "schedule_programs";
    private static $ACF_SECTION_ID_FIELD = "field_5afc4ffb8a1bf";
    private static $ACF_FIELDS_MAP = [
        "Station ID" => "field_5afc503e8a1c0",
        "Program Start Date" => "field_5afc504f8a1c1",
        "Program Start Time" => "field_5afc50d28a1c2",
        "Program End Time" => "field_5afc514c8a1c3",
        "Duration" => "field_5afc51748a1c4",
        "Program Title" => "field_5afc51838a1c5",
        "Episode Number" => "field_5afc519e8a1c6",
        "Episode Title" => "field_5afc51b78a1c7"
    ];

    private $db;

    function __construct($post_type){

        global $wpdb;
        $this->db = $wpdb;

        $this->post_type = $post_type;
        $this->param_list = [
            "day"
        ];

        $this->setup_params($post_type, $this->param_list);
        $this->init();

    }

    protected function add_meta_value($post_id, $key, $value)
    {
        if (!add_post_meta($post_id, $key, $value, true)) {
            update_post_meta($post_id, $key, $value);
        }
    }

    protected function getScheduleMap($filename){
        $filename = preg_replace("/(.+\/wp\-content)(.+)/i", HECTV_Schedules::$CDN . "/wp-content$2", $filename);
        $content = file($filename);
        $schedules = [];
        $headers = [];
        $map = [];

        foreach($content as $entry){
            $schedules = array_merge( $schedules, explode("\r", $entry));
        }

        for ($i = 0; $i < count($schedules); $i ++) {
            $value_list = explode("\t", $schedules[$i]);
            for ($x = 0; $x < count($value_list); $x++) {
                $value = $value_list[$x];
                if ($i == 0){
                    array_push($headers, $value);
                } else {
                    $current_header = $headers[$x];
                    $index = $i - 1;
                    if(!isset($map[$index]))
                        $map[$index] = [];

                    $map[$index][$current_header] = preg_replace('/"/', "", $value);
                }
            }
        }

        return $map;
    }


    public function param_day($args, $request)
    {
        $parameter_name = "day";
        $parameter_value   = $request->get_param( $parameter_name );
        if ( ! empty( $parameter_value )  ) {
            $date = strtotime($parameter_value);
            $month = date("Ym", $date);
            if($month != "") {
                $meta_query = array(
                    array(
                        'key' => "month",
                        'value' => $month,
                        'compare' => "like",
                    )
                );
                return $this->getNewMetaQuery($args, $meta_query);
            }
        }

        return $args;
    }

    public function addSchedule($file_id, $post_id)
    {
        $post = get_post($file_id);
        if($post != null){
            $filename = $post->guid;

            $map = $this->getScheduleMap($filename);
            $num_entries = count($map);

            $field_name = str_replace(" ", "_", strtolower(HECTV_Schedules::$ACF_SECTION_ID)) ;


            ini_set("memory_limit",-1);
            set_time_limit(0);
            ignore_user_abort(true);

            wp_defer_term_counting( true );
            wp_defer_comment_counting( true );
            $this->db->query( 'SET autocommit = 0;' );

            $this->add_meta_value($post_id, $field_name, $num_entries);
            $this->add_meta_value($post_id, "_".$field_name, HECTV_Schedules::$ACF_SECTION_ID_FIELD);

            for($x = 0; $x < $num_entries; $x ++){
                foreach($map[$x] as $key => $value) {
                    $key_name = HECTV_Schedules::$ACF_SECTION_ID . "_" . $x . "_" . $key;
                    $field_name = str_replace(" ", "_", strtolower($key_name)) ;
                    $this->add_meta_value($post_id, $field_name, $value);
                    $this->add_meta_value($post_id, "_".$field_name, HECTV_Schedules::$ACF_FIELDS_MAP[$key]);
                }
            }

            $this->db->query( 'COMMIT;' );
            wp_defer_term_counting( false );
            wp_defer_comment_counting( false );
        }
    }

    /**
     * Callback function registers the event post type
     */
    public function register_post_type()
    {
        $labels = array(
            'name' => _x( 'Schedules', 'post type general name' ), // Tip: _x('') is used for localization
            'singular_name' => _x( 'Schedule', 'post type singular name' ),
            'add_new' => _x( 'Add New', 'video' ),
            'add_new_item' => __( 'Add New Monthly Schedule' ),
            'edit_item' => __( 'Edit Monthly Schedule' ),
            'new_item' => __( 'New Monthly Schedule' ),
            'view_item' => __( 'View Schedule' ),
            'search_items' => __( 'Search Schedules' ),
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
            'menu_icon' => 'dashicons-schedule',
            'hierarchical' => false,
            'supports' => array( 'title'),
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