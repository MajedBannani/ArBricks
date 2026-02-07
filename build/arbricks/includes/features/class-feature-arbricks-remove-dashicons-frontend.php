<?php
/**
 * Feature: Remove Dashicons Frontend
 *
 * Removes Dashicons from the frontend for non-logged-in users for better performance.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Remove_Dashicons_Frontend
 *
 * Dequeues dashicons from the frontend when the user is not logged in.
 */
class Feature_ArBricks_Remove_Dashicons_Frontend implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string {
		return 'arbricks_remove_dashicons_frontend';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array Feature metadata.
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Remove Dashicons Frontend', 'arbricks' ),
			'description' => __( 'Remove Dashicons from the frontend for non-logged-in users to improve performance.', 'arbricks' ),
			'category'    => 'performance',
			'help'        => array(
				'summary' => __( 'Dashicons is the default icon font set for WordPress. It is automatically loaded on the frontend, which increases page size by about 30KB. This feature removes it to improve performance.', 'arbricks' ),
				'how_to'  => array(
					__( 'Enable the toggle above to remove the icons.', 'arbricks' ),
					__( 'Click "Save Changes" to apply.', 'arbricks' ),
					__( 'Browse the site as a guest to verify dashicons.min.css is no longer loaded.', 'arbricks' ),
				),
				'notes'   => array(
					__( 'Icons are kept for logged-in users to ensure the Admin Bar works correctly.', 'arbricks' ),
					__( 'If your theme or certain plugins use Dashicons on the frontend, those icons may disappear for visitors.', 'arbricks' ),
					__( 'Modern page builders like Bricks and Elementor usually use other icon sets, so it is safe to enable in most cases.', 'arbricks' ),
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
		// Only remove on frontend for non-logged-in users.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_dashicons' ), 100 );
	}

	/**
	 * Dequeue Dashicons if safety conditions are met.
	 *
	 * @return void
	 */
	public function maybe_dequeue_dashicons(): void {
		if ( ! is_admin() && ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
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
