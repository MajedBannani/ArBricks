<?php
/**
 * Feature: Last User Login Column
 *
 * Tracks user login time and displays it in a custom column in the Users table.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Last_Login_Column
 */
class Feature_ArBricks_Last_Login_Column implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string {
		return 'arbricks_last_login_column';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array Feature metadata.
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Show Last User Login', 'arbricks' ),
			'description' => __( 'Adds a column to display the last time a user logged in to the site.', 'arbricks' ),
			'category'    => 'tools',
			'help'        => array(
				'summary' => __( 'Helps site administrators monitor user activity by displaying the date and time of the last successful login for each user in the Users table.', 'arbricks' ),
				'how_to'  => array(
					__( 'Enable the toggle above to start tracking last login and add the column.', 'arbricks' ),
					__( 'Navigate to the "Users" screen in the dashboard.', 'arbricks' ),
					__( 'You will find a new column titled "Last Login" displaying the precise time.', 'arbricks' ),
				),
				'notes'   => array(
					__( 'Only the login time is stored (Unix Timestamp).', 'arbricks' ),
					__( 'No other sensitive data like IP address or browser type is stored.', 'arbricks' ),
					__( 'This feature only affects the dashboard and does not track user activity after login.', 'arbricks' ),
					__( 'Users who haven\'t logged in since the feature was enabled will see "Never logged in".', 'arbricks' ),
				),
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
		// Track login.
		add_action( 'wp_login', array( $this, 'track_last_login' ), 10, 2 );

		// Only show column in admin.
		if ( is_admin() ) {
			add_filter( 'manage_users_columns', array( $this, 'add_last_login_column' ) );
			add_filter( 'manage_users_custom_column', array( $this, 'render_last_login_column' ), 10, 3 );
		}
	}

	/**
	 * Track user last login time.
	 *
	 * @param string   $user_login User login name.
	 * @param \WP_User $user       User object.
	 */
	public function track_last_login( $user_login, $user ): void {
		update_user_meta( $user->ID, 'arbricks_last_login', time() );
	}

	/**
	 * Add the Last Login column to the Users table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_last_login_column( $columns ) {
		$columns['arbricks_last_login'] = __( 'Last Login', 'arbricks' );
		return $columns;
	}

	/**
	 * Render the content of the Last Login column.
	 *
	 * @param string $output      Custom column output.
	 * @param string $column_name Column name.
	 * @param int    $user_id     User ID.
	 * @return string
	 */
	public function render_last_login_column( $output, $column_name, $user_id ) {
		if ( 'arbricks_last_login' !== $column_name ) {
			return $output;
		}

		$last_login = get_user_meta( $user_id, 'arbricks_last_login', true );

		if ( ! $last_login ) {
			return '<span style="color: #999;">' . esc_html__( 'Never logged in', 'arbricks' ) . '</span>';
		}

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		return date_i18n( "{$date_format} {$time_format}", $last_login );
	}

	/**
	 * Render custom admin UI on the settings page
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		// No additional settings required for this feature.
	}
}
