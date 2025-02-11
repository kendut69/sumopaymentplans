<?php
/**
 * Email Payment Installments details.
 * 
 * Shows the details of a particular payment installments details on the email.
 *
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/emails/email-payment-installments.php.
 */
defined( 'ABSPATH' ) || exit ;

$text_align = is_rtl() ? 'right' : 'left' ;

do_action( 'sumopaymentplans_email_before_payment_installments_table', $payment, $initial_payment_order, $sent_to_admin, $plain_text, $email ) ;
?>
<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<?php foreach ( $columns as $column_key => $column_name ) : ?>
					<th class="td" scope=col style="text-align:<?php echo esc_attr( $text_align ) ; ?>;"><?php echo esc_html( $column_name ) ; ?></th>
				<?php endforeach ; ?>
			</tr>
		</thead>
		<tbody>
			<?php do_action( 'sumopaymentplans_email_payment_installments_table_row_start', $payment, $initial_payment_order, $sent_to_admin, $plain_text, $email ) ; ?>
			<?php
			if ( 'pay-in-deposit' === $payment->get_payment_type() ) {
				$paid_order_id       = isset( $balance_paid_orders[ 0 ] ) ? absint( $balance_paid_orders[ 0 ] ) : 0 ;
				$is_installment_paid = $paid_order_id > 0 ;
				?>
				<tr class="order_item">
					<?php foreach ( $columns as $column_key => $column_name ) : ?>
						<td class="td" style="text-align:<?php echo esc_attr( $text_align ) ; ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
							<?php
							switch ( $column_key ) {
								case 'installment-payment-of-product':
									if ( 'order' === $payment->get_product_type() ) {
										if ( $is_installment_paid ) {
											/* translators: 1: paid order url 2: product name */
											printf( wp_kses_post( __( '<a href="%1$s">Installment #1 of %2$s</a>', 'sumopaymentplans' ) ), esc_url( wc_get_endpoint_url( 'view-order', $paid_order_id, wc_get_page_permalink( 'myaccount' ) ) ), wp_kses_post( $payment->get_formatted_product_name() ) ) ;
										} else {
											/* translators: 1: product name */
											printf( wp_kses_post( __( 'Installment #1 of %s', 'sumopaymentplans' ) ), wp_kses_post( $payment->get_formatted_product_name() ) ) ;
										}
									} elseif ( $is_installment_paid ) {
											/* translators: 1: paid order url 2: product name */
											printf( wp_kses_post( __( '<a href="%1$s">Installment #1 of %2$s</a>&nbsp;&nbsp;x%3$s', 'sumopaymentplans' ) ), esc_url( wc_get_endpoint_url( 'view-order', $paid_order_id, wc_get_page_permalink( 'myaccount' ) ) ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false ) ) ), esc_html( $payment->get_product_qty() ) ) ;
									} else {
										/* translators: 1: product name */
										printf( wp_kses_post( __( 'Installment #1 of %1$s&nbsp;&nbsp;x%2$s', 'sumopaymentplans' ) ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false ) ) ), esc_html( $payment->get_product_qty() ) ) ;
									}
									break ;
								case 'installment-amount':
									$installment_amount = wc_price( $payment->get_product_price() - $payment->get_down_payment( false ), array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;

									if ( 'order' === $payment->get_product_type() ) {
										echo wp_kses_post( $installment_amount ) ;
									} else {
										echo wp_kses_post( "{$installment_amount}&nbsp;&nbsp;x{$payment->get_product_qty()}" ) ;
									}
									break ;
								case 'installment-expected-payment-date':
									$installment_date  = '' ;
									$next_payment_date = $payment->get_prop( 'next_payment_date' ) ;

									if ( $next_payment_date ) {
										$installment_date = $next_payment_date ;
									} elseif ( 'before' === $payment->get_pay_balance_type() ) {
											$installment_date = _sumo_pp_get_timestamp( $payment->get_pay_balance_before() ) ;
									} elseif ( ! $payment->has_status( 'await_aprvl' ) && $payment->get_pay_balance_after() > 0 ) {
											$installment_date = _sumo_pp_get_timestamp( "+{$payment->get_pay_balance_after()} days", _sumo_pp_get_timestamp( $payment->get_prop( 'payment_start_date' ) ) ) ;
									}

									if ( '' !== $installment_date ) {
										echo esc_html( _sumo_pp_get_date_to_display( $installment_date ) ) ;
									} else {
										echo '--' ;
									}
									break ;
								case 'installment-actual-payment-date':
									if ( ! empty( $actual_payments_date[ 0 ] ) ) {
										echo esc_html( _sumo_pp_get_date_to_display( $actual_payments_date[ 0 ] ) ) ;
									} else {
										echo '--' ;
									}
									break ;
								case 'installment-order-number':
									if ( $is_installment_paid ) {
										/* translators: 1: paid order url 2: paid order ID */
										printf( wp_kses_post( __( '<a href="%1$s">#%2$s</a><p>Paid</p>', 'sumopaymentplans' ) ), esc_url( wc_get_endpoint_url( 'view-order', $paid_order_id, wc_get_page_permalink( 'myaccount' ) ) ), esc_html( $paid_order_id ) ) ;
									} elseif ( $payment->balance_payable_order_exists() ) {
											/* translators: 1: invoice order url 2: invoice order ID */
											printf( wp_kses_post( __( '<a class="button" href="%1$s">Pay for #%2$s</a>', 'sumopaymentplans' ) ), esc_url( $payment->balance_payable_order->get_checkout_payment_url() ), esc_html( $payment->balance_payable_order->get_order_number() ) ) ;
									} else {
										echo '--' ;
									}
									break ;
								default:
									if ( has_action( 'sumopaymentplans_email_deposit_payment_installments_column_' . $column_key ) ) {
										do_action( 'sumopaymentplans_email_deposit_payment_installments_column_' . $column_key, $payment ) ;
									}
							}
							?>
						</td>
					<?php endforeach ; ?>
				</tr>
				<?php
			} elseif ( is_array( $payment->get_prop( 'payment_schedules' ) ) ) {
				foreach ( $payment->get_prop( 'payment_schedules' ) as $installment => $scheduled_installment ) {
					$paid_order_id       = isset( $balance_paid_orders[ $installment ] ) ? absint( $balance_paid_orders[ $installment ] ) : 0 ;
					$is_installment_paid = $paid_order_id > 0 ;
					?>
						<tr class="order_item">
						<?php foreach ( $columns as $column_key => $column_name ) : ?>
								<td class="td" style="text-align:<?php echo esc_attr( $text_align ) ; ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
									<?php
									switch ( $column_key ) {
										case 'installment-payment-of-product':
											$payment_count = $installment ;
											++$payment_count ;
											$payment_count = apply_filters( 'sumopaymentplans_installment_count', $payment_count ) ;

											if ( 'order' === $payment->get_product_type() ) {
												if ( $is_installment_paid ) {
													/* translators: 1: paid order url 2: payment count 3: product name */
													printf( wp_kses_post( __( '<a href="%1$s">Installment #%2$s of %3$s</a>', 'sumopaymentplans' ) ), esc_url( wc_get_endpoint_url( 'view-order', $paid_order_id, wc_get_page_permalink( 'myaccount' ) ) ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name() ) ) ;
												} else {
													/* translators: 1: payment count 2: product name */
													printf( wp_kses_post( __( 'Installment #%1$s of %2$s', 'sumopaymentplans' ) ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name() ) ) ;
												}
											} elseif ( $is_installment_paid ) {
													/* translators: 1: paid order url 2: payment count 3: product name 4: product qty */
													printf( wp_kses_post( __( '<a href="%1$s">Installment #%2$s of %3$s</a>&nbsp;&nbsp;x%4$s', 'sumopaymentplans' ) ), esc_url( wc_get_endpoint_url( 'view-order', $paid_order_id, wc_get_page_permalink( 'myaccount' ) ) ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false ) ) ), esc_html( $payment->get_product_qty() ) ) ;
											} else {
												/* translators: 1: payment count 2: product name 3: product qty */
												printf( wp_kses_post( __( 'Installment #%1$s of %2$s&nbsp;&nbsp;x%3$s', 'sumopaymentplans' ) ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false ) ) ), esc_html( $payment->get_product_qty() ) ) ;
											}
											break ;
										case 'installment-amount':
											if ( isset( $scheduled_installment[ 'scheduled_payment' ] ) ) {
												if ( 'fixed-price' === $payment->get_plan_price_type() ) {
													$installment_amount          = $scheduled_installment[ 'scheduled_payment' ] ;
													$formated_installment_amount = wc_price( $installment_amount, array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
												} else {
													$installment_amount          = $payment->get_product_price() * floatval( $scheduled_installment[ 'scheduled_payment' ] ) / 100 ;
													$formated_installment_amount = wc_price( $installment_amount, array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
												}
											} else {
												$installment_amount          = '0' ;
												$formated_installment_amount = wc_price( $installment_amount, array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
											}

											if ( 'order' === $payment->get_product_type() ) {
												echo wp_kses_post( $formated_installment_amount ) ;
											} else {
												echo wp_kses_post( "{$formated_installment_amount}&nbsp;&nbsp;x{$payment->get_product_qty()}" ) ;
											}
											break ;
										case 'installment-expected-payment-date':
											if ( ! empty( $scheduled_payments_date[ $installment ] ) ) {
												echo esc_html( _sumo_pp_get_date_to_display( $scheduled_payments_date[ $installment ] ) ) ;
											} else {
												echo '--' ;
											}
											break ;
										case 'installment-modified-expected-payment-date':
											if ( ! empty( $modified_payment_dates[ $installment ] ) ) {
												echo esc_html( _sumo_pp_get_date_to_display( $modified_payment_dates[ $installment ] ) ) ;
											} else {
												echo '--' ;
											}
											break ;
										case 'installment-actual-payment-date':
											if ( ! empty( $actual_payments_date[ $installment ] ) ) {
												echo esc_html( _sumo_pp_get_date_to_display( $actual_payments_date[ $installment ] ) ) ;
											} else {
												echo '--' ;
											}
											break ;
										case 'installment-order-number':
											if ( $is_installment_paid ) {
												/* translators: 1: paid order url 2: paid order ID */
												printf( wp_kses_post( __( '<a href="%1$s">#%2$s</a><p>Paid</p>', 'sumopaymentplans' ) ), esc_url( wc_get_endpoint_url( 'view-order', $paid_order_id, wc_get_page_permalink( 'myaccount' ) ) ), esc_html( $paid_order_id ) ) ;
											} elseif ( empty( $balance_payable_order ) && $payment->balance_payable_order_exists() ) {
													$balance_payable_order = $payment->balance_payable_order ;
													/* translators: 1: invoice order url 2: invoice order ID */
													printf( wp_kses_post( __( '<a class="button" href="%1$s">Pay for #%2$s</a>', 'sumopaymentplans' ) ), esc_url( $payment->balance_payable_order->get_checkout_payment_url() ), esc_html( $payment->balance_payable_order->get_order_number() ) ) ;
											} else {
												echo '--' ;
											}
											break ;
										default:
											if ( has_action( 'sumopaymentplans_email_payment_plan_installments_column_' . $column_key ) ) {
												do_action( 'sumopaymentplans_email_payment_plan_installments_column_' . $column_key, $payment, $installment, $scheduled_installment ) ;
											}
									}
									?>
								</td>
							<?php endforeach ; ?>
						</tr>
						<?php
				}
			}
			?>
			<?php do_action( 'sumopaymentplans_email_payment_installments_table_row_end', $payment, $initial_payment_order, $sent_to_admin, $plain_text, $email ) ; ?>
		</tbody>
	</table>
</div>
<?php do_action( 'sumopaymentplans_email_after_payment_installments_table', $payment, $initial_payment_order, $sent_to_admin, $plain_text, $email ) ; ?>
