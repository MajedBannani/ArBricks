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

		// Load WFPCore helper if exists.
		if ( file_exists( ARBRICKS_PLUGIN_DIR . 'WFPCore/WordPressContext.php' ) ) {
			require_once ARBRICKS_PLUGIN_DIR . 'WFPCore/WordPressContext.php';
		}
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Initialize components.
		add_action( 'init', array( $this, 'init_components' ), 0 );

		// Apply snippets after all plugins loaded.
		add_action( 'plugins_loaded', array( $this, 'apply_snippets' ), 20 );
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
	}

	/**
	 * Apply enabled snippets
	 *
	 * Called after plugins_loaded to ensure all WordPress functions are available.
	 *
	 * @return void
	 */
	public function apply_snippets() {
		if ( null !== $this->snippet_registry ) {
			$this->snippet_registry->apply_enabled_snippets();
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
		// Trigger migration if needed.
		Options::get_all();

		// Flush rewrite rules if needed in future.
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Clean up if needed.
		flush_rewrite_rules();
	}
}
