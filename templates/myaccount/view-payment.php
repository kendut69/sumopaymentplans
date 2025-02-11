<?php
/**
 * My Payments > View Payment.
 * 
 * Shows the details of a particular payment on the account page.
 *
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/myaccount/view-payment.php.
 */
defined( 'ABSPATH' ) || exit;

$currency = $initial_payment_order ? $initial_payment_order->get_currency() : '';

do_action( 'sumopaymentplans_before_view_payment_table', $payment_id );
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
    <tr class="payment_start_date">
        <td><b><?php esc_html_e( 'Payment Start Date ', 'sumopaymentplans' ); ?></b></td>
        <td>:</td>
        <td>
            <?php
            if ( $payment->get_prop( 'payment_start_date' ) ) {
                echo esc_html( _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_start_date' ) ) );
            } else {
                echo '--';
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
            ?>
        </td>
    </tr>   
    <?php    
    $valid_payment_statuses = ( array ) apply_filters( 'sumopaymentplans_add_or_change_payment_valid_statuses', array( 'in_progress', 'await_cancl', 'pendng_auth' , 'pending', 'overdue' ) );
    if ( in_array( $payment->get_status(), $valid_payment_statuses ) ) {
        ?>
        <tr class="payment_actions">
            <td><b><?php esc_html_e( 'Actions', 'sumopaymentplans' ); ?></b></td>
            <td>:</td>
            <td>
                <?php
                if ( $initial_payment_order ) :
                    $args = array(
                        'sumo_pp_payment_add_or_change_payment' => $payment_id,
                        '_sumo_pp_nonce'                        => wp_create_nonce( $payment_id ),
                    );
                    $url  = add_query_arg( $args, $initial_payment_order->get_checkout_payment_url() );
                    if ( 'auto' === $payment->get_payment_mode() ) :
                        printf(
                                '<a class="woocommerce-button button sumo-pp-change-payment" href="%s">%s</a>',
                                $url, __( 'Change Payment Method', 'sumopaymentplans' ) );
                    else:
                        printf(
                                '<a class="woocommerce-button button sumo-pp-add-payment" href="%s">%s</a>',
                                $url, __( 'Add Payment Method', 'sumopaymentplans' ) );
                    endif;
                endif;
                ?>
            </td>
        </tr>   
    <?php } ?>
</table>
<h6><?php esc_html_e( 'Payment Schedule', 'sumopaymentplans' ); ?></h6>
<?php
if (
        'payment-plans' === $payment->get_payment_type() &&
        $payment->has_status( array( 'in_progress', 'overdue', 'await_cancl', 'cancelled', 'failed' ) ) &&
        'immediately_after_payment' === _sumo_pp_get_plan_orders_creation( $payment )
 ) {
    ?>
    <div class="pay_installments">
        <select>
            <option value="pay-remaining"><?php esc_html_e( 'Pay for Remaining installment(s)', 'sumopaymentplans' ); ?></option>
            <?php for ( $i = 0; $i < absint( $payment->get_prop( 'remaining_installments' ) ); $i ++ ) { ?>
                <option value="<?php echo esc_attr( $i ); ?>">
                    <?php
                    /* translators: 1: installment count */
                    printf( esc_html__( 'Pay for %s installment(s)', 'sumopaymentplans' ), esc_html( $i + 1 ) );
                    ?>
                </option>
            <?php } ?>
        </select>
        <input type="button" class="button" value="<?php esc_html_e( 'Pay Now', 'sumopaymentplans' ); ?>">
    </div><br>
<?php } ?>

<?php do_action( 'sumopaymentplans_account_installments_table', $payment ); ?>

<table class="payment_activities">
    <tr> 
        <td><h6><?php esc_html_e( 'Activity Logs ', 'sumopaymentplans' ); ?></h6></td>
    </tr>
    <tr>
        <td>
            <?php
            $payment_notes = $payment->get_payment_notes();
            if ( $payment_notes ) {
                foreach ( $payment_notes as $index => $note ) :
                    if ( $index < 3 ) {
                        echo '<style type="text/css">.default_notes' . esc_attr( $index ) . '{display:block;}</style>';
                    } else {
                        echo '<style type="text/css">.default_notes' . esc_attr( $index ) . '{display:none;}</style>';
                    }

                    switch ( ! empty( $note->meta[ 'comment_status' ][ 0 ] ) ? $note->meta[ 'comment_status' ][ 0 ] : '' ) :
                        case 'success':
                            ?>
                            <div class="_alert_box _success default_notes<?php echo esc_attr( $index ); ?>"><span><?php echo esc_html( $note->content ); ?></span></div>
                            <?php
                            break;
                        case 'pending':
                            ?>
                            <div class="_alert_box warning default_notes<?php echo esc_attr( $index ); ?>"><span><?php echo esc_html( $note->content ); ?></span></div>
                            <?php
                            break;
                        case 'failure':
                            ?>
                            <div class="_alert_box error default_notes<?php echo esc_attr( $index ); ?>"><span><?php echo esc_html( $note->content ); ?></span></div>
                            <?php
                            break;
                        default:
                            ?>
                            <div class="_alert_box notice default_notes<?php echo esc_attr( $index ); ?>"><span><?php echo esc_html( $note->content ); ?></span></div>
                        <?php
                    endswitch;
                endforeach;

                if ( ! empty( $index ) && $index >= 3 ) {
                    ?>
                    <a data-flag="more" id="prevent_more_notes" style="cursor: pointer;"> <?php esc_html_e( 'Show More', 'sumopaymentplans' ); ?></a>
                    <?php
                }
            } else {
                ?>
                <div class="_alert_box notice">
                    <span><?php esc_html_e( 'No Activities Yet.', 'sumopaymentplans' ); ?></span>
                </div>
                <?php
            }
            ?>
        </td>
    </tr>
</table>
<?php
do_action( 'sumopaymentplans_after_view_payment_table', $payment_id );
