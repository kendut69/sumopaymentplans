<?php

/**
 * Advanced Tab.
 * 
 * @class SUMO_PP_Advanced_Settings
 * @package Class
 */
class SUMO_PP_Advanced_Settings extends SUMO_PP_Abstract_Settings {

	/**
	 * SUMO_PP_Advanced_Settings constructor.
	 */
	public function __construct() {

		$this->id       = 'advanced';
		$this->label    = __( 'Advanced', 'sumopaymentplans' );
		$this->settings = $this->get_settings();
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
			array(
				'name' => __( 'Advanced Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'advanced_settings',
			),
			array(
				'name'     => __( 'Disable WooCommerce Emails for Payment Plan Orders', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'disabled_wc_order_emails',
				'newids'   => $this->prefix . 'disabled_wc_order_emails',
				'class'    => 'wc-enhanced-select',
				'type'     => 'multiselect',
				'options'  => array(
					'new'        => __( 'New order', 'sumopaymentplans' ),
					'processing' => __( 'Processing order', 'sumopaymentplans' ),
					'completed'  => __( 'Completed order', 'sumopaymentplans' ),
				),
				'std'      => array(),
				'default'  => array(),
				'desc'     => __( 'This option will be applicable only for balance payable orders', 'sumopaymentplans' ),
				'desc_tip' => true,
			),
			array(
				'name'     => __( 'Calculate Price for Deposits/Payment Plans based on', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'calc_deposits_r_payment_plans_price_based_on',
				'newids'   => $this->prefix . 'calc_deposits_r_payment_plans_price_based_on',
				'type'     => 'select',
				'options'  => array(
					'regular-price' => __( 'Regular Price', 'sumopaymentplans' ),
					'sale-price'    => __( 'Sale Price', 'sumopaymentplans' ),
				),
				'std'      => 'sale-price',
				'default'  => 'sale-price',
				'desc_tip' => true,
			),
			array(
				'name'     => __( 'Balance Payment Activation for Deposits/Payment Plans will be decided', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'activate_payments',
				'newids'   => $this->prefix . 'activate_payments',
				'type'     => 'select',
				'std'      => 'auto',
				'default'  => 'auto',
				'options'  => array(
					'auto'                 => __( 'Automatically', 'sumopaymentplans' ),
					'after_admin_approval' => __( 'After Admin Approval', 'sumopaymentplans' ),
				),
				'desc_tip' => __( 'If "After Admin Approval" option is chosen, admin needs to activate Payment Plans/Deposits in edit payment page.', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Cancel Payments after Balance Payment Due Date', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'cancel_payments_after_balance_payment_due_date',
				'newids'  => $this->prefix . 'cancel_payments_after_balance_payment_due_date',
				'type'    => 'select',
				'std'     => 'after_admin_approval',
				'default' => 'after_admin_approval',
				'options' => array(
					'auto'                 => __( 'Automatically', 'sumopaymentplans' ),
					'after_admin_approval' => __( 'After Admin Approval', 'sumopaymentplans' ),
				),
			),
			array(
				'name'              => __( 'Payment Identification Number Prefix', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'payment_number_prefix',
				'newids'            => $this->prefix . 'payment_number_prefix',
				'type'              => 'text',
				'std'               => '',
				'default'           => '',
				'custom_attributes' => array(
					'maxlength' => 30,
				),
				'desc'              => __( 'Prefix can be alpha-numeric', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Hide Product Price in Single Product Page when User Selects the Payment Plans', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'hide_product_price_for_payment_plans',
				'newids'  => $this->prefix . 'hide_product_price_for_payment_plans',
				'type'    => 'checkbox',
				'std'     => 'no',
				'default' => 'no',
			),
			array(
				'name'    => __( 'Timezone', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'show_timezone_in',
				'newids'  => $this->prefix . 'show_timezone_in',
				'type'    => 'select',
				'std'     => 'wordpress',
				'default' => 'wordpress',
				'options' => array(
					'default'   => __( 'UTC+0', 'sumopaymentplans' ),
					'wordpress' => __( 'WordPress timezone', 'sumopaymentplans' ),
				),
				'desc'    => __( 'Note: Only for display purpose.', 'sumopaymentplans' ),
			),
			array(
				'name'     => __( 'Display Time', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'show_time_in_frontend',
				'newids'   => $this->prefix . 'show_time_in_frontend',
				'type'     => 'select',
				'std'      => 'enable',
				'default'  => 'enable',
				'options'  => array(
					'disable' => __( 'Disable', 'sumopaymentplans' ),
					'enable'  => __( 'Enable', 'sumopaymentplans' ),
				),
				'desc_tip' => __( 'If enabled, time will be displayed in frontend.', 'sumopaymentplans' ),
			),
			array(
				'name'    => __( 'Grant Permission to Download Files of Downloadable Products after', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'grant_permission_to_download_after',
				'newids'  => $this->prefix . 'grant_permission_to_download_after',
				'type'    => 'select',
				'std'     => 'initial-payment',
				'default' => 'initial-payment',
				'options' => array(
					'initial-payment' => __( 'Initial Payment', 'sumopaymentplans' ),
					'final-payment'   => __( 'Final Payment', 'sumopaymentplans' ),
				),
			),
			array(
				'name'    => __( 'Show Payment Gateways if the Payment Plan Order Amount is 0', 'sumopaymentplans' ),
				'desc'    => __( 'If enabled, payment gateways will be displayed in checkout page even  if the order amount is 0 when the cart contains payment product. In case of automatic payments, subscriber doesnot need to visit the site during payment renewals if this option is enabled', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'show_payment_gateways_when_order_amt_zero',
				'newids'  => $this->prefix . 'show_payment_gateways_when_order_amt_zero',
				'type'    => 'checkbox',
				'std'     => 'no',
				'default' => 'no',
			),
			array(
				'name'    => __( 'Hide Specific Payment Gateways', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'gateways_to_hide_when_order_amt_zero',
				'newids'  => $this->prefix . 'gateways_to_hide_when_order_amt_zero',
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'std'     => array(),
				'default' => array(),
				'options' => _sumo_pp_get_active_payment_gateways(),
			),
			array(
				'name'    => __( 'Custom CSS', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'custom_css',
				'newids'  => $this->prefix . 'custom_css',
				'type'    => 'textarea',
				'css'     => 'height:200px;',
				'std'     => '',
				'default' => '',
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'advanced_settings' ),
			array(
				'name' => __( 'My Account Page Endpoints', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'my_account_endpoints_settings',
			),
			array(
				'name'              => __( 'My Payments', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'my_account_payments_endpoint',
				'newids'            => $this->prefix . 'my_account_payments_endpoint',
				'type'              => 'text',
				'std'               => 'sumo-pp-my-payments',
				'default'           => 'sumo-pp-my-payments',
				'custom_attributes' => array(
					'required' => 'required',
				),
			),
			array(
				'name'              => __( 'View Payment', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'my_account_view_payment_endpoint',
				'newids'            => $this->prefix . 'my_account_view_payment_endpoint',
				'type'              => 'text',
				'std'               => 'sumo-pp-view-payment',
				'default'           => 'sumo-pp-view-payment',
				'custom_attributes' => array(
					'required' => 'required',
				),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'my_account_endpoints_settings' ),
			array(
				'name' => __( 'Payment Plan Specific Date Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'payment_plan_specific_date_settings',
			),
			array(
				'name'    => __( 'Payment Plan Behavior After Initial Payment Date', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'payment_plan_behaviour_after_initial_payment_date',
				'newids'  => $this->prefix . 'payment_plan_behaviour_after_initial_payment_date',
				'type'    => 'select',
				'std'     => 'sum-up-with-previous-installments',
				'default' => 'sum-up-with-previous-installments',
				'options' => array(
					'sum-up-with-previous-installments' => __( 'Add All the Previous Installment Amount and Charge with Current Installment Amount', 'sumopaymentplans' ),
					'hide-payment-plan'                 => __( 'Hide the Payment Plan', 'sumopaymentplans' ),
				),
			),
			array(
				'name'    => __( 'If No Valid Payment Plans are Available to Display', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'when_no_payment_plans_are_available',
				'newids'  => $this->prefix . 'when_no_payment_plans_are_available',
				'type'    => 'select',
				'std'     => 'disable-payment-plan',
				'default' => 'disable-payment-plan',
				'options' => array(
					'disable-payment-plan' => __( 'Disable Payment Plan for that Product', 'sumopaymentplans' ),
					'set-as-out-of-stock'  => __( 'Make the Product as Out of Stock', 'sumopaymentplans' ),
				),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'payment_plan_specific_date_settings' ),
			array(
				'name' => __( 'Experimental Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'experimental_settings',
			),
			array(
				'name'     => __( 'Display Payment Plans as Hyperlink in Single Product Page', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'payment_plan_add_to_cart_via_href',
				'newids'   => $this->prefix . 'payment_plan_add_to_cart_via_href',
				'type'     => 'checkbox',
				'std'      => 'no',
				'default'  => 'no',
				'desc'     => __( 'If enabled, payment plans will be displayed as hyperlink which when clicked, the payment plan will be directly added to cart', 'sumopaymentplans' ),
				'desc_tip' => true,
			),
			array(
				'name'     => __( 'When the Hyperlink is Clicked', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'after_hyperlink_clicked_redirect_to',
				'newids'   => $this->prefix . 'after_hyperlink_clicked_redirect_to',
				'type'     => 'select',
				'options'  => array(
					'product'  => __( 'Stay on Product Page', 'sumopaymentplans' ),
					'cart'     => __( 'Redirect to Cart Page', 'sumopaymentplans' ),
					'checkout' => __( 'Redirect to Checkout Page', 'sumopaymentplans' ),
				),
				'std'      => 'product',
				'default'  => 'product',
				'desc_tip' => true,
			),
			array(
				'name'     => __( 'Use Deposit/Payment Plan Variation Form Template.', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'variation_form_template',
				'newids'   => $this->prefix . 'variation_form_template',
				'type'     => 'select',
				'options'  => array(
					'from-woocommerce' => __( 'From WooCommerce', 'sumopaymentplans' ),
					'from-plugin'      => __( 'From Plugin', 'sumopaymentplans' ),
				),
				'std'      => 'from-woocommerce',
				'default'  => 'from-woocommerce',
				'desc'     => __( 'If the Deposit/Payment Plan variations or not displaying in Single Product page, then try using "From Plugin" option.', 'sumopaymentplans' ),
				'desc_tip' => true,
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'experimental_settings' ),
				) );
	}
}

return new SUMO_PP_Advanced_Settings();
