<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<p class="balance_payable_orders_creation">
    <select name="_sumo_pp_balance_payable_orders_creation">
        <option value="immediately_after_payment" <?php selected( $balance_payable_orders_creation, 'immediately_after_payment' ); ?>><?php esc_html_e( 'Immediately After Payment', 'sumopaymentplans' ); ?></option>
        <option value="based_upon_settings" <?php selected( $balance_payable_orders_creation, 'based_upon_settings' ); ?>><?php esc_html_e( 'Based Upon Settings', 'sumopaymentplans' ); ?></option>
    </select>
</p>
<p class="next_payment_date">
    <label for="next_payment_date"><?php esc_html_e( 'Next Payment Date:', 'sumopaymentplans' ); ?></label>
    <select name="_sumo_pp_next_payment_date_based_on">
        <option value="expected-payment-date" <?php selected( $next_payment_date_based_on, 'expected-payment-date' ); ?>><?php esc_html_e( 'Actual Payment Date', 'sumopaymentplans' ); ?></option>
        <option value="modified-payment-date" <?php selected( $next_payment_date_based_on, 'modified-payment-date' ); ?>><?php esc_html_e( 'Modified Payment Date', 'sumopaymentplans' ); ?></option>
    </select>
    <?php echo wc_help_tip( 'If "Modified Date" option is selected then, when the customer makes payment for 2 or more installments in a single pay, the modified expected payment date will be set as the expected payment date of the upcoming installment.' ); ?>
</p>
