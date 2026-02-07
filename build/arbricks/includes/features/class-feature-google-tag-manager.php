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

		// Priority 10 (default) to avoid conflicts with SEO/caching plugins.
		// GTM scripts work fine at default priority - no need to run super early.
		add_action( 'wp_head', array( $this, 'inject_head_script' ), 10 );

		if ( ! empty( $settings['inject_noscript'] ) ) {
			$location = ! empty( $settings['noscript_location'] ) ? $settings['noscript_location'] : 'wp_body_open';
			// Use default priority for consistency.
			add_action( $location, array( $this, 'inject_noscript' ), 10 );

			// Add admin notice if wp_body_open is selected but theme doesn't support it.
			if ( 'wp_body_open' === $location ) {
				add_action( 'admin_notices', array( $this, 'theme_compatibility_notice' ) );
			}
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
		})(window,document,'script','dataLayer','<?php echo esc_js( $gtm_id ); ?>');</script>
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

	/**
	 * Display admin notice if theme doesn't support wp_body_open
	 *
	 * @return void
	 */
	public function theme_compatibility_notice(): void {
		// Only show on ArBricks settings page.
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_arbricks' !== $screen->id ) {
			return;
		}

		// Check if theme supports wp_body_open by checking if function exists.
		if ( ! function_exists( 'wp_body_open' ) || ! did_action( 'wp_body_open' ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'ArBricks - Google Tag Manager:', 'arbricks' ); ?></strong>
					<?php
					esc_html_e(
						'Your theme may not support the wp_body_open hook. The GTM noscript tag might not display correctly. Consider selecting "Footer" as the noscript location instead.',
						'arbricks'
					);
					?>
				</p>
			</div>
			<?php
		}
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
