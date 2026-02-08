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
			'description' => __( 'Hide the WordPress admin bar on the frontend for users who do not have specified capabilities.', 'arbricks' ),
			'category'    => 'tools',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Hides the WordPress admin bar (the black bar at the top of pages) on the frontend for non-admin users. Clean up the site interface for subscribers, customers, and other users.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Click "Save Changes".', 'arbricks' ),
					__( 'The admin bar will be hidden on the frontend for non-admin users.', 'arbricks' ),
					__( 'Test by logging in with a non-admin user (Subscriber, Customer, etc.).', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Only affects the frontend - the admin bar still appears in the WordPress dashboard.', 'arbricks' ),
					__( 'Users with "manage_options" capability (usually Administrators) still see the admin bar.', 'arbricks' ),
					__( 'Logged-out visitors are not affected (they do not see the admin bar anyway).', 'arbricks' ),
					__( 'Developers can change the required capability using the "arbricks_admin_bar_capability" filter.', 'arbricks' ),
				),
			),
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
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
