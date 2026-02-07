<?php
/**
 * Feature: Disable jQuery Migrate
 *
 * Removes jQuery Migrate from the frontend to improve performance.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Disable_jQuery_Migrate
 *
 * Removes jquery-migrate dependency from jquery on the frontend.
 */
class Feature_ArBricks_Disable_jQuery_Migrate implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string {
		return 'arbricks_disable_jquery_migrate';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array Feature metadata.
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Disable jQuery Migrate', 'arbricks' ),
			'description' => __( 'Remove jQuery Migrate from the frontend to improve performance when not using plugins that depend on legacy code.', 'arbricks' ),
			'category'    => 'performance',
			'help'        => array(
				'summary' => __( 'jQuery Migrate is a helper library used to provide compatibility for old jQuery versions. Most modern themes and plugins no longer need it. Disabling it reduces the number of JavaScript files loaded on the frontend.', 'arbricks' ),
				'how_to'  => array(
					__( 'Enable the toggle above to disable the library.', 'arbricks' ),
					__( 'Click "Save Changes" to apply.', 'arbricks' ),
					__( 'Verify there are no errors in the browser "Console" after disabling.', 'arbricks' ),
				),
				'notes'   => array(
					__( 'Disabling happens on the frontend only and does not affect the dashboard or block editor.', 'arbricks' ),
					__( 'If you notice certain functions (like menus or sliders) stop working, your theme or a plugin might be using old code; in that case, re-enable the library.', 'arbricks' ),
					__( 'The file is not deleted from the server; it is only prevented from loading as a jQuery dependency.', 'arbricks' ),
				),
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * Called only when feature is enabled.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Only remove on frontend.
		if ( ! is_admin() ) {
			add_filter( 'wp_default_scripts', array( $this, 'disable_jquery_migrate' ) );
		}
	}

	/**
	 * Remove jQuery Migrate from jQuery dependencies.
	 *
	 * @param \WP_Scripts $scripts WP_Scripts object.
	 * @return void
	 */
	public function disable_jquery_migrate( $scripts ): void {
		if ( ! is_admin() && ! empty( $scripts->registered['jquery'] ) ) {
			$scripts->registered['jquery']->deps = array_diff(
				$scripts->registered['jquery']->deps,
				array( 'jquery-migrate' )
			);
		}
	}

	/**
	 * Render custom admin UI on the settings page
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		// No additional settings required for this feature.
	}
}
