<?php

/**
 * Order PaymentPlan Tab.
 * 
 * @class SUMO_PP_Order_PaymentPlan_Settings
 * @package Class
 */
class SUMO_PP_Order_PaymentPlan_Settings extends SUMO_PP_Abstract_Settings {

	/**
	 * SUMO_PP_Order_PaymentPlan_Settings constructor.
	 */
	public function __construct() {

		$this->id            = 'orderpp' ;
		$this->label         = __( 'Order PaymentPlan', 'sumopaymentplans' ) ;
		$this->custom_fields = array(
			'get_pay_balance_type',
			'get_limited_users',
			'get_include_products_selector',
			'get_exclude_products_selector',
			'get_include_categories_selector',
			'get_exclude_categories_selector',
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
			array(
				'name' => __( 'Order PaymentPlan Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'orderpp_settings',
			),
			array(
				'name'     => __( 'Enable Order PaymentPlan', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'enable_order_payment_plan',
				'newids'   => $this->prefix . 'enable_order_payment_plan',
				'type'     => 'checkbox',
				'std'      => 'no',
				'default'  => 'no',
				'desc'     => __( 'If enabled, a checkbox will be displayed on their checkout page using which customers can choose to pay for their orders using payment plans. Order PaymentPlan is not applicable if payment plans enabled products are in cart ', 'sumopaymentplans' ),
				'desc_tip' => true,
			),
			array(
				'name'    => __( 'Select Products/Categories', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'get_order_payment_plan_products_select_type',
				'newids'  => $this->prefix . 'get_order_payment_plan_products_select_type',
				'type'    => 'select',
				'std'     => 'all_products',
				'default' => 'all_products',
				'options' => array(
					'all_products'        => __( 'All Products', 'sumopaymentplans' ),
					'included_products'   => __( 'Include Products', 'sumopaymentplans' ),
					'excluded_products'   => __( 'Exclude Products', 'sumopaymentplans' ),
					'included_categories' => __( 'Include Categories', 'sumopaymentplans' ),
					'excluded_categories' => __( 'Exclude Categories', 'sumopaymentplans' ),
				),
				'std'     => 'all_products',
				'default' => 'all_products',
			),
			array(
				'type' => $this->get_custom_field_type( 'get_include_products_selector' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_exclude_products_selector' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_include_categories_selector' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_exclude_categories_selector' ),
			),
			array(
				'name'    => __( 'Payment Type', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'order_payment_type',
				'newids'  => $this->prefix . 'order_payment_type',
				'type'    => 'select',
				'options' => array(
					'pay-in-deposit' => __( 'Pay a Deposit Amount', 'sumopaymentplans' ),
					'payment-plans'  => __( 'Pay with Payment Plans', 'sumopaymentplans' ),
				),
				'std'     => 'pay-in-deposit',
				'default' => 'pay-in-deposit',
			),
			array(
				'name'    => __( 'Apply Global Level Settings', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'apply_global_settings_for_order_payment_plan',
				'newids'  => $this->prefix . 'apply_global_settings_for_order_payment_plan',
				'type'    => 'checkbox',
				'std'     => 'no',
				'default' => 'no',
			),
			array(
				'name'    => __( 'Force Deposit/Payment Plans', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'force_order_payment_plan',
				'newids'  => $this->prefix . 'force_order_payment_plan',
				'type'    => 'checkbox',
				'std'     => 'no',
				'default' => 'no',
			),
			array(
				'name'    => __( 'Deposit Type', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'order_payment_plan_deposit_type',
				'newids'  => $this->prefix . 'order_payment_plan_deposit_type',
				'type'    => 'select',
				'options' => array(
					'pre-defined'  => __( 'Predefined Deposit Amount', 'sumopaymentplans' ),
					'user-defined' => __( 'User Defined Deposit Amount', 'sumopaymentplans' ),
				),
				'std'     => 'pre-defined',
				'default' => 'pre-defined',
			),
			array(
				'name'    => __( 'Deposit Price Type', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'order_payment_plan_deposit_price_type',
				'newids'  => $this->prefix . 'order_payment_plan_deposit_price_type',
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
				'id'                => $this->prefix . 'fixed_order_payment_plan_deposit_price',
				'newids'            => $this->prefix . 'fixed_order_payment_plan_deposit_price',
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0.01',
					'step' => '0.01',
				),
			),
			array(
				'name'              => __( 'Deposit Percentage', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'fixed_order_payment_plan_deposit_percent',
				'newids'            => $this->prefix . 'fixed_order_payment_plan_deposit_percent',
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
				'name'    => __( 'User Defined Deposit Type', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'order_payment_plan_user_defined_deposit_type',
				'newids'  => $this->prefix . 'order_payment_plan_user_defined_deposit_type',
				'type'    => 'select',
				'options' => array(
					'percent-of-product-price' => __( 'Percentage of Product Price', 'sumopaymentplans' ),
					'fixed-price'              => __( 'Fixed Price', 'sumopaymentplans' ),
				),
				'std'     => 'percent-of-product-price',
				'default' => 'percent-of-product-price',
			),
			array(
				'name'              => __( 'Minimum Deposit (%)', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'min_order_payment_plan_deposit',
				'newids'            => $this->prefix . 'min_order_payment_plan_deposit',
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
				'id'                => $this->prefix . 'max_order_payment_plan_deposit',
				'newids'            => $this->prefix . 'max_order_payment_plan_deposit',
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
				'name'     => __( 'Minimum Deposit Price', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'min_order_payment_plan_user_defined_deposit_price',
				'newids'   => $this->prefix . 'min_order_payment_plan_user_defined_deposit_price',
				'type'     => 'text',
				'std'      => '',
				'default'  => '',
				'desc'     => '',
				'desc_tip' => true,
			),
			array(
				'name'     => __( 'Maximum Deposit Price', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'max_order_payment_plan_user_defined_deposit_price',
				'newids'   => $this->prefix . 'max_order_payment_plan_user_defined_deposit_price',
				'type'     => 'text',
				'std'      => '',
				'default'  => '',
				'desc'     => '',
				'desc_tip' => true,
			),
			array(
				'type' => $this->get_custom_field_type( 'get_pay_balance_type' ),
			),
			array(
				'name'    => __( 'Select Plans', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'selected_plans_for_order_payment_plan',
				'newids'  => $this->prefix . 'selected_plans_for_order_payment_plan',
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => _sumo_pp_get_payment_plan_names(),
				'std'     => array(),
				'default' => array(),
			),
			array(
				'name'              => __( 'Minimum Order Total to Display Order PaymentPlan', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'min_order_total_to_display_order_payment_plan',
				'newids'            => $this->prefix . 'min_order_total_to_display_order_payment_plan',
				'type'              => 'number',
				'std'               => '',
				'default'           => '',
				'custom_attributes' => array(
					'step' => '0.01',
				),
			),
			array(
				'name'              => __( 'Maximum Order Total to Display Order PaymentPlan', 'sumopaymentplans' ),
				'id'                => $this->prefix . 'max_order_total_to_display_order_payment_plan',
				'newids'            => $this->prefix . 'max_order_total_to_display_order_payment_plan',
				'type'              => 'number',
				'std'               => '',
				'default'           => '',
				'custom_attributes' => array(
					'step' => '0.01',
				),
			),
			array(
				'name'    => __( 'Charge Shipping Fee', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'order_payment_plan_charge_shipping_during',
				'newids'  => $this->prefix . 'order_payment_plan_charge_shipping_during',
				'type'    => 'select',
				'options' => array(
					'initial-payment' => __( 'During Initial Payment', 'sumopaymentplans' ),
					'final-payment'   => __( 'During Final Payment', 'sumopaymentplans' ),
				),
				'std'     => 'initial-payment',
				'default' => 'initial-payment',
			),
			array(
				'name'    => __( 'Order PaymentPlan Option in Checkout Page Label', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'order_payment_plan_label',
				'newids'  => $this->prefix . 'order_payment_plan_label',
				'type'    => 'text',
				'std'     => __( 'Order PaymentPlan', 'sumopaymentplans' ),
				'default' => __( 'Order PaymentPlan', 'sumopaymentplans' ),
			),
			array(
				'name'        => __( 'Order PaymentPlan Product Label', 'sumopaymentplans' ),
				'id'          => $this->prefix . 'order_payment_plan_product_label',
				'newids'      => $this->prefix . 'order_payment_plan_product_label',
				'type'        => 'text',
				'placeholder' => 'Order PaymentPlan',
			),
			array(
				'name'     => __( 'Show Order PaymentPlan Option for', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'show_order_payment_plan_for',
				'newids'   => $this->prefix . 'show_order_payment_plan_for',
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
				'type' => $this->get_custom_field_type( 'get_limited_users' ),
			),
			array(
				'name'     => __( 'Select User Role(s)', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'get_limited_userroles_of_order_payment_plan',
				'newids'   => $this->prefix . 'get_limited_userroles_of_order_payment_plan',
				'class'    => 'wc-enhanced-select',
				'type'     => 'multiselect',
				'options'  => _sumo_pp_get_user_roles( true ),
				'std'      => array(),
				'default'  => array(),
				'desc'     => '',
				'desc_tip' => true,
			),
			array(
				'name'    => __( 'Display Order PaymentPlan in Cart Page', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'order_payment_plan_in_cart',
				'newids'  => $this->prefix . 'order_payment_plan_in_cart',
				'type'    => 'checkbox',
				'std'     => 'no',
				'default' => 'no',
			),
			array(
				'name'    => __( 'Order PaymentPlan Position in Checkout Page', 'sumopaymentplans' ),
				'id'      => $this->prefix . 'order_payment_plan_form_position',
				'newids'  => $this->prefix . 'order_payment_plan_form_position',
				'type'    => 'select',
				'std'     => 'checkout_order_review',
				'default' => 'checkout_order_review',
				'options' => array(
					'checkout_order_review'           => 'Woocommerce Checkout Order Review',
					'checkout_after_customer_details' => 'Woocommerce Checkout After Customer Details',
					'before_checkout_form'            => 'Woocommerce Before Checkout Form',
					'checkout_before_order_review'    => 'Woocommerce Checkout Before Order Review',
					'review_order_before_payment'     => 'Woocommerce Review Order Before Payment',
				),
				'desc'    => __( 'Some themes do not support all the positions, if the positions is not supported then it might result in jquery conflict', 'sumopaymentplans' ),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'orderpp_settings' ),
			array(
				'name' => __( 'Troubleshoot Settings', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'troubleshoot_settings',
			),
			array(
				'name'     => __( 'Display Order PaymentPlan as', 'sumopaymentplans' ),
				'id'       => $this->prefix . 'order_payment_plan_display_mode',
				'newids'   => $this->prefix . 'order_payment_plan_display_mode',
				'type'     => 'select',
				'std'      => 'multiple',
				'default'  => 'multiple',
				'options'  => array(
					'single'   => __( 'Single Line Item', 'sumopaymentplans' ),
					'multiple' => __( 'Multiple Line Items', 'sumopaymentplans' ),
				),
				'desc'     => __( 'Select Multiple Line Items option if you face any issues in checkout  when using Order PaymentPlan', 'sumopaymentplans' ),
				'desc_tip' => true,
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'troubleshoot_settings' ),
				) ) ;
	}

	/**
	 * Save the custom options once.
	 */
	public function custom_types_add_options( $posted = null ) {
		// BKWD CMPT
		if ( false !== get_option( $this->prefix . 'version' ) ) {
			add_option( $this->prefix . 'order_payment_plan_display_mode', 'single' ) ;
		}

		add_option( $this->prefix . 'order_payment_plan_pay_balance_type', 'after' ) ;
		add_option( $this->prefix . 'order_payment_plan_pay_balance_after', '' ) ;
		add_option( $this->prefix . 'order_payment_plan_pay_balance_before', '' ) ;
		add_option( $this->prefix . 'get_limited_users_of_order_payment_plan', array() ) ;
	}

	/**
	 * Delete the custom options.
	 */
	public function custom_types_delete_options( $posted = null ) {
		delete_option( $this->prefix . 'order_payment_plan_pay_balance_type' ) ;
		delete_option( $this->prefix . 'order_payment_plan_pay_balance_after' ) ;
		delete_option( $this->prefix . 'order_payment_plan_pay_balance_before' ) ;
		delete_option( $this->prefix . 'get_limited_users_of_order_payment_plan' ) ;
		delete_option( $this->prefix . 'get_included_products_of_order_payment_plan' ) ;
		delete_option( $this->prefix . 'get_excluded_products_of_order_payment_plan' ) ;
		delete_option( $this->prefix . 'get_included_categories_of_order_payment_plan' ) ;
		delete_option( $this->prefix . 'get_excluded_categories_of_order_payment_plan' ) ;
		delete_option( $this->prefix . 'get_selected_products_of_order_payment_plan' ) ; // BKWD CMPT
	}

	/**
	 * Save custom settings.
	 */
	public function custom_types_save( $posted ) {

		if ( isset( $posted[ 'pay_balance_type' ] ) ) {
			update_option( $this->prefix . 'order_payment_plan_pay_balance_type', $posted[ 'pay_balance_type' ] ) ;
		}
		if ( isset( $posted[ 'pay_balance_after' ] ) ) {
			update_option( $this->prefix . 'order_payment_plan_pay_balance_after', $posted[ 'pay_balance_after' ] ) ;
		}
		if ( isset( $posted[ 'pay_balance_before' ] ) ) {
			update_option( $this->prefix . 'order_payment_plan_pay_balance_before', $posted[ 'pay_balance_before' ] ) ;
		}
		if ( isset( $posted[ 'get_limited_users' ] ) ) {
			update_option( $this->prefix . 'get_limited_users_of_order_payment_plan', ! is_array( $posted[ 'get_limited_users' ] ) ? array_filter( array_map( 'absint', explode( ',', $posted[ 'get_limited_users' ] ) ) ) : $posted[ 'get_limited_users' ]  ) ;
		}
		if ( isset( $posted[ 'get_included_products' ] ) ) {
			update_option( $this->prefix . 'get_included_products_of_order_payment_plan', ! is_array( $posted[ 'get_included_products' ] ) ? array_filter( array_map( 'absint', explode( ',', $posted[ 'get_included_products' ] ) ) ) : $posted[ 'get_included_products' ]  ) ;
		}
		if ( isset( $posted[ 'get_excluded_products' ] ) ) {
			update_option( $this->prefix . 'get_excluded_products_of_order_payment_plan', ! is_array( $posted[ 'get_excluded_products' ] ) ? array_filter( array_map( 'absint', explode( ',', $posted[ 'get_excluded_products' ] ) ) ) : $posted[ 'get_excluded_products' ]  ) ;
		}
		if ( isset( $posted[ 'get_included_categories' ] ) ) {
			update_option( $this->prefix . 'get_included_categories_of_order_payment_plan', ! is_array( $posted[ 'get_included_categories' ] ) ? array_filter( array_map( 'absint', explode( ',', $posted[ 'get_included_categories' ] ) ) ) : $posted[ 'get_included_categories' ]  ) ;
		}
		if ( isset( $posted[ 'get_excluded_categories' ] ) ) {
			update_option( $this->prefix . 'get_excluded_categories_of_order_payment_plan', ! is_array( $posted[ 'get_excluded_categories' ] ) ? array_filter( array_map( 'absint', explode( ',', $posted[ 'get_excluded_categories' ] ) ) ) : $posted[ 'get_excluded_categories' ]  ) ;
		}
	}

	/**
	 * Custom type field.
	 */
	public function get_include_products_selector() {

		_sumo_pp_wc_search_field( array(
			'class'       => 'wc-product-search',
			'id'          => $this->prefix . 'get_included_products',
			'name'        => 'get_included_products',
			'type'        => 'product',
			'action'      => 'woocommerce_json_search_products_and_variations',
			'title'       => __( 'Include Product(s) ', 'sumopaymentplans' ),
			'placeholder' => __( 'Search for a product&hellip;', 'sumopaymentplans' ),
			'options'     => get_option( "{$this->prefix}get_included_products_of_order_payment_plan", array() ),
		) ) ;
	}

	/**
	 * Custom type field.
	 */
	public function get_exclude_products_selector() {

		_sumo_pp_wc_search_field( array(
			'class'       => 'wc-product-search',
			'id'          => $this->prefix . 'get_excluded_products',
			'name'        => 'get_excluded_products',
			'type'        => 'product',
			'action'      => 'woocommerce_json_search_products_and_variations',
			'title'       => __( 'Exclude Product(s)', 'sumopaymentplans' ),
			'placeholder' => __( 'Search for a product&hellip;', 'sumopaymentplans' ),
			'options'     => get_option( "{$this->prefix}get_excluded_products_of_order_payment_plan", array() ),
		) ) ;
	}

	/**
	 * Custom type field.
	 */
	public function get_include_categories_selector() {
		?>
		<tr>
			<th>
				<?php esc_html_e( 'Select Categorie(s) to Include', 'sumopaymentplans' ) ; ?>
			</th>
			<td>                
				<select name="get_included_categories[]" class="wc-enhanced-select" id="_sumo_pp_get_included_categories" multiple="multiple" style="min-width:350px;">
					<?php
					$option_value = get_option( "{$this->prefix}get_included_categories_of_order_payment_plan", array() ) ;

					foreach ( _sumo_pp_get_product_categories()as $key => $val ) {
						?>
						<option value="<?php echo esc_attr( $key ) ; ?>"
						<?php
						if ( is_array( $option_value ) ) {
							selected( in_array( ( string ) $key, $option_value, true ), true ) ;
						} else {
							selected( $option_value, ( string ) $key ) ;
						}
						?>
								>
									<?php echo esc_html( $val ) ; ?>
						</option>
						<?php
					}
					?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_exclude_categories_selector() {
		?>
		<tr>
			<th>
				<?php esc_html_e( 'Select Categorie(s) to Exclude', 'sumopaymentplans' ) ; ?>
			</th>
			<td>                
				<select name="get_excluded_categories[]" class="wc-enhanced-select" id="_sumo_pp_get_excluded_categories" multiple="multiple" style="min-width:350px;">
					<?php
					$option_value = get_option( "{$this->prefix}get_excluded_categories_of_order_payment_plan", array() ) ;

					foreach ( _sumo_pp_get_product_categories() as $key => $val ) {
						?>
						<option value="<?php echo esc_attr( $key ) ; ?>"
						<?php
						if ( is_array( $option_value ) ) {
							selected( in_array( ( string ) $key, $option_value, true ), true ) ;
						} else {
							selected( $option_value, ( string ) $key ) ;
						}
						?>
								>
									<?php echo esc_html( $val ) ; ?>
						</option>
						<?php
					}
					?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_pay_balance_type() {
		?>
		<tr class="pay-balance-wrapper">
			<th>
				<?php esc_html_e( 'Deposit Balance Payment Due Date', 'sumopaymentplans' ) ; ?>
			</th>
			<td>
				<select id="_sumo_pp_order_payment_plan_pay_balance_type" name="pay_balance_type" style="width:95px;">
					<option value="after" <?php selected( 'after' === get_option( $this->prefix . 'order_payment_plan_pay_balance_type', 'after' ), true ) ; ?>><?php esc_html_e( 'After', 'sumopaymentplans' ) ; ?></option>
					<option value="before" <?php selected( 'before' === get_option( $this->prefix . 'order_payment_plan_pay_balance_type', 'after' ), true ) ; ?>><?php esc_html_e( 'Before', 'sumopaymentplans' ) ; ?></option>
				</select>
				<input id="_sumo_pp_order_payment_plan_pay_balance_after" name="pay_balance_after" type="number" value="<?php echo esc_attr( get_option( $this->prefix . 'order_payment_plan_pay_balance_after' ) ) ; ?>" style="width:150px;"/>
				<input id="_sumo_pp_order_payment_plan_pay_balance_before" name="pay_balance_before" type="text" placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumopaymentplans' ) ; ?>" value="<?php echo esc_attr( get_option( $this->prefix . 'order_payment_plan_pay_balance_before', '' ) ) ; ?>" style="width:150px;"/>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_limited_users() {

		_sumo_pp_wc_search_field( array(
			'class'       => 'wc-customer-search',
			'id'          => $this->prefix . 'get_limited_users_of_order_payment_plan',
			'name'        => 'get_limited_users',
			'type'        => 'customer',
			'title'       => __( 'Select User(s)', 'sumopaymentplans' ),
			'placeholder' => __( 'Search for a user&hellip;', 'sumopaymentplans' ),
			'options'     => ( array ) get_option( $this->prefix . 'get_limited_users_of_order_payment_plan', array() ),
		) ) ;
	}
}

return new SUMO_PP_Order_PaymentPlan_Settings() ;
