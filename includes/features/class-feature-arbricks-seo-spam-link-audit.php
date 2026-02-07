<?php
/**
 * Feature: SEO Spam Link Audit
 *
 * Audit tool to extract external links and detect suspicious domains.
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
 * Class Feature_ArBricks_SEO_Spam_Link_Audit
 */
class Feature_ArBricks_SEO_Spam_Link_Audit implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_seo_spam_link_audit';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'SEO Spam Link Audit', 'arbricks' ),
			'description' => __( 'Audit tool to extract all external links from your site\'s content and identify suspicious domains.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary'  => __( 'The Link Audit tool helps you discover hidden or suspicious external links that may be added to your site via comments or content injection for SEO spam purposes. This tool helps you clean up your site and improve its quality.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature for the scan form to appear below.', 'arbricks' ),
					__( 'Choose the scan scope (Posts, Pages, or Comments) and the maximum items limit.', 'arbricks' ),
					__( 'Click "Start Scan Now" to begin extracting and analyzing links.', 'arbricks' ),
					__( 'Review the resulting table and pay attention to domains flagged as "Suspicious".', 'arbricks' ),
				),
				'notes'    => array(
					__( 'This tool is read-only and does not automatically delete or modify any content.', 'arbricks' ),
					__( 'Links are classified as suspicious based on technical criteria such as domain length, number of hyphens, or the use of cheap TLD suffixes famous for spam.', 'arbricks' ),
					__( 'In case you find suspicious links, make sure to review their content and delete them manually if they prove to be spam.', 'arbricks' ),
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
			add_action( 'wp_ajax_arbricks_seo_spam_scan', array( $this, 'ajax_run_scan' ) );
		}
	}

	/**
	 * AJAX: Run link audit scan
	 *
	 * @return void
	 */
	public function ajax_run_scan(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'arbricks' ) ) );
		}

		check_ajax_referer( 'arbricks_seo_spam_scan', 'nonce' );

		$limit = isset( $_POST['limit'] ) ? min( 1000, max( 10, (int) $_POST['limit'] ) ) : 200;
		$scope = isset( $_POST['scope'] ) ? sanitize_text_field( wp_unslash( $_POST['scope'] ) ) : 'both';
		$include_comments = ! empty( $_POST['include_comments'] );

		$results = array();
		$scanned_count = 0;

		global $wpdb;

		// 1. Process Posts / Pages.
		$post_types = array();
		if ( 'posts' === $scope || 'both' === $scope ) {
			$post_types[] = 'post';
		}
		if ( 'pages' === $scope || 'both' === $scope ) {
			$post_types[] = 'page';
		}

		if ( ! empty( $post_types ) ) {
			$placeholders = array_fill( 0, count( $post_types ), '%s' );
			$where_type = implode( ',', $placeholders );
			$posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, post_content, post_type FROM $wpdb->posts WHERE post_type IN ($where_type) AND post_status = 'publish' ORDER BY post_date DESC LIMIT %d",
					array_merge( $post_types, array( $limit ) )
				)
			);

			foreach ( $posts as $post ) {
				$scanned_count++;
				$links = $this->extract_links( $post->post_content );
				$this->merge_links( $results, $links, 'post', $post->ID, $post->post_title );
			}
		}

		// 2. Process Comments.
		if ( $include_comments ) {
			$comments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT comment_ID, comment_content FROM $wpdb->comments WHERE comment_approved = '1' ORDER BY comment_date DESC LIMIT %d",
					$limit
				)
			);

			foreach ( $comments as $comment ) {
				$scanned_count++;
				$links = $this->extract_links( $comment->comment_content );
				$this->merge_links( $results, $links, 'comment', $comment->comment_ID, '#' . $comment->comment_ID );
			}
		}

		// Heuristic analysis.
		foreach ( $results as $domain => &$data ) {
			$data['suspicious'] = $this->is_domain_suspicious( $domain );
		}

		wp_send_json_success( array(
			'results' => $results,
			'scanned' => $scanned_count,
		) );
	}

	/**
	 * Extract external links from content
	 *
	 * @param string $content HTML content.
	 * @return array List of external domains.
	 */
	private function extract_links( $content ): array {
		$links = array();
		if ( empty( $content ) ) {
			return $links;
		}

		preg_match_all( '/href=["\'](http[s]?:\/\/[^"\']+)["\']/i', $content, $matches );

		if ( empty( $matches[1] ) ) {
			return $links;
		}

		$current_host = parse_url( home_url(), PHP_URL_HOST );

		foreach ( $matches[1] as $url ) {
			$host = parse_url( $url, PHP_URL_HOST );
			if ( ! $host || stripos( $host, $current_host ) !== false ) {
				continue;
			}
			$links[] = strtolower( str_replace( 'www.', '', $host ) );
		}

		return array_unique( $links );
	}

	/**
	 * Merge found links into the results array
	 */
	private function merge_links( &$results, $links, $type, $id, $title ): void {
		foreach ( $links as $domain ) {
			if ( ! isset( $results[ $domain ] ) ) {
				$results[ $domain ] = array(
					'count'    => 0,
					'found_in' => array(),
				);
			}

			$results[ $domain ]['count']++;
			if ( count( $results[ $domain ]['found_in'] ) < 3 ) {
				$results[ $domain ]['found_in'][] = array(
					'type'  => $type,
					'id'    => $id,
					'title' => $title,
				);
			}
		}
	}

	/**
	 * Heuristic check for suspicious domains
	 */
	private function is_domain_suspicious( $domain ): bool {
		// 1. Length.
		if ( strlen( $domain ) > 30 ) {
			return true;
		}

		// 2. Hyphens count.
		if ( substr_count( $domain, '-' ) >= 3 ) {
			return true;
		}

		// 3. Spammy TLDs.
		$spammy_tlds = array( '.top', '.click', '.work', '.xyz', '.date', '.online', '.site', '.vip', '.icu' );
		foreach ( $spammy_tlds as $tld ) {
			if ( substr( $domain, -strlen( $tld ) ) === $tld ) {
				return true;
			}
		}

		// 4. Keywords.
		$keywords = array( 'casino', 'gambling', 'viagra', 'pharma', 'poker', 'slots', 'adult', 'sex' );
		foreach ( $keywords as $word ) {
			if ( stripos( $domain, $word ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render custom admin UI inside feature card
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		?>
		<div class="arbricks-seo-spam-audit-tool" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
			<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
				<div class="field">
					<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Scan Scope', 'arbricks' ); ?></label>
					<select id="arbricks-spam-scope" class="widefat">
						<option value="both"><?php esc_html_e( 'Posts + Pages', 'arbricks' ); ?></option>
						<option value="posts"><?php esc_html_e( 'Posts Only', 'arbricks' ); ?></option>
						<option value="pages"><?php esc_html_e( 'Pages Only', 'arbricks' ); ?></option>
					</select>
				</div>
				<div class="field">
					<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Item Limit', 'arbricks' ); ?></label>
					<input type="number" id="arbricks-spam-limit" value="200" min="10" max="1000" class="widefat">
				</div>
				<div class="field" style="display: flex; align-items: flex-end; padding-bottom: 5px;">
					<label>
						<input type="checkbox" id="arbricks-spam-comments" checked>
						<?php esc_html_e( 'Include Comments', 'arbricks' ); ?>
					</label>
				</div>
			</div>

			<div class="actions">
				<button type="button" id="arbricks-spam-scan-btn" class="button button-primary">
					<?php esc_html_e( 'Start Scan Now', 'arbricks' ); ?>
				</button>
			</div>

			<div id="arbricks-spam-results" style="margin-top: 20px; display: none;">
				<div id="arbricks-spam-summary" style="margin-bottom: 15px; padding: 10px; background: #e7f3ff; border-radius: 4px;"></div>
				
				<table class="widefat striped" style="border: 1px solid #ccd0d4;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Domain', 'arbricks' ); ?></th>
							<th><?php esc_html_e( 'Detected Links Count', 'arbricks' ); ?></th>
							<th><?php esc_html_e( 'Status', 'arbricks' ); ?></th>
							<th><?php esc_html_e( 'Examples / Location', 'arbricks' ); ?></th>
						</tr>
					</thead>
					<tbody id="arbricks-spam-results-body"></tbody>
				</table>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#arbricks-spam-scan-btn').on('click', function() {
				var $btn = $(this);
				var originalText = $btn.text();
				
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Scanning...', 'arbricks' ) ); ?>');
				$('#arbricks-spam-results').hide();
				$('#arbricks-spam-results-body').empty();

				$.post(ajaxurl, {
					action: 'arbricks_seo_spam_scan',
					nonce: '<?php echo esc_js( wp_create_nonce( 'arbricks_seo_spam_scan' ) ); ?>',
					scope: $('#arbricks-spam-scope').val(),
					limit: $('#arbricks-spam-limit').val(),
					include_comments: $('#arbricks-spam-comments').is(':checked') ? 1 : 0
				}, function(response) {
					$btn.prop('disabled', false).text(originalText);
					
					if (response.success) {
						var results = response.data.results;
						var domains = Object.keys(results);
						
						$('#arbricks-spam-summary').text('<?php echo esc_js( __( 'Scanned:', 'arbricks' ) ); ?> ' + response.data.scanned + ' <?php echo esc_js( __( 'items.', 'arbricks' ) ); ?> ' + domains.length + ' <?php echo esc_js( __( 'external domains detected.', 'arbricks' ) ); ?>');
						
						if (domains.length === 0) {
							$('#arbricks-spam-results-body').html('<tr><td colspan="4" style="text-align:center;"><?php echo esc_js( __( 'No external links found.', 'arbricks' ) ); ?></td></tr>');
						} else {
							// Sort: Suspicious first.
							domains.sort(function(a, b) {
								if (results[a].suspicious && !results[b].suspicious) return -1;
								if (!results[a].suspicious && results[b].suspicious) return 1;
								return results[b].count - results[a].count;
							});

							domains.forEach(function(domain) {
								var data = results[domain];
								var status = data.suspicious ? '<span style="color: #d63638; font-weight: bold;">⚠️ <?php echo esc_js( __( 'Suspicious', 'arbricks' ) ); ?></span>' : '<span style="color: #68de7c;">✅ <?php echo esc_js( __( 'Safe', 'arbricks' ) ); ?></span>';
								
								var locations = data.found_in.map(function(loc) {
									return '<small>' + loc.type + ': ' + loc.title + '</small>';
								}).join('<br>');

								$('#arbricks-spam-results-body').append(
									'<tr>' +
										'<td><code>' + domain + '</code></td>' +
										'<td>' + data.count + '</td>' +
										'<td>' + status + '</td>' +
										'<td>' + locations + '</td>' +
									'</tr>'
								);
							});
						}
						$('#arbricks-spam-results').fadeIn();
					} else {
						alert(response.data.message || 'Error occurred during scan.');
					}
				});
			});
		});
		</script>
		<?php
	}
}
