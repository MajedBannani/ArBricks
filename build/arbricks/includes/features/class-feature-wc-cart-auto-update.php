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
}
