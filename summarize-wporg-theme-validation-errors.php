<?php

chdir( 'results/theme-directories/wporg-themes' );

$errors_by_count    = [];
$code_node_themes   = [];
$script_name_counts = [];
$theme_count = 0;
$theme_stylesheets = [];

function get_stylesheet_sizes( $data ) {
	$original_size = 0;
	$minified_size = 0;
	foreach ( $data['stylesheets'] as $stylesheet ) {
		$original_size += $stylesheet['original_size'];
		$minified_size += $stylesheet['final_size'];
	}
	return [ $original_size, $minified_size ];
}

$keyframes_sizes = [];
foreach ( glob( '*', GLOB_ONLYDIR ) as $theme ) {
	if ( ! file_exists( "$theme/monster.json" ) ) {
		fwrite( STDERR, "Missing monster.json: $theme\n" );
		continue;
	}

	$json = file_get_contents( "$theme/monster.json" );
	$json = preg_replace( '/^[^{]+?(?={)/s', '', $json ); // Strip any PHP warnings that WP-CLI may have output.

	$data = json_decode( $json, true );
	if ( ! is_array( $data ) ) {
		fwrite( STDERR, "Bad monster.json: $theme\n" );
		continue;
	}

	$theme_count++;

	list( $monster_original_size, $monster_final_size ) = get_stylesheet_sizes( $data );

	$theme_stylesheets[ $theme ] = [ $monster_original_size, $monster_final_size ];

	foreach ( $data['results'] as $result ) {
		$error = $result['error'];
		unset( $error['sources'] );
		foreach ( [ 'node_attributes', 'element_attributes' ] as $error_key ) {
			if ( isset( $error[ $error_key ] ) ) {
				foreach ( $error[ $error_key ] as $attr_name => &$attr_value ) {
					$attr_value = preg_replace( "#^.+wp-content/themes/[^/]+/#", '__THEME_ROOT__', $attr_value );
				}
			}
		}

		$serialized_error = json_encode( $error );
		if ( ! isset( $errors_by_count[ $serialized_error ] ) ) {
			$errors_by_count[ $serialized_error ] = 0;
		}
		$errors_by_count[ $serialized_error ]++;

		if ( ! isset( $error['node_name'] ) ) {
			fwrite( STDERR, "Missing node name: $theme\n" );
			continue;
		}

		if ( 'STYLESHEET_TOO_LONG' ===  $error['code'] ) {
			continue;
		}

		if ( 'script' === $error['node_name'] && 'DISALLOWED_TAG' === $error['code'] && isset( $error['node_attributes']['src'] ) ) {
			$src = $error['node_attributes']['src'];
			$basename = basename( parse_url( $src, PHP_URL_PATH ) );
			$basename = str_replace( '.min.js', '.js', $basename );
			if ( ! isset( $script_name_counts[ $basename ] ) ) {
				$script_name_counts[ $basename ] = 0;
			}
			$script_name_counts[ $basename ]++;
		}

		$key = $error['code'] . '::';
		if ( 'CSS_SYNTAX_INVALID_AT_RULE' === $error['code'] ) {
			$key .= $error['at_rule'];
		} elseif ( 'html_attribute_error' === $error['type'] ) {
			$key .= $error['parent_name'] . '@' . $error['node_name'];
		} else {
			$key .= $error['node_name'];
		}
		if ( ! isset( $code_node_themes[ $key ] ) ) {
			$code_node_themes[ $key ] = [];
		}
		$code_node_themes[ $key ][] = $theme;
	}
}

//arsort( $errors_by_count );
//foreach ( $errors_by_count as $error => $count ) {
//	print "$count\t$error\n";
//}
//return;

$count_error = 0;
$count_warn  = 0;
$count_ok    = 0;

$minification_amounts = [];
$max_minified_size = 0;
$min_minified_size = PHP_INT_MAX;

echo "Theme, Original CSS, Minified CSS\n";
#echo "----- | -----------: | -----------: | -------:\n";
foreach ( $theme_stylesheets as $theme => $stylesheet_data ) {
	$budget_used = $stylesheet_data[1] / 50000;
	if ( $budget_used > 1 ) {
		$count_error++;
	} elseif ( $budget_used >= 0.8 ) {
		$count_warn++;
	} else {
		$count_ok++;
	}

	$minification_amounts[] = $stylesheet_data[1] / $stylesheet_data[0];

	$max_minified_size = max( $max_minified_size, $stylesheet_data[1] );
	$min_minified_size = min( $min_minified_size, $stylesheet_data[1] );

	echo implode(
		", ",
		[
			$theme,
			$stylesheet_data[0],
			$stylesheet_data[1],
		]
	) . PHP_EOL;
}

echo "\n";
echo sprintf( '* Over the budget: %d%% 🚫', $count_error / $theme_count * 100 ) . PHP_EOL;
echo sprintf( '* Close (≥80%%) to the budget: %d%%  ⚠️', $count_warn / $theme_count * 100 ) . PHP_EOL;
echo sprintf( '* Well under the budget (<80%%): %d%% ✅', $count_ok / $theme_count * 100 ) . PHP_EOL;
echo sprintf( '* Average original CSS: %sB', number_format( array_sum( array_column( $theme_stylesheets, 0 ) ) / $theme_count ) ) . PHP_EOL;
echo sprintf( '* Minimum minified CSS: %sB', number_format( $min_minified_size ) ) . PHP_EOL;
echo sprintf( '* Maximum minified CSS: %sB', number_format( $max_minified_size ) ) . PHP_EOL;
echo sprintf( '* Average minified CSS: %sB', number_format( array_sum( array_column( $theme_stylesheets, 1 ) ) / $theme_count ) ) . PHP_EOL;
echo sprintf( '* Average minification: -%d%%', ( 1 - array_sum( $minification_amounts ) / $theme_count ) * 100 ) . PHP_EOL;


echo "\n";
echo "# Scripts Used\n";
arsort( $script_name_counts );
$scripts_used_less_than_thrice = 0;
foreach ( $script_name_counts as $script => $count ) {
	if ( $count < 3 ) {
		$scripts_used_less_than_thrice++;
		continue;
	}
	printf( "%d\t%s\n", $count, $script );
}
if ( $scripts_used_less_than_thrice > 0 ) {
	echo "And $scripts_used_less_than_thrice other scripts used less than three times.\n";
}

echo "\n";
echo "# Error Summary\n";
arsort( $code_node_themes );
foreach ( $code_node_themes as $code_node => $themes ) {
	$count = count( $themes );
	printf( "%d\t%s\n", $count, str_replace( "::", "\t", $code_node ) );
}
