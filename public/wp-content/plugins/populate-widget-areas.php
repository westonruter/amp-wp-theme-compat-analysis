<?php
/**
 * Plugin Name: Populate Widget Areas
 */

add_filter(
	'sidebars_widgets',
	function ( $sidebars_widgets ) {
		if ( ! did_action( 'template_redirect' ) || ! function_exists( '\Widget_Population\ensure_first_widget_setting_populated' ) ) {
			return $sidebars_widgets;
		}
		/**
		 * @var WP_Widget_Factory $wp_widget_factory
		 * @var array $wp_registered_sidebars
		 */
		global $wp_widget_factory, $wp_registered_sidebars;

		foreach ( array_keys( $wp_registered_sidebars ) as $sidebar_id ) {
			$widget_ids = [];
			foreach ( $wp_widget_factory->widgets as $widget ) {
				/**
				 * @var WP_Widget $widget
				 */
				\Widget_Population\ensure_first_widget_setting_populated( $widget );
				$widget_ids[] = $widget->id_base . '-2';
			}
			$sidebars_widgets[ $sidebar_id ] = $widget_ids;
		}

		return $sidebars_widgets;
	}
);
