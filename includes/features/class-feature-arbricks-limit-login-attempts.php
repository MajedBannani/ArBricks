<?php
/**
 * Feature: Limit Login Attempts
 *
 * Protects the site from brute force attacks by limiting failed login attempts.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

use ArBricks\Options;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Limit_Login_Attempts
 */
class Feature_ArBricks_Limit_Login_Attempts implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_limit_login_attempts';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Limit Login Attempts', 'arbricks' ),
			'description' => __( 'Protect your site from Brute Force attacks by limiting the number of failed login attempts across all forms.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary'  => __( 'Brute force attacks are repeated attempts by hackers to guess passwords to access your account. This feature blocks any IP address that exceeds a certain number of failed attempts for a specified period of time.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature and set the maximum attempts and lockout duration.', 'arbricks' ),
					__( 'When the limit is exceeded, the IP address will be automatically blocked from further attempts.', 'arbricks' ),
					__( 'Attempts are automatically reset upon successful login.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'This feature protects all login forms including the default WordPress form, WooCommerce, and other membership forms.', 'arbricks' ),
					__( 'This feature does not affect the REST API or automated system processes.', 'arbricks' ),
					__( 'Important Note: If you are using another security plugin that provides the same functionality, please enable only one to avoid conflict.', 'arbricks' ),
					__( 'If you are accidentally locked out, you can wait until the lockout duration expires or disable the feature via direct file access if necessary.', 'arbricks' ),
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
			'arbricks_lla_max_attempts'     => array(
				'type'        => 'number',
				'label'       => __( 'Maximum Failed Attempts', 'arbricks' ),
				'description' => __( 'The number of allowed failed attempts before lockout (Default: 5).', 'arbricks' ),
				'default'     => 5,
			),
			'arbricks_lla_lockout_duration' => array(
				'type'        => 'number',
				'label'       => __( 'Lockout Duration (Minutes)', 'arbricks' ),
				'description' => __( 'The duration in minutes for which the IP will be blocked (Default: 15).', 'arbricks' ),
				'default'     => 15,
			),
			'arbricks_lla_reset_on_success' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Reset on Success', 'arbricks' ),
				'description' => __( 'Reset the failed attempts counter after a successful login.', 'arbricks' ),
				'default'     => true,
			),
			'arbricks_lla_scope'            => array(
				'type'        => 'radio',
				'label'       => __( 'Protection Scope', 'arbricks' ),
				'description' => __( 'Choose which forms you want to protect.', 'arbricks' ),
				'options'     => array(
					'all'      => __( 'All Login Forms (Recommended)', 'arbricks' ),
					'wp-login' => __( 'Default wp-login.php only', 'arbricks' ),
				),
				'default'     => 'all',
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Priority 30 to run after standard validation but before core finishes.
		add_filter( 'authenticate', array( $this, 'intercept_authentication' ), 30, 3 );
		
		// Reset logic.
		add_action( 'wp_login', array( $this, 'reset_attempts_on_success' ), 10, 2 );
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private function get_user_ip(): string {
		// Prefer REMOTE_ADDR for security unless specific proxy headers are defined by the admin.
		// Blindly trusting X-Forwarded-For is an IP spoofing risk.
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			return trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		}
		return '';
	}

	/**
	 * Intercept authentication attempt
	 *
	 * @param mixed  $user     User object or WP_Error.
	 * @param string $username Username.
	 * @param string $password Password.
	 * @return mixed
	 */
	public function intercept_authentication( $user, $username, $password ) {
		// Bypass for CLI, REST, etc.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return $user;
		}
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return $user;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $user;
		}

		$ip = $this->get_user_ip();
		if ( empty( $ip ) ) {
			return $user;
		}

		$settings = Options::get_feature_settings( self::id() );
		$scope    = $settings['arbricks_lla_scope'] ?? 'all';

		// If scope is restricted to wp-login.php, check current page.
		if ( 'wp-login' === $scope && false === stripos( $_SERVER['SCRIPT_NAME'] ?? '', 'wp-login.php' ) ) {
			return $user;
		}

		// Check if currently locked out.
		$lockout_key = 'arb_lla_lock_' . md5( $ip );
		if ( get_transient( $lockout_key ) ) {
			return new WP_Error( 'too_many_retries', __( '<strong>Error</strong>: Too many failed login attempts. Please try again later.', 'arbricks' ) );
		}

		// Handle failed attempt results.
		if ( is_wp_error( $user ) && ! empty( $username ) ) {
			$this->track_failed_attempt( $ip, $settings );
		}

		return $user;
	}

	/**
	 * Track a failed login attempt
	 *
	 * @param string $ip       User IP.
	 * @param array  $settings Feature settings.
	 * @return void
	 */
	private function track_failed_attempt( string $ip, array $settings ): void {
		$attempts_key = 'arb_lla_att_' . md5( $ip );
		$attempts     = (int) get_transient( $attempts_key );
		$attempts++;

		$max_attempts     = (int) ( $settings['arbricks_lla_max_attempts'] ?? 5 );
		$lockout_duration = (int) ( $settings['arbricks_lla_lockout_duration'] ?? 15 );

		if ( $attempts >= $max_attempts ) {
			// Trigger lockout.
			$lockout_key = 'arb_lla_lock_' . md5( $ip );
			set_transient( $lockout_key, true, $lockout_duration * MINUTE_IN_SECONDS );
			delete_transient( $attempts_key );
		} else {
			// Update attempts (expire after 1 hour of no activity).
			set_transient( $attempts_key, $attempts, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Reset attempts on successful login
	 *
	 * @param string    $user_login Username.
	 * @param \WP_User $user       User object.
	 * @return void
	 */
	public function reset_attempts_on_success( $user_login, $user ): void {
		$settings = Options::get_feature_settings( self::id() );
		if ( empty( $settings['arbricks_lla_reset_on_success'] ) ) {
			return;
		}

		$ip = $this->get_user_ip();
		if ( empty( $ip ) ) {
			return;
		}

		delete_transient( 'arb_lla_att_' . md5( $ip ) );
		delete_transient( 'arb_lla_lock_' . md5( $ip ) );
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
