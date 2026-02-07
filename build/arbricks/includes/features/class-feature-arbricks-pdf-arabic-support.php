<?php
/**
 * Feature: Arabic PDF Support
 *
 * Ensures exported PDFs support Arabic text (UTF-8, RTL, and shaping).
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
 * Class Feature_ArBricks_PDF_Arabic_Support
 *
 * Configures PDF generation for Arabic support.
 */
class Feature_ArBricks_PDF_Arabic_Support implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string {
		return 'arbricks_pdf_arabic_support';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array Feature metadata.
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Arabic PDF Support', 'arbricks' ),
			'description' => __( 'Ensures exported PDFs support Arabic text correctly (RTL direction, letter shaping, and Arabic fonts).', 'arbricks' ),
			'category'    => 'tools',
			'help'        => array(
				'summary' => __( 'Arabic text in PDFs often appears with disconnected letters or incorrect direction. This feature configures the export engine to support RTL direction and character shaping automatically.', 'arbricks' ),
				'how_to'  => array(
					__( 'Enable the feature in the settings.', 'arbricks' ),
					__( 'Select your preferred Arabic font (e.g., Amiri).', 'arbricks' ),
					__( 'ArBricks will use these settings when exporting any content to PDF.', 'arbricks' ),
				),
				'notes'   => array(
					__( 'The mPDF library is only loaded when needed to minimize resource consumption.', 'arbricks' ),
					__( 'Ensures conversion of numbers and symbols to match the Arabic context.', 'arbricks' ),
					__( 'The Arabic font file must be available in the plugin assets for export to work correctly.', 'arbricks' ),
				),
			),
		);
	}

	/**
	 * Get settings schema
	 *
	 * @return array Settings schema.
	 */
	public function get_settings_schema(): array {
		return array(
			'font'       => array(
				'type'    => 'select',
				'label'   => __( 'Default PDF Font', 'arbricks' ),
				'default' => 'amiri',
				'options' => array(
					'amiri'  => 'Amiri (Classic)',
					'cairo'  => 'Cairo (Modern Sans-serif)',
					'system' => __( 'System Default', 'arbricks' ),
				),
			),
			'force_rtl'  => array(
				'type'    => 'checkbox',
				'label'   => __( 'Force RTL for Arabic Pages', 'arbricks' ),
				'default' => true,
			),
			'shaping'    => array(
				'type'    => 'checkbox',
				'label'   => __( 'Enable Arabic Shaping', 'arbricks' ),
				'default' => true,
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * Called only when feature is enabled.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// This feature provides utility services rather than direct UI hooks.
		// It will be leveraged by ArBricks\PDF\PDF_Generator.
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		// No custom UI needed beyond schema.
	}
}
