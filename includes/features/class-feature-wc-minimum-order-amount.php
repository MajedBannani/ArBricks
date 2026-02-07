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
			'description' => __( 'Enforce a minimum order total at checkout.', 'arbricks' ),
			'category'    => 'woocommerce',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Prevents customers from checking out if their cart total is below a specified amount. Useful for covering shipping costs, ensuring profitability, or encouraging bulk purchases.', 'arbricks' ),
				'how_to'   => array(
					__( 'Ensure WooCommerce is installed and active.', 'arbricks' ),
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Define the "Minimum Amount" (e.g., 50).', 'arbricks' ),
					__( 'Choose "Comparison Mode": Subtotal (before tax and shipping) or Total (after tax and shipping).', 'arbricks' ),
					__( 'Customize the error message (use %s as a placeholder for the amount).', 'arbricks' ),
					__( 'Click "Save Changes".', 'arbricks' ),
					__( 'Try adding products worth less than the minimum and attempt to checkout.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Requires WooCommerce plugin.', 'arbricks' ),
					__( 'Subtotal: Compares the value of products only before adding taxes and shipping costs.', 'arbricks' ),
					__( 'Total: Compares the final total amount the customer will pay.', 'arbricks' ),
					__( 'Minimum Amount must be a number (default is 50).', 'arbricks' ),
					__( 'The error message appears on the cart page and when trying to proceed to checkout.', 'arbricks' ),
					__( 'The %s symbol will be automatically replaced with the formatted amount (e.g., $50).', 'arbricks' ),
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
			'minimum_amount' => array(
				'type'        => 'text',
				'label'       => __( 'Minimum Amount', 'arbricks' ),
				'description' => __( 'Minimum order value (number only).', 'arbricks' ),
				'default'     => '50',
				'placeholder' => '50',
			),
			'compare_mode'   => array(
				'type'        => 'select',
				'label'       => __( 'Comparison Mode', 'arbricks' ),
				'description' => __( 'What to compare against the minimum.', 'arbricks' ),
				'options'     => array(
					'subtotal' => __( 'Subtotal (before tax and shipping)', 'arbricks' ),
					'total'    => __( 'Order Total (after tax and shipping)', 'arbricks' ),
				),
				'default'     => 'subtotal',
			),
			'error_message'  => array(
				'type'        => 'text',
				'label'       => __( 'Error Message', 'arbricks' ),
				'description' => __( 'Use %s for the amount placeholder.', 'arbricks' ),
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
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
