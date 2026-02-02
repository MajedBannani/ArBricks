<?php
/**
 * Feature: Head Cleanup
 *
 * Removes unnecessary default tags from wp_head output including
 * discovery links, shortlinks, and optionally REST/feed links.
 *
 * @package ArBricks
 * @since 2.0.1
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Head_Cleanup
 *
 * Cleans up wp_head by removing unnecessary default WordPress tags.
 */
class Feature_Head_Cleanup implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'head_cleanup';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Head Cleanup', 'arbricks' ),
			'description' => __( 'Remove unnecessary default tags from wp_head (discovery links, shortlink, and optional REST/feed links).', 'arbricks' ),
			'category'    => 'tools',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Removes unnecessary meta tags and links from the HTML <head> section. Cleans up wp_head() output by removing RSD link, Windows Live Writer manifest, and shortlink tags. Results in cleaner HTML and slightly improved performance.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above', 'arbricks' ),
					__( 'Click "Save Changes"', 'arbricks' ),
					__( 'Unnecessary tags are immediately removed from your site\'s HTML head', 'arbricks' ),
					__( 'Inspect page source to verify cleaner <head> section', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Always removed: RSD link, Windows Live Writer manifest, shortlink', 'arbricks' ),
					__( 'These tags are legacy and rarely used by modern tools', 'arbricks' ),
					__( 'Future versions may add settings to remove REST API and RSS feed links', 'arbricks' ),
					__( 'No configuration needed - works automatically', 'arbricks' ),
					__( 'Safe for all sites - does not affect functionality', 'arbricks' ),
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
		add_action( 'init', array( $this, 'apply' ), 1 );
	}

	/**
	 * Apply head cleanup actions
	 *
	 * Removes unnecessary wp_head output.
	 *
	 * @return void
	 */
	public function apply(): void {
		// Always remove these unnecessary items.
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );

		// Optional removals (default OFF for safety).
		// Future: make these configurable via feature settings.
		$remove_rest_discovery = false;
		$remove_feed_links     = false;

		if ( $remove_rest_discovery ) {
			remove_action( 'wp_head', 'rest_output_link_wp_head' );
			remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		}

		if ( $remove_feed_links ) {
			remove_action( 'wp_head', 'feed_links', 2 );
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
