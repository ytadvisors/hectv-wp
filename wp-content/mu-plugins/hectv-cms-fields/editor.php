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

/**
 * Keep ACF 5.6.9's private hook registry alive beside WordPress's modern one.
 *
 * ACF 5.6.9 assigns its legacy event manager to window.wp.hooks. Modern
 * WordPress later restores the core hook package, which strands every ACF
 * ready/append callback registered in the legacy manager. The visible result
 * is a repeater whose rows can be added but whose post-object Select2 controls
 * never initialize.
 *
 * Loading core's wp-hooks first lets us capture both registries around the ACF
 * script. ACF continues to use its private registry while WordPress retains its
 * own global registry. Scope this bridge to the Home editor so unrelated admin
 * screens keep their existing script graph.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return void
 */
function hectv_cms_bridge_legacy_acf_hooks( $hook_suffix ) {
	$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;

	if ( $hook_suffix !== 'post.php' || $post_id !== HECTV_HOME_PAGE_ID ) {
		return;
	}

	$scripts = wp_scripts();
	if ( empty( $scripts->registered['acf-input'] ) ) {
		return;
	}

	$acf_input = $scripts->registered['acf-input'];
	if ( ! in_array( 'wp-hooks', $acf_input->deps, true ) ) {
		$acf_input->deps[] = 'wp-hooks';
	}

	wp_add_inline_script(
		'acf-input',
		'window.hectvAcfCoreHooks = window.wp && window.wp.hooks ? window.wp.hooks : null;',
		'before'
	);

	wp_add_inline_script(
		'acf-input',
		<<<'JS'
(function (window) {
	'use strict';

	var acf = window.acf;
	var legacyHooks = window.wp && window.wp.hooks;
	var coreHooks = window.hectvAcfCoreHooks;

	if (!acf || !legacyHooks || typeof legacyHooks.storage !== 'function') {
		if (coreHooks && window.wp) {
			window.wp.hooks = coreHooks;
		}
		return;
	}

	function callLegacy(method, argsLike, splitNames) {
		var args = Array.prototype.slice.call(argsLike);
		var names = splitNames ? String(args[0] || '').split(' ') : [String(args[0] || '')];
		var result;

		for (var i = 0; i < names.length; i++) {
			if (!names[i]) {
				continue;
			}
			var callArgs = args.slice();
			callArgs[0] = 'acf/' + names[i];
			result = legacyHooks[method].apply(legacyHooks, callArgs);
		}

		return result;
	}

	acf.add_action = function () {
		callLegacy('addAction', arguments, true);
		return acf;
	};
	acf.remove_action = function () {
		callLegacy('removeAction', arguments, false);
		return acf;
	};
	acf.do_action = function () {
		callLegacy('doAction', arguments, false);
		return acf;
	};
	acf.add_filter = function () {
		callLegacy('addFilter', arguments, false);
		return acf;
	};
	acf.remove_filter = function () {
		callLegacy('removeFilter', arguments, false);
		return acf;
	};
	acf.apply_filters = function () {
		return callLegacy('applyFilters', arguments, false);
	};

	if (coreHooks && window.wp) {
		window.wp.hooks = coreHooks;
	}
	try {
		delete window.hectvAcfCoreHooks;
	} catch (error) {
		window.hectvAcfCoreHooks = null;
	}
}(window));
JS,
		'after'
	);
}

add_action( 'admin_enqueue_scripts', 'hectv_cms_bridge_legacy_acf_hooks', 100 );
