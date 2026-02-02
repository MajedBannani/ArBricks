<?php
/**
 * Feature: Disable Comments (Sitewide)
 *
 * Completely disables comments across the entire site including frontend,
 * admin UI, feeds, and REST endpoints.
 *
 * @package ArBricks
 * @since 2.0.1
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_Disable_Comments_Sitewide
 *
 * Disables all comment functionality across WordPress.
 */
class Feature_Disable_Comments_Sitewide implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'disable_comments_sitewide';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Disable Comments (Sitewide)', 'arbricks' ),
			'description' => __( 'Disable comments everywhere (frontend + admin), remove UI entries, disable comment feeds, and remove REST comments endpoints.', 'arbricks' ),
			'category'    => 'tools',
			'shortcode'   => '',
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'disable_comments' ), 1 );
		add_action( 'admin_menu', array( $this, 'remove_comments_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'wp_before_admin_bar_render', array( $this, 'remove_admin_bar_comments' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_comment_reply' ) );
		add_filter( 'rest_endpoints', array( $this, 'disable_rest_api_comments' ) );
	}

	/**
	 * Disable comments on init
	 *
	 * @return void
	 */
	public function disable_comments(): void {
		// Close comments and pings.
		add_filter( 'comments_open', '__return_false', 20, 2 );
		add_filter( 'pings_open', '__return_false', 20, 2 );

		// Return empty comments array.
		add_filter( 'comments_array', '__return_empty_array', 10, 2 );

		// Remove comments/trackbacks support from all post types.
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
			}
			if ( post_type_supports( $post_type, 'trackbacks' ) ) {
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}

		// Disable comment feeds.
		add_action( 'do_feed_rss2_comments', array( $this, 'disable_comment_feed' ), 1 );
		add_action( 'do_feed_atom_comments', array( $this, 'disable_comment_feed' ), 1 );
	}

	/**
	 * Disable comment feed
	 *
	 * @return void
	 */
	public function disable_comment_feed(): void {
		wp_die(
			esc_html__( 'Comments are disabled on this site.', 'arbricks' ),
			'',
			array( 'response' => 404 )
		);
	}

	/**
	 * Remove comments menu from admin
	 *
	 * @return void
	 */
	public function remove_comments_menu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * Redirect if accessing comments page in admin
	 *
	 * @return void
	 */
	public function admin_redirects(): void {
		global $pagenow;

		if ( 'edit-comments.php' === $pagenow ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		// Remove dashboard recent comments widget.
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * Remove comments from admin bar
	 *
	 * @return void
	 */
	public function remove_admin_bar_comments(): void {
		global $wp_admin_bar;

		if ( $wp_admin_bar ) {
			$wp_admin_bar->remove_menu( 'comments' );
		}
	}

	/**
	 * Dequeue comment-reply script
	 *
	 * @return void
	 */
	public function dequeue_comment_reply(): void {
		wp_deregister_script( 'comment-reply' );
	}

	/**
	 * Disable REST API comments endpoints
	 *
	 * @param array $endpoints REST endpoints.
	 * @return array
	 */
	public function disable_rest_api_comments( $endpoints ) {
		// Remove comments endpoints.
		unset( $endpoints['/wp/v2/comments'] );
		unset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] );

		return $endpoints;
	}
}
