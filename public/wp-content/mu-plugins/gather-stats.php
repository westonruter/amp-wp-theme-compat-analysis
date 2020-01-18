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
		chdir( ABSPATH . '/../results/theme-directories/wporg-themes' );

		$rows = [];
		foreach ( glob( '*', GLOB_ONLYDIR ) as $theme_dir ) {
			$data_file = "$theme_dir/$page_name.json";
			if ( ! file_exists( $data_file ) ) {
				WP_CLI::warning( "Missing $data_file" );
				continue;
			}

			$page_data = json_decode( file_get_contents( $data_file ), true );
			if ( json_last_error() ) {
				WP_CLI::warning( "Unable to parse $data_file." );
				continue;
			}

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

function sort_by_minified_size_descending( &$rows ) {
	usort(
		$rows,
		function ( $a, $b ) {
			return $b[2] - $a[2];
		}
	);
}

function sort_by_popularity_ascending( &$rows ) {
	$popularities = get_theme_popularities();
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

	$popularities = get_theme_popularities();

	//sort_by_minified_size_descending( $rows );
	sort_by_popularity_ascending( $rows );

	$count_error = 0;
	$count_warn  = 0;
	$count_ok    = 0;

	$minification_amounts = [];

	$max_minified_size = 0;
	$min_minified_size = PHP_INT_MAX;

	$lines = [];
	$lines[] = "Rank | Theme | Original CSS | Minified CSS | Budget % | Status";
	$lines[] = "---: | :---- | -----------: | -----------: | -------: | :----:";
	foreach ( $rows as $row ) {
		$output = [];
		$output[] = isset( $popularities[ $row[0] ] ) ? $popularities[ $row[0] ] + 1 : '?';
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

		$minification_amounts[] = $row[2] / $row[1];

		$max_minified_size = max( $max_minified_size, $row[2] );
		$min_minified_size = min( $min_minified_size, $row[2] );
	}

	WP_CLI::line( sprintf( '* Over the budget: %d%% 🚫', $count_error / count( $rows ) * 100 ) );
	WP_CLI::line( sprintf( '* Close (≥80%%) to the budget: %d%%  ⚠️', $count_warn / count( $rows ) * 100 ) );
	WP_CLI::line( sprintf( '* Well under the budget (<80%%): %d%% ✅', $count_ok / count( $rows ) * 100 ) );
	WP_CLI::line( sprintf( '* Average original CSS: %sB', number_format( array_sum( array_column( $rows, 1 ) ) / count( $rows ) ) ) );
	WP_CLI::line( sprintf( '* Minimum minified CSS: %sB', number_format( $min_minified_size ) ) );
	WP_CLI::line( sprintf( '* Maximum minified CSS: %sB', number_format( $max_minified_size ) ) );
	WP_CLI::line( sprintf( '* Average minified CSS: %sB', number_format( array_sum( array_column( $rows, 2 ) ) / count( $rows ) ) ) );
	WP_CLI::line( sprintf( '* Average minification: -%d%%', ( 1 - array_sum( $minification_amounts ) / count( $rows ) ) * 100 ) );

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
