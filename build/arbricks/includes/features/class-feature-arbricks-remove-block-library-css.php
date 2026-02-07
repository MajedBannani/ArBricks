<?php
/**
 * Feature: Remove Block Library CSS
 *
 * Removes Gutenberg block library CSS from the frontend for better performance.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Remove_Block_Library_CSS
 *
 * Dequeues wp-block-library and related styles from the frontend.
 */
class Feature_ArBricks_Remove_Block_Library_CSS implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string {
		return 'arbricks_remove_block_library_css';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array Feature metadata.
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Remove Block Library CSS', 'arbricks' ),
			'description' => __( 'Remove Gutenberg block library CSS from the frontend to improve performance when not using Gutenberg.', 'arbricks' ),
			'category'    => 'performance',
			'help'        => array(
				'summary' => __( 'WordPress by default loads large CSS files to support "Blocks" (Gutenberg) even if you don\'t use them. This feature removes these files from the frontend to reduce page size and improve load times.', 'arbricks' ),
				'how_to'  => array(
					__( 'Enable the toggle above to remove the files.', 'arbricks' ),
					__( 'Click "Save Changes" to apply.', 'arbricks' ),
					__( 'Verify site speed or inspect source code for wp-block-library files.', 'arbricks' ),
				),
				'notes'   => array(
					__( 'Very useful for users of Bricks, Elementor, and other page builders.', 'arbricks' ),
					__( 'Affects frontend only; does not disable the block editor in the dashboard.', 'arbricks' ),
					__( 'Warning: Do not enable if you use Gutenberg blocks on your frontend, as styles will be lost.', 'arbricks' ),
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
			add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_block_styles' ), 100 );
		}
	}

	/**
	 * Dequeue Gutenberg block styles.
	 *
	 * @return void
	 */
	public function dequeue_block_styles(): void {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-block-style' ); // WooCommerce block styles if present.
		wp_dequeue_style( 'global-styles' );    // WordPress 5.9+ block theme inline styles.
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
