<?php
/**
 * Feature: WooCommerce Free Checkout Minimal Fields
 *
 * Reduces checkout fields for free cart (no payment needed).
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
 * Class Feature_Wc_Free_Checkout_Min_Fields
 *
 * Simplifies checkout for free orders.
 */
class Feature_Wc_Free_Checkout_Min_Fields implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'wc_free_checkout_min_fields';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Free Checkout Minimal Fields', 'arbricks' ),
			'description' => __( 'Reduce checkout fields when cart is free (no payment needed).', 'arbricks' ),
			'category'    => 'woocommerce',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Simplifies the checkout process for free products by removing unnecessary billing fields (like address, phone, and company) when the cart total is zero. Reduces friction and increases conversions for free products or samples.', 'arbricks' ),
				'how_to'   => array(
					__( 'Ensure WooCommerce is installed and active.', 'arbricks' ),
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'In "Fields to Keep", enter a comma-separated list of field IDs you want to remain visible.', 'arbricks' ),
					__( 'Click "Save Changes".', 'arbricks' ),
					__( 'Try adding a free product to your cart and proceed to checkout to see the simplification.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Requires WooCommerce plugin.', 'arbricks' ),
					__( 'Only works when the cart total is 0.00 and no payment is required.', 'arbricks' ),
					__( 'Fields to Keep: Default is "billing_email,billing_first_name".', 'arbricks' ),
					__( 'Supported field IDs include: billing_first_name, billing_last_name, billing_company, billing_address_1, billing_city, billing_phone, etc.', 'arbricks' ),
					__( 'Great for building email lists through free digital giveaways.', 'arbricks' ),
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
			'keep_fields' => array(
				'type'        => 'text',
				'label'       => __( 'Fields to Keep', 'arbricks' ),
				'description' => __( 'Comma-separated field IDs (e.g., billing_email,billing_first_name)', 'arbricks' ),
				'default'     => 'billing_email,billing_first_name',
				'placeholder' => 'billing_email,billing_first_name',
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

		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_checkout_fields' ), 50 );
	}

	/**
	 * Filter checkout fields
	 *
	 * @param array $fields Checkout fields.
	 * @return array Modified fields.
	 */
	public function filter_checkout_fields( $fields ) {
		// Only modify if WC cart exists and doesn't need payment.
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->needs_payment() ) {
			return $fields;
		}

		if ( ! isset( $fields['billing'] ) || ! is_array( $fields['billing'] ) ) {
			return $fields;
		}

		$settings = Options::get_feature_settings( self::id() );
		$keep_csv = ! empty( $settings['keep_fields'] ) ? $settings['keep_fields'] : 'billing_email,billing_first_name';
		$keep     = array_map( 'trim', explode( ',', $keep_csv ) );

		foreach ( $fields['billing'] as $key => $field ) {
			if ( ! in_array( $key, $keep, true ) ) {
				unset( $fields['billing'][ $key ] );
			}
		}

		return $fields;
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
