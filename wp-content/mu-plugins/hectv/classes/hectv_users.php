<?php

namespace HECTV\Classes;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

use \Firebase\JWT\JWT;

class HECTV_Users extends HECTV_Routes
{
    private $db;
    public $post_type;

    function __construct($post_type)
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->post_type = $post_type;
        $this->param_list = [];

        $this->setup_params($post_type, $this->param_list);
        $this->init();
    }

    public function init()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_filter('jwt_auth_token_before_dispatch', array($this, 'auth_token_before_dispatch'), 10, 2);
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
                    'callback' => array($this, 'create_site_user')
                ),

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
        register_rest_route(
            $namespace,
            '/avatar',
            array(
                array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'update_user_avatar'),
                    'permission_callback' => 'is_user_logged_in'
                )
            )
        );
        register_rest_route(
            $namespace,
            '/token/email',
            array(
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'login_with_email_password')
                )
            )
        );
        register_rest_route(
            $namespace,
            '/token/thirdparty',
            array(
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'login_or_register_thirdparty')
                )
            )
        );
        register_rest_route(
            $namespace,
            '/token/key',
            array(
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'login_reset_password_key')
                )
            )
        );

        register_rest_route(
            $namespace,
            '/password',
            array(
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'send_reset_password_email')
                )
            )
        );
        /*
        register_rest_route(
            $namespace,
            '/password',
            array(
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'update_password'),
                    'permission_callback' => 'is_user_logged_in'
                )
            )
        );
        */
    }



    /**
     * Create a new user using the values from the request
     *
     * @param WP_REST_Request $request
     * @return array|bool|int|WP_Error
     */
    public function create_site_user(WP_REST_Request $request)
    {
        $params = $request->get_params();

        if (isset($params["stripe_token"]) && $params["plan_id"] !== "basic_monthly") {
            $payment = new HECTV_Payment($params["stripe_token"]);
            $has_paid = $payment->start_payment_plan($params["email"], $params["plan_id"]);
            if ($has_paid !== true) {
                return $has_paid;
            }

            $params["stripe_customer_id"] = $payment->get_customer_id();
        }

        $name = isset($params["name"]) ? $params["name"] : $params["firstName"] . " " . $params["lastName"];
        $user_id = $this->create_user_account($params["email"], $name, $params["password"]);

        if (is_int($user_id)) {
            $this->add_meta_data($user_id, $params);
        }

        return is_int($user_id) ? array("id" => $user_id) : $user_id;
    }

    /**
     * Update an existing user using the values from the request
     *
     * @param WP_REST_Request $request
     * @return int|WP_Error
     */
    public function update_user(\WP_REST_Request $request)
    {
        $read_only = array("plan_id", "email", "stripe_customer_id", "parent_user", "site");
        $params = $request->get_params();

        $id = $params["id"];
        if ($id === "me") {
            $id = get_current_user_id();
        } else {
            return new \WP_Error(
                'update_error',
                'Can only update your profile. The id parameter is set to ' . $id,
                array('status' => 404)
            );
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

    /**
     * Update a user's avatar using the file uploaded
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    public function update_user_avatar(\WP_REST_Request $request)
    {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        /**
         * @TODO @FIXME Permissions for filetypes etc
         */
        $attachment_id = media_handle_upload('avatar', 0);
        if (!$attachment_id) {
            return new WP_Error(
                'user_avatar',
                'unable to handle upload',
                array('status', 400)
            );
        }

        $user = wp_get_current_user();
        if (!$user) {
            return new WP_Error(
                'user_avatar',
                'unable to retrieve current user',
                array('status', 400)
            );
        }

        $user_id = 'user_' . $user->ID;
        update_field('avatar', $attachment_id, $user_id);

        return [];
    }

    /**
     * Check if the user has permissions to update
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function update_item_permissions_check($request)
    {
        return is_user_logged_in();
    }

    /**
     * Update user's password using the values from the request
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    public function update_password(WP_REST_Request $request)
    {
        $user = wp_get_current_user();
        if (!$user) {
            return new WP_Error(
                'password_error',
                'Unable to retrieve user',
                array('status' => 401)
            );
        }

        wp_set_password($user->ID, $request->get_param('password'));

        /**
         * @TODO? Should we return something?
         */
        return array();
    }

    /**
     * Gets user authentication token using the values from the request or returns an error
     *
     * @param WP_REST_Request $request
     * @return bool|mixed|WP_Error
     */
    public function login_with_email_password(WP_REST_Request $request)
    {
        $email = $request->get_param('username');
        $password = $request->get_param('password');

        if (!$email || !$password) {
            return new WP_Error(
                'login_error',
                'Missing email or password param',
                array('status' => 400)
            );
        }

        $user = get_user_by('email', $email);
        if (wp_check_password($password, $user->data->user_pass, $user->ID)) {
            return $this->generate_token($user);
        }

        return new WP_Error(
            'login_error',
            'Incorrect email or password',
            array('status' => 400)
        );

    }

    /**
     * Resets the user password and logs into the account
     *
     * @param WP_REST_Request $request
     * @return mixed|WP_Error
     */
    public function login_reset_password_key(WP_REST_Request $request)
    {
        $email = $request->get_param("username");
        $password_key = $request->get_param("resetCode");
        $new_password = $request->get_param("password");

        if (!$email) {
            return new WP_Error(
                'password_error',
                'Missing parameter: login'
            );
        }
        if (!$password_key) {
            return new WP_Error(
                'password_error',
                'Missing parameter: password_key'
            );
        }
        if (!$new_password) {
            return new WP_Error(
                'password_error',
                'Missing parameter: new user password'
            );
        }

        $field = (strpos($email, '@') !== false) ? 'email' : 'user_login';
        $user = get_user_by($field, $email);

        if (!$user) {
            return new WP_Error(
                'password_error',
                'User not found by login: ' . $email
            );
        }

        $validate_key = check_password_reset_key($password_key, $user->user_login);
        if (is_wp_error($validate_key)) {
            return new WP_Error(
                'key_error',
                $validate_key->get_error_message(),
                array('status' => 400)
            );
        }

        reset_password($user, $new_password);

        return $this->generate_token($user);

    }


    /**
     * Send password reset key from values in the request
     *
     * @param WP_REST_Request $request
     * @return array|bool|WP_Error
     */
    public function send_reset_password_email(WP_REST_Request $request){
        $email = $request->get_param("login");
        $field = (strpos($email, '@') !== false) ? 'email' : 'login';
        $user = get_user_by($field, $email);

        if (!$user) {
            return new WP_Error(
                'password_error',
                'User not found by login: ' . $email,
                array('status' => 400)
            );
        }

        $user_login = $user->user_login;
        $user_email = $user->user_email;
        $key = $this->get_password_reset_key( $user );
        if ( is_wp_error( $key ) ) {
            return new WP_Error(
                'email_error',
                $key->get_error_message(),
                array('status' => 400)
            );
        }
        if ( is_multisite() ) {
            $site_name = get_network()->site_name;
        } else {
            $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        }
        $message = __( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
        $message .= sprintf( __( 'Site Name: %s'), $site_name ) . "\r\n\r\n";
        $message .= sprintf( __( 'Username: %s'), $user_login ) . "\r\n\r\n";
        $message .= __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "\r\n\r\n";
        $message .= __( 'To reset your password, enter this code in the specified textbox: ' . $key ) . "\r\n\r\n";
        $title = sprintf( __( '[%s] Password Reset' ), $site_name );
        $title = apply_filters( 'retrieve_password_title', $title, $user_login, $user );
        $message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user );
        if ( $message && !wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) )
            return new WP_Error(
                'email_error',
                'Possible reason: your host may have disabled the mail() function.',
                array('status' => 500)
            );
        return array("message" => "sent");
    }


    public function login_or_register_thirdparty(WP_REST_Request $request)
    {
        if (!$provider = $request->get_param('provider')) {
            return new WP_Error(
                'register_error',
                'Missing provider field',
                array('status' => 400)
            );
        }

        $newUser = false;

        switch($provider){
            case "google":
                $resp = $this->validate_google_id_token($request->get_param('accessToken'));
                break;
            case "facebook":
                $resp = $this->validate_facebook_token($request->get_param('accessToken'), $request->get_param('id'));
                break;
            default:
                return new WP_Error(
                    'register_error',
                    'Unknown provider: ' . $provider,
                    array('status' => 404)
                );

        }

        if (isset($resp['error'])) {
            return new WP_Error(
                'register_error',
                'Invalid access token',
                array('status' => 401)
            );
        }

        if (!isset($resp['email']) || $resp['email'] !== $request->get_param('email')) {
            return new WP_Error(
                'register_error',
                'Access token email and param email do not match',
                array('status' => 401)
            );
        }


        /**
         * If the user email does not exist
         * create a new user
         */
        $user = get_user_by('email', $resp['email']);
        $site = $this->get_request_site($request);
        if (!$user || $user instanceof WP_Error) {
            /**
             * @TODO Update this with role etc
             * @SEE HECTV_Users::create_user_account
             * @SEE HECTV_Users::create_site_user
             */

            $firstName = $request->get_param('firstName');
            $lastName = $request->get_param('lastName');

            if(!$firstName)
                $firstName = $resp["firstName"];
            if(!$lastName)
                $lastName = $resp["lastName"];

            $id = wp_insert_user(
                array(
                    'user_login' => $resp['email'],
                    'user_email' => $resp['email'],
                    'user_pass' => wp_generate_password(),
                    'nickname' => $firstName,
                    'first_name' => $firstName,
                    'last_name' => $lastName
                )
            );

            if (!$id) {
                return new WP_Error(
                    'register_error',
                    'Could create new user - ' . $id,
                    array('status' => 401)
                );
            }

            if ($id instanceof WP_Error) {
                return $id;
            }

            if (!$user = get_user_by('id', $id)) {
                return new WP_Error(
                    'register_error',
                    'Could not retrieve newly created user',
                    array('status' => 500)
                );
            }

            if ($site) {
                update_field('site', $site->ID, 'user_' . $user->ID);
                // Refresh user after setting site
                $user = get_user_by('id', $user->ID);
            }

            $newUser = true;
        } else {
            $verified_site = $this->verify_user_site($user, $site);
            if (is_wp_error($verified_site)) {
                return $verified_site;
            }
        }

        $data = $this->generate_token($user);
        $data['newUser'] = $newUser;

        return $data;
    }

    public function update_user_field_default()
    {
        error_log(var_export(func_get_args(), true));
    }

    public function auth_token_before_dispatch($data, $user)
    {
        //Add needed update
        return $data;
    }


    /**
     * Get password hash key
     *
     * @param $user
     * @return bool|mixed|string|WP_Error
     */
    private function get_password_reset_key($user)
    {
        require_once(ABSPATH . 'wp-includes/class-phpass.php');
        $wp_hasher = new \PasswordHash(8, true);

        do_action('retreive_password', $user->data->user_login);
        do_action('retrieve_password', $user->data->user_login);

        $allow = true;
        if (is_multisite() && is_user_spammy($user)) {
            $allow = false;
        }

        $allow = apply_filters('allow_password_reset', $allow, $user->data->ID);
        if (!$allow) {
            return new WP_Error('no_password_reset', __('Password reset is not allowed for this user'));
        } elseif (is_wp_error($allow)) {
            return $allow;
        }
        // Generate something random for a password reset key.
        $key = wp_generate_password(6, false);
        do_action('retrieve_password_key', $user->data->user_login, $key);

        $hashed = time() . ':' . $wp_hasher->HashPassword($key);
        $key_saved = $this->db->update(
            $this->db->users,
            array(
                'user_activation_key' => $hashed
            ),
            array('user_login' => $user->user_login)
        );

        if (false === $key_saved) {
            return new WP_Error('no_password_key_update', __('Could not save password reset key to database.'));
        }

        return $key;
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
        $settings = get_page_by_title('User Settings', OBJECT, 'acf-field-group');
        $acf_field_names = [];
        foreach (acf_get_fields($settings->ID) as $f) {
            $acf_field_names[] = $f['name'];
        }
        foreach ($params as $key => $value) {
            if (in_array($key, $acf_field_names)) {
                if ($key === 'site') {
                    $site = get_posts(
                        [
                            'name' => $value,
                            'post_type' => 'sites',
                            'post_status' => 'publish',
                            'numberposts' => 1
                        ]
                    );
                    if ($site) {
                        update_field('site', $site[0]->ID, 'user_' . $user_id);
                    }

                    continue;
                }
                update_field($key, $value, 'user_' . $user_id);

                continue;
            }

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
        if ($previous_plan_id !== $plan_id) {
            $stripe_meta = get_user_meta($user_id, "stripe_customer_id");
            if ($stripe_meta === null || count($stripe_meta) <= 0) {
                if ($stripe_token !== null) {
                    $user_info = get_userdata($user_id);
                    $email = $user_info->user_email;
                    $payment = new HECTV_Payment($stripe_token);

                    $has_paid = $payment->start_payment_plan($email, $plan_id);
                    if ($has_paid !== true) {
                        return $has_paid;
                    }

                    $params = array(
                        "stripe_customer_id" => $payment->get_customer_id(),
                        "plan_id" => $plan_id
                    );
                    $this->update_meta_data($user_id, $params);
                } else {
                    return new \WP_Error('payment_error', 'No stripe token', array('status' => 404));
                }
            } else {
                $stripe_customer_id = $stripe_meta[0];
                $payment = new HECTV_Payment("", $stripe_customer_id);
                $payment->change_payment_plan($plan_id);
                $params = array("plan_id" => $plan_id);
                $this->update_meta_data($user_id, $params);
            }
        }
        return true;
    }

    /**
     * @param string $token Google id_token(not access token)
     *
     * @return array|null
     */
    private function validate_google_id_token($token)
    {
        return @json_decode(
            wp_remote_retrieve_body(
                wp_remote_get(
                    add_query_arg(
                        ['access_token' => $token],
                        'https://www.googleapis.com/oauth2/v1/tokeninfo'
                    )
                )
            ),
            true
        );
    }

    /**
     * @param string $token Facebook access token
     *
     * @return array|null
     */
    private function validate_facebook_token($token, $id)
    {
        return @json_decode(
            wp_remote_retrieve_body(
                wp_remote_get(
                    add_query_arg(
                        [
                            'access_token' => $token,
                            'fields' => 'email'
                        ],
                        'https://graph.facebook.com/v3.2/' . preg_replace('/[^0-9A-z]/', '', $id) . '/'
                    )
                )
            ),
            true
        );
    }

    private function generate_token($user)
    {
        $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
        $issuedAt = time();
        $notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
        $expire = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 7), $issuedAt);
        $token = array(
            'iss' => get_bloginfo('url'),
            'iat' => $issuedAt,
            'nbf' => $notBefore,
            'exp' => $expire,
            'data' => array(
                'user' => array(
                    'id' => $user->data->ID,
                ),
            ),
        );
        /** Let the user modify the token data before the sign. */
        $token = JWT::encode(apply_filters('jwt_auth_token_before_sign', $token, $user), $secret_key);
        /** The token is signed, now create the object with no sensible user data to the client*/
        $data = array(
            'token' => $token,
            'user_email' => $user->data->user_email,
            'user_nicename' => $user->data->user_nicename,
            'user_display_name' => $user->data->display_name,
        );
        /** Let the user modify the data before send it back */
        return apply_filters('jwt_auth_token_before_dispatch', $data, $user);
    }


    /**
     * Get the matching requested site
     *
     * @param WP_REST_Request $request
     * @return null|mixed
     */
    private function get_request_site(WP_REST_Request $request)
    {
        $site_name = $request->get_param('site');
        if (!empty($site_name)) {
            $site = get_posts(
                array(
                    'name' => $site_name,
                    'post_type' => 'sites',
                    'post_status' => 'publish',
                    'posts_per_page' => 1
                )
            );
            return $site[0];
        }
        return null;
    }

    /**
     * Get the custom field stored for the user
     *
     * @param WP_REST_Request $request
     * @param $field
     * @return array|WP_Error
     */
    private function get_requested_field(WP_REST_Request $request, $field){
        $user = $this->get_requested_user($request);
        if(is_wp_error($user))
            return $user; 
        
        $user_id = 'user_' . $user->ID;
        $response = get_field($field, $user_id);

        if($response)
            return $response;

        return [];
    }

    /**
     * Get the user from the WP_REST_Request
     *
     * @param WP_REST_Request $request
     * @return WP_Error|\WP_User
     */
    private function get_requested_user(WP_REST_Request $request){
        $id = $request->get_param('id');
        if($id === "me")
            $user = wp_get_current_user();
        else
            $user = get_user_by( 'id', $id );

        if (!$user) {
            return new WP_Error(
                'user_error',
                'unable to retrieve current user',
                array('status', 400)
            );
        }
        
        return $user;
    }


}
