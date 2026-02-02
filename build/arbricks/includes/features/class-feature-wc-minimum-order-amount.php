<?php
/**
 * Feature: WooCommerce Minimum Order Amount
 *
 * Enforce minimum order amount at checkout.
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
 * Class Feature_Wc_Minimum_Order_Amount
 *
 * Minimum order amount validation.
 */
class Feature_Wc_Minimum_Order_Amount implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'wc_minimum_order_amount';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Minimum Order Amount', 'arbricks' ),
			'description' => __( 'Enforce minimum order amount at checkout.', 'arbricks' ),
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
			'minimum_amount' => array(
				'type'        => 'text',
				'label'       => __( 'Minimum Amount', 'arbricks' ),
				'description' => __( 'Minimum order value (numeric)', 'arbricks' ),
				'default'     => '50',
				'placeholder' => '50',
			),
			'compare_mode'   => array(
				'type'        => 'select',
				'label'       => __( 'Compare Mode', 'arbricks' ),
				'description' => __( 'What to compare against minimum', 'arbricks' ),
				'options'     => array(
					'subtotal' => __( 'Subtotal (before tax/shipping)', 'arbricks' ),
					'total'    => __( 'Total (after tax/shipping)', 'arbricks' ),
				),
				'default'     => 'subtotal',
			),
			'error_message'  => array(
				'type'        => 'text',
				'label'       => __( 'Error Message', 'arbricks' ),
				'description' => __( 'Use %s for amount placeholder', 'arbricks' ),
				'default'     => __( 'The minimum order amount is %s.', 'arbricks' ),
				'placeholder' => __( 'The minimum order amount is %s.', 'arbricks' ),
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

		add_action( 'woocommerce_checkout_process', array( $this, 'validate_minimum_amount' ) );
		add_action( 'woocommerce_before_cart', array( $this, 'validate_minimum_amount_cart' ) );
	}

	/**
	 * Validate minimum order amount at checkout
	 *
	 * @return void
	 */
	public function validate_minimum_amount(): void {
		if ( ! WC()->cart ) {
			return;
		}

		$settings = Options::get_feature_settings( self::id() );
		
		// Validate minimum amount is numeric before using.
		$minimum = 50; // Default fallback.
		if ( ! empty( $settings['minimum_amount'] ) && is_numeric( $settings['minimum_amount'] ) ) {
			$minimum = floatval( $settings['minimum_amount'] );
			// Ensure minimum is positive.
			if ( $minimum <= 0 ) {
				$minimum = 50;
			}
		}
		
		$mode     = ! empty( $settings['compare_mode'] ) ? $settings['compare_mode'] : 'subtotal';
		$message  = ! empty( $settings['error_message'] ) ? $settings['error_message'] : __( 'The minimum order amount is %s.', 'arbricks' );

		// Get cart amount based on mode.
		if ( 'total' === $mode ) {
			$cart_amount = floatval( WC()->cart->get_total( 'edit' ) );
		} else {
			$cart_amount = floatval( WC()->cart->get_cart_contents_total() );
		}

		// Check minimum.
		if ( $cart_amount < $minimum ) {
			wc_add_notice(
				sprintf(
					esc_html( $message ),
					wp_kses_post( wc_price( $minimum ) )
				),
				'error'
			);
		}
	}

	/**
	 * Show notice on cart page if below minimum
	 *
	 * @return void
	 */
	public function validate_minimum_amount_cart(): void {
		if ( ! WC()->cart ) {
			return;
		}

		$settings = Options::get_feature_settings( self::id() );
		$minimum  = ! empty( $settings['minimum_amount'] ) ? floatval( $settings['minimum_amount'] ) : 50;
		$mode     = ! empty( $settings['compare_mode'] ) ? $settings['compare_mode'] : 'subtotal';
		$message  = ! empty( $settings['error_message'] ) ? $settings['error_message'] : __( 'The minimum order amount is %s.', 'arbricks' );

		// Get cart amount based on mode.
		if ( 'total' === $mode ) {
			$cart_amount = floatval( WC()->cart->get_total( 'edit' ) );
		} else {
			$cart_amount = floatval( WC()->cart->get_cart_contents_total() );
		}

		// Show notice if below minimum.
		if ( $cart_amount < $minimum ) {
			wc_print_notice(
				sprintf(
					esc_html( $message ),
					wp_kses_post( wc_price( $minimum ) )
				),
				'notice'
			);
		}
	}
}
