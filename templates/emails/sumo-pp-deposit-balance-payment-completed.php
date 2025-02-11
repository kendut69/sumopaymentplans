<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
	/* translators: 1: product name 2: payment number */
	printf( wp_kses_post( __( 'Hi, <br>The Balance Payment for your purchase of %1$s from payment #%2$s has been paid Successfully', 'sumopaymentplans' ) ), wp_kses_post( $product_title ), esc_html( $payment->get_payment_number() ) ) ;
	?>
</p>

<p><?php esc_html_e( 'Thanks', 'sumopaymentplans' ) ; ?></p>

<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
