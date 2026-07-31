<?php
/**
 * ACF Local JSON + PHP field group registration for Post Details.
 *
 * Field groups are defined in git (this file + acf-json/). They load even when
 * the ACF admin UI has never been opened. ACF free 5.x is sufficient.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Point ACF Local JSON at this package so field groups stay in git.
 */
add_filter(
	'acf/settings/save_json',
	static function ( $path ) {
		return HECTV_CMS_FIELDS_DIR . '/acf-json';
	}
);

add_filter(
	'acf/settings/load_json',
	static function ( $paths ) {
		// Prefer our package first; keep other paths as fallbacks.
		array_unshift( $paths, HECTV_CMS_FIELDS_DIR . '/acf-json' );
		return $paths;
	}
);

/**
 * Register Post Details field group in PHP (works without opening ACF UI).
 * Keys match production meta keys already consumed by hectv REST + staging GraphQL.
 */
add_action(
	'acf/init',
	static function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'                   => 'group_hectv_post_details',
				'title'                 => 'Post Details',
				'fields'                => array(
					array(
						'key'           => 'field_hectv_is_video',
						'label'         => 'Is Video',
						'name'          => 'is_video',
						'type'          => 'true_false',
						'instructions'  => 'Mark this post as a video item.',
						'ui'            => 1,
						'default_value' => 0,
					),
					array(
						'key'           => 'field_hectv_is_trending',
						'label'         => 'Trending',
						'name'          => 'is_trending',
						'type'          => 'true_false',
						'instructions'  => 'Include this post in the Trending Now rail when it is a video (or any post the frontend maps).',
						'ui'            => 1,
						'default_value' => 0,
					),
					array(
						'key'          => 'field_hectv_youtube_id',
						'label'        => 'YouTube ID',
						'name'         => 'youtube_id',
						'type'         => 'text',
						'instructions' => 'YouTube video id (not the full URL).',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_hectv_is_video',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'   => 'field_hectv_vimeo_id',
						'label' => 'Vimeo ID',
						'name'  => 'vimeo_id',
						'type'  => 'text',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_hectv_is_video',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'   => 'field_hectv_embed_url',
						'label' => 'Embed URL',
						'name'  => 'embed_url',
						'type'  => 'url',
					),
					array(
						'key'           => 'field_hectv_post_header',
						'label'         => 'Post Header',
						'name'          => 'post_header',
						'type'          => 'image',
						'return_format' => 'array',
						'preview_size'  => 'medium',
						'library'       => 'all',
					),
					array(
						'key'           => 'field_hectv_video_image',
						'label'         => 'Video Image',
						'name'          => 'video_image',
						'type'          => 'image',
						'return_format' => 'array',
						'preview_size'  => 'medium',
						'library'       => 'all',
					),
					array(
						'key'           => 'field_hectv_show_podcasts',
						'label'         => 'Show Podcasts',
						'name'          => 'show_podcasts',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
					),
					array(
						'key'           => 'field_hectv_hide_page_thumbnail',
						'label'         => 'Hide Page Thumbnail',
						'name'          => 'hide_page_thumbnail',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
					),
					array(
						'key'           => 'field_hectv_poll_for_updates',
						'label'         => 'Poll For Updates',
						'name'          => 'poll_for_updates',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
					),
				),
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
