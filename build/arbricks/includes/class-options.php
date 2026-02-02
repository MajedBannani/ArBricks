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

		// If option doesn't exist or is old version, initialize.
		if ( empty( $options ) || ! isset( $options['version'] ) || $options['version'] < self::SCHEMA_VERSION ) {
			$options = self::initialize_defaults();
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
	 * Get feature settings
	 *
	 * @param string $feature_id Feature ID.
	 * @return array Feature settings.
	 */
	public static function get_feature_settings( $feature_id ) {
		$options = self::get_all();
		return isset( $options['settings'][ $feature_id ] ) && is_array( $options['settings'][ $feature_id ] )
			? $options['settings'][ $feature_id ]
			: array();
	}

	/**
	 * Set feature settings
	 *
	 * @param string $feature_id Feature ID.
	 * @param array  $settings Feature settings.
	 * @return void
	 */
	public static function set_feature_settings( $feature_id, $settings ) {
		$options = self::get_all();
		if ( ! isset( $options['settings'] ) || ! is_array( $options['settings'] ) ) {
			$options['settings'] = array();
		}
		$options['settings'][ $feature_id ] = $settings;
		update_option( self::OPTION_NAME, $options );
		self::clear_cache();
	}

	/**
	 * Get single feature setting
	 *
	 * @param string $feature_id Feature ID.
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	public static function get_feature_setting( $feature_id, $key, $default = null ) {
		$settings = self::get_feature_settings( $feature_id );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
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
	 * Initialize default options
	 *
	 * Creates the default option structure if it doesn't exist.
	 *
	 * @return array Default options.
	 */
	private static function initialize_defaults(): array {
		$defaults = array(
			'version'  => self::SCHEMA_VERSION,
			'enabled'  => array(),
			'settings' => array(),
		);

		update_option( self::OPTION_NAME, $defaults );
		return $defaults;
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
			// Snippets (shortcodes & CSS).
			'qr_generator'                     => false,
			'webp_converter'                   => false,
			'youtube_timestamp'                => false,
			'css_minifier'                     => false,
			'grid_layout'                      => false,
			'auto_grid'                        => false,
			'blob_css'                         => false,
			'noise_css'                        => false,
			// Features (hooks & filters).
			'webp_auto_convert_uploads'        => false,
			'math_captcha_login'               => false,
			'head_cleanup'                     => false,
			'hide_admin_bar_non_admin'         => false,
			'disable_comments_sitewide'        => false,
			'remove_wp_version'                => false,
			'disable_xmlrpc'                   => false,
			'google_tag_manager'               => false,
			'wc_free_checkout_min_fields'      => false,
			// WooCommerce Features.
			'wc_free_price_label'              => false,
			'wc_cart_auto_update'              => false,
			'wc_direct_checkout_single_item'   => false,
			'wc_minimum_order_amount'          => false,
			// New Features (SEO, Tools, Security).
			'media_auto_meta'                  => false,
			'shortcode_email'                  => false,
			'seo_visibility_warning'           => false,
			'login_recaptcha'                  => false,
			'login_honeypot'                   => false,
			// Admin-Only Tool Features.
			'qr_generator_tool'                => false,
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
