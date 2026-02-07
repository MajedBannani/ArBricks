<?php
/**
 * Feature: Auto Image Meta on Upload
 *
 * Automatically generates title, caption, description, and alt text for uploaded images.
 *
 * @package ArBricks
 * @since 2.0.2
 */

namespace ArBricks\Features;

use ArBricks\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Media_Auto_Meta
 *
 * Auto-generate image metadata on upload.
 */
class Feature_Media_Auto_Meta implements Feature_Interface {

	/**
	 * Guard against infinite loops
	 *
	 * @var bool
	 */
	private static $processing = false;

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'media_auto_meta';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Auto Image Meta on Upload', 'arbricks' ),
			'description' => __( 'Automatically generate title, caption, description, and alt text from filename.', 'arbricks' ),
			'category'    => 'seo',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Automatically generates image metadata (title, alt text, caption, description) from the filename when you upload images. Saves time and improves SEO by ensuring all images have proper alt text and titles.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above', 'arbricks' ),
					__( 'Choose "Apply To" mode: Only Empty Fields (safe) or Always Overwrite', 'arbricks' ),
					__( 'Select which fields to auto-generate: Title, Caption, Description, Alt Text', 'arbricks' ),
					__( 'Configure text processing: Preserve Case and Clean Separators', 'arbricks' ),
					__( 'Click "Save Changes"', 'arbricks' ),
					__( 'Upload a new image to test - the filename will be converted to metadata', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Only Empty Fields (recommended): Only fills in blank metadata, preserves your manual edits', 'arbricks' ),
					__( 'Always Overwrite: Replaces existing metadata every time - use with caution', 'arbricks' ),
					__( 'Clean Separators: Converts hyphens and underscores to spaces (my-image-file.jpg → My Image File)', 'arbricks' ),
					__( 'Preserve Case (recommended for Arabic): Keeps original filename case, important for non-Latin text', 'arbricks' ),
					__( 'Only affects images uploaded AFTER enabling - does not modify existing attachments', 'arbricks' ),
					__( 'File extension is automatically removed (image.jpg → image)', 'arbricks' ),
					__( 'No external services used - completely local processing', 'arbricks' ),
				),
				'examples' => array(
					__( 'Filename: product-red-shoes.jpg → Alt: "product red shoes" or "Product Red Shoes"', 'arbricks' ),
					__( 'Arabic filename: صورة-منتج.jpg → Keeps original case if Preserve Case enabled', 'arbricks' ),
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
			'apply_to'          => array(
				'type'        => 'select',
				'label'       => __( 'Apply To', 'arbricks' ),
				'description' => __( 'When to set metadata', 'arbricks' ),
				'options'     => array(
					'only_empty_fields' => __( 'Only Empty Fields (Safe)', 'arbricks' ),
					'always_overwrite'  => __( 'Always Overwrite', 'arbricks' ),
				),
				'default'     => 'only_empty_fields',
			),
			'set_title'         => array(
				'type'        => 'checkbox',
				'label'       => __( 'Set Title', 'arbricks' ),
				'description' => __( 'Auto-generate attachment title', 'arbricks' ),
				'default'     => true,
			),
			'set_caption'       => array(
				'type'        => 'checkbox',
				'label'       => __( 'Set Caption', 'arbricks' ),
				'description' => __( 'Auto-generate caption (excerpt)', 'arbricks' ),
				'default'     => false,
			),
			'set_description'   => array(
				'type'        => 'checkbox',
				'label'       => __( 'Set Description', 'arbricks' ),
				'description' => __( 'Auto-generate description (content)', 'arbricks' ),
				'default'     => false,
			),
			'set_alt'           => array(
				'type'        => 'checkbox',
				'label'       => __( 'Set Alt Text', 'arbricks' ),
				'description' => __( 'Auto-generate alt text', 'arbricks' ),
				'default'     => true,
			),
			'preserve_case'     => array(
				'type'        => 'checkbox',
				'label'       => __( 'Preserve Case', 'arbricks' ),
				'description' => __( 'Keep original case (recommended for non-Latin text)', 'arbricks' ),
				'default'     => true,
			),
			'separator_cleanup' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Clean Separators', 'arbricks' ),
				'description' => __( 'Replace hyphens/underscores with spaces', 'arbricks' ),
				'default'     => true,
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'add_attachment', array( $this, 'auto_set_image_meta' ) );
	}

	/**
	 * Auto-set image metadata on upload
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function auto_set_image_meta( $attachment_id ): void {
		// Prevent infinite loops.
		if ( self::$processing ) {
			return;
		}

		// Only process images.
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		$settings  = Options::get_feature_settings( self::id() );
		$apply_to  = $settings['apply_to'] ?? 'only_empty_fields';
		$overwrite = ( 'always_overwrite' === $apply_to );

		// Get current attachment data.
		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return;
		}

		// Generate clean text from filename.
		$filename  = basename( get_attached_file( $attachment_id ) );
		$clean_text = $this->generate_clean_text( $filename, $settings );

		// Prepare update data.
		$update_data = array( 'ID' => $attachment_id );
		$needs_update = false;

		// Set title.
		if ( ! empty( $settings['set_title'] ) ) {
			if ( $overwrite || empty( $attachment->post_title ) ) {
				$update_data['post_title'] = $clean_text;
				$needs_update = true;
			}
		}

		// Set caption (excerpt).
		if ( ! empty( $settings['set_caption'] ) ) {
			if ( $overwrite || empty( $attachment->post_excerpt ) ) {
				$update_data['post_excerpt'] = $clean_text;
				$needs_update = true;
			}
		}

		// Set description (content).
		if ( ! empty( $settings['set_description'] ) ) {
			if ( $overwrite || empty( $attachment->post_content ) ) {
				$update_data['post_content'] = $clean_text;
				$needs_update = true;
			}
		}

		// Update post fields.
		if ( $needs_update ) {
			self::$processing = true;
			wp_update_post( $update_data );
			self::$processing = false;
		}

		// Set alt text (meta field).
		if ( ! empty( $settings['set_alt'] ) ) {
			$current_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			if ( $overwrite || empty( $current_alt ) ) {
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', $clean_text );
			}
		}
	}

	/**
	 * Generate clean text from filename
	 *
	 * @param string $filename Filename.
	 * @param array  $settings Feature settings.
	 * @return string Clean text.
	 */
	private function generate_clean_text( $filename, $settings ): string {
		// Remove extension.
		$text = pathinfo( $filename, PATHINFO_FILENAME );

		// Clean separators if enabled.
		if ( ! empty( $settings['separator_cleanup'] ) ) {
			// Replace hyphens, underscores, and multiple spaces with single space.
			$text = preg_replace( '/\s*[-_\s]+\s*/', ' ', $text );
		}

		// Preserve case or normalize (only for Latin if not preserving).
		if ( empty( $settings['preserve_case'] ) ) {
			// Only apply ucwords/strtolower if text is Latin-only.
			if ( preg_match( '/^[A-Za-z0-9\s\-_]+$/', $text ) ) {
				$text = ucwords( strtolower( $text ) );
			}
		}

		return trim( $text );
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
