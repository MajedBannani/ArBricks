<?php
/**
 * Feature: WooCommerce Direct Checkout (Single Item)
 *
 * Bypass cart and go directly to checkout for single items.
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
 * Class Feature_Wc_Direct_Checkout_Single_Item
 *
 * Direct checkout flow for single items.
 */
class Feature_Wc_Direct_Checkout_Single_Item implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'wc_direct_checkout_single_item';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Direct Checkout (Single Item Flow)', 'arbricks' ),
			'description' => __( 'Redirect to checkout and skip cart for single items.', 'arbricks' ),
			'category'    => 'woocommerce',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Streamlines the buying process by sending customers directly to the checkout page immediately after they add a product to their cart, skipping the cart page entirely. Ideal for stores selling single items or wanting to reduce friction.', 'arbricks' ),
				'how_to'   => array(
					__( 'Ensure WooCommerce is installed and active.', 'arbricks' ),
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Configure redirect behavior and message suppression settings.', 'arbricks' ),
					__( 'Optionally enable "Empty Cart Before Adding" to ensure customers only buy one item at a time.', 'arbricks' ),
					__( 'Click "Save Changes".', 'arbricks' ),
					__( 'Test by clicking "Add to Cart" on any product; you should land directly on the Checkout page.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Requires WooCommerce plugin.', 'arbricks' ),
					__( 'Redirect to Checkout: Bypasses the cart page after a successful add-to-cart action.', 'arbricks' ),
					__( 'Empty Cart Before Adding: Automatically clears the current cart before adding the new product. Prevents multi-item orders.', 'arbricks' ),
					__( 'Suppress Message: Hides the standard "Product added to cart" notice.', 'arbricks' ),
					__( 'Disable Order Again: Optionally removes the "Order Again" button from order history to maintain focus on current purchases.', 'arbricks' ),
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
			'redirect_to_checkout'       => array(
				'type'        => 'checkbox',
				'label'       => __( 'Redirect to Checkout', 'arbricks' ),
				'description' => __( 'Skip cart, go directly to checkout', 'arbricks' ),
				'default'     => true,
			),
			'suppress_add_to_cart_message' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Suppress Add-to-Cart Message', 'arbricks' ),
				'description' => __( 'Hide "Product added to cart" notice', 'arbricks' ),
				'default'     => true,
			),
			'empty_cart_before_add'      => array(
				'type'        => 'checkbox',
				'label'       => __( 'Empty Cart Before Adding', 'arbricks' ),
				'description' => __( 'Clear cart before adding new product', 'arbricks' ),
				'default'     => true,
			),
			'disable_order_again_button' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Disable Order Again Button', 'arbricks' ),
				'description' => __( 'Remove "Order Again" button from orders', 'arbricks' ),
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
		// Only register if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$settings = Options::get_feature_settings( self::id() );

		if ( ! empty( $settings['redirect_to_checkout'] ) ) {
			add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'redirect_to_checkout' ) );
		}

		if ( ! empty( $settings['suppress_add_to_cart_message'] ) ) {
			add_filter( 'wc_add_to_cart_message_html', '__return_empty_string' );
		}

		if ( ! empty( $settings['empty_cart_before_add'] ) ) {
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'empty_cart_before_add' ), 10, 3 );
		}

		if ( ! empty( $settings['disable_order_again_button'] ) ) {
			add_action( 'init', array( $this, 'disable_order_again_button' ) );
		}
	}

	/**
	 * Redirect to checkout after adding to cart
	 *
	 * @param string $url Default redirect URL.
	 * @return string Checkout URL.
	 */
	public function redirect_to_checkout( $url ) {
		return wc_get_checkout_url();
	}

	/**
	 * Empty cart before adding new product
	 *
	 * @param bool $passed Validation status.
	 * @param int  $product_id Product ID.
	 * @param int  $quantity Quantity.
	 * @return bool
	 */
	public function empty_cart_before_add( $passed, $product_id, $quantity ) {
		if ( ! WC()->cart->is_empty() ) {
			WC()->cart->empty_cart();
		}
		return $passed;
	}

	/**
	 * Disable order again button
	 *
	 * @return void
	 */
	public function disable_order_again_button(): void {
		remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
