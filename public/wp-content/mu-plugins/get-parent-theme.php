<?php
/**
 * Plugin Name: Get Parent THeme
 */

if ( defined( 'WP_CLI' ) ) {
	WP_CLI::add_command(
		'get-parent-theme',
		function ( $args ) {
			if ( empty( $args[0] ) ) {
				WP_CLI::error( "Missing required theme arg." );
			}

			$theme  = wp_get_theme( $args[0] );
			$parent = $theme['Template'];
			if ( ! empty( $parent ) ) {
				echo "$parent";
			}
		}
	);
}
