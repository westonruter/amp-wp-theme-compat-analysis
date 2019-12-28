<?php
/**
 * Plugin Name: Create Monster Post
 */


if ( ! defined( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command(
	'create-monster-post',
	function () {

		# Set featured image.
		# Re-assign all comments to have monster post as parent.
		# Gather all content of all posts and make content.

		$monster_post = get_page_by_path( 'monster', ARRAY_A, 'post' );
		if ( ! $monster_post ) {
			$monster_post = [];
		}
		$monster_post = array_merge(
			$monster_post,
			[
				'post_title' => 'Monster',
				'post_name'  => 'monster',
				'post_status' => 'publish',
				'post_type' => 'post',
			]
		);

		$all_posts = get_posts(
			[
				'posts_per_page' => -1,
				'post_type' => 'any',
				'exclude' => $monster_post ? [ $monster_post['ID'] ] : null,
			]
		);

		$all_posts[] = get_page_by_title( 'Block Unit Test', OBJECT, 'page' );
		$all_posts[] = get_page_by_title( 'CoBlocks Unit Test', OBJECT, 'page' );

		$featured_image = get_page_by_title( 'Golden Gate Bridge', OBJECT, 'attachment' );

		$all_posts = array_filter( $all_posts );

		WP_CLI::line( 'All posts: ' . count( $all_posts ) );

		$monster_post['post_content'] = implode(
			"\n\n",
			array_map(
				function ( WP_Post $post ) {
					$content = $post->post_content;

					// Fixup ASCII art for sake of libxml.
					// TODO: The "Page Markup And Formatting" unit test data has this problem, as the ASCII art causes libxml to incorrectly parse the content.
					$content = preg_replace_callback(
						'#<pre>(.+?)</pre>#s',
						function ( $matches ) {
							$fixed = $matches[1];
							$fixed = preg_replace( '#<(?!\w|/)#', '&lt;', $fixed );
							return sprintf( '<pre>%s</pre>', $fixed );
						},
						$content
					);

					// Ensure classic content can be injected alongside block content by auto-paragraphing it.
					if ( ! preg_match( '/<!--\s*wp:/', $content ) ) {
						/**
						 * @var WP_Embed $wp_embed
						 */
						global $wp_embed;
						$content = wpautop( $content );
						$content = $wp_embed->autoembed( $content );
					}

					return sprintf(
						"<!--wp:heading-->\n<h2>Original Post: <a href='%s'>%s</a></h2>\n<!--/wp:heading-->\n\n%s",
						esc_url( get_permalink( $post ) ),
						$post->post_title,
						$content
					);
				},
				$all_posts
			)
		);

		// Remove page breaks so everything is on one page.
		$monster_post['post_content'] = preg_replace( "#<!--\s*(/?wp:nextpage|nextpage)\s*-->\n?#", '', $monster_post['post_content'] );

		// Add a page break to cause the break element to appear.
		$monster_post['post_content'] .= "\n\n<!-- wp:nextpage -->\n<!--nextpage-->\n<!-- /wp:nextpage -->\n\nThe End";

		$post_id = wp_insert_post( wp_slash( $monster_post ) );
		if ( $post_id instanceof WP_Error ) {
			WP_CLI::error( $post_id );
		}

		if ( $featured_image ) {
			set_post_thumbnail( $post_id, $featured_image->ID );
		}

		WP_CLI::line( "Monster ID: $post_id" );
		WP_CLI::line( get_permalink( $post_id ) );

		/**
		 * @var WP_Comment[] $comments
		 */
		$comments = get_comments( [ 'status' => 'all' ] );
		WP_CLI::line( 'All comments: ' . count( $comments ) );
		foreach ( $comments as $comment ) {
			if ( (int) $comment->comment_post_ID !== $post_id ) {
				wp_update_comment(
					array_merge(
						(array) $comment,
						[
							'comment_post_ID' => $post_id,
						]
					)
				);
			}
		}
	}
);
