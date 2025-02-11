<?php
/**
 * Output Order PaymentPlan form.
 * 
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/checkout/order-paymentplan/form.php.
 */
defined( 'ABSPATH' ) || exit ;
?>
<table class="shop_table _sumo_pp_orderpp_fields">
	<tr>
		<td colspan="2">
			<?php if ( 'yes' === $option_props[ 'force_deposit' ] ) { ?>
				<input type="checkbox" id="_sumo_pp_enable_orderpp" value="1" checked="checked" readonly="readonly" onclick="return false;"/>
			<?php } else { ?>
				<input type="checkbox" id="_sumo_pp_enable_orderpp" value="1" <?php checked( $enabled, true, true ) ; ?>/>
			<?php } ?>
			<label for="subscribe"><?php echo wp_kses_post( $option_props[ 'labels' ][ 'enable' ] ) ; ?></label>
		</td>
	</tr>   
	<tr>
		<?php if ( 'pay-in-deposit' === $option_props[ 'payment_type' ] ) { ?>
			<td>                        
				<label for="deposit_amount"><?php echo wp_kses_post( $option_props[ 'labels' ][ 'deposit_amount' ] ) ; ?></label>
				<input type="hidden" value="pay-in-deposit" id="_sumo_pp_payment_type"/>
			</td>
			<td id="_sumo_pp_amount_to_choose">
				<?php if ( 'user-defined' === $option_props[ 'deposit_type' ] ) { ?>
					<?php
					if ( $max_deposit_price ) {
						/* translators: 1: min deposit price 2: max deposit price */
						printf( esc_html__( 'Enter your Deposit Amount between %1$s and %2$s', 'sumopaymentplans' ), wp_kses_post( wc_price( $min_deposit_price ) ), wp_kses_post( wc_price( $max_deposit_price ) ) ) ;
						?>
						<input type="number" min="<?php echo floatval( $min_deposit_price ) ; ?>" max="<?php echo floatval( $max_deposit_price ) ; ?>" step="0.01" class="input-text" id="_sumo_pp_deposited_amount" value="<?php echo floatval( $session_props[ 'down_payment' ] ) ; ?>"/>
						<?php
					} else {
						/* translators: 1: min deposit price */
						printf( esc_html__( 'Enter a deposit amount not less than %s', 'sumopaymentplans' ), wp_kses_post( wc_price( $min_deposit_price ) ) ) ;
						?>
						<input type="number" min="<?php echo floatval( $min_deposit_price ) ; ?>" step="0.01" class="input-text" id="_sumo_pp_deposited_amount" value="<?php echo floatval( $session_props[ 'down_payment' ] ) ; ?>"/>
						<?php
					}
				} else {
					?>
					<?php echo wp_kses_post( wc_price( $fixed_deposit_price ) ) ; ?>
					<input type="hidden" value="<?php echo esc_attr( $fixed_deposit_price ) ; ?>" id="_sumo_pp_deposited_amount"/>
				<?php } ?>
			</td>
		<?php } else { ?>
			<td>                       
				<label for="payment_plans"><?php echo wp_kses_post( $option_props[ 'labels' ][ 'payment_plans' ] ) ; ?></label>
				<input type="hidden" value="payment-plans" id="_sumo_pp_payment_type"/>
			</td>                    
			<td id="_sumo_pp_plans_to_choose">
				<?php
				foreach ( $option_props[ 'selected_plans' ] as $col => $plans ) {
					if ( is_array( $plans ) ) {
						foreach ( $plans as $row => $plan_id ) {
							$plan_props = _sumo_pp()->plan->get_props( $plan_id ) ;
							?>
							<p>
								<input type="radio" value="<?php echo esc_attr( $plan_props[ 'plan_id' ] ) ; ?>" id="_sumo_pp_chosen_payment_plan" name="_sumo_pp_chosen_payment_plan" 
								<?php
								if ( isset( $session_props[ 'payment_plan_props' ][ 'plan_id' ] ) ) {
									checked( $plan_props[ 'plan_id' ] === $session_props[ 'payment_plan_props' ][ 'plan_id' ], true, true ) ;
								} else {
									checked( 0 === $row, true, true ) ;
								}
								?>
									   />
								<strong><?php echo wp_kses_post( $plan_props[ 'plan_name' ] ) ; ?></strong><br>
								<?php
								if ( ! empty( $plan_props[ 'plan_description' ] ) ) {
									echo wp_kses_post( $plan_props[ 'plan_description' ] ) ;
								}
								?>
							</p>  
							<?php
						}
					} else {
						$plan_props = _sumo_pp()->plan->get_props( $plans ) ;
						?>
						<p>
							<input type="radio" value="<?php echo esc_attr( $plan_props[ 'plan_id' ] ) ; ?>" id="_sumo_pp_chosen_payment_plan" name="_sumo_pp_chosen_payment_plan" 
								   <?php
									if ( isset( $session_props[ 'payment_plan_props' ][ 'plan_id' ] ) ) {
										checked( $plan_props[ 'plan_id' ] === $session_props[ 'payment_plan_props' ][ 'plan_id' ], true, true ) ;
									} else {
										checked( 0 === $col, true, true ) ;
									}
									?>
								   />
							<strong><?php echo wp_kses_post( $plan_props[ 'plan_name' ] ) ; ?></strong><br>
							<?php
							if ( ! empty( $plan_props[ 'plan_description' ] ) ) {
								echo wp_kses_post( $plan_props[ 'plan_description' ] ) ;
							}
							?>
						</p>  
						<?php
					}
				}
				?>
			</td>
		<?php } ?>
	</tr>
</table>
