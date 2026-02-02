<?php
/**
 * Main plugin class
 *
 * Orchestrates all plugin components and initialization.
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Main plugin orchestrator using singleton pattern.
 */
class Plugin {

	/**
	 * Singleton instance
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Admin instance
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * Snippet Registry instance
	 *
	 * @var Snippets\Snippet_Registry
	 */
	public $snippet_registry;

	/**
	 * Feature Registry instance
	 *
	 * @var Features\Feature_Registry
	 */
	public $feature_registry;

	/**
	 * Get singleton instance
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * Private to enforce singleton pattern.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files
	 *
	 * @return void
	 */
	private function load_dependencies() {
		// Core classes.
		require_once ARBRICKS_PLUGIN_DIR . 'includes/class-options.php';
		require_once ARBRICKS_PLUGIN_DIR . 'includes/class-admin.php';

		// Snippet system.
		require_once ARBRICKS_PLUGIN_DIR . 'includes/snippets/interface-snippet.php';
		require_once ARBRICKS_PLUGIN_DIR . 'includes/snippets/abstract-snippet.php';
		require_once ARBRICKS_PLUGIN_DIR . 'includes/snippets/class-snippet-registry.php';

		// Feature system.
		require_once ARBRICKS_PLUGIN_DIR . 'includes/features/interface-feature.php';
		require_once ARBRICKS_PLUGIN_DIR . 'includes/features/class-feature-registry.php';
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Initialize components.
		add_action( 'init', array( $this, 'init_components' ), 0 );

		// Apply snippets on init (after components, before content processing).
		add_action( 'init', array( $this, 'apply_snippets' ), 10 );
	}

	/**
	 * Initialize plugin components
	 *
	 * @return void
	 */
	public function init_components() {
		// Initialize admin interface.
		if ( is_admin() ) {
			$this->admin = new Admin();
		}

		// Initialize snippet registry.
		$this->snippet_registry = new Snippets\Snippet_Registry();
		$this->snippet_registry->register_built_in_snippets();

		// Initialize feature registry.
		$this->feature_registry = new Features\Feature_Registry();
		$this->feature_registry->register_built_in_features();
	}

	/**
	 * Apply enabled snippets
	 *
	 * Called on init to ensure shortcodes are registered before content is processed.
	 *
	 * @return void
	 */
	public function apply_snippets() {
		// Apply snippets.
		if ( null !== $this->snippet_registry ) {
			$this->snippet_registry->apply_enabled_snippets();

			// Debug logging in WP_DEBUG mode.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$enabled = Options::get_enabled();
				foreach ( $enabled as $snippet_id => $is_enabled ) {
					if ( $is_enabled ) {
						$shortcode_map = array(
							'qr_generator'      => 'qr-generator',
							'webp_converter'    => 'webp-converter',
							'youtube_timestamp' => 'youtube-generator',
							'css_minifier'      => 'css-minifier',
						);
						
						if ( isset( $shortcode_map[ $snippet_id ] ) ) {
							$tag = $shortcode_map[ $snippet_id ];
							$exists = shortcode_exists( $tag );
							// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
							error_log( sprintf( '[ArBricks] Shortcode [%s] registered: %s', $tag, $exists ? 'YES' : 'NO' ) );
						}
					}
				}
			}
		}

		// Apply features.
		if ( null !== $this->feature_registry ) {
			$this->feature_registry->apply_enabled_features();
		}
	}

	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	public function get_version() {
		return ARBRICKS_VERSION;
	}

	/**
	 * Activation hook
	 *
	 * @return void
	 */
	public static function activate() {
		// Initialize plugin options.
		Options::get_all();

		// Clear feature discovery cache to force re-scan after update.
		delete_transient( 'arbricks_discovered_features' );
	}

	/**
	 * Deactivation hook
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Clean up scheduled actions if needed.
	}
}
