<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Manage payment order.
 * 
 * @class SUMO_PP_Order_Manager
 * @package Class
 */
class SUMO_PP_Order_Manager {

    /**
     * Get the cached order ID which are in progress.
     * 
     * @var array
     */
    protected static $payment_processing = array();

    /**
     * Get the cached order ID which are in payment creation.
     *
     * @var array
     */
    protected static $payment_creation = array();

    /**
     * The single instance of the class.
     */
    protected static $instance = null;

    /**
     * Create instance for SUMO_PP_Order_Manager.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Init SUMO_PP_Order_Manager.
     */
    public function init() {
        add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'checkout_update_order_meta' ), 9999, 1 );
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_update_order_meta' ), 9999, 2 );
        add_action( 'woocommerce_before_pay_action', array( $this, 'checkout_update_order_meta' ), 9999 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'create_new_payments' ), 15, 4 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'update_payments' ), 20, 4 );
        add_action( 'woocommerce_order_status_refunded', array( $this, 'upon_fully_refunded' ), 20 );
        add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'prevent_stock_reduction' ), 20, 2 );
        add_filter( 'woocommerce_order_is_pending_statuses', array( $this, 'get_pending_statuses' ), 20 );
    }

    /**
     * Save checkout information
     *
     * @param int $order_id The Order post ID
     * @param array $posted
     */
    public function checkout_update_order_meta( $order_id, $posted = array() ) {
        $order = is_object( $order_id ) ? $order_id : wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        if ( ! _sumo_pp_order_has_payment_data( $order ) ) {
            return;
        }

        if ( isset( $posted[ 'payment_method' ] ) ) {
            $payment_method = $posted[ 'payment_method' ];
        } else {
            $payment_method = isset( $_REQUEST[ 'payment_method' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'payment_method' ] ) ) : '';
        }

        $order->update_meta_data( '_sumo_pp_payment_mode', _sumo_pp()->gateways->get_chosen_mode_of_payment( $payment_method ) );
        $order->save();
    }

    /**
     * Create new payment orders after the customer successfully placed the initial payment order.
     * Fire only for the Initial Payment order.
     * 
     * @param int $order_id The Order post ID
     * @param string $old_order_status
     * @param string $new_order_status
     */
    public function create_new_payments( $order_id, $old_order_status, $new_order_status, $order ) {
        if (
                $order &&
                apply_filters( 'sumopaymentplans_add_new_payments', true, $order_id, $old_order_status, $new_order_status ) &&
                _sumo_pp_is_parent_order( $order ) &&
                _sumo_pp_order_has_payment_data( $order ) &&
                ! _sumo_pp_is_payment_order( $order ) &&
                ! isset( self::$payment_creation[ $order_id ] )
        ) {
            self::$payment_creation[ $order_id ] = true;

            do_action( 'sumopaymentplans_before_adding_new_payments', $order_id, $old_order_status, $new_order_status );

            if ( _sumo_pp_is_orderpp_created_by_multiple( $order ) ) {
                $payment_id = $this->add_new_payment( $order, $order->get_meta( '_sumo_pp_payment_data', true ) );

                if ( $payment_id ) {
                    $order->add_meta_data( '_sumo_pp_payment_id', $payment_id, true );
                }
            } else {
                foreach ( $order->get_items() as $item ) {
                    $payment_id = ! empty( $item[ '_sumo_pp_payment_data' ] ) ? $this->add_new_payment( $order, $item[ '_sumo_pp_payment_data' ] ) : 0;

                    if ( $payment_id ) {
                        $item->add_meta_data( '_sumo_pp_payment_id', $payment_id, true );
                    }
                }
            }

            $order->save();
        }

        do_action( 'sumopaymentplans_after_new_payments_added', $order_id, $old_order_status, $new_order_status );
    }

    /**
     * Add new Payment.
     *
     * @param mixed $order
     * @param mixed $payment_data
     */
    public function add_new_payment( $order, $payment_data ) {

        try {
            $payment_id = wp_insert_post( array(
                'post_type'     => 'sumo_pp_payments',
                'post_date'     => _sumo_pp_get_date(),
                'post_date_gmt' => _sumo_pp_get_date(),
                'post_status'   => '_sumo_pp_pending',
                'post_author'   => 1,
                'post_title'    => __( 'Payments', 'sumopaymentplans' ),
                    ), true );

            if ( is_wp_error( $payment_id ) ) {
                throw new Exception( $payment_id->get_error_message() );
            }

            $payment = _sumo_pp_get_payment( $payment_id );

            if ( ! empty( $payment_data ) ) {
                foreach ( $payment_data as $meta_key => $value ) {
                    if ( ! is_null( $value ) && '' !== $value ) {
                        if ( 'payment_product_props' === $meta_key ) {
                            foreach ( $value as $_meta_key => $_value ) {
                                if ( ! is_null( $_value ) && '' !== $_value ) {
                                    $payment->add_prop( $_meta_key, $_value );
                                }
                            }
                        } else if ( 'payment_plan_props' === $meta_key ) {
                            foreach ( $value as $_meta_key => $_value ) {
                                if ( ! is_null( $_value ) && '' !== $_value ) {
                                    $payment->add_prop( $_meta_key, $_value );
                                }
                            }
                        } else {
                            $payment->add_prop( $meta_key, $value );
                        }
                    }
                }
            }

            $payment->add_prop( 'payment_method', $order->get_payment_method() );
            $payment->add_prop( 'payment_method_title', $order->get_payment_method_title() );
            $payment->add_prop( 'initial_payment_order_id', $order->get_id() );
            $payment->add_prop( 'customer_id', $order->get_customer_id() );
            $payment->add_prop( 'customer_email', $order->get_billing_email() );
            $payment->add_prop( 'payment_number', $payment->set_payment_serial_number() );
            $payment->add_prop( 'version', SUMO_PP_PLUGIN_VERSION );

            $order->add_meta_data( 'is_sumo_pp_order', 'yes', true );
            $order->save();

            $payment = _sumo_pp_get_payment( $payment->id );
            $payment->add_payment_note( __( 'New payment order created.', 'sumopaymentplans' ), 'pending', __( 'New Payment Order', 'sumopaymentplans' ) );

            do_action( 'sumopaymentplans_new_payment_order', $payment, $order );
        } catch ( Exception $e ) {
            return 0;
        }

        return $payment->id;
    }

    /**
     * Update each payment data based upon Order status.
     *
     * @param int $order_id The Order post ID
     * @param string $old_order_status
     * @param string $new_order_status
     */
    public function update_payments( $order_id, $old_order_status, $new_order_status, $order ) {
        $order = wc_get_order( $order_id );

        if ( ! empty( self::$payment_processing[ $order->get_id() ] ) ) {
            return; // Bail if the order is in progress already.
        }

        if ( 'yes' === $order->get_meta( '_sumo_pp_order_paid', true ) ) {
            return;
        }

        //Check whether this order is already placed or refunded fully
        $is_valid_old_order_status = apply_filters( 'sumopaymentplans_is_valid_old_order_status_to_update_payment', ( ! in_array( $old_order_status, array( 'completed', 'processing' ) ) ), $old_order_status, $order_id );
        if ( 'refunded' === $new_order_status || ! $is_valid_old_order_status ) {
            return;
        }

        $payments = _sumo_pp()->query->get( array(
            'type'       => 'sumo_pp_payments',
            'status'     => array_keys( _sumo_pp_get_payment_statuses() ),
            'meta_key'   => '_initial_payment_order_id',
            'meta_value' => _sumo_pp_get_parent_order_id( $order ),
                ) );

        do_action( 'sumopaymentplans_before_update_payments_for_order', $payments, $order );

        self::$payment_processing[ $order->get_id() ] = true;
        $order_paid                                   = false;

        foreach ( $payments as $payment_id ) {
            $payment = _sumo_pp_get_payment( $payment_id );
            //may be balance payment is paying.
            if ( _sumo_pp_is_child_order( $order ) ) {
                //Check which balance payment is paying from the parent order.
                if ( $order->get_id() == $payment->get_balance_payable_order_id() || $order->get_id() == $payment->get_balance_payable_order_id( 'my_account' ) ) {
                    //Check payment status is valid to change.
                    if ( ! $payment->has_status( array( 'pending', 'in_progress', 'overdue', 'await_cancl', 'cancelled', 'failed', 'pendng_auth' ) ) ) {
                        continue;
                    }

                    //Proceed this payment based upon the Balance payment Order status.
                    switch ( apply_filters( 'sumopaymentplans_order_status_to_update_payment', $new_order_status, $payment->id, $order->get_id(), 'balance-payment-order' ) ) {
                        case 'pending':
                        case 'on-hold':
                            if ( 'auto' === $payment->get_payment_mode() ) {
                                /* translators: 1: order id */
                                $payment->add_payment_note( sprintf( __( 'Awaiting balance payment order#%s to complete automatically.', 'sumopaymentplans' ), $order->get_order_number() ), 'pending', __( 'Waiting For Balance Payment', 'sumopaymentplans' ) );
                            } else {
                                /* translators: 1: order id */
                                $payment->add_payment_note( sprintf( __( 'Awaiting balance payment order#%s to complete manually.', 'sumopaymentplans' ), $order->get_order_number() ), 'pending', __( 'Waiting For Balance Payment', 'sumopaymentplans' ) );
                            }

                            do_action( 'sumopaymentplans_payment_in_pending', $payment->id, $order->get_id(), 'balance-payment-order' );
                            break;
                        case 'completed':
                        case 'processing':
                            $payment->update_as_paid_order( $order->get_id() );

                            if ( $payment->has_next_installment() ) {
                                $payment->process_balance_payment( $order );
                            } else {
                                $payment->payment_complete( $order );
                            }

                            $order_paid = true;
                            break;
                        case 'failed':
                        case 'cancelled':
                            /* translators: 1: order id 2: order status */
                            $payment->add_payment_note( sprintf( __( 'Error in receiving payment from the user. Balance payable order#%1$s has been %2$s.', 'sumopaymentplans' ), $order->get_order_number(), $new_order_status ), 'failure', __( 'Balance Payment Failed', 'sumopaymentplans' ) );
                            break;
                    }
                }
                //may be Deposit Payment is paying.
            } else if ( _sumo_pp_is_parent_order( $order ) && '' === $payment->get_prop( 'payment_start_date' ) ) {
                //Proceed this payment based upon the Initial payment Order status.
                switch ( apply_filters( 'sumopaymentplans_order_status_to_update_payment', $new_order_status, $payment->id, $order->get_id(), 'initial-payment-order' ) ) {
                    case 'pending':
                    case 'on-hold':
                        $payment->update_status( 'pending' );
                        $payment->add_prop( 'next_installment_amount', $payment->get_next_installment_amount() );
                        $payment->add_prop( 'remaining_payable_amount', $payment->get_remaining_payable_amount() );
                        $payment->add_prop( 'remaining_installments', $payment->get_remaining_installments() );

                        if ( 'auto' === $payment->get_payment_mode() ) {
                            /* translators: 1: order id */
                            $payment->add_payment_note( sprintf( __( 'Awaiting initial payment order#%s to complete automatically.', 'sumopaymentplans' ), $order->get_order_number() ), 'pending', __( 'Waiting For Initial Payment', 'sumopaymentplans' ) );
                        } else {
                            /* translators: 1: order id */
                            $payment->add_payment_note( sprintf( __( 'Awaiting initial payment order#%s to complete manually.', 'sumopaymentplans' ), $order->get_order_number() ), 'pending', __( 'Waiting For Initial Payment', 'sumopaymentplans' ) );
                        }

                        do_action( 'sumopaymentplans_payment_in_pending', $payment->id, $order->get_id(), 'initial-payment-order' );
                        break;
                    case 'completed':
                    case 'processing':
                        if ( 'before' !== $payment->get_pay_balance_type() && 'after_admin_approval' === $payment->get_prop( 'activate_payment' ) ) {
                            $payment->update_status( 'await_aprvl' );
                            $payment->add_prop( 'next_installment_amount', $payment->get_next_installment_amount() );
                            $payment->add_prop( 'remaining_payable_amount', $payment->get_remaining_payable_amount() );
                            $payment->add_prop( 'remaining_installments', $payment->get_remaining_installments() );

                            if ( 'auto' === $payment->get_payment_mode() ) {
                                $payment->add_payment_note( __( 'Awaiting Admin to approve for future payments to be charged as automatic.', 'sumopaymentplans' ), 'pending', __( 'Awaiting Admin Approval', 'sumopaymentplans' ) );
                            } else {
                                $payment->add_payment_note( __( 'Awaiting Admin to approve the payment.', 'sumopaymentplans' ), 'pending', __( 'Awaiting Admin Approval', 'sumopaymentplans' ) );
                            }

                            do_action( 'sumopaymentplans_payment_awaiting_approval', $payment->id, $order->get_id(), 'initial-payment-order' );
                        } else if ( $payment->awaiting_initial_payment() ) {

                            $payment->process_initial_payment( array(
                                'content' => __( 'Payment is synced. Awaiting for the initial payment.', 'sumopaymentplans' ),
                                'status'  => 'pending',
                                'message' => __( 'Awaiting Initial Payment', 'sumopaymentplans' ),
                                    ), false, 'pending' );
                        } else {
                            $payment->process_initial_payment();
                        }

                        $order_paid = true;
                        break;
                    case 'failed':
                        $payment->fail_payment( array(
                            /* translators: 1: order id */
                            'content' => sprintf( __( 'Failed to pay the initial payment of order#%s . Payment is failed.', 'sumopaymentplans' ), $order->get_order_number() ),
                            'status'  => 'failure',
                            'message' => __( 'Initial Payment Failed', 'sumopaymentplans' ),
                        ) );
                        break;
                    case 'cancelled':
                        $payment->cancel_payment( array(
                            /* translators: 1: order id */
                            'content' => sprintf( __( 'Failed to pay the initial payment of order#%s. Payment is cancelled.', 'sumopaymentplans' ), $order->get_order_number() ),
                            'status'  => 'failure',
                            'message' => __( 'Initial Payment Cancelled', 'sumopaymentplans' ),
                        ) );
                        break;
                }
            }
        }

        if ( $order_paid ) {
            $order->add_meta_data( '_sumo_pp_order_paid', 'yes', true ); //Since v6.4
            $order->save();
        }

        do_action( 'sumopaymentplans_after_update_payments_for_order', $payments, $order );

        self::$payment_processing = array();
    }

    /**
     * When parent order gets fully refunded then make sure to cancel the payments.
     * 
     * @param int $order_id The Order post ID
     */
    public function upon_fully_refunded( $order_id ) {
        $parent_order_id = _sumo_pp_get_parent_order_id( $order_id );
        if ( ! $parent_order_id ) {
            return;
        }

        $payments = _sumo_pp()->query->get( array(
            'type'       => 'sumo_pp_payments',
            'status'     => array_keys( _sumo_pp_get_payment_statuses() ),
            'meta_key'   => '_initial_payment_order_id',
            'meta_value' => $parent_order_id,
                ) );

        foreach ( $payments as $payment_id ) {
            $payment = _sumo_pp_get_payment( $payment_id );

            if ( $payment->has_status( 'cancelled' ) ) {
                continue;
            }

            $payment->cancel_payment( array(
                /* translators: 1: order id */
                'content' => sprintf( __( 'Payment is cancelled since the order#%s is fully refunded.', 'sumopaymentplans' ), $parent_order_id ),
                'status'  => 'failure',
                'message' => __( 'Payment Cancelled', 'sumopaymentplans' ),
            ) );
        }
    }

    /**
     * To prevent stock reduction for balance payment.
     * 
     * @param bool $bool
     * @param WC_Order $order
     * @return bool
     */
    public function prevent_stock_reduction( $bool, $order ) {
        $order = _sumo_pp_maybe_get_order_instance( $order );

        if ( _sumo_pp_is_balance_payment_order( $order ) && _sumo_pp_get_payment( $order->get_meta( '_payment_id', true ) ) ) {
            return false;
        }

        return $bool;
    }

    /**
     * Get the statuses valid to pay for balance payment.
     * 
     * @param array $statuses
     * @return array
     */
    public function get_pending_statuses( $statuses ) {
        if ( _sumo_pp_get_balance_payable_order_in_pay_for_order_page() > 0 ) {
            $statuses[] = 'failed';
        }

        return $statuses;
    }

    /**
     * Create Balance payable Order.
     *
     * @param object $payment The Payment post.
     * @param array $args
     * @return int
     */
    public function create_balance_payable_order( $payment, $args = array() ) {
        $initial_payment_order = _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() );
        if ( ! $initial_payment_order ) {
            return 0;
        }

        $args = wp_parse_args( $args, array(
            'next_installment_amount' => floatval( $payment->get_prop( 'next_installment_amount' ) ),
            'next_installment_count'  => $payment->get_next_installment_count(),
            'remaining_installments'  => absint( $payment->get_prop( 'remaining_installments' ) ),
            'installments_included'   => 1,
            'created_via'             => 'default',
            'add_default_note'        => true,
            'custom_note'             => '',
                ) );

        $args[ 'remaining_payable_amount' ]   = $payment->get_remaining_payable_amount( 1 + $args[ 'next_installment_count' ] );
        $charge_shipping_during_final_payment = 'final-payment' === $payment->charge_shipping_during() && 1 === $payment->get_remaining_installments();

        do_action( 'sumopaymentplans_before_creating_balance_payable_order', $initial_payment_order, $payment, $args );

        //Create an Order.
        $balance_payable_order = wc_create_order( array(
            'created_via' => 'sumo_pp',
            'parent'      => $initial_payment_order->get_id(),
                ) );

        if ( is_wp_error( $balance_payable_order ) ) {
            $payment->add_payment_note( __( 'Error while creating balance payable order.', 'sumopaymentplans' ), 'failure', __( 'Balance Payable Order Creation Error', 'sumopaymentplans' ) );
            return 0;
        }

        //Set billing address
        $this->set_address_details( $initial_payment_order, $balance_payable_order, 'billing' );
        //Set shipping address
        $this->set_address_details( $initial_payment_order, $balance_payable_order, 'shipping' );
        //Set order meta
        $this->set_order_details( $initial_payment_order, $balance_payable_order );
        //Add Payment items
        $this->add_order_item( $initial_payment_order, $balance_payable_order, $payment, $args );
        //Set shippging methods
        if ( $charge_shipping_during_final_payment ) {
            $this->set_shipping_methods( $initial_payment_order, $balance_payable_order, $payment );
        }
        //Set tax
        $this->set_tax( $initial_payment_order, $balance_payable_order );

        $balance_payable_order->save();
        $balance_payable_order->calculate_totals( true );
        $balance_payable_order->update_status( 'pending' );

        if ( _sumo_pp_is_orderpp_created_by_multiple( $balance_payable_order ) ) {
            $payment_data   = $balance_payable_order->get_meta( '_sumo_pp_payment_data', true );
            $payable_amount = $payment_data[ 'payable_amount' ];

            if ( $charge_shipping_during_final_payment ) {
                $payable_amount += $initial_payment_order->get_shipping_total() + $initial_payment_order->get_shipping_tax();
            }

            $balance_payable_order->set_total( wc_format_decimal( $payable_amount ) );
        }

        $balance_payable_order->add_meta_data( 'is_sumo_pp_order', 'yes', true );
        $balance_payable_order->add_meta_data( '_payment_id', $payment->id, true );

        foreach ( $args as $key => $val ) {
            if ( 'note' !== $key ) {
                $balance_payable_order->add_meta_data( SUMO_PP_PLUGIN_PREFIX . $key, $val );
            }
        }

        $balance_payable_order->save();

        if ( 'default' === $args[ 'created_via' ] ) {
            $payment->add_prop( 'balance_payable_order_id', $balance_payable_order->get_id() );
        }

        $payment->update_prop( 'balance_payable_order_props', array(
            $payment->get_balance_payable_order_id() => array( 'created_via' => 'default' ),
                ) + array(
            $balance_payable_order->get_id() => array( 'created_via' => $args[ 'created_via' ] ),
                ) );

        if ( $args[ 'add_default_note' ] ) {
            /* translators: 1: order id */
            $payment->add_payment_note( sprintf( __( 'Balance payable order#%s is created.', 'sumopaymentplans' ), $balance_payable_order->get_order_number() ), 'pending', __( 'Balance Payable Order Created', 'sumopaymentplans' ) );
        }

        if ( ! empty( $args[ 'custom_note' ] ) ) {
            $payment->add_payment_note( $args[ 'custom_note' ], 'pending', __( 'Balance Payable Order Created', 'sumopaymentplans' ) );
        }

        do_action( 'sumopaymentplans_balance_payable_order_created', $balance_payable_order->get_id(), $initial_payment_order->get_id(), $payment );
        return $balance_payable_order->get_id();
    }

    /**
     * Extract billing and shipping information from Initial payment Order and set in Balance payable Order 
     */
    public function set_address_details( $initial_payment_order, &$balance_payable_order, $type ) {
        $data = array(
            'first_name' => array( 'billing', 'shipping' ),
            'last_name'  => array( 'billing', 'shipping' ),
            'company'    => array( 'billing', 'shipping' ),
            'address_1'  => array( 'billing', 'shipping' ),
            'address_2'  => array( 'billing', 'shipping' ),
            'city'       => array( 'billing', 'shipping' ),
            'postcode'   => array( 'billing', 'shipping' ),
            'country'    => array( 'billing', 'shipping' ),
            'state'      => array( 'billing', 'shipping' ),
            'email'      => array( 'billing' ),
            'phone'      => array( 'billing' ),
        );

        foreach ( $data as $key => $applicable_to ) {
            $value = '';

            if ( is_callable( array( $initial_payment_order, "get_{$type}_{$key}" ) ) ) {
                $value = $initial_payment_order->{"get_{$type}_{$key}"}();
            }

            if ( '' === $value ) {
                if ( is_callable( array( $initial_payment_order, "get_billing_{$key}" ) ) ) {
                    $value = $initial_payment_order->{"get_billing_{$key}"}();
                }
            }

            if ( is_callable( array( $balance_payable_order, "set_{$type}_{$key}" ) ) ) {
                $balance_payable_order->{"set_{$type}_{$key}"}( $value );
            }
        }
    }

    /**
     * Extract Initial payment Order details other than shipping/billing and set in Balance payable Order 
     */
    public function set_order_details( $initial_payment_order, &$balance_payable_order ) {
        $data = array(
            'version'            => 'order_version',
            'currency'           => 'order_currency',
            'order_key'          => 'order_key',
            'shipping_total'     => 'order_shipping',
            'shipping_tax'       => 'order_shipping_tax',
            'total_tax'          => 'order_tax',
            'customer_id'        => 'customer_user',
            'prices_include_tax' => 'prices_include_tax',
        );

        foreach ( $data as $method_key => $meta_key ) {
            $value = '';

            if ( is_callable( array( $initial_payment_order, "get_{$method_key}" ) ) ) {
                $value = $initial_payment_order->{"get_{$method_key}"}();
            }

            if ( is_callable( array( $balance_payable_order, "set_{$method_key}" ) ) ) {
                $balance_payable_order->{"set_{$method_key}"}( $value );
            }
        }
    }

    /**
     * Add Payment order Item in balance payable Order.
     */
    public function add_order_item( $initial_payment_order, &$balance_payable_order, $payment, $args ) {
        $new_item_id        = false;
        $item_meta          = false;
        $payment_data       = array();
        $prices_include_tax = 'yes' === get_option( 'woocommerce_prices_include_tax' );

        if ( 'order' === $payment->get_product_type() ) {
            if ( ! is_array( $payment->get_prop( 'order_items' ) ) ) {
                return;
            }

            if ( _sumo_pp_is_orderpp_created_by_multiple( $initial_payment_order ) ) {
                $payment_data = $initial_payment_order->get_meta( '_sumo_pp_payment_data', true );

                foreach ( $initial_payment_order->get_items() as $_item ) {
                    $product_id = $_item[ 'variation_id' ] > 0 ? $_item[ 'variation_id' ] : $_item[ 'product_id' ];
                    $_product   = wc_get_product( $product_id );

                    if ( ! $_product ) {
                        continue;
                    }

                    $new_item_id = $balance_payable_order->add_product( $_product, $_item[ 'qty' ], array(
                        'subtotal' => $this->get_price_excluding_tax(
                                array(
                                    'product' => $_product,
                                    'qty'     => 1,
                                    'price'   => ( $prices_include_tax ? $_item[ 'line_subtotal' ] + $_item[ 'subtotal_tax' ] : $_item[ 'line_subtotal' ] ),
                                    'order'   => $initial_payment_order,
                                    'payment' => $payment
                                )
                        ),
                        'total'    => $this->get_price_excluding_tax(
                                array(
                                    'product' => $_product,
                                    'qty'     => 1,
                                    'price'   => ( $prices_include_tax ? $_item[ 'line_total' ] + $_item[ 'line_tax' ] : $_item[ 'line_total' ] ),
                                    'order'   => $initial_payment_order,
                                    'payment' => $payment
                                )
                        ),
                            ) );

                    if ( isset( $_item[ 'item_meta' ] ) && ! empty( $_item[ 'item_meta' ] ) ) {
                        foreach ( $_item[ 'item_meta' ] as $key => $value ) {
                            wc_add_order_item_meta( $new_item_id, $key, $value, true );
                        }
                    }

                    do_action( 'sumopaymentplans_balance_payable_order_line_item_added', $new_item_id, $balance_payable_order, $initial_payment_order, $payment );
                }

                $new_item_id = false;

                $balance_payable_order->add_meta_data( 'is_sumo_pp_orderpp', 'yes' );
            } else {
                $order_item_data = array();
                foreach ( $payment->get_prop( 'order_items' ) as $product_id => $data ) {
                    $_product = wc_get_product( $product_id );

                    if ( ! $_product ) {
                        continue;
                    }

                    $order_item_data[] = array( 'product' => $_product, 'order_item' => array( 'quantity' => $data[ 'qty' ] ) );
                }

                $item_data   = current( $order_item_data );
                $new_item_id = _sumo_pp()->orderpp->add_items_to_order( $balance_payable_order, $item_data[ 'product' ], array(
                    'line_total'       => wc_format_decimal( $args[ 'next_installment_amount' ] ),
                    'order_item_data'  => $order_item_data,
                    'add_payment_meta' => false,
                        ) );

                foreach ( $initial_payment_order->get_items() as $_item ) {
                    if ( isset( $_item[ 'item_meta' ] ) ) {
                        $item_meta = $_item[ 'item_meta' ];
                    }

                    if ( isset( $_item[ '_sumo_pp_payment_data' ] ) ) {
                        $payment_data = $_item[ '_sumo_pp_payment_data' ];
                    }
                    break;
                }

                do_action( 'sumopaymentplans_balance_payable_order_line_item_added', $new_item_id, $balance_payable_order, $initial_payment_order, $payment );
            }
        } else {
            foreach ( $initial_payment_order->get_items() as $_item ) {
                $product_id = $_item[ 'variation_id' ] > 0 ? $_item[ 'variation_id' ] : $_item[ 'product_id' ];

                if ( $product_id == $payment->get_product_id() ) {

                    $_product = wc_get_product( $product_id );
                    if ( ! $_product ) {
                        break;
                    }

                    $product_qty = $payment->get_product_qty() ? $payment->get_product_qty() : 1;
                    $line_total  = wc_format_decimal( $args[ 'next_installment_amount' ] / $product_qty );

                    $new_item_id = $balance_payable_order->add_product( $_product, $product_qty, array(
                        'subtotal' => $this->get_price_excluding_tax(
                                array(
                                    'product' => $_product,
                                    'qty'     => $product_qty,
                                    'price'   => $line_total,
                                    'order'   => $initial_payment_order,
                                    'payment' => $payment
                                )
                        ),
                        'total'    => $this->get_price_excluding_tax(
                                array(
                                    'product' => $_product,
                                    'qty'     => $product_qty,
                                    'price'   => $line_total,
                                    'order'   => $initial_payment_order,
                                    'payment' => $payment
                                )
                        ),
                            ) );

                    if ( isset( $_item[ 'item_meta' ] ) ) {
                        $item_meta = $_item[ 'item_meta' ];
                    }

                    if ( isset( $_item[ '_sumo_pp_payment_data' ] ) ) {
                        $payment_data = $_item[ '_sumo_pp_payment_data' ];
                    }

                    do_action( 'sumopaymentplans_balance_payable_order_line_item_added', $new_item_id, $balance_payable_order, $initial_payment_order, $payment );
                    break;
                }
            }
        }

        if ( $payment_data ) {
            $next_of_next_installment_count             = 1 + $args[ 'next_installment_count' ];
            $payment_data[ 'installment_count' ]        = $args[ 'next_installment_count' ];
            $payment_data[ 'remaining_installments' ]   = $args[ 'remaining_installments' ];
            $payment_data[ 'remaining_payable_amount' ] = $args[ 'remaining_payable_amount' ];
            $payment_data[ 'payable_amount' ]           = $args[ 'next_installment_amount' ];
            $payment_data[ 'total_payable_amount' ]     = $payment->get_total_payable_amount();
            $payment_data[ 'next_installment_amount' ]  = $payment->get_next_installment_amount( $next_of_next_installment_count );
            $payment_data[ 'next_payment_date' ]        = $payment->get_next_payment_date( $next_of_next_installment_count );

            $balance_payable_order->update_meta_data( '_sumo_pp_payment_data', $payment_data );
        }

        if ( $new_item_id && ! is_wp_error( $new_item_id ) ) {
            wc_add_order_item_meta( $new_item_id, '_sumo_pp_payment_id', $payment->id, true );

            if ( 'payment-plans' === $payment->get_payment_type() ) {
                wc_add_order_item_meta( $new_item_id, __( 'Payment Plan', 'sumopaymentplans' ), $payment->get_plan( 'name' ), true );
            }

            $shipping_amount = _sumo_pp_maybe_get_shipping_amount_for_order( $initial_payment_order, $payment );
            wc_add_order_item_meta( $new_item_id, __( 'Total payable', 'sumopaymentplans' ), wc_price( $payment->get_total_payable_amount() + $shipping_amount[ 'for_total_payable' ], array( 'currency' => $initial_payment_order->get_currency() ) ), true );

            //Consider > 1 upon validate, since we excluding this unpaid order
            if ( $args[ 'remaining_installments' ] > 1 ) {
                $next_of_next_installment_count = 1 + $args[ 'next_installment_count' ];
                $due_date_label_deprecated      = str_replace( ':', '', get_option( SUMO_PP_PLUGIN_PREFIX . 'next_payment_date_label' ) );

                if ( $due_date_label_deprecated && false === strpos( $due_date_label_deprecated, '[sumo_pp_next_payment_date]' ) ) {
                    $due_date_label = $due_date_label_deprecated;
                } else {
                    $due_date_label = __( 'Next Payment Date', 'sumopaymentplans' );
                }

                wc_add_order_item_meta( $new_item_id, __( 'Next installment amount', 'sumopaymentplans' ), wc_price( $payment->get_next_installment_amount( $next_of_next_installment_count ), array( 'currency' => $initial_payment_order->get_currency() ) ), true );
                wc_add_order_item_meta( $new_item_id, $due_date_label, _sumo_pp_get_date_to_display( $payment->get_next_payment_date( $next_of_next_installment_count ) ), true );
            }
        }

        if ( ! empty( $item_meta ) ) {
            foreach ( $item_meta as $key => $value ) {
                if ( __( 'Next Payment Date', 'sumopaymentplans' ) === $key ) {
                    continue;
                }

                wc_add_order_item_meta( $new_item_id, $key, $value, true );
            }
        }
    }

    /**
     * Get Product Price Excluding Tax
     * 
     * @since 10.9.0
     * @param Array $args Arguments.
     */
    protected function get_price_excluding_tax( $args ) {
        $tax_based_on = get_option( 'woocommerce_tax_based_on' );
        $price_args = array(
            'qty'   => $args[ 'qty' ],
            'price' => $args[ 'price' ],
            /**
             * Get Order Object for excluding product price tax.
             * 
             * @since 10.9.0
             */
            'order' => apply_filters( 'sumopaymentplans_get_order_for_product_price_excluding_tax', ( 'base' === $tax_based_on ) ? '' : $args[ 'order' ], $args[ 'order' ], $args[ 'payment' ] ),
        );

        if ( ! empty( $price_args[ 'order' ] ) && is_a( $price_args[ 'order' ], 'WC_Order' ) ) {
            add_filter( 'woocommerce_apply_base_tax_for_local_pickup', '__return_false', 999 );
            add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false', 999 );
        }

        $price = wc_get_price_excluding_tax( $args[ 'product' ], $price_args );

        if ( ! empty( $price_args[ 'order' ] ) && is_a( $price_args[ 'order' ], 'WC_Order' ) ) {
            remove_filter( 'woocommerce_apply_base_tax_for_local_pickup', '_return_true', 999 );
            remove_filter( 'woocommerce_adjust_non_base_location_prices', '_return_true', 999 );
        }

        return $price;
    }

    /**
     * Extract shipping method from Initial payment Order and set in balance payable Order.
     */
    public function set_shipping_methods( $initial_payment_order, &$balance_payable_order, $payment ) {
        $shipping_methods = $initial_payment_order->get_shipping_methods();
        if ( ! $shipping_methods ) {
            return;
        }

        foreach ( $shipping_methods as $shipping_rate ) {
            $item = new WC_Order_Item_Shipping();
            $item->set_props( array(
                'method_title' => $shipping_rate[ 'name' ],
                'method_id'    => $shipping_rate[ 'id' ],
                'total'        => wc_format_decimal( $shipping_rate[ 'total' ] ),
                'taxes'        => $shipping_rate[ 'taxes' ],
            ) );

            foreach ( $shipping_rate->get_meta_data() as $key => $value ) {
                $item->add_meta_data( $key, $value, true );
            }

            $item->save();
            $balance_payable_order->add_item( $item );

            do_action( 'sumopaymentplans_balance_payable_order_shipping_item_added', $item->get_id(), $balance_payable_order, $initial_payment_order, $payment );
        }
    }

    /**
     * Extract Taxes from Initial payment Order and set in balance payable Order 
     */
    public function set_tax( $initial_payment_order, &$balance_payable_order ) {
        $taxes = $initial_payment_order->get_taxes();
        if ( ! $taxes ) {
            return;
        }

        foreach ( $taxes as $tax ) {
            $item = new WC_Order_Item_Tax();
            $item->set_props( array(
                'rate_id'            => $tax[ 'rate_id' ],
                'tax_total'          => $tax[ 'tax_total' ],
                'shipping_tax_total' => 0,
            ) );

            $item->save();
            $balance_payable_order->add_item( $item );
        }
    }

}
