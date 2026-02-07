<?php
/**
 * Feature: Admin Bar Greeting
 *
 * Customizes the "Howdy" greeting in the WordPress admin bar.
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
 * Class Feature_ArBricks_Admin_Bar_Howdy
 */
class Feature_ArBricks_Admin_Bar_Howdy implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_admin_bar_howdy';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Admin Bar Greeting', 'arbricks' ),
			'description' => __( 'Customize the "Howdy" text next to the username in the admin bar.', 'arbricks' ),
			'category'    => 'tools',
			'help'        => array(
				'summary'  => __( 'The "Howdy" greeting always appears next to the username in the top admin bar. This feature allows you to replace it with a custom greeting that suits your site\'s language or brand.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature to start customizing the greeting.', 'arbricks' ),
					__( 'Enter your preferred greeting (e.g., "Welcome", "Hello", or "Good Morning").', 'arbricks' ),
					__( 'Choose whether to keep the username after the greeting or hide it.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'This change is purely cosmetic and does not change any user permissions.', 'arbricks' ),
					__( 'The change appears in the admin bar whether you are in the dashboard or browsing the site.', 'arbricks' ),
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
			'arbricks_abh_greeting'      => array(
				'type'        => 'text',
				'label'       => __( 'Greeting Text', 'arbricks' ),
				'description' => __( 'The text that replaces "Howdy". Default is "Welcome".', 'arbricks' ),
				'default'     => 'Welcome',
			),
			'arbricks_abh_keep_username' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Keep Username', 'arbricks' ),
				'description' => __( 'Display the username after the greeting text.', 'arbricks' ),
				'default'     => true,
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_bar_menu', array( $this, 'customize_greeting' ), 25 );
	}

	/**
	 * Customize the admin bar greeting.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function customize_greeting( $wp_admin_bar ): void {
		$settings = Options::get_feature_settings( self::id() );
		$greeting = $settings['arbricks_abh_greeting'] ?? 'Welcome';
		$keep_user = $settings['arbricks_abh_keep_username'] ?? true;

		$my_account = $wp_admin_bar->get_node( 'my-account' );
		if ( ! $my_account ) {
			return;
		}

		$current_user = wp_get_current_user();
		if ( ! $current_user->exists() ) {
			return;
		}

		$display_name = $current_user->display_name;
		$avatar       = get_avatar( $current_user->ID, 28 );

		if ( $keep_user ) {
			/* translators: 1: Custom greeting, 2: Display name */
			$new_title = sprintf( __( '%1$s, %2$s', 'arbricks' ), $greeting, $display_name );
		} else {
			$new_title = $greeting;
		}

		// Preserve the avatar if it was there (it usually is in standard WP).
		$new_title .= $avatar;

		$wp_admin_bar->add_node( array(
			'id'    => 'my-account',
			'title' => $new_title,
		) );
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
