<?php
/**
 * Feature: Disable RSS Feeds
 *
 * Disables RSS feeds for posts and/or comments to reduce overhead and spam surface.
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
 * Class Feature_ArBricks_Disable_RSS_Feeds
 */
class Feature_ArBricks_Disable_RSS_Feeds implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_disable_rss_feeds';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Disable RSS Feeds', 'arbricks' ),
			'description' => __( 'Disable RSS feeds for posts and comments to reduce spam and unnecessary server overhead.', 'arbricks' ),
			'category'    => 'seo',
			'help'        => array(
				'summary'  => __( 'RSS feeds allowing users and software to follow your site updates automatically. You may want to disable them if you don\'t use them to reduce server resource consumption by bots and prevent content scraping.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature and choose what to disable (Posts, Comments, or All).', 'arbricks' ),
					__( 'Choose the response type (410, 404, or redirect to home).', 'arbricks' ),
					__( 'Removing feed links from the head is recommended to hide their presence.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Warning: If you use services depending on RSS like automated newsletters or Jetpack, do not enable this.', 'arbricks' ),
					__( '410 (Gone) response is recommended for search engines.', 'arbricks' ),
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
			'arbricks_drf_scope'             => array(
				'type'        => 'radio',
				'label'       => __( 'Disable Scope', 'arbricks' ),
				'description' => __( 'Choose which feeds to disable.', 'arbricks' ),
				'options'     => array(
					'all'      => __( 'Disable All (Recommended)', 'arbricks' ),
					'posts'    => __( 'Disable Posts Feeds only', 'arbricks' ),
					'comments' => __( 'Disable Comments Feeds only', 'arbricks' ),
				),
				'default'     => 'all',
			),
			'arbricks_drf_response'          => array(
				'type'        => 'radio',
				'label'       => __( 'Response Type', 'arbricks' ),
				'description' => __( 'What happens when a disabled feed is accessed.', 'arbricks' ),
				'options'     => array(
					'410'      => __( '410 Gone (Recommended)', 'arbricks' ),
					'404'      => __( '404 Not Found', 'arbricks' ),
					'redirect' => __( 'Redirect to Homepage', 'arbricks' ),
				),
				'default'     => '410',
			),
			'arbricks_drf_remove_head_links' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Remove feed links from <head>', 'arbricks' ),
				'description' => __( 'Remove automatic feed links added by WordPress in the page source.', 'arbricks' ),
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
		if ( is_admin() ) {
			return;
		}

		$settings = Options::get_feature_settings( self::id() );

		// 1. Block Feed Endpoints.
		add_action( 'do_feed', array( $this, 'block_feed' ), 1 );
		add_action( 'do_feed_rdf', array( $this, 'block_feed' ), 1 );
		add_action( 'do_feed_rss', array( $this, 'block_feed' ), 1 );
		add_action( 'do_feed_rss2', array( $this, 'block_feed' ), 1 );
		add_action( 'do_feed_atom', array( $this, 'block_feed' ), 1 );
		add_action( 'do_feed_rss2_comments', array( $this, 'block_feed' ), 1 );
		add_action( 'do_feed_atom_comments', array( $this, 'block_feed' ), 1 );

		// 2. Remove Head Links.
		if ( ! empty( $settings['arbricks_drf_remove_head_links'] ) ) {
			$scope = $settings['arbricks_drf_scope'] ?? 'all';
			
			if ( 'all' === $scope ) {
				remove_action( 'wp_head', 'feed_links', 2 );
				remove_action( 'wp_head', 'feed_links_extra', 3 );
			} elseif ( 'posts' === $scope ) {
				remove_action( 'wp_head', 'feed_links', 2 );
			} elseif ( 'comments' === $scope ) {
				remove_action( 'wp_head', 'feed_links_extra', 3 );
			}
		}
	}

	/**
	 * Block feed request based on scope and response type.
	 */
	public function block_feed(): void {
		$settings = Options::get_feature_settings( self::id() );
		$scope    = $settings['arbricks_drf_scope'] ?? 'all';
		$response = $settings['arbricks_drf_response'] ?? '410';

		$is_comments = is_comment_feed();
		$should_block = false;

		if ( 'all' === $scope ) {
			$should_block = true;
		} elseif ( 'posts' === $scope && ! $is_comments ) {
			$should_block = true;
		} elseif ( 'comments' === $scope && $is_comments ) {
			$should_block = true;
		}

		if ( ! $should_block ) {
			return;
		}

		if ( 'redirect' === $response ) {
			wp_safe_redirect( home_url( '/' ), 302 );
			exit;
		} elseif ( '404' === $response ) {
			status_header( 404 );
			nocache_headers();
			exit;
		} else {
			// Default 410.
			status_header( 410 );
			nocache_headers();
			wp_die(
				esc_html__( 'RSS feeds are disabled on this site.', 'arbricks' ),
				esc_html__( 'Feed disabled', 'arbricks' ),
				array( 'response' => 410 )
			);
		}
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
