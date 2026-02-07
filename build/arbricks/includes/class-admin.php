<?php
/**
 * Admin interface and settings page
 *
 * Handles admin menu, settings page rendering, and form processing.
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 *
 * Manages admin interface and settings.
 */
class Admin {

	/**
	 * Settings page slug
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'arbricks-settings';

	/**
	 * Capability required to access settings
	 *
	 * @var string
	 */
	const REQUIRED_CAPABILITY = 'manage_options';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_settings_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add admin menu page
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'ArBricks Settings', 'arbricks' ),
			__( 'ArBricks', 'arbricks' ),
			self::REQUIRED_CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-admin-generic',
			80
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our settings page.
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		// Enqueue WordPress media scripts.
		wp_enqueue_media();

		// Enqueue admin CSS.
		wp_enqueue_style(
			'arbricks-admin',
			ARBRICKS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ARBRICKS_VERSION
		);

		// Enqueue admin JS (minimal).
		wp_enqueue_script(
			'arbricks-admin',
			ARBRICKS_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ARBRICKS_VERSION,
			true
		);

		// Pass data to JS.
		wp_localize_script(
			'arbricks-admin',
			'arbricksAdmin',
			array(
				'copySuccess' => __( 'Shortcode copied!', 'arbricks' ),
				'copyError'   => __( 'Failed to copy shortcode.', 'arbricks' ),
			)
		);

		// Conditionally enqueue tool-specific assets for enabled features.
		$this->enqueue_tool_assets();
	}

	/**
	 * Handle settings form submission
	 *
	 * @return void
	 */
	public function handle_settings_save() {
		// Check if form was submitted.
		if ( ! isset( $_POST['arbricks_save_settings'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['arbricks_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['arbricks_nonce'] ) ), 'arbricks_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'arbricks' ) );
		}

		// Check user capability.
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'arbricks' ) );
		}

		// Get snippet registry to know all available snippets.
		$registry = Plugin::instance()->snippet_registry;
		if ( null === $registry ) {
			return;
		}

		$all_snippets       = $registry->get_all_snippets();
		$submitted_snippets = isset( $_POST['snippets'] ) && is_array( $_POST['snippets'] ) ? $_POST['snippets'] : array();

		// Build new enabled states for snippets.
		$new_states = array();
		foreach ( $all_snippets as $snippet ) {
			$snippet_id               = $snippet->get_id();
			$new_states[ $snippet_id ] = isset( $submitted_snippets[ $snippet_id ] );
		}

		// Get feature registry and handle feature toggles.
		$feature_registry = Plugin::instance()->feature_registry;
		if ( null !== $feature_registry ) {
			$all_features = $feature_registry->get_all_features();
			foreach ( $all_features as $feature ) {
				$feature_id               = $feature::id();
				$new_states[ $feature_id ] = isset( $submitted_snippets[ $feature_id ] );
			}
		}

		// Save all states at once.
		Options::set_enabled_bulk( $new_states );

		// Handle feature settings.
		if ( isset( $_POST['feature_settings'] ) && is_array( $_POST['feature_settings'] ) ) {
			foreach ( $_POST['feature_settings'] as $feature_id => $settings ) {
				if ( ! is_array( $settings ) ) {
					continue;
				}

				// Sanitize settings based on expected types.
				$sanitized = array();
				foreach ( $settings as $key => $value ) {
					$sanitized[ sanitize_key( $key ) ] = $this->sanitize_setting_value( $value );
				}

				Options::set_feature_settings( sanitize_key( $feature_id ), $sanitized );
			}
		}

		// Clear cache.
		Options::clear_cache();
		delete_transient( 'arbricks_discovered_features' );

		// Redirect with success message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Get translated category label
	 *
	 * @param string $category Category slug.
	 * @return string Translated category label.
	 */
	private function get_category_label( $category ): string {
		$labels = array(
			'tools'       => __( 'Tools', 'arbricks' ),
			'security'    => __( 'Security', 'arbricks' ),
			'seo'         => __( 'SEO', 'arbricks' ),
			'woocommerce' => __( 'WooCommerce', 'arbricks' ),
			'css'         => __( 'CSS Styles', 'arbricks' ),
			'performance' => __( 'Performance', 'arbricks' ),
		);

		return $labels[ $category ] ?? ucfirst( $category );
	}

	/**
	 * Render sidebar navigation
	 *
	 * @param array $categories Array of category slugs.
	 * @return void
	 */
	private function render_sidebar_navigation( $categories ) {
		?>
		<nav class="arbricks-admin-sidebar">
			<ul class="arbricks-category-nav">
				<li class="arbricks-category-item">
					<a href="#" class="arbricks-category-link is-active" data-filter="all">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'All Features', 'arbricks' ); ?>
					</a>
				</li>
				<?php foreach ( $categories as $category ) : ?>
					<li class="arbricks-category-item">
						<a href="#" class="arbricks-category-link" data-filter="<?php echo esc_attr( $category ); ?>">
							<span class="dashicons <?php echo esc_attr( $this->get_category_icon( $category ) ); ?>"></span>
							<?php echo esc_html( $this->get_category_label( $category ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}

	/**
	 * Get category icon mapping
	 *
	 * @param string $category Category slug.
	 * @return string Dashicon class.
	 */
	private function get_category_icon( $category ) {
		$icons = array(
			'tools'       => 'dashicons-admin-tools',
			'security'    => 'dashicons-shield',
			'seo'         => 'dashicons-search',
			'woocommerce' => 'dashicons-cart',
			'css'         => 'dashicons-editor-code',
			'performance' => 'dashicons-performance',
		);

		return $icons[ $category ] ?? 'dashicons-category';
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_settings_page() {
		// Check user capability.
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'arbricks' ) );
		}

		$plugin = Plugin::instance();
		$all_items = array();

		// Get snippets from registry.
		if ( null !== $plugin->snippet_registry ) {
			foreach ( $plugin->snippet_registry->get_all_snippets() as $snippet ) {
				$all_items[] = array(
					'type'        => 'snippet',
					'id'          => $snippet->get_id(),
					'label'       => $snippet->get_label(),
					'description' => $snippet->get_description(),
					'category'    => $snippet->get_category(),
					'object'      => $snippet,
				);
			}
		}

		// Get features from registry.
		if ( null !== $plugin->feature_registry ) {
			foreach ( $plugin->feature_registry->get_all_features() as $feature ) {
				$meta = $feature::meta();
				$all_items[] = array(
					'type'        => 'feature',
					'id'          => $feature::id(),
					'label'       => $meta['title'] ?? '',
					'description' => $meta['description'] ?? '',
					'category'    => $meta['category'] ?? 'other',
					'object'      => $feature,
				);
			}
		}

		// Organize by category.
		$categorized = array();
		foreach ( $all_items as $item ) {
			$category = $item['category'];
			if ( ! isset( $categorized[ $category ] ) ) {
				$categorized[ $category ] = array();
			}
			$categorized[ $category ][] = $item;
		}

		// Get enabled states.
		$enabled = Options::get_enabled();

		// Show success message if updated.
		$updated = isset( $_GET['updated'] ) && 'true' === $_GET['updated'];

		?>
		<div class="wrap arbricks-admin-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( $updated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully!', 'arbricks' ); ?></p>
				</div>
			<?php endif; ?>

			<p class="arbricks-intro">
				<?php esc_html_e( 'Enable or disable features as needed. Changes take effect immediately after saving.', 'arbricks' ); ?>
			</p>

			<form method="post" action="">
				<?php wp_nonce_field( 'arbricks_save_settings', 'arbricks_nonce' ); ?>
				<input type="hidden" name="arbricks_save_settings" value="1">

				<div class="arbricks-admin-layout">
					<?php $this->render_sidebar_navigation( array_keys( $categorized ) ); ?>

					<div class="arbricks-admin-main">
						<div class="arbricks-settings">
					<?php foreach ( $categorized as $category => $category_items ) : ?>
						<section class="arbricks-section" data-section="<?php echo esc_attr( $category ); ?>">
							<?php if ( ! empty( $category ) ) : ?>
								<h2 class="arbricks-section-title">
									<?php echo esc_html( $this->get_category_label( $category ) ); ?>
								</h2>
							<?php endif; ?>

							<div class="arbricks-section__cards">
								<?php foreach ( $category_items as $item ) : ?>
									<?php
									$item_id     = $item['id'];
									$is_enabled  = isset( $enabled[ $item_id ] ) && $enabled[ $item_id ];
									$has_shortcode = $this->item_has_shortcode( $item );
									?>
									<div class="arbricks-snippet-card <?php echo $is_enabled ? 'is-enabled' : 'is-disabled'; ?>">
										<div class="arbricks-snippet-header">
											<h3 class="arbricks-snippet-title"><?php echo esc_html( $item['label'] ); ?></h3>
											<label class="arbricks-toggle">
												<input 
													type="checkbox" 
													name="snippets[<?php echo esc_attr( $item_id ); ?>]" 
													value="1" 
													<?php checked( $is_enabled ); ?>
												>
												<span class="arbricks-toggle-slider"></span>
											</label>
										</div>
										
										<p class="arbricks-snippet-description">
											<?php echo esc_html( $item['description'] ); ?>
										</p>


									<?php
									// Render settings fields if feature has schema.
									if ( 'feature' === $item['type'] && method_exists( $item['object'], 'get_settings_schema' ) ) {
										$this->render_feature_settings( $item['object'], $item_id );
									}

									// Render custom tool UI if feature provides it.
									if ( 'feature' === $item['type'] ) {
										$item['object']->render_admin_ui();
									}

									// Render help section if feature has help content.
									if ( 'feature' === $item['type'] ) {
										$this->render_feature_help( $item['object'], $item_id );
									}
									?>

									<div class="arbricks-snippet-footer">
											<span class="arbricks-status-badge">
												<?php
												echo $is_enabled
													? esc_html__( 'ENABLED', 'arbricks' )
													: esc_html__( 'DISABLED', 'arbricks' );
												?>
											</span>

											<?php if ( $is_enabled && $has_shortcode ) : ?>
												<button 
													type="button" 
													class="button button-small arbricks-copy-shortcode" 
													data-shortcode="<?php echo esc_attr( $this->get_shortcode_for_item( $item ) ); ?>"
												>
													<span class="dashicons dashicons-clipboard"></span>
													<?php esc_html_e( 'Copy Shortcode', 'arbricks' ); ?>
												</button>
											<?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endforeach; ?>
						</div>

						<?php submit_button( __( 'Save Changes', 'arbricks' ), 'primary', 'submit', true ); ?>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Categorize snippets by their category
	 *
	 * @param array $snippets Array of snippet objects.
	 * @return array Categorized snippets.
	 */
	private function categorize_snippets( $snippets ) {
		$categorized = array(
			'tools'  => array(),
			'styles' => array(),
			'other'  => array(),
		);

		foreach ( $snippets as $snippet ) {
			$category = $snippet->get_category();
			if ( ! isset( $categorized[ $category ] ) ) {
				$categorized[ $category ] = array();
			}
			$categorized[ $category ][] = $snippet;
		}

		// Remove empty categories.
		return array_filter( $categorized );
	}

	/**
	 * Check if item has a shortcode
	 *
	 * @param array $item Item array with 'id', 'type' keys.
	 * @return bool True if has shortcode.
	 */
	private function item_has_shortcode( $item ) {
		// Features don't have shortcodes (they use hooks).
		if ( 'feature' === $item['type'] ) {
			return false;
		}

		// Legacy snippet shortcode check.
		return $this->snippet_has_shortcode( $item['id'] );
	}

	/**
	 * Get shortcode for item
	 *
	 * @param array $item Item array.
	 * @return string Shortcode tag.
	 */
	private function get_shortcode_for_item( $item ) {
		// Features don't have shortcodes.
		if ( 'feature' === $item['type'] ) {
			return '';
		}

		// Legacy snippet shortcode.
		return $this->get_shortcode_for_snippet( $item['id'] );
	}

	/**
	 * Check if snippet has a shortcode
	 *
	 * @param string $snippet_id Snippet ID.
	 * @return bool True if has shortcode.
	 */
	private function snippet_has_shortcode( $snippet_id ) {
		$shortcode_snippets = array( 'qr_generator', 'webp_converter', 'youtube_timestamp', 'css_minifier' );
		return in_array( $snippet_id, $shortcode_snippets, true );
	}

	/**
	 * Get shortcode for snippet
	 *
	 * @param string $snippet_id Snippet ID.
	 * @return string Shortcode.
	 */
	private function get_shortcode_for_snippet( $snippet_id ) {
		$shortcodes = array(
			'qr_generator'      => '[qr-generator]',
			'webp_converter'    => '[webp-converter]',
			'youtube_timestamp' => '[youtube-generator]',
			'css_minifier'      => '[css-minifier]',
		);

		return isset( $shortcodes[ $snippet_id ] ) ? $shortcodes[ $snippet_id ] : '';
	}

	/**
	 * Render feature settings fields
	 *
	 * @param object $feature Feature instance.
	 * @param string $feature_id Feature ID.
	 * @return void
	 */
	private function render_feature_settings( $feature, $feature_id ): void {
		if ( ! method_exists( $feature, 'get_settings_schema' ) ) {
			return;
		}

		$schema   = $feature->get_settings_schema();
		$settings = Options::get_feature_settings( $feature_id );

		if ( empty( $schema ) ) {
			return;
		}

		echo '<div class="arbricks-feature-settings">';

		foreach ( $schema as $key => $field ) {
			$value       = isset( $settings[ $key ] ) ? $settings[ $key ] : ( $field['default'] ?? '' );
			$field_name  = 'feature_settings[' . esc_attr( $feature_id ) . '][' . esc_attr( $key ) . ']';
			$field_id    = 'setting-' . esc_attr( $feature_id ) . '-' . esc_attr( $key );
			$type        = $field['type'] ?? 'text';
			$label       = $field['label'] ?? '';
			$description = $field['description'] ?? '';
			$placeholder = $field['placeholder'] ?? '';

			echo '<div class="arbricks-setting-field">';

			if ( 'checkbox' === $type ) {
				echo '<label class="arbricks-setting-label">';
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="1" ' . checked( $value, true, false ) . '>';
				echo '<span>' . esc_html( $label ) . '</span>';
				echo '</label>';
			} elseif ( 'select' === $type ) {
				echo '<label for="' . esc_attr( $field_id ) . '" class="arbricks-setting-label">' . esc_html( $label ) . '</label>';
				echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="arbricks-setting-input">';
				foreach ( $field['options'] as $opt_value => $opt_label ) {
					echo '<option value="' . esc_attr( $opt_value ) . '" ' . selected( $value, $opt_value, false ) . '>';
					echo esc_html( $opt_label );
					echo '</option>';
				}
				echo '</select>';
			} elseif ( 'media' === $type ) {
				$image_url = '';
				if ( $value ) {
					$image_url = wp_get_attachment_image_url( $value, 'thumbnail' );
				}
				echo '<label class="arbricks-setting-label">' . esc_html( $label ) . '</label>';
				echo '<div class="arbricks-media-picker" data-id="' . esc_attr( $field_id ) . '">';
				echo '<div class="arbricks-media-preview" style="margin-bottom: 10px;">';
				if ( $image_url ) {
					echo '<img src="' . esc_url( $image_url ) . '" style="max-width: 100px; height: auto; display: block; border: 1px solid #ddd; padding: 5px;">';
				}
				echo '</div>';
				echo '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $value ) . '">';
				echo '<div class="arbricks-media-picker-actions">';
				echo '<button type="button" class="button arbricks-media-upload">' . esc_html__( 'Select Image', 'arbricks' ) . '</button>';
				echo ' <button type="button" class="button arbricks-media-remove" ' . ( ! $value ? 'style="display:none;"' : '' ) . '>' . esc_html__( 'Remove', 'arbricks' ) . '</button>';
				echo '</div>';
				echo '</div>';
			} else {
				// Text input.
				echo '<label for="' . esc_attr( $field_id ) . '" class="arbricks-setting-label">' . esc_html( $label ) . '</label>';
				echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $value ) . '" class="arbricks-setting-input" placeholder="' . esc_attr( $placeholder ) . '">';
			}

			if ( ! empty( $description ) ) {
				echo '<p class="arbricks-setting-description">' . esc_html( $description ) . '</p>';
			}

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Render feature help section
	 *
	 * @param object $feature Feature instance.
	 * @param string $feature_id Feature ID.
	 * @return void
	 */
	private function render_feature_help( $feature, $feature_id ): void {
		$meta = $feature::meta();

		if ( empty( $meta['help'] ) ) {
			return;
		}

		$help = $meta['help'];
		?>
		<div class="arbricks-help-section">
			<button type="button" class="arbricks-help-toggle" aria-expanded="false" aria-controls="help-<?php echo esc_attr( $feature_id ); ?>">
				<?php esc_html_e( 'Help', 'arbricks' ); ?>
				<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
			</button>
			<div class="arbricks-help-content" id="help-<?php echo esc_attr( $feature_id ); ?>" hidden aria-hidden="true">
				<?php if ( ! empty( $help['summary'] ) ) : ?>
					<p class="arbricks-help-summary">
						<?php echo wp_kses_post( $help['summary'] ); ?>
					</p>
				<?php endif; ?>

				<?php if ( ! empty( $help['how_to'] ) && is_array( $help['how_to'] ) ) : ?>
					<div class="arbricks-help-how-to">
						<strong><?php esc_html_e( 'How to use:', 'arbricks' ); ?></strong>
						<ol>
							<?php foreach ( $help['how_to'] as $step ) : ?>
								<li><?php echo wp_kses_post( $step ); ?></li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $help['notes'] ) && is_array( $help['notes'] ) ) : ?>
					<div class="arbricks-help-notes">
						<strong><?php esc_html_e( 'Notes:', 'arbricks' ); ?></strong>
						<ul>
							<?php foreach ( $help['notes'] as $note ) : ?>
								<li><?php echo wp_kses_post( $note ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $help['examples'] ) && is_array( $help['examples'] ) ) : ?>
					<div class="arbricks-help-examples">
						<strong><?php esc_html_e( 'Examples:', 'arbricks' ); ?></strong>
						<ul>
							<?php foreach ( $help['examples'] as $example ) : ?>
								<li><?php echo wp_kses_post( $example ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Sanitize setting value based on type
	 *
	 * @param mixed $value Setting value to sanitize.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_setting_value( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'sanitize_setting_value' ), $value );
		}

		// Handle boolean values.
		if ( is_bool( $value ) || 'true' === $value || 'false' === $value ) {
			return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}

		// Handle numeric values.
		if ( is_numeric( $value ) ) {
			return is_float( $value ) || str_contains( (string) $value, '.' ) ? floatval( $value ) : intval( $value );
		}

		//Convert to string for further checks.
		$string_value = (string) $value;

		// Validate and sanitize URLs.
		if ( filter_var( $string_value, FILTER_VALIDATE_URL ) ) {
			return esc_url_raw( $string_value );
		}

		// Validate and sanitize emails.
		if ( is_email( $string_value ) ) {
			return sanitize_email( $string_value );
		}

		// Default: sanitize as text.
		return sanitize_text_field( $string_value );
	}

	/**
	 * Conditionally enqueue tool-specific assets
	 *
	 * Only enqueue assets for tools that are enabled.
	 *
	 * @return void
	 */
	private function enqueue_tool_assets() {
		$enabled = Options::get_enabled();

		// CSS Minifier Tool.
		if ( ! empty( $enabled['css_minifier_tool'] ) ) {
			wp_enqueue_style(
'arbricks-tool-css-minifier',
ARBRICKS_PLUGIN_URL . 'assets/css/tools-css-minifier.css',
array( 'arbricks-admin' ),
ARBRICKS_VERSION
);
			wp_enqueue_script(
'arbricks-tool-css-minifier',
ARBRICKS_PLUGIN_URL . 'assets/js/tools-css-minifier.js',
array( 'jquery', 'arbricks-admin' ),
ARBRICKS_VERSION,
true
);
			wp_localize_script(
'arbricks-tool-css-minifier',
'arbricksCssMinifier',
array(
					'minifySuccess'  => __( 'CSS minified successfully!', 'arbricks' ),
					'minifyError'    => __( 'Failed to minify CSS.', 'arbricks' ),
					'copySuccess'    => __( 'Copied to clipboard!', 'arbricks' ),
					'copyError'      => __( 'Failed to copy.', 'arbricks' ),
					'emptyInputError' => __( 'Please enter CSS to minify.', 'arbricks' ),
				)
			);
		}


		// WebP Converter Tool.
		if ( ! empty( $enabled['webp_converter_tool'] ) ) {
			wp_enqueue_media();
			wp_enqueue_style(
'arbricks-tool-webp-converter',
ARBRICKS_PLUGIN_URL . 'assets/css/tools-webp-converter.css',
array( 'arbricks-admin' ),
ARBRICKS_VERSION
);
			wp_enqueue_script(
'arbricks-tool-webp-converter',
ARBRICKS_PLUGIN_URL . 'assets/js/tools-webp-converter.js',
array( 'jquery', 'arbricks-admin' ),
ARBRICKS_VERSION,
true
);
			wp_localize_script(
'arbricks-tool-webp-converter',
'arbricksWebpConverter',
array(
					'convertSuccess'    => __( 'Conversion complete!', 'arbricks' ),
					'convertError'      => __( 'Conversion failed.', 'arbricks' ),
					'noImagesSelected'  => __( 'Please select images to convert.', 'arbricks' ),
					'webpNotSupported'  => __( 'WebP format is not supported on this server.', 'arbricks' ),
					'selectImages'      => __( 'Select Images', 'arbricks' ),
				)
			);
		}

		// YouTube Timestamp Generator Tool.
		if ( ! empty( $enabled['youtube_timestamp_tool'] ) ) {
			wp_enqueue_style(
'arbricks-tool-youtube-timestamp',
ARBRICKS_PLUGIN_URL . 'assets/css/tools-youtube-timestamp.css',
array( 'arbricks-admin' ),
ARBRICKS_VERSION
);
			wp_enqueue_script(
'arbricks-tool-youtube-timestamp',
ARBRICKS_PLUGIN_URL . 'assets/js/tools-youtube-timestamp.js',
array( 'jquery', 'arbricks-admin' ),
ARBRICKS_VERSION,
true
);
			wp_localize_script(
'arbricks-tool-youtube-timestamp',
'arbricksYoutubeTimestamp',
array(
'generateSuccess'   => __( 'Timestamp URL generated!', 'arbricks' ),
					'invalidUrl'        => __( 'Please enter a valid YouTube URL.', 'arbricks' ),
					'invalidTimestamp'  => __( 'Please enter a valid timestamp (e.g., 2:30 or 01:30:45).', 'arbricks' ),
					'copySuccess'       => __( 'Copied to clipboard!', 'arbricks' ),
					'copyError'         => __( 'Failed to copy.', 'arbricks' ),
				)
			);
		}
	}
}
