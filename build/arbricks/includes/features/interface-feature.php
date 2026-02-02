<?php
/**
 * Feature Interface
 *
 * Contract for all feature modules.
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Feature_Interface
 *
 * All features must implement this interface.
 */
interface Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string;

	/**
	 * Get feature metadata
	 *
	 * @return array {
	 *     Feature metadata.
	 *
	 *     @type string $title       Feature title.
	 *     @type string $description Feature description.
	 *     @type string $category    Feature category (tools, styles, etc.).
	 *     @type string $shortcode   Optional shortcode tag if feature provides one.
	 * }
	 */
	public static function meta(): array;

	/**
	 * Register WordPress hooks
	 *
	 * Called only when feature is enabled.
	 *
	 * @return void
	 */
	public function register_hooks(): void;
}
