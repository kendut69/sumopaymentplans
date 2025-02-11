<?php
/**
 * My Payments > View Payments > Form Add or Change Payment Method.
 * 
 * Shows the details of a particular payments on the My account view payments page.
 *
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/form-add-or-change-payment-method.php.
 * 
 * @since 10.9.0
 */
defined( 'ABSPATH' ) || exit;

$currency = $order ? $order->get_currency() : '';

/**
 * Before add or Change Payment Method Details
 * 
 * @since 10.9.0
 * @param int $payment_id 
 */
do_action( 'sumopaymentplans_before_add_or_change_payment_method_details', $payment_id );
?>
<table class="payment_details" data-payment_id="<?php echo esc_attr( $payment_id ); ?>">
    <tr class="payment_status">
        <td><b><?php esc_html_e( 'Payment Status', 'sumopaymentplans' ); ?></b></td>
        <td>:</td>
        <td><?php _sumo_pp_payment_status_html( $payment ); ?></td>
    </tr>    
    <tr class="payment_product">
        <td><b><?php esc_html_e( 'Payment Product ', 'sumopaymentplans' ); ?></b></td>
        <td>:</td>
        <td><?php echo wp_kses_post( $payment->get_formatted_product_name() ); ?></td>
    </tr>    
    <tr class="payment_plan">
        <td><b><?php esc_html_e( 'Payment Plan ', 'sumopaymentplans' ); ?></b></td>
        <td>:</td>
        <td>
            <?php
            if ( 'payment-plans' === $payment->get_payment_type() ) {
                echo esc_html( $payment->get_plan( 'name' ) );
            } else {
                echo 'N/A';
            }
            ?>
        </td>
    </tr>   
    <tr class="payment_next_payment_date">
        <td><b><?php esc_html_e( 'Payment Next Payment Date ', 'sumopaymentplans' ); ?></b></td>
        <td>:</td>
        <td>
            <?php
            if ( $payment->get_prop( 'next_payment_date' ) ) {
                echo esc_html( _sumo_pp_get_date_to_display( $payment->get_prop( 'next_payment_date' ) ) );
            } else {
                echo '--';
            }
            ?>
        </td>
    </tr>
    <tr class="payment_end_date">
        <td><b><?php esc_html_e( 'Payment End Date ', 'sumopaymentplans' ); ?></b></td>
        <td>:</td>
        <td>
            <?php
            if ( $payment->get_prop( 'payment_end_date' ) ) {
                echo esc_html( _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_end_date' ) ) );
            } else {
                echo '--';
            }
            ?>
    </tr>
    <tr class="initial_payment_amount">
        <td><b><?php esc_html_e( 'Initial Payment Amount ', 'sumopaymentplans' ); ?></b></td> 
        <td>:</td>
        <td>
            <?php _sumo_pp_deposit_amount_html( $payment, $currency ); ?>
        </td>
    </tr>
    <tr class="remaining_payable_amount">
        <td><b><?php esc_html_e( 'Remaining Payable Amount ', 'sumopaymentplans' ); ?></b></td> 
        <td>:</td>
        <td><?php echo wp_kses_post( wc_price( $payment->get_prop( 'remaining_payable_amount' ), array( 'currency' => $currency ) ) ); ?></td>
    </tr>
    <tr class="remaining_installments">
        <td><b><?php esc_html_e( 'Remaining Installments ', 'sumopaymentplans' ); ?></b></td> 
        <td>:</td>
        <td><?php echo is_numeric( $payment->get_prop( 'remaining_installments' ) ) ? esc_html( $payment->get_prop( 'remaining_installments' ) ) : '--'; ?></td>
    </tr>
    <tr class="payment_method">
        <td><b><?php esc_html_e( 'Payable Payment Method ', 'sumopaymentplans' ); ?></b></td> 
        <td>:</td>
        <td><?php
            /* translators: 1: payment method title */
            printf( esc_html__( 'Payment %s', 'sumopaymentplans' ), $payment->get_payment_method_to_display( 'customer' ) );
            ?></td>
    </tr>
</table>

<form id="order_review" method="post">
    <div id="payment">          
        <ul class="wc_payment_methods payment_methods methods">
            <?php
            if ( count( $available_gateways ) ) {
                current( $available_gateways )->set_current();
            }

            if ( ! empty( $available_gateways ) ) {
                foreach ( $available_gateways as $gateway ) {
                    wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
                }
            } else {
                /**
                 * Get no available payment methods to display text.
                 * 
                 * @since 10.9.0
                 */
                echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . esc_html( apply_filters( 'woocommerce_no_available_payment_methods_message', esc_html__( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'sumopaymentplans' ) ) ) . '</li>';
            }
            ?>
        </ul>       

        <?php if ( $available_gateways ) : ?>                         
            <span class="update-all-payments-payment-method">
                <?php
                //translators: $1: opening <strong> tag, $2: closing </strong> tag
                $label = sprintf( esc_html__( 'Update the payment method used for %1$sall%2$s of my current payments', 'sumopaymentplans' ), '<strong>', '</strong>' );

                woocommerce_form_field(
                        'update_payment_method_for_all_valid_payments',
                        array(
                            'type'    => 'checkbox',
                            'class'   => array( 'form-row-wide' ),
                            'label'   => $label,
                            'default' => false,
                        )
                );
                ?>
            </span>       
            <?php
            /**
             * Add/Change payment method before submit.
             * 
             * @since 10.9.0
             */
            do_action( 'sumopaymentplans_add_or_change_payment_method_before_submit' );
            ?>
            <div class="form-row">          
                <?php wp_nonce_field( 'sumo_pp_add_or_change_payment_method', '_sumo_pp_nonce', true, true ); ?>                                
                <input type="submit" class="button alt" id="place_order" value="<?php echo esc_attr( $payment_button_text ) ?>" data-value="<?php echo esc_attr( $payment_button_text ) ?>" />
                <input type="hidden" name="sumo_pp_add_or_change_payment" value="<?php echo esc_attr( $payment_id ); ?>" />
            </div>
        <?php endif; ?>
    </div>
</form>
