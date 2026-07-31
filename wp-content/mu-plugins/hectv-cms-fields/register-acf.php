<?php
/**
 * ACF registration for the git-owned Trending field.
 *
 * Production already owns a database-backed "Post Details" group with legacy
 * field keys. Re-registering that group under new keys creates duplicate admin
 * panels, so this package adds only its new field to the existing group. A
 * standalone fallback group is used only when Post Details is not installed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locate the existing production Post Details field group.
 *
 * @return string|null
 */
function hectv_cms_post_details_group_key() {
	if ( ! function_exists( 'acf_get_field_groups' ) ) {
		return null;
	}

	foreach ( acf_get_field_groups( array( 'post_type' => 'post' ) ) as $group ) {
		if ( isset( $group['title'], $group['key'] ) && $group['title'] === 'Post Details' ) {
			return (string) $group['key'];
		}
	}

	return null;
}

/**
 * Check whether a field name is already present in a group.
 *
 * @param string $group_key ACF field group key.
 * @param string $field_name ACF field name.
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

add_action(
	'acf/init',
	static function () {
		if ( ! function_exists( 'acf_add_local_field' ) || ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$parent = hectv_cms_post_details_group_key();
		if ( ! $parent ) {
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

		if ( hectv_cms_group_has_field( $parent, HECTV_META_IS_TRENDING ) ) {
			return;
		}

		acf_add_local_field(
			array(
				'key'           => 'field_hectv_is_trending',
				'parent'        => $parent,
				'label'         => 'Trending',
				'name'          => 'is_trending',
				'type'          => 'true_false',
				'instructions'  => 'Include this post in the Trending Now rail.',
				'ui'            => 1,
				'default_value' => 0,
			)
		);
	}
);

/**
 * Also register core post meta for REST/admin tools that do not go through ACF.
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

		register_post_meta( 'post', HECTV_META_IS_TRENDING, $bool_args );
		register_post_meta( 'post', HECTV_META_IS_VIDEO, $bool_args );

		register_post_meta(
			'post',
			HECTV_META_YOUTUBE_ID,
			array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
				'auth_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
);
