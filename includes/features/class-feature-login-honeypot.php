<?php
/**
 * Feature: Login Honeypot
 *
 * Adds honeypot field to wp-login to block bots.
 *
 * @package ArBricks
 * @since 2.0.2
 */

namespace ArBricks\Features;

use ArBricks\Options;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Login_Honeypot
 *
 * Honeypot anti-bot protection for login.
 */
class Feature_Login_Honeypot implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'login_honeypot';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Login Honeypot', 'arbricks' ),
			'description' => __( 'Add a hidden field (honeypot) to block bots during login.', 'arbricks' ),
			'category'    => 'security',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'Adds an invisible field (honeypot) to the login form that bots will fill but humans won\'t see. Any login attempt with a filled honeypot is automatically blocked, preventing automated bot attacks without requiring any user interaction.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'You can customize the honeypot field name (advanced users only).', 'arbricks' ),
					__( 'You can customize the block message displayed to bots.', 'arbricks' ),
					__( 'Choose the concealment method: CSS (recommended) or Hidden Input.', 'arbricks' ),
					__( 'Click "Save Changes" to activate.', 'arbricks' ),
					__( 'No further settings needed - protection is automatic.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'CSS Hide (recommended): The field is visually hidden but remains available for bots that ignore CSS files.', 'arbricks' ),
					__( 'Hidden Field: Uses type="hidden" which sophisticated bots might detect.', 'arbricks' ),
					__( 'Field Name: Only change if you feel bots have learned your specific field name (rare).', 'arbricks' ),
					__( 'No external services used - the feature works locally, maintaining privacy.', 'arbricks' ),
					__( 'Works alongside other login security features (reCAPTCHA, Math Captcha).', 'arbricks' ),
					__( 'Automatically excluded for XML-RPC, REST API, WP-CLI, and AJAX requests.', 'arbricks' ),
				),
				'examples' => array(
					__( 'Default Field Name: login_honeypot', 'arbricks' ),
					__( 'Example Custom Field Name: user_website_url (something bots will definitely fill).', 'arbricks' ),
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
			'field_name'      => array(
				'type'        => 'text',
				'label'       => __( 'Field Name', 'arbricks' ),
				'description' => __( 'The name of the invisible honeypot field.', 'arbricks' ),
				'default'     => 'login_honeypot',
				'placeholder' => 'login_honeypot',
			),
			'block_message'   => array(
				'type'        => 'text',
				'label'       => __( 'Block Message', 'arbricks' ),
				'description' => __( 'The message displayed to blocked bots.', 'arbricks' ),
				'default'     => __( 'Login attempt blocked.', 'arbricks' ),
				'placeholder' => __( 'Login attempt blocked.', 'arbricks' ),
			),
			'enable_css_hide' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Enable CSS Hiding', 'arbricks' ),
				'description' => __( 'Use CSS code to hide the honeypot field.', 'arbricks' ),
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
		add_action( 'login_form', array( $this, 'add_honeypot_field' ) );
		add_action( 'login_head', array( $this, 'add_honeypot_css' ) );
		add_filter( 'authenticate', array( $this, 'check_honeypot' ), 20, 3 );
	}

	/**
	 * Add honeypot field to login form
	 *
	 * @return void
	 */
	public function add_honeypot_field(): void {
		$settings   = Options::get_feature_settings( self::id() );
		$field_name = sanitize_key( $settings['field_name'] ?? 'login_honeypot' );
		$use_css    = ! empty( $settings['enable_css_hide'] );

		if ( $use_css ) {
			?>
			<p class="arbricks-honeypot-field">
				<label for="<?php echo esc_attr( $field_name ); ?>" aria-hidden="true">
					<?php esc_html_e( 'Leave this field empty', 'arbricks' ); ?>
				</label>
				<input 
					type="text" 
					name="<?php echo esc_attr( $field_name ); ?>" 
					id="<?php echo esc_attr( $field_name ); ?>" 
					value="" 
					tabindex="-1" 
					autocomplete="off"
					aria-hidden="true"
				>
			</p>
			<?php
		} else {
			// Use hidden input without CSS.
			?>
			<input 
				type="hidden" 
				name="<?php echo esc_attr( $field_name ); ?>" 
				value=""
			>
			<?php
		}
	}

	/**
	 * Add CSS to hide honeypot field
	 *
	 * @return void
	 */
	public function add_honeypot_css(): void {
		$settings = Options::get_feature_settings( self::id() );
		$use_css  = ! empty( $settings['enable_css_hide'] );

		if ( ! $use_css ) {
			return;
		}

		?>
		<style>
		.arbricks-honeypot-field {
			position: absolute !important;
			left: -9999px !important;
			width: 1px !important;
			height: 1px !important;
			overflow: hidden !important;
			clip: rect(0, 0, 0, 0) !important;
			white-space: nowrap !important;
		}
		</style>
		<?php
	}

	/**
	 * Check honeypot on login
	 *
	 * @param WP_User|WP_Error|null $user User object or error.
	 * @param string                $username Username.
	 * @param string                $password Password.
	 * @return WP_User|WP_Error
	 */
	public function check_honeypot( $user, $username, $password ) {
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
		$field_name = sanitize_key( $settings['field_name'] ?? 'login_honeypot' );

		// Get honeypot value.
		$honeypot_value = isset( $_POST[ $field_name ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) ) : '';

		// If honeypot is filled, it's a bot.
		if ( ! empty( $honeypot_value ) ) {
			$block_message = $settings['block_message'] ?? __( 'Login attempt blocked.', 'arbricks' );
			return new WP_Error( 'honeypot_failed', esc_html( $block_message ) );
		}

		return $user;
	}

	/**
	 * Check if we should bypass honeypot check
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
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}

}
