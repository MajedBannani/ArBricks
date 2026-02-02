<?php
/**
 * Snippet interface
 *
 * Defines the contract for all snippet classes.
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks\Snippets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Snippet_Interface
 *
 * Contract that all snippets must implement.
 */
interface Snippet_Interface {

	/**
	 * Get unique snippet ID
	 *
	 * @return string Unique identifier (slug format).
	 */
	public function get_id();

	/**
	 * Get snippet label
	 *
	 * @return string Human-readable label.
	 */
	public function get_label();

	/**
	 * Get snippet description
	 *
	 * @return string Short description of what the snippet does.
	 */
	public function get_description();

	/**
	 * Get snippet category
	 *
	 * @return string Category (e.g., 'tools', 'styles').
	 */
	public function get_category();

	/**
	 * Check if snippet is enabled by default
	 *
	 * @return bool True if should be enabled by default.
	 */
	public function is_default_enabled();

	/**
	 * Apply snippet functionality
	 *
	 * This method is called when the snippet is enabled.
	 * Register hooks, shortcodes, or add filters here.
	 *
	 * @return void
	 */
	public function apply();
}
