<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<tr>
	<td></td>
	<td>
		<?php
		switch ( $payment_type ) {
			case 'payment-plans':
				?>
				<p class="_sumo_pp_selected_plan">
					<select name="<?php echo 'order' === $product_type ? '_sumo_pp_selected_plan' : esc_attr( '_sumo_pp_selected_plan[' . $product_id . ']' ) ; ?>">
						<option value=""><?php esc_html_e( 'Select the payment plan', 'sumopaymentplans' ) ; ?></option>
						<?php
						if ( is_array( $selected_plans ) ) {
							foreach ( $selected_plans as $col => $plans ) {
								if ( is_array( $plans ) ) {
									foreach ( $plans as $row => $plan_id ) {
										?>
										<option value="<?php echo esc_attr( $plan_id ) ; ?>"><?php echo esc_html( get_the_title( $plan_id ) ) ; ?></option>
										<?php
									}
								} else {
									?>
									<option value="<?php echo esc_attr( $plans ) ; ?>"><?php echo esc_html( get_the_title( $plans ) ) ; ?></option>
									<?php
								}
							}
						}
						?>
					</select>
					<input type="hidden" name="_sumo_pp_product_type" value="<?php echo esc_attr( $product_type ) ; ?>"/>
				</p>
				<?php
				break ;
			case 'pay-in-deposit':
				?>
				<p class="_sumo_pp_deposit_amount">
					<input type="number" name="<?php echo 'order' === $product_type ? '_sumo_pp_deposit_amount' : esc_attr( '_sumo_pp_deposit_amount[' . $product_id . ']' ) ; ?>" value="" min="0" step="0.01" style="width: 50%;" placeholder="<?php esc_attr_e( 'Enter the deposit amount', 'sumopaymentplans' ) ; ?>">
					<input type="hidden" name="_sumo_pp_product_type" value="<?php echo esc_attr( $product_type ) ; ?>"/>
				</p>
				<?php
				break ;
		}
		?>
	</td>
</tr>
