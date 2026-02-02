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
			'description' => __( 'Show admin notice if search engines are blocked.', 'arbricks' ),
			'category'    => 'seo',
			'shortcode'   => '',
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
				'label'       => __( 'Show On', 'arbricks' ),
				'description' => __( 'Where to display the notice', 'arbricks' ),
				'options'     => array(
					'all_admin_pages' => __( 'All Admin Pages', 'arbricks' ),
					'dashboard_only'  => __( 'Dashboard Only', 'arbricks' ),
				),
				'default'     => 'all_admin_pages',
			),
			'notice_type' => array(
				'type'        => 'select',
				'label'       => __( 'Notice Type', 'arbricks' ),
				'description' => __( 'Type of admin notice', 'arbricks' ),
				'options'     => array(
					'error'   => __( 'Error (Red)', 'arbricks' ),
					'warning' => __( 'Warning (Yellow)', 'arbricks' ),
				),
				'default'     => 'error',
			),
			'show_dismiss' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Allow Dismissal', 'arbricks' ),
				'description' => __( 'Let users dismiss the notice', 'arbricks' ),
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
				<strong><?php esc_html_e( 'SEO Warning:', 'arbricks' ); ?></strong>
				<?php esc_html_e( 'Search engines are currently discouraged from indexing this site.', 'arbricks' ); ?>
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
}
