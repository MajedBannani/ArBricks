<?php
/**
 * Feature: SEO Spam Content Scan
 *
 * Audit tool to detect suspicious content patterns like hidden styles or obfuscation.
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
 * Class Feature_ArBricks_SEO_Spam_Content_Scan
 */
class Feature_ArBricks_SEO_Spam_Content_Scan implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_seo_spam_content_scan';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'SEO Spam Content Scan', 'arbricks' ),
			'description' => __( 'Scan posts and pages for suspicious patterns like hidden text, obfuscated code, or spam keywords.', 'arbricks' ),
			'category'    => 'security',
			'help'        => array(
				'summary'  => __( 'This tool scans your site\'s content for "SEO Spam" indicators. This technique is used by attackers to add hidden links or keywords to your site to improve other sites\' rankings, which harms your site\'s reputation.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature and configure scan options (count and scope).', 'arbricks' ),
					__( 'Click "Start Scan Now" to begin analyzing the content code.', 'arbricks' ),
					__( 'Review the resulting list and pay attention to items with a "High" risk level.', 'arbricks' ),
					__( 'Use the direct "Edit" link to review and remove suspicious content if necessary.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'This tool is for auditing only and does not change anything in the database.', 'arbricks' ),
					__( 'The tool looks for text hidden with CSS, obfuscated code (Base64), or suspicious keywords.', 'arbricks' ),
					__( 'Notice: Some themes may use hidden text for legitimate design purposes, so manual review is always required before taking action.', 'arbricks' ),
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
			add_action( 'wp_ajax_arbricks_seo_content_scan', array( $this, 'ajax_run_scan' ) );
		}
	}

	/**
	 * AJAX: Run content audit scan
	 */
	public function ajax_run_scan(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'arbricks' ) ) );
		}

		check_ajax_referer( 'arbricks_seo_content_scan', 'nonce' );

		$limit = isset( $_POST['limit'] ) ? min( 500, max( 10, (int) $_POST['limit'] ) ) : 100;
		$scope = isset( $_POST['scope'] ) ? sanitize_text_field( wp_unslash( $_POST['scope'] ) ) : 'both';
		$include_private = ! empty( $_POST['include_private'] );

		global $wpdb;

		$post_types = array();
		if ( 'posts' === $scope || 'both' === $scope ) {
			$post_types[] = 'post';
		}
		if ( 'pages' === $scope || 'both' === $scope ) {
			$post_types[] = 'page';
		}

		$status = $include_private ? array( 'publish', 'private' ) : array( 'publish' );
		
		$placeholders_type = array_fill( 0, count( $post_types ), '%s' );
		$placeholders_status = array_fill( 0, count( $status ), '%s' );

		$query = "SELECT ID, post_title, post_content, post_type FROM $wpdb->posts 
				  WHERE post_type IN (" . implode( ',', $placeholders_type ) . ") 
				  AND post_status IN (" . implode( ',', $placeholders_status ) . ") 
				  ORDER BY post_modified DESC LIMIT %d";

		$params = array_merge( $post_types, $status, array( $limit ) );
		$posts  = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		$flagged_items = array();

		foreach ( $posts as $post ) {
			$issues = $this->analyze_content( $post->post_content );
			if ( ! empty( $issues ) ) {
				$flagged_items[] = array(
					'id'        => $post->ID,
					'title'     => $post->post_title,
					'type'      => $post->post_type,
					'issues'    => $issues['types'],
					'risk'      => $issues['risk'],
					'edit_link' => get_edit_post_link( $post->ID ),
				);
			}
		}

		wp_send_json_success( array(
			'flagged' => $flagged_items,
			'scanned' => count( $posts ),
		) );
	}

	/**
	 * Analyze content for suspicious patterns
	 *
	 * @param string $content HTML content.
	 * @return array Found issues and risk level.
	 */
	private function analyze_content( $content ): array {
		if ( empty( $content ) ) {
			return array();
		}

		$issues = array();
		$risk   = 0;

		// 1. Hidden Content Detection.
		$hidden_patterns = array(
			'display:\s*none'      => __( 'Hidden via CSS (Display)', 'arbricks' ),
			'visibility:\s*hidden' => __( 'Hidden via CSS (Visibility)', 'arbricks' ),
			'opacity:\s*0'         => __( 'Hidden via CSS (Opacity)', 'arbricks' ),
			'font-size:\s*0'       => __( 'Hidden via CSS (Zero font)', 'arbricks' ),
		);

		foreach ( $hidden_patterns as $p => $label ) {
			if ( preg_match( '/' . $p . '/i', $content ) ) {
				$issues[] = $label;
				$risk += 2;
			}
		}

		// 2. Obfuscation Patterns.
		$obfuscation = array(
			'base64_decode' => __( 'Base64 Obfuscation', 'arbricks' ),
			'gzinflate'     => __( 'Gzip Obfuscation', 'arbricks' ),
			'eval\s*\('     => __( 'Code Execution (Eval)', 'arbricks' ),
		);

		foreach ( $obfuscation as $p => $label ) {
			if ( preg_match( '/' . $p . '/i', $content ) ) {
				$issues[] = $label;
				$risk += 5;
			}
		}

		// 3. Keyword Detection.
		$keywords = array( 'casino', 'gambling', 'viagra', 'pharma', 'poker', 'pussy', 'fuck', 'adult', 'sex' );
		$found_keywords = array();
		foreach ( $keywords as $word ) {
			if ( stripos( $content, $word ) !== false ) {
				$found_keywords[] = $word;
			}
		}

		if ( ! empty( $found_keywords ) ) {
			$issues[] = __( 'Spam Keywords', 'arbricks' ) . ' (' . count( $found_keywords ) . ')';
			$risk += count( $found_keywords ) >= 3 ? 3 : 1;
		}

		// 4. Excessive Links.
		$link_count = substr_count( strtolower( $content ), '<a ' );
		if ( $link_count > 15 && strlen( $content ) < 1000 ) {
			$issues[] = __( 'High link density', 'arbricks' );
			$risk += 4;
		}

		if ( empty( $issues ) ) {
			return array();
		}

		$risk_label = __( 'Low', 'arbricks' );
		if ( $risk >= 7 ) {
			$risk_label = __( 'High', 'arbricks' );
		} elseif ( $risk >= 3 ) {
			$risk_label = __( 'Medium', 'arbricks' );
		}

		return array(
			'types' => array_unique( $issues ),
			'risk'  => $risk_label,
		);
	}

	/**
	 * Render custom admin UI inside feature card
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		?>
		<div class="arbricks-seo-content-scan-tool" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
			<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
				<div class="field">
					<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Scan Scope', 'arbricks' ); ?></label>
					<select id="arbricks-scs-scope" class="widefat">
						<option value="both"><?php esc_html_e( 'Posts + Pages', 'arbricks' ); ?></option>
						<option value="posts"><?php esc_html_e( 'Posts Only', 'arbricks' ); ?></option>
						<option value="pages"><?php esc_html_e( 'Pages Only', 'arbricks' ); ?></option>
					</select>
				</div>
				<div class="field">
					<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Item Limit', 'arbricks' ); ?></label>
					<input type="number" id="arbricks-scs-limit" value="100" min="10" max="500" class="widefat">
				</div>
				<div class="field" style="display: flex; align-items: flex-end; padding-bottom: 5px;">
					<label>
						<input type="checkbox" id="arbricks-scs-private">
						<?php esc_html_e( 'Include private content', 'arbricks' ); ?>
					</label>
				</div>
			</div>

			<div class="actions">
				<button type="button" id="arbricks-scs-scan-btn" class="button button-primary">
					<?php esc_html_e( 'Start Scan Now', 'arbricks' ); ?>
				</button>
			</div>

			<div id="arbricks-scs-results" style="margin-top: 20px; display: none;">
				<div id="arbricks-scs-summary" style="margin-bottom: 15px; padding: 10px; background: #fff8e1; border-radius: 4px;"></div>
				
				<table class="widefat striped" style="border: 1px solid #ccd0d4;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'arbricks' ); ?></th>
							<th><?php esc_html_e( 'Title', 'arbricks' ); ?></th>
							<th><?php esc_html_e( 'Detected Issues', 'arbricks' ); ?></th>
							<th><?php esc_html_e( 'Risk Level', 'arbricks' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'arbricks' ); ?></th>
						</tr>
					</thead>
					<tbody id="arbricks-scs-results-body"></tbody>
				</table>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#arbricks-scs-scan-btn').on('click', function() {
				var $btn = $(this);
				var originalText = $btn.text();
				
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Scanning...', 'arbricks' ) ); ?>');
				$('#arbricks-scs-results').hide();
				$('#arbricks-scs-results-body').empty();

				$.post(ajaxurl, {
					action: 'arbricks_seo_content_scan',
					nonce: '<?php echo esc_js( wp_create_nonce( 'arbricks_seo_content_scan' ) ); ?>',
					scope: $('#arbricks-scs-scope').val(),
					limit: $('#arbricks-scs-limit').val(),
					include_private: $('#arbricks-scs-private').is(':checked') ? 1 : 0
				}, function(response) {
					$btn.prop('disabled', false).text(originalText);
					
					if (response.success) {
						var flagged = response.data.flagged;
						
						$('#arbricks-scs-summary').text('<?php echo esc_js( __( 'Scanned:', 'arbricks' ) ); ?> ' + response.data.scanned + ' <?php echo esc_js( __( 'items.', 'arbricks' ) ); ?> ' + flagged.length + ' <?php echo esc_js( __( 'suspicious items detected.', 'arbricks' ) ); ?>');
						
						if (flagged.length === 0) {
							$('#arbricks-scs-results-body').html('<tr><td colspan="5" style="text-align:center;"><?php echo esc_js( __( 'No suspicious indicators found.', 'arbricks' ) ); ?></td></tr>');
						} else {
							// Sort: High risk first.
							flagged.sort(function(a, b) {
								var riskMap = { '<?php echo esc_js( __( 'High', 'arbricks' ) ); ?>': 3, '<?php echo esc_js( __( 'Medium', 'arbricks' ) ); ?>': 2, '<?php echo esc_js( __( 'Low', 'arbricks' ) ); ?>': 1 };
								return riskMap[b.risk] - riskMap[a.risk];
							});

							flagged.forEach(function(item) {
								var riskColor = '#68de7c'; // Low
								if (item.risk === '<?php echo esc_js( __( 'High', 'arbricks' ) ); ?>') riskColor = '#d63638';
								else if (item.risk === '<?php echo esc_js( __( 'Medium', 'arbricks' ) ); ?>') riskColor = '#f0b20a';

								var issuesStr = item.issues.join(', ');
								var riskLabel = '<?php echo esc_js( __( 'Risk:', 'arbricks' ) ); ?> ' + item.risk;

								$('#arbricks-scs-results-body').append(
									'<tr>' +
										'<td>' + item.id + '</td>' +
										'<td><strong>' + item.title + '</strong><br><small>' + item.type + '</small></td>' +
										'<td>' + issuesStr + '</td>' +
										'<td><span style="font-weight:bold; color:' + riskColor + ';">' + item.risk + '</span></td>' +
										'<td><a href="' + item.edit_link + '" class="button button-small" target="_blank"><?php echo esc_js( __( 'Edit', 'arbricks' ) ); ?></a></td>' +
									'</tr>'
								);
							});
						}
						$('#arbricks-scs-results').fadeIn();
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
