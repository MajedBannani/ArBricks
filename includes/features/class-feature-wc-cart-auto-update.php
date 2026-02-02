<?php
/**
 * Feature: WooCommerce Cart Auto Update
 *
 * Auto-updates cart when quantity changes.
 *
 * @package ArBricks
 * @since 2.0.1
 */

namespace ArBricks\Features;

use ArBricks\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Wc_Cart_Auto_Update
 *
 * Auto-update cart on quantity change.
 */
class Feature_Wc_Cart_Auto_Update implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'wc_cart_auto_update';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Cart Auto Update on Quantity Change', 'arbricks' ),
			'description' => __( 'Automatically update cart when quantity changes.', 'arbricks' ),
			'category'    => 'woocommerce',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Automatically refreshes the cart totals whenever a customer changes the quantity of an item in their cart. Removes the need for customers to manually click the "Update Cart" button, providing a smoother and more modern shopping experience.', 'arbricks' ),
				'how_to'   => array(
					__( 'Ensure WooCommerce is installed and active.', 'arbricks' ),
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Set the "Debounce Delay" (default 250ms) to control how long to wait after the last quantity change before updating.', 'arbricks' ),
					__( 'Decide whether to hide the manual "Update Cart" button.', 'arbricks' ),
					__( 'Click "Save Changes".', 'arbricks' ),
					__( 'Go to your Cart page and change any product quantity to see it auto-update.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Requires WooCommerce plugin.', 'arbricks' ),
					__( 'Uses AJAX to update the cart without a full page reload.', 'arbricks' ),
					__( 'Debounce Delay: Prevents multiple updates if a customer clicks the quantity buttons rapidly.', 'arbricks' ),
					__( 'Hide Update Button: Recommended for a cleaner UI when auto-update is active.', 'arbricks' ),
					__( 'Hide notices: Optionally suppresses the "Cart updated" message for a more seamless feel.', 'arbricks' ),
					__( 'Compatibility: Works with most themes using standard WooCommerce cart markup.', 'arbricks' ),
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
			'debounce_ms'        => array(
				'type'        => 'text',
				'label'       => __( 'Debounce Delay (ms)', 'arbricks' ),
				'description' => __( 'Milliseconds to wait before updating', 'arbricks' ),
				'default'     => '250',
				'placeholder' => '250',
			),
			'hide_update_button' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Hide Update Cart Button', 'arbricks' ),
				'description' => __( 'Auto-update replaces manual update', 'arbricks' ),
				'default'     => true,
			),
			'hide_notices'       => array(
				'type'        => 'checkbox',
				'label'       => __( 'Hide Cart Update Notices', 'arbricks' ),
				'description' => __( 'Suppress "Cart updated" messages', 'arbricks' ),
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
		// Only register if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_cart_scripts' ) );
	}

	/**
	 * Enqueue cart auto-update scripts
	 *
	 * @return void
	 */
	public function enqueue_cart_scripts(): void {
		// Only on cart page.
		if ( ! is_cart() ) {
			return;
		}

		$settings    = Options::get_feature_settings( self::id() );
		$debounce_ms = ! empty( $settings['debounce_ms'] ) ? absint( $settings['debounce_ms'] ) : 250;
		$hide_button = ! empty( $settings['hide_update_button'] );
		$hide_notice = ! empty( $settings['hide_notices'] );

		$handle = 'arbricks-cart-auto-update';

		// Register a dummy handle to attach inline scripts.
		wp_register_script( $handle, false, array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( $handle );

		// Inline JavaScript.
		$js = "
		jQuery(function($) {
			var timeout;
			var debounce = " . esc_js( $debounce_ms ) . ";
			
			$('div.woocommerce').on('change', 'input.qty', function(){
				clearTimeout(timeout);
				timeout = setTimeout(function() {
					$('[name=\"update_cart\"]').trigger('click');
				}, debounce);
			});
		});
		";

		wp_add_inline_script( $handle, $js );

		// Optional CSS to hide elements.
		if ( $hide_button || $hide_notice ) {
			wp_register_style( $handle . '-style', false, array(), '1.0.0' );
			wp_enqueue_style( $handle . '-style' );

			$css = '';
			if ( $hide_button ) {
				$css .= 'button[name="update_cart"] { display: none !important; }';
			}
			if ( $hide_notice ) {
				$css .= '.woocommerce-message { display: none !important; }';
			}

			wp_add_inline_style( $handle . '-style', $css );
		}
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
