<?php

namespace HECTV\Classes;

use \ICanBoogie\Inflector;
use HECTV\Lib\HECTV_Custom_Taxonomy_Interface;

class HECTV_Taxonomy extends HECTV_Routes implements HECTV_Custom_Taxonomy_Interface {
    public $post_type;
    private $parent_post;
    private $category_name;
    private $hierarchical;

    function __construct($post_type, $parent_post, $category_name, $hierarchical = false){
        $this->category_name = $category_name;
        $this->post_type = $post_type;
        $this->parent_post = $parent_post;
        $this->hierarchical = $hierarchical;
        $this->param_list = [
            'order_by'
        ];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }

    public function param_order_by( $args, $request ){
        $orderby = $request->get_param( "order_by" );
        $tax = array();
        if ( ! empty( $orderby ) ) {
            $tax["orderby"] = $orderby;
            $tax["order"] = "asc";
        }
        return $this->getNewTaxQuery($args, $tax);
    }

    public function register_taxonomy()
    {
        $inflector = Inflector::get('en');
        $category_name = $this->category_name;
        $plural_category = $inflector->pluralize($this->category_name);



        $labels = array(
            'name'                       => _x( $category_name, 'Taxonomy General Name', 'hectv' ),
            'singular_name'              => _x( $category_name, 'Taxonomy Singular Name', 'hectv' ),
            'menu_name'                  => __( $inflector->pluralize($this->category_name), 'hectv' ),
            'all_items'                  => __( "All {$plural_category}", 'hectv' ),
            'parent_item'                => __( "Parent {$category_name}", 'hectv' ),
            'parent_item_colon'          => __( "Parent {$category_name}:", 'hectv' ),
            'new_item_name'              => __( "New {$category_name}", 'hectv' ),
            'add_new_item'               => __( "Add New {$category_name}", 'hectv' ),
            'edit_item'                  => __( "Edit {$category_name}", 'hectv' ),
            'update_item'                => __( "Update {$category_name}", 'hectv' ),
            'view_item'                  => __( 'View Item', 'hectv' ),
            'separate_items_with_commas' => __( "Separate {$plural_category} with commas", 'hectv' ),
            'add_or_remove_items'        => __( "Add or remove {$plural_category}", 'hectv' ),
            'choose_from_most_used'      => __( "Choose from the most used {$plural_category}", 'hectv' ),
            'popular_items'              => __( 'Popular Items', 'hectv' ),
            'search_items'               => __( "Search {$plural_category}", 'hectv' ),
            'not_found'                  => __( 'Not Found', 'hectv' ),
            'no_terms'                   => __( 'No items', 'hectv' ),
            'items_list'                 => __( 'Items list', 'hectv' ),
            'items_list_navigation'      => __( 'Items list navigation', 'hectv' ),
        );
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => $this->hierarchical,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rest_base'                  => $this->post_type,
            'rest_controller_class'      => 'WP_REST_Terms_Controller',
        );

        $parent_post = is_array($this->parent_post) ? $this->parent_post : array($this->parent_post);
        register_taxonomy( $this->post_type, $parent_post, $args );
    }

    public function init()
    {
        add_filter( 'init', array( $this, 'register_taxonomy'), 0 );
    }
}