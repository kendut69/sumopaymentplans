<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
	/* translators: 1: product title with installment number 2: payment number 3: payment url 4: next action on 5: next action on 6: next action status */
	printf( wp_kses_post( __( 'Hi, <br>Your payment for %1$s from payment #%2$s is currently Overdue. Please make the payment using the payment link %3$s before <strong>%4$s</strong>. If Payment is not received within <strong>%5$s</strong>, the Payment Plan will be <strong>%6$s</strong>.', 'sumopaymentplans' ) ), wp_kses_post( $product_title_with_installment ), esc_html( $payment->get_payment_number() ), '<a href="' . esc_url( $payment_order->get_checkout_payment_url() ) . '">' . esc_html__( 'pay', 'sumopaymentplans' ) . '</a>', esc_html( $next_action_on ), esc_html( $next_action_on ), esc_html( $next_action_status ) ) ;
	?>
</p>

<p><?php esc_html_e( 'Thanks', 'sumopaymentplans' ) ; ?></p>

<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
