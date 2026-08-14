<?php
/**
 * Editor compatibility for pages that use legacy interactive ACF controls.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Production Home page ID (matches the Required Posts ACF location rule). */
define( 'HECTV_HOME_PAGE_ID', 31155 );

/**
 * Use WordPress's Classic Editor screen for the production Home page only.
 *
 * ACF 5.6.9 and the standalone Repeater 2.1.0 add-on predate the current
 * block-editor metabox bridge. The bridge can render the Required Posts rows
 * without initializing their text and post-object controls. Core's per-post
 * filter keeps those controls on the legacy editor screen while leaving every
 * other page, post, and custom post type on its configured editor.
 *
 * @param bool    $use_block_editor Whether WordPress would use the block editor.
 * @param WP_Post $post             Post being edited.
 * @return bool
 */
function hectv_cms_use_classic_editor_for_home( $use_block_editor, $post ) {
	if (
		is_object( $post )
		&& isset( $post->ID, $post->post_type )
		&& (int) $post->ID === HECTV_HOME_PAGE_ID
		&& $post->post_type === 'page'
	) {
		return false;
	}

	return $use_block_editor;
}

add_filter( 'use_block_editor_for_post', 'hectv_cms_use_classic_editor_for_home', 100, 2 );
