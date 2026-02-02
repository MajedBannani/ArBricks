<?php
/**
 * Plugin Name:     arbricks
 * Plugin URI:      https://arbricks.net/
 * Description:     استمتع بأسهل طريقة لإضافة أنماط وأدوات احترافية إلى موقع ووردبريس الخاص بك
 * Version:         2.0.10
 * Author:          Majed | ArBricks
 * Author URI:      https://arbricks.net/
 * Text Domain:     arbricks
 * Domain Path:     /languages
 * Update URI:      https://github.com/MajedBannani/arbricks-plugin
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
define( 'ARBRICKS_VERSION', '2.0.10' );
define( 'ARBRICKS_PLUGIN_FILE', __FILE__ );
define( 'ARBRICKS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARBRICKS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ARBRICKS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ARBRICKS_PLUGIN_SLUG', 'arbricks' );
define( 'ARBRICKS_GITHUB_REPO_URL', 'https://github.com/MajedBannani/arbricks-plugin' );
define( 'ARBRICKS_GITHUB_TOKEN', '' ); // Optional: for private repos.

// Load Options class first (needed by activation hook).
require_once ARBRICKS_PLUGIN_DIR . 'includes/class-options.php';

// Load main plugin class.
require_once ARBRICKS_PLUGIN_DIR . 'includes/class-plugin.php';

// Load GitHub Updater.
require_once ARBRICKS_PLUGIN_DIR . 'includes/class-github-updater.php';

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


	// Start plugin.
	\ArBricks\Plugin::instance();

	// Initialize GitHub Updater.
	if ( class_exists( 'ArBricks_GitHub_Updater' ) ) {
		new \ArBricks_GitHub_Updater();
	}
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

/**
 * Register privacy policy content
 *
 * Informs site administrators about data processing by optional features.
 *
 * @return void
 */
function arbricks_privacy_policy() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content = sprintf(
		'<h2>%s</h2>' .
		'<p>%s</p>' .
		'<h3>%s</h3>' .
		'<p><strong>%s</strong><br>%s</p>' .
		'<ul>' .
		'<li>%s</li>' .
		'<li>%s</li>' .
		'<li>%s</li>' .
		'<li>%s</li>' .
		'</ul>' .
		'<p><strong>%s</strong><br>%s</p>' .
		'<ul>' .
		'<li>%s</li>' .
		'<li>%s</li>' .
		'<li>%s</li>' .
		'</ul>' .
		'<p>%s</p>',
		esc_html__( 'ArBricks Plugin', 'arbricks' ),
		esc_html__( 'By default, this plugin does not collect or transmit any personal data. However, certain optional features may transmit data to third-party services when enabled:', 'arbricks' ),
		esc_html__( 'Optional External Services', 'arbricks' ),
		esc_html__( 'Google reCAPTCHA (Login Protection)', 'arbricks' ),
		esc_html__( 'If you enable the Google reCAPTCHA feature for login protection:', 'arbricks' ),
		esc_html__( 'Data sent: User IP address and browser fingerprint', 'arbricks' ),
		esc_html__( 'Purpose: Bot detection and spam prevention', 'arbricks' ),
		esc_html__( 'Service: Google LLC - Privacy Policy: https://policies.google.com/privacy', 'arbricks' ),
		esc_html__( 'Control: You must manually enable this feature and provide API keys', 'arbricks' ),
		esc_html__( 'Google Tag Manager (Marketing & Analytics)', 'arbricks' ),
		esc_html__( 'If you enable the Google Tag Manager feature:', 'arbricks' ),
		esc_html__( 'Data sent: Depends on your GTM container configuration', 'arbricks' ),
		esc_html__( 'Purpose: Analytics and marketing tracking as configured by you', 'arbricks' ),
		esc_html__( 'Service: Google LLC - Privacy Policy: https://policies.google.com/privacy', 'arbricks' ),
		esc_html__( 'All features using external services are disabled by default and require manual configuration. You are responsible for updating your privacy policy if you enable any of these features.', 'arbricks' )
	);

	wp_add_privacy_policy_content(
		__( 'ArBricks', 'arbricks' ),
		wp_kses_post( $content )
	);
}
add_action( 'admin_init', 'arbricks_privacy_policy' );

