<?php
/**
 * QR Code Generator Snippet
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks\Snippets\Built_In;

use ArBricks\Snippets\Abstract_Snippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Qr_Generator
 */
class Qr_Generator extends Abstract_Snippet {

	/**
	 * Get snippet ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'qr_generator';
	}

	/**
	 * Get snippet label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'QR Code Generator', 'arbricks' );
	}

	/**
	 * Get snippet description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Generate QR codes for any URL with a simple shortcode.', 'arbricks' );
	}

	/**
	 * Get snippet category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'tools';
	}

	/**
	 * Apply snippet functionality
	 *
	 * @return void
	 */
	public function apply() {
		$this->register_shortcode( 'qr-generator', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render shortcode
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function render_shortcode( $atts, $content = '' ) {
		wp_enqueue_script(
			'qrcode-js',
			'https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js',
			array(),
			null,
			true
		);

		ob_start();
		?>
		<style>
			.qr-generator {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				gap: var(--space-s, 1rem);
			}

			.qr-container__image-wrapper {
				inline-size: 100%;
				max-inline-size: 400px;
			}

			#qrCanvas {
				inline-size: 100% !important;
				block-size: auto !important;
			}
		</style>

		<div class="qr-generator">
			<p class="qr-generator__text"><?php esc_html_e( 'Enter the URL to convert, then click generate:', 'arbricks' ); ?></p>
			<input lang="en" dir="ltr" class="qr-generator__input input" type="text" id="urlInput" placeholder="<?php esc_attr_e( 'Enter URL to generate QR code', 'arbricks' ); ?>">
			<br>
			<button class="qr-generator__btn btn" onclick="generateQRCode()"><?php esc_html_e( 'Generate Code', 'arbricks' ); ?></button>
			<div class="qr-container__image-wrapper">
				<canvas class="qr-generator__image" id="qrCanvas"></canvas>
				<div class="my-logo" id="mylogoImage"></div>
			</div>
		</div>

		<script>
		function generateQRCode() {
			const url = document.getElementById("urlInput").value;
			if (!url) {
				alert("<?php echo esc_js( __( 'Please enter a valid URL', 'arbricks' ) ); ?>");
				return;
			}
			const canvas = document.getElementById("qrCanvas");
			QRCode.toCanvas(
				canvas,
				url,
				{
					width: 400,
					height: 400
				},
				function (error) {
					if (error) {
						console.error(error);
						alert("<?php echo esc_js( __( 'An error occurred', 'arbricks' ) ); ?>");
					}
				}
			);
		}
		</script>
		<?php
		return ob_get_clean();
	}
}
