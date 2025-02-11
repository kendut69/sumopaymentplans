<?php
/**
 * Output Order PaymentPlan > Review payment details.
 * 
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/checkout/order-paymentplan/review-payment.php.
 */
defined( 'ABSPATH' ) || exit;
?>

<?php if ( $is_checkout_blocks_api_request ) { ?>
    <?php echo wp_kses_post( $payment_info ); ?>
            <?php echo wp_kses_post( $balance_payable ); ?>
<?php } else { ?>
    <tr class="_sumo_pp_orderpp_payable_now_info">
        <th><?php esc_html_e( 'Payable Now', 'sumopaymentplans' ); ?></th>
        <td style="vertical-align: top;">
            <strong><?php echo wp_kses_post( wc_price( $down_payment ) ); ?></strong>
        </td>
    </tr>

    <tr class="_sumo_pp_orderpp_payment_details_info">
        <th><?php esc_html_e( 'Payment Details', 'sumopaymentplans' ); ?></th>
        <td style="vertical-align: top;">
            <p style="font-weight:normal;text-transform:none;">
                <?php echo wp_kses_post( $payment_info ); ?>
                <?php echo wp_kses_post( $balance_payable ); ?>
            </p>
        </td>
    </tr>
    <?php
} 

