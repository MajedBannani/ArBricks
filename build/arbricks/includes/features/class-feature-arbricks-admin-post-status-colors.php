<?php
/**
 * Feature: Admin Post Status Colors
 *
 * Adds subtle background colors to post list rows based on their status.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

use ArBricks\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Admin_Post_Status_Colors
 */
class Feature_ArBricks_Admin_Post_Status_Colors implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_admin_post_status_colors';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Post Status Colors in Admin', 'arbricks' ),
			'description' => __( 'Highlight post rows with subtle colors based on their status (Draft, Pending Review, etc.) in the admin area.', 'arbricks' ),
			'category'    => 'tools',
			'help'        => array(
				'summary'  => __( 'Adds subtle background colors to post list table rows based on their status, making it easier to visually distinguish between drafts, scheduled, and private posts.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the main feature then choose the statuses you want to color from the options below.', 'arbricks' ),
					__( 'Navigate to the post (or page) list to see the color effect.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Supported statuses: Draft, Pending Review, Scheduled (Future), and Private.', 'arbricks' ),
					__( 'Colors are designed to be very subtle and do not affect text readability.', 'arbricks' ),
					__( 'Applies to all posts, pages, and custom post type list tables.', 'arbricks' ),
					__( 'Note: If your theme or another plugin already modifies table colors, you might notice an overlap.', 'arbricks' ),
				),
			),
		);
	}

	/**
	 * Get settings schema
	 *
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'arbricks_color_draft'   => array(
				'type'        => 'checkbox',
				'label'       => __( 'Color Drafts', 'arbricks' ),
				'description' => __( 'Color draft posts with a subtle yellow background.', 'arbricks' ),
				'default'     => true,
			),
			'arbricks_color_pending' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Color Pending', 'arbricks' ),
				'description' => __( 'Color pending review posts with a subtle blue background.', 'arbricks' ),
				'default'     => true,
			),
			'arbricks_color_future'  => array(
				'type'        => 'checkbox',
				'label'       => __( 'Color Scheduled', 'arbricks' ),
				'description' => __( 'Color scheduled posts with a subtle green background.', 'arbricks' ),
				'default'     => true,
			),
			'arbricks_color_private' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Color Private', 'arbricks' ),
				'description' => __( 'Color private posts with a subtle red background.', 'arbricks' ),
				'default'     => true,
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_head', array( $this, 'inject_status_colors_css' ) );
	}

	/**
	 * Inject CSS into admin head
	 *
	 * @return void
	 */
	public function inject_status_colors_css(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'edit' !== $screen->base ) {
			return;
		}

		$settings = Options::get_feature_settings( self::id() );
		$css      = '';

		// Draft: Light Yellow
		if ( ! empty( $settings['arbricks_color_draft'] ) ) {
			$css .= '.wp-list-table .status-draft { background-color: #fff9e5 !important; } ';
		}

		// Pending: Light Blue
		if ( ! empty( $settings['arbricks_color_pending'] ) ) {
			$css .= '.wp-list-table .status-pending { background-color: #e5f5ff !important; } ';
		}

		// Future: Light Green
		if ( ! empty( $settings['arbricks_color_future'] ) ) {
			$css .= '.wp-list-table .status-future { background-color: #e5ffed !important; } ';
		}

		// Private: Light Red
		if ( ! empty( $settings['arbricks_color_private'] ) ) {
			$css .= '.wp-list-table .status-private { background-color: #ffe5e5 !important; } ';
		}

		if ( ! empty( $css ) ) {
			echo '<style id="arbricks-status-colors-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
