<?php
/**
 * Feature: Admin Clean Dashboard
 *
 * Allows hiding default WordPress dashboard widgets to declutter the admin area.
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
 * Class Feature_ArBricks_Admin_Clean_Dashboard
 */
class Feature_ArBricks_Admin_Clean_Dashboard implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_admin_clean_dashboard';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Clean Dashboard', 'arbricks' ),
			'description' => __( 'Reduce clutter in the WordPress dashboard by hiding default widgets you don\'t use.', 'arbricks' ),
			'category'    => 'tools',
			'help'        => array(
				'summary'  => __( 'Helps you customize the dashboard by hiding default widgets you don\'t need, giving you a cleaner and faster admin interface.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the main feature then choose the widgets you want to hide from the options below.', 'arbricks' ),
					__( 'Click "Save Changes" to see the result on the main dashboard.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Widgets available to hide: At a Glance, Quick Draft, Activity, Recent Comments, and WordPress Events and News.', 'arbricks' ),
					__( 'This feature does not delete data; it only hides widgets from view.', 'arbricks' ),
					__( 'You can re-enable any widget at any time by unchecking it here.', 'arbricks' ),
					__( 'Note: Some other plugins may add their own widgets; this feature focuses on default WordPress widgets.', 'arbricks' ),
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
			'arbricks_hide_right_now'      => array(
				'type'        => 'checkbox',
				'label'       => __( 'Hide "At a Glance"', 'arbricks' ),
				'description' => __( 'Hide the "At a Glance" widget which displays the number of posts and pages.', 'arbricks' ),
				'default'     => false,
			),
			'arbricks_hide_activity'       => array(
				'type'        => 'checkbox',
				'label'       => __( 'Hide "Activity"', 'arbricks' ),
				'description' => __( 'Hide the "Activity" widget which displays recent posts and comments.', 'arbricks' ),
				'default'     => false,
			),
			'arbricks_hide_quick_draft'    => array(
				'type'        => 'checkbox',
				'label'       => __( 'Hide "Quick Draft"', 'arbricks' ),
				'description' => __( 'Hide the "Quick Draft" widget for writing quick post ideas.', 'arbricks' ),
				'default'     => false,
			),
			'arbricks_hide_recent_comments' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Hide "Recent Comments"', 'arbricks' ),
				'description' => __( 'Hide the "Recent Comments" widget.', 'arbricks' ),
				'default'     => false,
			),
			'arbricks_hide_wordpress_news' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Hide "WordPress Events and News"', 'arbricks' ),
				'description' => __( 'Hide the widget that displays official WordPress news.', 'arbricks' ),
				'default'     => false,
			),
			'arbricks_hide_welcome_panel'  => array(
				'type'        => 'checkbox',
				'label'       => __( 'Hide "Welcome Panel"', 'arbricks' ),
				'description' => __( 'Hide the welcome panel that appears on the dashboard.', 'arbricks' ),
				'default'     => false,
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'wp_dashboard_setup', array( $this, 'clean_dashboard_widgets' ), 999 );
		
		// Welcome panel is handled slightly differently.
		$settings = Options::get_feature_settings( self::id() );
		if ( ! empty( $settings['arbricks_hide_welcome_panel'] ) ) {
			remove_action( 'welcome_panel', 'wp_welcome_panel' );
		}
	}

	/**
	 * Remove selected dashboard widgets
	 *
	 * @return void
	 */
	public function clean_dashboard_widgets(): void {
		$settings = Options::get_feature_settings( self::id() );

		// Right Now (At a Glance).
		if ( ! empty( $settings['arbricks_hide_right_now'] ) ) {
			remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
		}

		// Activity.
		if ( ! empty( $settings['arbricks_hide_activity'] ) ) {
			remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
		}

		// Recent Comments.
		if ( ! empty( $settings['arbricks_hide_recent_comments'] ) ) {
			remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		}

		// Quick Draft.
		if ( ! empty( $settings['arbricks_hide_quick_draft'] ) ) {
			remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		}

		// WordPress News.
		if ( ! empty( $settings['arbricks_hide_wordpress_news'] ) ) {
			remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		}
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
