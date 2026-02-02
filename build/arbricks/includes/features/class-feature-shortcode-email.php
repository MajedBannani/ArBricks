<?php
/**
 * Feature: Email Shortcode
 *
 * Provides [email] shortcode to output safe mailto links.
 *
 * @package ArBricks
 * @since 2.0.2
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Shortcode_Email
 *
 * Email shortcode with antispambot.
 */
class Feature_Shortcode_Email implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'shortcode_email';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Email Shortcode', 'arbricks' ),
			'description' => __( 'Provides [email] shortcode for spam-protected mailto links.', 'arbricks' ),
			'category'    => 'tools',
			'shortcode'   => '[email]',
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_shortcode( 'email', array( $this, 'email_shortcode' ) );
	}

	/**
	 * Email shortcode handler
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string Output HTML.
	 */
	public function email_shortcode( $atts, $content = null ): string {
		// Trim and validate.
		$email = trim( $content );

		if ( empty( $email ) || ! is_email( $email ) ) {
			// Return original content if invalid.
			return esc_html( $content );
		}

		// Use antispambot for email protection.
		$protected_email = antispambot( $email );

		// Build mailto link.
		return sprintf(
			'<a href="mailto:%s">%s</a>',
			esc_attr( $protected_email ),
			esc_html( $protected_email )
		);
	}
}
