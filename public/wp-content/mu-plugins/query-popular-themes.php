<?php
/**
 * Plugin Name: Query Popular Themes
 */

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command(
	'query-popular-themes',
	function () {
		$response = themes_api( "query_themes", [ "browse" => "popular", "per_page" => 100 ] );
		echo implode( "\n", wp_list_pluck( $response->themes, 'slug' ) );
	}
);
