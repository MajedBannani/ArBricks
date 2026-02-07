<?php
/**
 * Feature: Math captcha for wp-login.php and WooCommerce My Account login/register
 *
 * Adds a simple math captcha to prevent automated login attempts.
 *
 * @package ArBricks
 * @since 2.0.1
 */

namespace ArBricks\Features;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Math_Captcha_Login
 *
 * Implements math captcha for WordPress and WooCommerce login forms.
 */
class Feature_Math_Captcha_Login implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'math_captcha_login';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Math Captcha (Login + WooCommerce)', 'arbricks' ),
			'description' => __( 'Add a simple math question to WordPress and WooCommerce login forms to block bots.', 'arbricks' ),
			'category'    => 'security',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Adds a simple math question (e.g., "What is 3 + 5?") to login and registration forms to block automated bots. Works on the default WordPress login (wp-login.php) and WooCommerce My Account forms.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Click "Save Changes" to activate.', 'arbricks' ),
					__( 'Test by logging out and visiting the login page - you will see the math question.', 'arbricks' ),
					__( 'If WooCommerce is active, also test the login and register form on the "My Account" page.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'No further settings needed - the feature works automatically after activation.', 'arbricks' ),
					__( 'Covers 3 forms: WordPress Login, WooCommerce Login, and WooCommerce Registration.', 'arbricks' ),
					__( 'Uses random numbers (1-9) for each request for better security.', 'arbricks' ),
					__( 'The question is processed locally - no external services are used, maintaining privacy.', 'arbricks' ),
					__( 'Automatically excluded for XML-RPC, REST API, WP-CLI, and AJAX requests.', 'arbricks' ),
					__( 'Easier for non-technical users than invisible traps or reCAPTCHA.', 'arbricks' ),
				),
				'examples' => array(
					__( 'Example Question: "Simple Verification: What is 7 + 4 ?"', 'arbricks' ),
					__( 'The user must type 11 to proceed and log in.', 'arbricks' ),
				),
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// wp-login.php render + validate.
		add_action( 'login_form', array( $this, 'render_wp_login' ) );
		add_filter( 'authenticate', array( $this, 'validate_wp_login' ), 20, 3 );

		// WooCommerce hooks only if Woo is active.
		if ( $this->is_woocommerce_active() ) {
			add_action( 'woocommerce_login_form', array( $this, 'render_wc_login' ) );
			add_filter( 'woocommerce_process_login_errors', array( $this, 'validate_wc_login' ), 10, 3 );

			add_action( 'woocommerce_register_form', array( $this, 'render_wc_register' ) );
			add_filter( 'woocommerce_registration_errors', array( $this, 'validate_wc_register' ), 10, 3 );
		}
	}

	/**
	 * Check if WooCommerce is active
	 *
	 * @return bool
	 */
	private function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Check if current request should bypass captcha
	 *
	 * @return bool
	 */
	private function should_bypass(): bool {
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return true;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return true;
		}
		return false;
	}

	/**
	 * Generate random number pair for captcha
	 *
	 * @return array Array with two random integers.
	 */
	private function generate_pair(): array {
		return array( random_int( 1, 9 ), random_int( 1, 9 ) );
	}

	/**
	 * Render captcha fields
	 *
	 * @param string $context Context key: login|wc_login|wc_register.
	 * @return void
	 */
	private function render_fields( string $context ): void {
		$pair = $this->generate_pair();
		$a    = (int) $pair[0];
		$b    = (int) $pair[1];

		$id = 'arb_math_captcha_answer_' . sanitize_key( $context );

		$label = sprintf(
			/* translators: 1: first number, 2: second number */
			esc_html__( 'Simple verification: what is %1$d + %2$d ?', 'arbricks' ),
			$a,
			$b
		);

		printf(
			'<p class="arb-math-captcha form-row"><label for="%1$s">%2$s</label><input type="number" name="arb_math_captcha_answer" id="%1$s" class="input" value="" autocomplete="off" required /><input type="hidden" name="arb_math_captcha_a" value="%3$s"><input type="hidden" name="arb_math_captcha_b" value="%4$s">',
			esc_attr( $id ),
			esc_html( $label ),
			esc_attr( (string) $a ),
			esc_attr( (string) $b )
		);

		$nonce_action = $this->nonce_action_for_context( $context );
		wp_nonce_field( $nonce_action, 'arb_math_captcha_nonce' );

		echo '</p>';
	}

	/**
	 * Get nonce action for context
	 *
	 * @param string $context Context key.
	 * @return string Nonce action.
	 */
	private function nonce_action_for_context( string $context ): string {
		if ( 'wc_login' === $context ) {
			return 'arb_math_captcha_wc_login';
		}
		if ( 'wc_register' === $context ) {
			return 'arb_math_captcha_wc_register';
		}
		return 'arb_math_captcha_login';
	}

	/**
	 * Validate captcha for a given context
	 *
	 * @param string $context Context key: login|wc_login|wc_register.
	 * @return true|WP_Error
	 */
	private function validate( string $context ) {

		if ( $this->should_bypass() ) {
			return true;
		}

		// Ensure it's a form submit attempt (avoid blocking other flows).
		if ( empty( $_POST ) ) {
			return true;
		}

		$nonce_action = $this->nonce_action_for_context( $context );

		$nonce = isset( $_POST['arb_math_captcha_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['arb_math_captcha_nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return new WP_Error( 'captcha_invalid', __( 'Security verification error. Please try again.', 'arbricks' ) );
		}

		$a = isset( $_POST['arb_math_captcha_a'] ) ? (int) wp_unslash( $_POST['arb_math_captcha_a'] ) : null;
		$b = isset( $_POST['arb_math_captcha_b'] ) ? (int) wp_unslash( $_POST['arb_math_captcha_b'] ) : null;
		$ans = isset( $_POST['arb_math_captcha_answer'] ) ? (int) wp_unslash( $_POST['arb_math_captcha_answer'] ) : null;

		if ( null === $a || null === $b || null === $ans ) {
			return new WP_Error( 'captcha_missing', __( 'You must answer the verification question.', 'arbricks' ) );
		}

		if ( $ans !== ( $a + $b ) ) {
			return new WP_Error( 'captcha_error', __( 'Incorrect verification answer.', 'arbricks' ) );
		}

		return true;
	}

	/**
	 * Render captcha for wp-login.php
	 *
	 * @return void
	 */
	public function render_wp_login(): void {
		$this->render_fields( 'login' );
	}

	/**
	 * Validate captcha for wp-login.php
	 *
	 * @param mixed  $user User object or WP_Error.
	 * @param string $username Username.
	 * @param string $password Password.
	 * @return mixed
	 */
	public function validate_wp_login( $user, $username, $password ) {

		if ( $this->should_bypass() ) {
			return $user;
		}

		// Only validate on actual wp-login submit.
		if ( ! isset( $_POST['log'], $_POST['pwd'] ) ) {
			return $user;
		}

		$valid = $this->validate( 'login' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		return $user;
	}

	/**
	 * Render captcha for WooCommerce login
	 *
	 * @return void
	 */
	public function render_wc_login(): void {
		$this->render_fields( 'wc_login' );
	}

	/**
	 * Validate captcha for WooCommerce login
	 *
	 * @param WP_Error $errors Errors object.
	 * @param string   $username Username.
	 * @param string   $password Password.
	 * @return WP_Error
	 */
	public function validate_wc_login( $errors, $username, $password ) {

		// Only validate on actual Woo login submit.
		if ( ! isset( $_POST['username'], $_POST['password'] ) ) {
			return $errors;
		}

		$valid = $this->validate( 'wc_login' );
		if ( is_wp_error( $valid ) ) {
			$errors->add( $valid->get_error_code(), $valid->get_error_message() );
		}

		return $errors;
	}

	/**
	 * Render captcha for WooCommerce registration
	 *
	 * @return void
	 */
	public function render_wc_register(): void {
		$this->render_fields( 'wc_register' );
	}

	/**
	 * Validate captcha for WooCommerce registration
	 *
	 * @param WP_Error $errors Errors object.
	 * @param string   $username Username.
	 * @param string   $email Email.
	 * @return WP_Error
	 */
	public function validate_wc_register( $errors, $username, $email ) {

		// Only validate on actual Woo register submit.
		if ( ! isset( $_POST['email'] ) ) {
			return $errors;
		}

		$valid = $this->validate( 'wc_register' );
		if ( is_wp_error( $valid ) ) {
			$errors->add( $valid->get_error_code(), $valid->get_error_message() );
		}

		return $errors;
	}
	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
