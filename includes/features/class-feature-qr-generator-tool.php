<?php
/**
 * Feature: QR Code Generator Tool
 *
 * Admin-only tool for generating QR codes from URLs (client-side).
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
 * Class Feature_Qr_Generator_Tool
 *
 * Provides QR code generation tool in admin (client-side using QRCode.js).
 */
class Feature_Qr_Generator_Tool implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'qr_generator_tool';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'QR Code Generator Tool', 'arbricks' ),
			'description' => __( 'Generate QR codes from URLs directly in admin interface.', 'arbricks' ),
			'category'    => 'tools',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Easily create QR codes for URLs. Perfect for marketing materials, print materials, and sharing links.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Enter the URL you want to encode.', 'arbricks' ),
					__( 'Choose the QR code size and error correction level.', 'arbricks' ),
					__( 'Click "Generate" to create the QR code.', 'arbricks' ),
					__( 'Download the QR code as PNG image.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Higher error correction allows QR codes to work even if partially damaged.', 'arbricks' ),
					__( 'Larger sizes are better for print materials.', 'arbricks' ),
					__( 'QR codes are generated instantly in your browser (no server processing).', 'arbricks' ),
				),
				'examples' => array(
					__( 'Error Correction L = Low (~7% recovery)', 'arbricks' ),
					__( 'Error Correction M = Medium (~15% recovery)', 'arbricks' ),
					__( 'Error Correction Q = Quartile (~25% recovery)', 'arbricks' ),
					__( 'Error Correction H = High (~30% recovery)', 'arbricks' ),
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
			'default_size' => array(
				'type'        => 'select',
				'label'       => __( 'Default QR Code Size', 'arbricks' ),
				'description' => __( 'Default size in pixels for generated QR codes.', 'arbricks' ),
				'options'     => array(
					'128' => __( '128px (Small)', 'arbricks' ),
					'256' => __( '256px (Medium)', 'arbricks' ),
					'400' => __( '400px (Large)', 'arbricks' ),
					'512' => __( '512px (Extra Large)', 'arbricks' ),
				),
				'default'     => '256',
			),
			'default_ecc'  => array(
				'type'        => 'select',
				'label'       => __( 'Default Error Correction', 'arbricks' ),
				'description' => __( 'Default error correction level.', 'arbricks' ),
				'options'     => array(
					'L' => __( 'Low (7%)', 'arbricks' ),
					'M' => __( 'Medium (15%)', 'arbricks' ),
					'Q' => __( 'Quartile (25%)', 'arbricks' ),
					'H' => __( 'High (30%)', 'arbricks' ),
				),
				'default'     => 'M',
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * No hooks needed - this is a client-side only tool.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Client-side tool - no server hooks needed.
	}

	/**
	 * Render custom admin UI for QR Generator
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		$settings = Options::get_feature_settings( self::id() );
		$size     = $settings['default_size'] ?? '256';
		$ecc      = $settings['default_ecc'] ?? 'M';
		?>
		<div class="arbricks-tool-qr-generator">
			<div class="tool-section">
				<label for="qr-url-input"><?php esc_html_e( 'Enter URL:', 'arbricks' ); ?></label>
				<input type="url" id="qr-url-input" class="widefat" placeholder="https://example.com" value="">
			</div>

			<div class="tool-section row">
				<div class="col">
					<label for="qr-size-select"><?php esc_html_e( 'Size:', 'arbricks' ); ?></label>
					<select id="qr-size-select">
						<option value="128" <?php selected( $size, '128' ); ?>><?php esc_html_e( '128px', 'arbricks' ); ?></option>
						<option value="256" <?php selected( $size, '256' ); ?>><?php esc_html_e( '256px', 'arbricks' ); ?></option>
						<option value="400" <?php selected( $size, '400' ); ?>><?php esc_html_e( '400px', 'arbricks' ); ?></option>
						<option value="512" <?php selected( $size, '512' ); ?>><?php esc_html_e( '512px', 'arbricks' ); ?></option>
					</select>
				</div>
				<div class="col">
					<label for="qr-ecc-select"><?php esc_html_e( 'Error Correction:', 'arbricks' ); ?></label>
					<select id="qr-ecc-select">
						<option value="L" <?php selected( $ecc, 'L' ); ?>><?php esc_html_e( 'Low (7%)', 'arbricks' ); ?></option>
						<option value="M" <?php selected( $ecc, 'M' ); ?>><?php esc_html_e( 'Medium (15%)', 'arbricks' ); ?></option>
						<option value="Q" <?php selected( $ecc, 'Q' ); ?>><?php esc_html_e( 'Quartile (25%)', 'arbricks' ); ?></option>
						<option value="H" <?php selected( $ecc, 'H' ); ?>><?php esc_html_e( 'High (30%)', 'arbricks' ); ?></option>
					</select>
				</div>
			</div>

			<div class="tool-actions">
				<button type="button" id="qr-generate-btn" class="button button-primary">
					<?php esc_html_e( 'Generate QR Code', 'arbricks' ); ?>
				</button>
				<button type="button" id="qr-download-btn" class="button" style="display:none;">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Download PNG', 'arbricks' ); ?>
				</button>
			</div>

			<div class="qr-preview empty" data-placeholder="<?php esc_attr_e( 'QR code will appear here...', 'arbricks' ); ?>"></div>
			<div class="tool-notice" style="display:none;"></div>
		</div>
		<?php
	}
}
