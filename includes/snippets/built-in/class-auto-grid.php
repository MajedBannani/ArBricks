<?php
/**
 * Auto Grid CSS Snippet
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
 * Class Auto_Grid
 */
class Auto_Grid extends Abstract_Snippet {

	/**
	 * Get snippet ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'auto_grid';
	}

	/**
	 * Get snippet label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Auto Grid CSS', 'arbricks' );
	}

	/**
	 * Get snippet description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Automatic adaptive grid system for responsive layouts.', 'arbricks' );
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
/* ArBricks Auto Grid */ 
:root {
  --auto-grid-3: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((3 - 1) * var(--space-m))) / 3) * 0.7, (100% - (3 - 1) * var(--space-m)) / 3)), 1fr));
  --auto-grid-2: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((2 - 1) * var(--space-m))) / 2) * 0.7, (100% - (2 - 1) * var(--space-m)) / 2)), 1fr));
  --auto-grid-4: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((4 - 1) * var(--space-m))) / 4) * 0.7, (100% - (4 - 1) * var(--space-m)) / 4)), 1fr));
  --auto-grid-5: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((5 - 1) * var(--space-m))) / 5) * 0.7, (100% - (5 - 1) * var(--space-m)) / 5)), 1fr));
  --auto-grid-6: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((6 - 1) * var(--space-m))) / 6) * 0.7, (100% - (6 - 1) * var(--space-m)) / 6)), 1fr));
  --auto-grid-7: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((7 - 1) * var(--space-m))) / 7) * 0.7, (100% - (7 - 1) * var(--space-m)) / 7)), 1fr));
  --auto-grid-8: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((8 - 1) * var(--space-m))) / 8) * 0.7, (100% - (8 - 1) * var(--space-m)) / 8)), 1fr));
  --auto-grid-9: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((9 - 1) * var(--space-m))) / 9) * 0.7, (100% - (9 - 1) * var(--space-m)) / 9)), 1fr));
  --auto-grid-10: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((10 - 1) * var(--space-m))) / 10) * 0.7, (100% - (10 - 1) * var(--space-m)) / 10)), 1fr));
  --auto-grid-11: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((11 - 1) * var(--space-m))) / 11) * 0.7, (100% - (11 - 1) * var(--space-m)) / 11)), 1fr));
  --auto-grid-12: repeat(auto-fit, minmax(min(100%, max(calc((var(--max-screen-width) - ((12 - 1) * var(--space-m))) / 12) * 0.7, (100% - (12 - 1) * var(--space-m)) / 12)), 1fr));
}
';

		$this->add_inline_css( $css, 'auto-grid' );
	}
}
