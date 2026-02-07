<?php
/**
 * Feature: WooCommerce Admin Invoice Print
 *
 * Adds functionality to print invoices for WooCommerce orders from the admin panel.
 * Supports single order print and bulk printing with RTL support for thermal printers.
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
 * Class Feature_ArBricks_WC_Admin_Invoice_Print
 */
class Feature_ArBricks_WC_Admin_Invoice_Print implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_wc_admin_invoice_print';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Print Order Invoice from Admin', 'arbricks' ),
			'description' => __( 'Adds an invoice print button for orders within the WooCommerce admin panel, supporting single and bulk printing.', 'arbricks' ),
			'category'    => 'woocommerce',
			'help'        => array(
				'summary' => __( 'Allows managers to print simplified invoices (receipts) for orders directly from the WooCommerce orders page. The invoice is designed to be compatible with thermal printers and supports RTL direction.', 'arbricks' ),
				'how_to'  => array(
					__( 'Navigate to the WooCommerce orders page.', 'arbricks' ),
					__( 'To print a single order: Click the print icon in the actions column.', 'arbricks' ),
					__( 'To print bulk orders: Select orders, then choose "Print Invoices" from the "Bulk actions" menu.', 'arbricks' ),
				),
				'notes'   => array(
					__( 'The browser may block multiple popups during bulk printing; please allow popups for your site.', 'arbricks' ),
					__( 'Tip: Ensure paper size is set to match your printer (80mm or 58mm).', 'arbricks' ),
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
			'logo_id'     => array(
				'type'        => 'media',
				'label'       => __( 'Invoice Logo', 'arbricks' ),
				'description' => __( 'Choose the logo to appear at the top of the invoice.', 'arbricks' ),
				'default'     => '',
			),
			'paper_size'  => array(
				'type'    => 'select',
				'label'   => __( 'Paper Size', 'arbricks' ),
				'default' => '80mm',
				'options' => array(
					'80mm' => '80mm (Thermal)',
					'58mm' => '58mm (Small Thermal)',
					'A4'   => 'A4 (Standard)',
				),
			),
			'auto_print'  => array(
				'type'    => 'checkbox',
				'label'   => __( 'Auto Print & Close', 'arbricks' ),
				'description' => __( 'Open print dialog immediately and close window after printing.', 'arbricks' ),
				'default' => true,
			),
			'bulk_delay'  => array(
				'type'    => 'number',
				'label'   => __( 'Bulk Print Delay (ms)', 'arbricks' ),
				'description' => __( 'Delay between opening windows during bulk printing to avoid browser blocks.', 'arbricks' ),
				'default' => 800,
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Single order actions (legacy and modern).
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_order_print_action' ), 10, 2 );
		
		// Bulk actions.
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'add_bulk_print_action' ) );
		add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_bulk_print_action' ), 10, 3 );
		
		// Support for older WooCommerce/HPOS disabled views if needed.
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_print_action' ) );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_print_action' ), 10, 3 );

		// Print endpoint.
		add_action( 'admin_post_arbricks_wc_invoice_print', array( $this, 'render_invoice' ) );

		// Styles for the action icon.
		add_action( 'admin_head', array( $this, 'add_admin_styles' ) );
		
		// JS for bulk printing.
		add_action( 'admin_footer', array( $this, 'add_bulk_js' ) );
	}

	/**
	 * Add print action to order list
	 */
	public function add_order_print_action( $actions, $order ) {
		$order_id = $order->get_id();
		$url      = $this->get_print_url( $order_id );

		$actions['arbricks_print'] = array(
			'url'    => $url,
			'name'   => __( 'Print Invoice', 'arbricks' ),
			'action' => 'arbricks-print-invoice',
		);

		return $actions;
	}

	/**
	 * Add bulk print action
	 */
	public function add_bulk_print_action( $actions ) {
		$actions['arbricks_bulk_print'] = __( 'Print Invoice (ArBricks)', 'arbricks' );
		return $actions;
	}

	/**
	 * Handle bulk print action
	 */
	public function handle_bulk_print_action( $redirect_to, $action, $ids ) {
		if ( 'arbricks_bulk_print' !== $action ) {
			return $redirect_to;
		}

		$urls = array();
		foreach ( $ids as $id ) {
			$urls[] = $this->get_print_url( $id );
		}

		// We store the URLs in session transient to pick them up in the footer JS.
		set_transient( 'arbricks_bulk_print_' . get_current_user_id(), $urls, 60 );

		return add_query_arg( 'arbricks_bulk_print', '1', $redirect_to );
	}

	/**
	 * Get print URL for an order
	 */
	private function get_print_url( $order_id ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=arbricks_wc_invoice_print&order_id=' . $order_id ),
			'arbricks_print_invoice_' . $order_id
		);
	}

	/**
	 * Add custom CSS for the action icon
	 */
	public function add_admin_styles() {
		?>
		<style>
			.widefat .column-order_actions a.arbricks-print-invoice::after {
				font-family: Dashicons;
				content: "\f115"; /* printer */
			}
		</style>
		<?php
	}

	/**
	 * Add JS for handling bulk printing via multiple windows
	 */
	public function add_bulk_js() {
		$user_id = get_current_user_id();
		$urls    = get_transient( 'arbricks_bulk_print_' . $user_id );

		if ( ! $urls || ! is_array( $urls ) ) {
			return;
		}

		delete_transient( 'arbricks_bulk_print_' . $user_id );
		$delay = (int) Options::get_feature_setting( self::id(), 'bulk_delay', 800 );

		?>
		<script>
			(function($) {
				const urls = <?php echo wp_json_encode( $urls ); ?>;
				const delay = <?php echo (int) $delay; ?>;
				
				if (urls.length > 0) {
					let i = 0;
					function openNext() {
						if (i < urls.length) {
							window.open(urls[i], '_blank');
							i++;
							setTimeout(openNext, delay);
						}
					}
					openNext();
				}
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * Render the invoice HTML
	 */
	public function render_invoice() {
		$order_id = isset( $_GET['order_id'] ) ? (int) $_GET['order_id'] : 0;
		if ( ! $order_id ) {
			wp_die( esc_html__( 'Invalid order ID.', 'arbricks' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'arbricks' ) );
		}

		check_admin_referer( 'arbricks_print_invoice_' . $order_id );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_die( esc_html__( 'Order not found.', 'arbricks' ) );
		}

		$settings   = Options::get_feature_settings( self::id() );
		$logo_id    = $settings['logo_id'] ?? '';
		$paper_size = $settings['paper_size'] ?? '80mm';
		$auto_print = $settings['auto_print'] ?? true;

		$logo_url = '';
		if ( $logo_id ) {
			$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
		}

		// RTL Support check.
		$is_rtl = is_rtl() || get_bloginfo( 'language' ) === 'ar';

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?> dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
		<head>
			<meta charset="UTF-8">
			<title><?php printf( esc_html__( 'Invoice #%s', 'arbricks' ), $order->get_order_number() ); ?></title>
			<style>
				@page {
					margin: 0;
				}
				body {
					margin: 0;
					padding: 10px;
					font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
					font-size: 12px;
					line-height: 1.4;
					color: #000;
					background: #fff;
					<?php if ( 'A4' !== $paper_size ) : ?>
					width: <?php echo esc_attr( $paper_size ); ?>;
					<?php endif; ?>
				}
				.invoice-container {
					width: 100%;
				}
				.header {
					text-align: center;
					margin-bottom: 20px;
				}
				.logo {
					max-width: 150px;
					height: auto;
					margin-bottom: 10px;
				}
				.info-section {
					margin-bottom: 15px;
					border-bottom: 1px dashed #ccc;
					padding-bottom: 10px;
				}
				.info-row {
					display: flex;
					justify-content: space-between;
					margin-bottom: 4px;
				}
				.info-label {
					font-weight: bold;
				}
				.items-table {
					width: 100%;
					border-collapse: collapse;
					margin-bottom: 15px;
				}
				.items-table th {
					text-align: inherit;
					border-bottom: 1px solid #000;
					padding: 4px 0;
				}
				.items-table td {
					padding: 6px 0;
					vertical-align: top;
				}
				.items-table .line-total {
					text-align: <?php echo $is_rtl ? 'left' : 'right'; ?>;
				}
				.totals-section {
					border-top: 1px double #000;
					padding-top: 10px;
				}
				.totals-row {
					display: flex;
					justify-content: space-between;
					font-weight: bold;
					font-size: 14px;
				}
				.footer-note {
					margin-top: 20px;
					text-align: center;
					font-style: italic;
					font-size: 10px;
				}
				
				@media print {
					.no-print { display: none; }
					body { padding: 0; }
				}
			</style>
		</head>
		<body>
			<div class="invoice-container">
				<div class="header">
					<?php if ( $logo_url ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>" class="logo" alt="Logo">
					<?php endif; ?>
					<h2><?php esc_html_e( 'Order Receipt', 'arbricks' ); ?></h2>
					<p>#<?php echo esc_html( $order->get_order_number() ); ?></p>
				</div>

				<div class="info-section">
					<div class="info-row">
						<span class="info-label"><?php esc_html_e( 'Date:', 'arbricks' ); ?></span>
						<span><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
					</div>
					<div class="info-row">
						<span class="info-label"><?php esc_html_e( 'Customer:', 'arbricks' ); ?></span>
						<span><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></span>
					</div>
					<div class="info-row">
						<span class="info-label"><?php esc_html_e( 'Phone:', 'arbricks' ); ?></span>
						<span><?php echo esc_html( $order->get_billing_phone() ); ?></span>
					</div>
					<div class="info-row">
						<span class="info-label"><?php esc_html_e( 'Payment Method:', 'arbricks' ); ?></span>
						<span><?php echo esc_html( $order->get_payment_method_title() ); ?></span>
					</div>
				</div>

				<div class="info-section">
					<div class="info-label"><?php esc_html_e( 'Address:', 'arbricks' ); ?></div>
					<div>
						<?php 
						$address_parts = array();
						$address_parts[] = $order->get_billing_state();
						$address_parts[] = $order->get_billing_address_1();
						if ( $order->get_billing_address_2() ) {
							$address_parts[] = $order->get_billing_address_2();
						}
						$address_parts[] = $order->get_billing_city();
						$address_parts[] = $order->get_billing_postcode();
						$address_parts[] = WC()->countries->get_countries()[ $order->get_billing_country() ] ?? $order->get_billing_country();
						
						echo esc_html( implode( '، ', array_filter( $address_parts ) ) );
						?>
					</div>
				</div>

				<?php if ( $order->get_customer_note() ) : ?>
					<div class="info-section">
						<div class="info-label"><?php esc_html_e( 'Customer Note:', 'arbricks' ); ?></div>
						<div><?php echo esc_html( $order->get_customer_note() ); ?></div>
					</div>
				<?php endif; ?>

				<table class="items-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Product', 'arbricks' ); ?></th>
							<th><?php esc_html_e( 'Qty', 'arbricks' ); ?></th>
							<th class="line-total"><?php esc_html_e( 'Total', 'arbricks' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
							<tr>
								<td><?php echo esc_html( $item->get_name() ); ?></td>
								<td><?php echo esc_html( $item->get_quantity() ); ?></td>
								<td class="line-total"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<div class="totals-section">
					<div class="totals-row">
						<span><?php esc_html_e( 'Total:', 'arbricks' ); ?></span>
						<span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
					</div>
				</div>

				<div class="footer-note">
					<?php esc_html_e( 'Thank you for your business!', 'arbricks' ); ?>
				</div>
			</div>

			<?php if ( $auto_print ) : ?>
				<script>
					window.onload = function() {
						window.print();
						window.onafterprint = function() {
							window.close();
						};
						// Fallback if onafterprint isn't supported
						setTimeout(function() {
							// window.close(); // Optional: might be too aggressive if they want to see it
						}, 3000);
					};
				</script>
			<?php endif; ?>
		</body>
		</html>
		<?php
		exit;
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
