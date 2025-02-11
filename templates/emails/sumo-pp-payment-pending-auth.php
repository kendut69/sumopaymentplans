<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
	/* translators: 1: product title 2: payment number */
	printf( wp_kses_post( __( 'Your payment #%1$s is in Pending Authorization status because we couldn\'t charge your account for future payment as your bank have declined the authorization which you have previously given. Please pay using another card or else using any other payment gateway <a href="%2$s">pay</a>. If payment is not made for the future payment by <strong>%3$s</strong>, the Payment will be <strong>%4$s</strong>.', 'sumopaymentplans' ) ), esc_html( $payment->get_payment_number() ), esc_url( $payment_order->get_checkout_payment_url() ), esc_html( $next_action_on ), esc_html( $next_action_status ) ) ;
	?>
</p>

<?php do_action( 'woocommerce_email_before_order_table', $payment_order, $sent_to_admin, $plain_text, $email ) ; ?>

<h2>
	<?php
	/* translators: 1: payment number */
	printf( wp_kses_post( __( 'Payment #%s', 'sumopaymentplans' ) ), esc_html( $payment->get_payment_number() ) ) ;
	?>
</h2>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
	<thead>
		<tr>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Product', 'sumopaymentplans' ) ; ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Quantity', 'sumopaymentplans' ) ; ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Price', 'sumopaymentplans' ) ; ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		echo wp_kses_post( wc_get_email_order_items( $payment_order, array(
			'show_sku'      => $sent_to_admin,
			'show_image'    => false,
			'image_size'    => array( 32, 32 ),
			'plain_text'    => $plain_text,
			'sent_to_admin' => $sent_to_admin,
		) ) ) ;
		?>
	</tbody>
	<tfoot>
		<?php
		$text_align  = is_rtl() ? 'right' : 'left' ;
		$item_totals = $payment_order->get_order_item_totals() ;

		if ( $item_totals ) {
			$i = 0 ;
			foreach ( $item_totals as $total ) {
				$i++ ;
				?>
				<tr>
					<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ) ; ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : '' ; ?>"><?php echo wp_kses_post( $total[ 'label' ] ) ; ?></th>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ) ; ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : '' ; ?>"><?php echo wp_kses_post( $total[ 'value' ] ) ; ?></td>
				</tr>
				<?php
			}
		}
		?>
	</tfoot>
</table>

<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
