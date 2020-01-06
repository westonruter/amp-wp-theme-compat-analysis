<?php
/**
 * Plugin Name: Gather Stats
 */

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command(
	'gather-stats',
	function ( $args ) {
		$page_name = isset( $args[0] ) ? $args[0] : 'hello-world';
		chdir( ABSPATH . '/../results' );

		$rows = [];
		foreach ( glob( '*', GLOB_ONLYDIR ) as $theme_dir ) {
			$data_file = "$theme_dir/$page_name.json";
			if ( ! file_exists( $data_file ) ) {
				WP_CLI::warning( "Missing $data_file" );
				continue;
			}

			$page_data = json_decode( file_get_contents( $data_file ), true );
			list( $original_size, $minified_size ) = get_stylesheet_sizes( $page_data );

			$rows[] = [
				$theme_dir,
				$original_size,
				$minified_size,
			];
		}

		print_table( $rows );
	}
);

function get_theme_popularities( $themes ) {
	$results = get_transient( 'popular-250-themes' );
	if ( empty( $results ) ) {
		$results = themes_api( 'query_themes', [ 'browse' => 'popular', 'per_page' => 250 ] );
		set_transient( 'popular-250-themes', $results, DAY_IN_SECONDS );
	}

	$popularities = [];
	foreach ( $results->themes as $i => $theme ) {
		if ( in_array( $theme->slug, $themes ) ) {
			$popularities[ $theme->slug ] = $i;
		}
	}

	// Normalize for missing themes.
	asort( $popularities );
	foreach ( array_keys( $popularities ) as $i => $theme ) {
		$popularities[ $theme ] = $i + 1;
	}

	return $popularities;
}

function sort_by_minified_size_descending( &$rows ) {
	usort(
		$rows,
		function ( $a, $b ) {
			return $b[2] - $a[2];
		}
	);
}

function sort_by_popularity_ascending( &$rows ) {
	$popularities = get_theme_popularities( array_column( $rows, 0 ) );
	usort(
		$rows,
		function ( $a, $b ) use ( $popularities ) {
			if ( ! isset( $popularities[ $a[0] ] ) || ! isset( $popularities[ $b[0] ] ) ) {
				return 0;
			}
			return $popularities[ $a[0] ] - $popularities[ $b[0] ];
		}
	);
}

function print_table( $rows ) {

	$popularities = get_theme_popularities( array_column( $rows, 0 ) );

	//sort_by_minified_size_descending( $rows );
	sort_by_popularity_ascending( $rows );

	$count_error = 0;
	$count_warn  = 0;
	$count_ok    = 0;

	$lines = [];
	$lines[] = "Rank | Theme | Original CSS | Minified CSS | Budget % | Status";
	$lines[] = "---- | ----- | ------------ | ------------ | -------- | ------";
	foreach ( $rows as $row ) {
		$output = [];
		$output[] = isset( $popularities[ $row[0] ] ) ? $popularities[ $row[0] ] : '?';
		$output[] = sprintf( '[%1$s](https://wordpress.org/themes/%1$s/)', $row[0] );
		$output[] = number_format( $row[1] );
		$output[] = number_format( $row[2] );
		$budget_used = $row[2] / 50000;
		$output[] = sprintf( '%.1f%%', $budget_used * 100 );
		if ( $budget_used > 1 ) {
			$output[] = '🚫';
			$count_error++;
		} elseif ( $budget_used >= 0.8 ) {
			$output[] = '⚠️';
			$count_warn++;
		} else {
			$output[] = '✅';
			$count_ok++;
		}
		$lines[] = '| ' . implode( ' | ', $output ) . ' |';
	}

	WP_CLI::line( sprintf( '* Over the budget: %d%% 🚫', $count_error / count( $rows ) * 100 ) );
	WP_CLI::line( sprintf( '* Close (≥80%%) to the budget: %d%%  ⚠️', $count_warn / count( $rows ) * 100 ) );
	WP_CLI::line( sprintf( '* Well under the budget (<80%%): %d%% ✅', $count_ok / count( $rows ) * 100 ) );

	WP_CLI::line( '' );

	foreach ( $lines as $line ) {
		WP_CLI::line( $line );
	}
}

function get_stylesheet_sizes( $data ) {
	$original_size = 0;
	$minified_size = 0;
	foreach ( $data['stylesheets'] as $stylesheet ) {
		$original_size += $stylesheet['original_size'];
		$minified_size += $stylesheet['final_size'];
	}
	return [ $original_size, $minified_size ];
}
