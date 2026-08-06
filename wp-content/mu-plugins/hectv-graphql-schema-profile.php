<?php
/**
 * Plugin Name: HEC TV GraphQL Schema Profile
 * Description: Explicit dual-schema profile shared by staging and production. Replaces
 *              environment-only GraphQL contract drift (the 2026-08-06 root cause).
 * Version: 1.0.0
 * Author: YT Advisors
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Allow source-level unit tests to require this file without a WordPress boot.
	return;
}

/**
 * Allowed values for HECTV_GRAPHQL_SCHEMA_PROFILE.
 *
 * consumer-v1 — production-safe dual schema: legacy Lambda@Edge (v146) operations
 *               plus modern hecmedia GraphQL documents. Required for staging and
 *               production release candidates (identical profile in both envs).
 */
function hectv_graphql_schema_profile(): string {
	$raw = getenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE' );
	if ( $raw === false || $raw === '' ) {
		// Default ON for both staging and production so an omitted env var cannot
		// reintroduce staging-only contract drift.
		return 'consumer-v1';
	}
	$profile = strtolower( trim( (string) $raw ) );
	$allowed = array( 'consumer-v1' );
	return in_array( $profile, $allowed, true ) ? $profile : 'consumer-v1';
}

/**
 * Whether the consumer dual-schema contract layer is active.
 */
function hectv_graphql_consumer_contract_enabled(): bool {
	return hectv_graphql_schema_profile() === 'consumer-v1';
}

/**
 * Expose the active profile for ops probes and readiness checks.
 */
add_action(
	'init',
	static function () {
		if ( ! defined( 'HECTV_GRAPHQL_SCHEMA_PROFILE_ACTIVE' ) ) {
			define( 'HECTV_GRAPHQL_SCHEMA_PROFILE_ACTIVE', hectv_graphql_schema_profile() );
		}
	},
	1
);
