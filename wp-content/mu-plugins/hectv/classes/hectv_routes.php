<?php


namespace HECTV\Classes;

/**
 * Class HECTV_Videos
 * @package HECTV\Classes\Posts
 *
 */

class HECTV_Routes  extends \WP_REST_Controller {


    public $param_list;

    public function helper_param_compare_values($parameter_name, $comparison, $args, $request){
        $parameter_value   = $request->get_param( $parameter_name );

        $meta_query = array();
        if ( ! empty( $parameter_value ) ) {
            $meta_query = array(
                array(
                    'key'     => $parameter_name,
                    'value'   => $parameter_value,
                    'compare' => $comparison,
                )
            );
        }

        return $this->getNewMetaQuery($args, $meta_query);
    }

    public function helper_param_compare_array($parameter_name, $comparison, $args, $request){
        $parameter_array_values  = $request->get_param( $parameter_name );

        $meta_query = array();
        if ( ! empty( $parameter_array_values) ) {

            $query = array_map(function ($parameter_value) use ($parameter_name, $comparison){
                return array(
                    'key'     => $parameter_name,
                    'value'   => $parameter_value,
                    'compare' => $comparison,
                );
            }, $parameter_array_values);

            $meta_query = array(
                $query
            );
        }

        return $this->getNewMetaQuery($args, $meta_query);
    }

    public function getNewMetaQuery($args, $meta_query){
        if(count($meta_query) > 0){
            if(isset($args["meta_query"]))
                $args["meta_query"][] = $meta_query;
            else{
                $args["meta_query"] = array("relation" => "AND");
                $args["meta_query"][] = $meta_query;
            }

        }

        return $args;
    }

    public function getNewTaxQuery($args, $tax_query){
        if(count($tax_query) > 0){
            if(isset($args["tax_query"]))
                $args["tax_query"][] = $tax_query;
            else{
                $args["tax_query"] = array("relation" => "AND");
                $args["tax_query"][] = $tax_query;

            }
        }
        return $args;
    }

    public function setup_params($post_type, $param_list = []){
        $this->param_list = $param_list;

        for ($x = 0; $x < count($param_list);  $x++) {
            $function_name = "param_{$param_list[$x]}";
            add_filter("rest_{$post_type}_query", array($this, $function_name), 10, 2);
        }
    }

}