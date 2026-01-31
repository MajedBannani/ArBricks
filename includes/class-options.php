<?php
/**
 * Options management and data layer
 *
 * Handles reading/writing plugin options and migration from v1.
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Options
 *
 * Manages plugin options with backward compatibility migration.
 */
class Options {

	/**
	 * Option name for plugin settings
	 *
	 * @var string
	 */
	const OPTION_NAME = 'arbricks_options';

	/**
	 * Legacy option name (v1.x)
	 *
	 * @var string
	 */
	const LEGACY_OPTION_NAME = 'arbricks_features';

	/**
	 * Current schema version
	 *
	 * @var int
	 */
	const SCHEMA_VERSION = 2;

	/**
	 * Cached options
	 *
	 * @var array
	 */
	private static $cache = null;

	/**
	 * Get all options
	 *
	 * @return array Options array with version, enabled, and settings keys.
	 */
	public static function get_all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$options = get_option( self::OPTION_NAME, array() );

		// If option doesn't exist or is old version, migrate.
		if ( empty( $options ) || ! isset( $options['version'] ) || $options['version'] < self::SCHEMA_VERSION ) {
			$options = self::migrate();
		}

		self::$cache = $options;
		return $options;
	}

	/**
	 * Get enabled snippets
	 *
	 * @return array Associative array of snippet_id => bool.
	 */
	public static function get_enabled() {
		$options = self::get_all();
		return isset( $options['enabled'] ) ? $options['enabled'] : array();
	}

	/**
	 * Check if a specific snippet is enabled
	 *
	 * @param string $snippet_id Snippet identifier.
	 * @return bool True if enabled, false otherwise.
	 */
	public static function is_enabled( $snippet_id ) {
		$enabled = self::get_enabled();
		return isset( $enabled[ $snippet_id ] ) && $enabled[ $snippet_id ];
	}

	/**
	 * Get settings for a specific snippet
	 *
	 * @param string $snippet_id Snippet identifier.
	 * @return array Snippet settings or empty array.
	 */
	public static function get_snippet_settings( $snippet_id ) {
		$options = self::get_all();
		return isset( $options['settings'][ $snippet_id ] ) ? $options['settings'][ $snippet_id ] : array();
	}

	/**
	 * Save snippet enabled state
	 *
	 * @param string $snippet_id Snippet identifier.
	 * @param bool   $enabled Whether snippet is enabled.
	 * @return bool True on success, false on failure.
	 */
	public static function set_enabled( $snippet_id, $enabled ) {
		$options                           = self::get_all();
		$options['enabled'][ $snippet_id ] = (bool) $enabled;

		self::$cache = $options;
		return update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Save multiple snippet states at once
	 *
	 * @param array $states Associative array of snippet_id => bool.
	 * @return bool True on success, false on failure.
	 */
	public static function set_enabled_bulk( $states ) {
		$options = self::get_all();

		foreach ( $states as $snippet_id => $enabled ) {
			$options['enabled'][ $snippet_id ] = (bool) $enabled;
		}

		self::$cache = $options;
		return update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Save settings for a specific snippet
	 *
	 * @param string $snippet_id Snippet identifier.
	 * @param array  $settings Settings array.
	 * @return bool True on success, false on failure.
	 */
	public static function set_snippet_settings( $snippet_id, $settings ) {
		$options                             = self::get_all();
		$options['settings'][ $snippet_id ] = $settings;

		self::$cache = $options;
		return update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Migrate from v1.x to v2.x
	 *
	 * Detects legacy option structure and migrates to new schema.
	 *
	 * @return array Migrated options.
	 */
	private static function migrate() {
		// Check for legacy option.
		$legacy_features = get_option( self::LEGACY_OPTION_NAME, false );

		if ( false !== $legacy_features && is_array( $legacy_features ) ) {
			// Migrate from v1.x.
			$new_options = array(
				'version'  => self::SCHEMA_VERSION,
				'enabled'  => $legacy_features,
				'settings' => array(),
			);

			// Save new option.
			update_option( self::OPTION_NAME, $new_options );

			// Backup old option.
			update_option( self::LEGACY_OPTION_NAME . '_backup', $legacy_features );

			// Delete old option.
			delete_option( self::LEGACY_OPTION_NAME );

			return $new_options;
		}

		// No migration needed, return fresh options with defaults.
		return self::get_default_options();
	}

	/**
	 * Get default options structure
	 *
	 * @return array Default options.
	 */
	private static function get_default_options() {
		return array(
			'version'  => self::SCHEMA_VERSION,
			'enabled'  => self::get_default_enabled_state(),
			'settings' => array(),
		);
	}

	/**
	 * Get default enabled state for all snippets
	 *
	 * @return array Default enabled state (all false).
	 */
	private static function get_default_enabled_state() {
		return array(
			'qr_generator'     => false,
			'webp_converter'   => false,
			'youtube_timestamp' => false,
			'css_minifier'     => false,
			'grid_layout'      => false,
			'auto_grid'        => false,
			'blob_css'         => false,
			'noise_css'        => false,
		);
	}

	/**
	 * Clear options cache
	 *
	 * @return void
	 */
	public static function clear_cache() {
		self::$cache = null;
	}
}
