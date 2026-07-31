<?php
/**
 * WPGraphQL exposure for CMS fields.
 *
 * - Post.postDetails.isTrending (extends existing HecPostDetails when present)
 * - RootQuery.trendingSettings { maxVideos }
 * - RootQuery.forEducators { image, url, label }
 * - RootQuery.trendingPosts (posts with is_trending, limited by maxVideos)
 * - RootQuery.topbarCtas (menu header_actions; does not clobber staging options)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'graphql_register_types',
	static function () {
		if ( ! function_exists( 'register_graphql_field' ) ) {
			return;
		}

		// --- Site settings types -------------------------------------------

		register_graphql_object_type(
			'HectvTrendingSettings',
			array(
				'description' => 'Site-wide Trending Now settings.',
				'fields'      => array(
					'maxVideos' => array(
						'type'        => 'Int',
						'description' => 'Maximum number of trending videos to show in the rail.',
					),
				),
			)
		);

		register_graphql_object_type(
			'HectvForEducators',
			array(
				'description' => 'Site-wide For Educators logo card.',
				'fields'      => array(
					'label' => array( 'type' => 'String' ),
					'url'   => array(
						'type'        => 'String',
						'description' => 'Destination link (source/href for the card).',
					),
					'image' => array(
						'type'        => 'MediaItem',
						'description' => 'Logo image from the media library.',
					),
				),
			)
		);

		// topbar CTA type may already exist (staging content controls).
		if ( ! function_exists( 'register_graphql_object_type' ) ) {
			return;
		}
		// register_graphql_object_type is not idempotent on all versions — guard
		// by only defining HectvTopbarCta when our staging plugin did not.
		// WPGraphQL does not expose a public "type exists" helper; re-registering
		// the same name is typically a no-op warning. Prefer a distinct alias
		// only if needed. We reuse HectvTopbarCta name for frontend contract.

		register_graphql_field(
			'RootQuery',
			'trendingSettings',
			array(
				'type'        => 'HectvTrendingSettings',
				'description' => 'Site-wide Trending Now configuration.',
				'resolve'     => static function () {
					return array(
						'maxVideos' => hectv_cms_get_trending_max_videos(),
					);
				},
			)
		);

		register_graphql_field(
			'RootQuery',
			'forEducators',
			array(
				'type'        => 'HectvForEducators',
				'description' => 'For Educators logo image + link for the home rail.',
				'resolve'     => static function () {
					$edu     = hectv_cms_get_educators_settings();
					$image   = null;
					$logo_id = $edu['logo_id'];
					if ( $logo_id && class_exists( '\\WPGraphQL\\Model\\Post' ) ) {
						$attachment = get_post( $logo_id );
						if ( $attachment && $attachment->post_type === 'attachment' ) {
							$image = new \WPGraphQL\Model\Post( $attachment );
						}
					}
					return array(
						'label' => $edu['label'],
						'url'   => $edu['url'],
						'image' => $image,
					);
				},
			)
		);

		// topbarCtas: only register if staging plugin did not already.
		// Staging plugin gates on HECTV_ENVIRONMENT=staging; when absent we own it.
		// When both load, staging registered first — skip duplicate.
		add_filter(
			'graphql_RootQuery_fields',
			static function ( $fields ) {
				if ( isset( $fields['topbarCtas'] ) ) {
					// Wrap existing resolver: if staging returns empty, use menu.
					$original = $fields['topbarCtas']['resolve'] ?? null;
					$fields['topbarCtas']['resolve'] = static function ( $root, $args, $context, $info ) use ( $original ) {
						$rows = array();
						if ( is_callable( $original ) ) {
							$rows = $original( $root, $args, $context, $info );
						}
						if ( is_array( $rows ) && count( $rows ) > 0 ) {
							return $rows;
						}
						return hectv_cms_get_header_action_items();
					};
					return $fields;
				}

				$fields['topbarCtas'] = array(
					'type'        => array( 'list_of' => 'HectvTopbarCta' ),
					'description' => 'Header action links (Support / Subscribe) from the header_actions menu.',
					'resolve'     => static function () {
						return hectv_cms_get_header_action_items();
					},
				);

				// Ensure type exists when staging plugin is off.
				return $fields;
			},
			20
		);

		// Ensure HectvTopbarCta type when staging plugin is not loaded.
		if ( getenv( 'HECTV_ENVIRONMENT' ) !== 'staging' ) {
			register_graphql_object_type(
				'HectvTopbarCta',
				array(
					'description' => 'A customizable action link displayed beside the social icons.',
					'fields'      => array(
						'label' => array( 'type' => 'String' ),
						'url'   => array( 'type' => 'String' ),
						'style' => array( 'type' => 'String' ),
					),
				)
			);

			register_graphql_field(
				'RootQuery',
				'topbarCtas',
				array(
					'type'        => array( 'list_of' => 'HectvTopbarCta' ),
					'description' => 'Header action links (Support / Subscribe) from the header_actions menu.',
					'resolve'     => static function () {
						return hectv_cms_get_header_action_items();
					},
				)
			);
		}

		// --- Extend postDetails with isTrending ----------------------------
		// Prefer field on Post.postDetails; also flat Post.isTrending for queries.

		register_graphql_field(
			'Post',
			'isTrending',
			array(
				'type'        => 'Boolean',
				'description' => 'Whether this post is featured in Trending Now (post meta is_trending).',
				'resolve'     => static function ( $post ) {
					$id = hectv_cms_graphql_post_id( $post );
					if ( ! $id ) {
						return false;
					}
					return (bool) get_post_meta( $id, HECTV_META_IS_TRENDING, true );
				},
			)
		);

		// If HecPostDetails exists (staging compat), add isTrending to it via filter.
		add_filter(
			'graphql_HecPostDetails_fields',
			static function ( $fields ) {
				$fields['isTrending'] = array(
					'type'        => 'Boolean',
					'description' => 'Include in Trending Now rail.',
					'resolve'     => static function ( $source ) {
						// Source is the array returned by postDetails resolve.
						if ( is_array( $source ) && array_key_exists( 'isTrending', $source ) ) {
							return (bool) $source['isTrending'];
						}
						return false;
					},
				);
				return $fields;
			}
		);

		// --- trendingPosts connection-like list ----------------------------

		register_graphql_field(
			'RootQuery',
			'trendingPosts',
			array(
				'type'        => array( 'list_of' => 'Post' ),
				'description' => 'Posts flagged is_trending, newest first, limited by trendingSettings.maxVideos.',
				'args'        => array(
					'first' => array(
						'type'        => 'Int',
						'description' => 'Optional override of site max (still capped at 50).',
					),
				),
				'resolve'     => static function ( $root, $args ) {
					$limit = isset( $args['first'] ) ? (int) $args['first'] : hectv_cms_get_trending_max_videos();
					if ( $limit < 1 ) {
						$limit = hectv_cms_get_trending_max_videos();
					}
					if ( $limit > 50 ) {
						$limit = 50;
					}

					$query = new WP_Query(
						array(
							'post_type'              => 'post',
							'post_status'            => 'publish',
							'posts_per_page'         => $limit,
							'orderby'                => 'date',
							'order'                  => 'DESC',
							'no_found_rows'          => true,
							'update_post_meta_cache' => true,
							'meta_query'             => array(
								array(
									'key'     => HECTV_META_IS_TRENDING,
									'value'   => array( '1', 'true', 1, true ),
									'compare' => 'IN',
								),
							),
						)
					);

					if ( empty( $query->posts ) || ! class_exists( '\\WPGraphQL\\Model\\Post' ) ) {
						return array();
					}

					$out = array();
					foreach ( $query->posts as $p ) {
						$out[] = new \WPGraphQL\Model\Post( $p );
					}
					return $out;
				},
			)
		);
	},
	20
);

/**
 * Resolve a post database id from a GraphQL model or array.
 *
 * @param mixed $post GraphQL source.
 * @return int
 */
function hectv_cms_graphql_post_id( $post ) {
	if ( is_object( $post ) ) {
		if ( isset( $post->databaseId ) ) {
			return (int) $post->databaseId;
		}
		if ( isset( $post->ID ) ) {
			return (int) $post->ID;
		}
	}
	if ( is_array( $post ) && isset( $post['databaseId'] ) ) {
		return (int) $post['databaseId'];
	}
	return 0;
}
