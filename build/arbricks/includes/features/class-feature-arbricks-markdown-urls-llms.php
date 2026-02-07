<?php
/**
 * Feature: Markdown URLs for LLMs
 *
 * Injects Markdown-formatted links into an HTML comment for AI/LLM readability.
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
 * Class Feature_ArBricks_Markdown_URLs_LLMs
 *
 * Injects invisible Markdown links to help AI crawlers.
 */
class Feature_ArBricks_Markdown_URLs_LLMs implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string {
		return 'arbricks_markdown_urls_llms';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array Feature metadata.
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Markdown URLs for LLMs', 'arbricks' ),
			'description' => __( 'Adds an additional representation of links in Markdown format within the page to help AI models understand the site structure.', 'arbricks' ),
			'category'    => 'seo',
			'help'        => array(
				'summary' => __( 'AI models (LLMs) use Markdown links as an easy way to understand relationships between pages. This feature injects an invisible HTML comment containing the site\'s most important links.', 'arbricks' ),
				'how_to'  => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Choose whether to include primary menu links and the current page link.', 'arbricks' ),
					__( 'Set the maximum number of links to be injected.', 'arbricks' ),
				),
				'notes'   => array(
					__( 'These links are completely hidden from human visitors and only appear in the source code.', 'arbricks' ),
					__( 'This feature does not directly affect traditional SEO rankings at the moment, but it increases your site\'s "AI readiness".', 'arbricks' ),
					__( 'The comment is injected into the site Footer to ensure initial load speed is not affected.', 'arbricks' ),
				),
			),
		);
	}

	/**
	 * Get settings schema
	 *
	 * @return array Settings schema.
	 */
	public function get_settings_schema(): array {
		return array(
			'include_nav'     => array(
				'type'    => 'checkbox',
				'label'   => __( 'Include Primary Menu Links', 'arbricks' ),
				'default' => true,
			),
			'include_current' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Include Current Page Link', 'arbricks' ),
				'default' => true,
			),
			'max_links'       => array(
				'type'        => 'number',
				'label'       => __( 'Maximum Links Limit', 'arbricks' ),
				'default'     => 10,
				'placeholder' => '10',
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
		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_footer', array( $this, 'inject_markdown_comment' ), 999 );
	}

	/**
	 * Inject Markdown links inside an HTML comment.
	 *
	 * @return void
	 */
	public function inject_markdown_comment(): void {
		$links = array();
		$max   = (int) Options::get_feature_setting( self::id(), 'max_links', 10 );

		// 1. Home link
		$links[] = array(
			'title' => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);

		// 2. Current page
		if ( Options::get_feature_setting( self::id(), 'include_current', true ) && is_singular() ) {
			$links[] = array(
				'title' => get_the_title(),
				'url'   => get_permalink(),
			);
		}

		// 3. Navigation links
		if ( Options::get_feature_setting( self::id(), 'include_nav', true ) ) {
			$locations = get_nav_menu_locations();
			$menu_id   = $locations['primary'] ?? $locations['main'] ?? null;
			
			if ( $menu_id ) {
				$menu_items = wp_get_nav_menu_items( $menu_id );
				if ( is_array( $menu_items ) ) {
					foreach ( $menu_items as $item ) {
						if ( count( $links ) >= $max ) {
							break;
						}
						// Avoid duplicates
						$url = $item->url;
						$exists = false;
						foreach ( $links as $l ) {
							if ( $l['url'] === $url ) {
								$exists = true;
								break;
							}
						}
						if ( ! $exists ) {
							$links[] = array(
								'title' => $item->title,
								'url'   => $url,
							);
						}
					}
				}
			}
		}

		// Trim to max
		$links = array_slice( $links, 0, $max );

		if ( empty( $links ) ) {
			return;
		}

		// Build output
		echo "\n<!-- arbricks:markdown-links\n";
		foreach ( $links as $link ) {
			printf( "[%s](%s)\n", esc_html( $link['title'] ), esc_url( $link['url'] ) );
		}
		echo "-->\n";
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		// No custom UI needed beyond schema.
	}
}
