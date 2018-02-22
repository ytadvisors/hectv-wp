<?php

namespace HECTV\Classes;

use WP_REST_Request;
use WP_REST_Server;


class HECTV_Users extends HECTV_Routes
{
    public $post_type;

    function __construct($post_type)
    {
        $this->post_type = $post_type;
        $this->param_list = [];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }

    private function update_user_name($user_id, $name)
    {
        $user_id = wp_update_user(
            (object)array(
                "ID" => $user_id,
                "display_name" => $name
            )
        );

        if (is_wp_error($user_id)) {
            return new \WP_Error('no_user', 'Invalid user', array('status' => 404));
        } else {
            return $user_id;
        }
    }

    private function add_meta_data($user_id, $params)
    {
        foreach ($params as $key => $value) {
            add_user_meta($user_id, $key, $value);
        }
        return $params;
    }

    private function update_meta_data($user_id, $params)
    {
        foreach ($params as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }
        return $params;
    }

    private function create_user_account($email, $name, $password)
    {
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            return new \WP_Error('existing_user', 'Invalid user', array('status' => 404));
        } else {
            $this->update_user_name($user_id, $name);
            $user = new \WP_User($user_id);
            $user->set_role('contributor');
        }

        return $user_id;
    }

    private function change_plan($user_id, $plan_id, $stripe_token = null)
    {
        $previous_plan_id = get_user_meta($user_id, "plan_id")[0];
        if($previous_plan_id !== $plan_id){
            $stripe_meta = get_user_meta($user_id, "stripe_customer_id");
            if($stripe_meta === null || count($stripe_meta) <= 0){
                if($stripe_token !== null){
                    $user_info = get_userdata($user_id);
                    $email = $user_info->user_email;
                    $payment = new HECTV_Payment($stripe_token);
                    $payment->start_payment_plan($email, $plan_id);
                    $params = array(
                        "stripe_customer_id" => $payment->get_customer_id(),
                        "plan_id" => $plan_id
                    );
                    $this->update_meta_data($user_id, $params);
                }
                else
                    return new \WP_Error('payment_error', 'No stripe token', array('status' => 404));
            } else {
                $stripe_customer_id = $stripe_meta[0];
                $payment = new HECTV_Payment("", $stripe_customer_id);
                $payment->change_payment_plan($plan_id);
                $params = array( "plan_id" => $plan_id );
                $this->update_meta_data($user_id, $params);
            }
        }
    }

    public function create_user(WP_REST_Request $request)
    {
        $params = $request->get_params();
        $user_id = $this->create_user_account($params["email"], $params["name"], $params["password"]);

        if (is_int($user_id)) {
            if (isset($params["stripe_token"]) && $params["plan_id"] !== "basic_monthly") {
                $payment = new HECTV_Payment($params["stripe_token"]);
                $payment->start_payment_plan($params["email"], $params["plan_id"]);
                $params["stripe_customer_id"] = $payment->get_customer_id();
            }
            $this->add_meta_data($user_id, $params);
        }

        return is_int($user_id) ? array("id" => $user_id) : $user_id;
    }

    public function update_user(\WP_REST_Request $request)
    {
        $read_only = array("plan_id", "email", "stripe_customer_id", "parent_user");
        $params = $request->get_params();

        $id = $params["id"];
        if ($id === "me") {
            $id = get_current_user_id();
        } else {
            return new \WP_Error('update_error', 'Can only update your profile. The id parameter is set to ' . $id, array('status' => 404));
        }
        $updated = $id;

        if (isset($params["plan_id"]) && $params["plan_id"] !== "") //change plan
        {
            $token = isset($params["stripe_token"]) ? $params["stripe_token"] : null;
            $this->change_plan($id, $params["plan_id"], $token);
        }

        foreach ($read_only as $key) {
            unset($params[$key]);
        }

        if (isset($params["name"])) {
            $updated = $this->update_user_name($id, $params["name"]);
        }
        if ($updated === $id) {
            $updated = $this->update_meta_data($id, $params);
        }

        return $updated;
    }


    public function update_item_permissions_check($request)
    {
        return is_user_logged_in();
    }

    public function register_routes()
    {
        $namespace = 'hectv/v1';
        register_rest_route(
            $namespace,
            '/users',
            array(
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'create_user')
                )
            )
        );
        register_rest_route(
            $namespace,
            '/users/(?P<id>.+)',
            array(
                array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'update_user'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                    'args' => array(
                        'id' => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return is_numeric($param) || $param == "me";
                            }
                        ),
                    )
                )
            )
        );
    }

    public function init()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }


}