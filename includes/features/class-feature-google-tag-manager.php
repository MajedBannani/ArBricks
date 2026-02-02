<?php
/**
 * Feature: Google Tag Manager
 *
 * Injects Google Tag Manager scripts into head and body.
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
 * Class Feature_Google_Tag_Manager
 *
 * Handles GTM script injection.
 */
class Feature_Google_Tag_Manager implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'google_tag_manager';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Google Tag Manager', 'arbricks' ),
			'description' => __( 'Inject Google Tag Manager scripts into your site.', 'arbricks' ),
			'category'    => 'tools',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Easily add Google Tag Manager tracking to your WordPress site with automatic code injection.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Enter your GTM Container ID (format: GTM-XXXXXXX).', 'arbricks' ),
					__( 'Choose whether to include noscript tag for non-JS users.', 'arbricks' ),
					__( 'Select noscript position (body or footer).', 'arbricks' ),
					__( 'Click "Save Changes" to apply.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Invalid GTM IDs will be rejected - nothing will be injected.', 'arbricks' ),
					__( 'Code is automatically placed in correct locations (head + body/footer).', 'arbricks' ),
					__( 'Works with all WordPress themes.', 'arbricks' ),
				),
				'examples' => array(
					__( 'Valid ID: GTM-ABC1234', 'arbricks' ),
					__( 'Invalid ID: GA-123456 (this is Analytics, not Tag Manager)', 'arbricks' ),
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
			'gtm_id'             => array(
				'type'        => 'text',
				'label'       => __( 'GTM Container ID', 'arbricks' ),
				'description' => __( 'Enter your GTM ID (e.g., GTM-XXXXXXX)', 'arbricks' ),
				'default'     => '',
				'placeholder' => __( 'GTM-XXXXXXX', 'arbricks' ),
			),
			'inject_noscript'    => array(
				'type'        => 'checkbox',
				'label'       => __( 'Include noscript tag', 'arbricks' ),
				'description' => __( 'Inject noscript fallback for users without JavaScript.', 'arbricks' ),
				'default'     => true,
			),
			'noscript_location'  => array(
				'type'        => 'select',
				'label'       => __( 'Noscript Location', 'arbricks' ),
				'description' => __( 'Where to inject the noscript tag.', 'arbricks' ),
				'options'     => array(
					'wp_body_open' => __( 'Body Open (Recommended)', 'arbricks' ),
					'wp_footer'    => __( 'Footer', 'arbricks' ),
				),
				'default'     => 'wp_body_open',
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		$settings = Options::get_feature_settings( self::id() );
		$gtm_id   = ! empty( $settings['gtm_id'] ) ? sanitize_text_field( $settings['gtm_id'] ) : '';

		// Validate GTM ID format.
		if ( empty( $gtm_id ) || ! preg_match( '/^GTM-[A-Z0-9]+$/i', $gtm_id ) ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'inject_head_script' ), 1 );

		if ( ! empty( $settings['inject_noscript'] ) ) {
			$location = ! empty( $settings['noscript_location'] ) ? $settings['noscript_location'] : 'wp_body_open';
			add_action( $location, array( $this, 'inject_noscript' ), 1 );
		}
	}

	/**
	 * Inject GTM head script
	 *
	 * @return void
	 */
	public function inject_head_script(): void {
		$settings = Options::get_feature_settings( self::id() );
		$gtm_id   = esc_js( $settings['gtm_id'] ?? '' );

		if ( empty( $gtm_id ) ) {
			return;
		}

		?>
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','<?php echo $gtm_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>');</script>
		<!-- End Google Tag Manager -->
		<?php
	}

	/**
	 * Inject GTM noscript
	 *
	 * @return void
	 */
	public function inject_noscript(): void {
		$settings = Options::get_feature_settings( self::id() );
		$gtm_id   = esc_attr( $settings['gtm_id'] ?? '' );

		if ( empty( $gtm_id ) ) {
			return;
		}

		$iframe_url = esc_url( 'https://www.googletagmanager.com/ns.html?id=' . $gtm_id );
		?>
		<!-- Google Tag Manager (noscript) -->
		<noscript><iframe src="<?php echo esc_url( $iframe_url ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<!-- End Google Tag Manager (noscript) -->
		<?php
	}
}
