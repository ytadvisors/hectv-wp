<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;

class HECTV_Schedule extends HECTV_Routes  implements HECTV_Custom_Post_Interface {
    public $post_type;

    function __construct($post_type){
        $this->post_type = $post_type;
        $this->param_list = [
            'program_id',
            'program_name',
            'program_price',
            'order_by',
            'order'
        ];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }

    private function getAdjustedPrice($plan, $price){

        //TODO: Automate price based on api for plan pricing.
        if(preg_match("/[0-9]+/", "$price", $price_value)){
            $value = $price_value[0];
            switch ($plan) {
                case 'bronze_monthly':
                case 'silver_monthly':
                case 'gold_monthly':
                    return $value - $value * 0.25;
                case 'platinum_monthly':
                    return $value - $value * 0.3;
                default:
                    return $value;
            }
        } else{
            return new \WP_Error('price_error', 'Cannot interpret the price', array('status' => 404));
        }
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


    public function param_program_id($args, $request)
    {
        return $this->helper_param_compare_values('program_id', "=", $args, $request);
    }


    public function param_program_name($args, $request)
    {
        return $this->helper_param_compare_values('program_name', "=", $args, $request);
    }


    public function param_program_price($args, $request)
    {
        return $this->helper_param_compare_values('program_price', "=", $args, $request);
    }


    public function update_stripe_token($stripe_token, $schedule_object){
        $video = get_field("video_id",$schedule_object->ID);
        $teacher = get_field("teacher_name",$schedule_object->ID);
        $price = get_field("price", $video->ID);
        $title = get_the_title($video->ID);
        $user_id = get_current_user_id();
        $plan = get_user_meta($user_id, "plan_id");
        $plan_id = $plan[0];
        $payment = new HECTV_Payment($stripe_token);
        $payment->create_payment(
            "Scheduled Program for video: $video->ID",
            $this->getAdjustedPrice($plan_id, $price) * 100,
            "Video: $video->ID",
            array(
                "title" => html_entity_decode($title),
                "teacher" => $teacher,
                "video_id" => $video->ID,
                "user_id" => $user_id,
                "slug" => "schedeule-$video->ID-$user_id"
            )
        );

        return true;
    }

    public function register_rest_field(){
        register_rest_field('edschedule',
            'stripe_token',
            array(
                'get_callback' => null,
                'update_callback' => array($this, 'update_stripe_token'),
                'schema' => null
            ));

    }



    public function register_post_type()
    {
        $labels = array(
            'name'                  => _x( 'Schedule', 'Post Type General Name', 'hectv' ),
            'singular_name'         => _x( 'Schedule', 'Post Type Singular Name', 'hectv' ),
            'menu_name'             => __( 'Schedule', 'hectv' ),
            'name_admin_bar'        => __( 'Schedule', 'hectv' ),
            'archives'              => __( 'Schedule Archives', 'hectv' ),
            'attributes'            => __( 'Schedule Attributes', 'hectv' ),
            'parent_item_colon'     => __( 'Parent Schedule:', 'hectv' ),
            'all_items'             => __( 'All Schedule', 'hectv' ),
            'add_new_item'          => __( 'Add New Program', 'hectv' ),
            'add_new'               => __( 'Add New', 'hectv' ),
            'new_item'              => __( 'New Schedule', 'hectv' ),
            'edit_item'             => __( 'Edit Schedule', 'hectv' ),
            'update_item'           => __( 'Update Schedule', 'hectv' ),
            'view_item'             => __( 'View Schedule', 'hectv' ),
            'view_items'            => __( 'View Schedule', 'hectv' ),
            'search_items'          => __( 'Search Schedule', 'hectv' ),
            'not_found'             => __( 'Not found', 'hectv' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'hectv' ),
            'featured_image'        => __( 'Featured Image', 'hectv' ),
            'set_featured_image'    => __( 'Set featured image', 'hectv' ),
            'remove_featured_image' => __( 'Remove featured image', 'hectv' ),
            'use_featured_image'    => __( 'Use as featured image', 'hectv' ),
            'insert_into_item'      => __( 'Insert into edschedule', 'hectv' ),
            'uploaded_to_this_item' => __( 'Uploaded to this plan', 'hectv' ),
            'items_list'            => __( 'Video list', 'hectv' ),
            'items_list_navigation' => __( 'Video list navigation', 'hectv' ),
            'filter_items_list'     => __( 'Filter videos list', 'hectv' ),
        );
        $args = array(
            'label'                 => __( 'Schedules', 'hectv' ),
            'description'           => __( 'Schedule Post Type', 'hectv' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'author'),
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
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'edschedule'
        );
        register_post_type( $this->post_type, $args );
    }

    public function init()
    {
        add_filter( 'init', array( $this, 'register_post_type' ), 0 );
        add_action( 'rest_api_init', array($this, 'register_rest_field'));
    }
}