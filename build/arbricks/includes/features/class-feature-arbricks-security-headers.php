<?php
/**
 * Feature: Security Headers
 *
 * Adds essential HTTP security headers to improve site protection.
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
 * Class Feature_Arbricks_Security_Headers
 */
class Feature_Arbricks_Security_Headers implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_security_headers';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Security Headers', 'arbricks' ),
			'description' => __( 'Add essential HTTP security headers to improve site and browser protection.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary'  => __( 'Security Headers are HTTP headers sent by the server to the browser to enable additional protection layers that prevent attacks like XSS, Clickjacking, and Content Sniffing.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the main feature then choose the headers you want to activate below.', 'arbricks' ),
					__( 'Click "Save Changes" to apply the settings.', 'arbricks' ),
					__( 'You can verify headers via browser developer tools (Network tab).', 'arbricks' ),
				),
				'notes'    => array(
					__( 'X-Content-Type-Options: Prevents the browser from guessing the file type (Sniffing).', 'arbricks' ),
					__( 'X-Frame-Options: Prevents your site from being displayed in an iframe to protect it from Clickjacking attacks.', 'arbricks' ),
					__( 'Referrer-Policy: Controls information sent when navigating from your site to another.', 'arbricks' ),
					__( 'Permissions-Policy: Reduces browser permissions like camera and geolocation.', 'arbricks' ),
					__( 'HSTS: Forces the browser to connect via HTTPS only.', 'arbricks' ),
					__( 'Note: If you use Cloudflare or another security plugin that already adds these headers, disable the duplicates.', 'arbricks' ),
					__( 'HSTS Warning: Only enable this if your site is fully stable on HTTPS; configuration errors may block site access.', 'arbricks' ),
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
			'arbricks_enable_x_content_type_options' => array(
				'type'        => 'checkbox',
				'label'       => 'X-Content-Type-Options: nosniff',
				'description' => __( 'Prevent browser from guessing the content type.', 'arbricks' ),
				'default'     => true,
			),
			'arbricks_enable_x_frame_options'        => array(
				'type'        => 'checkbox',
				'label'       => 'X-Frame-Options: SAMEORIGIN',
				'description' => __( 'Protection against Clickjacking attacks.', 'arbricks' ),
				'default'     => true,
			),
			'arbricks_enable_referrer_policy'        => array(
				'type'        => 'checkbox',
				'label'       => 'Referrer-Policy: strict-origin-when-cross-origin',
				'description' => __( 'Control referrer information.', 'arbricks' ),
				'default'     => true,
			),
			'arbricks_enable_permissions_policy'     => array(
				'type'        => 'checkbox',
				'label'       => __( 'Permissions Policy (Conservative)', 'arbricks' ),
				'description' => __( 'Reduce browser permissions (camera, microphone, location).', 'arbricks' ),
				'default'     => false,
			),
			'arbricks_enable_hsts'                   => array(
				'type'        => 'checkbox',
				'label'       => __( 'HSTS Policy (Force HTTPS)', 'arbricks' ),
				'description' => __( 'Force secure connection (use with caution).', 'arbricks' ),
				'default'     => false,
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Only run on frontend (excluding admin and REST).
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		add_action( 'send_headers', array( $this, 'add_security_headers' ) );
	}

	/**
	 * Add security headers
	 *
	 * @return void
	 */
	public function add_security_headers(): void {
		if ( headers_sent() ) {
			return;
		}

		$settings = Options::get_feature_settings( self::id() );

		// 1) X-Content-Type-Options
		if ( ! empty( $settings['arbricks_enable_x_content_type_options'] ) ) {
			header( 'X-Content-Type-Options: nosniff', false );
		}

		// 2) X-Frame-Options
		if ( ! empty( $settings['arbricks_enable_x_frame_options'] ) ) {
			header( 'X-Frame-Options: SAMEORIGIN', false );
		}

		// 3) Referrer-Policy
		if ( ! empty( $settings['arbricks_enable_referrer_policy'] ) ) {
			header( 'Referrer-Policy: strict-origin-when-cross-origin', false );
		}

		// 4) Permissions-Policy
		if ( ! empty( $settings['arbricks_enable_permissions_policy'] ) ) {
			header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()', false );
		}

		// 5) HSTS
		if ( ! empty( $settings['arbricks_enable_hsts'] ) ) {
			// Only send if request is HTTPS.
			if ( is_ssl() ) {
				header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains', false );
			}
		}
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
