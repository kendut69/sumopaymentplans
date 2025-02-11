<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
	/* translators: 1: payment number 2: product title  */
	printf( wp_kses_post( __( 'Hi, <br>Your payment #%1$s for product %2$s has been cancelled since you have not paid the balance payments within the due date', 'sumopaymentplans' ) ), esc_html( $payment->get_payment_number() ), wp_kses_post( $product_title ) ) ;
	?>
</p>

<h2><?php esc_html_e( 'Payment Schedule', 'sumopaymentplans' ) ; ?></h2>

<?php do_action( 'sumopaymentplans_email_installments_table', $payment, $sent_to_admin, $plain_text, $email ) ; ?>

<p><?php esc_html_e( 'Thanks', 'sumopaymentplans' ) ; ?></p>

<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
