<?php
/**
 * Plugin Name: Query Popular Themes
 */

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command(
	'query-popular-themes',
	function ( $args ) {
		$per_page = isset( $args[0] ) ? $args[0] : 100;
		$response = themes_api( "query_themes", [ "browse" => "popular", "per_page" => $per_page ] );
		echo implode( "\n", wp_list_pluck( $response->themes, 'slug' ) );
	}
);
