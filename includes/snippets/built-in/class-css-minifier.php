<?php
/**
 * CSS Minifier Snippet
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
 * Class Css_Minifier
 */
class Css_Minifier extends Abstract_Snippet {

	/**
	 * Get snippet ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'css_minifier';
	}

	/**
	 * Get snippet label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'CSS Minifier', 'arbricks' );
	}

	/**
	 * Get snippet description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Minify CSS code to reduce file size and improve performance.', 'arbricks' );
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
		$this->register_shortcode( 'css-minifier', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render shortcode
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function render_shortcode( $atts, $content = '' ) {
		ob_start();
		?>
		<div class="container">
			<label class="ar-yt-ts__label" for="css-input"><?php esc_html_e( 'Paste your CSS code here to minify...', 'arbricks' ); ?></label>
			<textarea id="css-input" dir="ltr"></textarea>
			<label class="ar-yt-ts__label" for="css-output"><?php esc_html_e( 'Here you will get the minified version of your CSS code', 'arbricks' ); ?></label>
			<textarea id="css-output" readonly dir="ltr"></textarea>
			<button class="btn" id="minify-button"><?php esc_html_e( 'Minify CSS Now', 'arbricks' ); ?></button>
		</div>

		<style>
			#css-output, #css-input {
				inline-size: 100%;
				block-size: 20rem;
				margin-block-end: var(--space-s, 1rem);
				padding: var(--space-s, 1rem);
			}
			#css-output::placeholder, #css-input::placeholder {
				color: var(--dark, #111);
			}
		</style>

		<script>
		document.getElementById("minify-button").addEventListener("click", function() {
			const cssInput = document.getElementById("css-input").value;
			const minified = cssInput
				.replace(/\/\*[\s\S]*?\*\//g, "") // Remove comments
				.replace(/\s+/g, " ") // Replace multiple spaces with single space
				.replace(/\s*{\s*/g, "{")
				.replace(/\s*}\s*/g, "}")
				.replace(/\s*;\s*/g, ";")
				.replace(/\s*:\s*/g, ":")
				.replace(/\s*,\s*/g, ",")
				.trim();
			document.getElementById("css-output").value = minified;
		});
		</script>
		<?php
		return ob_get_clean();
	}
}
