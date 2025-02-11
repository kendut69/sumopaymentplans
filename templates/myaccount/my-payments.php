<?php
/**
 * My Payments.
 *
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/myaccount/my-payments.php.
 */
defined( 'ABSPATH' ) || exit ;
?>
<div class="woocommerce_account_sumo_payments">
	<?php
	do_action( 'woocommerce_before_account_sumo_payments', $customer_payments ) ;

	if ( $has_payment ) :
		?>
		<table class="my_account_sumo_payments my_account_orders account-orders-table woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
			<thead>
				<tr>
					<th class="sumo-payment-id woocommerce-orders-table__header woocommerce-orders-table__header-payment-id"><span class="nobr"><?php esc_html_e( 'Payment Number', 'sumopaymentplans' ) ; ?></span></th>
					<th class="sumo-payment-product woocommerce-orders-table__header woocommerce-orders-table__header-payment-product"><span class="nobr"><?php esc_html_e( 'Product Title', 'sumopaymentplans' ) ; ?></span></th>
					<th class="sumo-payment-plan woocommerce-orders-table__header woocommerce-orders-table__header-payment-plan"><span class="nobr"><?php esc_html_e( 'Payment Plan', 'sumopaymentplans' ) ; ?></span></th>
					<th class="sumo-payment-status woocommerce-orders-table__header woocommerce-orders-table__header-payment-status"><span class="nobr"><?php esc_html_e( 'Payment Status', 'sumopaymentplans' ) ; ?></span></th>
					<th class="sumo-payment-actions woocommerce-orders-table__header woocommerce-orders-table__header-payment-actions">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $customer_payments->payments as $payment_id ) :
					$payment = _sumo_pp_get_payment( $payment_id ) ;
					?>
					<tr class="woocommerce-orders-table__row sumo-payment woocommerce-orders-table__row--status-<?php echo esc_attr( $payment->get_status( true ) ) ; ?>">
						<td class="sumo-payment-id woocommerce-orders-table__cell woocommerce-orders-table__cell-payment-id" data-title="<?php esc_attr_e( 'Payment Number', 'sumopaymentplans' ) ; ?>">
							<?php echo '<a href="' . esc_url( $payment->get_view_endpoint_url() ) . '">#' . esc_html( $payment->get_payment_number() ) . '</a>' ; ?>
						</td>
						<td class="sumo-payment-product woocommerce-orders-table__cell woocommerce-orders-table__cell-payment-product" data-title="<?php esc_attr_e( 'Product Title', 'sumopaymentplans' ) ; ?>">
							<?php echo wp_kses_post( $payment->get_formatted_product_name() ) ; ?>
						</td>
						<td class="sumo-payment-plan woocommerce-orders-table__cell woocommerce-orders-table__cell-payment-plan" data-title="<?php esc_attr_e( 'Payment Plan', 'sumopaymentplans' ) ; ?>">
							<?php
							if ( 'payment-plans' === $payment->get_payment_type() ) {
								echo wp_kses_post( $payment->get_plan( 'name' ) ) ;
							} else {
								echo 'N/A' ;
							}
							?>
						</td>
						<td class="sumo-payment-status woocommerce-orders-table__cell woocommerce-orders-table__cell-payment-status" data-title="<?php esc_attr_e( 'Payment Status', 'sumopaymentplans' ) ; ?>">
							<?php _sumo_pp_payment_status_html( $payment ) ; ?>
						</td>
						<td class="sumo-payment-actions woocommerce-orders-table__cell woocommerce-orders-table__cell-payment-actions">
							<a href="<?php echo esc_url( $payment->get_view_endpoint_url() ) ; ?>" class="woocommerce-button button view"><?php esc_html_e( 'View', 'sumopaymentplans' ) ; ?></a>
							<?php do_action( 'woocommerce_my_sumo_payments_actions', $payment ) ; ?>
						</td>
					</tr>
				<?php endforeach ; ?>
			</tbody>
		</table>
		<?php do_action( 'woocommerce_before_account_sumo_payments_pagination', $customer_payments ) ; ?>

		<?php if ( 1 < $customer_payments->max_num_pages ) : ?>
			<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
				<?php if ( 1 !== $current_page ) : ?>
					<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( $endpoint, $current_page - 1 ) ) ; ?>"><?php esc_html_e( 'Previous', 'sumopaymentplans' ) ; ?></a>
				<?php endif ; ?>

				<?php if ( intval( $customer_payments->max_num_pages ) !== $current_page ) : ?>
					<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( $endpoint, $current_page + 1 ) ) ; ?>"><?php esc_html_e( 'Next', 'sumopaymentplans' ) ; ?></a>
				<?php endif ; ?>
			</div>
		<?php endif ; ?>
	<?php else : ?>
		<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
			<a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ) ; ?>">
				<?php esc_html_e( 'Browse products.', 'sumopaymentplans' ) ; ?>
			</a>
			<?php esc_html_e( 'You have no payments.', 'sumopaymentplans' ) ; ?>
		</div>
	<?php endif ; ?>

	<?php do_action( 'woocommerce_after_account_sumo_payments', $customer_payments ) ; ?>
</div>
