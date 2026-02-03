<?php
/**
 * Feature: WooCommerce Area Shipping
 *
 * Calculate WooCommerce shipping fees based on the selected area (state),
 * using admin-defined regions and prices.
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
 * Class Feature_Arbricks_WC_Area_Shipping
 *
 * Calculate WooCommerce shipping fees based on the selected area.
 */
class Feature_Arbricks_WC_Area_Shipping implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string Unique feature identifier.
	 */
	public static function id(): string {
		return 'arbricks_wc_area_shipping';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array Feature metadata.
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'رسوم الشحن حسب المنطقة', 'arbricks' ),
			'description' => __( 'Calculate WooCommerce shipping fees based on the selected area (state).', 'arbricks' ),
			'category'    => 'woocommerce',
			'shortcode'   => '',
			'help'        => array(
				'summary'  => __( 'تتيح هذه الميزة حساب رسوم الشحن بناءً على المنطقة المختارة في صفحة الدفع. يمكنك تحديد أسماء المناطق وأسعار الشحن الخاصة بكل منها.', 'arbricks' ),
				'how_to'   => array(
					__( 'تأكد من تفعيل إضافة WooCommerce.', 'arbricks' ),
					__( 'قم بتفعيل الميزة من الأعلى.', 'arbricks' ),
					__( 'اختر الدولة التي تريد تطبيق حساب الشحن لها.', 'arbricks' ),
					__( 'أضف المناطق وأسعارها في قسم "المناطق المدعومة".', 'arbricks' ),
					__( 'احفظ التغييرات.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'الميزة تعمل فقط للدولة المختارة.', 'arbricks' ),
					__( 'تتطلب وجود حقل "المنطقة" (State) في صفحة الدفع والباركود الخاص به.', 'arbricks' ),
					__( 'سيتم تغيير تسمية حقل "المنطقة" إلى "المنطقة" وجعله إجبارياً.', 'arbricks' ),
					__( 'يتم حساب الشحن بناءً على المنطقة المختارة من القائمة حصراً.', 'arbricks' ),
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
		// Get WooCommerce countries if available.
		$countries = array();
		if ( class_exists( 'WooCommerce' ) ) {
			$countries = WC()->countries->get_countries();
		} else {
			$countries = array( 'SA' => 'Saudi Arabia' ); // Fallback.
		}

		return array(
			'arbricks_country'              => array(
				'type'        => 'select',
				'label'       => __( 'الدولة المختارة', 'arbricks' ),
				'description' => __( 'اختر الدولة التي سيتم تطبيق حساب الشحن لمناطقها.', 'arbricks' ),
				'options'     => $countries,
				'default'     => 'SA',
			),
			'arbricks_set_default_country' => array(
				'type'        => 'checkbox',
				'label'       => __( 'تعيين كدولة افتراضية', 'arbricks' ),
				'description' => __( 'تعيين الدولة المختارة كدولة افتراضية في صفحة الدفع.', 'arbricks' ),
				'default'     => false,
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Checkout field modifications.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'modify_checkout_fields' ), 20 );

		// Inject dynamic states/areas.
		add_filter( 'woocommerce_states', array( $this, 'inject_areas_as_states' ) );

		// Calculate shipping fees.
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_area_shipping_fee' ) );

		// Default country.
		add_filter( 'default_checkout_country', array( $this, 'set_default_checkout_country' ) );
	}

	/**
	 * Modify checkout fields (State labels and requirement)
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function modify_checkout_fields( $fields ): array {
		$settings = Options::get_feature_settings( self::id() );
		$target_country = $settings['arbricks_country'] ?? '';

		if ( empty( $target_country ) ) {
			return $fields;
		}

		// Apply to Billing and Shipping.
		foreach ( array( 'billing', 'shipping' ) as $type ) {
			if ( isset( $fields[ $type ][ $type . '_state' ] ) ) {
				$fields[ $type ][ $type . '_state' ]['label']    = __( 'المنطقة', 'arbricks' );
				$fields[ $type ][ $type . '_state' ]['required'] = true;
			}
		}

		return $fields;
	}

	/**
	 * Inject areas as states for the selected country
	 *
	 * @param array $states WooCommerce states.
	 * @return array
	 */
	public function inject_areas_as_states( $states ): array {
		$settings = Options::get_feature_settings( self::id() );
		$target_country = $settings['arbricks_country'] ?? '';
		$areas = $settings['arbricks_areas'] ?? array();

		if ( empty( $target_country ) || empty( $areas ) || ! is_array( $areas ) ) {
			return $states;
		}

		$formatted_states = array();
		foreach ( $areas as $area ) {
			if ( ! empty( $area['arbricks_area_name'] ) ) {
				// Use the area name as both key and value to simplify match.
				$key = sanitize_title_with_dashes( $area['arbricks_area_name'] );
				$formatted_states[ $key ] = $area['arbricks_area_name'];
			}
		}

		if ( ! empty( $formatted_states ) ) {
			$states[ $target_country ] = $formatted_states;
		}

		return $states;
	}

	/**
	 * Calculate and add shipping fee based on area
	 *
	 * @return void
	 */
	public function calculate_area_shipping_fee(): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$settings = Options::get_feature_settings( self::id() );
		$target_country = $settings['arbricks_country'] ?? '';
		$areas = $settings['arbricks_areas'] ?? array();

		if ( empty( $target_country ) || empty( $areas ) ) {
			return;
		}

		$customer_country = WC()->customer->get_shipping_country();
		$customer_state   = WC()->customer->get_shipping_state();

		if ( $customer_country !== $target_country || empty( $customer_state ) ) {
			return;
		}

		foreach ( $areas as $area ) {
			$key = sanitize_title_with_dashes( $area['arbricks_area_name'] );
			if ( $key === $customer_state && ! empty( $area['arbricks_shipping_cost'] ) ) {
				WC()->cart->add_fee(
					$area['arbricks_area_name'],
					floatval( $area['arbricks_shipping_cost'] ),
					false // Non-taxable for simplicity, but could be adjusted.
				);
				break;
			}
		}
	}

	/**
	 * Set default checkout country
	 *
	 * @param string $default_country Default country.
	 * @return string
	 */
	public function set_default_checkout_country( $default_country ): string {
		$settings = Options::get_feature_settings( self::id() );
		$is_enabled = ! empty( $settings['arbricks_set_default_country'] );
		$target_country = $settings['arbricks_country'] ?? '';

		if ( $is_enabled && ! empty( $target_country ) ) {
			return $target_country;
		}

		return $default_country;
	}

	/**
	 * Render custom admin UI for Areas Repeater
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {
		$settings = Options::get_feature_settings( self::id() );
		$areas    = $settings['arbricks_areas'] ?? array();
		$feature_id = self::id();
		?>
		<div class="arbricks-tool-area-shipping">
			<h4 class="arbricks-setting-label"><?php esc_html_e( 'المناطق والأسعار', 'arbricks' ); ?></h4>
			
			<div id="arbricks-areas-repeater" class="arbricks-repeater">
				<div class="arbricks-repeater-items">
					<?php if ( ! empty( $areas ) && is_array( $areas ) ) : ?>
						<?php foreach ( $areas as $index => $area ) : ?>
							<div class="arbricks-repeater-row" data-index="<?php echo esc_attr( $index ); ?>">
								<input type="text" 
									name="feature_settings[<?php echo esc_attr( $feature_id ); ?>][arbricks_areas][<?php echo esc_attr( $index ); ?>][arbricks_area_name]" 
									placeholder="<?php esc_attr_e( 'اسم المنطقة', 'arbricks' ); ?>" 
									value="<?php echo esc_attr( $area['arbricks_area_name'] ?? '' ); ?>"
									class="widefat">
								<input type="number" 
									name="feature_settings[<?php echo esc_attr( $feature_id ); ?>][arbricks_areas][<?php echo esc_attr( $index ); ?>][arbricks_shipping_cost]" 
									placeholder="<?php esc_attr_e( 'سعر الشحن', 'arbricks' ); ?>" 
									step="0.01"
									value="<?php echo esc_attr( $area['arbricks_shipping_cost'] ?? '' ); ?>"
									class="widefat">
								<button type="button" class="button arbricks-remove-row">
									<span class="dashicons dashicons-no-alt"></span>
								</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<button type="button" id="arbricks-add-area" class="button">
					<span class="dashicons dashicons-plus"></span>
					<?php esc_html_e( 'إضافة منطقة جديدة', 'arbricks' ); ?>
				</button>
			</div>

			<style>
				.arbricks-repeater-row {
					display: flex;
					gap: 10px;
					margin-bottom: 10px;
					align-items: center;
				}
				.arbricks-repeater-row input {
					flex: 1;
				}
				.arbricks-repeater-row .arbricks-remove-row {
					color: #d63638;
					border-color: #d63638;
					background: #fff;
				}
				.arbricks-repeater-row .arbricks-remove-row:hover {
					background: #fbe9e9;
					border-color: #d63638;
					color: #d63638;
				}
			</style>

			<script>
				jQuery(document).ready(function($) {
					const $repeater = $('#arbricks-areas-repeater');
					const $items = $repeater.find('.arbricks-repeater-items');
					const featureId = '<?php echo esc_js( $feature_id ); ?>';

					$('#arbricks-add-area').on('click', function() {
						const nextIndex = $items.find('.arbricks-repeater-row').length;
						const rowHtml = `
							<div class="arbricks-repeater-row" data-index="${nextIndex}">
								<input type="text" 
									name="feature_settings[${featureId}][arbricks_areas][${nextIndex}][arbricks_area_name]" 
									placeholder="<?php esc_attr_e( 'اسم المنطقة', 'arbricks' ); ?>" 
									class="widefat">
								<input type="number" 
									name="feature_settings[${featureId}][arbricks_areas][${nextIndex}][arbricks_shipping_cost]" 
									placeholder="<?php esc_attr_e( 'سعر الشحن', 'arbricks' ); ?>" 
									step="0.01"
									class="widefat">
								<button type="button" class="button arbricks-remove-row">
									<span class="dashicons dashicons-no-alt"></span>
								</button>
							</div>
						`;
						$items.append(rowHtml);
					});

					$items.on('click', '.arbricks-remove-row', function() {
						$(this).closest('.arbricks-repeater-row').remove();
						// Re-index to ensure sequential keys in POS
						reindex();
					});

					function reindex() {
						$items.find('.arbricks-repeater-row').each(function(index) {
							$(this).attr('data-index', index);
							$(this).find('input').each(function() {
								const name = $(this).attr('name');
								const newName = name.replace(/\[arbricks_areas\]\[\d+\]/, '[arbricks_areas][' + index + ']');
								$(this).attr('name', newName);
							});
						});
					}
				});
			</script>
		</div>
		<?php
	}
}
