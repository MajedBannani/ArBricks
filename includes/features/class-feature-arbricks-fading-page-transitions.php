<?php
/**
 * Feature: Fading Page Transitions
 *
 * Adds a smooth fade-in and fade-out effect when navigating between internal pages.
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
 * Class Feature_ArBricks_Fading_Page_Transitions
 *
 * Implements lightweight CSS/JS based page transitions.
 */
class Feature_ArBricks_Fading_Page_Transitions implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string {
		return 'arbricks_fading_page_transitions';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array Feature metadata.
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Fading Page Transitions', 'arbricks' ),
			'description' => __( 'Adds a smooth fade transition effect between pages to improve user experience without affecting performance or SEO.', 'arbricks' ),
			'category'    => 'tools',
			'help'        => array(
				'summary' => __( 'Improves navigation smoothness by masking the abrupt change between pages with a simple opacity-based animation.', 'arbricks' ),
				'how_to'  => array(
					__( 'Set the transition duration in milliseconds (e.g., 200).', 'arbricks' ),
					__( 'Choose the element to apply the effect to (default is body).', 'arbricks' ),
					__( 'Save changes and test by navigating between site pages.', 'arbricks' ),
				),
				'notes'   => array(
					__( 'The effect is automatically disabled if the user prefers reduced motion (Accessibility).', 'arbricks' ),
					__( 'Does not apply to external links or links that open in a new window.', 'arbricks' ),
					__( 'This is a visual-only feature and does not consume server resources.', 'arbricks' ),
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
			'duration' => array(
				'type'        => 'number',
				'label'       => __( 'Transition Duration (ms)', 'arbricks' ),
				'default'     => 200,
				'placeholder' => '200',
			),
			'selector' => array(
				'type'        => 'text',
				'label'       => __( 'CSS Selector', 'arbricks' ),
				'default'     => 'body',
				'placeholder' => 'body, .site-content',
				'description' => __( 'Specify the element to fade. Default is body.', 'arbricks' ),
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

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets for the transition.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$duration = (int) Options::get_feature_setting( self::id(), 'duration', 200 );
		$selector = Options::get_feature_setting( self::id(), 'selector', 'body' );
		
		// 1. CSS for initial state and classes
		$css = "
			{$selector} {
				opacity: 0;
				transition: opacity {$duration}ms ease-in-out;
			}
			{$selector}.arbricks-fade-in {
				opacity: 1;
			}
			{$selector}.arbricks-fade-out {
				opacity: 0;
			}
			@media (prefers-reduced-motion: reduce) {
				{$selector} {
					opacity: 1 !important;
					transition: none !important;
				}
			}
		";
		wp_add_inline_style( 'arbricks-frontend', $css ); // Assuming frontend.css exists or use another handle

		// 2. JS for logic
		$js = "
			(function() {
				var selector = '{$selector}';
				var duration = {$duration};
				var element = document.querySelector(selector);
				if (!element) return;

				// Fade In
				window.addEventListener('pageshow', function(event) {
					// Handle back/forward cache
					element.classList.remove('arbricks-fade-out');
					element.classList.add('arbricks-fade-in');
				});

				// Fade Out on clicks
				document.addEventListener('click', function(e) {
					var link = e.target.closest('a');
					if (!link) return;

					var url = link.getAttribute('href');
					if (!url || url.indexOf('#') === 0 || url.indexOf('javascript:') === 0) return;

					// Exclusions
					if (link.getAttribute('target') === '_blank') return;
					if (link.getAttribute('download')) return;
					if (url.indexOf(window.location.origin) !== 0) return; // External
					if (url.match(/\.(pdf|zip|docx|xlsx|jpg|png|mp4)$/i)) return; // Files
					if (url.indexOf('/wp-admin/') !== -1 || url.indexOf('/wp-login.php') !== -1) return;

					// Accessibility check
					if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

					e.preventDefault();
					element.classList.remove('arbricks-fade-in');
					element.classList.add('arbricks-fade-out');

					setTimeout(function() {
						window.location.href = url;
					}, duration);
				});
			})();
		";
		wp_add_inline_script( 'arbricks-frontend', $js );
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
