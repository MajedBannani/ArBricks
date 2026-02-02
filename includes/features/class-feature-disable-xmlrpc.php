<?php
/**
 * Feature: Disable XML-RPC
 *
 * Disables XML-RPC functionality entirely and optionally blocks direct access.
 *
 * @package ArBricks
 * @since 2.0.1
 */

namespace ArBricks\Features;

use ArBricks\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Disable_Xmlrpc
 *
 * Disables XML-RPC with optional direct access blocking.
 */
class Feature_Disable_Xmlrpc implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'disable_xmlrpc';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Disable XML-RPC', 'arbricks' ),
			'description' => __( 'Disable XML-RPC completely and optionally block direct access.', 'arbricks' ),
			'category'    => 'security',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Disables WordPress XML-RPC functionality which is often targeted by brute force attacks and DDoS attempts. XML-RPC is a legacy remote access protocol rarely needed on modern sites.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above', 'arbricks' ),
					__( 'Optionally enable "Block Direct Access" to return 403 error when xmlrpc.php is accessed', 'arbricks' ),
					__( 'Click "Save Changes"', 'arbricks' ),
					__( 'XML-RPC is immediately disabled', 'arbricks' ),
				),
				'notes'    => array(
					__( 'XML-RPC is used by some mobile apps and legacy publishing tools (rarely needed today)', 'arbricks' ),
					__( 'Jetpack and some plugins may require XML-RPC - test after enabling', 'arbricks' ),
					__( 'Block Direct Access: Returns 403 Forbidden when xmlrpc.php is accessed directly', 'arbricks' ),
					__( 'Improves security by closing common attack vector', 'arbricks' ),
				),
			),
		);
	}

	/**
	 * Get settings schema
	 *
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'block_direct_xmlrpc' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Block Direct Access', 'arbricks' ),
				'description' => __( 'Return 403 when xmlrpc.php is accessed directly.', 'arbricks' ),
				'default'     => false,
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'xmlrpc_enabled', '__return_false', 99 );
		add_filter( 'xmlrpc_methods', '__return_empty_array', 99 );

		$settings = Options::get_feature_settings( self::id() );
		if ( ! empty( $settings['block_direct_xmlrpc'] ) ) {
			add_action( 'init', array( $this, 'block_xmlrpc_access' ), 1 );
		}
	}

	/**
	 * Block direct XML-RPC access
	 *
	 * @return void
	 */
	public function block_xmlrpc_access(): void {
		// Check if this is an XML-RPC request using WordPress constant.
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			http_response_code( 403 );
			wp_die(
				esc_html__( 'XML-RPC is disabled on this site.', 'arbricks' ),
				'',
				array( 'response' => 403 )
			);
		}
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
