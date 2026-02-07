<?php
/**
 * Feature: QR Code Generator
 *
 * Admin-only QR Code generator tool.
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
 * Class Feature_Arbricks_QR_Generator
 */
class Feature_Arbricks_QR_Generator implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_qr_generator';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'QR Code Generator', 'arbricks' ),
			'description' => __( 'Provide an admin-only QR Code generator tool inside the feature card.', 'arbricks' ),
			'category'    => 'tools',
			'help'        => array(
				'summary'  => __( 'Generate QR codes from URLs directly in the admin interface.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enter the URL you want to convert into a QR code.', 'arbricks' ),
					__( 'Select the desired size for the QR code.', 'arbricks' ),
					__( 'Click "Generate QR" to preview the result.', 'arbricks' ),
					__( 'Click "Download QR" to save the image to your computer.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'This tool is for administrative use and does not output anything on the frontend.', 'arbricks' ),
					__( 'QR codes are generated locally in your browser.', 'arbricks' ),
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
		return array();
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		}
	}

	/**
	 * Enqueue admin assets
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		// Only load on ArBricks settings page to keep it clean.
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_arbricks-settings' !== $screen->id ) {
			return;
		}

		wp_enqueue_script(
			'arbricks-vendor-qrcode',
			ARBRICKS_PLUGIN_URL . 'assets/vendor/qrcode/qrcode.min.js',
			array(),
			'1.0.0',
			true
		);

		wp_enqueue_script(
			'arbricks-qr-generator',
			ARBRICKS_PLUGIN_URL . 'assets/js/arbricks-qr-generator.js',
			array( 'arbricks-vendor-qrcode', 'jquery' ),
			ARBRICKS_VERSION,
			true
		);

		wp_localize_script(
			'arbricks-qr-generator',
			'arbricksQRGenerator',
			array(
				'error_no_url' => __( 'Please enter a valid URL', 'arbricks' ),
			)
		);
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		?>
		<div class="arbricks-qr-generator-tool">
			<div class="arbricks-tool-field">
				<label for="arbricks-qr-url"><?php esc_html_e( 'URL', 'arbricks' ); ?></label>
				<input type="text" id="arbricks-qr-url" class="widefat" dir="ltr" lang="en" placeholder="https://example.com">
			</div>

			<div class="arbricks-tool-field">
				<label for="arbricks-qr-size"><?php esc_html_e( 'Size', 'arbricks' ); ?></label>
				<select id="arbricks-qr-size" class="widefat">
					<option value="200">200x200</option>
					<option value="300" selected>300x300</option>
					<option value="400">400x400</option>
					<option value="500">500x500</option>
				</select>
			</div>

			<div class="arbricks-tool-actions" style="margin-top: 15px; display: flex; gap: 10px;">
				<button type="button" id="arbricks-qr-generate" class="button button-primary">
					<?php esc_html_e( 'Generate QR', 'arbricks' ); ?>
				</button>
				<button type="button" id="arbricks-qr-download" class="button" style="display: none;">
					<?php esc_html_e( 'Download QR', 'arbricks' ); ?>
				</button>
			</div>

			<div id="arbricks-qr-output" style="margin-top: 20px; text-align: center; min-height: 100px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; border: 1px dashed #ccc; border-radius: 4px;">
				<span class="description"><?php esc_html_e( 'QR code will appear here', 'arbricks' ); ?></span>
			</div>
		</div>
		<style>
			.arbricks-qr-generator-tool .arbricks-tool-field { margin-bottom: 15px; }
			.arbricks-qr-generator-tool label { display: block; margin-bottom: 5px; font-weight: 600; }
			#arbricks-qr-output img { max-width: 100%; height: auto; border: 1px solid #ddd; padding: 5px; background: #fff; }
		</style>
		<?php
	}
}
