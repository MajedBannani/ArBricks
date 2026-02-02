<?php
/**
 * Plugin Name:     بريكس بالعربي
 * Plugin URI:      https://arbricks.net/
 * Description:     استمتع بأسهل طريقة لإضافة أنماط وأدوات احترافية إلى موقع ووردبريس الخاص بك
 * Version:         2.0.0
 * Author:          Majed | ArBricks
 * Author URI:      https://arbricks.net/
 * Text Domain:     arbricks
 * Domain Path:     /languages
 * Requires PHP:    7.4
 * Requires at least: 5.8
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package ArBricks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Plugin constants.
define( 'ARBRICKS_VERSION', '2.0.0' );
define( 'ARBRICKS_PLUGIN_FILE', __FILE__ );
define( 'ARBRICKS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARBRICKS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ARBRICKS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load Options class first (needed by activation hook).
require_once ARBRICKS_PLUGIN_DIR . 'includes/class-options.php';

// Load main plugin class.
require_once ARBRICKS_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initialize plugin
 *
 * Loads text domain and starts the plugin.
 *
 * @return void
 */
function arbricks_init() {
	// Load translations.
	load_plugin_textdomain(
		'arbricks',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	// Debug: Comprehensive i18n verification.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[ArBricks] ===== i18n DEBUG START =====' );
		error_log( '[ArBricks] Locale (determine): ' . determine_locale() );
		error_log( '[ArBricks] User Locale: ' . get_user_locale() );
		error_log( '[ArBricks] Textdomain loaded: ' . ( is_textdomain_loaded( 'arbricks' ) ? 'YES' : 'NO' ) );
		error_log( '[ArBricks] Test "Tools": ' . __( 'Tools', 'arbricks' ) );
		error_log( '[ArBricks] Test "Security": ' . __( 'Security', 'arbricks' ) );
		error_log( '[ArBricks] Test "Enabled": ' . __( 'Enabled', 'arbricks' ) );
		error_log( '[ArBricks] Test "Save Changes": ' . __( 'Save Changes', 'arbricks' ) );
		
		$locale = determine_locale();
		$mofile = dirname( __FILE__ ) . "/languages/arbricks-{$locale}.mo";
		error_log( '[ArBricks] .mo file path: ' . $mofile );
		error_log( '[ArBricks] .mo exists: ' . ( file_exists( $mofile ) ? 'YES' : 'NO' ) );
		if ( file_exists( $mofile ) ) {
			error_log( '[ArBricks] .mo size: ' . filesize( $mofile ) . ' bytes' );
		}
		error_log( '[ArBricks] ===== i18n DEBUG END =====' );
	}

	// Start plugin.
	\ArBricks\Plugin::instance();
}
add_action( 'plugins_loaded', 'arbricks_init' );

/**
 * Activation hook
 *
 * @return void
 */
function arbricks_activate() {
	\ArBricks\Plugin::activate();
}
register_activation_hook( __FILE__, 'arbricks_activate' );

/**
 * Deactivation hook
 *
 * @return void
 */
function arbricks_deactivate() {
	\ArBricks\Plugin::deactivate();
}
register_deactivation_hook( __FILE__, 'arbricks_deactivate' );
