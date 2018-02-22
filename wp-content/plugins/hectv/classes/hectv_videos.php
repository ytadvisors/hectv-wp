<?php

namespace HECTV\Classes;

use HECTV\Lib\HECTV_Custom_Post_Interface;
use Requests;
use WP_Query;
use \Guzzle\Http\Mimetypes;

/**
 * Class HECTV_Videos
 * @package HECTV\Classes\Posts
 *
 */
class HECTV_Videos extends HECTV_Routes implements HECTV_Custom_Post_Interface
{

    public $post_type;
    public $param_list;

    function __construct($post_type)
    {
        $this->post_type = $post_type;
        $this->param_list = [
            'curriculum_area_1',
            'curriculum_area_2',
            'curriculum_area_3',
            'curriculum_search',
            'free_interactive',
            'paid_interactive',
            'member_created_video',
            'home_school_video',
            'tutorial_video',
            'edgate_id',
            'k_to_4',
            '5_to_8',
            '9_to_12',
            'hec_kw',
            'uid',
            'meta_query',
            'featured'
        ];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }

    private function get_meta_post_id($key, $value)
    {
        $query = new WP_Query(array(
            "post_type" => $this->post_type,
            "meta_key" => $key,
            "meta_value" => $value
        ));

        $post_id = $query->posts[0]->ID;
        wp_reset_query();

        return $post_id;
    }

    public function get_uid_meta_values() {

        global $wpdb;
        $status = 'publish';

        $r = $wpdb->get_col( $wpdb->prepare( "
        SELECT pm.meta_value 
        FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'uid'
        AND p.post_status = '%s'",
            $status
        ));

        return $r;
    }

    private function create_thumbnail_post($parent_post_id, $video_thumbnail, $title)
    {
        $post_name = $video_thumbnail;
        $query = new WP_Query(array(
            "post_type" => "attachment",
            "post_status" => "inherit",
            "name" => $post_name,
            "post_parent" => $parent_post_id
        ));
        wp_reset_query();
        if ($query->have_posts()) {
            $thumbnail_post_id = $query->posts[0]->ID;
        } else {
            $thumbnail_post_id = wp_insert_post(
                array(
                    "post_title" => $title,
                    "post_status" => "inherit",
                    "comment_status" => "open",
                    "ping_status" => "closed",
                    "post_name" => $post_name,
                    "post_parent" => $parent_post_id,
                    "guid" => $video_thumbnail,
                    "post_type" => "attachment",
                    "post_mime_type" => Mimetypes::getInstance()->fromExtension($video_thumbnail)
                )
            );
        }

        return $thumbnail_post_id;
    }

    private function add_meta_value($post_id, $key, $value)
    {
        if (!add_post_meta($post_id, $key, $value, true)) {
            update_post_meta($post_id, $key, $value);
        }
    }

    public function param_curriculum_area_1($args, $request)
    {
        return $this->helper_param_compare_values('curriculum_area_1', "=", $args, $request);
    }

    public function param_curriculum_area_2($args, $request)
    {
        return $this->helper_param_compare_values('curriculum_area_2', "=", $args, $request);
    }

    public function param_curriculum_area_3($args, $request)
    {
        return $this->helper_param_compare_values('curriculum_area_3', "=", $args, $request);
    }

    public function param_curriculum_search($args, $request)
    {
        $curriculum_search = $request->get_param('curriculum_search');

        $meta_query = array();
        if (!empty($curriculum_search)) {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => 'curriculum_area_1',
                    'value' => $curriculum_search,
                    'compare' => 'IN',
                ),
                array(
                    'key' => 'curriculum_area_2',
                    'value' => $curriculum_search,
                    'compare' => 'IN',
                ),
                array(
                    'key' => 'curriculum_area_3',
                    'value' => $curriculum_search,
                    'compare' => 'IN',
                )
            );
        }

        return $this->getNewMetaQuery($args, $meta_query);
    }

    public function param_edgate_id($args, $request)
    {
        return $this->helper_param_compare_values('edgate_id', "IN", $args, $request);
    }

    public function param_free_interactive($args, $request)
    {
        return $this->helper_param_compare_values('free_interactive', "==", $args, $request);
    }

    public function param_paid_interactive($args, $request)
    {
        return $this->helper_param_compare_values('paid_interactive', "==", $args, $request);
    }

    public function param_member_created_video($args, $request)
    {
        return $this->helper_param_compare_values('member_created_video', "==", $args, $request);
    }

    public function param_home_school_video($args, $request)
    {
        return $this->helper_param_compare_values('home_school_video', "==", $args, $request);
    }

    public function param_tutorial_video($args, $request)
    {
        return $this->helper_param_compare_values('tutorial_video', "==", $args, $request);
    }

    public function param_k_to_4($args, $request)
    {
        return $this->helper_param_compare_values('k_to_4', "==", $args, $request);

    }

    public function param_5_to_8($args, $request)
    {
        return $this->helper_param_compare_values('5_to_8', "==", $args, $request);
    }

    public function param_9_to_12($args, $request)
    {
        return $this->helper_param_compare_values('9_to_12', "==", $args, $request);
    }

    public function param_hec_kw($args, $request)
    {

        $keywords = $request->get_param('hec_kw');
        $tax_query = array();
        if (!empty($keywords)) {
            $query = array_map(function($item){
                return array(
                    'taxonomy' => 'hec_kw',
                    'field'    => 'term_id',
                    'terms'    => $item,
                );
            }, $keywords);

            $tax_query = array(
                $query
            );
        }

        return $this->getNewTaxQuery($args, $tax_query);
    }

    public function param_uid($args, $request)
    {
        return $this->helper_param_compare_array('uid', "EXISTS", $args, $request);
    }

    public function param_meta_query($args, $request)
    {
        return $args;
    }

    public function param_featured($args, $request)
    {
        return $this->helper_param_compare_values('featured', "=", $args, $request);
    }


    public function add_vimeo_thumbNail($vimeo_url, $post_id = "", $redirect = false)
    {
        if ($vimeo_url != "") {
            if ($post_id == "") {
                $post_id = $this->get_meta_post_id("video_url", $vimeo_url);
            }

            $headers = array('Accept' => 'application/json');
            $request = Requests::get("https://vimeo.com/api/oembed.json?url={$vimeo_url}", $headers);
            $data = json_decode($request->body, true);
            if(isset($data["thumbnail_url"])){
                $thumbnail_url = $data["thumbnail_url"];
                $title = $data["title"];

                if ($thumbnail_url != "") {
                    $thumbnail_post_id = $this->create_thumbnail_post($post_id, $thumbnail_url, $title);
                    if ($thumbnail_post_id != "") {
                        $this->add_meta_value($post_id, "video_thumbnail", $thumbnail_post_id);
                        $this->add_meta_value($post_id, "_video_thumbnail", "field_5976a8c6c48b2");
                    }
                }
            }
        }
    }

    public function update_hook($value, $post_id, $field)
    {
        switch($field["name"]){
            case "video_thumbnail":
                $video_url = get_field("video_url", $post_id);
                if($video_url != ""){
                    $this->add_vimeo_thumbNail($video_url, $post_id, true);
                }
                break;
        }

        return true;
    }

    public function get_standards(){

    }

    public function register_routes()
    {
        $namespace = 'hectv/v1';
        register_rest_route( $namespace,
            '/uniqueid', array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_uid_meta_values' ),
            )
        );

        register_rest_field('edschedule/standards',
            'stripe_token',
            array(
                'get_callback' => array($this, 'get_standards'),
                'update_callback' => null,
                'schema' => null
            ));
    }


    public function register_post_type()
    {
        $labels = array(
            'name' => _x('Educational Videos', 'Post Type General Name', 'hectv'),
            'singular_name' => _x('Educational Video', 'Post Type Singular Name', 'hectv'),
            'menu_name' => __('Videos', 'hectv'),
            'name_admin_bar' => __('Video', 'hectv'),
            'archives' => __('Video Archives', 'hectv'),
            'attributes' => __('Video Attributes', 'hectv'),
            'parent_item_colon' => __('Parent Video:', 'hectv'),
            'all_items' => __('All Videos', 'hectv'),
            'add_new_item' => __('Add New Video', 'hectv'),
            'add_new' => __('Add New', 'hectv'),
            'new_item' => __('New Video', 'hectv'),
            'edit_item' => __('Edit Video', 'hectv'),
            'update_item' => __('Update Video', 'hectv'),
            'view_item' => __('View Video', 'hectv'),
            'view_items' => __('View Videos', 'hectv'),
            'search_items' => __('Search Videos', 'hectv'),
            'not_found' => __('Not found', 'hectv'),
            'not_found_in_trash' => __('Not found in Trash', 'hectv'),
            'featured_image' => __('Featured Image', 'hectv'),
            'set_featured_image' => __('Set featured image', 'hectv'),
            'remove_featured_image' => __('Remove featured image', 'hectv'),
            'use_featured_image' => __('Use as featured image', 'hectv'),
            'insert_into_item' => __("INSERT INTO video", 'hectv'),
            'uploaded_to_this_item' => __('Uploaded to this video', 'hectv'),
            'items_list' => __('Video list', 'hectv'),
            'items_list_navigation' => __('Video list navigation', 'hectv'),
            'filter_items_list' => __('Filter videos list', 'hectv'),
        );
        $args = array(
            'label' => __('Educational Video', 'hectv'),
            'description' => __('Educational Video Post Type', 'hectv'),
            'labels' => $labels,
            'supports' => array('title'),
            'taxonomies' => array('hec_kw', 'vid_cat'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 4,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'page',
            'show_in_rest' => true,
            'rest_base' => $this->post_type,
        );

        register_post_type($this->post_type, $args);
    }

    public function init()
    {
        add_filter('init', array($this, 'register_post_type'), 0);
        add_action("acf/update_value", array($this, 'update_hook'), 10, 3);
        add_action( 'rest_api_init', array($this, 'register_routes') );
    }
}