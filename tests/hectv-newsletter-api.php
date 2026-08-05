<?php
/**
 * Behavioral contract for the CAPTCHA-protected HEC newsletter bridge.
 *
 * Run: php tests/hectv-newsletter-api.php
 */

define( 'ABSPATH', __DIR__ );

$actions              = array();
$registered_route     = null;
$options              = array();
$mailchimp_api        = null;
$remote_post_response = null;
$remote_post_calls    = array();

function add_action( $hook, $callback ) {
	global $actions;
	$actions[ $hook ][] = $callback;
}

function register_rest_route( $namespace, $route, $config ) {
	global $registered_route;
	$registered_route = compact( 'namespace', 'route', 'config' );
}

function __return_true() {
	return true;
}

function get_option( $key, $default = false ) {
	global $options;
	return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_email( $value ) {
	return trim( strtolower( (string) $value ) );
}

function is_email( $value ) {
	return filter_var( $value, FILTER_VALIDATE_EMAIL ) !== false;
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function wp_remote_post( $url, $args ) {
	global $remote_post_calls, $remote_post_response;
	$remote_post_calls[] = array( $url, $args );
	return $remote_post_response;
}

function wp_remote_retrieve_response_code( $response ) {
	return isset( $response['response']['code'] ) ? $response['response']['code'] : 0;
}

function wp_remote_retrieve_body( $response ) {
	return isset( $response['body'] ) ? $response['body'] : '';
}

function mc4wp() {
	global $mailchimp_api;
	return $mailchimp_api;
}

class WP_REST_Server {
	const CREATABLE = 'POST';
}

class WP_Error {
	public $code;
	public $message;
	public $data;

	public function __construct( $code, $message, $data = array() ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}
}

class WP_REST_Response {
	public $data;
	public $status;
	public $headers = array();

	public function __construct( $data, $status ) {
		$this->data   = $data;
		$this->status = $status;
	}

	public function header( $name, $value ) {
		$this->headers[ $name ] = $value;
	}
}

class MC4WP_API_Resource_Not_Found_Exception extends Exception {}

require dirname( __DIR__ ) . '/wp-content/plugins/mailchimp-for-wp/includes/api/class-api-v3.php';

class Hectv_Newsletter_Fake_Client {
	public $put_calls = array();

	public function put( $resource, $payload ) {
		$this->put_calls[] = array( $resource, $payload );
		return (object) array( 'status' => 'pending' );
	}
}

class Hectv_Newsletter_Pinned_Api extends MC4WP_API_v3 {
	public function __construct( $client ) {
		$this->client = $client;
	}
}

class Hectv_Newsletter_Test_Request {
	private $params;

	public function __construct( $params ) {
		$this->params = $params;
	}

	public function get_json_params() {
		return $this->params;
	}
}

class Hectv_Newsletter_Fake_Api {
	public $member;
	public $get_exception;
	public $get_sequence = array();
	public $add_exception;
	public $add_result;
	public $get_calls = array();
	public $add_calls = array();

	public function get_list_member( $list_id, $email ) {
		$this->get_calls[] = array( $list_id, $email );
		if ( count( $this->get_sequence ) > 0 ) {
			$next = array_shift( $this->get_sequence );
			if ( $next instanceof Exception ) {
				throw $next;
			}
			return $next;
		}
		if ( $this->get_exception ) {
			throw $this->get_exception;
		}
		return $this->member;
	}

	public function add_list_member( $list_id, $payload ) {
		$this->add_calls[] = array( $list_id, $payload );
		if ( $this->add_exception ) {
			throw $this->add_exception;
		}
		return $this->add_result;
	}
}

function expect_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, "$message\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

function expect_true( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "$message\n" );
		exit( 1 );
	}
}

function newsletter_request( $payload ) {
	return new Hectv_Newsletter_Test_Request( $payload );
}

function captcha_response( $success, $hostname = 'hecmedia.org' ) {
	return array(
		'response' => array( 'code' => 200 ),
		'body'     => json_encode(
			array(
				'success'  => $success,
				'hostname' => $hostname,
			)
		),
	);
}

require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-newsletter-api.php';

expect_same( true, hectv_newsletter_captcha_enabled(), 'CAPTCHA defaults on when Site Settings has not been saved.' );

$pinned_client = new Hectv_Newsletter_Fake_Client();
$pinned_api    = new Hectv_Newsletter_Pinned_Api( $pinned_client );
$pinned_result = $pinned_api->add_list_member(
	'newsletter-list',
	array(
		'email_address' => 'reader@example.com',
		'status'        => 'pending',
	)
);
expect_same( 'pending', $pinned_result->status, 'The pinned provider upsert returns the member state.' );
expect_same( 1, count( $pinned_client->put_calls ), 'The pinned add_list_member method performs one request.' );
expect_true(
	strpos( $pinned_client->put_calls[0][0], '/lists/newsletter-list/members/' ) === 0,
	'The pinned provider targets the subscriber-hash member resource.'
);
expect_same(
	'pending',
	$pinned_client->put_calls[0][1]['status'],
	'The pinned provider sends the pending double-opt-in state via PUT.'
);

foreach ( $actions['rest_api_init'] as $callback ) {
	$callback();
}

expect_same( 'hectv/v1', $registered_route['namespace'], 'Newsletter route uses the owned HEC namespace.' );
expect_same( '/newsletter/subscribe', $registered_route['route'], 'Newsletter route path remains stable.' );
expect_same( 'POST', $registered_route['config']['methods'], 'Newsletter route only accepts POST.' );
expect_same( '__return_true', $registered_route['config']['permission_callback'], 'CAPTCHA, not a browser-visible application secret, protects the public route.' );

$valid_payload = array(
	'firstName'    => ' Ada ',
	'lastName'     => ' Lovelace ',
	'email'        => 'READER@example.com',
	'consent'      => true,
	'captchaToken' => 'captcha-token-123',
	'source'       => 'newsletter-page',
);

putenv( 'HECTV_ENVIRONMENT=staging' );
putenv( 'HECTV_DISABLE_OUTBOUND=1' );
$disabled = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 'hectv_newsletter_delivery_disabled', $disabled->code, 'Staging cannot write to CAPTCHA or Mailchimp.' );
expect_same( 0, count( $remote_post_calls ), 'No-send mode exits before any external request.' );

putenv( 'HECTV_ENVIRONMENT=production' );
putenv( 'HECTV_DISABLE_OUTBOUND=0' );
putenv( 'HECTV_RECAPTCHA_SECRET_KEY=short' );
$unconfigured = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 'hectv_newsletter_captcha_unconfigured', $unconfigured->code, 'A missing CAPTCHA secret fails closed.' );

putenv( 'HECTV_RECAPTCHA_SECRET_KEY=test-recaptcha-secret-with-safe-length' );
putenv( 'HECTV_RECAPTCHA_ALLOWED_HOSTS' );
$invalid_payload                 = $valid_payload;
$invalid_payload['captchaToken'] = '';
$invalid                         = hectv_newsletter_subscribe( newsletter_request( $invalid_payload ) );
expect_same( 400, $invalid->data['status'], 'WordPress independently requires a CAPTCHA token.' );

$remote_post_response = captcha_response( false );
$captcha_failed       = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 'hectv_newsletter_captcha_failed', $captcha_failed->code, 'A failed CAPTCHA cannot reach Mailchimp.' );

$remote_post_response = captcha_response( true, 'attacker.example' );
$wrong_host           = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 'hectv_newsletter_captcha_failed', $wrong_host->code, 'A token for another hostname is rejected.' );

$remote_post_response = new WP_Error( 'transport_error', 'private transport detail' );
$captcha_unavailable  = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 'hectv_newsletter_captcha_unavailable', $captcha_unavailable->code, 'CAPTCHA transport failures fail closed.' );
expect_true( strpos( $captcha_unavailable->message, 'private transport' ) === false, 'CAPTCHA transport details are not exposed.' );

$remote_post_response = captcha_response( true );
putenv( 'HECTV_NEWSLETTER_LIST_ID' );
$options = array(
	'mc4wp_mailchimp_list_ids'             => array( 'staff-list', 'newsletter-list' ),
	'mc4wp_mailchimp_list_staff-list'      => (object) array( 'name' => 'HEC-TV Staff' ),
	'mc4wp_mailchimp_list_newsletter-list' => (object) array( 'name' => 'Newsletter Master' ),
);

$mailchimp_api         = new Hectv_Newsletter_Fake_Api();
$mailchimp_api->member = (object) array( 'status' => 'pending' );
$pending               = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 202, $pending->status, 'Already-pending subscribers remain an accepted request.' );
expect_same( 'accepted', $pending->data['status'], 'The response does not reveal member state.' );
expect_same( 0, count( $mailchimp_api->add_calls ), 'A retry does not resend the double-opt-in request.' );

// The WordPress setting is authoritative: when explicitly disabled, a missing
// browser token reaches Mailchimp without contacting Google.
$options['hectv_newsletter_captcha_enabled'] = '0';
$captcha_free_payload = $valid_payload;
unset( $captcha_free_payload['captchaToken'] );
$captcha_calls_before = count( $remote_post_calls );
$mailchimp_api         = new Hectv_Newsletter_Fake_Api();
$mailchimp_api->member = (object) array( 'status' => 'pending' );
$captcha_free          = hectv_newsletter_subscribe( newsletter_request( $captcha_free_payload ) );
expect_same( 202, $captcha_free->status, 'Site Settings can disable CAPTCHA for newsletter signup.' );
expect_same( $captcha_calls_before, count( $remote_post_calls ), 'Disabled CAPTCHA never calls the Google verification endpoint.' );
$options['hectv_newsletter_captcha_enabled'] = '1';

$mailchimp_api         = new Hectv_Newsletter_Fake_Api();
$mailchimp_api->member = (object) array( 'status' => 'subscribed' );
$subscribed            = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 202, $subscribed->status, 'Existing subscribers receive the same accepted response.' );
expect_same( $pending->data, $subscribed->data, 'The API cannot be used to enumerate subscribers.' );
expect_same( 0, count( $mailchimp_api->add_calls ), 'Existing subscribers are not rewritten.' );

$mailchimp_api             = new Hectv_Newsletter_Fake_Api();
$mailchimp_api->member     = (object) array( 'status' => 'unsubscribed' );
$mailchimp_api->add_result = (object) array( 'status' => 'pending' );
$resubscribed              = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 202, $resubscribed->status, 'Unsubscribed members can restart double opt-in.' );
expect_same( $pending->data, $resubscribed->data, 'Re-subscribe does not reveal the previous member state.' );
expect_same( 1, count( $mailchimp_api->add_calls ), 'A non-active member is updated through one idempotent upsert.' );
expect_same( 'pending', $mailchimp_api->add_calls[0][1]['status'], 'The upsert returns a non-active member to pending.' );

$mailchimp_api                = new Hectv_Newsletter_Fake_Api();
$mailchimp_api->get_exception = new MC4WP_API_Resource_Not_Found_Exception();
$mailchimp_api->add_result    = (object) array( 'status' => 'pending' );
$created                      = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 202, $created->status, 'New subscribers enter Mailchimp as pending.' );
expect_same( $pending->data, $created->data, 'New and existing members receive the same response.' );
expect_same( 'no-store', $created->headers['Cache-Control'], 'Subscription responses are never cached.' );
expect_same( 1, count( $mailchimp_api->add_calls ), 'New subscribers produce one Mailchimp write.' );
expect_same( 'newsletter-list', $mailchimp_api->add_calls[0][0], 'The Newsletter Master audience is selected from WordPress configuration.' );
expect_same( 'reader@example.com', $mailchimp_api->add_calls[0][1]['email_address'], 'Email addresses are normalized.' );
expect_same( 'Ada', $mailchimp_api->add_calls[0][1]['merge_fields']['FNAME'], 'First name maps to Mailchimp FNAME.' );
expect_same( 'Lovelace', $mailchimp_api->add_calls[0][1]['merge_fields']['LNAME'], 'Last name maps to Mailchimp LNAME.' );
expect_same( 'pending', $mailchimp_api->add_calls[0][1]['status'], 'Double opt-in is mandatory.' );

$mailchimp_api                = new Hectv_Newsletter_Fake_Api();
$mailchimp_api->get_sequence  = array(
	new MC4WP_API_Resource_Not_Found_Exception(),
	(object) array( 'status' => 'pending' ),
);
$mailchimp_api->add_exception = new RuntimeException( 'concurrent provider conflict' );
$raced                        = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 202, $raced->status, 'A concurrent create that becomes pending remains accepted.' );
expect_same( $pending->data, $raced->data, 'Race recovery keeps the response non-enumerating.' );
expect_same( 2, count( $mailchimp_api->get_calls ), 'Race recovery re-reads the member after the failed upsert.' );

$mailchimp_api                = new Hectv_Newsletter_Fake_Api();
$mailchimp_api->get_sequence  = array(
	new MC4WP_API_Resource_Not_Found_Exception(),
	new RuntimeException( 'provider still unavailable' ),
);
$mailchimp_api->add_exception = new RuntimeException( 'provider write failed' );
$unrecovered                  = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 502, $unrecovered->data['status'], 'A failed upsert without an accepted member state still fails closed.' );

$last_captcha_call = end( $remote_post_calls );
expect_same( HECTV_NEWSLETTER_RECAPTCHA_VERIFY_URL, $last_captcha_call[0], 'WordPress uses the canonical reCAPTCHA verification endpoint.' );
expect_same( 'captcha-token-123', $last_captcha_call[1]['body']['response'], 'The visitor token reaches server-side verification.' );
expect_same( 'test-recaptcha-secret-with-safe-length', $last_captcha_call[1]['body']['secret'], 'The managed secret is added only inside WordPress.' );

$invalid_payload            = $valid_payload;
$invalid_payload['consent'] = false;
$invalid                    = hectv_newsletter_subscribe( newsletter_request( $invalid_payload ) );
expect_same( 400, $invalid->data['status'], 'WordPress independently enforces explicit consent.' );

$mailchimp_api                = new Hectv_Newsletter_Fake_Api();
$mailchimp_api->get_exception = new RuntimeException( 'provider detail must stay private' );
$provider_error               = hectv_newsletter_subscribe( newsletter_request( $valid_payload ) );
expect_same( 502, $provider_error->data['status'], 'Provider failures return a controlled gateway error.' );
expect_true( strpos( $provider_error->message, 'provider detail' ) === false, 'Provider exception details are never exposed.' );

echo "HEC newsletter API contracts passed.\n";
