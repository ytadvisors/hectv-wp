<?php
/**
 * Git-canonical ACF field groups for HECMedia / HECTV.
 *
 * Source of truth for field shapes lives in acf-field-groups.json (export from
 * production admin on 2026-08-01). Registration rules:
 *
 *  1. Prefer the production group key / field keys from that export so existing
 *     post meta continues to resolve (no key remapping).
 *  2. Register every exported group as one complete same-key local group. The
 *     local definition overlays a database copy instead of creating a second
 *     metabox, so PHP and GraphQL cannot drift from admin-created definitions.
 *  3. Always ensure the git-owned Trending field (`is_trending`) is nested in
 *     that complete Post Details definition.
 *  4. If an older database group has the same title under a different group
 *     key, reuse that active key while retaining the exported field keys.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Production Post Details group key (from admin export). */
define( 'HECTV_ACF_POST_DETAILS_KEY', 'group_5a9bf131f2b91' );

/** Git-owned Trending field key. */
define( 'HECTV_ACF_IS_TRENDING_KEY', 'field_hectv_is_trending' );

/**
 * Path to the exported field-group JSON.
 *
 * @return string
 */
function hectv_cms_acf_export_path() {
	return HECTV_CMS_FIELDS_DIR . '/acf-field-groups.json';
}

/**
 * Load the exported ACF field groups.
 *
 * @return array<int, array<string, mixed>>
 */
function hectv_cms_load_acf_export() {
	static $cache = null;
	if ( $cache !== null ) {
		return $cache;
	}

	$path = hectv_cms_acf_export_path();
	if ( ! is_readable( $path ) ) {
		$cache = array();
		return $cache;
	}

	$raw = file_get_contents( $path );
	if ( $raw === false || $raw === '' ) {
		$cache = array();
		return $cache;
	}

	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		$cache = array();
		return $cache;
	}

	// Export is a list of groups (ACF Tools → Export).
	$groups = array();
	foreach ( $data as $item ) {
		if ( is_array( $item ) && ! empty( $item['key'] ) && ! empty( $item['title'] ) ) {
			$groups[] = $item;
		}
	}

	$cache = $groups;
	return $cache;
}

/**
 * Trending field definition (git-owned; not in the legacy admin export).
 *
 * @param string $parent_group_key Parent field group key.
 * @return array<string, mixed>
 */
function hectv_cms_is_trending_field( $parent_group_key ) {
	return array(
		'key'           => HECTV_ACF_IS_TRENDING_KEY,
		'parent'        => $parent_group_key,
		'label'         => 'Trending',
		'name'          => HECTV_META_IS_TRENDING,
		'type'          => 'true_false',
		'instructions'  => 'Include this post in the Trending Now rail.',
		'required'      => 0,
		'ui'            => 1,
		'default_value' => 0,
		'message'       => '',
		'wrapper'       => array(
			'width' => '',
			'class' => '',
			'id'    => '',
		),
	);
}

/**
 * Inject is_trending into a Post Details group field list if missing.
 *
 * @param array<string, mixed> $group Field group.
 * @return array<string, mixed>
 */
function hectv_cms_with_is_trending( array $group ) {
	$fields = isset( $group['fields'] ) && is_array( $group['fields'] ) ? $group['fields'] : array();
	foreach ( $fields as $field ) {
		if ( isset( $field['name'] ) && $field['name'] === HECTV_META_IS_TRENDING ) {
			return $group;
		}
		if ( isset( $field['key'] ) && $field['key'] === HECTV_ACF_IS_TRENDING_KEY ) {
			return $group;
		}
	}

	// Place Trending near the top, after is_video when present.
	$trending = hectv_cms_is_trending_field( $group['key'] );
	unset( $trending['parent'] ); // Parent is implied when nested under group.fields.

	$inserted = false;
	$out      = array();
	foreach ( $fields as $field ) {
		$out[] = $field;
		if ( ! $inserted && isset( $field['name'] ) && $field['name'] === 'is_video' ) {
			$out[]    = $trending;
			$inserted = true;
		}
	}
	if ( ! $inserted ) {
		array_unshift( $out, $trending );
	}

	$group['fields'] = $out;
	return $group;
}

/**
 * Normalize an export group for acf_add_local_field_group.
 *
 * @param array<string, mixed> $group Export group.
 * @return array<string, mixed>
 */
function hectv_cms_normalize_local_group( array $group ) {
	// Ensure required local-group flags exist without clobbering export values.
	if ( ! isset( $group['active'] ) ) {
		$group['active'] = true;
	}
	if ( ! isset( $group['menu_order'] ) ) {
		$group['menu_order'] = 0;
	}
	if ( ! isset( $group['position'] ) ) {
		$group['position'] = 'normal';
	}
	if ( ! isset( $group['style'] ) ) {
		$group['style'] = 'default';
	}
	if ( ! isset( $group['label_placement'] ) ) {
		$group['label_placement'] = 'top';
	}
	if ( ! isset( $group['instruction_placement'] ) ) {
		$group['instruction_placement'] = 'label';
	}
	if ( ! array_key_exists( 'show_in_rest', $group ) ) {
		$group['show_in_rest'] = 0;
	}

	if ( isset( $group['title'] ) && $group['title'] === 'Post Details' ) {
		$group = hectv_cms_with_is_trending( $group );
	}

	return $group;
}

/**
 * Index currently known ACF field groups by key and title.
 *
 * @return array{by_key: array<string, array>, by_title: array<string, array>}
 */
function hectv_cms_existing_acf_groups_index() {
	$by_key   = array();
	$by_title = array();

	if ( ! function_exists( 'acf_get_field_groups' ) ) {
		return array(
			'by_key'   => $by_key,
			'by_title' => $by_title,
		);
	}

	// Empty args returns all groups (DB + already-local).
	foreach ( (array) acf_get_field_groups() as $group ) {
		if ( ! is_array( $group ) ) {
			continue;
		}
		if ( ! empty( $group['key'] ) ) {
			$by_key[ (string) $group['key'] ] = $group;
		}
		if ( ! empty( $group['title'] ) ) {
			$by_title[ (string) $group['title'] ] = $group;
		}
	}

	return array(
		'by_key'   => $by_key,
		'by_title' => $by_title,
	);
}

/**
 * Locate the Post Details group key currently active (export, DB, or none).
 *
 * @return string|null
 */
function hectv_cms_post_details_group_key() {
	$index = hectv_cms_existing_acf_groups_index();
	if ( isset( $index['by_key'][ HECTV_ACF_POST_DETAILS_KEY ] ) ) {
		return HECTV_ACF_POST_DETAILS_KEY;
	}
	if ( isset( $index['by_title']['Post Details']['key'] ) ) {
		return (string) $index['by_title']['Post Details']['key'];
	}
	return null;
}

/**
 * Whether a field name already exists on a group.
 *
 * @param string $group_key  ACF group key.
 * @param string $field_name Field name.
 * @return bool
 */
function hectv_cms_group_has_field( $group_key, $field_name ) {
	if ( ! function_exists( 'acf_get_fields' ) ) {
		return false;
	}

	foreach ( (array) acf_get_fields( $group_key ) as $field ) {
		if ( isset( $field['name'] ) && $field['name'] === $field_name ) {
			return true;
		}
	}

	return false;
}

/**
 * Register complete git-canonical groups and ensure Trending.
 *
 * ACF local groups with the same key as a database group are overlays, not
 * duplicates. Registering the entire group is essential: a partial local group
 * can shadow database children, while skipping a database-owned group lets PHP,
 * GraphQL, and the editor silently drift apart.
 */
function hectv_cms_register_acf_groups() {
	if ( ! function_exists( 'acf_add_local_field' ) || ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$export = hectv_cms_load_acf_export();
	$index  = hectv_cms_existing_acf_groups_index();
	$registered_post_details_key = null;

	foreach ( $export as $group ) {
		$key   = isset( $group['key'] ) ? (string) $group['key'] : '';
		$title = isset( $group['title'] ) ? (string) $group['title'] : '';
		if ( $key === '' || $title === '' ) {
			continue;
		}

		// Reuse an existing same-title group key to avoid a duplicate metabox when
		// an older database import used a different group key. Field keys from the
		// export remain unchanged, preserving all existing ACF reference metadata.
		if ( ! isset( $index['by_key'][ $key ] ) && isset( $index['by_title'][ $title ]['key'] ) ) {
			$group['key'] = (string) $index['by_title'][ $title ]['key'];
		}

		$local = hectv_cms_normalize_local_group( $group );
		acf_add_local_field_group( $local );

		// Track the authoritative overlay so later attaches see it.
		$index['by_key'][ (string) $local['key'] ] = $local;
		$index['by_title'][ $title ] = $local;
		if ( $title === 'Post Details' ) {
			$registered_post_details_key = (string) $local['key'];
		}

	}

	// Resolve the active Post Details key without re-querying ACF (local groups
	// just registered may not appear in acf_get_field_groups() until later).
	$parent = $registered_post_details_key;
	if ( ! $parent && isset( $index['by_key'][ HECTV_ACF_POST_DETAILS_KEY ] ) ) {
		$parent = HECTV_ACF_POST_DETAILS_KEY;
	}
	if ( ! $parent && isset( $index['by_title']['Post Details']['key'] ) ) {
		$parent = (string) $index['by_title']['Post Details']['key'];
	}

	if ( ! $parent ) {
		// Export missing or unreadable — last-resort minimal group so Trending still works.
		$parent = 'group_hectv_post_controls';
		acf_add_local_field_group(
			array(
				'key'                   => $parent,
				'title'                 => 'HEC Post Controls',
				'fields'                => array(),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'post',
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'active'                => true,
				'show_in_rest'          => 0,
			)
		);
	}

	// Full exported Post Details already nests is_trending. Only the last-resort
	// fallback needs a separately attached field.
	if ( $registered_post_details_key === null ) {
		if ( ! hectv_cms_group_has_field( $parent, HECTV_META_IS_TRENDING ) ) {
			acf_add_local_field( hectv_cms_is_trending_field( $parent ) );
		}
	}
}

add_action( 'acf/init', 'hectv_cms_register_acf_groups' );

/**
 * Register core post meta for REST/admin tools that do not go through ACF.
 *
 * Covers legacy Post Details scalars plus the git-owned Trending flag so meta
 * remains readable even when ACF is offline.
 */
add_action(
	'init',
	static function () {
		$bool_args = array(
			'type'              => 'boolean',
			'single'            => true,
			'show_in_rest'      => true,
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
			'sanitize_callback' => static function ( $value ) {
				return (bool) $value;
			},
		);

		$string_args = array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => static function () {
				return current_user_can( 'edit_posts' );
			},
		);

		$number_args = array(
			'type'          => 'number',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => static function () {
				return current_user_can( 'edit_posts' );
			},
		);

		// Boolean flags.
		register_post_meta( 'post', HECTV_META_IS_TRENDING, $bool_args );
		register_post_meta( 'post', HECTV_META_IS_VIDEO, $bool_args );
		register_post_meta( 'post', HECTV_META_SHOW_PODCASTS, $bool_args );
		register_post_meta( 'post', HECTV_META_HIDE_PAGE_THUMBNAIL, $bool_args );

		// Text / URL media identifiers.
		register_post_meta( 'post', HECTV_META_YOUTUBE_ID, $string_args );
		register_post_meta( 'post', HECTV_META_VIMEO_ID, $string_args );
		register_post_meta( 'post', HECTV_META_EMBED_URL, $string_args );
		register_post_meta( 'post', 'broadcast_location', $string_args );
		register_post_meta( 'post', 'internal_id', $string_args );
		register_post_meta( 'post', 'duration', $string_args );

		// Image attachment IDs (ACF image fields store attachment IDs by default
		// when return_format is array at read time; raw meta is typically the ID).
		register_post_meta( 'post', HECTV_META_POST_HEADER, $number_args );
		register_post_meta( 'post', HECTV_META_POST_HERO, $number_args );
		register_post_meta( 'post', HECTV_META_VIDEO_IMAGE, $number_args );

		// Poll interval / flag (export type is number).
		register_post_meta( 'post', HECTV_META_POLL_FOR_UPDATES, $number_args );
	}
);
