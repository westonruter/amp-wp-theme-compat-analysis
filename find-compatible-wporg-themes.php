<?php

chdir( 'results/theme-directories/wporg-themes' );

foreach ( glob( '*', GLOB_ONLYDIR ) as $theme ) {
	if ( ! file_exists( "$theme/monster.json" ) ) {
		fwrite( STDERR, "Missing monster.json: $theme\n" );
		continue;
	}

	$monster_json = file_get_contents( "$theme/monster.json" );
	$monster_data = json_decode( $monster_json, true );
	if ( ! is_array( $monster_data ) ) {
		fwrite( STDERR, "Bad monster.json: $theme\n" );
		continue;
	}

	$validation_errors = array_filter(
		$monster_data['results'],
		function ( $result ) {
			if ( 'STYLESHEET_TOO_LONG' === $result['error']['code'] ) {
				return false;
			}
			if ( 'DISALLOWED_TAG' === $result['error']['code'] && 'script' === $result['error']['node_name'] && isset( $result['error']['node_attributes']['src'] ) ) {
				$src = $result['error']['node_attributes']['src'];
				if (
					false !== strpos( $src, 'videopress-iframe.js' )
					||
					false !== strpos( $src, 'skip-link-focus-fix' )
				) {
					return false;
				}
			}
			if ( 'DISALLOWED_ATTR' === $result['error']['code'] && 'pubdate' === $result['error']['node_name'] ) {
				return false;
			}

			if ( 'CSS_SYNTAX_INVALID_AT_RULE' === $result['error']['code'] && false !== strpos( $result['error']['at_rule'], 'viewport' ) ) {
				return false;
			}
			return true;
		}
	);

	$total_css = 0;
	foreach ( $monster_data['stylesheets'] as $stylesheet ) {
		$total_css += $stylesheet['final_size'];
	}

	if ( empty( $validation_errors ) ) {
		echo "$theme\t$total_css\n";
	}
}
