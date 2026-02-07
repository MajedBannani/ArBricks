<?php
/**
 * Feature: Disable User Enumeration
 *
 * Prevents attackers from discovering usernames via author archives and query parameters.
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
 * Class Feature_Arbricks_Disable_User_Enumeration
 */
class Feature_Arbricks_Disable_User_Enumeration implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_disable_user_enumeration';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Disable User Enumeration', 'arbricks' ),
			'description' => __( 'Prevent attackers from discovering usernames via author archives and query parameters.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary'  => __( 'User enumeration is a technique where attackers discover registered usernames by scanning author archives or using specific query parameters like author=1.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Any attempt to access author archives or enumeration query parameters will be redirected to the homepage.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Security Risk: Knowing a username is the first step in a Brute Force attack.', 'arbricks' ),
					__( 'This feature does not affect the WordPress dashboard or REST API.', 'arbricks' ),
					__( 'Important Note: If you are using another security plugin or a Web Application Firewall (WAF) that provides the same protection, disable one to avoid conflict.', 'arbricks' ),
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
		return array();
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Only run on frontend.
		if ( is_admin() ) {
			return;
		}

		// 1) Prevent access via ?author=ID
		add_action( 'parse_request', array( $this, 'block_user_enumeration_query' ) );

		// 2) Prevent access via /author/username/
		add_action( 'template_redirect', array( $this, 'block_author_archives' ) );
	}

	/**
	 * Block user enumeration via query parameters (?author=N)
	 *
	 * @param \WP $wp WordPress environment object.
	 * @return void
	 */
	public function block_user_enumeration_query( $wp ): void {
		if ( isset( $wp->query_vars['author'] ) && ! empty( $wp->query_vars['author'] ) ) {
			$this->safe_redirect_home();
		}
	}

	/**
	 * Block access to author archive pages
	 *
	 * @return void
	 */
	public function block_author_archives(): void {
		if ( is_author() ) {
			$this->safe_redirect_home();
		}
	}

	/**
	 * Safely redirect to homepage and exit
	 *
	 * @return void
	 */
	private function safe_redirect_home(): void {
		wp_safe_redirect( home_url(), 301 );
		exit;
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
