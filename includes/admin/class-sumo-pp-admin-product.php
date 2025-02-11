<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Product Admin. 
 * 
 * @class SUMO_PP_Admin_Product
 * @package Class
 */
class SUMO_PP_Admin_Product {

	protected static $payment_fields = array(
		'enable_sumopaymentplans'        => 'checkbox',
		'payment_type'                   => 'select',
		'available_dates_from'           => 'datepicker-from',
		'available_dates_to'             => 'datepicker-to',
		'apply_global_settings'          => 'checkbox',
		'force_deposit'                  => 'checkbox',
		'default_selected_type'          => 'select',
		'deposit_type'                   => 'select',
		'deposit_price_type'             => 'select',
		'pay_balance_type'               => 'select',
		'pay_balance_after'              => 'number',
		'pay_balance_before'             => 'datepicker',
		'pay_balance_before_booked_date' => 'number',
		'set_expired_deposit_payment_as' => 'select',
		'fixed_deposit_price'            => 'price',
		'fixed_deposit_percent'          => 'text',
		'user_defined_deposit_type'      => 'select',
		'min_user_defined_deposit_price' => 'price',
		'max_user_defined_deposit_price' => 'price',
		'min_deposit'                    => 'number',
		'max_deposit'                    => 'number',
		'selected_plans'                 => 'select',
	);

	/**
	 * Init Payment Plans Product Settings.
	 */
	public static function init() {
		add_action( 'woocommerce_product_options_general_product_data', __CLASS__ . '::get_product_settings' );
		add_action( 'woocommerce_product_after_variable_attributes', __CLASS__ . '::get_variation_product_settings', 10, 3 );
		add_action( 'woocommerce_process_product_meta', __CLASS__ . '::save_product_data' );
		add_action( 'woocommerce_save_product_variation', __CLASS__ . '::save_variation_data', 10, 2 );
	}

	public static function get_payment_fields() {
		return self::$payment_fields;
	}

	/**
	 * Get payment plans product setting fields.
	 */
	public static function get_product_settings() {
		global $post;

		$product = wc_get_product( $post );

		if ( ! $product || in_array( $product->get_type(), array( 'variable' ) ) ) {
			return;
		}

		woocommerce_wp_checkbox( array(
			'label'    => __( 'Enable SUMO Payment Plans', 'sumopaymentplans' ),
			'id'       => '_sumo_pp_enable_sumopaymentplans',
			'desc_tip' => __( 'Enabling this option allows you to configure the product to accept product booking by paying a deposit amount / purchase the product by choosing from the available payment plans', 'sumopaymentplans' ),
		) );
		woocommerce_wp_select( array(
			'label'         => __( 'Payment Type', 'sumopaymentplans' ),
			'id'            => '_sumo_pp_payment_type',
			'wrapper_class' => '_sumo_pp_fields',
			'options'       => array(
				'pay-in-deposit' => __( 'Pay a Deposit Amount', 'sumopaymentplans' ),
				'payment-plans'  => __( 'Pay with Payment Plans', 'sumopaymentplans' ),
			),
			'description'   => '<a href="#" class="_sumo_pp_schedule_availability">' . __( 'Schedule', 'sumopaymentplans' ) . '</a>',
		) );

		$from = get_post_meta( $post->ID, '_sumo_pp_available_dates_from', true );
		$to   = get_post_meta( $post->ID, '_sumo_pp_available_dates_to', true );
		?>
		<p class="form-field _sumo_pp_availability_dates_field _sumo_pp_fields">
			<input type="text" class="short" name="_sumo_pp_available_dates_from" id="_sumo_pp_available_dates_from" value="<?php echo '' === $from ? '' : esc_attr( date_i18n( 'Y-m-d', _sumo_pp_get_timestamp( $from ) ) ); ?>" placeholder="<?php esc_html_e( 'From&hellip;YYYY-MM-DD', 'sumopaymentplans' ); ?>" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
			<input type="text" class="short" name="_sumo_pp_available_dates_to" id="_sumo_pp_available_dates_to" value="<?php echo '' === $to ? '' : esc_attr( date_i18n( 'Y-m-d', _sumo_pp_get_timestamp( $to ) ) ); ?>" placeholder="<?php esc_html_e( 'To&hellip;YYYY-MM-DD', 'sumopaymentplans' ); ?>" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
			<a href="#" class="description _sumo_pp_cancel_availability"><?php esc_html_e( 'Cancel', 'sumopaymentplans' ); ?></a><?php echo wc_help_tip( __( 'The payment plan/deposit options will be given to users starting at 00:00:00 of "From" date and ending at 23:59:59 of "To" date.', 'sumopaymentplans' ) ); ?>
		</p>
		<?php
		woocommerce_wp_checkbox( array(
			'label'         => __( 'Apply Global Level Settings', 'sumopaymentplans' ),
			'id'            => '_sumo_pp_apply_global_settings',
			'wrapper_class' => '_sumo_pp_fields',
			'desc_tip'      => __( 'When enabled, the settings for SUMO Payment Plans will apply from global level', 'sumopaymentplans' ),
		) );
		woocommerce_wp_checkbox( array(
			'label'         => __( 'Force Deposit/Payment Plans', 'sumopaymentplans' ),
			'id'            => '_sumo_pp_force_deposit',
			'wrapper_class' => '_sumo_pp_fields',
			'desc_tip'      => __( 'When enabled, the user will be forced to pay a deposit amount', 'sumopaymentplans' ),
		) );
		woocommerce_wp_select( array(
			'label'         => __( 'Default Selection', 'sumopaymentplans' ),
			'id'            => '_sumo_pp_default_selected_type',
			'wrapper_class' => '_sumo_pp_fields',
			'options'       => array(
				'full-pay'    => __( 'Pay in Full', 'sumopaymentplans' ),
				'deposit-pay' => __( 'Pay with Deposit/Payment Plans', 'sumopaymentplans' ),
			),
		) );
		woocommerce_wp_select( array(
			'label'         => __( 'Deposit Type', 'sumopaymentplans' ),
			'id'            => '_sumo_pp_deposit_type',
			'wrapper_class' => '_sumo_pp_fields',
			'options'       => array(
				'pre-defined'  => __( 'Predefined Deposit Amount', 'sumopaymentplans' ),
				'user-defined' => __( 'User Defined Deposit Amount', 'sumopaymentplans' ),
			),
		) );
		woocommerce_wp_select( array(
			'label'         => __( 'Deposit Price Type', 'sumopaymentplans' ),
			'id'            => '_sumo_pp_deposit_price_type',
			'wrapper_class' => '_sumo_pp_fields',
			'options'       => array(
				'fixed-price'              => __( 'Fixed Price', 'sumopaymentplans' ),
				'percent-of-product-price' => __( 'Percentage of Product Price', 'sumopaymentplans' ),
			),
		) );
		woocommerce_wp_text_input( array(
			'label'         => __( 'Deposit Amount', 'sumopaymentplans' ),
			'id'            => '_sumo_pp_fixed_deposit_price',
			'wrapper_class' => '_sumo_pp_fields',
			'style'         => 'width:20%;',
			'data_type'     => 'price',
		) );
		woocommerce_wp_text_input( array(
			'label'             => __( 'Deposit Percentage', 'sumopaymentplans' ),
			'id'                => '_sumo_pp_fixed_deposit_percent',
			'wrapper_class'     => '_sumo_pp_fields',
			'style'             => 'width:20%;',
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => '0.01',
			),
		) );
		woocommerce_wp_select( array(
			'label'         => __( 'User Defined Deposit Type', 'sumopaymentplans' ),
			'id'            => '_sumo_pp_user_defined_deposit_type',
			'wrapper_class' => '_sumo_pp_fields',
			'options'       => array(
				'percent-of-product-price' => __( 'Percentage of Product Price', 'sumopaymentplans' ),
				'fixed-price'              => __( 'Fixed Price', 'sumopaymentplans' ),
			),
		) );
		woocommerce_wp_text_input( array(
			'label'         => __( 'Minimum Deposit Price', 'sumopaymentplans' ),
			'id'            => '_sumo_pp_min_user_defined_deposit_price',
			'wrapper_class' => '_sumo_pp_fields',
			'style'         => 'width:20%;',
			'data_type'     => 'price',
		) );
		woocommerce_wp_text_input( array(
			'label'         => __( 'Maximum Deposit Price', 'sumopaymentplans' ),
			'id'            => '_sumo_pp_max_user_defined_deposit_price',
			'wrapper_class' => '_sumo_pp_fields',
			'style'         => 'width:20%;',
			'data_type'     => 'price',
		) );
		woocommerce_wp_text_input( array(
			'label'             => __( 'Minimum Deposit(%)', 'sumopaymentplans' ),
			'id'                => '_sumo_pp_min_deposit',
			'wrapper_class'     => '_sumo_pp_fields',
			'style'             => 'width:20%;',
			'type'              => 'number',
			'custom_attributes' => array(
				'min'  => '0.01',
				'max'  => '99.99',
				'step' => '0.01',
			),
		) );
		woocommerce_wp_text_input( array(
			'label'             => __( 'Maximum Deposit(%)', 'sumopaymentplans' ),
			'id'                => '_sumo_pp_max_deposit',
			'wrapper_class'     => '_sumo_pp_fields',
			'style'             => 'width:20%;',
			'type'              => 'number',
			'custom_attributes' => array(
				'min'  => '0.01',
				'max'  => '99.99',
				'step' => '0.01',
			),
		) );
		?>

		<p class="form-field _sumo_pp_pay_balance_type_field _sumo_pp_fields">
			<label for="_sumo_pp_pay_balance_type"><?php esc_html_e( 'Deposit Balance Payment Due Date', 'sumopaymentplans' ); ?></label>
			<select id="_sumo_pp_pay_balance_type" name="_sumo_pp_pay_balance_type">
				<option value="after" <?php selected( true, 'after' === get_post_meta( $post->ID, '_sumo_pp_pay_balance_type', true ) ); ?>><?php esc_html_e( 'After', 'sumopaymentplans' ); ?></option> 
				<option value="before" <?php selected( true, 'before' === get_post_meta( $post->ID, '_sumo_pp_pay_balance_type', true ) ); ?>><?php esc_html_e( 'Before', 'sumopaymentplans' ); ?></option>
			</select>
			<span>
				<input type="number" id="_sumo_pp_pay_balance_after" name="_sumo_pp_pay_balance_after" value="<?php echo esc_html( '' === get_post_meta( $post->ID, '_sumo_pp_balance_payment_due', true ) ? get_post_meta( $post->ID, '_sumo_pp_pay_balance_after', true ) : get_post_meta( $post->ID, '_sumo_pp_balance_payment_due', true )  ); ?>" style="width:20%;">
				<span class="description"><?php esc_html_e( 'day(s) from the date of deposit payment', 'sumopaymentplans' ); ?></span>
			</span>
			<span>
				<input type="text" placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumopaymentplans' ); ?>" id="_sumo_pp_pay_balance_before" name="_sumo_pp_pay_balance_before" value="<?php echo esc_html( get_post_meta( $post->ID, '_sumo_pp_pay_balance_before', true ) ); ?>" style="width:20%;">
			</span>
			<?php if ( class_exists( 'SUMO_Bookings' ) ) { ?>
				<span>
					<input type="number" min="0" id="_sumo_pp_pay_balance_before_booked_date" name="_sumo_pp_pay_balance_before_booked_date" value="<?php echo esc_html( get_post_meta( $post->ID, '_sumo_pp_pay_balance_before_booked_date', true ) ); ?>" style="width:20%;display: none;">
					<span class="description"><?php esc_html_e( 'day(s) of booking start date', 'sumopaymentplans' ); ?></span>
				</span>
			<?php } ?>
		</p>
		<p class="form-field _sumo_pp_set_expired_deposit_payment_as_field _sumo_pp_fields">
			<label for="_sumo_pp_set_expired_deposit_payment_as"><?php esc_html_e( 'After Balance Payment Due Date', 'sumopaymentplans' ); ?></label>
			<select id="_sumo_pp_set_expired_deposit_payment_as" name="_sumo_pp_set_expired_deposit_payment_as">
				<option value="normal" <?php selected( true, 'normal' === get_post_meta( $post->ID, '_sumo_pp_set_expired_deposit_payment_as', true ) ); ?>><?php esc_html_e( 'Disable SUMO Payment Plans', 'sumopaymentplans' ); ?></option> 
				<option value="out-of-stock" <?php selected( true, 'out-of-stock' === get_post_meta( $post->ID, '_sumo_pp_set_expired_deposit_payment_as', true ) ); ?>><?php esc_html_e( 'Set Product as Out of Stock', 'sumopaymentplans' ); ?></option>
			</select>
		</p>
		<p class="form-field _sumo_pp_selected_plans_field _sumo_pp_fields">
			<label for="_sumo_pp_selected_plans"><?php esc_html_e( 'Select Plans', 'sumopaymentplans' ); ?></label>
			<span class="_sumo_pp_add_plans">
				<span class="woocommerce-help-tip" data-tip="<?php esc_html_e( 'Select the layout as per your theme preference', 'sumopaymentplans' ); ?>"></span>
				<a href="#" class="button" id="_sumo_pp_add_col_1_plan"><?php esc_html_e( 'Add Row for Column 1', 'sumopaymentplans' ); ?></a>
				<a href="#" class="button" id="_sumo_pp_add_col_2_plan"><?php esc_html_e( 'Add Row for Column 2', 'sumopaymentplans' ); ?></a>
				<span class="spinner"></span>
			</span>
		</p>		
		<?php
		$selected_plans     = get_post_meta( $post->ID, '_sumo_pp_selected_plans', true );
		$bkw_selected_plans = is_array( $selected_plans ) && ! empty( $selected_plans ) ? $selected_plans : array( 'col_1' => array(), 'col_2' => array() );
		$selected_plans     = $bkw_selected_plans;
		$selected_plans     = wp_parse_args( $selected_plans, array( 'col_1' => array(), 'col_2' => array() ) );

		if ( ! isset( $bkw_selected_plans[ 'col_1' ] ) ) {
			$selected_plans = array( 'col_1' => array(), 'col_2' => array() );

			foreach ( $bkw_selected_plans as $row_id => $selected_plan ) {
				$selected_plans[ 'col_1' ][] = ! empty( $selected_plan ) ? ( array ) $selected_plan : array();
			}
		}

		foreach ( $selected_plans as $column_id => $selected_datas ) {
			$inline_style = 'col_1' === $column_id ? 'float:left;margin-left:3px;' : 'float:right;margin-right:3px;';
			$inline_style .= 'width:49%;clear:none;';
			?>
			<table class="widefat wc_input_table wc_gateways sortable sumo-pp-selected-plans _sumo_pp_selected_col_<?php echo esc_attr( $column_id ); ?>_plans _sumo_pp_selected_plans _sumo_pp_fields" style="<?php echo esc_attr( $inline_style ); ?>">
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
	}

	/**
	 * Get payment plans variation product setting fields.
	 *
	 * @param int $loop
	 * @param mixed $variation_data
	 * @param object $variation The Variation post ID
	 */
	public static function get_variation_product_settings( $loop, $variation_data, $variation ) {

		woocommerce_wp_checkbox( array(
			'label'    => __( 'Enable SUMO Payment Plans', 'sumopaymentplans' ),
			'id'       => "_sumo_pp_enable_sumopaymentplans{$loop}",
			'name'     => "_sumo_pp_enable_sumopaymentplans[{$loop}]",
			'value'    => get_post_meta( $variation->ID, '_sumo_pp_enable_sumopaymentplans', true ),
			'desc_tip' => __( 'Enabling this option allows you to configure the product to accept product booking by paying a deposit amount / purchase the product by choosing from the available payment plans', 'sumopaymentplans' ),
		) );
		woocommerce_wp_select( array(
			'label'         => __( 'Payment Type', 'sumopaymentplans' ),
			'id'            => "_sumo_pp_payment_type{$loop}",
			'name'          => "_sumo_pp_payment_type[{$loop}]",
			'wrapper_class' => "_sumo_pp_fields{$loop}",
			'options'       => array(
				'pay-in-deposit' => __( 'Pay a Deposit Amount', 'sumopaymentplans' ),
				'payment-plans'  => __( 'Pay with Payment Plans', 'sumopaymentplans' ),
			),
			'value'         => get_post_meta( $variation->ID, '_sumo_pp_payment_type', true ),
			'description'   => '<a href="#" class="_sumo_pp_schedule_availability' . esc_attr( $loop ) . '">' . __( 'Schedule', 'sumopaymentplans' ) . '</a>',
		) );

		$from               = get_post_meta( $variation->ID, '_sumo_pp_available_dates_from', true );
		$to                 = get_post_meta( $variation->ID, '_sumo_pp_available_dates_to', true );
		?>
		<p class="form-field _sumo_pp_availability_dates_field <?php echo esc_attr( "_sumo_pp_fields{$loop}" ) . ' ' . esc_attr( "_sumo_pp_availability_dates_field{$loop}" ); ?>">
			<input type="text" class="short" name="<?php echo esc_attr( "_sumo_pp_available_dates_from[{$loop}]" ); ?>" id="<?php echo esc_attr( "_sumo_pp_available_dates_from{$loop}" ); ?>" value="<?php echo '' === $from ? '' : esc_attr( date_i18n( 'Y-m-d', _sumo_pp_get_timestamp( $from ) ) ); ?>" placeholder="<?php esc_html_e( 'From&hellip;YYYY-MM-DD', 'sumopaymentplans' ); ?>" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
			<input type="text" class="short" name="<?php echo esc_attr( "_sumo_pp_available_dates_to[{$loop}]" ); ?>" id="<?php echo esc_attr( "_sumo_pp_available_dates_to{$loop}" ); ?>" value="<?php echo '' === $to ? '' : esc_attr( date_i18n( 'Y-m-d', _sumo_pp_get_timestamp( $to ) ) ); ?>" placeholder="<?php esc_html_e( 'To&hellip;YYYY-MM-DD', 'sumopaymentplans' ); ?>" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
			<a href="#" class="description <?php echo esc_attr( "_sumo_pp_cancel_availability{$loop}" ); ?>"><?php esc_html_e( 'Cancel', 'sumopaymentplans' ); ?></a><?php echo wc_help_tip( __( 'The payment plan/deposit options will be given to users starting at 00:00:00 of "From" date and ending at 23:59:59 of "To" date.', 'sumopaymentplans' ) ); ?>
		</p>
		<?php
		woocommerce_wp_checkbox( array(
			'label'         => __( 'Apply Global Level Settings', 'sumopaymentplans' ),
			'id'            => "_sumo_pp_apply_global_settings{$loop}",
			'name'          => "_sumo_pp_apply_global_settings[{$loop}]",
			'wrapper_class' => "_sumo_pp_fields{$loop}",
			'value'         => get_post_meta( $variation->ID, '_sumo_pp_apply_global_settings', true ),
			'desc_tip'      => __( 'When enabled, the settings for SUMO Payment Plans will apply from global level', 'sumopaymentplans' ),
		) );
		woocommerce_wp_checkbox( array(
			'label'         => __( 'Force Deposit/Payment Plans', 'sumopaymentplans' ),
			'id'            => "_sumo_pp_force_deposit{$loop}",
			'name'          => "_sumo_pp_force_deposit[{$loop}]",
			'wrapper_class' => "_sumo_pp_fields{$loop}",
			'value'         => get_post_meta( $variation->ID, '_sumo_pp_force_deposit', true ),
			'desc_tip'      => __( 'When enabled, the user will be forced to pay a deposit amount', 'sumopaymentplans' ),
		) );
		woocommerce_wp_select( array(
			'label'         => __( 'Default Selection', 'sumopaymentplans' ),
			'id'            => "_sumo_pp_default_selected_type{$loop}",
			'name'          => "_sumo_pp_default_selected_type[{$loop}]",
			'wrapper_class' => "_sumo_pp_fields{$loop}",
			'options'       => array(
				'full-pay'    => __( 'Pay in Full', 'sumopaymentplans' ),
				'deposit-pay' => __( 'Pay with Deposit/Payment Plans', 'sumopaymentplans' ),
			),
			'value'         => get_post_meta( $variation->ID, '_sumo_pp_default_selected_type', true ),
		) );
		woocommerce_wp_select( array(
			'label'         => __( 'Deposit Type', 'sumopaymentplans' ),
			'id'            => "_sumo_pp_deposit_type{$loop}",
			'name'          => "_sumo_pp_deposit_type[{$loop}]",
			'wrapper_class' => "_sumo_pp_fields{$loop}",
			'options'       => array(
				'pre-defined'  => __( 'Predefined Deposit Amount', 'sumopaymentplans' ),
				'user-defined' => __( 'User Defined Deposit Amount', 'sumopaymentplans' ),
			),
			'value'         => get_post_meta( $variation->ID, '_sumo_pp_deposit_type', true ),
		) );
		woocommerce_wp_select( array(
			'label'         => __( 'Deposit Price Type', 'sumopaymentplans' ),
			'id'            => "_sumo_pp_deposit_price_type{$loop}",
			'name'          => "_sumo_pp_deposit_price_type[{$loop}]",
			'wrapper_class' => "_sumo_pp_fields{$loop}",
			'options'       => array(
				'fixed-price'              => __( 'Fixed Price', 'sumopaymentplans' ),
				'percent-of-product-price' => __( 'Percentage of Product Price', 'sumopaymentplans' ),
			),
			'value'         => get_post_meta( $variation->ID, '_sumo_pp_deposit_price_type', true ),
		) );
		woocommerce_wp_text_input( array(
			'label'         => __( 'Deposit Amount', 'sumopaymentplans' ),
			'id'            => "_sumo_pp_fixed_deposit_price{$loop}",
			'name'          => "_sumo_pp_fixed_deposit_price[{$loop}]",
			'wrapper_class' => "_sumo_pp_fields{$loop}",
			'style'         => 'width:20%;',
			'data_type'     => 'price',
			'value'         => get_post_meta( $variation->ID, '_sumo_pp_fixed_deposit_price', true ),
		) );
		woocommerce_wp_text_input( array(
			'label'             => __( 'Deposit Percentage', 'sumopaymentplans' ),
			'id'                => "_sumo_pp_fixed_deposit_percent{$loop}",
			'name'              => "_sumo_pp_fixed_deposit_percent[{$loop}]",
			'wrapper_class'     => "_sumo_pp_fields{$loop}",
			'style'             => 'width:20%;',
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => '0.01',
			),
			'value'             => get_post_meta( $variation->ID, '_sumo_pp_fixed_deposit_percent', true ),
		) );
		woocommerce_wp_select( array(
			'label'         => __( 'User Defined Deposit Type', 'sumopaymentplans' ),
			'id'            => "_sumo_pp_user_defined_deposit_type{$loop}",
			'name'          => "_sumo_pp_user_defined_deposit_type[{$loop}]",
			'wrapper_class' => "_sumo_pp_fields{$loop}",
			'options'       => array(
				'fixed-price'              => __( 'Fixed Price', 'sumopaymentplans' ),
				'percent-of-product-price' => __( 'Percentage of Product Price', 'sumopaymentplans' ),
			),
			'value'         => get_post_meta( $variation->ID, '_sumo_pp_user_defined_deposit_type', true ),
		) );
		woocommerce_wp_text_input( array(
			'label'         => __( 'Minimum Deposit Price', 'sumopaymentplans' ),
			'id'            => "_sumo_pp_min_user_defined_deposit_price{$loop}",
			'name'          => "_sumo_pp_min_user_defined_deposit_price[{$loop}]",
			'wrapper_class' => "_sumo_pp_fields{$loop}",
			'style'         => 'width:20%;',
			'data_type'     => 'price',
			'value'         => get_post_meta( $variation->ID, '_sumo_pp_min_user_defined_deposit_price', true ),
		) );
		woocommerce_wp_text_input( array(
			'label'         => __( 'Maximum Deposit Price', 'sumopaymentplans' ),
			'id'            => "_sumo_pp_max_user_defined_deposit_price{$loop}",
			'name'          => "_sumo_pp_max_user_defined_deposit_price[{$loop}]",
			'wrapper_class' => "_sumo_pp_fields{$loop}",
			'style'         => 'width:20%;',
			'data_type'     => 'price',
			'value'         => get_post_meta( $variation->ID, '_sumo_pp_max_user_defined_deposit_price', true ),
		) );
		woocommerce_wp_text_input( array(
			'label'             => __( 'Minimum Deposit(%)', 'sumopaymentplans' ),
			'id'                => "_sumo_pp_min_deposit{$loop}",
			'name'              => "_sumo_pp_min_deposit[{$loop}]",
			'wrapper_class'     => "_sumo_pp_fields{$loop}",
			'style'             => 'width:20%;',
			'type'              => 'number',
			'custom_attributes' => array(
				'min'  => '0.01',
				'max'  => '99.99',
				'step' => '0.01',
			),
			'value'             => get_post_meta( $variation->ID, '_sumo_pp_min_deposit', true ),
		) );
		woocommerce_wp_text_input( array(
			'label'             => __( 'Maximum Deposit(%)', 'sumopaymentplans' ),
			'id'                => "_sumo_pp_max_deposit{$loop}",
			'name'              => "_sumo_pp_max_deposit[{$loop}]",
			'wrapper_class'     => "_sumo_pp_fields{$loop}",
			'style'             => 'width:20%;',
			'type'              => 'number',
			'custom_attributes' => array(
				'min'  => '0.01',
				'max'  => '99.99',
				'step' => '0.01',
			),
			'value'             => get_post_meta( $variation->ID, '_sumo_pp_max_deposit', true ),
		) );
		?>

		<p class="form-field _sumo_pp_pay_balance_type_field <?php echo esc_attr( "_sumo_pp_fields{$loop}" ); ?>">
			<label for="_sumo_pp_pay_balance_type"><?php esc_html_e( 'Deposit Balance Payment Due Date', 'sumopaymentplans' ); ?></label>
			<select id="<?php echo esc_attr( "_sumo_pp_pay_balance_type{$loop}" ); ?>" name="<?php echo esc_attr( "_sumo_pp_pay_balance_type[{$loop}]" ); ?>">
				<option value="after" <?php selected( true, 'after' === get_post_meta( $variation->ID, '_sumo_pp_pay_balance_type', true ) ); ?>><?php esc_html_e( 'After', 'sumopaymentplans' ); ?></option> 
				<option value="before" <?php selected( true, 'before' === get_post_meta( $variation->ID, '_sumo_pp_pay_balance_type', true ) ); ?>><?php esc_html_e( 'Before', 'sumopaymentplans' ); ?></option>
			</select>
			<span>
				<input type="number" id="<?php echo esc_attr( "_sumo_pp_pay_balance_after{$loop}" ); ?>" name="<?php echo esc_attr( "_sumo_pp_pay_balance_after[{$loop}]" ); ?>" value="<?php echo esc_attr( '' === get_post_meta( $variation->ID, '_sumo_pp_balance_payment_due', true ) ? get_post_meta( $variation->ID, '_sumo_pp_pay_balance_after', true ) : get_post_meta( $variation->ID, '_sumo_pp_balance_payment_due', true )  ); ?>" style="width:20%;">
				<span class="description"><?php esc_html_e( 'day(s) from the date of deposit payment', 'sumopaymentplans' ); ?></span>
			</span>
			<span>
				<input type="text" placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumopaymentplans' ); ?>" id="<?php echo esc_attr( "_sumo_pp_pay_balance_before{$loop}" ); ?>" name="<?php echo esc_attr( "_sumo_pp_pay_balance_before[{$loop}]" ); ?>" value="<?php echo esc_attr( get_post_meta( $variation->ID, '_sumo_pp_pay_balance_before', true ) ); ?>" style="width:20%;">
			</span>
		</p>
		<p class="form-field _sumo_pp_set_expired_deposit_payment_as_field <?php echo esc_attr( "_sumo_pp_fields{$loop}" ); ?>">
			<label for="_sumo_pp_set_expired_deposit_payment_as"><?php esc_html_e( 'After Balance Payment Due Date', 'sumopaymentplans' ); ?></label>
			<select id="<?php echo esc_attr( "_sumo_pp_set_expired_deposit_payment_as{$loop}" ); ?>" name="<?php echo esc_attr( "_sumo_pp_set_expired_deposit_payment_as[{$loop}]" ); ?>">
				<option value="normal" <?php selected( true, 'normal' === get_post_meta( $variation->ID, '_sumo_pp_set_expired_deposit_payment_as', true ) ); ?>><?php esc_html_e( 'Disable SUMO Payment Plans', 'sumopaymentplans' ); ?></option> 
				<option value="out-of-stock" <?php selected( true, 'out-of-stock' === get_post_meta( $variation->ID, '_sumo_pp_set_expired_deposit_payment_as', true ) ); ?>><?php esc_html_e( 'Set Product as Out of Stock', 'sumopaymentplans' ); ?></option>
			</select>
		</p>
		<p class="form-field _sumo_pp_selected_plans_field <?php echo esc_attr( "_sumo_pp_fields{$loop}" ); ?>">
			<label for="_sumo_pp_selected_plans"><?php esc_html_e( 'Select Plans', 'sumopaymentplans' ); ?></label>
			<span class="<?php echo esc_attr( "_sumo_pp_add_plans{$loop}" ); ?>">
				<span class="woocommerce-help-tip" data-tip="<?php esc_html_e( 'Select the layout as per your theme preference', 'sumopaymentplans' ); ?>"></span>
				<a href="#" class="button" id="<?php echo esc_attr( "_sumo_pp_add_col_1_plan{$loop}" ); ?>"><?php esc_html_e( 'Add Row for Column 1', 'sumopaymentplans' ); ?></a>
				<a href="#" class="button" id="<?php echo esc_attr( "_sumo_pp_add_col_2_plan{$loop}" ); ?>"><?php esc_html_e( 'Add Row for Column 2', 'sumopaymentplans' ); ?></a>
				<span class="spinner"></span>
			</span>
		</p>
		<?php
		$selected_plans     = get_post_meta( $variation->ID, '_sumo_pp_selected_plans', true );
		$bkw_selected_plans = is_array( $selected_plans ) && ! empty( $selected_plans ) ? $selected_plans : array( 'col_1' => array(), 'col_2' => array() );
		$selected_plans     = $bkw_selected_plans;
		$selected_plans     = wp_parse_args( $selected_plans, array( 'col_1' => array(), 'col_2' => array() ) );

		if ( ! isset( $bkw_selected_plans[ 'col_1' ] ) ) {
			$selected_plans = array( 'col_1' => array(), 'col_2' => array() );

			foreach ( $bkw_selected_plans as $row_id => $selected_plan ) {
				$selected_plans[ 'col_1' ][] = ! empty( $selected_plan ) ? ( array ) $selected_plan : array();
			}
		}

		foreach ( $selected_plans as $column_id => $selected_datas ) {
			$inline_style = 'col_1' === $column_id ? 'float:left;margin-left:3px;' : 'float:right;margin-right:3px;';
			$inline_style .= 'width:49%;clear:none;padding:0px;';
			?>
			<table class="widefat wc_input_table wc_gateways sortable sumo-pp-selected-plans <?php echo esc_attr( "_sumo_pp_selected_col_{$column_id}_plans{$loop} _sumo_pp_selected_plans{$loop} _sumo_pp_fields{$loop}" ); ?>" style="<?php echo esc_attr( $inline_style ); ?>">
				<tbody class="selected_plans">
					<?php
					if ( is_array( $selected_datas ) && ! empty( $selected_datas ) ) {
						foreach ( $selected_datas as $row_id => $selected_data ) {
							echo '<tr><td class="sort" width="1%"></td><td>';
							_sumo_pp_wc_search_field( array(
								'class'       => 'wc-product-search',
								'action'      => '_sumo_pp_json_search_payment_plans',
								'id'          => "selected_{$column_id}_payment_plan_{$row_id}{$loop}",
								'name'        => "_sumo_pp_selected_plans[{$loop}][{$column_id}][{$row_id}]",
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
	}

	/**
	 * Save payment plans product data.
	 *
	 * @param int $product_id The Product post ID
	 */
	public static function save_product_data( $product_id ) {
		if ( empty( $_POST[ 'woocommerce_meta_nonce' ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ 'woocommerce_meta_nonce' ] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		global $sitepress;
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && is_object( $sitepress ) ) {
			$trid         = $sitepress->get_element_trid( $product_id );
			$translations = $sitepress->get_element_translations( $trid );

			if ( ! empty( $translations ) ) {
				foreach ( $translations as $translation ) {
					self::save_meta( $translation->element_id, '', $_POST );
				}
			} else {
				self::save_meta( $product_id, '', $_POST );
			}
		} else {
			self::save_meta( $product_id, '', $_POST );
		}
	}

	/**
	 * Save payment plans variation product data.
	 *
	 * @param int $variation_id The Variation post ID
	 * @param int $loop
	 */
	public static function save_variation_data( $variation_id, $loop ) {
		check_ajax_referer( 'save-variations', 'security' );

		global $sitepress;
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && is_object( $sitepress ) ) {
			$trid         = $sitepress->get_element_trid( $variation_id );
			$translations = $sitepress->get_element_translations( $trid );

			if ( ! empty( $translations ) ) {
				foreach ( $translations as $translation ) {
					self::save_meta( $translation->element_id, $loop, $_POST );
				}
			} else {
				self::save_meta( $variation_id, $loop, $_POST );
			}
		} else {
			self::save_meta( $variation_id, $loop, $_POST );
		}
	}

	/**
	 * Save payment plans product meta's.
	 *
	 * @param int $product_id The Product post ID
	 * @param int $loop The Variation loop
	 */
	public static function save_meta( $product_id, $loop = '', $props = array() ) {
		foreach ( self::$payment_fields as $field_name => $type ) {
			$meta_key         = SUMO_PP_PLUGIN_PREFIX . $field_name;
			$posted_meta_data = isset( $props[ "$meta_key" ] ) ? $props[ "$meta_key" ] : '';

			if ( is_numeric( $loop ) ) {
				if ( 'checkbox' === $type ) {
					delete_post_meta( $product_id, "$meta_key" );
				}

				if ( isset( $posted_meta_data[ $loop ] ) ) {
					if ( 'price' === $type ) {
						$posted_meta_data[ $loop ] = wc_format_decimal( $posted_meta_data[ $loop ] );
					}
					if ( 'selected_plans' === $field_name && is_array( $posted_meta_data[ $loop ] ) ) {
						foreach ( array( 'col_1', 'col_2' ) as $column_id ) {
							$posted_meta_data[ $loop ][ $column_id ] = ! empty( $posted_meta_data[ $loop ][ $column_id ] ) && is_array( $posted_meta_data[ $loop ][ $column_id ] ) ? array_map( 'implode', ( array_values( $posted_meta_data[ $loop ][ $column_id ] ) ) ) : array();
						}
					}

					update_post_meta( $product_id, "$meta_key", wc_clean( $posted_meta_data[ $loop ] ) );

					//backward compatible
					if ( $posted_meta_data[ $loop ] && 'pay_balance_after' === $field_name ) {
						delete_post_meta( $product_id, '_sumo_pp_balance_payment_due' );
					}
				}
			} else {
				if ( 'price' === $type && ! is_array( $posted_meta_data ) ) {
					$posted_meta_data = wc_format_decimal( $posted_meta_data );
				}

				if ( 'datepicker-from' === $type && '' !== $posted_meta_data && ! is_array( $posted_meta_data ) ) {
					$posted_meta_data .= ' 00:00:00';
				}

				if ( 'datepicker-to' === $type && '' !== $posted_meta_data && ! is_array( $posted_meta_data ) ) {
					$posted_meta_data .= ' 23:59:59';
				}

				if ( 'selected_plans' === $field_name && is_array( $posted_meta_data ) ) {
					foreach ( array( 'col_1', 'col_2' ) as $column_id ) {
						if ( ! empty( $posted_meta_data[ $column_id ] ) && is_array( $posted_meta_data[ $column_id ] ) ) {
							$plans = array_values( $posted_meta_data[ $column_id ] );

							if ( ! empty( $plans[ 0 ] ) ) {
								if ( is_array( $plans[ 0 ] ) ) {
									$posted_meta_data[ $column_id ] = array_map( 'implode', $plans );
								} else {
									$posted_meta_data[ $column_id ] = $plans;
								}
							}
						}
					}
				}
				update_post_meta( $product_id, "$meta_key", wc_clean( $posted_meta_data ) );

				//backward compatible
				if ( $posted_meta_data && 'pay_balance_after' === $field_name ) {
					delete_post_meta( $product_id, '_sumo_pp_balance_payment_due' );
				}
			}
		}
	}
}

SUMO_PP_Admin_Product::init();
