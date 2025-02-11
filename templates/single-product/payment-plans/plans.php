<?php
/**
 * Output List of Plans.
 * 
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/single-product/payment-plans/plans.php.
 */
defined( 'ABSPATH' ) || exit ;
?>
<table class="_sumo_pp_payment_plans">
	<?php
	foreach ( $plans as $col => $plan ) {
		$plan = ( array ) $plan ;
		?>
		<tr>
			<?php
			foreach ( $plan as $row => $plan_id ) {
				$plan_props = _sumo_pp()->plan->get_props( $plan_id ) ;

				_sumo_pp_get_template( 'single-product/payment-plans/plan.php', array(
					'product_props'                  => $product_props,
					'plan_props'                     => $plan_props,
					'product'                        => $product,
					'is_default'                     => 0 === $col && 0 === $row,
					'activate_payments'              => $activate_payments,
					'display_add_to_cart_plan_link'  => $display_add_to_cart_plan_link,
					'added_to_cart_plan_redirect_to' => $added_to_cart_plan_redirect_to,
					'total_payable'                  => _sumo_pp()->product->get_prop( 'total_payable', array( 'props' => $product_props, 'plan_props' => $plan_props ) ),
				) ) ;
			}
			?>
		</tr>
		<?php
	}
	?>
</table>
