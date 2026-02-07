<?php
/**
 * Feature: Redirect Anomaly Check
 *
 * Audit tool to detect suspicious redirects or conditional redirects targeting bots.
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
 * Class Feature_ArBricks_Redirect_Anomaly_Check
 */
class Feature_ArBricks_Redirect_Anomaly_Check implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_redirect_anomaly_check';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Redirect Anomaly Check', 'arbricks' ),
			'description' => __( 'Detect suspicious redirects that may target search engine bots differently than regular users.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary'  => __( 'SEO Spam attacks sometimes use conditional redirects that appear only to search engine bots to hide malicious content from the site owner. This tool simulates both regular visits and search engine crawls to compare behaviors.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature and set the scan scope (Homepage only or with a sample of posts).', 'arbricks' ),
					__( 'Ensure the search bot simulation option is enabled to compare behaviors.', 'arbricks' ),
					__( 'Click "Scan Now" to start requesting links and analyzing the redirect chain.', 'arbricks' ),
					__( 'Review the results and pay attention to any redirects that take you to an unknown external domain.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'This tool is for auditing only and does not change any settings on your site or server.', 'arbricks' ),
					__( 'Some normal redirects may appear, such as HTTP to HTTPS or non-WWW to WWW.', 'arbricks' ),
					__( 'If you find completely different behavior between a regular user and a search bot for the same link, it is a strong indicator of an SEO spam issue.', 'arbricks' ),
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
		return array();
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( is_admin() ) {
			add_action( 'wp_ajax_arbricks_redirect_scan', array( $this, 'ajax_run_scan' ) );
		}
	}

	/**
	 * AJAX: Run redirect scan
	 */
	public function ajax_run_scan(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'arbricks' ) ) );
		}

		check_ajax_referer( 'arbricks_redirect_scan', 'nonce' );

		$scope = isset( $_POST['scope'] ) ? sanitize_text_field( wp_unslash( $_POST['scope'] ) ) : 'home';
		$check_bot = ! empty( $_POST['check_bot'] );

		$urls = array( home_url( '/' ) );
		
		if ( $scope !== 'home' ) {
			$post_count = ( $scope === 'home_10' ) ? 10 : 3;
			$posts = get_posts( array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => $post_count,
				'orderby'        => 'rand',
			) );

			foreach ( $posts as $post ) {
				$urls[] = get_permalink( $post->ID );
			}
		}

		$results = array();
		$user_agent_normal = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
		$user_agent_bot    = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

		foreach ( $urls as $url ) {
			$normal_check = $this->trace_url( $url, $user_agent_normal );
			$bot_check    = $check_bot ? $this->trace_url( $url, $user_agent_bot ) : null;

			$results[] = array(
				'url'      => $url,
				'normal'   => $normal_check,
				'bot'      => $bot_check,
				'anomaly'  => $this->detect_anomaly( $normal_check, $bot_check ),
			);
		}

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Trace URL redirects
	 */
	private function trace_url( $url, $ua ): array {
		$chain = array();
		$current_url = $url;
		$max_hops = 3;
		$hops = 0;
		$final_url = $url;
		$home_host = parse_url( home_url(), PHP_URL_HOST );

		while ( $hops < $max_hops ) {
			$response = wp_remote_get( $current_url, array(
				'user-agent'  => $ua,
				'timeout'     => 10,
				'redirection' => 0, // Manual follow
				'sslverify'   => false,
			) );

			if ( is_wp_error( $response ) ) {
				$chain[] = array( 'code' => 'Error', 'url' => $current_url );
				break;
			}

			$code = wp_remote_retrieve_response_code( $response );
			$chain[] = array( 'code' => $code, 'url' => $current_url );

			if ( $code >= 300 && $code < 400 ) {
				$location = wp_remote_retrieve_header( $response, 'location' );
				if ( ! $location ) {
					break;
				}

				// Handle relative redirects
				if ( strpos( $location, 'http' ) !== 0 ) {
					$parsed = parse_url( $current_url );
					$location = $parsed['scheme'] . '://' . $parsed['host'] . ( isset( $parsed['port'] ) ? ':' . $parsed['port'] : '' ) . '/' . ltrim( $location, '/' );
				}

				$current_url = $location;
				$final_url   = $location;
				$hops++;

				// Stop if external
				$loc_host = parse_url( $location, PHP_URL_HOST );
				if ( $loc_host && stripos( $loc_host, $home_host ) === false ) {
					$chain[] = array( 'code' => 'External', 'url' => $location );
					break;
				}
			} else {
				break;
			}
		}

		return array(
			'chain'     => $chain,
			'final_url' => $final_url,
			'external'  => ( stripos( parse_url( $final_url, PHP_URL_HOST ) ?? '', $home_host ) === false ),
		);
	}

	/**
	 * Detect anomalies between normal and bot behavior
	 */
	private function detect_anomaly( $normal, $bot ): array {
		$issues = array();
		$risk   = 'Low';

		if ( $normal['external'] ) {
			$issues[] = __( 'Redirects to external domain', 'arbricks' );
			$risk = 'High';
		}

		if ( count( $normal['chain'] ) > 2 ) {
			$issues[] = __( 'Multiple redirect hops', 'arbricks' );
			$risk = $this->max_risk( $risk, 'Medium' );
		}

		if ( $bot ) {
			if ( $normal['final_url'] !== $bot['final_url'] ) {
				$issues[] = __( 'Discrepancy: Bot redirected to different URL', 'arbricks' );
				$risk = 'High';
			}
			if ( count( $normal['chain'] ) !== count( $bot['chain'] ) ) {
				$issues[] = __( 'Discrepancy: Different redirect chain length', 'arbricks' );
				$risk = $this->max_risk( $risk, 'Medium' );
			}
		}

		return array(
			'issues' => $issues,
			'risk'   => $risk,
		);
	}

	/**
	 * Helper to get max risk level
	 */
	private function max_risk( $current, $new ) {
		$levels = array( 'Low' => 1, 'Medium' => 2, 'High' => 3 );
		if ( $levels[ $new ] > $levels[ $current ] ) {
			return $new;
		}
		return $current;
	}

	/**
	 * Render custom admin UI inside feature card
	 */
	public function render_admin_ui(): void {
		?>
		<div class="arbricks-redirect-audit-tool" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
			<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
				<div class="field">
					<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Scan Scope', 'arbricks' ); ?></label>
					<select id="arbricks-red-scope" class="widefat">
						<option value="home"><?php esc_html_e( 'Homepage Only', 'arbricks' ); ?></option>
						<option value="home_3"><?php esc_html_e( 'Homepage + 3 random posts', 'arbricks' ); ?></option>
						<option value="home_10"><?php esc_html_e( 'Homepage + 10 random posts', 'arbricks' ); ?></option>
					</select>
				</div>
				<div class="field" style="display: flex; align-items: flex-end; padding-bottom: 5px;">
					<label>
						<input type="checkbox" id="arbricks-red-bot" checked>
						<?php esc_html_e( 'Simulate Search Bot (Googlebot)', 'arbricks' ); ?>
					</label>
				</div>
			</div>

			<div class="actions">
				<button type="button" id="arbricks-red-scan-btn" class="button button-primary">
					<?php esc_html_e( 'Scan Now', 'arbricks' ); ?>
				</button>
			</div>

			<div id="arbricks-red-results" style="margin-top: 20px; display: none;">
				<table class="widefat striped" style="border: 1px solid #ccd0d4;">
					<thead>
						<tr>
							<th style="width: 30%;"><?php esc_html_e( 'Tested URL', 'arbricks' ); ?></th>
							<th><?php esc_html_e( 'Status Chain', 'arbricks' ); ?></th>
							<th><?php esc_html_e( 'Risk & Issues', 'arbricks' ); ?></th>
						</tr>
					</thead>
					<tbody id="arbricks-red-results-body"></tbody>
				</table>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#arbricks-red-scan-btn').on('click', function() {
				var $btn = $(this);
				var originalText = $btn.text();
				
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Scanning...', 'arbricks' ) ); ?>');
				$('#arbricks-red-results').hide();
				$('#arbricks-red-results-body').empty();

				$.post(ajaxurl, {
					action: 'arbricks_redirect_scan',
					nonce: '<?php echo esc_js( wp_create_nonce( 'arbricks_redirect_scan' ) ); ?>',
					scope: $('#arbricks-red-scope').val(),
					check_bot: $('#arbricks-red-bot').is(':checked') ? 1 : 0
				}, function(response) {
					$btn.prop('disabled', false).text(originalText);
					
					if (response.success) {
						response.data.results.forEach(function(item) {
							var $row = $('<tr>');
							$row.append($('<td>').append($('<small>').append($('<code>').text(item.url))));
							
							var $chainCell = $('<td>');
							$chainCell.append($('<strong>').text('<?php echo esc_js( __( 'Browser:', 'arbricks' ) ); ?> '));
							item.normal.chain.forEach(function(c, i) {
								if (i > 0) $chainCell.append(' → ');
								$chainCell.append($('<span>').addClass('arbricks-badge').text(c.code));
							});
							$chainCell.append($('<br>'));
							$chainCell.append($('<strong>').text('<?php echo esc_js( __( 'Bot:', 'arbricks' ) ); ?> '));
							if (item.bot) {
								item.bot.chain.forEach(function(c, i) {
									if (i > 0) $chainCell.append(' → ');
									$chainCell.append($('<span>').addClass('arbricks-badge').text(c.code));
								});
							} else {
								$chainCell.append('<?php echo esc_js( __( 'N/A', 'arbricks' ) ); ?>');
							}
							$row.append($chainCell);

							var $riskCell = $('<td>');
							$riskCell.append($('<span>').css({'font-weight': 'bold', 'color': riskColor}).text(item.anomaly.risk));
							
							if (item.anomaly.issues.length) {
								var $list = $('<ul>');
								item.anomaly.issues.forEach(function(issue) {
									$list.append($('<li>').text(issue));
								});
								$riskCell.append($list);
							} else {
								$riskCell.append($('<p>').text('<?php echo esc_js( __( 'No issues detected', 'arbricks' ) ); ?>'));
							}
							$row.append($riskCell);

							$('#arbricks-red-results-body').append($row);
						});
						$('#arbricks-red-results').fadeIn();
					} else {
						alert(response.data.message || '<?php echo esc_js( __( 'Error occurred during scan.', 'arbricks' ) ); ?>');
					}
				});
			});
		});
		</script>
		<style>
		.arbricks-badge {
			background: #eee;
			padding: 2px 6px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
		}
		</style>
		<?php
	}
}

