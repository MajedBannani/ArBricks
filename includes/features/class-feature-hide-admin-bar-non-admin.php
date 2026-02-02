<?php
/**
 * Feature: Hide Admin Bar (Non-Admins)
 *
 * Hides WordPress admin bar on the front-end for users who do not
 * have a specified capability (default: manage_options).
 *
 * @package ArBricks
 * @since 2.0.1
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Hide_Admin_Bar_Non_Admin
 *
 * Conditionally hides admin bar based on user capability.
 */
class Feature_Hide_Admin_Bar_Non_Admin implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'hide_admin_bar_non_admin';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Hide Admin Bar (Non-Admins)', 'arbricks' ),
			'description' => __( 'Hide WordPress admin bar on the front-end for users who do not have a given capability.', 'arbricks' ),
			'category'    => 'tools',
			'shortcode'   => '',
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'show_admin_bar', array( $this, 'filter_admin_bar' ), 20 );
	}

	/**
	 * Filter admin bar visibility
	 *
	 * @param bool $show Whether to show admin bar.
	 * @return bool
	 */
	public function filter_admin_bar( $show ) {
		// Don't modify for logged-out users.
		if ( ! is_user_logged_in() ) {
			return $show;
		}

		// Allow filtering the required capability.
		$capability = apply_filters( 'arbricks_admin_bar_capability', 'manage_options' );

		// Hide admin bar if user lacks capability.
		if ( ! current_user_can( $capability ) ) {
			return false;
		}

		return $show;
	}
}
