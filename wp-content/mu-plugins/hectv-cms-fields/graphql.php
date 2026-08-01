<?php
/**
 * WPGraphQL exposure for CMS / ACF fields.
 *
 * Owns the frontend contract for Post.postDetails (HecPostDetails) so integrated
 * ACF field groups are queryable without the legacy wp-graphql-acf plugin:
 *
 * - Post.postDetails { isVideo, isTrending, youtubeId, vimeoId, embedUrl,
 *     postHeader, videoImage, showPodcasts, hidePageThumbnail, pollForUpdates,
 *     broadcastLocation, internalId, duration, relatedPosts, postEvents }
 * - Post.isTrending
 * - RootQuery.trendingSettings / forEducators / trendingPosts / topbarCtas
 *
 * When staging-compat already registered the same types/fields, we still ensure
 * a complete postDetails resolver (filter priority 50) so incomplete types cannot
 * strip legacy fields from the response.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

/**
 * Read post meta with optional default.
 *
 * Prefers ACF get_field() when available so image/repeater values are shaped
 * correctly; falls back to get_post_meta.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Field / meta key.
 * @param mixed  $default Default when empty.
 * @return mixed
 */
function hectv_cms_gql_meta( $post_id, $key, $default = null ) {
	$post_id = (int) $post_id;
	if ( ! $post_id ) {
		return $default;
	}

	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $key, $post_id );
		// ACF returns null/false/'' for empty; keep 0 and '0'.
		if ( $value !== null && $value !== false && $value !== '' ) {
			return $value;
		}
	}

	$value = get_post_meta( $post_id, $key, true );
	if ( $value === '' || $value === null ) {
		return $default;
	}
	return $value;
}

/**
 * Coerce truthy meta into bool (frontend treats many flags as Boolean).
 *
 * @param mixed $val Raw value.
 * @return bool
 */
function hectv_cms_gql_bool( $val ) {
	if ( is_bool( $val ) ) {
		return $val;
	}
	if ( $val === null || $val === '' ) {
		return false;
	}
	if ( is_numeric( $val ) ) {
		return (int) $val !== 0;
	}
	return in_array( strtolower( (string) $val ), array( '1', 'true', 'yes', 'on' ), true );
}

/**
 * Resolve MediaItem model from attachment id / ACF image array / URL.
 *
 * @param mixed $raw ACF image array, attachment ID, or empty.
 * @return \WPGraphQL\Model\Post|null
 */
function hectv_cms_gql_media_model( $raw ) {
	$attachment_id = 0;
	if ( is_array( $raw ) ) {
		if ( isset( $raw['ID'] ) ) {
			$attachment_id = (int) $raw['ID'];
		} elseif ( isset( $raw['id'] ) ) {
			$attachment_id = (int) $raw['id'];
		}
	} elseif ( is_numeric( $raw ) ) {
		$attachment_id = (int) $raw;
	}

	if ( ! $attachment_id ) {
		return null;
	}

	$attachment = get_post( $attachment_id );
	if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
		return null;
	}

	if ( class_exists( '\\WPGraphQL\\Model\\Post' ) ) {
		return new \WPGraphQL\Model\Post( $attachment );
	}

	return null;
}

/**
 * Resolve a published Post model by ID.
 *
 * @param int $id Post ID.
 * @return \WPGraphQL\Model\Post|null
 */
function hectv_cms_gql_post_model( $id ) {
	$id = (int) $id;
	if ( ! $id ) {
		return null;
	}
	$post = get_post( $id );
	if ( ! $post instanceof WP_Post || $post->post_status !== 'publish' ) {
		return null;
	}
	if ( class_exists( '\\WPGraphQL\\Model\\Post' ) ) {
		return new \WPGraphQL\Model\Post( $post );
	}
	return null;
}

/**
 * Normalize ACF repeater / relationship-ish meta into a list of post IDs.
 *
 * Handles:
 * - get_field() row arrays with sub-field keys
 * - plain list of IDs
 * - single ID
 * - classic ACF repeater count + {$key}_{$i}_{$sub} meta
 *
 * @param int    $post_id Post ID.
 * @param string $key     Repeater field name.
 * @param string $sub_key Sub-field name (e.g. related_post).
 * @return int[]
 */
function hectv_cms_gql_repeater_post_ids( $post_id, $key, $sub_key ) {
	$post_id = (int) $post_id;
	$ids     = array();

	$raw = hectv_cms_gql_meta( $post_id, $key, null );
	if ( is_array( $raw ) ) {
		foreach ( $raw as $row ) {
			if ( is_numeric( $row ) ) {
				$ids[] = (int) $row;
				continue;
			}
			if ( ! is_array( $row ) ) {
				continue;
			}
			// Sub-field may be ID, post object, or ACF post_object array.
			$candidate = null;
			if ( isset( $row[ $sub_key ] ) ) {
				$candidate = $row[ $sub_key ];
			} elseif ( isset( $row['ID'] ) ) {
				$candidate = $row['ID'];
			}
			if ( is_object( $candidate ) && isset( $candidate->ID ) ) {
				$ids[] = (int) $candidate->ID;
			} elseif ( is_array( $candidate ) && isset( $candidate['ID'] ) ) {
				$ids[] = (int) $candidate['ID'];
			} elseif ( is_numeric( $candidate ) ) {
				$ids[] = (int) $candidate;
			}
		}
	} elseif ( is_numeric( $raw ) && (int) $raw > 0 ) {
		// Either a single related ID or a repeater row count.
		$count_or_id = (int) $raw;
		// Prefer classic ACF indexed rows when present.
		$found_rows = false;
		for ( $i = 0; $i < max( $count_or_id, 1 ); $i++ ) {
			$sub = get_post_meta( $post_id, "{$key}_{$i}_{$sub_key}", true );
			if ( $sub === '' || $sub === null ) {
				break;
			}
			$found_rows = true;
			if ( is_numeric( $sub ) ) {
				$ids[] = (int) $sub;
			}
		}
		if ( ! $found_rows && $count_or_id > 0 ) {
			// Single ID stored under the parent key (staging seed pattern).
			$ids[] = $count_or_id;
		}
	} elseif ( is_string( $raw ) && $raw !== '' ) {
		foreach ( preg_split( '/[\s,]+/', $raw ) as $part ) {
			if ( is_numeric( $part ) ) {
				$ids[] = (int) $part;
			}
		}
	}

	// Deduplicate while preserving order.
	$out  = array();
	$seen = array();
	foreach ( $ids as $id ) {
		if ( $id > 0 && empty( $seen[ $id ] ) ) {
			$seen[ $id ] = true;
			$out[]       = $id;
		}
	}
	return $out;
}

/**
 * Build the full postDetails payload for a post.
 *
 * @param mixed $source GraphQL Post source.
 * @return array<string, mixed>|null
 */
function hectv_cms_resolve_post_details( $source ) {
	$id = hectv_cms_graphql_post_id( $source );
	if ( ! $id ) {
		return null;
	}

	$related_posts = array();
	foreach ( hectv_cms_gql_repeater_post_ids( $id, 'related_posts', 'related_post' ) as $rid ) {
		$model = hectv_cms_gql_post_model( $rid );
		if ( $model ) {
			$related_posts[] = array(
				'relatedPost' => $model,
				'post'        => $model,
			);
		}
	}

	$post_events = array();
	foreach ( hectv_cms_gql_repeater_post_ids( $id, 'post_events', 'related_event' ) as $eid ) {
		$model = hectv_cms_gql_post_model( $eid );
		if ( $model ) {
			$post_events[] = array(
				'relatedEvent' => $model,
			);
		}
	}

	return array(
		'videoImage'         => hectv_cms_gql_media_model( hectv_cms_gql_meta( $id, HECTV_META_VIDEO_IMAGE ) ),
		'postHeader'         => hectv_cms_gql_media_model( hectv_cms_gql_meta( $id, HECTV_META_POST_HEADER ) ),
		'isVideo'            => hectv_cms_gql_bool( hectv_cms_gql_meta( $id, HECTV_META_IS_VIDEO, '0' ) ),
		'isTrending'         => hectv_cms_gql_bool( hectv_cms_gql_meta( $id, HECTV_META_IS_TRENDING, '0' ) ),
		'youtubeId'          => hectv_cms_gql_meta( $id, HECTV_META_YOUTUBE_ID, null ),
		'vimeoId'            => hectv_cms_gql_meta( $id, HECTV_META_VIMEO_ID, null ),
		'embedUrl'           => hectv_cms_gql_meta( $id, HECTV_META_EMBED_URL, null ),
		'showPodcasts'       => hectv_cms_gql_bool( hectv_cms_gql_meta( $id, HECTV_META_SHOW_PODCASTS ) ),
		'hidePageThumbnail'  => hectv_cms_gql_bool( hectv_cms_gql_meta( $id, HECTV_META_HIDE_PAGE_THUMBNAIL ) ),
		'pollForUpdates'     => hectv_cms_gql_bool( hectv_cms_gql_meta( $id, HECTV_META_POLL_FOR_UPDATES ) ),
		'broadcastLocation'  => hectv_cms_gql_meta( $id, 'broadcast_location', null ),
		'internalId'         => hectv_cms_gql_meta( $id, 'internal_id', null ),
		'duration'           => hectv_cms_gql_meta( $id, 'duration', null ),
		'relatedPosts'       => $related_posts,
		'postEvents'         => $post_events,
	);
}

/**
 * Register object type if WPGraphQL is available. Duplicate names are ignored.
 *
 * @param string               $name   Type name.
 * @param array<string, mixed> $config Type config.
 */
function hectv_cms_register_object_type( $name, $config ) {
	if ( ! function_exists( 'register_graphql_object_type' ) ) {
		return;
	}
	// WPGraphQL TypeRegistry throws on true duplicates in some versions.
	try {
		register_graphql_object_type( $name, $config );
	} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		// Type already registered (e.g. staging-compat) — leave existing definition.
	}
}

add_action(
	'graphql_register_types',
	static function () {
		if ( ! function_exists( 'register_graphql_field' ) ) {
			return;
		}

		// --- Site settings types -------------------------------------------

		hectv_cms_register_object_type(
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

		hectv_cms_register_object_type(
			'HectvForEducatorsCard',
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
				'type'        => 'HectvForEducatorsCard',
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
		add_filter(
			'graphql_RootQuery_fields',
			static function ( $fields ) {
				if ( isset( $fields['topbarCtas'] ) ) {
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

				return $fields;
			},
			20
		);

		if ( getenv( 'HECTV_ENVIRONMENT' ) !== 'staging' ) {
			hectv_cms_register_object_type(
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

		// --- Post.postDetails (integrated ACF Post Details fields) ----------

		hectv_cms_register_object_type(
			'HecRelatedPostRow',
			array(
				'description' => 'Related post wrapper row (ACF repeater).',
				'fields'      => array(
					'relatedPost' => array( 'type' => 'Post' ),
					'post'        => array( 'type' => 'Post' ),
					'eventPost'   => array( 'type' => 'Post' ),
				),
			)
		);

		hectv_cms_register_object_type(
			'HecRelatedEventRow',
			array(
				'description' => 'Related event wrapper row (ACF repeater).',
				'fields'      => array(
					'relatedEvent' => array( 'type' => 'Event' ),
				),
			)
		);

		hectv_cms_register_object_type(
			'HecPostDetails',
			array(
				'description' => 'Post Details ACF group exposed for the headless frontend.',
				'fields'      => array(
					'videoImage'        => array(
						'type'        => 'MediaItem',
						'description' => 'Video thumbnail (ACF video_image).',
					),
					'postHeader'        => array(
						'type'        => 'MediaItem',
						'description' => 'Blog/header image (ACF post_header).',
					),
					'isVideo'           => array(
						'type'        => 'Boolean',
						'description' => 'Whether this post is a video (ACF is_video).',
					),
					'isTrending'        => array(
						'type'        => 'Boolean',
						'description' => 'Include in Trending Now rail (ACF is_trending).',
					),
					'youtubeId'         => array(
						'type'        => 'String',
						'description' => 'YouTube video id (ACF youtube_id).',
					),
					'vimeoId'           => array(
						'type'        => 'String',
						'description' => 'Vimeo video id (ACF vimeo_id).',
					),
					'embedUrl'          => array(
						'type'        => 'String',
						'description' => 'Embed URL (ACF embed_url).',
					),
					'showPodcasts'      => array(
						'type'        => 'Boolean',
						'description' => 'Show podcast links (ACF show_podcasts).',
					),
					'hidePageThumbnail' => array(
						'type'        => 'Boolean',
						'description' => 'Hide page thumbnail (ACF hide_page_thumbnail).',
					),
					'pollForUpdates'    => array(
						'type'        => 'Boolean',
						'description' => 'Poll for live updates (ACF poll_for_updates).',
					),
					'broadcastLocation' => array(
						'type'        => 'String',
						'description' => 'Broadcast file location (ACF broadcast_location).',
					),
					'internalId'        => array(
						'type'        => 'String',
						'description' => 'Internal media id (ACF internal_id).',
					),
					'duration'          => array(
						'type'        => 'String',
						'description' => 'Duration (ACF duration).',
					),
					'relatedPosts'      => array(
						'type'        => array( 'list_of' => 'HecRelatedPostRow' ),
						'description' => 'Related posts repeater (ACF related_posts).',
					),
					'postEvents'        => array(
						'type'        => array( 'list_of' => 'HecRelatedEventRow' ),
						'description' => 'Related events repeater (ACF post_events).',
					),
				),
			)
		);

		// Ensure every HecPostDetails field can resolve from our array payload
		// (and fall back to re-reading meta if an older resolver left keys out).
		add_filter(
			'graphql_HecPostDetails_fields',
			static function ( $fields ) {
				$ensure = array(
					'videoImage'        => 'MediaItem',
					'postHeader'        => 'MediaItem',
					'isVideo'           => 'Boolean',
					'isTrending'        => 'Boolean',
					'youtubeId'         => 'String',
					'vimeoId'           => 'String',
					'embedUrl'          => 'String',
					'showPodcasts'      => 'Boolean',
					'hidePageThumbnail' => 'Boolean',
					'pollForUpdates'    => 'Boolean',
					'broadcastLocation' => 'String',
					'internalId'        => 'String',
					'duration'          => 'String',
					'relatedPosts'      => array( 'list_of' => 'HecRelatedPostRow' ),
					'postEvents'        => array( 'list_of' => 'HecRelatedEventRow' ),
				);

				foreach ( $ensure as $name => $type ) {
					$fields[ $name ] = array(
						'type'        => $type,
						'description' => isset( $fields[ $name ]['description'] ) ? $fields[ $name ]['description'] : $name,
						'resolve'     => static function ( $source ) use ( $name ) {
							if ( is_array( $source ) && array_key_exists( $name, $source ) ) {
								return $source[ $name ];
							}
							// Last resort: re-resolve full payload when source is a Post model.
							$full = hectv_cms_resolve_post_details( $source );
							return is_array( $full ) && array_key_exists( $name, $full ) ? $full[ $name ] : null;
						},
					);
				}

				return $fields;
			},
			50
		);

		// Authoritative Post.postDetails — wins over incomplete legacy bridges.
		$post_details_field = array(
			'type'        => 'HecPostDetails',
			'description' => 'Post Details ACF fields (git-canonical hectv-cms-fields).',
			'resolve'     => static function ( $source ) {
				return hectv_cms_resolve_post_details( $source );
			},
		);

		register_graphql_field( 'Post', 'postDetails', $post_details_field );

		add_filter(
			'graphql_Post_fields',
			static function ( $fields ) use ( $post_details_field ) {
				$fields['postDetails'] = $post_details_field;
				return $fields;
			},
			50
		);

		// Flat Post.isTrending for list queries.
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
					return hectv_cms_gql_bool( hectv_cms_gql_meta( $id, HECTV_META_IS_TRENDING, '0' ) );
				},
			)
		);

		// --- trendingPosts -------------------------------------------------

		register_graphql_field(
			'RootQuery',
			'trendingPosts',
			array(
				'type'        => array( 'list_of' => 'Post' ),
				'description' => 'Trending Now rail: is_trending posts first (newest), then backfill with most recent posts up to trendingSettings.maxVideos.',
				'args'        => array(
					'first' => array(
						'type'        => 'Int',
						'description' => 'Optional override of site max from Settings → HEC Site Settings (still capped at 50).',
					),
				),
				'resolve'     => static function ( $root, $args ) {
					$limit = isset( $args['first'] ) ? (int) $args['first'] : null;
					return hectv_cms_resolve_trending_posts( $limit );
				},
			)
		);
	},
	20
);

/**
 * Normalize the Trending Now list size from config (or an optional override).
 *
 * @param int|null $limit Optional override (GraphQL first:).
 * @return int Between 1 and 50.
 */
function hectv_cms_trending_limit( $limit = null ) {
	if ( $limit === null || (int) $limit < 1 ) {
		$limit = hectv_cms_get_trending_max_videos();
	}
	$limit = (int) $limit;
	if ( $limit < 1 ) {
		$limit = hectv_cms_trending_max_videos_default();
	}
	if ( $limit > 50 ) {
		$limit = 50;
	}
	return $limit;
}

/**
 * Query WP posts for the Trending Now rail.
 *
 * 1. Take up to $limit posts with is_trending truthy (newest first).
 * 2. If fewer than $limit, backfill with the most recent published posts
 *    not already selected, until the list is full.
 *
 * @param int|null $limit Optional size override; default site config maxVideos.
 * @return \WP_Post[]
 */
function hectv_cms_query_trending_posts( $limit = null ) {
	$limit = hectv_cms_trending_limit( $limit );
	$posts = array();
	$ids   = array();

	$base = array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'no_found_rows'          => true,
		'ignore_sticky_posts'    => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
	);

	// Prefer explicitly flagged trending posts.
	$trending_q = new WP_Query(
		array_merge(
			$base,
			array(
				'posts_per_page' => $limit,
				'meta_query'     => array(
					array(
						'key'     => HECTV_META_IS_TRENDING,
						'value'   => array( '1', 'true', 1, true ),
						'compare' => 'IN',
					),
				),
			)
		)
	);

	if ( ! empty( $trending_q->posts ) ) {
		foreach ( $trending_q->posts as $p ) {
			if ( ! is_object( $p ) || ! isset( $p->ID ) ) {
				continue;
			}
			$posts[] = $p;
			$ids[]   = (int) $p->ID;
		}
	}

	$need = $limit - count( $posts );
	if ( $need > 0 ) {
		// Backfill with most recent posts not already in the trending set.
		$fill_args = array_merge(
			$base,
			array(
				'posts_per_page' => $need,
			)
		);
		if ( ! empty( $ids ) ) {
			$fill_args['post__not_in'] = $ids;
		}

		$fill_q = new WP_Query( $fill_args );
		if ( ! empty( $fill_q->posts ) ) {
			foreach ( $fill_q->posts as $p ) {
				if ( ! is_object( $p ) || ! isset( $p->ID ) ) {
					continue;
				}
				$posts[] = $p;
				if ( count( $posts ) >= $limit ) {
					break;
				}
			}
		}
	}

	return $posts;
}

/**
 * GraphQL resolver for RootQuery.trendingPosts.
 *
 * @param int|null $limit Optional size override.
 * @return array<\WPGraphQL\Model\Post>
 */
function hectv_cms_resolve_trending_posts( $limit = null ) {
	if ( ! class_exists( '\\WPGraphQL\\Model\\Post' ) ) {
		return array();
	}

	$posts = hectv_cms_query_trending_posts( $limit );
	if ( empty( $posts ) ) {
		return array();
	}

	$out = array();
	foreach ( $posts as $p ) {
		$out[] = new \WPGraphQL\Model\Post( $p );
	}
	return $out;
}
