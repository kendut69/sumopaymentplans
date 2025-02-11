<?php

/**
 * Message Tab.
 * 
 * @class SUMO_PP_Messages_Settings
 * @package Class
 */
class SUMO_PP_Messages_Settings extends SUMO_PP_Abstract_Settings {

	/**
	 * SUMO_PP_Messages_Settings constructor.
	 */
	public function __construct() {

		$this->id            = 'messages' ;
		$this->label         = __( 'Messages', 'sumopaymentplans' ) ;
		$this->custom_fields = array(
			'get_shortcodes_and_its_usage',
				) ;
		$this->settings      = $this->get_settings() ;
		$this->init() ;
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section ;

		return apply_filters( 'sumopaymentplans_get_' . $this->id . '_settings', array(
			array( 'type' => $this->get_custom_field_type( 'get_shortcodes_and_its_usage' ) ),
			array(
				'name' => __( 'Shop Page Message Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'shop_message_settings',
			),
			array(
				'name'    => __( 'Add to Cart Label', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'add_to_cart_label',
				'newids'  => $this->prefix . 'add_to_cart_label',
				'type'    => 'text',
				'std'     => __( 'View More', 'sumopaymentplans' ),
				'default' => __( 'View More', 'sumopaymentplans' ),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'shop_message_settings' ),
			array(
				'name' => __( 'Single Product Page Message Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'single_product_message_settings',
			),
			array(
				'name'    => __( 'Pay in Full', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'pay_in_full_label',
				'newids'  => $this->prefix . 'pay_in_full_label',
				'type'    => 'text',
				'std'     => __( 'Pay in Full', 'sumopaymentplans' ),
				'default' => __( 'Pay in Full', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Pay a Deposit Amount', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'pay_a_deposit_amount_label',
				'newids'  => $this->prefix . 'pay_a_deposit_amount_label',
				'type'    => 'text',
				'std'     => __( 'Pay a Deposit Amount', 'sumopaymentplans' ),
				'default' => __( 'Pay a Deposit Amount', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Pay with Payment Plans', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'pay_with_payment_plans_label',
				'newids'  => $this->prefix . 'pay_with_payment_plans_label',
				'type'    => 'text',
				'std'     => __( 'Pay with Payment Plans', 'sumopaymentplans' ),
				'default' => __( 'Pay with Payment Plans', 'sumopaymentplans' ),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'single_product_message_settings' ),
			array(
				'name' => __( 'Cart And Checkout Page Message Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'cart_message_settings',
			),
			array(
				'name'    => __( 'Payment Plan', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'payment_plan_label',
				'newids'  => $this->prefix . 'payment_plan_label',
				'type'    => 'textarea',
				'std'     => __( '<p><strong>Payment Plan:</strong> <br>[sumo_pp_payment_plan_name]</p>', 'sumopaymentplans' ),
				'default' => __( '<p><strong>Payment Plan:</strong> <br>[sumo_pp_payment_plan_name]</p>', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Payment Plan Description', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'payment_plan_desc_label',
				'newids'  => $this->prefix . 'payment_plan_desc_label',
				'type'    => 'textarea',
				'std'     => __( '<small style="color:#777;">[sumo_pp_payment_plan_desc]</small>', 'sumopaymentplans' ),
				'default' => __( '<small style="color:#777;">[sumo_pp_payment_plan_desc]</small>', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Total Payable', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'total_payable_label',
				'newids'  => $this->prefix . 'total_payable_label',
				'type'    => 'textarea',
				'std'     => __( '<small style="color:#777;">Total <strong>[sumo_pp_total_payable]</strong> payable</small>', 'sumopaymentplans' ),
				'default' => __( '<small style="color:#777;">Total <strong>[sumo_pp_total_payable]</strong> payable</small>', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Next Installment Amount', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'next_installment_amount_label',
				'newids'  => $this->prefix . 'next_installment_amount_label',
				'type'    => 'textarea',
				'std'     => __( '<br><small style="color:#777;">Next Installment Amount: <strong>[sumo_pp_next_installment_amount]</strong></small>', 'sumopaymentplans' ),
				'default' => __( '<br><small style="color:#777;">Next Installment Amount: <strong>[sumo_pp_next_installment_amount]</strong></small>', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Next Payment Date', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'next_payment_date_label',
				'newids'  => $this->prefix . 'next_payment_date_label',
				'type'    => 'textarea',
				'std'     => __( '<br><small style="color:#777;">Next Payment Date: <strong>[sumo_pp_next_payment_date]</strong></small>', 'sumopaymentplans' ),
				'default' => __( '<br><small style="color:#777;">Next Payment Date: <strong>[sumo_pp_next_payment_date]</strong></small>', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'First Payment On', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'first_payment_on_label',
				'newids'  => $this->prefix . 'first_payment_on_label',
				'type'    => 'textarea',
				'std'     => __( '<br><small style="color:#777;">First Payment On: <strong>[sumo_pp_next_payment_date]</strong></small>', 'sumopaymentplans' ),
				'default' => __( '<br><small style="color:#777;">First Payment On: <strong>[sumo_pp_next_payment_date]</strong></small>', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Balance Payment Due Date', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'balance_payment_due_date_label',
				'newids'  => $this->prefix . 'balance_payment_due_date_label',
				'type'    => 'textarea',
				'std'     => __( '<br><small style="color:#777;">Balance Payment Due Date: <strong>[sumo_pp_next_payment_date]</strong></small>', 'sumopaymentplans' ),
				'default' => __( '<br><small style="color:#777;">Balance Payment Due Date: <strong>[sumo_pp_next_payment_date]</strong></small>', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Balance Payable', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'balance_payable_label',
				'newids'  => $this->prefix . 'balance_payable_label',
				'type'    => 'textarea',
				'std'     => __( '<br><small style="color:#777;">Balance <strong>[sumo_pp_balance_payable]</strong> payable</small>', 'sumopaymentplans' ),
				'default' => __( '<br><small style="color:#777;">Balance <strong>[sumo_pp_balance_payable]</strong> payable</small>', 'sumopaymentplans' ),
			),
			array(
				'name'     => __( 'Balance Payable Amount', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'balance_payable_amount_label',
				'newids'   => $this->prefix . 'balance_payable_amount_label',
				'type'     => 'textarea',
				'std'      => __( 'Balance Payable Amount', 'sumopaymentplans' ),
				'default'  => __( 'Balance Payable Amount', 'sumopaymentplans' ),
				'desc_tip' => __( 'To display label under "Cart Totals"', 'sumopaymentplans' ),
			),
			array(
				'name'     => __( 'Total Payable Amount', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'total_payable_amount_label',
				'newids'   => $this->prefix . 'total_payable_amount_label',
				'type'     => 'textarea',
				'std'      => __( 'Total Payable Amount', 'sumopaymentplans' ),
				'default'  => __( 'Total Payable Amount', 'sumopaymentplans' ),
				'desc_tip' => __( 'To display label under "Cart Totals"', 'sumopaymentplans' ),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'cart_message_settings' ),
				) ) ;
	}

	/**
	 * Custom type field.
	 */
	public function get_shortcodes_and_its_usage() {
		$shortcodes = array(
			'[sumo_pp_payment_plan_name]'          => __( 'Use this shortcode to display payment plan name.', 'sumopaymentplans' ),
			'[sumo_pp_payment_plan_desc]'          => __( 'Use this shortcode to display payment plan description.', 'sumopaymentplans' ),
			'[sumo_pp_total_payable]'              => __( 'Use this shortcode to display total payable amount.', 'sumopaymentplans' ),
			'[sumo_pp_balance_payable]'            => __( 'Use this shortcode to display balance payable amount.', 'sumopaymentplans' ),
			'[sumo_pp_next_payment_date]'          => __( 'Use this shortcode to display next payment date.', 'sumopaymentplans' ),
			'[sumo_pp_next_installment_amount]'    => __( 'Use this shortcode to display next installment_amount.', 'sumopaymentplans' ),
			'[sumo_pp_current_installment_amount]' => __( 'Use this shortcode to display current installment_amount.', 'sumopaymentplans' ),
				) ;
		?>
		<table class="widefat" data-sort="false">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shortcode', 'sumopaymentplans' ) ; ?></th>
					<th><?php esc_html_e( 'Purpose', 'sumopaymentplans' ) ; ?></th>
				</tr>
			</thead>
			<tbody>                
				<?php foreach ( $shortcodes as $shortcode => $purpose ) : ?>
					<tr>
						<td><?php echo esc_html( $shortcode ) ; ?></td>
						<td><?php echo esc_html( $purpose ) ; ?></td>
					</tr>
				<?php endforeach ; ?>
			</tbody>
		</table>
		<?php
	}
}

return new SUMO_PP_Messages_Settings() ;
