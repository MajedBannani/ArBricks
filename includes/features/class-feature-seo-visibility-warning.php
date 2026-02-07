<?php
/**
 * Feature: SEO Visibility Warning
 *
 * Shows admin notice if search engines are discouraged.
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
 * Class Feature_Seo_Visibility_Warning
 *
 * Admin warning for search engine visibility.
 */
class Feature_Seo_Visibility_Warning implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'seo_visibility_warning';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'SEO Visibility Warning', 'arbricks' ),
			'description' => __( 'Shows an admin notice if the site is discouraged from search engines.', 'arbricks' ),
			'category'    => 'seo',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Displays a prominent warning in the admin panel when "Discourage search engines from indexing this site" is enabled in WordPress settings. This prevents accidentally launching a site with search engine indexing disabled.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Choose where to show the notice: All Admin Pages or Dashboard Only.', 'arbricks' ),
					__( 'Select the notice type: Error (Red) or Warning (Yellow).', 'arbricks' ),
					__( 'Choose whether users can dismiss the notice.', 'arbricks' ),
					__( 'Click "Save Changes".', 'arbricks' ),
					__( 'If the site is currently discouraged, you will see the notice immediately.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'This checks the WordPress Reading settings option "Search Engine Visibility".', 'arbricks' ),
					__( 'The notice only appears when the "blog_public" option is set to 0 (discouraged).', 'arbricks' ),
					__( 'All Admin Pages: Shows the notice on every admin page to ensure it\'s seen.', 'arbricks' ),
					__( 'Dashboard Only: Shows the notice only on the main admin dashboard.', 'arbricks' ),
					__( 'Dismissible: If allowed, users can close the notice (it reappears on page refresh unless handled per-user).', 'arbricks' ),
					__( 'Prevents the common mistake of launching a site to the public with SEO remaining blocked.', 'arbricks' ),
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
			'enabled_on'  => array(
				'type'        => 'select',
				'label'       => __( 'Show on', 'arbricks' ),
				'description' => __( 'Where the notice should appear in the admin panel.', 'arbricks' ),
				'options'     => array(
					'all_admin_pages' => __( 'All Admin Pages', 'arbricks' ),
					'dashboard_only'  => __( 'Dashboard Only', 'arbricks' ),
				),
				'default'     => 'all_admin_pages',
			),
			'notice_type' => array(
				'type'        => 'select',
				'label'       => __( 'Notice Type', 'arbricks' ),
				'description' => __( 'Warning format and color.', 'arbricks' ),
				'options'     => array(
					'error'   => __( 'Error (Red)', 'arbricks' ),
					'warning' => __( 'Warning (Yellow)', 'arbricks' ),
				),
				'default'     => 'error',
			),
			'show_dismiss' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Allow Dismiss', 'arbricks' ),
				'description' => __( 'Allow users to temporarily dismiss the notice.', 'arbricks' ),
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
		add_action( 'admin_notices', array( $this, 'show_visibility_notice' ) );
		add_action( 'wp_ajax_arbricks_dismiss_seo_warning', array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Show visibility warning notice
	 *
	 * @return void
	 */
	public function show_visibility_notice(): void {
		// Only for users who can manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Don't show on network admin.
		if ( is_network_admin() ) {
			return;
		}

		// Check if search engines are discouraged.
		if ( '1' === get_option( 'blog_public' ) ) {
			return;
		}

		$settings   = Options::get_feature_settings( self::id() );
		$enabled_on = $settings['enabled_on'] ?? 'all_admin_pages';

		// Check page restriction.
		if ( 'dashboard_only' === $enabled_on ) {
			$screen = get_current_screen();
			if ( ! $screen || 'dashboard' !== $screen->id ) {
				return;
			}
		}

		// Check if dismissed.
		if ( ! empty( $settings['show_dismiss'] ) ) {
			$dismissed = get_user_meta( get_current_user_id(), 'arbricks_seo_warning_dismissed', true );
			if ( $dismissed ) {
				return;
			}
		}

		$notice_type = $settings['notice_type'] ?? 'error';
		$dismissible = ! empty( $settings['show_dismiss'] ) ? 'is-dismissible' : '';

		?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> <?php echo esc_attr( $dismissible ); ?>" id="arbricks-seo-warning">
			<p>
				<strong><?php esc_html_e( 'SEO Alert:', 'arbricks' ); ?></strong>
				<?php esc_html_e( 'Your site is currently discouraged from search engines and is not being indexed.', 'arbricks' ); ?>
				<a href="<?php echo esc_url( admin_url( 'options-reading.php' ) ); ?>">
					<?php esc_html_e( 'Change Settings', 'arbricks' ); ?>
				</a>
			</p>
		</div>
		<?php

		// Add AJAX handler for dismissal.
		if ( ! empty( $settings['show_dismiss'] ) ) {
			?>
			<script>
			jQuery(function($) {
				$('#arbricks-seo-warning').on('click', '.notice-dismiss', function() {
					$.post(ajaxurl, {
						action: 'arbricks_dismiss_seo_warning',
						nonce: '<?php echo esc_js( wp_create_nonce( 'arbricks_dismiss_seo_warning' ) ); ?>'
					});
				});
			});
			</script>
			<?php
		}
	}

	/**
	 * Handle AJAX dismiss action
	 *
	 * @return void
	 */
	public function handle_dismiss(): void {
		check_ajax_referer( 'arbricks_dismiss_seo_warning', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		update_user_meta( get_current_user_id(), 'arbricks_seo_warning_dismissed', true );
		wp_send_json_success();
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
