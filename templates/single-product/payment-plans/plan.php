<?php
/**
 * Output the plan details.
 * 
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/single-product/payment-plans/plan.php.
 */
defined( 'ABSPATH' ) || exit;
?>
<td>
	<input type="radio" value="<?php echo esc_attr( $plan_props[ 'plan_id' ] ); ?>" name="_sumo_pp_chosen_payment_plan" <?php checked( $is_default, true, true ); ?>/>
	<?php if ( 'yes' === $display_add_to_cart_plan_link ) { ?>
		<a href="<?php echo esc_url_raw( add_query_arg( array( '_sumo_pp_payment_type' => 'payment-plans', '_sumo_pp_chosen_payment_plan' => $plan_props[ 'plan_id' ] ), _sumo_pp_product_add_to_cart_url( $product ) ) ); ?>"><?php echo esc_html( $plan_props[ 'plan_name' ] ); ?></a>
	<?php } else { ?>
		<strong><?php echo esc_html( $plan_props[ 'plan_name' ] ); ?></strong>
	<?php } ?>

	<p class="_sumo_pp_initial_payable">
		<?php
		if ( 'fixed-price' === $plan_props[ 'plan_price_type' ] ) {
			$initial_payable = floatval( $plan_props[ 'initial_payment' ] );
		} else {
			$initial_payable = ( $product_props[ 'product_price' ] * floatval( $plan_props[ 'initial_payment' ] ) ) / 100;
		}
		?>
		<?php
		/* translators: 1: initial payable amount */
		printf( wp_kses_post( __( '<strong>Initial Payable:</strong> %s<br>', 'sumopaymentplans' ) ), wp_kses_post( _sumo_pp_format_product_price( $product, $initial_payable ) ) );
		?>
	</p>    

	<p class="_sumo_pp_total_payable">
		<?php
		/* translators: 1: initial payable amount */
		printf( wp_kses_post( __( '<strong>Total Payable:</strong> %s<br>', 'sumopaymentplans' ) ), wp_kses_post( _sumo_pp_format_product_price( $product, $total_payable ) ) );
		?>
	</p>
	<?php
	do_action( 'sumopaymentplans_after_total_payable_html', $product_props, $plan_props );

	if ( ! empty( $plan_props[ 'plan_description' ] ) ) {
		?>
		<p class="_sumo_pp_plan_description">
			<?php echo wp_kses_post( $plan_props[ 'plan_description' ] ); ?>
		</p>
		<?php
	}

	if ( 'after' === $plan_props[ 'pay_balance_type' ] && 'after_admin_approval' === $activate_payments ) {
		//Do not display plan information since scheduled date is not available for this plan.
		echo '';
	} elseif ( ! empty( $plan_props[ 'payment_schedules' ] ) ) {
		?>
		<div class="_sumo_pp_plan_view_more">
			<p><a class="view-plan-more" href="#"><?php esc_html_e( 'View more', 'sumopaymentplans' ); ?></a></p>
				<?php
				_sumo_pp_get_template( 'single-product/payment-plans/view-more.php', array(
					'product_props' => $product_props,
					'plan_props'    => $plan_props,
					'product'       => $product,
				) );
				?>
		</div>
		<?php
	}
	?>
</td>
