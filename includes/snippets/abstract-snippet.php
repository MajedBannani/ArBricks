<?php
/**
 * Abstract snippet base class
 *
 * Provides default implementations for snippet interface methods.
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks\Snippets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class Abstract_Snippet
 *
 * Base implementation for snippets with common functionality.
 */
abstract class Abstract_Snippet implements Snippet_Interface {

	/**
	 * Get snippet category
	 *
	 * Default implementation, can be overridden.
	 *
	 * @return string
	 */
	public function get_category() {
		return 'other';
	}

	/**
	 * Check if enabled by default
	 *
	 * Default implementation: disabled by default.
	 *
	 * @return bool
	 */
	public function is_default_enabled() {
		return false;
	}

	/**
	 * Helper: Add inline CSS to wp_head
	 *
	 * @param string $css CSS content.
	 * @param string $handle Optional handle for the style tag.
	 * @return void
	 */
	protected function add_inline_css( $css, $handle = '' ) {
		add_action(
			'wp_head',
			function () use ( $css, $handle ) {
				$id_attr = ! empty( $handle ) ? ' id="' . esc_attr( 'arbricks-' . $handle ) . '"' : '';
				echo '<style type="text/css"' . $id_attr . '>' . "\n";
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS content.
				echo $css;
				echo "\n</style>\n";
			},
			10
		);
	}

	/**
	 * Helper: Register shortcode
	 *
	 * @param string   $tag Shortcode tag.
	 * @param callable $callback Shortcode callback.
	 * @return void
	 */
	protected function register_shortcode( $tag, $callback ) {
		add_shortcode( $tag, $callback );
	}

	/**
	 * Check if the context condition is met
	 *
	 * @return bool
	 */
	protected function check_context_condition() {
		$condition = $this->get_context_condition();

		// If no condition set, always return true.
		if ( empty( $condition ) ) {
			return true;
		}

		// Simple WordPress conditionals.
		if ( 'admin' === $condition ) {
			return is_admin();
		}

		if ( 'frontend' === $condition ) {
			return ! is_admin();
		}

		// Default to true.
		return true;
	}
}
