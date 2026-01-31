<?php
/**
 * WebP Converter Snippet
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
 * Class Webp_Converter
 */
class Webp_Converter extends Abstract_Snippet {

	/**
	 * Get snippet ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'webp_converter';
	}

	/**
	 * Get snippet label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'WebP Converter', 'arbricks' );
	}

	/**
	 * Get snippet description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Convert images to WebP format for better performance.', 'arbricks' );
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
		$this->register_shortcode( 'webp-converter', array( $this, 'render_shortcode' ) );
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
			'jszip',
			'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
			array(),
			'3.10.1',
			true
		);

		ob_start();
		?>
		<style>
			.webp-container {
				display: flex;
				flex-direction: column;
				gap: var(--space-s, 1rem);
			}

			input[type="file"] {
				margin-block-end: var(--space-s, 1rem);
			}

			.webp-container__btn:disabled {
				background-color: #ccc;
				cursor: not-allowed;
			}

			.webp-container__output-wrapper {
				margin-block-start: var(--space-s, 1rem);
				display: none;
			}
		</style>

		<div class="webp-container">
			<p class="webp-container__text"><?php esc_html_e( 'Upload up to 20 images to convert to WebP format and download as ZIP.', 'arbricks' ); ?></p>
			<input class="webp-container__input input" type="file" id="fileInput" accept="image/*" multiple />
			<button class="webp-container__btn btn" id="convertButton" disabled><?php esc_html_e( 'Convert Images', 'arbricks' ); ?></button>
			<div class="webp-container__output-wrapper output" id="output">
				<p class="webp-container__output-text" id="status"></p>
			</div>
		</div>

		<script>
		const fileInput = document.getElementById("fileInput");
		const convertButton = document.getElementById("convertButton");
		const status = document.getElementById("status");
		const output = document.getElementById("output");
		const downloadLink = document.createElement("a");

		downloadLink.textContent = "<?php echo esc_js( __( 'Download ZIP', 'arbricks' ) ); ?>";
		downloadLink.style.display = "none";
		output.appendChild(downloadLink);

		const allowedExtensions = ["image/png", "image/jpeg", "image/jpg", "image/gif"];
		const maxFileSize = 5 * 1024 * 1024; // 5 MB
		const webpQuality = 0.8; // 80% quality

		fileInput.addEventListener("change", () => {
			const files = Array.from(fileInput.files);
			const invalidFiles = files.filter(
				(file) => !allowedExtensions.includes(file.type) || file.size > maxFileSize
			);

			if (invalidFiles.length > 0) {
				convertButton.disabled = true;
				status.textContent = "<?php echo esc_js( __( 'Some files are invalid. Allowed: PNG, JPG, GIF. Max size: 5 MB.', 'arbricks' ) ); ?>";
			} else if (files.length > 20) {
				convertButton.disabled = true;
				status.textContent = "<?php echo esc_js( __( 'Maximum 20 files allowed.', 'arbricks' ) ); ?>";
			} else {
				convertButton.disabled = false;
				status.textContent = `${files.length} <?php echo esc_js( __( 'file(s) selected', 'arbricks' ) ); ?>`;
			}
		});

		convertButton.addEventListener("click", async () => {
			const files = Array.from(fileInput.files);
			const zip = new JSZip();
			const webpFolder = zip.folder("webp_images");

			status.textContent = "<?php echo esc_js( __( 'Converting images, please wait...', 'arbricks' ) ); ?>";
			for (const file of files) {
				if (allowedExtensions.includes(file.type) && file.size <= maxFileSize) {
					const webpBlob = await convertToWebP(file);
					if (webpBlob) {
						const webpFileName = file.name.replace(/\.(png|jpg|jpeg|gif)$/i, ".webp");
						webpFolder.file(webpFileName, webpBlob);
					}
				}
			}

			status.textContent = "<?php echo esc_js( __( 'Creating ZIP file...', 'arbricks' ) ); ?>";
			const zipBlob = await zip.generateAsync({ type: "blob" });

			const url = URL.createObjectURL(zipBlob);
			downloadLink.href = url;
			downloadLink.download = "converted-images.zip";
			downloadLink.style.display = "inline";

			status.textContent = "<?php echo esc_js( __( 'Conversion successful! Click the link below to download your ZIP file.', 'arbricks' ) ); ?>";
			output.style.display = "flex";
		});

		function convertToWebP(file) {
			return new Promise((resolve) => {
				const reader = new FileReader();
				reader.onload = () => {
					const img = new Image();
					img.onload = () => {
						const canvas = document.createElement("canvas");
						canvas.width = img.width;
						canvas.height = img.height;
						const ctx = canvas.getContext("2d");
						ctx.drawImage(img, 0, 0);
						canvas.toBlob(
							(blob) => {
								resolve(blob);
							},
							"image/webp",
							webpQuality
						);
					};
					img.src = reader.result;
				};
				reader.readAsDataURL(file);
			});
		}
		</script>
		<?php
		return ob_get_clean();
	}
}
