<?php
/**
 * Plugin Name: Populate Nav Menu Locations
 */

add_filter(
	'theme_mod_nav_menu_locations',
	function ( $locations ) {
		if ( is_admin() ) {
			return $locations;
		}

		$testing_menu = wp_get_nav_menu_object( 'Testing Menu' );
		$social_menu = wp_get_nav_menu_object( 'Social menu' );
		if ( ! ( $testing_menu instanceof WP_Term ) || ! ( $social_menu instanceof WP_Term ) ) {
			return $locations;
		}

		$locations = [];
		foreach ( array_keys( get_registered_nav_menus() ) as $nav_menu_location ) {
			if ( 'social' === $nav_menu_location ) {
				$locations[ $nav_menu_location ] = $social_menu->term_id;
			} else {
				$locations[ $nav_menu_location ] = $testing_menu->term_id;
			}
		}
		return $locations;
	}
);
