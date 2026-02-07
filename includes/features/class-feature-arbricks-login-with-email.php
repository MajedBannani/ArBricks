<?php
/**
 * Feature: Login with Email
 *
 * Allows users to log in using their email address instead of just their username.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Login_With_Email
 */
class Feature_ArBricks_Login_With_Email implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_login_with_email';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Login with Email', 'arbricks' ),
			'description' => __( 'Allows users to log in using their email address or username instead of just their username.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary'  => __( 'This feature allows users to log in using their email address instead of just their username, making it easier to access the site.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Users can now type their email address in the "Username" field on the login page.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'This works by converting the entered email to its matching username before the authentication process begins.', 'arbricks' ),
					__( 'The feature does not change the login page interface or any error messages.', 'arbricks' ),
					__( 'Important Note: If you are using a security plugin that customizes the login process (e.g., 2FA or OTP), a conflict might occur. Disable one of them if you face issues.', 'arbricks' ),
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
		add_action( 'wp_authenticate', array( $this, 'handle_email_login' ), 10, 2 );
	}

	/**
	 * Handle authentication using email address
	 *
	 * @param string $username Username or email.
	 * @param string $password Password.
	 * @return void
	 */
	public function handle_email_login( &$username, &$password ): void {
		if ( empty( $username ) || ! is_email( $username ) ) {
			return;
		}

		$user = get_user_by( 'email', $username );

		if ( $user ) {
			$username = $user->user_login;
		}
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
