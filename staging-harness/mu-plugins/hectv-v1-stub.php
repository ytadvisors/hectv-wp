<?php
/**
 * Plugin Name: HECMedia hectv/v1 local stub (staging boundary)
 * Description: LOCAL/STAGING ONLY. Reproduces the URL shape of the custom hectv/v1
 *              REST namespace that the headless app calls. The production plugin is
 *              custom WP-side code whose full source is not redistributed here.
 *              This stub returns well-formed fixture JSON so local development and
 *              contract tests hit the same endpoints. It is NOT a behavioral clone
 *              of production auth or live-video logic. STUB BOUNDARY PRESERVED.
 * Version: 0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	static function () {
		// PostApi.getLiveVideos expects res.data as a bare array.
		register_rest_route(
			'hectv/v1',
			'/livevideos/live',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					// Bare-array shape expected by PostApi.getLiveVideos (res.data as array).
					return new WP_REST_Response( array(), 200 );
				},
			)
		);

		// Also expose a diagnostic object route under a distinct path (not used by app).
		register_rest_route(
			'hectv/v1',
			'/livevideos/live-status',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					return new WP_REST_Response(
						array(
							'live'  => false,
							'items' => array(),
							'_stub' => 'hectv-v1-stub: fixture only; no production video source',
						),
						200
					);
				},
			)
		);

		register_rest_route(
			'hectv/v1',
			'/token/email',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $request ) {
					return new WP_REST_Response(
						array(
							'token' => 'dev-stub-token',
							'user'  => array( 'email' => $request->get_param( 'email' ) ),
							'_stub' => true,
						),
						200
					);
				},
			)
		);

		register_rest_route(
			'hectv/v1',
			'/token/thirdparty',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					return new WP_REST_Response(
						array(
							'token' => 'dev-stub-token',
							'_stub' => true,
						),
						200
					);
				},
			)
		);

		register_rest_route(
			'hectv/v1',
			'/users/me',
			array(
				'methods'             => 'GET,PUT',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					return new WP_REST_Response(
						array(
							'id'    => 1,
							'email' => 'dev@localhost',
							'_stub' => true,
						),
						200
					);
				},
			)
		);

		register_rest_route(
			'hectv/v1',
			'/users',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					return new WP_REST_Response(
						array(
							'id'    => 1,
							'_stub' => true,
						),
						201
					);
				},
			)
		);
	}
);
