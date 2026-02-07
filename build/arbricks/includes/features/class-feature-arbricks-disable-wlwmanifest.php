<?php
/**
 * Feature: Disable WLWManifest Link
 *
 * Removes the Windows Live Writer manifest link from the frontend head.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Disable_WLWManifest
 *
 * Removes the wlwmanifest link from wp_head.
 */
class Feature_ArBricks_Disable_WLWManifest implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string {
		return 'arbricks_disable_wlwmanifest';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array Feature metadata.
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Disable WLWManifest Link', 'arbricks' ),
			'description' => __( 'Remove the Windows Live Writer manifest link from the head section.', 'arbricks' ),
			'category'    => 'seo',
			'help'        => array(
				'summary' => __( 'Removes the wlwmanifest.xml link from the site head. This file was used by Windows Live Writer to enable publishing to WordPress, and is obsolete for most modern sites.', 'arbricks' ),
				'how_to'  => array(
					__( 'Enable the toggle above to remove the link.', 'arbricks' ),
					__( 'Click "Save Changes" to apply the setting.', 'arbricks' ),
					__( 'The link will be immediately removed from the HTML head section on the frontend.', 'arbricks' ),
				),
				'notes'   => array(
					__( 'Improves head section cleanliness.', 'arbricks' ),
					__( 'Does not affect modern editors or basic site performance.', 'arbricks' ),
					__( 'The actual file is not deleted from the server, only the link in the header is removed.', 'arbricks' ),
					__( 'This feature does not affect the admin panel.', 'arbricks' ),
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
			remove_action( 'wp_head', 'wlwmanifest_link' );
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
