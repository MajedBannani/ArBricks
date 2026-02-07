<?php
/**
 * Feature: Hide Sensitive WP Files
 *
 * Prevents direct access to sensitive WordPress files.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Hide_Sensitive_WP_Files
 */
class Feature_ArBricks_Hide_Sensitive_WP_Files implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_hide_sensitive_wp_files';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Hide Sensitive WP Files', 'arbricks' ),
			'description' => __( 'Prevent direct access to sensitive WordPress files that might reveal system information.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary'  => __( 'WordPress contains default files that may reveal system version or server information to attackers. This feature prevents direct access to these files.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Access to sensitive files will be automatically blocked for direct requests.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Protected files: readme.html, readme.txt, install.php, wp-config.php, debug.log, error_log, .htaccess, .htpasswd.', 'arbricks' ),
					__( 'A 404 error (Not Found) is returned to suggest the file does not exist.', 'arbricks' ),
					__( 'This feature does not affect internal site operations or the admin panel.', 'arbricks' ),
					__( 'Important Note: If your server (Nginx/Apache) already blocks these files, you do not need to enable this feature.', 'arbricks' ),
				),
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Only run on frontend.
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		add_action( 'init', array( $this, 'block_sensitive_files' ) );
	}

	/**
	 * Block direct access to sensitive files
	 *
	 * @return void
	 */
	public function block_sensitive_files(): void {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		
		if ( empty( $request_uri ) ) {
			return;
		}

		// Remove query strings to check the base filename.
		$path = parse_url( $request_uri, PHP_URL_PATH );
		$file = basename( $path );

		$sensitive_files = array(
			'readme.html',
			'readme.txt',
			'install.php',
			'wp-config.php',
			'debug.log',
			'error_log',
			'.htaccess',
			'.htpasswd',
		);

		if ( in_array( strtolower( $file ), $sensitive_files, true ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			include get_query_template( '404' );
			exit;
		}
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
