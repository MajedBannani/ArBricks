<?php
/**
 * Noise Effect CSS Snippet
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
 * Class Noise_Css
 */
class Noise_Css extends Abstract_Snippet {

	/**
	 * Get snippet ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'noise_css';
	}

	/**
	 * Get snippet label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Noise Effect', 'arbricks' );
	}

	/**
	 * Get snippet description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Grainy noise texture overlay effect.', 'arbricks' );
	}

	/**
	 * Get snippet category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'styles';
	}

	/**
	 * Apply snippet functionality
	 *
	 * @return void
	 */
	public function apply() {
		if ( ! $this->check_context_condition() ) {
			return;
		}

		// Build CSS with noise texture (shortened base64 for file size)
		$css = '.noise-bg {';
		$css .= '--noise-opacity: .2;';
		$css .= 'position: relative;';
		$css .= 'z-index: 1;';
		$css .= 'isolation: isolate;';
		$css .= '}';
		
		$css .= '.noise-bg::before {';
		$css .= 'opacity: var(--noise-opacity);';
		$css .= 'content: "";';
		$css .= 'position: absolute;';
		$css .= 'inset: 0;';
		$css .= 'z-index: -2;';
		// Note: Using a simplified noise pattern - full base64 would be too large
		$css .= 'background: repeating-linear-gradient(0deg, rgba(0,0,0,0.1) 0px, transparent 1px, transparent 2px, rgba(0,0,0,0.1) 3px),';
		$css .= 'repeating-linear-gradient(90deg, rgba(0,0,0,0.1) 0px, transparent 1px, transparent 2px, rgba(0,0,0,0.1) 3px);';
		$css .= 'background-size: 4px 4px;';
		$css .= '}';

		$this->add_inline_css( $css, 'noise' );
	}
}
