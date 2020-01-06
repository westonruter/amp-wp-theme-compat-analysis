<?php
/**
 * Plugin Name: Query Popular Themes
 */

function get_theme_popularities() {
	$transient_key = 'theme_popularities';
	$popularities  = get_transient( $transient_key );
	if ( empty( $popularities ) ) {
		$popularities = [];

		$rank = 0;
		for ( $page = 1;; $page++ ) {
			$results = themes_api( 'query_themes', [ 'browse' => 'popular', 'per_page' => 500, 'page' => $page ] );
			if ( empty( $results->themes ) ) {
				break;
			}
			foreach ( $results->themes as $theme ) {
				$popularities[ $theme->slug ] = $rank++;
			}
		}
		set_transient( $transient_key, $popularities, DAY_IN_SECONDS );
	}

	asort( $popularities );

	return $popularities;
}

if ( defined( 'WP_CLI' ) ) {
	WP_CLI::add_command(
		'query-popular-themes',
		function ( $args ) {
			$limit = isset( $args[0] ) && is_numeric( $args[0] ) ? (int) $args[0] : PHP_INT_MAX;
			foreach ( array_keys( get_theme_popularities() ) as $i => $theme ) {
				WP_CLI::line( $theme );
				if ( $i + 1 === $limit ) {
					break;
				}
			}
		}
	);
}
