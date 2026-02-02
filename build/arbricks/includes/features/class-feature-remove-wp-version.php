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
}
