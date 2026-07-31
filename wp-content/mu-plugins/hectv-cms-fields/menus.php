<?php
/**
 * Menu location for Support / Subscribe (and other top-bar action links).
 *
 * Appearance → Menus → "Header Actions (Support / Subscribe)".
 * Items are ordinary custom links or pages; the headless app reads them via
 * WPGraphQL menus (slug: header-actions) or RootQuery.topbarCtas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	static function () {
		register_nav_menu(
			HECTV_MENU_HEADER_ACTIONS,
			__( 'Header Actions (Support / Subscribe)', 'hectv' )
		);
	}
);

/**
 * Return header-actions menu items as [ label, url, style? ] rows.
 * Used by GraphQL topbarCtas when the staging options list is empty.
 *
 * @return array<int, array{label:string,url:string,style:string}>
 */
function hectv_cms_get_header_action_items() {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ HECTV_MENU_HEADER_ACTIONS ] ) ) {
		// Fall back to a menu named "header-actions" / "Header Actions".
		$menu = wp_get_nav_menu_object( 'header-actions' );
		if ( ! $menu ) {
			$menu = wp_get_nav_menu_object( 'Header Actions' );
		}
		if ( ! $menu ) {
			return array();
		}
		$menu_id = (int) $menu->term_id;
	} else {
		$menu_id = (int) $locations[ HECTV_MENU_HEADER_ACTIONS ];
	}

	$items = wp_get_nav_menu_items( $menu_id );
	if ( ! is_array( $items ) || ! $items ) {
		return array();
	}

	$out = array();
	foreach ( $items as $item ) {
		// Top-level only — nested items are not top-bar CTAs.
		if ( (int) $item->menu_item_parent !== 0 ) {
			continue;
		}
		$label = trim( (string) $item->title );
		$url   = trim( (string) $item->url );
		if ( $label === '' || $url === '' ) {
			continue;
		}
		// Optional style from CSS classes: primary|secondary|tertiary.
		$style   = 'secondary';
		$classes = is_array( $item->classes ) ? $item->classes : array();
		foreach ( array( 'primary', 'secondary', 'tertiary' ) as $candidate ) {
			if ( in_array( $candidate, $classes, true ) ) {
				$style = $candidate;
				break;
			}
		}
		$out[] = array(
			'label' => $label,
			'url'   => $url,
			'style' => $style,
		);
	}

	return $out;
}

/**
 * Ensure a "header-actions" menu exists with Support + Subscribe when empty.
 * Idempotent; safe to call from seed / activation. Does not overwrite custom menus.
 */
function hectv_cms_ensure_default_header_actions_menu() {
	$menu_name = 'Header Actions';
	$menu_slug = 'header-actions';
	$created   = false;
	$menu      = wp_get_nav_menu_object( $menu_slug );
	if ( ! $menu ) {
		$menu = wp_get_nav_menu_object( $menu_name );
	}
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $menu_id ) ) {
			return false;
		}
		$created = true;
	} else {
		$menu_id = (int) $menu->term_id;
	}

	$existing = wp_get_nav_menu_items( $menu_id );
	if ( is_array( $existing ) && count( $existing ) > 0 ) {
		return true;
	}

	$defaults = array(
		array(
			'title'  => 'Subscribe',
			'url'    => home_url( '/newsletter' ),
			'classes' => 'primary',
		),
		array(
			'title'  => 'Support',
			'url'    => home_url( '/support' ),
			'classes' => 'secondary',
		),
	);

	foreach ( $defaults as $i => $row ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => $row['title'],
				'menu-item-url'    => $row['url'],
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
				'menu-item-classes' => $row['classes'],
				'menu-item-position' => $i + 1,
			)
		);
	}

	if ( $created ) {
		$locations = get_nav_menu_locations();
		$locations[ HECTV_MENU_HEADER_ACTIONS ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	return true;
}

// Seed defaults once after theme setup when the location is empty.
add_action(
	'init',
	static function () {
		$env              = getenv( 'HECTV_ENVIRONMENT' );
		$environment_seed = $env === 'staging' || $env === 'local';
		$explicit_seed    = defined( 'HECTV_CMS_SEED_MENUS' ) && HECTV_CMS_SEED_MENUS;
		if ( ! $environment_seed && ! $explicit_seed ) {
			return;
		}
		if ( get_option( 'hectv_cms_header_actions_seeded', false ) ) {
			return;
		}

		$locations = get_nav_menu_locations();
		if (
			empty( $locations[ HECTV_MENU_HEADER_ACTIONS ] )
			&& hectv_cms_ensure_default_header_actions_menu()
		) {
			update_option( 'hectv_cms_header_actions_seeded', 1, false );
		}
	},
	20
);
