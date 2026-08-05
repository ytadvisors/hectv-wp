<?php
/**
 * Plugin Name: HEC newsletter API
 * Description: Site-controlled newsletter bridge from HEC Media to Mailchimp for WordPress.
 */

define( 'HECTV_NEWSLETTER_AUDIENCE_NAME', 'Newsletter Master' );
define( 'HECTV_NEWSLETTER_RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify' );
define( 'HECTV_NEWSLETTER_RECAPTCHA_HOSTS', 'hecmedia.org,www.hecmedia.org' );

/**
 * Register the write endpoint consumed by the HEC Media Next.js API route.
 */
function hectv_newsletter_register_route() {
	register_rest_route(
		'hectv/v1',
		'/newsletter/subscribe',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'hectv_newsletter_subscribe',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'hectv_newsletter_register_route' );

/**
 * Return a REST-safe error without exposing provider or credential details.
 */
function hectv_newsletter_error( $code, $message, $status ) {
	return new WP_Error(
		$code,
		$message,
		array( 'status' => (int) $status )
	);
}

/**
 * Outbound writes are production-only unless a developer explicitly opts in.
 */
function hectv_newsletter_delivery_enabled() {
	if ( getenv( 'HECTV_DISABLE_OUTBOUND' ) === '1' ) {
		return false;
	}

	return getenv( 'HECTV_ENVIRONMENT' ) === 'production'
		|| getenv( 'HECTV_NEWSLETTER_ALLOW_NON_PRODUCTION' ) === '1';
}

/**
 * WordPress is the authority for whether the public newsletter route requires
 * CAPTCHA. The option defaults on and remains available even if the CMS fields
 * MU-plugin is temporarily unavailable or loads after this file in a test.
 */
function hectv_newsletter_captcha_enabled() {
	$option_name = defined( 'HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED' )
		? HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED
		: 'hectv_newsletter_captcha_enabled';
	$value = get_option( $option_name, '1' );
	if ( is_bool( $value ) ) {
		return $value;
	}
	return ! in_array( strtolower( trim( (string) $value ) ), array( '0', 'false', 'off', 'no' ), true );
}

/**
 * Verify the browser token where the CAPTCHA secret can remain in managed ECS
 * runtime secrets. The legacy HEC Media Lambda@Edge runtime cannot safely
 * receive request-time secrets.
 */
function hectv_newsletter_verify_captcha( $token ) {
	$secret = trim( (string) getenv( 'HECTV_RECAPTCHA_SECRET_KEY' ) );
	if ( strlen( $secret ) < 20 ) {
		return hectv_newsletter_error(
			'hectv_newsletter_captcha_unconfigured',
			'Newsletter signup is not configured.',
			503
		);
	}

	$response = wp_remote_post(
		HECTV_NEWSLETTER_RECAPTCHA_VERIFY_URL,
		array(
			'body'    => array(
				'secret'   => $secret,
				'response' => $token,
			),
			'timeout' => 5,
		)
	);

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return hectv_newsletter_error(
			'hectv_newsletter_captcha_unavailable',
			'Newsletter signup could not be completed.',
			502
		);
	}

	$verification = json_decode( wp_remote_retrieve_body( $response ), true );
	$hostname     = is_array( $verification ) && isset( $verification['hostname'] )
		? strtolower( rtrim( (string) $verification['hostname'], '.' ) )
		: '';
	$host_setting = trim( (string) getenv( 'HECTV_RECAPTCHA_ALLOWED_HOSTS' ) );
	$allowed      = array_map(
		function ( $host ) {
			return strtolower( rtrim( trim( $host ), '.' ) );
		},
		explode(
		',',
		$host_setting === '' ? HECTV_NEWSLETTER_RECAPTCHA_HOSTS : $host_setting
		)
	);
	$allowed      = array_filter( $allowed );

	if (
		! is_array( $verification )
		|| ! isset( $verification['success'] )
		|| $verification['success'] !== true
		|| ! in_array( $hostname, $allowed, true )
	) {
		return hectv_newsletter_error(
			'hectv_newsletter_captcha_failed',
			'Spam verification failed.',
			400
		);
	}

	return true;
}

/**
 * Resolve the newsletter audience from the Mailchimp for WordPress cache.
 * An explicit ID can be provided for disaster recovery, but is never committed.
 */
function hectv_newsletter_audience_id() {
	$explicit_id = trim( (string) getenv( 'HECTV_NEWSLETTER_LIST_ID' ) );
	if ( $explicit_id !== '' ) {
		if ( ! preg_match( '/^[A-Za-z0-9_-]+$/', $explicit_id ) ) {
			return hectv_newsletter_error(
				'hectv_newsletter_invalid_audience',
				'Newsletter signup is not configured.',
				503
			);
		}
		return $explicit_id;
	}

	$audience_name = trim( (string) getenv( 'HECTV_NEWSLETTER_LIST_NAME' ) );
	if ( $audience_name === '' ) {
		$audience_name = HECTV_NEWSLETTER_AUDIENCE_NAME;
	}

	$matches  = array();
	$list_ids = (array) get_option( 'mc4wp_mailchimp_list_ids', array() );
	foreach ( $list_ids as $list_id ) {
		$list = get_option( 'mc4wp_mailchimp_list_' . $list_id, null );
		if ( is_object( $list ) && isset( $list->name ) && strcasecmp( $list->name, $audience_name ) === 0 ) {
			$matches[] = (string) $list_id;
		}
	}

	if ( count( $matches ) !== 1 ) {
		return hectv_newsletter_error(
			'hectv_newsletter_audience_missing',
			'Newsletter signup is not configured.',
			503
		);
	}

	return $matches[0];
}

/**
 * Resolve the API object supplied by the installed Mailchimp for WordPress plugin.
 */
function hectv_newsletter_mailchimp_api() {
	if ( ! function_exists( 'mc4wp' ) ) {
		return hectv_newsletter_error(
			'hectv_newsletter_provider_missing',
			'Newsletter signup is not configured.',
			503
		);
	}

	try {
		$api = mc4wp( 'api' );
	} catch ( Exception $exception ) {
		return hectv_newsletter_error(
			'hectv_newsletter_provider_missing',
			'Newsletter signup is not configured.',
			503
		);
	}
	if (
		! is_object( $api )
		|| ! method_exists( $api, 'get_list_member' )
		|| ! method_exists( $api, 'add_list_member' )
	) {
		return hectv_newsletter_error(
			'hectv_newsletter_provider_incompatible',
			'Newsletter signup is not configured.',
			503
		);
	}

	return $api;
}

/**
 * Normalize and validate the public form payload a second time at WordPress.
 */
function hectv_newsletter_payload( $request ) {
	$data = $request->get_json_params();
	if ( ! is_array( $data ) ) {
		return hectv_newsletter_error(
			'hectv_newsletter_invalid_request',
			'Please provide valid newsletter signup information.',
			400
		);
	}

	$first_name = isset( $data['firstName'] )
		? substr( sanitize_text_field( $data['firstName'] ), 0, 100 )
		: '';
	$last_name = isset( $data['lastName'] )
		? substr( sanitize_text_field( $data['lastName'] ), 0, 100 )
		: '';
	$email = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
	$captcha_token = isset( $data['captchaToken'] ) && is_string( $data['captchaToken'] )
		? trim( $data['captchaToken'] )
		: '';
	$captcha_required = hectv_newsletter_captcha_enabled();

	if (
		$first_name === ''
		|| $last_name === ''
		|| ! is_email( $email )
		|| ! isset( $data['consent'] )
		|| $data['consent'] !== true
		|| ( $captcha_required && strlen( $captcha_token ) < 10 )
		|| strlen( $captcha_token ) > 4096
	) {
		return hectv_newsletter_error(
			'hectv_newsletter_invalid_request',
			'Please provide valid newsletter signup information.',
			400
		);
	}

	return array(
		'email'      => strtolower( $email ),
		'first_name' => $first_name,
		'last_name'  => $last_name,
		'captcha'    => $captcha_token,
	);
}

/**
 * Return one non-enumerating response for new, pending, and existing members.
 */
function hectv_newsletter_accepted_response() {
	$response = new WP_REST_Response(
		array( 'ok' => true, 'status' => 'accepted' ),
		202
	);
	$response->header( 'Cache-Control', 'no-store' );
	return $response;
}

/**
 * Whether Mailchimp already has the member in a non-enumerating accepted state.
 */
function hectv_newsletter_member_is_accepted( $member ) {
	return is_object( $member )
		&& isset( $member->status )
		&& in_array( $member->status, array( 'pending', 'subscribed' ), true );
}

/**
 * Recover a concurrent upsert whose provider response failed after another
 * request created the member. Only an observed accepted state is swallowed;
 * transport errors and non-active states continue to fail closed.
 */
function hectv_newsletter_member_became_accepted( $api, $list_id, $email ) {
	try {
		$member = $api->get_list_member( $list_id, $email );
	} catch ( Exception $exception ) {
		return false;
	}

	return hectv_newsletter_member_is_accepted( $member );
}

/**
 * Add a subscriber as pending so Mailchimp owns confirmation and consent proof.
 */
function hectv_newsletter_subscribe( $request ) {
	if ( ! hectv_newsletter_delivery_enabled() ) {
		return hectv_newsletter_error(
			'hectv_newsletter_delivery_disabled',
			'Newsletter signup is not available in this environment.',
			503
		);
	}

	$payload = hectv_newsletter_payload( $request );
	if ( is_wp_error( $payload ) ) {
		return $payload;
	}

	if ( hectv_newsletter_captcha_enabled() ) {
		$captcha = hectv_newsletter_verify_captcha( $payload['captcha'] );
		if ( is_wp_error( $captcha ) ) {
			return $captcha;
		}
	}

	$list_id = hectv_newsletter_audience_id();
	if ( is_wp_error( $list_id ) ) {
		return $list_id;
	}

	$api = hectv_newsletter_mailchimp_api();
	if ( is_wp_error( $api ) ) {
		return $api;
	}

	$member = null;
	try {
		$member = $api->get_list_member( $list_id, $payload['email'] );
	} catch ( MC4WP_API_Resource_Not_Found_Exception $exception ) {
		$member = null;
	} catch ( Exception $exception ) {
		return hectv_newsletter_error(
			'hectv_newsletter_provider_error',
			'Newsletter signup could not be completed.',
			502
		);
	}

	if ( hectv_newsletter_member_is_accepted( $member ) ) {
		return hectv_newsletter_accepted_response();
	}

	try {
		// MC4WP 4.3.3 implements add_list_member() as a subscriber-hash PUT,
		// despite the historical method name. Keep this upsert path: it moves
		// known inactive members back to pending and makes concurrent creates
		// idempotent. update_list_member() is PATCH-only and cannot create a
		// member that was absent when the request began.
		$result = $api->add_list_member(
			$list_id,
			array(
				'email_address' => $payload['email'],
				'email_type'    => 'html',
				'status'        => 'pending',
				'merge_fields'  => array(
					'FNAME' => $payload['first_name'],
					'LNAME' => $payload['last_name'],
				),
			)
		);
	} catch ( Exception $exception ) {
		if ( hectv_newsletter_member_became_accepted( $api, $list_id, $payload['email'] ) ) {
			return hectv_newsletter_accepted_response();
		}

		return hectv_newsletter_error(
			'hectv_newsletter_provider_error',
			'Newsletter signup could not be completed.',
			502
		);
	}

	if (
		! is_object( $result )
		|| empty( $result->status )
		|| ! in_array( $result->status, array( 'pending', 'subscribed' ), true )
	) {
		return hectv_newsletter_error(
			'hectv_newsletter_provider_error',
			'Newsletter signup could not be completed.',
			502
		);
	}

	return hectv_newsletter_accepted_response();
}
