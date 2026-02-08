<?php
/**
 * Feature: Auto convert uploaded JPEG/PNG to WebP (replace original)
 *
 * Automatically converts uploaded JPEG and PNG images to WebP format
 * and replaces the original file.
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_WebP_Auto_Convert
 *
 * Converts uploaded JPEG/PNG images to WebP format automatically.
 */
class Feature_WebP_Auto_Convert implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'webp_auto_convert_uploads';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Auto WebP Converter (Uploads)', 'arbricks' ),
			'description' => __( 'Automatically convert uploaded JPEG/PNG images to WebP and replace the original file.', 'arbricks' ),
			'category'    => 'tools',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Automatically optimizes your media library by converting new JPEG and PNG uploads to the modern WebP format. WebP images are significantly smaller in size without losing quality, leading to faster site loading times.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Click "Save Changes".', 'arbricks' ),
					__( 'Upload a new JPEG or PNG image to your Media Library.', 'arbricks' ),
					__( 'The original image will be deleted and replaced with a .webp version.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Requires GD or ImageMagick PHP extension with WebP support enabled on your server.', 'arbricks' ),
					sprintf( __( '%s: %s', 'arbricks' ), __( 'Original Capture', 'arbricks' ), __( 'Replaces the original JPG/PNG file entirely to save disk space.', 'arbricks' ) ),
					sprintf( __( '%s: %s', 'arbricks' ), __( 'Quality', 'arbricks' ), __( 'Converted at 85% quality by default (optimal balance between size and quality).', 'arbricks' ) ),
					sprintf( __( '%s: %s', 'arbricks' ), __( 'Transparency', 'arbricks' ), __( 'PNG transparency is preserved during conversion.', 'arbricks' ) ),
					sprintf( __( '%s: %s', 'arbricks' ), __( 'Scope', 'arbricks' ), __( 'Only affects NEW uploads. Existing images in your media library are not processed.', 'arbricks' ) ),
					sprintf( __( '%s: %s', 'arbricks' ), __( 'Performance', 'arbricks' ), __( 'Slight increase in processing time during upload as conversion happens on-the-fly.', 'arbricks' ) ),
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
		add_filter( 'wp_handle_upload', array( $this, 'handle_upload' ), 20 );
	}

	/**
	 * Convert uploaded JPEG/PNG to WebP and replace file
	 *
	 * @param array $upload Upload data: file, url, type.
	 * @return array Modified upload data.
	 */
	public function handle_upload( array $upload ): array {

		if ( empty( $upload['file'] ) || ! is_string( $upload['file'] ) ) {
			return $upload;
		}

		$file = $upload['file'];

		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return $upload;
		}

		// Avoid double-processing.
		if ( preg_match( '/\.webp$/i', $file ) ) {
			return $upload;
		}

		// Detect mime safely.
		$type = '';
		if ( ! empty( $upload['type'] ) && is_string( $upload['type'] ) ) {
			$type = $upload['type'];
		} elseif ( function_exists( 'mime_content_type' ) ) {
			$type = mime_content_type( $file );
		}

		// Only JPG/PNG.
		if ( ! in_array( $type, array( 'image/jpeg', 'image/png' ), true ) ) {
			return $upload;
		}

		// Check WebP support in GD.
		if ( ! function_exists( 'imagewebp' ) ) {
			return $upload;
		}

		$image = null;

		if ( 'image/jpeg' === $type && function_exists( 'imagecreatefromjpeg' ) ) {
			$image = @imagecreatefromjpeg( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		} elseif ( 'image/png' === $type && function_exists( 'imagecreatefrompng' ) ) {
			$image = @imagecreatefrompng( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( $image ) {
				// Ensure alpha is handled properly.
				imagepalettetotruecolor( $image );
				imagealphablending( $image, true );
				imagesavealpha( $image, true );
			}
		}

		if ( ! $image ) {
			return $upload;
		}

		$webp_file = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $file );
		if ( empty( $webp_file ) || ! is_string( $webp_file ) ) {
			imagedestroy( $image );
			return $upload;
		}

		// Ensure destination is writable (same dir).
		$dir = dirname( $webp_file );
		if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
			imagedestroy( $image );
			return $upload;
		}

		$quality = 85; // Future: make configurable via feature settings.

		$ok = imagewebp( $image, $webp_file, $quality );
		imagedestroy( $image );

		// If conversion failed, do nothing.
		if ( ! $ok || ! file_exists( $webp_file ) ) {
			return $upload;
		}

		// Remove original file only if we can.
		if ( is_writable( $file ) ) {
			@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		// Update upload payload.
		$upload['file'] = $webp_file;
		if ( ! empty( $upload['url'] ) && is_string( $upload['url'] ) ) {
			$upload['url'] = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $upload['url'] );
		}
		$upload['type'] = 'image/webp';

		return $upload;
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
