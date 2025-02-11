<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
	/* translators: 1: payment number */
	printf( wp_kses_post( __( 'Hi, <br>You have successfully completed the Payment Schedule for Payment #%s. Your Payment details are as follows.', 'sumopaymentplans' ) ), esc_html( $payment->get_payment_number() ) ) ;
	?>
</p>

<h2><?php esc_html_e( 'Payment Schedule', 'sumopaymentplans' ) ; ?></h2>

<?php do_action( 'sumopaymentplans_email_installments_table', $payment, $sent_to_admin, $plain_text, $email ) ; ?>

<p><?php esc_html_e( 'Thanks', 'sumopaymentplans' ) ; ?></p>

<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
