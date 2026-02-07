<?php
/**
 * Feature: Two-Factor Authentication (2FA)
 *
 * Provides TOTP-based 2FA for administrators using apps like Google Authenticator.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

use ArBricks\Options;
use ArBricks\Lib\GoogleAuthenticator;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Two_Factor_Auth
 */
class Feature_ArBricks_Two_Factor_Auth implements Feature_Interface {

	/**
	 * Library instance
	 *
	 * @var GoogleAuthenticator
	 */
	private $ga;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Only load library when needed.
		if ( ! class_exists( 'ArBricks\Lib\GoogleAuthenticator' ) ) {
			require_once ARBRICKS_PLUGIN_DIR . 'includes/lib/class-phpgangsta-googleauthenticator.php';
		}
		$this->ga = new GoogleAuthenticator();
	}

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_two_factor_auth';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Two-Factor Authentication (2FA)', 'arbricks' ),
			'description' => __( 'Add an extra layer of security using authentication apps like Google Authenticator.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary'  => __( 'Two-Factor Authentication (2FA) is one of the most important ways to secure accounts. Once activated, you will be required to enter a temporary code from an authentication app on your phone after entering your password.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the main feature toggle above.', 'arbricks' ),
					__( 'Click on the "Setup Two-Factor Authentication" button below.', 'arbricks' ),
					__( 'Scan the QR Code using an app like Google Authenticator or Microsoft Authenticator.', 'arbricks' ),
					__( 'Enter the 6-digit code to verify the setup and activate it.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'This feature is currently for Administrators only to ensure maximum protection.', 'arbricks' ),
					__( 'Supported apps: Google Authenticator, Microsoft Authenticator, Authy, and other TOTP apps.', 'arbricks' ),
					__( 'WARNING: Do not delete the account from your app after activation until you disable the feature here, or you may lose access.', 'arbricks' ),
					__( 'IMPORTANT NOTE: If you use another security plugin for login, a conflict may occur. Enable only one 2FA system.', 'arbricks' ),
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
			'arbricks_2fa_admins_only' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Apply to Administrators only (Recommended)', 'arbricks' ),
				'description' => __( 'Apply two-factor authentication only to administrator accounts.', 'arbricks' ),
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
		// Admin setup.
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'wp_ajax_arbricks_2fa_setup_generate', array( $this, 'ajax_setup_generate' ) );
			add_action( 'wp_ajax_arbricks_2fa_setup_verify', array( $this, 'ajax_setup_verify' ) );
			add_action( 'wp_ajax_arbricks_2fa_disable', array( $this, 'ajax_disable' ) );
		}

		// Login flow.
		add_action( 'login_form', array( $this, 'render_login_field' ) );
		add_filter( 'authenticate', array( $this, 'validate_login' ), 50, 3 );
	}

	/**
	 * Enqueue admin assets
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_arbricks-settings' !== $screen->id ) {
			return;
		}

		// Use the existing vendor qrcode library.
		wp_enqueue_script(
			'arbricks-vendor-qrcode',
			ARBRICKS_PLUGIN_URL . 'assets/vendor/qrcode/qrcode.min.js',
			array(),
			'1.0.0',
			true
		);

		// Add custom JS for 2FA UI.
		wp_add_inline_script(
			'arbricks-vendor-qrcode',
			'
			jQuery(document).ready(function($) {
				// Generate Setup UI.
				$("#arbricks-2fa-setup-btn").on("click", function() {
					var $btn = $(this);
					$btn.prop("disabled", true).text("' . esc_js( __( 'Generating...', 'arbricks' ) ) . '");
					
					$.post(ajaxurl, { action: "arbricks_2fa_setup_generate" }, function(response) {
						if (response.success) {
							$("#arbricks-2fa-setup-section").show();
							$("#arbricks-2fa-secret-text").text(response.data.secret);
							$("#arbricks-2fa-setup-qr").empty();
							new QRCode(document.getElementById("arbricks-2fa-setup-qr"), {
								text: response.data.qr_uri,
								width: 200,
								height: 200
							});
							$btn.hide();
						} else {
							alert(response.data.message);
							$btn.prop("disabled", false).text("' . esc_js( __( 'Setup Two-Factor Authentication', 'arbricks' ) ) . '");
						}
					});
				});

				// Verify and Activate.
				$("#arbricks-2fa-verify-btn").on("click", function() {
					var $btn = $(this);
					var code = $("#arbricks-2fa-verify-code").val();
					if (!code) return;

					$btn.prop("disabled", true).text("' . esc_js( __( 'Verifying...', 'arbricks' ) ) . '");
					
					$.post(ajaxurl, { 
						action: "arbricks_2fa_setup_verify",
						code: code
					}, function(response) {
						if (response.success) {
							location.reload();
						} else {
							alert(response.data.message);
							$btn.prop("disabled", false).text("' . esc_js( __( 'Verify and Activate', 'arbricks' ) ) . '");
						}
					});
				});

				// Disable.
				$("#arbricks-2fa-disable-btn").on("click", function() {
					if (!confirm("' . esc_js( __( 'Are you sure you want to disable 2FA? This will reduce your account security.', 'arbricks' ) ) . '")) return;
					
					$.post(ajaxurl, { action: "arbricks_2fa_disable" }, function(response) {
						if (response.success) {
							location.reload();
						}
					});
				});
			});
			'
		);
	}

	/**
	 * AJAX: Generate temporary secret and QR URI
	 *
	 * @return void
	 */
	public function ajax_setup_generate(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'arbricks' ) ) );
		}

		$user   = wp_get_current_user();
		$secret = $this->ga->createSecret();
		
		// Store temporary secret in user meta.
		update_user_meta( $user->ID, 'arbricks_2fa_temp_secret', $secret );

		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : 'WordPress';
		$qr_uri = 'otpauth://totp/' . rawurlencode( $host . ':' . $user->user_email ) . '?secret=' . $secret . '&issuer=' . rawurlencode( 'ArBricks' );

		wp_send_json_success( array(
			'secret' => $secret,
			'qr_uri' => $qr_uri,
		) );
	}

	/**
	 * AJAX: Verify code and activate 2FA
	 *
	 * @return void
	 */
	public function ajax_setup_verify(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'arbricks' ) ) );
		}

		$code   = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		$user   = wp_get_current_user();
		$secret = get_user_meta( $user->ID, 'arbricks_2fa_temp_secret', true );

		if ( empty( $secret ) ) {
			wp_send_json_error( array( 'message' => __( 'No setup in progress.', 'arbricks' ) ) );
		}

		if ( $this->ga->verifyCode( $secret, $code ) ) {
			// Activate permanently.
			update_user_meta( $user->ID, 'arbricks_2fa_secret', $secret );
			delete_user_meta( $user->ID, 'arbricks_2fa_temp_secret' );
			update_user_meta( $user->ID, 'arbricks_2fa_enabled', '1' );
			wp_send_json_success();
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid 2FA code. Please try again.', 'arbricks' ) ) );
		}
	}

	/**
	 * AJAX: Disable 2FA
	 *
	 * @return void
	 */
	public function ajax_disable(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$user = wp_get_current_user();
		delete_user_meta( $user->ID, 'arbricks_2fa_secret' );
		delete_user_meta( $user->ID, 'arbricks_2fa_enabled' );
		delete_user_meta( $user->ID, 'arbricks_2fa_temp_secret' );
		wp_send_json_success();
	}

	/**
	 * Render 2FA field on login form
	 *
	 * @return void
	 */
	public function render_login_field(): void {
		?>
		<p class="arb-2fa-field">
			<label for="arbricks_2fa_code"><?php esc_html_e( '2FA Code (If enabled)', 'arbricks' ); ?></label>
			<input type="text" name="arbricks_2fa_code" id="arbricks_2fa_code" class="input" value="" size="6" maxlength="6" autocomplete="off" placeholder="000000" />
		</p>
		<style>
			.arb-2fa-field { margin-bottom: 20px; }
			.arb-2fa-field input { font-size: 20px; letter-spacing: 5px; text-align: center; }
		</style>
		<?php
	}

	/**
	 * Validate 2FA code during authentication
	 *
	 * @param mixed  $user User object or WP_Error.
	 * @param string $username Username.
	 * @param string $password Password.
	 * @return mixed
	 */
	public function validate_login( $user, $username, $password ) {
		// If already errored or not a valid user yet, don't interfere.
		if ( empty( $user ) || is_wp_error( $user ) ) {
			return $user;
		}

		// Check if 2FA is mandatory for this user.
		$user_id = $user->ID;
		$enabled = get_user_meta( $user_id, 'arbricks_2fa_enabled', true );
		$secret  = get_user_meta( $user_id, 'arbricks_2fa_secret', true );

		if ( ! $enabled || empty( $secret ) ) {
			return $user;
		}

		// Verify 2FA code.
		$code = isset( $_POST['arbricks_2fa_code'] ) ? sanitize_text_field( wp_unslash( $_POST['arbricks_2fa_code'] ) ) : '';

		if ( empty( $code ) ) {
			return new WP_Error( 'arbricks_2fa_required', '<strong>' . __( 'Error:', 'arbricks' ) . '</strong> ' . __( 'Please enter your 2FA code.', 'arbricks' ) );
		}

		if ( ! $this->ga->verifyCode( $secret, $code ) ) {
			return new WP_Error( 'arbricks_2fa_invalid', '<strong>' . __( 'Error:', 'arbricks' ) . '</strong> ' . __( 'Invalid 2FA code.', 'arbricks' ) );
		}

		return $user;
	}

	/**
	 * Render custom admin UI inside feature card
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		$user    = wp_get_current_user();
		$enabled = get_user_meta( $user->ID, 'arbricks_2fa_enabled', true );
		?>
		<div class="arbricks-2fa-setup" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
			<?php if ( $enabled ) : ?>
				<div class="arbricks-2fa-status-active" style="background: #f0fff0; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; display: flex; align-items: center; justify-content: space-between;">
					<div>
						<strong style="color: #28a745; display: block; margin-bottom: 5px;">✅ <?php esc_html_e( '2FA is Active', 'arbricks' ); ?></strong>
						<span class="description"><?php esc_html_e( 'Your account is protected with Two-Factor Authentication.', 'arbricks' ); ?></span>
					</div>
					<button type="button" id="arbricks-2fa-disable-btn" class="button button-link-delete">
						<?php esc_html_e( 'Disable 2FA', 'arbricks' ); ?>
					</button>
				</div>
			<?php else : ?>
				<button type="button" id="arbricks-2fa-setup-btn" class="button button-primary">
					<?php esc_html_e( 'Setup Two-Factor Authentication', 'arbricks' ); ?>
				</button>

				<div id="arbricks-2fa-setup-section" style="display: none; margin-top: 20px; background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
					<h4 style="margin-top: 0;"><?php esc_html_e( 'Setup Steps:', 'arbricks' ); ?></h4>
					
					<p>1. <?php esc_html_e( 'Scan this QR code with your Authenticator app:', 'arbricks' ); ?></p>
					<div id="arbricks-2fa-setup-qr" style="margin: 20px 0; display: flex; justify-content: center;"></div>
					
					<p>2. <?php esc_html_e( 'Or enter this secret key manually:', 'arbricks' ); ?> <br>
					   <code id="arbricks-2fa-secret-text" style="font-size: 16px; padding: 5px 10px; background: #f0f0f0;"></code></p>
					
					<hr style="margin: 20px 0;">
					
					<p>3. <?php esc_html_e( 'Enter the 6-digit code from the app to verify:', 'arbricks' ); ?></p>
					<div style="display: flex; gap: 10px; align-items: center;">
						<input type="text" id="arbricks-2fa-verify-code" class="regular-text" style="width: 120px; text-align: center; font-size: 18px;" placeholder="000000" maxlength="6">
						<button type="button" id="arbricks-2fa-verify-btn" class="button button-primary">
							<?php esc_html_e( 'Verify and Activate', 'arbricks' ); ?>
						</button>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<style>
			#arbricks-2fa-setup-qr img { border: 1px solid #ddd; padding: 10px; background: #fff; }
		</style>
		<?php
	}
}
