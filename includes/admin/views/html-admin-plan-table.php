<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<div class="sumo-pp-plan-options-inside">    
	<table class="plan_options">
		<thead>
			<tr class="price_type">
				<td><?php esc_html_e( 'Price Type: ', 'sumopaymentplans' ) ; ?></td>
				<td>
					<select name="_sumo_pp_price_type">
						<option value="percent" <?php selected( $price_type, 'percent' ) ; ?>><?php esc_html_e( 'Percentage', 'sumopaymentplans' ) ; ?></option>
						<option value="fixed-price" <?php selected( $price_type, 'fixed-price' ) ; ?>><?php esc_html_e( 'Fixed Price', 'sumopaymentplans' ) ; ?></option>
					</select>
				</td>
			</tr>
			<tr class="sync">
				<td><?php esc_html_e( 'Sync Payment: ', 'sumopaymentplans' ) ; ?></td>
				<td>
					<select name="_sumo_pp_sync">
						<option value="disabled" <?php selected( $sync, 'disabled' ) ; ?>><?php esc_html_e( 'Disabled', 'sumopaymentplans' ) ; ?></option>
						<option value="enabled" <?php selected( $sync, 'enabled' ) ; ?>><?php esc_html_e( 'Enabled', 'sumopaymentplans' ) ; ?></option>
					</select>
				</td>
			</tr>
			<tr class="sync_fields">
				<td><?php esc_html_e( 'Sync Start Date: ', 'sumopaymentplans' ) ; ?></td>
				<td>
					<input type="number" min="1" max="28" name="_sumo_pp_sync_start_date[day]" placeholder="<?php esc_attr_e( 'Select Day ', 'sumopaymentplans' ) ; ?>" value="<?php echo ! empty( $sync_start_date[ 'day' ] ) ? esc_attr( $sync_start_date[ 'day' ] ) : '' ; ?>">
					<select name="_sumo_pp_sync_start_date[month]">
						<option value=""><?php esc_html_e( 'Select Month ', 'sumopaymentplans' ) ; ?></option>
						<?php foreach ( _sumo_pp_get_month_options() as $duration => $label ) : ?>
							<option value="<?php echo esc_attr( $duration ) ; ?>" <?php selected(  ! empty( $sync_start_date[ 'month' ] ) ? $sync_start_date[ 'month' ] : '', $duration ) ; ?>><?php echo esc_html( $label ) ; ?></option>
						<?php endforeach ; ?>
					</select>
					<input type="number" min="2016" name="_sumo_pp_sync_start_date[year]" placeholder="<?php esc_html_e( 'Select Year ', 'sumopaymentplans' ) ; ?>" value="<?php echo ! empty( $sync_start_date[ 'year' ] ) ? esc_attr( $sync_start_date[ 'year' ] ) : '' ; ?>">
				</td>
			</tr>
			<tr class="sync_fields">
				<td><?php esc_html_e( 'Sync Payment Every ', 'sumopaymentplans' ) ; ?></td>
				<td>
					<select name="_sumo_pp_sync_month_duration">
						<?php foreach ( array( 1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12 ) as $duration => $label ) : ?>
							<option value="<?php echo esc_attr( $duration ) ; ?>" <?php selected( get_post_meta( $post->ID, '_sync_month_duration', true ), $duration ) ; ?>><?php echo esc_html( $label ) ; ?></option>
						<?php endforeach ; ?>
					</select>
					<?php esc_html_e( ' month(s)', 'sumopaymentplans' ) ; ?>
				</td>
			</tr>
			<tr class="pay_balance_type">
				<td><?php esc_html_e( 'Next Payment Date: ', 'sumopaymentplans' ) ; ?></td>
				<td>
					<select name="_sumo_pp_pay_balance_type">
						<option value="after" <?php selected( $pay_balance_type, 'after' ) ; ?>><?php esc_html_e( 'After Specific Number of Days', 'sumopaymentplans' ) ; ?></option>
						<option value="before" <?php selected( $pay_balance_type, 'before' ) ; ?>><?php esc_html_e( 'Before a Specific Date', 'sumopaymentplans' ) ; ?></option>
					</select>
				</td>
			</tr>
			<tr class="installments_type">
				<td><?php esc_html_e( 'Installments: ', 'sumopaymentplans' ) ; ?></td>
				<td>
					<select name="_sumo_pp_installments_type">
						<option value="variable" <?php selected( $installments_type, 'variable' ) ; ?>><?php esc_html_e( 'Variable', 'sumopaymentplans' ) ; ?></option>
						<option value="fixed" <?php selected( $installments_type, 'fixed' ) ; ?>><?php esc_html_e( 'Fixed', 'sumopaymentplans' ) ; ?></option>
					</select>
				</td>
			</tr>
			<tr class="installments_type_fields">
				<td><?php esc_html_e( 'No. of Installments: ', 'sumopaymentplans' ) ; ?></td>
				<td>
					<input class="fixed_no_of_installments" type="number" step="1" name="_sumo_pp_fixed_no_of_installments" value="<?php echo $fixed_no_of_installments ? esc_attr( $fixed_no_of_installments ) : '' ; ?>"/>
				</td>
			</tr>
			<tr class="installments_type_fields">
				<td><?php esc_html_e( 'Payment Amount: ', 'sumopaymentplans' ) ; ?></td>
				<td>
					<input class="fixed_payment_amount" type="number" step="0.01" name="_sumo_pp_fixed_payment_amount" value="<?php echo esc_attr( $fixed_payment_amount ) ; ?>"/><span><?php echo 'fixed-price' === $price_type ? esc_html( get_woocommerce_currency_symbol() ) : '%' ; ?></span>
				</td>
			</tr>
			<tr class="installments_type_fields">
				<td><?php esc_html_e( 'Interval: ', 'sumopaymentplans' ) ; ?></td>
				<td>
					<?php esc_html_e( 'After', 'sumopaymentplans' ) ; ?>
					<input class="fixed_duration_length" type="number" min="1" name="_sumo_pp_fixed_duration_length" value="<?php echo ! empty( $fixed_duration_length ) ? esc_attr( $fixed_duration_length ) : 1 ; ?>"/>
					<select class="fixed_duration_period" name="_sumo_pp_fixed_duration_period">
						<?php foreach ( _sumo_pp_get_duration_options() as $period => $label ) { ?>
							<option value="<?php echo esc_attr( $period ) ; ?>" <?php selected( $period === $fixed_duration_period, true ) ; ?>><?php echo esc_html( $label ) ; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
		</thead>
	</table>
	<input type="hidden" id="_sumo_pp_hidden_datas" data-currency_symbol="<?php echo esc_attr( get_woocommerce_currency_symbol() ) ; ?>"/>
	<table class="widefat striped payment_plans">
		<thead>
			<tr>
				<th><b><?php esc_html_e( 'Payment Amount', 'sumopaymentplans' ) ; ?></b></th>
				<th style="<?php echo 'enabled' === $sync ? 'display:none;' : '' ; ?>"><b><?php esc_html_e( 'Interval', 'sumopaymentplans' ) ; ?></b></th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody class="payment_schedules">
			<tr style="<?php echo 'after' === $pay_balance_type || 'enabled' === $sync ? '' : 'display:none;' ; ?>">
				<td>                           
					<input class="payment_amount" type="number" step="0.01" name="_sumo_pp_initial_payment" value="<?php echo esc_attr( $initial_payment ) ; ?>"/><span><?php echo 'fixed-price' === $price_type ? esc_html( get_woocommerce_currency_symbol() ) : '%' ; ?></span>
				</td>
				<td style="<?php echo 'after' === $pay_balance_type ? '' : 'display:none;' ; ?>"><?php esc_html_e( 'Initial Payment', 'sumopaymentplans' ) ; ?></td>
				<td><span style="<?php echo 'enabled' === $sync ? '' : 'display:none;' ; ?>"><?php esc_html_e( 'Initial Payment', 'sumopaymentplans' ) ; ?></span></td>
			</tr>
			<?php
			if ( is_array( $payment_schedules ) ) {
				foreach ( $payment_schedules as $plan_row_id => $defined_plan ) {
					$scheduled_payment = isset( $defined_plan[ 'scheduled_payment' ] ) ? $defined_plan[ 'scheduled_payment' ] : 0 ;
					$scheduled_date    = isset( $defined_plan[ 'scheduled_date' ] ) ? $defined_plan[ 'scheduled_date' ] : '' ;
					$scheduled_period  = isset( $defined_plan[ 'scheduled_period' ] ) ? $defined_plan[ 'scheduled_period' ] : '' ;

					$scheduled_duration_length = '' ;
					if ( isset( $defined_plan[ 'scheduled_duration_length' ] ) ) {
						$is_final_installment      = count( $payment_schedules ) - 1 === $plan_row_id ;
						$scheduled_duration_length = $is_final_installment && 0 === $defined_plan[ 'scheduled_duration_length' ] ? '' : $defined_plan[ 'scheduled_duration_length' ] ;
					}

					$total_payment_amount += wc_format_decimal( $scheduled_payment, wc_get_price_decimals() ) ;
					?>
					<tr>
						<td>                                   
							<input class="payment_amount" type="number" step="0.01" name="<?php echo esc_attr( '_sumo_pp_scheduled_payment[' . $plan_row_id . ']' ) ; ?>" value="<?php echo esc_attr( $scheduled_payment ) ; ?>"/><span><?php echo 'fixed-price' === $price_type ? esc_html( get_woocommerce_currency_symbol() ) : '%' ; ?></span>
						</td>
						<td style="<?php echo 'enabled' === $sync ? 'display:none;' : '' ; ?>">
							<div class="pay_balance_by_after" style="<?php echo 'after' === $pay_balance_type ? '' : 'display:none;' ; ?>">
								<?php esc_html_e( 'After', 'sumopaymentplans' ) ; ?>
								<input class="duration_length" type="number" min="1" name="<?php echo esc_attr( '_sumo_pp_scheduled_duration_length[' . $plan_row_id . ']' ) ; ?>" value="<?php echo esc_attr( $scheduled_duration_length ) ; ?>"/>
								<select class="duration_period" name="<?php echo esc_attr( '_sumo_pp_scheduled_period[' . $plan_row_id . ']' ) ; ?>">
									<?php foreach ( _sumo_pp_get_duration_options() as $period => $label ) { ?>
										<option value="<?php echo esc_attr( $period ) ; ?>" <?php selected( $period === $scheduled_period, true ) ; ?>><?php echo esc_html( $label ) ; ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="pay_balance_by_before" style="<?php echo 'before' === $pay_balance_type ? '' : 'display:none;' ; ?>">
								<input class="scheduled_date" type="text" name="<?php echo esc_attr( '_sumo_pp_scheduled_date[' . $plan_row_id . ']' ) ; ?>" value="<?php echo esc_html( $scheduled_date ) ; ?>"/>                                        
							</div>
						</td>
						<td><a href="#" class="remove_row button">X</a></td>
					</tr>
					<?php
				}
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<th><b><?php esc_html_e( 'Total Payment Amount: ', 'sumopaymentplans' ) ; ?></b><span class="total_payment_amount"><?php echo 'fixed-price' === $price_type ? esc_html( get_woocommerce_currency_symbol() . "$total_payment_amount" ) : esc_html( "$total_payment_amount%" ) ; ?></span></th>
				<th colspan="<?php echo 'enabled' === $sync ? '2' : '3' ; ?>"><a href="#" class="add button"><?php esc_html_e( 'Add Rule', 'sumopaymentplans' ) ; ?></a></th>
			</tr>
		</tfoot>
	</table>
</div>
