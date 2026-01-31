<?php
/**
 * Snippet registry
 *
 * Discovers, registers, and manages all snippets.
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks\Snippets;

use ArBricks\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Snippet_Registry
 *
 * Central registry for all snippet instances.
 */
class Snippet_Registry {

	/**
	 * Registered snippets
	 *
	 * @var Snippet_Interface[]
	 */
	private $snippets = array();

	/**
	 * Register a snippet
	 *
	 * @param Snippet_Interface $snippet Snippet instance.
	 * @return void
	 */
	public function register_snippet( Snippet_Interface $snippet ) {
		$this->snippets[ $snippet->get_id() ] = $snippet;
	}

	/**
	 * Get all registered snippets
	 *
	 * @return Snippet_Interface[]
	 */
	public function get_all_snippets() {
		return $this->snippets;
	}

	/**
	 * Get enabled snippets
	 *
	 * @return Snippet_Interface[]
	 */
	public function get_enabled_snippets() {
		$enabled_ids = Options::get_enabled();
		$enabled     = array();

		foreach ( $this->snippets as $id => $snippet ) {
			if ( isset( $enabled_ids[ $id ] ) && $enabled_ids[ $id ] ) {
				$enabled[ $id ] = $snippet;
			}
		}

		return $enabled;
	}

	/**
	 * Check if a snippet is registered
	 *
	 * @param string $snippet_id Snippet ID.
	 * @return bool
	 */
	public function is_registered( $snippet_id ) {
		return isset( $this->snippets[ $snippet_id ] );
	}

	/**
	 * Get a specific snippet
	 *
	 * @param string $snippet_id Snippet ID.
	 * @return Snippet_Interface|null
	 */
	public function get_snippet( $snippet_id ) {
		return isset( $this->snippets[ $snippet_id ] ) ? $this->snippets[ $snippet_id ] : null;
	}

	/**
	 * Register built-in snippets
	 *
	 * Auto-discover and register snippet classes in /built-in directory.
	 *
	 * @return void
	 */
	public function register_built_in_snippets() {
		$built_in_dir = ARBRICKS_PLUGIN_DIR . 'includes/snippets/built-in/';

		// Check if directory exists.
		if ( ! is_dir( $built_in_dir ) ) {
			return;
		}

		// Get all PHP files in built-in directory.
		$files = glob( $built_in_dir . 'class-*.php' );

		if ( empty( $files ) ) {
			return;
		}

		foreach ( $files as $file ) {
			require_once $file;

			// Extract class name from filename.
			// Pattern: class-snippet-name.php -> Snippet_Name.
			$filename   = basename( $file, '.php' );
			$class_part = str_replace( 'class-', '', $filename );
			$class_name = str_replace( '-', '_', $class_part );
			$class_name = implode( '_', array_map( 'ucfirst', explode( '_', $class_name ) ) );

			// Build full class name.
			$full_class_name = '\\ArBricks\\Snippets\\Built_In\\' . $class_name;

			// Instantiate if class exists and implements interface.
			if ( class_exists( $full_class_name ) ) {
				$snippet = new $full_class_name();
				if ( $snippet instanceof Snippet_Interface ) {
					$this->register_snippet( $snippet );
				}
			}
		}
	}

	/**
	 * Apply all enabled snippets
	 *
	 * Calls apply() on each enabled snippet.
	 *
	 * @return void
	 */
	public function apply_enabled_snippets() {
		$enabled = $this->get_enabled_snippets();

		foreach ( $enabled as $snippet ) {
			$snippet->apply();
		}
	}
}
