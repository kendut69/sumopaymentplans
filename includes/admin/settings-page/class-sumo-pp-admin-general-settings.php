<?php

/**
 * General Tab.
 *
 * @class SUMO_PP_General_Settings
 * @package Class
 */
class SUMO_PP_General_Settings extends SUMO_PP_Abstract_Settings {

	/**
	 * SUMO_PP_General_Settings constructor.
	 */
	public function __construct() {

		$this->id            = 'general';
		$this->label         = __( 'General', 'sumopaymentplans' );
		$this->custom_fields = array(
			'get_shortcodes_and_its_usage',
			'get_limited_users_of_payment_product',
			'get_global_selected_plans',
		);
		$this->settings      = $this->get_settings();
		$this->init();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section;

		return apply_filters( 'sumopaymentplans_get_' . $this->id . '_settings', array(
			array( 'type' => $this->get_custom_field_type( 'get_shortcodes_and_its_usage' ) ),
			array(
				'name' => __( 'Deposit Global Level Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'deposit_global_settings',
			),
			array(
				'name'     => __( 'Force Deposit', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'force_deposit',
				'newids'   => $this->prefix . 'force_deposit',
				'type'     => 'checkbox',
				'std'      => 'no',
				'default'  => 'no',
				'desc'     => __( 'When enabled, the user will be forced to pay a deposit amount', 'sumopaymentplans' ),
				'desc_tip' => true,
			),
			array(
				'name'     => __( 'Deposit Type', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'deposit_type',
				'newids'   => $this->prefix . 'deposit_type',
				'type'     => 'select',
				'options'  => array(
					'pre-defined'  => __( 'Predefined Deposit Amount', 'sumopaymentplans' ),
					'user-defined' => __( 'User Defined Deposit Amount', 'sumopaymentplans' ),
				),
				'std'      => 'pre-defined',
				'default'  => 'pre-defined',
				'desc'     => '',
				'desc_tip' => true,
			),
			array(
				'name'    => __( 'Deposit Price Type', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'deposit_price_type',
				'newids'  => $this->prefix . 'deposit_price_type',
				'type'    => 'select',
				'options' => array(
					'fixed-price'              => __( 'Fixed Price', 'sumopaymentplans' ),
					'percent-of-product-price' => __( 'Percentage of Product Price', 'sumopaymentplans' ),
				),
				'std'     => 'percent-of-product-price',
				'default' => 'percent-of-product-price',
			),
			array(
				'name'              => __( 'Deposit Amount', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'fixed_deposit_price',
				'newids'            => $this->prefix . 'fixed_deposit_price',
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0.01',
					'step' => '0.01',
				),
			),
			array(
				'name'              => __( 'Deposit Percentage', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'fixed_deposit_percent',
				'newids'            => $this->prefix . 'fixed_deposit_percent',
				'type'              => 'number',
				'std'               => '50',
				'default'           => '50',
				'desc'              => '',
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min'  => '0.01',
					'max'  => '99.99',
					'step' => '0.01',
				),
			),
			array(
				'name'              => __( 'Minimum Deposit (%)', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'min_deposit',
				'newids'            => $this->prefix . 'min_deposit',
				'type'              => 'number',
				'std'               => '0.01',
				'default'           => '0.01',
				'desc'              => '',
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min'  => '0.01',
					'max'  => '99.99',
					'step' => '0.01',
				),
			),
			array(
				'name'              => __( 'Maximum Deposit (%)', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'max_deposit',
				'newids'            => $this->prefix . 'max_deposit',
				'type'              => 'number',
				'std'               => '99.99',
				'default'           => '99.99',
				'desc'              => '',
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min'  => '0.01',
					'max'  => '99.99',
					'step' => '0.01',
				),
			),
			array(
				'name'    => __( 'Deposit Balance Payment Due Date', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'pay_balance_after',
				'newids'  => $this->prefix . 'pay_balance_after',
				'type'    => 'number',
				'std'     => '10',
				'default' => '10',
				'desc'    => __( 'day(s) from the date of deposit payment', 'sumopaymentplans' ),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'deposit_global_settings' ),
			array(
				'name' => __( 'Payment Plan Global Level Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'global_payment_plan_settings',
			),
			array(
				'name'     => __( 'Force Payment Plan', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'force_payment_plan',
				'newids'   => $this->prefix . 'force_payment_plan',
				'type'     => 'checkbox',
				'std'      => 'no',
				'default'  => 'no',
				'desc'     => '',
				'desc_tip' => true,
			),
			array(
				'type' => $this->get_custom_field_type( 'get_global_selected_plans' ),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'global_payment_plan_settings' ),
			array(
				'name' => __( 'General Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'general_settings',
			),
			array(
				'name'              => __( 'Create Next Payable Order', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'create_next_payable_order_before',
				'newids'            => $this->prefix . 'create_next_payable_order_before',
				'type'              => 'number',
				'std'               => '1',
				'default'           => '1',
				'desc'              => __( 'day(s)', 'sumopaymentplans' ),
				'desc_tip'          => __( 'Payable order will be created before specified days. If set as 1 then order will be created one day before payment date', 'sumopaymentplans' ),
				'custom_attributes' => array(
					'min' => '1',
				),
			),
			array(
				'name'     => __( 'Invoice Reminder', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'notify_invoice_before',
				'newids'   => $this->prefix . 'notify_invoice_before',
				'type'     => 'text',
				'std'      => '3,2,1',
				'default'  => '3,2,1',
				'desc'     => __( 'day(s) before next payment date', 'sumopaymentplans' ),
				'desc_tip' => false,
			),
			array(
				'name'     => __( 'Automatic Charge Reminder', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'notify_auto_charge_reminder_before',
				'newids'   => $this->prefix . 'notify_auto_charge_reminder_before',
				'type'     => 'text',
				'std'      => '3,2,1',
				'default'  => '3,2,1',
				'desc'     => __( 'day(s) before next payment date', 'sumopaymentplans' ),
				'desc_tip' => false,
			),
			array(
				'name'    => __( 'Allow Coupon Usage for Payment Plan Orders', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'allow_coupon',
				'newids'  => $this->prefix . 'allow_coupon',
				'type'    => 'checkbox',
				'std'     => 'yes',
				'default' => 'yes',
			),
			array(
				'name'     => __( 'Show Deposit/Payment Plans Option for', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'show_deposit_r_payment_plans_for',
				'newids'   => $this->prefix . 'show_deposit_r_payment_plans_for',
				'type'     => 'select',
				'std'      => 'all_users',
				'default'  => 'all_users',
				'options'  => array(
					'all_users'         => __( 'All Users', 'sumopaymentplans' ),
					'include_users'     => __( 'Include User(s)', 'sumopaymentplans' ),
					'exclude_users'     => __( 'Exclude User(s)', 'sumopaymentplans' ),
					'include_user_role' => __( 'Include User Role(s)', 'sumopaymentplans' ),
					'exclude_user_role' => __( 'Exclude User Role(s)', 'sumopaymentplans' ),
				),
				'desc'     => '',
				'desc_tip' => true,
			),
			array(
				'type' => $this->get_custom_field_type( 'get_limited_users_of_payment_product' ),
			),
			array(
				'name'     => __( 'Select User Role(s)', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'get_limited_userroles_of_payment_product',
				'newids'   => $this->prefix . 'get_limited_userroles_of_payment_product',
				'class'    => 'wc-enhanced-select',
				'type'     => 'multiselect',
				'options'  => _sumo_pp_get_user_roles( true ),
				'std'      => array(),
				'default'  => array(),
				'desc'     => '',
				'desc_tip' => true,
			),
			array(
				'name'     => __( 'Products that can be Placed in a Single Order', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'products_that_can_be_placed_in_an_order',
				'newids'   => $this->prefix . 'products_that_can_be_placed_in_an_order',
				'type'     => 'select',
				'options'  => array(
					'any'               => __( 'Both Payment Plan/Deposit & Non-Payment Plan/Deposit', 'sumopaymentplans' ),
					'multiple-payments' => __( 'Multiple Payment Plan/Deposit Products', 'sumopaymentplans' ),
					'single-payment'    => __( 'Only One Payment Plan/Deposit Product', 'sumopaymentplans' ),
				),
				'std'      => 'any',
				'default'  => 'any',
				'desc'     => '',
				'desc_tip' => true,
			),
			array(
				'name'     => __( 'Payment Plans that can be Selected for Multiple Products in a Single Order', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'allow_payment_plans_in_cart_as',
				'newids'   => $this->prefix . 'allow_payment_plans_in_cart_as',
				'type'     => 'select',
				'options'  => array(
					'same-r-different-plan' => __( 'Same/Different', 'sumopaymentplans' ),
					'same-plan'             => __( 'Same', 'sumopaymentplans' ),
				),
				'std'      => 'same-r-different-plan',
				'default'  => 'same-r-different-plan',
				'desc'     => '',
				'desc_tip' => true,
			),
			array(
				'name'     => __( 'Charge Shipping Fee', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'charge_shipping_during',
				'newids'   => $this->prefix . 'charge_shipping_during',
				'type'     => 'select',
				'options'  => array(
					'initial-payment' => __( 'During Initial Payment', 'sumopaymentplans' ),
					'final-payment'   => __( 'During Final Payment', 'sumopaymentplans' ),
				),
				'std'      => 'initial-payment',
				'default'  => 'initial-payment',
				'desc'     => '',
				'desc_tip' => true,
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'general_settings' ),
			array(
				'name' => __( 'Overdue Payment Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'overdue_payment_settings',
			),
			array(
				'name'              => __( 'Overdue Period', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'specified_overdue_days',
				'newids'            => $this->prefix . 'specified_overdue_days',
				'type'              => 'number',
				'std'               => '0',
				'default'           => '0',
				'desc'              => __( 'day(s)', 'sumopaymentplans' ),
				'desc_tip'          => __( 'If the payment is not made within the payment date, payment will goto overdue status and it will be in that status for the specified number of days.', 'sumopaymentplans' ),
				'custom_attributes' => array(
					'min' => '0',
				),
			),
			array(
				'name'     => __( 'Overdue Reminder', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'notify_overdue_before',
				'newids'   => $this->prefix . 'notify_overdue_before',
				'type'     => 'text',
				'std'      => '1',
				'default'  => '1',
				'desc'     => __( 'day(s) after payment due date', 'sumopaymentplans' ),
				'desc_tip' => false,
			),
			array(
				'name'    => __( 'Number of Attempts to Charge Automatic Payment during Overdue Status', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'automatic_payment_retries_during_overdue',
				'newids'  => $this->prefix . 'automatic_payment_retries_during_overdue',
				'type'    => 'text',
				'std'     => '2',
				'default' => '2',
				'desc'    => __( 'times per day', 'sumopaymentplans' ),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'overdue_payment_settings' ),
			array(
				'name' => __( 'Payment Gateway Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'payment_gateway_settings',
			),
			array(
				'name'    => __( 'Disable Payment Gateways', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'disabled_payment_gateways',
				'newids'  => $this->prefix . 'disabled_payment_gateways',
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => _sumo_pp_get_active_payment_gateways(),
				'std'     => array(),
				'default' => array(),
			),
			array(
				'name'    => __( 'Enable Automatic Payment Gateways', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'enable_automatic_payment_gateways',
				'newids'  => $this->prefix . 'enable_automatic_payment_gateways',
				'type'    => 'checkbox',
				'std'     => 'yes',
				'default' => 'yes',
				'desc'    => __( 'If enabled, automatic payment gateways will be displayed in checkout page when payment plan/deposit product is added in cart', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Automatic Payment Gateway Mode', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'automatic_payment_gateway_mode',
				'newids'  => $this->prefix . 'automatic_payment_gateway_mode',
				'type'    => 'select',
				'options' => array(
					'auto-or-manual' => __( 'Automatic/Manual', 'sumopaymentplans' ),
					'force-auto'     => __( 'Force Automatic', 'sumopaymentplans' ),
					'force-manual'   => __( 'Force Manual', 'sumopaymentplans' ),
				),
				'std'     => 'auto-or-manual',
				'default' => 'auto-or-manual',
			),
			array(
				'name'    => __( 'Enable Manual Payment Gateways', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'enable_manual_payment_gateways',
				'newids'  => $this->prefix . 'enable_manual_payment_gateways',
				'type'    => 'checkbox',
				'std'     => 'yes',
				'default' => 'yes',
				'desc'    => __( 'If enabled, manual payment gateways will be displayed along with automatic payment gateways in checkout page when payment plan/deposit product is added in cart', 'sumopaymentplans' ),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'payment_gateway_settings' ),
				) );
	}

	/**
	 * Custom type field.
	 */
	public function get_shortcodes_and_its_usage() {
		$shortcodes = array(
			'[sumo_pp_my_payments]' => __( 'Use this shortcode to display My Payments.', 'sumopaymentplans' ),
		);
		?>
		<table class="widefat" data-sort="false">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shortcode', 'sumopaymentplans' ); ?></th>
					<th><?php esc_html_e( 'Purpose', 'sumopaymentplans' ); ?></th>
				</tr>
			</thead>
			<tbody>                
				<?php foreach ( $shortcodes as $shortcode => $purpose ) : ?>
					<tr>
						<td><?php echo esc_html( $shortcode ); ?></td>
						<td><?php echo esc_html( $purpose ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_limited_users_of_payment_product() {

		_sumo_pp_wc_search_field( array(
			'class'       => 'wc-customer-search',
			'id'          => $this->prefix . 'get_limited_users_of_payment_product',
			'type'        => 'customer',
			'title'       => __( 'Select User(s)', 'sumopaymentplans' ),
			'placeholder' => __( 'Search for a user&hellip;', 'sumopaymentplans' ),
			'options'     => ( array ) get_option( $this->prefix . 'get_limited_users_of_payment_product', array() ),
		) );
	}

	/**
	 * Custom type field.
	 */
	public function get_global_selected_plans() {
		?>
		<tr>
			<th>
				<?php esc_html_e( 'Select Plans', 'sumopaymentplans' ); ?>
			</th>
			<td>
				<span class="_sumo_pp_add_plans">
					<span class="woocommerce-help-tip" data-tip="<?php esc_html_e( 'Select the layout as per your theme preference', 'sumopaymentplans' ); ?>"></span>
					<a href="#" class="button" id="_sumo_pp_add_col_1_plan"><?php esc_html_e( 'Add Row for Column 1', 'sumopaymentplans' ); ?></a>
					<a href="#" class="button" id="_sumo_pp_add_col_2_plan"><?php esc_html_e( 'Add Row for Column 2', 'sumopaymentplans' ); ?></a>
					<span class="spinner"></span>
				</span>
				<?php
				$selected_plans     = get_option( $this->prefix . 'selected_plans' );
				$bkw_selected_plans = is_array( $selected_plans ) && ! empty( $selected_plans ) ? $selected_plans : array( 'col_1' => array(), 'col_2' => array() );
				$selected_plans     = $bkw_selected_plans;

				if ( ! isset( $bkw_selected_plans[ 'col_1' ] ) ) {
					$selected_plans = array( 'col_1' => array(), 'col_2' => array() );

					foreach ( $bkw_selected_plans as $row_id => $selected_plan ) {
						$selected_plans[ 'col_1' ][] = ! empty( $selected_plan ) ? ( array ) $selected_plan : array();
					}
				}
				?>
				<div class="sumo-pp-selected-plans">
					<?php
					foreach ( $selected_plans as $column_id => $selected_datas ) {
						$inline_style = 'col_1' === $column_id ? 'width:49%;display:block;float:left;clear:none;' : 'width:49%;display:block;float:right;clear:none;margin-right:10px;';
						?>
						<table class="widefat wc_input_table wc_gateways sortable <?php echo esc_attr( "_sumo_pp_selected_col_{$column_id}_plans" ); ?> _sumo_pp_selected_plans _sumo_pp_fields" style="<?php echo esc_attr( $inline_style ); ?>">
							<tbody class="selected_plans">
								<?php
								if ( is_array( $selected_datas ) && ! empty( $selected_datas ) ) {
									foreach ( $selected_datas as $row_id => $selected_data ) {
										echo '<tr><td class="sort" width="1%"></td><td>';
										_sumo_pp_wc_search_field( array(
											'class'       => 'wc-product-search',
											'action'      => '_sumo_pp_json_search_payment_plans',
											'id'          => "selected_{$column_id}_payment_plan_{$row_id}",
											'name'        => "_sumo_pp_selected_plans[{$column_id}][{$row_id}]",
											'type'        => 'payment_plans',
											'multiple'    => false,
											'options'     => ( array ) $selected_data,
											'placeholder' => __( 'Search for a payment plan&hellip;', 'sumopaymentplans' ),
										) );
										echo '</td><td><a href="#" class="remove_row button">X</a></td></tr>';
									}
								}
								?>
							</tbody>
						</table>
						<?php
					}
					?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Delete the custom options.
	 */
	public function custom_types_delete_options( $posted = null ) {
		delete_option( $this->prefix . 'selected_plans' );
		delete_option( $this->prefix . 'get_limited_users_of_payment_product' );
	}

	/**
	 * Save custom settings.
	 */
	public function custom_types_save( $posted ) {
		if ( isset( $posted[ $this->prefix . 'get_limited_users_of_payment_product' ] ) ) {
			update_option( $this->prefix . 'get_limited_users_of_payment_product', ! is_array( $posted[ $this->prefix . 'get_limited_users_of_payment_product' ] ) ? array_filter( array_map( 'absint', explode( ',', $posted[ $this->prefix . 'get_limited_users_of_payment_product' ] ) ) ) : $posted[ $this->prefix . 'get_limited_users_of_payment_product' ]  );
		}

		$selected_plans = isset( $posted[ "{$this->prefix}selected_plans" ] ) ? $posted[ "{$this->prefix}selected_plans" ] : array();
		foreach ( array( 'col_1', 'col_2' ) as $column_id ) {
			$selected_plans[ $column_id ] = ! empty( $selected_plans[ $column_id ] ) && is_array( $selected_plans[ $column_id ] ) ? array_map( 'implode', ( array_values( $selected_plans[ $column_id ] ) ) ) : array();
		}

		update_option( $this->prefix . 'selected_plans', $selected_plans );
	}

	/**
	 * Save the custom options once.
	 */
	public function custom_types_add_options( $posted = null ) {
		add_option( $this->prefix . 'selected_plans', array() );
		add_option( $this->prefix . 'get_limited_users_of_payment_product', array() );

		// Backward compatibility.
		if ( false === get_option( $this->prefix . 'balance_payment_due' ) ) {
			add_option( $this->prefix . 'pay_balance_after', get_option( $this->prefix . 'pay_balance_after' ) );
		} elseif ( add_option( $this->prefix . 'pay_balance_after', get_option( $this->prefix . 'balance_payment_due' ) ) ) {
				delete_option( $this->prefix . 'balance_payment_due' );
		}

		$bkw_hide_payment_plans_for = get_option( $this->prefix . 'hide_payment_plans_only_for' );

		if ( false !== $bkw_hide_payment_plans_for && is_array( $bkw_hide_payment_plans_for ) && ! empty( $bkw_hide_payment_plans_for ) ) {
			update_option( $this->prefix . 'show_deposit_r_payment_plans_for', 'exclude_user_role' );
			update_option( $this->prefix . 'get_limited_userroles_of_payment_product', $bkw_hide_payment_plans_for );
			delete_option( $this->prefix . 'hide_payment_plans_only_for' );
		}
	}
}

return new SUMO_PP_General_Settings();
