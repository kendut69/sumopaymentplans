<?php
/**
 * HTML Admin Payment Installments details.
 * 
 * Shows the details of a particular payment installments details on the admin screen.
 */
defined( 'ABSPATH' ) || exit;

$actual_payments_date    = $payment->get_prop( 'actual_payments_date' );
$scheduled_payments_date = $payment->get_prop( 'scheduled_payments_date' );
$modified_payment_dates  = $payment->get_prop( 'modified_expected_payment_dates' );
$initial_payment_order   = _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() );
$balance_paid_orders     = $payment->get_balance_paid_orders();

$columns = apply_filters( 'sumopaymentplans_admin_payment_installments_columns', array(
    'installment-payment-of-product'             => __( 'Payments', 'sumopaymentplans' ),
    'installment-amount'                         => __( 'Installment Amount', 'sumopaymentplans' ),
    'installment-expected-payment-date'          => __( 'Expected Payment Date', 'sumopaymentplans' ),
    'installment-modified-expected-payment-date' => __( 'Modified Expected Payment Date', 'sumopaymentplans' ),
    'installment-actual-payment-date'            => __( 'Actual Payment Date', 'sumopaymentplans' ),
    'installment-order-number'                   => __( 'Order Number', 'sumopaymentplans' ),
        ) );

if ( ! $payment->is_expected_payment_dates_modified() || $payment->has_status( 'completed' ) ) {
    unset( $columns[ 'installment-modified-expected-payment-date' ] );
}
?>
<div class="sumo-pp-installment-orders-inside">
    <table class="widefat fixed striped sumo-pp-installment_orders">
        <thead>
            <tr>
                <?php foreach ( $columns as $column_key => $column_name ) : ?>
                    <th><?php echo esc_html( $column_name ); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php do_action( 'sumopaymentplans_admin_payment_installments_table_row_start', $payment, $initial_payment_order ); ?>
            <?php
            if ( 'pay-in-deposit' === $payment->get_payment_type() ) {
                $paid_order_id       = isset( $balance_paid_orders[ 0 ] ) ? absint( $balance_paid_orders[ 0 ] ) : 0;
                $is_installment_paid = $paid_order_id > 0;
                ?>
                <tr>
                    <?php foreach ( $columns as $column_key => $column_name ) : ?>
                        <td>
                            <?php
                            switch ( $column_key ) {
                                case 'installment-payment-of-product':
                                    if ( 'order' === $payment->get_product_type() ) {
                                        if ( $is_installment_paid ) {
                                            /* translators: 1: paid order url 2: product name */
                                            printf( wp_kses_post( __( '<a href="%1$s">Installment #1 of %2$s</a>', 'sumopaymentplans' ) ), esc_url( admin_url( "post.php?post={$paid_order_id}&action=edit" ) ), wp_kses_post( $payment->get_formatted_product_name( array( 'page' => 'admin' ) ) ) );
                                        } else {
                                            /* translators: 1: product name */
                                            printf( wp_kses_post( __( 'Installment #1 of %s', 'sumopaymentplans' ) ), wp_kses_post( $payment->get_formatted_product_name( array( 'page' => 'admin' ) ) ) );
                                        }
                                    } elseif ( $is_installment_paid ) {
                                        /* translators: 1: paid order url 2: product name */
                                        printf( wp_kses_post( __( '<a href="%1$s">Installment #1 of %2$s</a>&nbsp;&nbsp;x%3$s', 'sumopaymentplans' ) ), esc_url( admin_url( "post.php?post={$paid_order_id}&action=edit" ) ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false, 'page' => 'admin' ) ) ), esc_html( $payment->get_product_qty() ) );
                                    } else {
                                        /* translators: 1: product name */
                                        printf( wp_kses_post( __( 'Installment #1 of %1$s&nbsp;&nbsp;x%2$s', 'sumopaymentplans' ) ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false, 'page' => 'admin' ) ) ), esc_html( $payment->get_product_qty() ) );
                                    }
                                    break;
                                case 'installment-amount':
                                    $installment_amount = wc_price( $payment->get_product_price() - $payment->get_down_payment( false ), array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) );

                                    if ( 'order' === $payment->get_product_type() ) {
                                        echo wp_kses_post( $installment_amount );
                                    } else {
                                        echo wp_kses_post( "{$installment_amount}&nbsp;&nbsp;x{$payment->get_product_qty()}" );
                                    }
                                    break;
                                case 'installment-expected-payment-date':
                                    $installment_date  = '';
                                    $next_payment_date = $payment->get_prop( 'next_payment_date' );

                                    if ( $next_payment_date ) {
                                        $installment_date = $next_payment_date;
                                    } elseif ( 'before' === $payment->get_pay_balance_type() ) {
                                        $installment_date = _sumo_pp_get_timestamp( $payment->get_pay_balance_before() );
                                    } elseif ( ! $payment->has_status( 'await_aprvl' ) && $payment->get_pay_balance_after() > 0 ) {
                                        $installment_date = _sumo_pp_get_timestamp( "+{$payment->get_pay_balance_after()} days", _sumo_pp_get_timestamp( $payment->get_prop( 'payment_start_date' ) ) );
                                    }

                                    if ( '' !== $installment_date ) {
                                        echo esc_html( _sumo_pp_get_date_to_display( $installment_date, 'admin' ) );
                                    }

                                    if ( 0 === $paid_order_id ) {
                                        if ( '' === $installment_date ) {
                                            echo '<input class="expected_payment_date" type="text" name="expected_payment_date" value=""/>';
                                        } else {
                                            echo '<p><a href="#" class="edit-installment-date">' . esc_html__( 'Edit', 'sumopaymentplans' ) . '</a><input class="expected_payment_date" type="text" style="display:none;" name="expected_payment_date" value="' . esc_attr( _sumo_pp_get_date( $installment_date ) ) . '"/></p>';
                                        }
                                    }
                                    break;
                                case 'installment-actual-payment-date':
                                    if ( ! empty( $actual_payments_date[ 0 ] ) ) {
                                        echo esc_html( _sumo_pp_get_date_to_display( $actual_payments_date[ 0 ], 'admin' ) );
                                    } else {
                                        echo '--';
                                    }
                                    break;
                                case 'installment-order-number':
                                    if ( $is_installment_paid ) {
                                        /* translators: 1: paid order url 2: paid order ID */
                                        printf( wp_kses_post( __( '<a href="%1$s">#%2$s</a><p>Paid</p>', 'sumopaymentplans' ) ), esc_url( admin_url( "post.php?post={$paid_order_id}&action=edit" ) ), esc_html( $paid_order_id ) );
                                    } else {
                                        echo '--';
                                    }
                                    break;
                                default:
                                    if ( has_action( 'sumopaymentplans_admin_deposit_payment_installments_column_' . $column_key ) ) {
                                        do_action( 'sumopaymentplans_admin_deposit_payment_installments_column_' . $column_key, $payment );
                                    }
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php
            } elseif ( is_array( $payment->get_prop( 'payment_schedules' ) ) ) {
                foreach ( $payment->get_prop( 'payment_schedules' ) as $installment => $scheduled_installment ) {
                    $paid_order_id       = isset( $balance_paid_orders[ $installment ] ) ? absint( $balance_paid_orders[ $installment ] ) : 0;
                    $is_installment_paid = $paid_order_id > 0;
                    ?>
                    <tr>
                        <?php foreach ( $columns as $column_key => $column_name ) : ?>
                            <td>
                                <?php
                                switch ( $column_key ) {
                                    case 'installment-payment-of-product':
                                        $payment_count = $installment;
                                        ++ $payment_count;
                                        $payment_count = apply_filters( 'sumopaymentplans_installment_count', $payment_count );

                                        if ( 'order' === $payment->get_product_type() ) {
                                            if ( $is_installment_paid ) {
                                                /* translators: 1: paid order url 2: payment count 3: product name */
                                                printf( wp_kses_post( __( '<a href="%1$s">Installment #%2$s of %3$s</a>', 'sumopaymentplans' ) ), esc_url( admin_url( "post.php?post={$paid_order_id}&action=edit" ) ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name( array( 'page' => 'admin' ) ) ) );
                                            } else {
                                                /* translators: 1: payment count 2: product name */
                                                printf( wp_kses_post( __( 'Installment #%1$s of %2$s', 'sumopaymentplans' ) ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name( array( 'page' => 'admin' ) ) ) );
                                            }
                                        } elseif ( $is_installment_paid ) {
                                            /* translators: 1: paid order url 2: payment count 3: product name 4: product qty */
                                            printf( wp_kses_post( __( '<a href="%1$s">Installment #%2$s of %3$s</a>&nbsp;&nbsp;x%4$s', 'sumopaymentplans' ) ), esc_url( admin_url( "post.php?post={$paid_order_id}&action=edit" ) ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false, 'page' => 'admin' ) ) ), esc_html( $payment->get_product_qty() ) );
                                        } else {
                                            /* translators: 1: payment count 2: product name 3: product qty */
                                            printf( wp_kses_post( __( 'Installment #%1$s of %2$s&nbsp;&nbsp;x%3$s', 'sumopaymentplans' ) ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false, 'page' => 'admin' ) ) ), esc_html( $payment->get_product_qty() ) );
                                        }
                                        break;
                                    case 'installment-amount':
                                        if ( isset( $scheduled_installment[ 'scheduled_payment' ] ) ) {
                                            if ( 'fixed-price' === $payment->get_plan_price_type() ) {
                                                $installment_amount          = $scheduled_installment[ 'scheduled_payment' ];
                                                $formated_installment_amount = wc_price( $installment_amount, array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) );
                                            } else {
                                                $installment_amount          = $payment->get_product_price() * floatval( $scheduled_installment[ 'scheduled_payment' ] ) / 100;
                                                $formated_installment_amount = wc_price( $installment_amount, array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) );
                                            }
                                        } else {
                                            $installment_amount          = '0';
                                            $formated_installment_amount = wc_price( $installment_amount, array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) );
                                        }

                                        if ( 'order' === $payment->get_product_type() ) {
                                            echo wp_kses_post( $formated_installment_amount );
                                        } else {
                                            echo wp_kses_post( "{$formated_installment_amount}&nbsp;&nbsp;x{$payment->get_product_qty()}" );
                                        }

                                        if ( 0 === $paid_order_id && ! ( $payment->get_next_installment_count() === $installment && $payment->balance_payable_order_exists() ) ) {
                                            echo '<p><a href="#" class="edit-installment-amount">' . esc_html__( 'Edit', 'sumopaymentplans' ) . '</a><input class="expected_installment_amount wc_input_price" type="text" style="display:none;" name="expected_installment_amount[' . esc_attr( $installment ) . ']" value="' . esc_attr( $installment_amount ) . '"/></p>';
                                        }
                                        break;
                                    case 'installment-expected-payment-date':
                                        if ( ! empty( $scheduled_payments_date[ $installment ] ) ) {
                                            echo esc_html( _sumo_pp_get_date_to_display( $scheduled_payments_date[ $installment ], 'admin' ) );

                                            if ( 0 === $paid_order_id ) {
                                                echo '<p><a href="#" class="edit-installment-date">' . esc_html__( 'Edit', 'sumopaymentplans' ) . '</a><input class="expected_payment_date" type="text" style="display:none;" name="expected_payment_date[' . esc_attr( $installment ) . ']" value="' . esc_attr( _sumo_pp_get_date( $scheduled_payments_date[ $installment ] ) ) . '"/></p>';
                                            }
                                        } else {
                                            echo '--';
                                        }
                                        break;
                                    case 'installment-modified-expected-payment-date':
                                        if ( ! empty( $modified_payment_dates[ $installment ] ) ) {
                                            echo esc_html( _sumo_pp_get_date_to_display( $modified_payment_dates[ $installment ], 'admin' ) );
                                        } else {
                                            echo '--';
                                        }
                                        break;
                                    case 'installment-actual-payment-date':
                                        if ( ! empty( $actual_payments_date[ $installment ] ) ) {
                                            echo esc_html( _sumo_pp_get_date_to_display( $actual_payments_date[ $installment ], 'admin' ) );
                                        } else {
                                            echo '--';
                                        }
                                        break;
                                    case 'installment-order-number':
                                        if ( $is_installment_paid ) {
                                            /* translators: 1: paid order url 2: paid order ID */
                                            printf( wp_kses_post( __( '<a href="%1$s">#%2$s</a><p>Paid</p>', 'sumopaymentplans' ) ), esc_url( admin_url( "post.php?post={$paid_order_id}&action=edit" ) ), esc_html( $paid_order_id ) );
                                        } else {
                                            echo '--';
                                        }
                                        break;
                                    default:
                                        if ( has_action( 'sumopaymentplans_admin_payment_plan_installments_column_' . $column_key ) ) {
                                            do_action( 'sumopaymentplans_admin_payment_plan_installments_column_' . $column_key, $payment, $installment, $scheduled_installment );
                                        }
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php
                }
            }
            ?>
            <?php do_action( 'sumopaymentplans_admin_payment_installments_table_row_end', $payment, $initial_payment_order ); ?>
        </tbody>
    </table>
</div>
