<?php
/**
 * YouTube Timestamp Generator Snippet
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
 * Class Youtube_Timestamp
 */
class Youtube_Timestamp extends Abstract_Snippet {

	/**
	 * Get snippet ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'youtube_timestamp';
	}

	/**
	 * Get snippet label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'YouTube Timestamp Generator', 'arbricks' );
	}

	/**
	 * Get snippet description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Generate YouTube links with specific timestamps.', 'arbricks' );
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
		$this->register_shortcode( 'youtube-generator', array( $this, 'render_shortcode' ) );
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
		<style>
			.ar-yt-ts__form {
				display: flex;
				flex-direction: column;
				gap: 15px;
			}

			.ar-yt-ts__btn {
				cursor: pointer;
			}

			.ar-yt-ts__output-wrapper {
				margin-block-start: var(--space-s, 1rem);
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				gap: var(--space-s, 1rem);
			}

			.ar-yt-ts__output-text {
				text-align: center;
			}
		</style>

		<div class="ar-yt-ts">
			<h2 class="ar-yt-ts__title">
				<?php esc_html_e( 'YouTube Timestamp Link Generator', 'arbricks' ); ?>
			</h2>
			<form class="ar-yt-ts__form" id="timestampForm">
				<label class="ar-yt-ts__label" for="videoId"><?php esc_html_e( 'YouTube Video ID:', 'arbricks' ); ?></label>
				<input class="ar-yt-ts__input input" type="text" id="videoId" placeholder="<?php esc_attr_e( 'Enter video ID here', 'arbricks' ); ?>" required />

				<label class="ar-yt-ts__label" for="timestamp"><?php esc_html_e( 'Timestamp (start time):', 'arbricks' ); ?></label>
				<input class="ar-yt-ts__input input" type="text" id="timestamp" placeholder="<?php esc_attr_e( 'Enter time here e.g. 2:32', 'arbricks' ); ?>" required />

				<label class="ar-yt-ts__label" for="description"><?php esc_html_e( 'Description (optional):', 'arbricks' ); ?></label>
				<input class="ar-yt-ts__input input" type="text" id="description" placeholder="<?php esc_attr_e( 'Enter description here', 'arbricks' ); ?>" />

				<button class="ar-yt-ts__btn btn" type="submit"><?php esc_html_e( 'Generate Link', 'arbricks' ); ?></button>
			</form>

			<div class="ar-yt-ts__output-wrapper" id="output" style="display: none;">
				<h3 class="ar-yt-ts__output-title"><?php esc_html_e( 'Generated Link:', 'arbricks' ); ?></h3>
				<div class="ar-yt-ts__output-text" id="generatedLink"></div>
				<button class="ar-yt-ts__output-link btn" id="copyLink">
					<?php esc_html_e( 'Copy Link', 'arbricks' ); ?>
				</button>
			</div>
		</div>

		<script>
		document.getElementById("timestampForm").addEventListener("submit", (e) => {
			e.preventDefault();

			const videoId = document.getElementById("videoId").value.trim();
			const timestamp = document.getElementById("timestamp").value;
			const description = document.getElementById("description").value;
			const timeInSeconds = convertToSeconds(timestamp);

			if (videoId && timeInSeconds !== null) {
				const generatedLink = `https://www.youtube.com/watch?v=${videoId}&t=${timeInSeconds}s`;
				const outputContent = description
					? `<strong>${description}:</strong><br><a href="${generatedLink}" target="_blank">${generatedLink}</a>`
					: `<a href="${generatedLink}" target="_blank">${generatedLink}</a>`;

				document.getElementById("generatedLink").innerHTML = outputContent;
				document.getElementById("output").style.display = "flex";
			} else {
				alert("<?php echo esc_js( __( 'Please enter a valid ID and time format (e.g., 1:23 or 0:45)', 'arbricks' ) ); ?>");
			}
		});

		document.getElementById("copyLink").addEventListener("click", () => {
			const generatedLinkText = document.getElementById("generatedLink").textContent;
			navigator.clipboard.writeText(generatedLinkText).then(() => {
				alert("<?php echo esc_js( __( 'Link copied successfully', 'arbricks' ) ); ?>");
			});
		});

		function convertToSeconds(time) {
			const parts = time.split(":").map(Number);
			if (parts.length === 2) {
				return parts[0] * 60 + parts[1];
			} else if (parts.length === 3) {
				return parts[0] * 3600 + parts[1] * 60 + parts[2];
			}
			return null;
		}
		</script>
		<?php
		return ob_get_clean();
	}
}
