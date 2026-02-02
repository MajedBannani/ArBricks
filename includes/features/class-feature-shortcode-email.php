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
			'help'        => array(
				'summary'  => __( 'Provides a simple [email] shortcode to create clickable email links (mailto links) with built-in spam protection. WordPress\'s antispambot function encodes the email address to make it harder for spam bots to harvest.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above', 'arbricks' ),
					__( 'Click "Save Changes"', 'arbricks' ),
					__( 'In any post, page, or widget, use: [email]youraddress@example.com[/email]', 'arbricks' ),
					__( 'The shortcode will output a clickable mailto link with spam protection', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Email address is encoded using WordPress antispambot() function', 'arbricks' ),
					__( 'Invalid email addresses are displayed as plain text (not linked)', 'arbricks' ),
					__( 'Works in posts, pages, custom post types, and text widgets that support shortcodes', 'arbricks' ),
					__( 'No configuration needed - just use the shortcode', 'arbricks' ),
				),
				'examples' => array(
					__( 'Usage: [email]contact@yoursite.com[/email]', 'arbricks' ),
					__( 'Output: Clickable mailto link with spam-protected email', 'arbricks' ),
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
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
