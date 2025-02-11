<?php
/**
 * Output more plan details.
 * 
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/single-product/payment-plans/view-more.php.
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="_sumo_pp_modal">
	<div class="_sumo_pp_modal-wrapper">
		<div class="_sumo_pp_modal-close">
			<img class="close-sumo-pp-modal" src="<?php echo esc_url( SUMO_PP_PLUGIN_URL ) . '/assets/images/close.png'; ?>"/>
		</div>    
		<table class="_sumo_pp_modal-info">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Payment Amount', 'sumopaymentplans' ); ?></th>
					<th><?php esc_html_e( 'Payment Date', 'sumopaymentplans' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( apply_filters( 'sumopaymentplans_show_initial_payment_in_view_more', true, $plan_props ) ) {
					?>
					<tr>
						<td>
							<?php
							if ( 'fixed-price' === $plan_props[ 'plan_price_type' ] ) {
								$price = floatval( $plan_props[ 'initial_payment' ] );
							} else {
								$price = ( $product_props[ 'product_price' ] * floatval( $plan_props[ 'initial_payment' ] ) ) / 100;
							}

							echo wp_kses_post( _sumo_pp_format_product_price( $product, $price ) );
							?>
						</td>
						<td>
							<?php echo esc_html( _sumo_pp_get_date_to_display( _sumo_pp_get_timestamp() ) ); ?>
						</td>
					</tr>
					<?php
				}

				foreach ( $plan_props[ 'payment_schedules' ] as $payment_schedule ) {
					if ( ! isset( $payment_schedule[ 'scheduled_payment' ] ) ) {
						continue;
					}
					?>
					<tr>
						<td>
							<?php
							if ( 'fixed-price' === $plan_props[ 'plan_price_type' ] ) {
								$price = floatval( $payment_schedule[ 'scheduled_payment' ] );
							} else {
								$price = ( $product_props[ 'product_price' ] * floatval( $payment_schedule[ 'scheduled_payment' ] ) ) / 100;
							}

							echo wp_kses_post( _sumo_pp_format_product_price( $product, $price ) );
							?>
						</td>
						<td>
							<?php echo $payment_schedule[ 'scheduled_date' ] ? esc_html( _sumo_pp_get_date_to_display( _sumo_pp_get_date( $payment_schedule[ 'scheduled_date' ] ) ) ) : '--'; ?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>
</div>
