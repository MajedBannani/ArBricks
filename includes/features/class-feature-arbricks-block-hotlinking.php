<?php
/**
 * Feature: Block Hotlinking
 *
 * Prevents other websites from using your media files directly.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

use ArBricks\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Block_Hotlinking
 */
class Feature_ArBricks_Block_Hotlinking implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_block_hotlinking';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Block Hotlinking', 'arbricks' ),
			'description' => __( 'Prevent other websites from using your media files directly, saving your bandwidth and server resources.', 'arbricks' ),
			'category'    => 'performance',
			'help'        => array(
				'summary'  => __( 'Hotlinking is when other sites use your image links within their pages, consuming your bandwidth instead of theirs. This feature prevents unauthorized access to your media files.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the main feature and choose your preferred blocking method.', 'arbricks' ),
					__( 'You can choose to show a 403 error or display a placeholder image (like your logo).', 'arbricks' ),
					__( 'Add friendly domains you want to allow to the whitelist.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Search engines (Google, Bing, etc.) and direct access are automatically allowed to ensure SEO is not affected.', 'arbricks' ),
					__( 'Warning: If you use a CDN or a separate media domain, make sure to add it to the whitelist.', 'arbricks' ),
					__( 'Note: This feature works via PHP and may have a very small performance impact on very large sites; in those cases, server-level protection is preferred.', 'arbricks' ),
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
			'arbricks_hotlink_allow_search_engines' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Allow Search Engines', 'arbricks' ),
				'description' => __( 'Allow search engines like Google and Bing to display your images.', 'arbricks' ),
				'default'     => true,
			),
			'arbricks_hotlink_whitelist'            => array(
				'type'        => 'textarea',
				'label'       => __( 'Whitelisted Domains', 'arbricks' ),
				'description' => __( 'Enter domains allowed to use your images (one per line). Example: sub.domain.com', 'arbricks' ),
				'default'     => '',
			),
			'arbricks_hotlink_block_method'         => array(
				'type'        => 'radio',
				'label'       => __( 'Blocking Method', 'arbricks' ),
				'description' => __( 'Choose what happens when hotlinking is detected.', 'arbricks' ),
				'options'     => array(
					'403'         => __( '403 Forbidden', 'arbricks' ),
					'placeholder' => __( 'Placeholder Image', 'arbricks' ),
				),
				'default'     => '403',
			),
			'arbricks_hotlink_placeholder_url'      => array(
				'type'        => 'text',
				'label'       => __( 'Placeholder Image URL', 'arbricks' ),
				'description' => __( 'URL of the image to show instead of the blocked image.', 'arbricks' ),
				'default'     => '',
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// Run early to catch media requests.
		add_action( 'init', array( $this, 'process_hotlinking' ), 5 );
	}

	/**
	 * Process hotlinking protection
	 *
	 * @return void
	 */
	public function process_hotlinking(): void {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( empty( $request_uri ) ) {
			return;
		}

		// Check if the request is for a media file.
		$path       = parse_url( $request_uri, PHP_URL_PATH );
		$extension  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$media_exts = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' );

		if ( ! in_array( $extension, $media_exts, true ) ) {
			return;
		}

		// Check the referer.
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		
		// If no referer, it's usually a direct visit or browser privacy - allow it.
		if ( empty( $referer ) ) {
			return;
		}

		$referer_host = parse_url( $referer, PHP_URL_HOST );
		$current_host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';

		// If referer is from the same site - allow it.
		if ( $referer_host === $current_host ) {
			return;
		}

		$settings = Options::get_feature_settings( self::id() );

		// Check search engines.
		if ( ! empty( $settings['arbricks_hotlink_allow_search_engines'] ) ) {
			$search_engines = array( 'google.', 'bing.', 'yahoo.', 'yandex.', 'duckduckgo.', 'baidu.', 'ask.' );
			foreach ( $search_engines as $engine ) {
				if ( stripos( $referer_host, $engine ) !== false ) {
					return;
				}
			}
		}

		// Check whitelist.
		if ( ! empty( $settings['arbricks_hotlink_whitelist'] ) ) {
			$whitelist = array_filter( array_map( 'trim', explode( "\n", $settings['arbricks_hotlink_whitelist'] ) ) );
			foreach ( $whitelist as $allowed_domain ) {
				if ( $referer_host === $allowed_domain || stripos( $referer_host, '.' . $allowed_domain ) !== false ) {
					return;
				}
			}
		}

		// Hotlinking detected! Trigger block action.
		$method = $settings['arbricks_hotlink_block_method'] ?? '403';

		if ( 'placeholder' === $method && ! empty( $settings['arbricks_hotlink_placeholder_url'] ) ) {
			wp_safe_redirect( $settings['arbricks_hotlink_placeholder_url'] );
			exit;
		}

		// Default to 403.
		status_header( 403 );
		nocache_headers();
		exit;
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
