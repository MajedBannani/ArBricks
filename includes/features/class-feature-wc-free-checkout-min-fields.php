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
			'description' => __( 'Reduce checkout fields when cart is free (no payment required).', 'arbricks' ),
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
			'keep_fields' => array(
				'type'        => 'text',
				'label'       => __( 'Fields to Keep', 'arbricks' ),
				'description' => __( 'Comma-separated billing field keys (e.g., billing_email,billing_first_name)', 'arbricks' ),
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
}
