<?php
/**
 * Feature: Remove WP Version
 *
 * Removes WordPress version from head and feeds for security.
 *
 * @package ArBricks
 * @since 2.0.1
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Remove_Wp_Version
 *
 * Removes WordPress version meta tags.
 */
class Feature_Remove_Wp_Version implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'remove_wp_version';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Remove WP Version', 'arbricks' ),
			'description' => __( 'Remove WordPress version from head and feeds for security.', 'arbricks' ),
			'category'    => 'security',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Removes the WordPress version number from your HTML head and RSS feeds. This prevents potential attackers from easily identifying your WordPress version and targeting known vulnerabilities for that specific version.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature using the toggle above.', 'arbricks' ),
					__( 'Click "Save Changes".', 'arbricks' ),
					__( 'The WordPress version will be immediately hidden from the site\'s public output.', 'arbricks' ),
					__( 'Inspect page source - you should not see the "generator" meta tag.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Security through obscurity - not a complete security solution but an extra step.', 'arbricks' ),
					__( 'Removes the <meta name="generator"> tag from HTML head.', 'arbricks' ),
					__( 'Removes versioning from RSS/Atom feeds.', 'arbricks' ),
					__( 'Part of a multi-layered security approach - best used with other security features.', 'arbricks' ),
					__( 'No configuration needed - works automatically.', 'arbricks' ),
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
		add_filter( 'the_generator', '__return_empty_string', 99 );
		add_action(
			'init',
			function () {
				remove_action( 'wp_head', 'wp_generator' );
			},
			1
		);
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
