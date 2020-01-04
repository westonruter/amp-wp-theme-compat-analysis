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

		$menu = wp_get_nav_menu_object( 'Testing Menu' );
		if ( ! ( $menu instanceof WP_Term ) ) {
			return $locations;
		}

		$locations = [];
		foreach ( array_keys( get_registered_nav_menus() ) as $nav_menu_location ) {
			$locations[ $nav_menu_location ] = $menu->term_id;
		}
		return $locations;
	}
);
