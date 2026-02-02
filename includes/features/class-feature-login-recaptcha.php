<?php
/**
 * Feature: Google reCAPTCHA on Login
 *
 * Adds Google reCAPTCHA v2 checkbox to wp-login.php.
 *
 * @package ArBricks
 * @since 2.0.2
 */

namespace ArBricks\Features;

use ArBricks\Options;
use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Login_Recaptcha
 *
 * Login reCAPTCHA protection.
 */
class Feature_Login_Recaptcha implements Feature_Interface {

	/**
	 * reCAPTCHA API URL
	 */
	const API_URL = 'https://www.google.com/recaptcha/api/siteverify';

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'login_recaptcha';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Login reCAPTCHA (v2)', 'arbricks' ),
			'description' => __( 'Add Google reCAPTCHA v2 checkbox to login form.', 'arbricks' ),
			'category'    => 'security',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Adds Google reCAPTCHA v2 ("I\'m not a robot" checkbox) to the WordPress login page (wp-login.php) to prevent automated brute force attacks and bot login attempts.', 'arbricks' ),
				'how_to'   => array(
					__( 'Get free API keys from Google reCAPTCHA: https://www.google.com/recaptcha/admin', 'arbricks' ),
					__( 'Select "reCAPTCHA v2" and choose "I\'m not a robot Checkbox"', 'arbricks' ),
					__( 'Add your domain and copy the Site Key and Secret Key', 'arbricks' ),
					__( 'Enable the feature toggle above', 'arbricks' ),
					__( 'Paste your Site Key and Secret Key into the fields below', 'arbricks' ),
					__( 'Optionally customize the error message and timeout', 'arbricks' ),
					__( 'Click "Save Changes" to activate', 'arbricks' ),
					__( 'Test by logging out and trying to log in - you should see the reCAPTCHA checkbox', 'arbricks' ),
				),
				'notes'    => array(
					__( 'External Service: User IP addresses are sent to Google\'s servers for verification (required for reCAPTCHA to work)', 'arbricks' ),
					__( 'Requires valid API keys - invalid or missing keys will block ALL logins including administrators', 'arbricks' ),
					__( 'Only protects wp-login.php - does not affect WooCommerce login forms or custom login pages', 'arbricks' ),
					__( 'Bypass for Admins: When enabled, users with administrator role skip reCAPTCHA verification', 'arbricks' ),
					__( 'Timeout setting controls how long to wait for Google\'s API response (default 5 seconds)', 'arbricks' ),
				),
				'examples' => array(
					__( 'Site Key format: 6LcExampleKey123ABCdef...', 'arbricks' ),
					__( 'Secret Key format: 6LcExampleSecret456XYZabc...', 'arbricks' ),
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
			'site_key'          => array(
				'type'        => 'text',
				'label'       => __( 'Site Key', 'arbricks' ),
				'description' => __( 'Google reCAPTCHA site key (required)', 'arbricks' ),
				'default'     => '',
				'placeholder' => '6Lc...',
			),
			'secret_key'        => array(
				'type'        => 'text',
				'label'       => __( 'Secret Key', 'arbricks' ),
				'description' => __( 'Google reCAPTCHA secret key (required)', 'arbricks' ),
				'default'     => '',
				'placeholder' => '6Lc...',
			),
			'error_message'     => array(
				'type'        => 'text',
				'label'       => __( 'Error Message', 'arbricks' ),
				'description' => __( 'Message shown on validation failure', 'arbricks' ),
				'default'     => __( 'يرجى التحقق من أنك لست روبوت.', 'arbricks' ),
				'placeholder' => __( 'Please verify you are not a robot.', 'arbricks' ),
			),
			'bypass_for_admins' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Bypass for Admins', 'arbricks' ),
				'description' => __( 'Skip reCAPTCHA for administrator accounts', 'arbricks' ),
				'default'     => false,
			),
			'timeout_seconds'   => array(
				'type'        => 'text',
				'label'       => __( 'Timeout (seconds)', 'arbricks' ),
				'description' => __( 'API request timeout', 'arbricks' ),
				'default'     => '5',
				'placeholder' => '5',
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_recaptcha_script' ) );
		add_action( 'login_form', array( $this, 'display_recaptcha_field' ) );
		add_filter( 'authenticate', array( $this, 'verify_recaptcha' ), 30, 3 );
	}

	/**
	 * Enqueue reCAPTCHA script on login page
	 *
	 * @return void
	 */
	public function enqueue_recaptcha_script(): void {
		$settings = Options::get_feature_settings( self::id() );
		$site_key = trim( $settings['site_key'] ?? '' );

		if ( empty( $site_key ) ) {
			return;
		}

		wp_enqueue_script(
			'google-recaptcha',
			'https://www.google.com/recaptcha/api.js',
			array(),
			null,
			true
		);
	}

	/**
	 * Display reCAPTCHA field in login form
	 *
	 * @return void
	 */
	public function display_recaptcha_field(): void {
		$settings = Options::get_feature_settings( self::id() );
		$site_key = trim( $settings['site_key'] ?? '' );

		if ( empty( $site_key ) ) {
			echo '<p style="color:red;">' . esc_html__( 'reCAPTCHA site key not configured.', 'arbricks' ) . '</p>';
			return;
		}

		?>
		<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>" style="margin-bottom: 10px;"></div>
		<?php
	}

	/**
	 * Verify reCAPTCHA on login
	 *
	 * @param WP_User|WP_Error|null $user User object or error.
	 * @param string                $username Username.
	 * @param string                $password Password.
	 * @return WP_User|WP_Error
	 */
	public function verify_recaptcha( $user, $username, $password ) {
		// Skip if already an error.
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Bypass for non-login requests.
		if ( $this->should_bypass_check() ) {
			return $user;
		}

		// Only validate on actual login submit.
		if ( empty( $username ) || empty( $password ) ) {
			return $user;
		}

		$settings   = Options::get_feature_settings( self::id() );
		$secret_key = trim( $settings['secret_key'] ?? '' );

		// Skip if not configured.
		if ( empty( $secret_key ) ) {
			return $user;
		}

		// Bypass for admins if enabled.
		if ( ! empty( $settings['bypass_for_admins'] ) && $user instanceof WP_User && user_can( $user, 'manage_options' ) ) {
			return $user;
		}

		// Get reCAPTCHA response.
		$recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '';

		if ( empty( $recaptcha_response ) ) {
			$error_message = $settings['error_message'] ?? __( 'Please complete the reCAPTCHA.', 'arbricks' );
			return new WP_Error( 'recaptcha_missing', esc_html( $error_message ) );
		}

		// Verify with Google API.
		$timeout = absint( $settings['timeout_seconds'] ?? 5 );
		if ( $timeout < 1 ) {
			$timeout = 5;
		}

		$response = wp_remote_post(
			self::API_URL,
			array(
				'timeout' => $timeout,
				'body'    => array(
					'secret'   => $secret_key,
					'response' => $recaptcha_response,
					'remoteip' => $this->get_user_ip(),
				),
			)
		);

		// Handle WP_Error.
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'recaptcha_error',
				sprintf(
					/* translators: %s: error message */
					__( 'reCAPTCHA verification failed: %s', 'arbricks' ),
					$response->get_error_message()
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check verification result.
		if ( empty( $data['success'] ) ) {
			$error_message = $settings['error_message'] ?? __( 'reCAPTCHA verification failed.', 'arbricks' );
			return new WP_Error( 'recaptcha_failed', esc_html( $error_message ) );
		}

		return $user;
	}

	/**
	 * Check if we should bypass reCAPTCHA check
	 *
	 * @return bool
	 */
	private function should_bypass_check(): bool {
		// Bypass for XMLRPC.
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return true;
		}

		// Bypass for REST API.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		// Bypass for WP-CLI.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}

		// Bypass for AJAX.
		if ( wp_doing_ajax() ) {
			return true;
		}

		return false;
	}

	/**
	 * Get user IP address safely
	 *
	 * @return string
	 */
	private function get_user_ip(): string {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
