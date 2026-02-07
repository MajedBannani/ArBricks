<?php
/**
 * Feature: Auto Logout Inactive Users
 *
 * Automatically logs out users after a specified period of inactivity.
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
 * Class Feature_ArBricks_Auto_Logout_Inactive_Users
 *
 * Tracks user activity and logs them out if inactive.
 */
class Feature_ArBricks_Auto_Logout_Inactive_Users implements Feature_Interface {

	/**
	 * Meta key for last activity
	 */
	const META_KEY = 'arbricks_last_activity';

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string {
		return 'arbricks_auto_logout_inactive_users';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array Feature metadata.
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Auto Logout Inactive Users', 'arbricks' ),
			'description' => __( 'Automatically logs out users after a specified period of inactivity to improve security.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary' => __( 'Protects user accounts by closing open sessions that may contain sensitive data when left unused.', 'arbricks' ),
				'how_to'  => array(
					__( 'Set the inactivity duration in minutes.', 'arbricks' ),
					__( 'Select the roles this restriction will apply to.', 'arbricks' ),
					__( 'You can enable a warning that appears to the user before they are logged out.', 'arbricks' ),
				),
				'notes'   => array(
					__( 'Activity is tracked when pages are loaded or when interacting with the site.', 'arbricks' ),
					__( 'This feature does not affect non-logged-in visitors.', 'arbricks' ),
					__( 'REST API, Cron, and WP-CLI requests are excluded to ensure background processes continue working.', 'arbricks' ),
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
		$roles = array();
		if ( function_exists( 'wp_roles' ) ) {
			foreach ( wp_roles()->get_names() as $slug => $name ) {
				$roles[ $slug ] = $name;
			}
		}

		return array(
			'duration'        => array(
				'type'        => 'number',
				'label'       => __( 'Inactivity Duration (Minutes)', 'arbricks' ),
				'default'     => 30,
				'placeholder' => '30',
			),
			'roles'           => array(
				'type'        => 'roles_multiselect', // Custom type handled in render_admin_ui.
				'label'       => __( 'Apply to Roles', 'arbricks' ),
				'default'     => array_keys( $roles ),
				'options'     => $roles,
			),
			'show_warning'    => array(
				'type'        => 'checkbox',
				'label'       => __( 'Show Warning Before Logout', 'arbricks' ),
				'default'     => true,
			),
			'warning_time'    => array(
				'type'        => 'number',
				'label'       => __( 'Warning Time Before Logout (Minutes)', 'arbricks' ),
				'default'     => 2,
				'placeholder' => '2',
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
		if ( $this->should_skip() ) {
			return;
		}

		add_action( 'init', array( $this, 'track_and_check_inactivity' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_arbricks_update_activity', array( $this, 'ajax_update_activity' ) );
	}

	/**
	 * Check if feature should skip current request
	 *
	 * @return bool
	 */
	private function should_skip(): bool {
		if ( ! is_user_logged_in() ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return true;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}

		// Skip if user role is not in the list.
		$user          = wp_get_current_user();
		$allowed_roles = Options::get_feature_setting( self::id(), 'roles', array() );
		if ( ! empty( $allowed_roles ) && is_array( $allowed_roles ) ) {
			$has_role = false;
			foreach ( $user->roles as $role ) {
				if ( in_array( $role, $allowed_roles, true ) ) {
					$has_role = true;
					break;
				}
			}
			if ( ! $has_role ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Track activity and check for inactivity.
	 *
	 * @return void
	 */
	public function track_and_check_inactivity(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$now              = time();
		$last_activity    = (int) get_user_meta( $user_id, self::META_KEY, true );
		$duration_minutes = (int) Options::get_feature_setting( self::id(), 'duration', 30 );
		$duration_seconds = $duration_minutes * 60;

		// Check for inactivity.
		if ( $last_activity > 0 && ( $now - $last_activity ) > $duration_seconds ) {
			// Clear activity so we don't repeat this.
			delete_user_meta( $user_id, self::META_KEY );
			
			// Logout and redirect.
			wp_logout();
			wp_safe_redirect( wp_login_url( home_url() ) . '&arbricks_logout=inactive' );
			exit;
		}

		// Update activity if it's not a background callback.
		if ( ! wp_doing_ajax() ) {
			update_user_meta( $user_id, self::META_KEY, $now );
		}
	}

	/**
	 * AJAX Update activity.
	 *
	 * @return void
	 */
	public function ajax_update_activity(): void {
		check_ajax_referer( 'arbricks_activity', 'nonce' );
		$user_id = get_current_user_id();
		if ( $user_id ) {
			update_user_meta( $user_id, self::META_KEY, time() );
			wp_send_json_success();
		}
		wp_send_json_error();
	}

	/**
	 * Enqueue frontend/admin scripts for warning.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( $this->should_skip() ) {
			return;
		}

		$show_warning = (bool) Options::get_feature_setting( self::id(), 'show_warning', true );
		if ( ! $show_warning ) {
			return;
		}

		$duration_minutes = (int) Options::get_feature_setting( self::id(), 'duration', 30 );
		$warning_minutes  = (int) Options::get_feature_setting( self::id(), 'warning_time', 2 );

		wp_enqueue_script( 'arbricks-auto-logout', false, array( 'jquery' ), '1.0.0', true );
		wp_add_inline_script( 'arbricks-auto-logout', $this->get_js_code( $duration_minutes, $warning_minutes ) );
		add_action( 'wp_footer', array( $this, 'render_warning_modal' ) );
		add_action( 'admin_footer', array( $this, 'render_warning_modal' ) );
	}

	/**
	 * Get JS code for inactivity warning.
	 *
	 * @param int $duration
	 * @param int $warning
	 * @return string
	 */
	private function get_js_code( $duration, $warning ): string {
		ob_start();
		?>
		(function($) {
			var duration = <?php echo (int) $duration * 60; ?>;
			var warning = <?php echo (int) $warning * 60; ?>;
			var lastActivity = Date.now();
			var timer;

			function checkInactivity() {
				var now = Date.now();
				var diff = Math.floor((now - lastActivity) / 1000);
				var remaining = duration - diff;

				if (remaining <= warning && remaining > 0) {
					$('#arbricks-logout-warning').fadeIn();
					$('#arbricks-logout-timer').text(remaining);
				} else if (remaining <= 0) {
					window.location.href = '<?php echo esc_url( wp_login_url( home_url() ) ); ?>' + '&arbricks_logout=inactive';
				} else {
					$('#arbricks-logout-warning').fadeOut();
				}
			}

			$(document).on('click keypress mousemove', function() {
				// Don't update too often, once every 30 seconds is enough for server but JS resets locally.
				lastActivity = Date.now();
			});

			$('#arbricks-stay-logged-in').on('click', function() {
				$.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					action: 'arbricks_update_activity',
					nonce: '<?php echo wp_create_nonce( 'arbricks_activity' ); ?>'
				}).done(function() {
					lastActivity = Date.now();
					$('#arbricks-logout-warning').fadeOut();
				});
			});

			timer = setInterval(checkInactivity, 1000);
		})(jQuery);
		<?php
		return ob_get_clean();
	}

	/**
	 * Rendering the warning modal.
	 *
	 * @return void
	 */
	public function render_warning_modal(): void {
		?>
		<div id="arbricks-logout-warning" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:999999; display:flex; align-items:center; justify-content:center; direction:rtl; font-family:sans-serif;">
			<div style="background:#fff; padding:30px; border-radius:10px; max-width:400px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
				<h3 style="margin-top:0; color:#d63638;"><?php esc_html_e( 'Session Expiration Alert', 'arbricks' ); ?></h3>
				<p><?php esc_html_e( 'You will be automatically logged out in', 'arbricks' ); ?> <strong id="arbricks-logout-timer"></strong> <?php esc_html_e( 'seconds due to inactivity.', 'arbricks' ); ?></p>
				<button id="arbricks-stay-logged-in" class="button button-primary" style="background:#2271b1; color:#fff; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; font-size:16px;"><?php esc_html_e( 'Stay Logged In', 'arbricks' ); ?></button>
			</div>
		</div>
		<script>
			// Ensure it only shows once if both footers run (rare but possible).
			if (jQuery('#arbricks-logout-warning').length > 1) {
				jQuery('#arbricks-logout-warning').last().remove();
			}
		</script>
		<?php
	}

	/**
	 * Render custom admin UI for role targeting.
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		$feature_id = self::id();
		$settings   = Options::get_feature_settings( $feature_id );
		$all_roles  = array();
		if ( function_exists( 'wp_roles' ) ) {
			$all_roles = wp_roles()->get_names();
		}
		
		$selected_roles = isset( $settings['roles'] ) ? (array) $settings['roles'] : array_keys( $all_roles );

		// We use this to inject the roles selection as the Admin class doesn't have a multi-select yet.
		?>
		<div class="arbricks-feature-settings-manual" style="margin-top:15px; border-top:1px solid #eee; padding-top:15px;">
			<label class="arbricks-setting-label" style="display:block; margin-bottom:8px; font-weight:600;">
				<?php esc_html_e( 'Apply to Roles:', 'arbricks' ); ?>
			</label>
			<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap:10px;">
				<?php foreach ( $all_roles as $slug => $name ) : ?>
					<label style="display:flex; align-items:center; gap:5px; font-size:13px;">
						<input type="checkbox" name="feature_settings[<?php echo esc_attr( $feature_id ); ?>][roles][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $selected_roles, true ) ); ?>>
						<?php echo esc_html( $name ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="arbricks-setting-description" style="font-size:12px; color:#666; margin-top:5px;">
				<?php esc_html_e( 'Users with these roles will be subject to automatic logout.', 'arbricks' ); ?>
			</p>
		</div>
		<?php
	}
}
