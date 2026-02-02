<?php
/**
 * Feature: WooCommerce Free Price Label
 *
 * Displays custom label for zero-price products.
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
 * Class Feature_Wc_Free_Price_Label
 *
 * Custom price label for free products.
 */
class Feature_Wc_Free_Price_Label implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'wc_free_price_label';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Free Price Label (Zero Price)', 'arbricks' ),
			'description' => __( 'Display custom label for products with zero price.', 'arbricks' ),
			'category'    => 'woocommerce',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Replaces the default price display with custom text (e.g., "Free!", "No Charge", "مجاني") for WooCommerce products that have a price of zero. Perfect for giveaways, free samples, or promotional items.', 'arbricks' ),
				'how_to'   => array(
					__( 'Ensure WooCommerce is installed and active', 'arbricks' ),
					__( 'Enable the feature toggle above', 'arbricks' ),
					__( 'Enter your custom "Free Text Label" (e.g., "Free!", "Gratis", "مجاني")', 'arbricks' ),
					__( 'Click "Save Changes"', 'arbricks' ),
					__( 'Set any product price to 0 to see your custom label', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Requires WooCommerce plugin', 'arbricks' ),
					__( 'Only affects products with price exactly 0.00 (zero)', 'arbricks' ),
					__( 'If product is on sale (regular price > 0, sale price = 0), shows strikethrough with free label', 'arbricks' ),
					__( 'Works on shop pages, single product pages, and widgets', 'arbricks' ),
				),
				'examples' => array(
					__( 'English: "Free!" or "No Charge"', 'arbricks' ),
					__( 'Arabic: "مجاني" or "بدون تكلفة"', 'arbricks' ),
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
			'free_text' => array(
				'type'        => 'text',
				'label'       => __( 'Free Text Label', 'arbricks' ),
				'description' => __( 'Text to display for free products', 'arbricks' ),
				'default'     => __( 'Free!', 'arbricks' ),
				'placeholder' => __( 'Free!', 'arbricks' ),
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

		add_filter( 'woocommerce_get_price_html', array( $this, 'filter_price_html' ), 10, 2 );
	}

	/**
	 * Filter price HTML for free products
	 *
	 * @param string     $price Price HTML.
	 * @param WC_Product $product Product object.
	 * @return string Modified price HTML.
	 */
	public function filter_price_html( $price, $product ) {
		$settings  = Options::get_feature_settings( self::id() );
		$free_text = ! empty( $settings['free_text'] ) ? $settings['free_text'] : __( 'Free!', 'arbricks' );

		// Get product price.
		$product_price = (float) $product->get_price();

		// If price is zero or less, show free label.
		if ( $product_price <= 0 ) {
			// Check if on sale with regular price.
			if ( $product->is_on_sale() && $product->get_regular_price() > 0 ) {
				$regular_price = wc_price( $product->get_regular_price() );
				return sprintf(
					'<del aria-hidden="true">%s</del> <ins>%s</ins>',
					wp_kses_post( $regular_price ),
					esc_html( $free_text )
				);
			}

			return '<span class="woocommerce-Price-amount amount">' . esc_html( $free_text ) . '</span>';
		}

		return $price;
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
