<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
	/* translators: 1: payment number 2: blog name 3: view payment url */
	printf( wp_kses_post( __( 'Hi, <br>Payment #%1$s on %2$s is awaiting for cancel. To cancel the payment <a href="%3$s">click here</a>', 'sumopaymentplans' ) ), esc_html( $payment->get_payment_number() ), esc_html( get_option( 'blogname' ) ), esc_url( admin_url( "post.php?post={$payment->id}&action=edit" ) ) ) ;
	?>
</p>

<h2><?php esc_html_e( 'Payment Schedule', 'sumopaymentplans' ) ; ?></h2>

<?php do_action( 'sumopaymentplans_email_installments_table', $payment, $sent_to_admin, $plain_text, $email ) ; ?>

<p><?php esc_html_e( 'Thanks', 'sumopaymentplans' ) ; ?></p>

<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
