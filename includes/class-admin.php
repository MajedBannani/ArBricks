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

		// Build new enabled states.
		$new_states = array();
		foreach ( $all_snippets as $snippet ) {
			$snippet_id               = $snippet->get_id();
			$new_states[ $snippet_id ] = isset( $submitted_snippets[ $snippet_id ] );
		}

		// Save all states at once.
		Options::set_enabled_bulk( $new_states );

		// Clear cache.
		Options::clear_cache();

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
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_settings_page() {
		// Check user capability.
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'arbricks' ) );
		}

		// Get snippets from registry.
		$registry = Plugin::instance()->snippet_registry;
		if ( null === $registry ) {
			$snippets = array();
		} else {
			$snippets = $registry->get_all_snippets();
		}

		// Organize by category.
		$categorized = $this->categorize_snippets( $snippets );

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

				<div class="arbricks-snippets-grid">
					<?php foreach ( $categorized as $category => $category_snippets ) : ?>
						<?php if ( ! empty( $category ) ) : ?>
							<h2 class="arbricks-category-title"><?php echo esc_html( ucfirst( $category ) ); ?></h2>
						<?php endif; ?>

						<?php foreach ( $category_snippets as $snippet ) : ?>
							<?php
							$snippet_id  = $snippet->get_id();
							$is_enabled  = isset( $enabled[ $snippet_id ] ) && $enabled[ $snippet_id ];
							$has_shortcode = $this->snippet_has_shortcode( $snippet_id );
							?>
							<div class="arbricks-snippet-card <?php echo $is_enabled ? 'is-enabled' : 'is-disabled'; ?>">
								<div class="arbricks-snippet-header">
									<h3 class="arbricks-snippet-title"><?php echo esc_html( $snippet->get_label() ); ?></h3>
									<label class="arbricks-toggle">
										<input 
											type="checkbox" 
											name="snippets[<?php echo esc_attr( $snippet_id ); ?>]" 
											value="1" 
											<?php checked( $is_enabled ); ?>
										>
										<span class="arbricks-toggle-slider"></span>
									</label>
								</div>
								
								<p class="arbricks-snippet-description">
									<?php echo esc_html( $snippet->get_description() ); ?>
								</p>

								<div class="arbricks-snippet-footer">
									<span class="arbricks-status-badge">
										<?php
										echo $is_enabled
											? esc_html__( 'Enabled', 'arbricks' )
											: esc_html__( 'Disabled', 'arbricks' );
										?>
									</span>

									<?php if ( $is_enabled && $has_shortcode ) : ?>
										<button 
											type="button" 
											class="button button-small arbricks-copy-shortcode" 
											data-shortcode="<?php echo esc_attr( $this->get_shortcode_for_snippet( $snippet_id ) ); ?>"
										>
											<span class="dashicons dashicons-clipboard"></span>
											<?php esc_html_e( 'Copy Shortcode', 'arbricks' ); ?>
										</button>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</div>

				<?php submit_button( __( 'Save Changes', 'arbricks' ), 'primary', 'submit', true ); ?>
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
}
