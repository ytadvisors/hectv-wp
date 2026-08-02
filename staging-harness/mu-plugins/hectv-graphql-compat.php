<?php
/**
 * Plugin Name: HEC TV GraphQL Compatibility (staging)
 * Description: STAGING/LOCAL ONLY. Newly authored HEC-owned WPGraphQL field and type
 *              registrations that reproduce the frontend contract from
 *              ytadvisors/hecmedia lib/graphql.js against modern upstream WPGraphQL.
 *              Deterministic fixture resolvers only — no production data, no licensed
 *              ACF bridge, no redistributed WPGraphQL 0.4.0 fork. Never deploy to
 *              production without a separate explicit approval.
 * Version: 0.1.0
 * Author: YT Advisors (owned compatibility code)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add modern WPGraphQL metadata while the imported HEC types are registered.
 *
 * WPGraphQL may cache its allowed post types before a later init callback can
 * mutate the global post-type objects. Filtering the registration arguments
 * makes the GraphQL contract part of the objects from their creation.
 *
 * Event content remains retired from GraphQL connection results (frontend no
 * longer queries events). Magazines stay queryable — the hecmedia Magazines
 * page and detail routes still load via `magazines` / `magazineBy`.
 */
add_filter(
	'register_post_type_args',
	static function ( $args, $post_type ) {
		$graphql_names = array(
			'magazine' => array( 'Magazine', 'magazines' ),
			'event'    => array( 'Event', 'events' ),
			'schedule' => array( 'Schedule', 'schedules' ),
			'video'    => array( 'Video', 'videos' ),
		);

		if ( ! isset( $graphql_names[ $post_type ] ) ) {
			return $args;
		}

		$args['show_in_graphql']     = true;
		$args['graphql_single_name'] = $graphql_names[ $post_type ][0];
		$args['graphql_plural_name'] = $graphql_names[ $post_type ][1];
		$args['graphql_register_root_field']      = true;
		$args['graphql_register_root_connection'] = true;
		return $args;
	},
	0,
	2
);

add_filter(
	'register_taxonomy_args',
	static function ( $args, $taxonomy ) {
		if ( $taxonomy !== 'event_category' ) {
			return $args;
		}

		$args['show_in_graphql']     = true;
		$args['graphql_single_name'] = 'EventCategory';
		$args['graphql_plural_name'] = 'eventCategories';
		$args['graphql_register_root_field']      = true;
		$args['graphql_register_root_connection'] = true;
		return $args;
	},
	0,
	2
);

/**
 * Register HEC custom post types with modern WPGraphQL exposure.
 * CPT slugs match production HEC code (magazine, event, schedule, video).
 * GraphQL names match the frontend contract (magazines, events, schedules, videos).
 */
add_action(
	'init',
	static function () {
		$cpts = array(
			'magazine' => array(
				'label'               => 'Magazines',
				'graphql_single_name' => 'Magazine',
				'graphql_plural_name' => 'magazines',
			),
			'event'    => array(
				'label'               => 'Events',
				'graphql_single_name' => 'Event',
				'graphql_plural_name' => 'events',
			),
			'schedule' => array(
				'label'               => 'Schedules',
				'graphql_single_name' => 'Schedule',
				'graphql_plural_name' => 'schedules',
			),
			'video'    => array(
				'label'               => 'Videos',
				'graphql_single_name' => 'Video',
				'graphql_plural_name' => 'videos',
			),
		);

		foreach ( $cpts as $slug => $cfg ) {
			if ( post_type_exists( $slug ) ) {
				// Ensure GraphQL exposure even if another loader registered the CPT first.
				global $wp_post_types;
				if ( isset( $wp_post_types[ $slug ] ) ) {
					$wp_post_types[ $slug ]->show_in_graphql     = true;
					$wp_post_types[ $slug ]->graphql_single_name = $cfg['graphql_single_name'];
					$wp_post_types[ $slug ]->graphql_plural_name = $cfg['graphql_plural_name'];
					$wp_post_types[ $slug ]->graphql_register_root_field      = true;
					$wp_post_types[ $slug ]->graphql_register_root_connection = true;
				}
				continue;
			}

			register_post_type(
				$slug,
				array(
					'label'               => $cfg['label'],
					'public'              => true,
					'publicly_queryable'  => true,
					'show_ui'             => true,
					'show_in_rest'        => true,
					'has_archive'         => true,
					'rewrite'             => array( 'slug' => $slug ),
					'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
					'show_in_graphql'     => true,
					'graphql_single_name' => $cfg['graphql_single_name'],
					'graphql_plural_name' => $cfg['graphql_plural_name'],
					'graphql_register_root_field'      => true,
					'graphql_register_root_connection' => true,
				)
			);
		}

		// Event categories taxonomy used by GET_EVENTS_CATEGORIES.
		if ( ! taxonomy_exists( 'event_category' ) ) {
			register_taxonomy(
				'event_category',
				array( 'event' ),
				array(
					'label'                 => 'Event Categories',
					'public'                => true,
					'hierarchical'          => true,
					'show_in_rest'          => true,
					'show_in_graphql'       => true,
					'graphql_single_name'   => 'EventCategory',
					'graphql_plural_name'   => 'eventCategories',
					'graphql_register_root_field'      => true,
					'graphql_register_root_connection' => true,
					'rewrite'               => array( 'slug' => 'event-category' ),
				)
			);
		} else {
			global $wp_taxonomies;
			if ( isset( $wp_taxonomies['event_category'] ) ) {
				$wp_taxonomies['event_category']->show_in_graphql     = true;
				$wp_taxonomies['event_category']->graphql_single_name = 'EventCategory';
				$wp_taxonomies['event_category']->graphql_plural_name = 'eventCategories';
				$wp_taxonomies['event_category']->graphql_register_root_field      = true;
				$wp_taxonomies['event_category']->graphql_register_root_connection = true;
			}
		}
	},
	100
);

/**
 * Block all GraphQL mutations in the isolated staging harness.
 * WPGraphQL serves /graphql via its own router (not only REST), so hook the
 * GraphQL request lifecycle rather than rest_pre_dispatch alone.
 */
add_action(
	'do_graphql_request',
	static function ( $query, $operation, $variables, $params ) {
		$haystack = is_string( $query ) ? $query : '';
		if ( is_string( $operation ) && strcasecmp( $operation, 'mutation' ) === 0 ) {
			$GLOBALS['hectv_gql_block_mutation'] = true;
			return;
		}
		if ( $haystack && preg_match( '/\bmutation\b/i', $haystack ) ) {
			$GLOBALS['hectv_gql_block_mutation'] = true;
		}
	},
	1,
	4
);

add_filter(
	'graphql_request_results',
	static function ( $response ) {
		if ( empty( $GLOBALS['hectv_gql_block_mutation'] ) ) {
			return $response;
		}
		$GLOBALS['hectv_gql_block_mutation'] = false;
		$blocked                             = array(
			'errors' => array(
				array(
					'message'    => 'Mutations are disabled in the HEC staging GraphQL harness.',
					'extensions' => array( 'code' => 'STAGING_MUTATIONS_DISABLED' ),
				),
			),
		);
		if ( is_array( $response ) ) {
			return $blocked;
		}
		// Some WPGraphQL versions return an object implementing ArrayAccess.
		if ( is_object( $response ) && method_exists( $response, 'toArray' ) ) {
			return $blocked;
		}
		return $blocked;
	},
	1
);

/**
 * Normalize a GraphQL source (WP_Post, Model\Post, array, int) to a database ID.
 *
 * @param mixed $source GraphQL resolve source.
 * @return int
 */
function hectv_gql_id( $source ) {
	if ( is_numeric( $source ) ) {
		return (int) $source;
	}
	if ( is_array( $source ) ) {
		if ( isset( $source['databaseId'] ) ) {
			return (int) $source['databaseId'];
		}
		if ( isset( $source['ID'] ) ) {
			return (int) $source['ID'];
		}
		return 0;
	}
	if ( is_object( $source ) ) {
		if ( isset( $source->databaseId ) ) {
			return (int) $source->databaseId;
		}
		if ( isset( $source->ID ) ) {
			return (int) $source->ID;
		}
	}
	return 0;
}

/**
 * Return a WPGraphQL Model\Post for a post ID, or null.
 *
 * @param int $id Post ID.
 * @return \WPGraphQL\Model\Post|null
 */
function hectv_gql_model_post( $id ) {
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
	return $post;
}

/**
 * Helper: read raw post meta with a sensible default.
 *
 * Intentionally does NOT call ACF `get_field()`. Existing resolvers (media,
 * repeaters stored as JSON strings, ID lists) expect raw meta shapes. Preferring
 * ACF-formatted values here would break image fields with return_format=array
 * when cast through hectv_gql_media(), and other structured meta consumers.
 *
 * @param mixed  $post Post source.
 * @param string $key  Meta key.
 * @param mixed  $default Default.
 * @return mixed
 */
function hectv_gql_meta( $post, $key, $default = null ) {
	$id = hectv_gql_id( $post );
	if ( ! $id ) {
		return $default;
	}
	$val = get_post_meta( $id, $key, true );
	if ( $val === '' || $val === null ) {
		return $default;
	}
	return $val;
}

/**
 * About/Contact only: prefer ACF-formatted field values when ACF is present.
 *
 * Live About/Contact pages store team / tv_providers as ACF repeaters (arrays),
 * not raw post_meta JSON strings. Scoped here so shared media/ID resolvers keep
 * raw get_post_meta() behavior via hectv_gql_meta().
 *
 * @param mixed  $post Post source.
 * @param string $key  Field / meta key.
 * @param mixed  $default Default.
 * @return mixed
 */
function hectv_gql_acf_field( $post, $key, $default = null ) {
	$id = hectv_gql_id( $post );
	if ( ! $id ) {
		return $default;
	}
	if ( function_exists( 'get_field' ) ) {
		$acf_val = get_field( $key, $id );
		if ( $acf_val !== null && $acf_val !== false && $acf_val !== '' ) {
			return $acf_val;
		}
	}
	return hectv_gql_meta( $post, $key, $default );
}

/**
 * Helper: coerce common truthy meta into bool|null.
 *
 * @param mixed $val Raw meta.
 * @return bool|null
 */
function hectv_gql_bool( $val ) {
	if ( $val === null || $val === '' ) {
		return null;
	}
	if ( is_bool( $val ) ) {
		return $val;
	}
	return in_array( (string) $val, array( '1', 'true', 'yes', 'on' ), true );
}

/**
 * Resolve a MediaItem by attachment ID, or null.
 *
 * @param mixed $id Attachment ID.
 * @return WP_Post|null
 */
function hectv_gql_media( $id ) {
	$id = (int) $id;
	if ( ! $id ) {
		return null;
	}
	$post = get_post( $id );
	return ( $post && $post->post_type === 'attachment' ) ? $post : null;
}

/**
 * Register GraphQL object types and fields required by the frontend contract.
 */
add_action(
	'graphql_register_types',
	static function () {
		// --- Shared leaf types -------------------------------------------------

		register_graphql_object_type(
			'HecFeedRowLayout',
			array(
				'description' => 'Owned staging type: feed row layout entry.',
				'fields'      => array(
					'rowLayout'   => array( 'type' => 'String' ),
					'displayType' => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_object_type(
			'HecFeedDesign',
			array(
				'description' => 'Owned staging type: page feed design (was ACF).',
				'fields'      => array(
					'newRowLayout'       => array( 'type' => array( 'list_of' => 'HecFeedRowLayout' ) ),
					'defaultDisplayType' => array( 'type' => 'String' ),
					'defaultRowLayout'   => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_object_type(
			'HecRelatedPostRow',
			array(
				'description' => 'Owned staging type: related post wrapper.',
				'fields'      => array(
					'relatedPost' => array( 'type' => 'Post' ),
					'post'        => array( 'type' => 'Post' ),
					'eventPost'   => array( 'type' => 'Post' ),
				),
			)
		);

		register_graphql_object_type(
			'HecRelatedEventRow',
			array(
				'description' => 'Owned staging type: related event wrapper.',
				'fields'      => array(
					'relatedEvent' => array( 'type' => 'Event' ),
				),
			)
		);

		register_graphql_object_type(
			'HecRequiredPostListRow',
			array(
				'description' => 'Owned staging type: requiredPosts.postList row.',
				'fields'      => array(
					'post' => array( 'type' => 'Post' ),
				),
			)
		);

		register_graphql_object_type(
			'HecRequiredPosts',
			array(
				'description' => 'Owned staging type: page requiredPosts group.',
				'fields'      => array(
					'postList' => array( 'type' => array( 'list_of' => 'HecRequiredPostListRow' ) ),
				),
			)
		);

		register_graphql_object_type(
			'HecEventDate',
			array(
				'description' => 'Owned staging type: event date row.',
				'fields'      => array(
					'startTime' => array( 'type' => 'String' ),
					'endTime'   => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_object_type(
			'HecEventDetails',
			array(
				'description' => 'Owned staging type: eventDetails group.',
				'fields'      => array(
					'eventDates'    => array( 'type' => array( 'list_of' => 'HecEventDate' ) ),
					'eventImage'    => array( 'type' => 'MediaItem' ),
					'venue'         => array( 'type' => 'String' ),
					'webAddress'    => array( 'type' => 'String' ),
					'eventPrice'    => array( 'type' => 'String' ),
					'externalImage' => array( 'type' => 'String' ),
					'eventPosts'    => array( 'type' => array( 'list_of' => 'HecRelatedPostRow' ) ),
				),
			)
		);

		register_graphql_object_type(
			'HecPostDetails',
			array(
				'description' => 'Owned staging type: postDetails group (was ACF).',
				'fields'      => array(
					'videoImage'        => array( 'type' => 'MediaItem' ),
					'postHeader'        => array( 'type' => 'MediaItem' ),
					'postHero'          => array( 'type' => 'MediaItem' ),
					'isVideo'           => array( 'type' => 'Boolean' ),
					'isTrending'        => array( 'type' => 'Boolean' ),
					'youtubeId'         => array( 'type' => 'String' ),
					'vimeoId'           => array( 'type' => 'String' ),
					'embedUrl'          => array( 'type' => 'String' ),
					'showPodcasts'      => array( 'type' => 'Boolean' ),
					'hidePageThumbnail' => array( 'type' => 'Boolean' ),
					// Interval seconds (ACF number / production Float). Not Boolean —
					// frontend uses pollInterval: pollForUpdates * 1000.
					'pollForUpdates'    => array( 'type' => 'Float' ),
					'relatedPosts'      => array( 'type' => array( 'list_of' => 'HecRelatedPostRow' ) ),
					'postEvents'        => array( 'type' => array( 'list_of' => 'HecRelatedEventRow' ) ),
				),
			)
		);

		register_graphql_object_type(
			'HecMagazineDetail',
			array(
				'description' => 'Owned staging type: magazineDetail group.',
				'fields'      => array(
					'coverImage'   => array( 'type' => 'MediaItem' ),
					'magazinePost' => array( 'type' => array( 'list_of' => 'HecRelatedPostRow' ) ),
				),
			)
		);

		register_graphql_object_type(
			'HecScheduleProgram',
			array(
				'description' => 'Owned staging type: schedule program row.',
				'fields'      => array(
					'programStartTime' => array( 'type' => 'String' ),
					'programEndTime'   => array( 'type' => 'String' ),
					'programTitle'     => array( 'type' => 'String' ),
					'programStartDate' => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_object_type(
			'HecScheduleDetails',
			array(
				'description' => 'Owned staging type: scheduleDetails group.',
				'fields'      => array(
					'schedulePrograms' => array( 'type' => array( 'list_of' => 'HecScheduleProgram' ) ),
				),
			)
		);

		register_graphql_object_type(
			'HecTemporaryLink',
			array(
				'description' => 'Owned staging type: live video temporaryLink group.',
				'fields'      => array(
					'url'              => array( 'type' => 'String' ),
					'endDate'          => array( 'type' => 'String' ),
					'displayDate'      => array( 'type' => 'String' ),
					'startDate'        => array( 'type' => 'String' ),
					'showTime'         => array( 'type' => 'String' ),
					'bannerTitle'      => array( 'type' => 'String' ),
					'bannerBackground' => array( 'type' => 'String' ),
					'bannerTextColor'  => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_object_type(
			'HecContact',
			array(
				'description' => 'Owned staging type: page contact group.',
				'fields'      => array(
					'address'       => array( 'type' => 'String' ),
					'directions'    => array( 'type' => 'String' ),
					'faxNumber'     => array( 'type' => 'String' ),
					'opportunities' => array( 'type' => 'String' ),
					'phoneNumber'   => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_object_type(
			'HecTvProvider',
			array(
				'description' => 'Owned staging type: TV provider row.',
				'fields'      => array(
					'provider' => array( 'type' => 'String' ),
					'channel'  => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_object_type(
			'HecTeamMember',
			array(
				'description' => 'Owned staging type: about.team row.',
				'fields'      => array(
					'email'    => array( 'type' => 'String' ),
					'name'     => array( 'type' => 'String' ),
					'position' => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_object_type(
			'HecAbout',
			array(
				'description' => 'Owned staging type: page about group.',
				'fields'      => array(
					'phoneNumber' => array( 'type' => 'String' ),
					'address'     => array( 'type' => 'String' ),
					'faxNumber'   => array( 'type' => 'String' ),
					'tvProviders' => array( 'type' => array( 'list_of' => 'HecTvProvider' ) ),
					'team'        => array( 'type' => array( 'list_of' => 'HecTeamMember' ) ),
					'videoId'     => array( 'type' => 'String' ),
				),
			)
		);

		// --- Field resolvers on core + CPT types -------------------------------

		$post_details_resolve = static function ( $source ) {
			$id = hectv_gql_id( $source );
			if ( ! $id ) {
				return null;
			}

			$related_ids = hectv_gql_meta( $id, 'related_posts', array() );
			if ( is_string( $related_ids ) ) {
				$related_ids = array_filter( array_map( 'intval', explode( ',', $related_ids ) ) );
			}
			if ( ! is_array( $related_ids ) ) {
				$related_ids = array();
			}
			$related_posts = array();
			foreach ( $related_ids as $rid ) {
				$model = hectv_gql_model_post( $rid );
				if ( $model ) {
					$related_posts[] = array( 'relatedPost' => $model, 'post' => $model );
				}
			}

			$event_ids = hectv_gql_meta( $id, 'post_events', array() );
			if ( is_string( $event_ids ) ) {
				$event_ids = array_filter( array_map( 'intval', explode( ',', $event_ids ) ) );
			}
			if ( ! is_array( $event_ids ) ) {
				$event_ids = array();
			}
			$post_events = array();
			foreach ( $event_ids as $eid ) {
				$model = hectv_gql_model_post( $eid );
				if ( $model ) {
					$post_events[] = array( 'relatedEvent' => $model );
				}
			}

			// MediaItem fields need Model\Post of attachment when present.
			$video_img = hectv_gql_media( hectv_gql_meta( $id, 'video_image' ) );
			$post_hdr  = hectv_gql_media( hectv_gql_meta( $id, 'post_header' ) );
			$post_hero = hectv_gql_media( hectv_gql_meta( $id, 'post_hero' ) );
			if ( $video_img && class_exists( '\\WPGraphQL\\Model\\Post' ) ) {
				$video_img = new \WPGraphQL\Model\Post( $video_img );
			}
			if ( $post_hdr && class_exists( '\\WPGraphQL\\Model\\Post' ) ) {
				$post_hdr = new \WPGraphQL\Model\Post( $post_hdr );
			}
			if ( $post_hero && class_exists( '\\WPGraphQL\\Model\\Post' ) ) {
				$post_hero = new \WPGraphQL\Model\Post( $post_hero );
			}


			return array(
				'videoImage'        => $video_img,
				'postHeader'        => $post_hdr,
				'postHero'          => $post_hero,
				'isVideo'           => hectv_gql_bool( hectv_gql_meta( $id, 'is_video', '0' ) ),
				'isTrending'        => hectv_gql_bool( hectv_gql_meta( $id, 'is_trending', '0' ) ),
				'youtubeId'         => hectv_gql_meta( $id, 'youtube_id', null ),
				'vimeoId'           => hectv_gql_meta( $id, 'vimeo_id', null ),
				'embedUrl'          => hectv_gql_meta( $id, 'embed_url', null ),
				'showPodcasts'      => hectv_gql_bool( hectv_gql_meta( $id, 'show_podcasts' ) ),
				'hidePageThumbnail' => hectv_gql_bool( hectv_gql_meta( $id, 'hide_page_thumbnail' ) ),
				'pollForUpdates'    => ( static function ( $raw ) {
					if ( $raw === null || $raw === '' || $raw === false || is_bool( $raw ) || ! is_numeric( $raw ) ) {
						return null;
					}
					return (float) $raw;
				} )( hectv_gql_meta( $id, 'poll_for_updates', null ) ),
				'relatedPosts'      => $related_posts,
				'postEvents'        => $post_events,
			);
		};

		register_graphql_field(
			'Post',
			'postDetails',
			array(
				'type'        => 'HecPostDetails',
				'description' => 'Owned staging postDetails (frontend contract).',
				'resolve'     => $post_details_resolve,
			)
		);

		$feed_design_resolve = static function ( $source ) {
			$id = hectv_gql_id( $source );
			if ( ! $id ) {
				return array(
					'newRowLayout'       => array(),
					'defaultDisplayType' => 'grid',
					'defaultRowLayout'   => 'standard',
				);
			}
			$raw  = hectv_gql_meta( $id, 'feed_design_rows', '' );
			$rows = array();
			if ( is_string( $raw ) && $raw !== '' ) {
				$decoded = json_decode( $raw, true );
				if ( is_array( $decoded ) ) {
					foreach ( $decoded as $row ) {
						$rows[] = array(
							'rowLayout'   => isset( $row['rowLayout'] ) ? (string) $row['rowLayout'] : 'standard',
							'displayType' => isset( $row['displayType'] ) ? (string) $row['displayType'] : 'grid',
						);
					}
				}
			}
			return array(
				'newRowLayout'       => $rows,
				'defaultDisplayType' => (string) hectv_gql_meta( $id, 'default_display_type', 'grid' ),
				'defaultRowLayout'   => (string) hectv_gql_meta( $id, 'default_row_layout', 'standard' ),
			);
		};

		register_graphql_field(
			'Page',
			'feedDesign',
			array(
				'type'    => 'HecFeedDesign',
				'resolve' => $feed_design_resolve,
			)
		);

		register_graphql_field(
			'Page',
			'requiredPosts',
			array(
				'type'    => 'HecRequiredPosts',
				'resolve' => static function ( $source ) {
					$ids = hectv_gql_meta( $source, 'required_posts', array() );
					if ( is_string( $ids ) ) {
						$ids = array_filter( array_map( 'intval', explode( ',', $ids ) ) );
					}
					if ( ! is_array( $ids ) ) {
						$ids = array();
					}
					$list = array();
					foreach ( $ids as $pid ) {
						$model = hectv_gql_model_post( $pid );
						if ( $model ) {
							$list[] = array( 'post' => $model );
						}
					}
					// Fall back to latest posts so home layout is never empty in fixtures.
					if ( ! $list ) {
						$latest = get_posts(
							array(
								'post_type'      => 'post',
								'posts_per_page' => 3,
								'post_status'    => 'publish',
							)
						);
						foreach ( $latest as $p ) {
							$model = hectv_gql_model_post( $p->ID );
							if ( $model ) {
								$list[] = array( 'post' => $model );
							}
						}
					}
					return array( 'postList' => $list );
				},
			)
		);

		register_graphql_field(
			'Page',
			'pageTemplate',
			array(
				'type'    => 'String',
				'resolve' => static function ( $source ) {
					$id  = hectv_gql_id( $source );
					$tpl = $id ? get_page_template_slug( $id ) : '';
					return $tpl ? $tpl : null;
				},
			)
		);

		// About + Contact ACF field names (production export / REST `acf`):
		//   address, phone_number, fax_number, directions, opportunities,
		//   video_id, team[{name,position,email}], tv_providers[{provider,channel}]
		// NOT prefixed about_* / contact_* — those keys never exist on live pages
		// and left localhost:4000 /about-us and /contact-us empty against staging.
		register_graphql_field(
			'Page',
			'contact',
			array(
				'type'    => 'HecContact',
				'resolve' => static function ( $source ) {
					// Prefer live ACF names via hectv_gql_acf_field; fall back to
					// harness-prefixed seed keys. Do not use hectv_gql_meta for the
					// primary ACF keys — that helper stays raw-meta-only.
					$pick = static function ( $source, $primary, $legacy ) {
						$v = hectv_gql_acf_field( $source, $primary, null );
						return ( $v !== null && $v !== '' ) ? $v : hectv_gql_acf_field( $source, $legacy, null );
					};
					return array(
						'address'       => $pick( $source, 'address', 'contact_address' ),
						'directions'    => $pick( $source, 'directions', 'contact_directions' ),
						'faxNumber'     => $pick( $source, 'fax_number', 'contact_fax' ),
						'opportunities' => $pick( $source, 'opportunities', 'contact_opportunities' ),
						'phoneNumber'   => $pick( $source, 'phone_number', 'contact_phone' ),
					);
				},
			)
		);

		register_graphql_field(
			'Page',
			'about',
			array(
				'type'    => 'HecAbout',
				'resolve' => static function ( $source ) {
					$pick = static function ( $source, $primary, $legacy, $default = null ) {
						$v = hectv_gql_acf_field( $source, $primary, null );
						if ( $v !== null && $v !== '' && $v !== array() ) {
							return $v;
						}
						return hectv_gql_acf_field( $source, $legacy, $default );
					};
					$providers = $pick( $source, 'tv_providers', 'about_tv_providers', array() );
					$team      = $pick( $source, 'team', 'about_team', array() );
					// Accept ACF arrays (preferred) or legacy JSON strings.
					if ( is_string( $providers ) ) {
						$decoded = json_decode( $providers, true );
						$providers = is_array( $decoded ) ? $decoded : array();
					}
					if ( is_string( $team ) ) {
						$decoded = json_decode( $team, true );
						$team = is_array( $decoded ) ? $decoded : array();
					}
					if ( ! is_array( $providers ) ) {
						$providers = array();
					}
					if ( ! is_array( $team ) ) {
						$team = array();
					}
					// Normalize repeater rows to the GraphQL field names the frontend selects.
					$providers = array_values(
						array_map(
							static function ( $row ) {
								if ( ! is_array( $row ) ) {
									return array( 'provider' => null, 'channel' => null );
								}
								return array(
									'provider' => isset( $row['provider'] ) ? $row['provider'] : null,
									'channel'  => isset( $row['channel'] ) ? $row['channel'] : null,
								);
							},
							$providers
						)
					);
					$team = array_values(
						array_map(
							static function ( $row ) {
								if ( ! is_array( $row ) ) {
									return array( 'email' => null, 'name' => null, 'position' => null );
								}
								return array(
									'email'    => isset( $row['email'] ) ? $row['email'] : null,
									'name'     => isset( $row['name'] ) ? $row['name'] : null,
									'position' => isset( $row['position'] ) ? $row['position'] : null,
								);
							},
							$team
						)
					);
					return array(
						'phoneNumber' => $pick( $source, 'phone_number', 'about_phone', null ),
						'address'     => $pick( $source, 'address', 'about_address', null ),
						'faxNumber'   => $pick( $source, 'fax_number', 'about_fax', null ),
						'tvProviders' => $providers,
						'team'        => $team,
						'videoId'     => $pick( $source, 'video_id', 'about_video_id', null ),
					);
				},
			)
		);

		// Magazine fields.
		register_graphql_field(
			'Magazine',
			'magazineDetail',
			array(
				'type'    => 'HecMagazineDetail',
				'resolve' => static function ( $source ) {
					$ids = hectv_gql_meta( $source, 'magazine_posts', array() );
					if ( is_string( $ids ) ) {
						$ids = array_filter( array_map( 'intval', explode( ',', $ids ) ) );
					}
					if ( ! is_array( $ids ) ) {
						$ids = array();
					}
					$rows = array();
					foreach ( $ids as $pid ) {
						$model = hectv_gql_model_post( $pid );
						if ( $model ) {
							$rows[] = array( 'post' => $model, 'relatedPost' => $model );
						}
					}
					$cover = hectv_gql_media( hectv_gql_meta( $source, 'cover_image' ) );
					if ( $cover && class_exists( '\\WPGraphQL\\Model\\Post' ) ) {
						$cover = new \WPGraphQL\Model\Post( $cover );
					}
					return array(
						'coverImage'   => $cover,
						'magazinePost' => $rows,
					);
				},
			)
		);

		// Event fields.
		register_graphql_field(
			'Event',
			'eventDetails',
			array(
				'type'    => 'HecEventDetails',
				'resolve' => static function ( $source ) {
					$dates_raw = hectv_gql_meta( $source, 'event_dates', '[]' );
					$dates     = json_decode( is_string( $dates_raw ) ? $dates_raw : '[]', true );
					if ( ! is_array( $dates ) || ! $dates ) {
						// Fallback from simple start/end meta for seed fixtures.
						$start = hectv_gql_meta( $source, 'event_dates_$_start_time', null );
						$end   = hectv_gql_meta( $source, 'event_dates_$_end_time', null );
						if ( $start || $end ) {
							$dates = array(
								array(
									'startTime' => $start,
									'endTime'   => $end,
								),
							);
						} else {
							$dates = array();
						}
					}

					$post_ids = hectv_gql_meta( $source, 'event_posts', array() );
					if ( is_string( $post_ids ) ) {
						$post_ids = array_filter( array_map( 'intval', explode( ',', $post_ids ) ) );
					}
					if ( ! is_array( $post_ids ) ) {
						$post_ids = array();
					}
					$event_posts = array();
					foreach ( $post_ids as $pid ) {
						$model = hectv_gql_model_post( $pid );
						if ( $model ) {
							$event_posts[] = array( 'eventPost' => $model, 'post' => $model, 'relatedPost' => $model );
						}
					}

					$event_image = hectv_gql_media( hectv_gql_meta( $source, 'event_image' ) );
					if ( $event_image && class_exists( '\\WPGraphQL\\Model\\Post' ) ) {
						$event_image = new \WPGraphQL\Model\Post( $event_image );
					}

					return array(
						'eventDates'    => $dates,
						'eventImage'    => $event_image,
						'venue'         => hectv_gql_meta( $source, 'venue', null ),
						'webAddress'    => hectv_gql_meta( $source, 'web_address', null ),
						'eventPrice'    => hectv_gql_meta( $source, 'event_price', null ),
						'externalImage' => hectv_gql_meta( $source, 'external_image', null ),
						'eventPosts'    => $event_posts,
					);
				},
			)
		);

		// Schedule fields.
		register_graphql_field(
			'Schedule',
			'scheduleDetails',
			array(
				'type'    => 'HecScheduleDetails',
				'resolve' => static function ( $source ) {
					$raw  = hectv_gql_meta( $source, 'schedule_programs', '[]' );
					$rows = json_decode( is_string( $raw ) ? $raw : '[]', true );
					if ( ! is_array( $rows ) ) {
						$rows = array();
					}
					return array( 'schedulePrograms' => $rows );
				},
			)
		);

		// Video temporary link.
		register_graphql_field(
			'Video',
			'temporaryLink',
			array(
				'type'    => 'HecTemporaryLink',
				'resolve' => static function ( $source ) {
					return array(
						'url'              => hectv_gql_meta( $source, 'temp_url', null ),
						'endDate'          => hectv_gql_meta( $source, 'end_date', null ),
						'displayDate'      => hectv_gql_meta( $source, 'display_date', null ),
						'startDate'        => hectv_gql_meta( $source, 'start_date', null ),
						'showTime'         => hectv_gql_meta( $source, 'show_time', null ),
						'bannerTitle'      => hectv_gql_meta( $source, 'banner_title', null ),
						'bannerBackground' => hectv_gql_meta( $source, 'banner_background', null ),
						'bannerTextColor'  => hectv_gql_meta( $source, 'banner_text_color', null ),
					);
				},
			)
		);

		// Category connection arg used by production queries (no-op; always flat).
		register_graphql_field(
			'RootQueryToCategoryConnectionWhereArgs',
			'shouldOutputInFlatList',
			array(
				'type'        => 'Boolean',
				'description' => 'Compatibility arg from production schema; ignored in staging (always flat).',
			)
		);
		register_graphql_field(
			'CategoryToCategoryConnectionWhereArgs',
			'shouldOutputInFlatList',
			array(
				'type' => 'Boolean',
			)
		);
		// EventCategory connection where args (if type exists).
		if ( function_exists( 'register_graphql_field' ) ) {
			// Attempt registration; silent if connection where type name differs by version.
			try {
				register_graphql_field(
					'RootQueryToEventCategoryConnectionWhereArgs',
					'shouldOutputInFlatList',
					array( 'type' => 'Boolean' )
				);
			} catch ( Exception $e ) { // phpcs:ignore
				// no-op
			}
		}

		// --- metaQuery / taxQuery compatibility on posts -----------------------
		// Frontend queries use unquoted GraphQL enums (CATEGORY, EQUAL_TO, SLUG, IN).
		// Register owned enums + inputs so production query documents validate.

		register_graphql_enum_type(
			'HecMetaCompareEnum',
			array(
				'values' => array(
					'EQUAL_TO'                 => array( 'value' => 'EQUAL_TO' ),
					'NOT_EQUAL_TO'             => array( 'value' => 'NOT_EQUAL_TO' ),
					'GREATER_THAN'             => array( 'value' => 'GREATER_THAN' ),
					'GREATER_THAN_OR_EQUAL_TO' => array( 'value' => 'GREATER_THAN_OR_EQUAL_TO' ),
					'LESS_THAN'                => array( 'value' => 'LESS_THAN' ),
					'LESS_THAN_OR_EQUAL_TO'    => array( 'value' => 'LESS_THAN_OR_EQUAL_TO' ),
					'LIKE'                     => array( 'value' => 'LIKE' ),
					'NOT_LIKE'                 => array( 'value' => 'NOT_LIKE' ),
					'IN'                       => array( 'value' => 'IN' ),
					'NOT_IN'                   => array( 'value' => 'NOT_IN' ),
					'BETWEEN'                  => array( 'value' => 'BETWEEN' ),
					'NOT_BETWEEN'              => array( 'value' => 'NOT_BETWEEN' ),
					'EXISTS'                   => array( 'value' => 'EXISTS' ),
					'NOT_EXISTS'               => array( 'value' => 'NOT_EXISTS' ),
				),
			)
		);

		register_graphql_enum_type(
			'HecMetaRelationEnum',
			array(
				'values' => array(
					'AND' => array( 'value' => 'AND' ),
					'OR'  => array( 'value' => 'OR' ),
				),
			)
		);

		register_graphql_enum_type(
			'HecTaxonomyEnum',
			array(
				'values' => array(
					'CATEGORY'        => array( 'value' => 'CATEGORY' ),
					'TAG'             => array( 'value' => 'TAG' ),
					'EVENTCATEGORY'   => array( 'value' => 'EVENTCATEGORY' ),
					'EVENT_CATEGORY'  => array( 'value' => 'EVENT_CATEGORY' ),
				),
			)
		);

		register_graphql_enum_type(
			'HecTaxFieldEnum',
			array(
				'values' => array(
					'ID'          => array( 'value' => 'ID' ),
					'SLUG'        => array( 'value' => 'SLUG' ),
					'NAME'        => array( 'value' => 'NAME' ),
					'TERM_ID'     => array( 'value' => 'TERM_ID' ),
					'TERM_TAXONOMY_ID' => array( 'value' => 'TERM_TAXONOMY_ID' ),
				),
			)
		);

		register_graphql_enum_type(
			'HecTaxOperatorEnum',
			array(
				'values' => array(
					'IN'         => array( 'value' => 'IN' ),
					'NOT_IN'     => array( 'value' => 'NOT_IN' ),
					'AND'        => array( 'value' => 'AND' ),
					'EXISTS'     => array( 'value' => 'EXISTS' ),
					'NOT_EXISTS' => array( 'value' => 'NOT_EXISTS' ),
				),
			)
		);

		register_graphql_input_type(
			'HecMetaArrayInput',
			array(
				'fields' => array(
					'key'     => array( 'type' => 'String' ),
					'value'   => array( 'type' => 'String' ),
					'compare' => array( 'type' => 'HecMetaCompareEnum' ),
				),
			)
		);

		register_graphql_input_type(
			'HecMetaQueryInput',
			array(
				'fields' => array(
					'relation'  => array( 'type' => 'HecMetaRelationEnum' ),
					'metaArray' => array( 'type' => array( 'list_of' => 'HecMetaArrayInput' ) ),
				),
			)
		);

		register_graphql_input_type(
			'HecTaxArrayInput',
			array(
				'fields' => array(
					'taxonomy'        => array( 'type' => 'HecTaxonomyEnum' ),
					'terms'           => array( 'type' => array( 'list_of' => 'String' ) ),
					'operator'        => array( 'type' => 'HecTaxOperatorEnum' ),
					'field'           => array( 'type' => 'HecTaxFieldEnum' ),
					'includeChildren' => array( 'type' => 'Boolean' ),
				),
			)
		);

		register_graphql_input_type(
			'HecTaxQueryInput',
			array(
				'fields' => array(
					'relation' => array( 'type' => 'HecMetaRelationEnum' ),
					// Accept single object or list (frontend often sends a single object).
					'taxArray' => array( 'type' => 'HecTaxArrayInput' ),
				),
			)
		);
	}
);

/**
 * Alias production metaQuery/taxQuery input names onto owned implementations
 * when upstream does not already provide them.
 */
add_filter(
	'graphql_input_fields',
	static function ( $fields, $type_name ) {
		$targets = array(
			'RootQueryToPostConnectionWhereArgs',
			'RootQueryToEventConnectionWhereArgs',
			'RootQueryToVideoConnectionWhereArgs',
			'RootQueryToMagazineConnectionWhereArgs',
		);
		if ( ! in_array( $type_name, $targets, true ) ) {
			return $fields;
		}
		// Always prefer owned production-compatible shapes (frontend enum names).
		$fields['metaQuery'] = array(
			'type'        => 'HecMetaQueryInput',
			'description' => 'Production-compatible metaQuery (owned staging registration).',
		);
		$fields['taxQuery'] = array(
			'type'        => 'HecTaxQueryInput',
			'description' => 'Production-compatible taxQuery (owned staging registration).',
		);
		return $fields;
	},
	100,
	2
);

/**
 * Apply owned metaQuery / taxQuery to WP_Query args.
 */
add_filter(
	'graphql_post_object_connection_query_args',
	static function ( $query_args, $source, $args, $context, $info ) {
		$post_types       = isset( $query_args['post_type'] ) ? (array) $query_args['post_type'] : array();
		$field_name       = is_object( $info ) && isset( $info->fieldName ) ? $info->fieldName : '';
		// Events only: magazines are live on the frontend again (list + detail).
		$retired_types    = array( 'event' );
		$retired_fields   = array( 'events' );

		// Keep retired roots queryable for schema compatibility, but never return content.
		if (
			array_intersect( $retired_types, $post_types ) ||
			in_array( $field_name, $retired_fields, true )
		) {
			$query_args['post__in'] = array( 0 );
			return $query_args;
		}

		$where = isset( $args['where'] ) && is_array( $args['where'] ) ? $args['where'] : array();

		// metaQuery (owned or upstream shape).
		$meta_query_in = null;
		if ( isset( $where['metaQuery'] ) ) {
			$meta_query_in = $where['metaQuery'];
		} elseif ( isset( $where['hecMetaQuery'] ) ) {
			$meta_query_in = $where['hecMetaQuery'];
		}

		if ( is_array( $meta_query_in ) ) {
			$relation = isset( $meta_query_in['relation'] ) ? strtoupper( (string) $meta_query_in['relation'] ) : 'AND';
			$clauses  = array( 'relation' => in_array( $relation, array( 'AND', 'OR' ), true ) ? $relation : 'AND' );
			$items    = array();
			if ( isset( $meta_query_in['metaArray'] ) ) {
				// Accept single object or list (frontend sometimes sends object).
				$raw_items = $meta_query_in['metaArray'];
				if ( isset( $raw_items['key'] ) ) {
					$raw_items = array( $raw_items );
				}
				if ( is_array( $raw_items ) ) {
					foreach ( $raw_items as $item ) {
						if ( ! is_array( $item ) || empty( $item['key'] ) ) {
							continue;
						}
						$compare = isset( $item['compare'] ) ? (string) $item['compare'] : '=';
						// Map GraphQL enum-ish names to WP meta_compare.
						$map = array(
							'EQUAL_TO'                 => '=',
							'NOT_EQUAL_TO'             => '!=',
							'GREATER_THAN'             => '>',
							'GREATER_THAN_OR_EQUAL_TO' => '>=',
							'LESS_THAN'                => '<',
							'LESS_THAN_OR_EQUAL_TO'    => '<=',
							'LIKE'                     => 'LIKE',
							'NOT_LIKE'                 => 'NOT LIKE',
							'IN'                       => 'IN',
							'NOT_IN'                   => 'NOT IN',
							'BETWEEN'                  => 'BETWEEN',
							'NOT_BETWEEN'              => 'NOT BETWEEN',
							'EXISTS'                   => 'EXISTS',
							'NOT_EXISTS'               => 'NOT EXISTS',
						);
						if ( isset( $map[ $compare ] ) ) {
							$compare = $map[ $compare ];
						}
						$clause = array(
							'key'     => (string) $item['key'],
							'compare' => $compare,
						);
						if ( array_key_exists( 'value', $item ) ) {
							$clause['value'] = $item['value'];
						}
						$items[] = $clause;
					}
				}
			}
			if ( $items ) {
				$query_args['meta_query'] = array_merge( array( 'relation' => $clauses['relation'] ), $items );
			}
		}

		// taxQuery.
		$tax_query_in = null;
		if ( isset( $where['taxQuery'] ) ) {
			$tax_query_in = $where['taxQuery'];
		} elseif ( isset( $where['hecTaxQuery'] ) ) {
			$tax_query_in = $where['hecTaxQuery'];
		}

		if ( is_array( $tax_query_in ) ) {
			$relation = isset( $tax_query_in['relation'] ) ? strtoupper( (string) $tax_query_in['relation'] ) : 'AND';
			$items    = array( 'relation' => in_array( $relation, array( 'AND', 'OR' ), true ) ? $relation : 'AND' );
			$raw      = isset( $tax_query_in['taxArray'] ) ? $tax_query_in['taxArray'] : array();
			if ( isset( $raw['taxonomy'] ) ) {
				$raw = array( $raw );
			}
			if ( is_array( $raw ) ) {
				foreach ( $raw as $item ) {
					if ( ! is_array( $item ) || empty( $item['taxonomy'] ) ) {
						continue;
					}
					$tax = strtolower( (string) $item['taxonomy'] );
					// Frontend uses enum CATEGORY → category.
					if ( $tax === 'category' || $tax === 'CATEGORY' ) {
						$tax = 'category';
					}
					$field = isset( $item['field'] ) ? strtolower( (string) $item['field'] ) : 'term_id';
					if ( $field === 'slug' || $field === 'SLUG' ) {
						$field = 'slug';
					}
					$operator = isset( $item['operator'] ) ? strtoupper( (string) $item['operator'] ) : 'IN';
					$terms    = isset( $item['terms'] ) ? (array) $item['terms'] : array();
					$items[]  = array(
						'taxonomy'         => $tax,
						'field'            => $field,
						'terms'            => $terms,
						'operator'         => $operator,
						'include_children' => ! empty( $item['includeChildren'] ),
					);
				}
			}
			if ( count( $items ) > 1 ) {
				$query_args['tax_query'] = $items;
			}
		}

		return $query_args;
	},
	20,
	5
);

/**
 * Keep the deprecated eventCategories root schema-compatible but empty.
 */
add_filter(
	'graphql_term_object_connection_query_args',
	static function ( $query_args, $source, $args, $context, $info ) {
		$taxonomies = isset( $query_args['taxonomy'] ) ? (array) $query_args['taxonomy'] : array();
		$field_name = is_object( $info ) && isset( $info->fieldName ) ? $info->fieldName : '';

		if ( in_array( 'event_category', $taxonomies, true ) || $field_name === 'eventCategories' ) {
			$query_args['include'] = array( 0 );
		}

		return $query_args;
	},
	20,
	5
);
