<?php
/**
 * PDF Generator Helper
 *
 * Handles HTML to PDF conversion using mPDF with Arabic support.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\PDF;

use ArBricks\Options;
use ArBricks\Features\Feature_ArBricks_PDF_Arabic_Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PDF_Generator
 *
 * Static helper for PDF generation.
 */
class PDF_Generator {

	/**
	 * Render HTML to PDF
	 *
	 * @param string $html     HTML content.
	 * @param string $filename Output filename.
	 * @param array  $options  Optional overrides.
	 * @return bool|string PDF binary or file path depending on options.
	 */
	public static function render_html_to_pdf( $html, $filename = 'export.pdf', $options = array() ) {
		$feature_id = Feature_ArBricks_PDF_Arabic_Support::id();
		
		// Check if feature is enabled.
		if ( ! Options::is_feature_enabled( $feature_id ) ) {
			// Fallback or return false if no other PDF engine is available.
			return false;
		}

		// Lazy load mPDF.
		if ( ! class_exists( '\Mpdf\Mpdf' ) ) {
			$mpdf_path = ARBRICKS_PATH . 'lib/mpdf/vendor/autoload.php';
			if ( file_exists( $mpdf_path ) ) {
				require_once $mpdf_path;
			} else {
				error_log( 'ArBricks: mPDF library not found at ' . $mpdf_path );
				return false;
			}
		}

		try {
			$font_family = Options::get_feature_setting( $feature_id, 'font', 'amiri' );
			$force_rtl   = Options::get_feature_setting( $feature_id, 'force_rtl', true );
			$shaping     = Options::get_feature_setting( $feature_id, 'shaping', true );

			// Basic configuration.
			$config = array(
				'mode'         => 'utf-8',
				'format'       => $options['format'] ?? 'A4',
				'default_font' => $font_family === 'amiri' ? 'amiri' : ( $font_family === 'cairo' ? 'cairo' : '' ),
			);

			// Initialize mPDF.
			$mpdf = new \Mpdf\Mpdf( $config );

			// Set Direction.
			if ( $force_rtl ) {
				$mpdf->SetDirectionality( 'rtl' );
			}

			// Arabic shaping is usually handled by mPDF automatically in RTL mode if configured.
			$mpdf->autoScriptToLang     = true;
			$mpdf->autoLangToFont       = true;
			$mpdf->allow_charset_conversion = false;

			// Add HTML.
			$mpdf->WriteHTML( $html );

			// Output.
			$dest = $options['dest'] ?? 'D'; // D for download, I for inline, S for string.
			return $mpdf->Output( $filename, $dest );

		} catch ( \Exception $e ) {
			error_log( 'ArBricks PDF Error: ' . $e->getMessage() );
			return false;
		}
	}
}
