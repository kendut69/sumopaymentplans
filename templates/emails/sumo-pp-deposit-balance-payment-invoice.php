<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<?php if ( $payment_order->has_status( 'pending' ) ) : ?>

	<p>
		<?php
		/* translators: 1: product title 2: payment number */
		printf( wp_kses_post( __( 'Hi, <br>Your Invoice for Balance Payment of %1$s from payment #%2$s has been generated . The Payment details are as follows', 'sumopaymentplans' ) ), wp_kses_post( $product_title ), esc_html( $payment->get_payment_number() ) ) ;
		?>
	</p>

<?php endif ; ?>

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

<?php if ( $payment->get_prop( 'next_payment_date' ) ) { ?>
	<p>
		<?php
		/* translators: 1: payment url 2: next payment date */
		printf( wp_kses_post( __( 'Please make the payment using the payment link %1$s on or before <strong>%2$s</strong>', 'sumopaymentplans' ) ), '<a href="' . esc_url( $payment_order->get_checkout_payment_url() ) . '">' . esc_html__( 'pay', 'sumopaymentplans' ) . '</a>', esc_html( _sumo_pp_get_date_to_display( $payment->get_prop( 'next_payment_date' ), 'email' ) ) ) ;
		?>
	</p>
<?php } ?>
<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
