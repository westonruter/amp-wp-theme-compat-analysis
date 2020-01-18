<?php

chdir( 'results/theme-directories/wporg-themes' );

$keyframes_sizes = [];
foreach ( glob( '*', GLOB_ONLYDIR ) as $theme ) {
	if ( ! ( file_exists( "$theme/monster.html" ) && file_exists( "$theme/monster.json" ) ) ) {
		continue;
	}
	$data = json_decode( file_get_contents( "$theme/monster.json" ), true );
	if ( ! is_array( $data ) ) {
		continue;
	}

	$keyframes_size = 0;
	if ( preg_match_all( '#@(-\w+-)?keyframes\s*[^{]+{([^{}]+?{[^{}]+?})+}#', file_get_contents( "$theme/monster.html" ), $matches ) ) {
		foreach ( $matches[0] as $keyframes ) {
			if ( false === strpos( $keyframes, '-amp-start' ) ) {
				$keyframes_size += strlen( $keyframes );
			}
		}
	}

	$original_size = 0;
	$final_size    = 0;
	foreach ( $data['stylesheets'] as $stylesheet ) {
		$original_size += $stylesheet['original_size'];
		$final_size    += $stylesheet['final_size'];
	}

	printf( "$theme, $keyframes_size, $original_size, $final_size, %f\n", $keyframes_size / $final_size );

	$keyframes_sizes[] = $keyframes_size;

	#echo "$theme: $keyframes_size\n";
	#ack -o '@(-\w+-)?keyframes\s*[^{]+{([^{}]+?{[^{}]+?})+}' $theme/monster.html
}

//return;
sort( $keyframes_sizes );

//print implode( "\n", $keyframes_sizes );

//return;
printf( "Min keyframes: %d\n", min( $keyframes_sizes ) );
printf( "Max keyframes: %d\n", max( $keyframes_sizes ) );
printf( "Med keyframes: %d\n", $keyframes_sizes[ floor( count( $keyframes_sizes ) / 2 ) ] );
printf( "Avg keyframes: %d\n", array_sum( $keyframes_sizes ) / count( $keyframes_sizes ) );

