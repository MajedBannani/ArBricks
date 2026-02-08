<?php
/**
 * Feature Registry
 *
 * Discovers, registers, and manages feature modules.
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks\Features;

use ArBricks\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Registry
 *
 * Manages feature registration and activation.
 */
class Feature_Registry {

	/**
	 * Registered features
	 *
	 * @var array<string, Feature_Interface>
	 */
	private $features = array();

	/**
	 * Register a feature
	 *
	 * @param Feature_Interface $feature Feature instance.
	 * @return void
	 */
	public function register_feature( Feature_Interface $feature ): void {
		$id = $feature::id();
		$this->features[ $id ] = $feature;
	}

	/**
	 * Get all registered features
	 *
	 * @return array<string, Feature_Interface>
	 */
	public function get_all_features(): array {
		return $this->features;
	}

	/**
	 * Get enabled features
	 *
	 * @return array<Feature_Interface>
	 */
	public function get_enabled_features(): array {
		$enabled_ids = Options::get_enabled();
		$enabled     = array();

		foreach ( $this->features as $id => $feature ) {
			if ( isset( $enabled_ids[ $id ] ) && $enabled_ids[ $id ] ) {
				$enabled[] = $feature;
			}
		}

		return $enabled;
	}

	/**
	 * Auto-discover and register built-in features
	 *
	 * Scans /features/class-feature-*.php files and auto-registers them.
	 *
	 * DEVELOPER NOTE:
	 * All features MUST use the plugin namespace (ArBricks\Features) and text domain ('arbricks')
	 * from the main plugin bootstrap (arbricks.php). Do NOT hardcode new namespace or text domain values.
	 *
	 * @return void
	 */
	public function register_built_in_features(): void {
		// Try to get cached feature list.
		$cached_features = get_transient( 'arbricks_discovered_features' );
		
		if ( false !== $cached_features && is_array( $cached_features ) ) {
			// Use cached list - no filesystem access needed.
			$cache_valid = true;
			foreach ( $cached_features as $file => $full_class_name ) {
				if ( file_exists( $file ) ) {
					require_once $file;
					if ( class_exists( $full_class_name ) ) {
						$feature = new $full_class_name();
						if ( $feature instanceof Feature_Interface ) {
							$this->register_feature( $feature );
						}
					}
				} else {
					$cache_valid = false;
				}
			}
			
			if ( $cache_valid ) {
				return;
			}
			// If cache was invalid (file missing), continue to rediscovery.
		}

		// Cache miss - discover features from filesystem.
		$features_dir = ARBRICKS_PLUGIN_DIR . 'includes/features/';
		$pattern      = $features_dir . 'class-feature-*.php';
		$files        = glob( $pattern );

		if ( empty( $files ) || ! is_array( $files ) ) {
			return;
		}

		$feature_map = array();

		foreach ( $files as $file ) {
			require_once $file;

			// Extract class name from filename.
			// Pattern: class-feature-name.php -> Feature_Name.
			$filename   = basename( $file, '.php' );
			$class_part = str_replace( 'class-feature-', '', $filename );
			$class_name = str_replace( '-', '_', $class_part );
			$class_name = implode( '_', array_map( 'ucfirst', explode( '_', $class_name ) ) );

			// Build full class name.
			$full_class_name = '\\ArBricks\\Features\\Feature_' . $class_name;

			// Instantiate if class exists and implements interface.
			if ( class_exists( $full_class_name ) ) {
				$feature = new $full_class_name();
				if ( $feature instanceof Feature_Interface ) {
					$this->register_feature( $feature );
					// Store in cache map.
					$feature_map[ $file ] = $full_class_name;
				}
			}
		}

		// Cache discovered features for 12 hours.
		// Cache will be invalidated on plugin update via activation hook.
		set_transient( 'arbricks_discovered_features', $feature_map, 12 * HOUR_IN_SECONDS );
	}

	/**
	 * Apply all enabled features
	 *
	 * Calls register_hooks() on each enabled feature.
	 *
	 * @return void
	 */
	public function apply_enabled_features(): void {
		$enabled = $this->get_enabled_features();

		foreach ( $enabled as $feature ) {
			$feature->register_hooks();

			// Debug logging in WP_DEBUG mode.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$meta = $feature::meta();
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					sprintf(
						'[ArBricks] Feature "%s" hooks registered',
						$meta['title'] ?? $feature::id()
					)
				);
			}
		}
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
