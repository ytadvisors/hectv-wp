<?php

namespace HECTV\Classes;

use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Customer;
use Stripe\Charge;


class HECTV_Payment
{

    private $customer_id;
    private $stripe_token;
    private $payments_disabled = false;

    function __construct($stripe_token = "", $stripe_customer_id = ""){
        $this->payments_disabled = $this->get_env_var("HECTV_DISABLE_PAYMENTS") === "1";
        $api_key = $this->get_env_var("STRIPE_SECRET_KEY");
        if (!$this->payments_disabled && $api_key) {
            Stripe::setApiKey($api_key);
        }
        $this->stripe_token = $stripe_token;
        $this->customer_id = $stripe_customer_id;
    }

    private static function get_env_var($var)
    {
        return isset($_SERVER[$var]) ? $_SERVER[$var] : getenv($var);
    }

    public function get_plan_price($plan_id){
        $plans = get_posts(array(
           "post_type" => 'edplans',
            "post_status" => "publish"
        ));

        foreach($plans as $plan){
            if($plan_id === get_field("plan_id", $plan->ID))
                return $plan->plan_price;
        }

        return -1;
    }

    public function get_customer_id(){
        return $this->customer_id;
    }

    public function create_payment($item, $price, $description = "", $meta_data = array()){
        if ($this->payments_disabled) {
            return new \WP_Error('payment_disabled', 'Payments are disabled in validation mode', array('status' => 503));
        }

        if($description == "")
            $description = $item;

        return Charge::create(array(
            "amount" => $price,
            "currency" => "usd",
            "description" => $item,
            "statement_descriptor" => $description,
            "metadata" => $meta_data,
            "source" => $this->stripe_token,
        ));
    }

    public function change_payment_plan($plan_id){
        if ($this->payments_disabled) {
            return new \WP_Error('payment_disabled', 'Payments are disabled in validation mode', array('status' => 503));
        }

        $plan_price = $this->get_plan_price($plan_id);
        if($plan_price >= 0) {
            //change subscription
            $customer = Customer::retrieve($this->customer_id);
            $subscription = $customer->subscriptions->data[0];
            $stripe_plan_id = $subscription->id;
            $item_id = $subscription->items->data[0]->id;
            Subscription::update(
                $stripe_plan_id,
                array(
                    "items" => array(
                        array(
                            "id" => $item_id,
                            "plan" => $plan_id
                        )
                    )
                )
            );
        } else {
            return new \WP_Error( 'payment_error', 'Invalid plan', array( 'status' => 404 ) );
        }

        return true;
    }

    public function start_payment_plan($email, $plan_id){
        if ($this->payments_disabled) {
            return new \WP_Error('payment_disabled', 'Payments are disabled in validation mode', array('status' => 503));
        }

        $plan_price = $this->get_plan_price($plan_id);
        if($plan_price > 0){
            try{

                if($this->customer_id == ""){
                    $customer = Customer::create(array(
                        "email" => $email
                    ));

                    $this->customer_id = $customer->id;
                }

                Subscription::create(
                    array(
                        "customer" => $this->customer_id,
                        "source" => $this->stripe_token,
                        "items" => array(
                            array(
                                "plan" => $plan_id
                            )
                        )
                    )
                );

            } catch (\Exception $error){
                return new \WP_Error( 'payment_error', $error->getMessage(), array( 'status' => 404 ) );
            }

        } else if($plan_price === 0){
            return new \WP_Error( 'payment_error', 'No payment plan available', array( 'status' => 404 ) );
        } else{
            return new \WP_Error( 'payment_error', 'Invalid plan', array( 'status' => 404 ) );
        }

        return true;
    }

}
