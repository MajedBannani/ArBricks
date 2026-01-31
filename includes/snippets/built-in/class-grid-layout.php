<?php
/**
 * Grid Layout CSS Snippet
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
 * Class Grid_Layout
 */
class Grid_Layout extends Abstract_Snippet {

	/**
	 * Get snippet ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'grid_layout';
	}

	/**
	 * Get snippet label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Grid Layout CSS', 'arbricks' );
	}

	/**
	 * Get snippet description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Responsive grid layout system for page design.', 'arbricks' );
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

		$css = '
/* ArBricks Grid Layout */ 
:root {
  --content-max-width: var(--max-screen-width);
  --section-inline-padding: var(--space-m);
  --ar-grid-layout: [full-start] 1fr 
    [content-start first-start] minmax(0, calc(min(var(--content-max-width) / 2, (100vw - var(--section-inline-padding)) / 2))) 
    [first-end second-start] minmax(0, calc(min(var(--content-max-width) / 2, (100vw - var(--section-inline-padding)) / 2))) 
    [second-end content-end] 1fr
    [full-end];
}

.ar-grid-layout {
  --content-max-width: var(--max-screen-width);
  --section-inline-padding: var(--space-m);
  display: grid;
  grid-template-columns: 
    [full-start] 1fr 
    [content-start first-start] minmax(0, calc(min(var(--content-max-width) / 2, (100vw - var(--section-inline-padding)) / 2))) 
    [first-end second-start] minmax(0, calc(min(var(--content-max-width) / 2, (100vw - var(--section-inline-padding)) / 2))) 
    [second-end content-end] 1fr
    [full-end];
}
.ar-grid-layout > * {
  grid-column: content;
}
';

		$this->add_inline_css( $css, 'grid-layout' );
	}
}
