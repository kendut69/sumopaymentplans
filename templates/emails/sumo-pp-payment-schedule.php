<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
	/* translators: 1: product title 2: payment start date 3: payment number */
	printf( wp_kses_post( __( 'Hi, <br>Your Payment Schedule for Purchase of %1$s on %2$s from Payment #%3$s is as Follows', 'sumopaymentplans' ) ), wp_kses_post( $product_title ), esc_html( _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_start_date' ), 'email' ) ), esc_html( $payment->get_payment_number() ) ) ;
	?>
</p>

<h2><?php esc_html_e( 'Payment Schedule', 'sumopaymentplans' ) ; ?></h2>

<?php do_action( 'sumopaymentplans_email_installments_table', $payment, $sent_to_admin, $plain_text, $email ) ; ?>

<p><?php esc_html_e( 'Thanks', 'sumopaymentplans' ) ; ?></p>

<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
