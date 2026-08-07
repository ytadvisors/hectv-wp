<?php
/**
 * Plugin Name: HEC TV GraphQL Schema Profile
 * Description: Explicit dual-schema profile shared by staging and production. Replaces
 *              environment-only GraphQL contract drift (the 2026-08-06 root cause).
 * Version: 1.0.1
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
 *
 * Unset/empty defaults to consumer-v1 so an omitted env var cannot reintroduce
 * staging-only drift. Unknown values fail closed (empty string / contract off).
 */
function hectv_graphql_schema_profile(): string {
	$raw = getenv( 'HECTV_GRAPHQL_SCHEMA_PROFILE' );
	if ( $raw === false || trim( (string) $raw ) === '' ) {
		return 'consumer-v1';
	}
	$profile = strtolower( trim( (string) $raw ) );
	$allowed = array( 'consumer-v1' );
	return in_array( $profile, $allowed, true ) ? $profile : '';
}

/**
 * Whether the consumer dual-schema contract layer is active.
 */
function hectv_graphql_consumer_contract_enabled(): bool {
	return hectv_graphql_schema_profile() === 'consumer-v1';
}

/**
 * Stable fingerprint of the dual-schema consumer field set. Staging and
 * production must expose the same fingerprint for release parity.
 */
function hectv_graphql_consumer_fingerprint(): array {
	return array(
		'profile' => 'consumer-v1',
		'fields'  => array(
			'PostToCategoryConnectionWhereArgs.shouldOutputInFlatList',
			'RootQueryToCategoryConnectionWhereArgs.shouldOutputInFlatList',
			'CategoryToCategoryConnectionWhereArgs.shouldOutputInFlatList',
			'RootQueryToEventCategoryConnectionWhereArgs.shouldOutputInFlatList',
			'Event.excerpt',
			'Post.postDetails.videoImage',
			'Post.postDetails.postHeader',
			'Post.postDetails.isVideo',
		),
	);
}

add_action(
	'init',
	static function () {
		if ( ! defined( 'HECTV_GRAPHQL_SCHEMA_PROFILE_ACTIVE' ) ) {
			define( 'HECTV_GRAPHQL_SCHEMA_PROFILE_ACTIVE', hectv_graphql_schema_profile() );
		}
	},
	1
);
