<?php
/**
 * Feature: Block PHP Uploads
 *
 * Prevents uploading or executing PHP files within the uploads directory.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Block_PHP_Uploads
 */
class Feature_ArBricks_Block_PHP_Uploads implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_block_php_uploads';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Block PHP Uploads', 'arbricks' ),
			'description' => __( 'Prevent uploading PHP files to the uploads directory for security.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary'  => __( 'Allowing PHP file uploads is a critical security risk that could allow attackers to execute malicious code on your server. This feature prevents uploading any file with a PHP-related extension.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature using the toggle above.', 'arbricks' ),
					__( 'Attempting to upload a PHP file via the Media Library will be rejected with an error message.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Blocked extensions: .php, .phtml, .php3, .php4, .php5, .phar.', 'arbricks' ),
					__( 'Does not prevent uploading images or standard documents (jpg, png, pdf, etc.).', 'arbricks' ),
					__( 'Does not scan previously uploaded files; focuses on new uploads.', 'arbricks' ),
					__( 'Important: If you use a plugin that legitimately needs to upload PHP files (very rare), you may need to disable this.', 'arbricks' ),
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
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'block_php_upload_prefilter' ) );
	}

	/**
	 * Filter upload attempt to block PHP files
	 *
	 * @param array $file File data array.
	 * @return array
	 */
	public function block_php_upload_prefilter( $file ) {
		$filename = isset( $file['name'] ) ? $file['name'] : '';
		
		if ( empty( $filename ) ) {
			return $file;
		}

		// List of blocked extensions.
		$blocked_extensions = array( 'php', 'phtml', 'php3', 'php4', 'php5', 'phar' );
		
		foreach ( $blocked_extensions as $ext ) {
			if ( preg_match( '/\.' . preg_quote( $ext, '/' ) . '$/i', $filename ) ) {
				$file['error'] = __( 'Sorry, uploading PHP files is not allowed for security reasons.', 'arbricks' );
				break;
			}
		}

		return $file;
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
