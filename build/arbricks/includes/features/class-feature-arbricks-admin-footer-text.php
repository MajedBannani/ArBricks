<?php
/**
 * Feature: Admin Footer Text Customization
 *
 * Allows customizing the text in the WordPress admin dashboard footer.
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
 * Class Feature_ArBricks_Admin_Footer_Text
 */
class Feature_ArBricks_Admin_Footer_Text implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_admin_footer_text';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Admin Footer Text', 'arbricks' ),
			'description' => __( 'Customize the text displayed in the WordPress admin dashboard footer for client sites or your own branding.', 'arbricks' ),
			'category'    => 'tools',
			'help'        => array(
				'summary'  => __( 'The admin footer is the section at the bottom of every page in the WordPress dashboard. This feature allows you to replace default text with custom text, which is very useful when delivering sites to clients.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature to start customizing strings.', 'arbricks' ),
					__( 'Enter the desired text for the right side (usually WordPress version) and the left side (default thank you text).', 'arbricks' ),
					__( 'You can use simple HTML tags like <a> links or <strong> bold text.', 'arbricks' ),
					__( 'Save settings and you will see the change immediately at the bottom of the dashboard.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'If you leave fields empty, WordPress will continue to show its default strings.', 'arbricks' ),
					__( '"Restore Default Text" option temporarily disables customization without deleting the text you wrote.', 'arbricks' ),
					__( 'This feature only appears in the dashboard and does not affect the frontend of the site.', 'arbricks' ),
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
			'arbricks_aft_left_text'     => array(
				'type'        => 'textarea',
				'label'       => __( 'Footer Text (Left)', 'arbricks' ),
				'description' => __( 'The text that replaces "Thank you for creating with WordPress". HTML allowed.', 'arbricks' ),
				'default'     => '',
			),
			'arbricks_aft_right_text'    => array(
				'type'        => 'text',
				'label'       => __( 'Footer Text (Right)', 'arbricks' ),
				'description' => __( 'The text that appears next to the version number. HTML allowed.', 'arbricks' ),
				'default'     => '',
			),
			'arbricks_aft_restore_default' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Restore Default WordPress Text', 'arbricks' ),
				'description' => __( 'Ignore custom strings and revert to official WordPress footer text.', 'arbricks' ),
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
		if ( ! is_admin() ) {
			return;
		}

		$settings = Options::get_feature_settings( self::id() );
		if ( ! empty( $settings['arbricks_aft_restore_default'] ) ) {
			return;
		}

		add_filter( 'admin_footer_text', array( $this, 'customize_left_footer' ), 11 );
		add_filter( 'update_footer', array( $this, 'customize_right_footer' ), 11 );
	}

	/**
	 * Customize left side footer text
	 */
	public function customize_left_footer( $text ) {
		$settings = Options::get_feature_settings( self::id() );
		$custom   = $settings['arbricks_aft_left_text'] ?? '';

		if ( ! empty( $custom ) ) {
			return $this->sanitize_footer_html( $custom );
		}

		return $text;
	}

	/**
	 * Customize right side footer text
	 */
	public function customize_right_footer( $text ) {
		$settings = Options::get_feature_settings( self::id() );
		$custom   = $settings['arbricks_aft_right_text'] ?? '';

		if ( ! empty( $custom ) ) {
			return $this->sanitize_footer_html( $custom );
		}

		return $text;
	}

	/**
	 * Sanitize HTML content allowed in footer
	 */
	private function sanitize_footer_html( $content ) {
		return wp_kses( $content, array(
			'a'      => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'span'   => array(
				'style' => array(),
				'class' => array(),
			),
		) );
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
