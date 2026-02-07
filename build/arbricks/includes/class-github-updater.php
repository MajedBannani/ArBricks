<?php
/**
 * GitHub Updater Class
 *
 * Handles plugin updates via GitHub Releases using the Plugin Update Checker library.
 *
 * @package ArBricks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class ArBricks_GitHub_Updater
 */
class ArBricks_GitHub_Updater {

	/**
	 * The update checker instance.
	 *
	 * @var object|null
	 */
	protected $update_checker = null;

	/**
	 * ArBricks_GitHub_Updater constructor.
	 */
	public function __construct() {
		$this->init();
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Initialize the update checker.
	 *
	 * @return void
	 */
	protected function init() {
		// Ensure constants are defined.
		if ( ! defined( 'ARBRICKS_GITHUB_REPO_URL' ) || ! defined( 'ARBRICKS_PLUGIN_FILE' ) || ! defined( 'ARBRICKS_PLUGIN_SLUG' ) ) {
			return;
		}

		$puc_path = ARBRICKS_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

		// Safely load the library.
		if ( ! file_exists( $puc_path ) ) {
			return;
		}

		require_once $puc_path;

		// Build the update checker.
		if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		$this->update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			ARBRICKS_GITHUB_REPO_URL,
			ARBRICKS_PLUGIN_FILE,
			ARBRICKS_PLUGIN_SLUG
		);

		if ( ! $this->update_checker ) {
			return;
		}

		/**
		 * For GitHub tags like v1.2.3, PUC usually handles this automatically if the
		 * repo is public. If you experience issues where PUC doesn't see updates, 
		 * ensure the tag format on GitHub strictly matches vX.Y.Z.
		 */

		// Enable GitHub Release Assets.
		if ( method_exists( $this->update_checker, 'getVcsApi' ) ) {
			$vcs_api = $this->update_checker->getVcsApi();
			if ( method_exists( $vcs_api, 'enableReleaseAssets' ) ) {
				$vcs_api->enableReleaseAssets();
			}

			// Support private repo access token via constant or filter.
			$token = defined( 'ARBRICKS_GITHUB_TOKEN' ) ? ARBRICKS_GITHUB_TOKEN : '';
			
			/**
			 * Filter: arbricks_github_token
			 * Allows developers to provide the GitHub token securely.
			 */
			$token = apply_filters( 'arbricks_github_token', $token );

			if ( ! empty( $token ) ) {
				if ( method_exists( $vcs_api, 'setAuthentication' ) ) {
					$vcs_api->setAuthentication( $token );
				}
			}
		}
	}

	/**
	 * Display admin notices for configuration errors on the plugins page.
	 *
	 * @return void
	 */
	public function admin_notices() {
		global $pagenow;

		// Only show on the Plugins page.
		if ( 'plugins.php' !== $pagenow ) {
			return;
		}

		// Capability check.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$errors = array();

		if ( ! defined( 'ARBRICKS_GITHUB_REPO_URL' ) || empty( ARBRICKS_GITHUB_REPO_URL ) ) {
			$errors[] = __( 'GitHub Repository URL is not defined.', 'arbricks' );
		}

		if ( ! defined( 'ARBRICKS_PLUGIN_SLUG' ) || empty( ARBRICKS_PLUGIN_SLUG ) ) {
			$errors[] = __( 'Plugin slug is not defined.', 'arbricks' );
		}

		$puc_path = ARBRICKS_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
		if ( ! file_exists( $puc_path ) ) {
			$errors[] = __( 'Plugin Update Checker library is missing.', 'arbricks' );
		}

		if ( ! empty( $errors ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><strong><?php esc_html_e( 'ArBricks Update System Warning:', 'arbricks' ); ?></strong></p>
				<ul>
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}
	}
}
