<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="panel-wrap sumo-pp-payment-data">
    <input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? esc_html__( 'Payment', 'sumopaymentplans' ) : esc_attr( $post->post_title ); ?>" />
    <input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>" />
    <div id="order_data" class="panel">
        <h2 style="float: left;">
            <?php
            /* translators: 1: post name 2: payment number  */
            echo esc_html( sprintf( __( '%1$s #%2$s details', 'sumopaymentplans' ), get_post_type_object( $post->post_type )->labels->singular_name, $payment->get_payment_number() ) );
            ?>
        </h2>

        <?php _sumo_pp_payment_status_html( $payment, 'admin' ); ?>

        <p class="order_number" style="clear:both;">
            <?php
            if ( $initial_payment_order ) {
                $payment_method = $initial_payment_order->get_payment_method();

                if ( $payment_method ) {
                    $payment_gateways = WC()->payment_gateways() ? WC()->payment_gateways->payment_gateways() : array();

                    /* translators: 1: payment gateway title  */
                    printf( esc_html__( 'Payment via %s', 'sumopaymentplans' ), ( isset( $payment_gateways[ $payment_method ] ) ? esc_html( $payment_gateways[ $payment_method ]->get_title() ) : esc_html( $payment_method ) ) );

                    $transaction_id = $initial_payment_order->get_transaction_id();
                    if ( $transaction_id ) {
                        if ( isset( $payment_gateways[ $payment_method ] ) ) {
                            $url = $payment_gateways[ $payment_method ]->get_transaction_url( $initial_payment_order );

                            echo $url ? ' (<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>)' : ' (' . esc_html( $transaction_id ) . ')';
                        } else {
                            echo ' (' . esc_html( $transaction_id ) . ')';
                        }
                    }
                    echo '. ';
                }

                $ip_address = $initial_payment_order->get_customer_ip_address();
                if ( $ip_address ) {
                    echo esc_html__( 'Customer IP', 'sumopaymentplans' ) . ': ' . esc_html( $ip_address );
                }
            }
            ?>
        </p>                
        <div class="order_data_column_container">
            <div class="order_data_column">
                <h4>
                    <?php esc_html_e( 'Initial Payment Amount', 'sumopaymentplans' ); ?>
                </h4>
                <p class="form-field form-field-wide">
                    <?php _sumo_pp_deposit_amount_html( $payment, $initial_payment_order ? $initial_payment_order->get_currency() : get_woocommerce_currency_symbol(), 'admin' ); ?>
                </p><br>
                <h4>
                    <?php esc_html_e( 'Initial Payment Order', 'sumopaymentplans' ); ?>
                </h4>
                <p class="form-field form-field-wide">
                    <?php
                    if ( $initial_payment_order ) {
                        ?>
                        <a href="<?php echo esc_url( get_admin_url( null, 'post.php?post=' . $initial_payment_order->get_id() . '&action=edit' ) ); ?>">#<?php echo esc_html( $initial_payment_order->get_order_number() ); ?></a>
                        <?php
                    }
                    ?>
                </p><br>
                <h4>
                    <?php esc_html_e( 'General Details', 'sumopaymentplans' ); ?>
                </h4>
                <p class="form-field form-field-wide"><label for="order_date"><?php esc_html_e( 'Payment Start date:', 'sumopaymentplans' ); ?></label>
                    <?php
                    $payment_start_date = $payment->get_prop( 'payment_start_date' );
                    if ( $payment_start_date ) {
                        ?>
                        <input type="text" name="_sumo_pp_payment_start_date" value="<?php echo esc_attr( _sumo_pp_get_date_to_display( $payment_start_date, 'admin' ) ); ?>" readonly/>                                
                        <?php
                    } else {
                        echo '<b>' . esc_html_e( 'Not Yet Started !!', 'sumopaymentplans' ) . '</b>';
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide"><label for="order_date"><?php esc_html_e( 'Payment End date:', 'sumopaymentplans' ); ?></label>
                    <?php
                    $payment_end_date = $payment->get_prop( 'payment_end_date' );
                    if ( $payment_end_date ) {
                        ?>
                        <input type="text" name="_sumo_pp_payment_end_date" value="<?php echo esc_attr( _sumo_pp_get_date_to_display( $payment_end_date, 'admin' ) ); ?>" readonly/>
                        <?php
                    } else {
                        switch ( $payment->get_payment_type() ) {
                            case 'pay-in-deposit':
                                echo '<b>--</b>';
                                break;
                            case 'payment-plans':
                                if ( $payment->has_status( array( 'in_progress', 'overdue' ) ) ) {
                                    echo '<b>' . esc_html_e( 'Never Ends !!', 'sumopaymentplans' ) . '</b>';
                                } else if ( $payment->has_status( array( 'failed', 'cancelled', 'completed' ) ) ) {
                                    echo '<b>' . esc_html_e( 'Payment Ended !!', 'sumopaymentplans' ) . '</b>';
                                } else {
                                    echo '<b>--</b>';
                                }
                                break;
                        }
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide"><label for="order_date"><?php esc_html_e( 'Next Payment date:', 'sumopaymentplans' ); ?></label>
                    <?php
                    $next_payment_date = $payment->get_prop( 'next_payment_date' );
                    if ( $next_payment_date ) {
                        ?>
                        <input type="text" name="_sumo_pp_next_payment_date" value="<?php echo esc_attr( _sumo_pp_get_date_to_display( $next_payment_date, 'admin' ) ); ?>" readonly/>
                        <?php
                    } else {
                        echo '<b>--</b>';
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide">
                    <?php
                    if ( $payment->has_status( array( 'pending', 'in_progress', 'overdue', 'await_aprvl', 'await_cancl' ) ) ) {
                        ?>
                        <label for="order_status"><?php esc_html_e( 'Payment Status:', 'sumopaymentplans' ); ?></label>
                        <select class="wc-enhanced-select" id="payment_status" name="_sumo_pp_payment_status">
                            <option value=""><?php echo esc_html( $payment->get_status_label() ); ?></option>
                            <optgroup label="<?php esc_html_e( 'Change to', 'sumopaymentplans' ); ?>">
                                <?php
                                $payment_statuses = _sumo_pp_get_payment_statuses();
                                $statuses         = array( '_sumo_pp_cancelled' => $payment_statuses[ '_sumo_pp_cancelled' ], '_sumo_pp_completed' => $payment_statuses[ '_sumo_pp_completed' ] );

                                if ( $payment->has_status( 'await_aprvl' ) ) {
                                    $statuses = array_merge( array( '_sumo_pp_in_progress' => __( 'Activate Payment', 'sumopaymentplans' ) ), $statuses );
                                }

                                if ( is_array( $statuses ) && $statuses ) {
                                    foreach ( $statuses as $_status => $status_name ) {
                                        echo '<option value="' . esc_attr( $_status ) . '" ' . selected( $_status, $payment->get_status( true ), false ) . '>' . esc_html( $status_name ) . '</option>';
                                    }
                                }
                                ?>
                            </optgroup>
                        </select>
                        <?php
                    } else {
                        echo '<b>' . esc_html_e( 'This Payment cannot be changed to any other status !!', 'sumopaymentplans' ) . '</b>';
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide">
                    <label for="customer_user"><?php esc_html_e( 'Customer:', 'sumopaymentplans' ); ?></label>
                    <input type="text" required name="_sumo_pp_customer_email" placeholder="<?php esc_attr_e( 'Customer Email Address', 'sumopaymentplans' ); ?>" value="<?php echo esc_attr( $payment->get_customer_email() ); ?>" data-allow_clear="true" />
                </p>
                <?php
                if ( $balance_payable_order && ! _sumo_pp_is_order_paid( $balance_payable_order ) ) :
                    ?>
                    <div class="view_next_payable_order" style="text-align:right;">
                        <a href="#"><?php esc_html_e( 'View Next Payable Order', 'sumopaymentplans' ); ?></a>
                        <p style="font-weight: bolder;display: none;">
                            <a href="<?php echo esc_url( get_admin_url( null, 'post.php?post=' . $balance_payable_order->get_id() . '&action=edit' ) ); ?>">#<?php echo esc_html( $balance_payable_order->get_order_number() ); ?></a>
                        </p>
                    </div>
                    <?php
                endif;
                ?>
                <p class="form-field form-field-wide">
                    <label for="customer_user">
                        <?php
                        /* translators: 1: next installment amount currency symbol  */
                        printf( esc_html__( 'Next Installment Amount: (%s)', 'sumopaymentplans' ), $initial_payment_order ? esc_html( get_woocommerce_currency_symbol( $initial_payment_order->get_currency() ) ) : esc_html( get_woocommerce_currency_symbol() )  );
                        ?>
                    </label>
                    <input type="text" name="_sumo_pp_next_installment_amount" value="<?php echo esc_attr( wc_format_decimal( $payment->get_prop( 'next_installment_amount' ), '' ) ); ?>" data-allow_clear="true" readonly/>
                </p>
            </div>
            <div class="order_data_column">
                <h4>
                    <?php esc_html_e( 'Billing Details', 'sumopaymentplans' ); ?>
                </h4>
                <div class="address">
                    <?php
                    if ( $initial_payment_order && $initial_payment_order->get_formatted_billing_address() ) {
                        echo '<p><strong>' . esc_html__( 'Address', 'sumopaymentplans' ) . ':</strong>' . wp_kses( $initial_payment_order->get_formatted_billing_address(), array( 'br' => array() ) ) . '</p>';
                    } else {
                        echo '<p class="none_set"><strong>' . esc_html__( 'Address', 'sumopaymentplans' ) . ':</strong> ' . esc_html__( 'No billing address set.', 'sumopaymentplans' ) . '</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="order_data_column">
                <h4>
                    <?php esc_html_e( 'Shipping Details', 'sumopaymentplans' ); ?>
                </h4>
                <div class="address">
                    <?php
                    if ( $initial_payment_order && $initial_payment_order->get_formatted_shipping_address() ) {
                        echo '<p><strong>' . esc_html__( 'Address', 'sumopaymentplans' ) . ':</strong>' . wp_kses( $initial_payment_order->get_formatted_shipping_address(), array( 'br' => array() ) ) . '</p>';
                    } else {
                        echo '<p class="none_set"><strong>' . esc_html__( 'Address', 'sumopaymentplans' ) . ':</strong> ' . esc_html__( 'No shipping address set.', 'sumopaymentplans' ) . '</p>';
                    }
                    ?>
                </div>
            </div>                    
        </div>
        <div class="clear"></div>
    </div>
</div>
