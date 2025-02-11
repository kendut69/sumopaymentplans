<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Manage payments in My account page
 * 
 * @class SUMO_PP_My_Account_Manager
 * @package Class
 */
class SUMO_PP_My_Account_Manager {

    /**
     * The single instance of the class.
     */
    protected static $instance = null;

    /**
     * Create instance for SUMO_PP_My_Account_Manager.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Init SUMO_PP_My_Account_Manager.
     */
    public function init() {
        add_filter( 'woocommerce_account_menu_items', array( $this, 'set_my_account_menu_items' ) );

        add_action( 'woocommerce_account_sumo-pp-my-payments_endpoint', array( $this, 'my_payments' ) );
        add_shortcode( 'sumo_pp_my_payments', array( $this, 'my_payments' ), 10, 3 );

        add_action( 'woocommerce_account_sumo-pp-view-payment_endpoint', array( $this, 'view_payment' ) );
        add_action( 'sumopaymentplans_my_payments_sumo-pp-view-payment_endpoint', array( $this, 'view_payment' ) );

        add_action( 'sumopaymentplans_account_installments_table', array( $this, 'view_installments' ) );
        add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'prevent_cancel_action' ), 99, 2 );
        add_filter( 'user_has_cap', array( $this, 'customer_has_capability' ), 10, 3 );

        // Render change payment method form
        add_action( 'after_woocommerce_pay', __CLASS__ . '::render_change_payment_method_form', 99 );
        // Change the "Pay for Order" page title to "Change Payment Method"
        add_filter( 'the_title', __CLASS__ . '::change_payment_method_page_title', 100 );
        // Change the "Pay for Order" breadcrumb to "Change Payment Method"
        add_filter( 'woocommerce_get_breadcrumb', __CLASS__ . '::change_payment_method_breadcrumb', 10, 1 );
        // Process change payment method 
        add_action( 'wp', __CLASS__ . '::process_change_payment_method', 20 );

        if ( isset( $_GET[ 'pay_for_order' ] ) ) {
            add_filter( 'woocommerce_product_is_in_stock', array( $this, 'prevent_from_outofstock_product' ), 20, 2 );
        }
    }

    /**
     * Set our menus under My account menu items
     *
     * @param array $items
     * @return array
     */
    public function set_my_account_menu_items( $items ) {
        $endpoint = _sumo_pp()->query->get_query_var( 'sumo-pp-my-payments' );
        $menu     = array( $endpoint => apply_filters( 'sumopaymentplans_my_payments_title', __( 'My Payments', 'sumopaymentplans' ) ) );
        $position = 2;
        $items    = array_slice( $items, 0, $position ) + $menu + array_slice( $items, $position, count( $items ) - 1 );
        return $items;
    }

    /**
     * My Payments template.
     */
    public function my_payments( $atts = '', $content = '', $tag = '' ) {
        if ( is_null( WC()->cart ) ) {
            return;
        }

        global $wp;
        if ( 'sumo_pp_my_payments' === $tag ) {
            if ( ! empty( $wp->query_vars ) ) {
                foreach ( $wp->query_vars as $key => $value ) {
                    // Ignore pagename param.
                    if ( 'pagename' === $key ) {
                        continue;
                    }

                    if ( has_action( 'sumopaymentplans_my_payments_' . $key . '_endpoint' ) ) {
                        do_action( 'sumopaymentplans_my_payments_' . $key . '_endpoint', $value );
                        return;
                    }
                }
            }
        }

        $endpoint = _sumo_pp()->query->get_query_var( 'sumo-pp-my-payments' );
        if ( isset( $wp->query_vars[ $endpoint ] ) && ! empty( $wp->query_vars[ $endpoint ] ) ) {
            $current_page = absint( $wp->query_vars[ $endpoint ] );
        } else {
            $current_page = 1;
        }

        $query = new WP_Query( apply_filters( 'woocommerce_my_account_my_sumo_payments_query', array(
                    'post_type'      => 'sumo_pp_payments',
                    'post_status'    => array_keys( _sumo_pp_get_payment_statuses() ),
                    'meta_key'       => '_customer_id',
                    'meta_value'     => get_current_user_id(),
                    'fields'         => 'ids',
                    'paged'          => $current_page,
                    'posts_per_page' => 5,
                    'orderby'        => 'ID',
                ) ) );

        $customer_payments = ( object ) array(
                    'payments'      => $query->posts,
                    'max_num_pages' => $query->max_num_pages,
                    'total'         => $query->found_posts,
        );

        _sumo_pp_get_template( 'myaccount/my-payments.php', array(
            'current_page'      => absint( $current_page ),
            'customer_payments' => $customer_payments,
            'payments'          => $customer_payments->payments,
            'has_payment'       => 0 < $customer_payments->total,
            'endpoint'          => $endpoint,
        ) );
    }

    /**
     * My Payments > View Payment template.
     *
     * @param int $payment_id
     */
    public function view_payment( $payment_id ) {
        $payment = _sumo_pp_get_payment( $payment_id );
        if ( ! $payment || ! current_user_can( 'sumo-pp-view-payment', $payment_id ) ) {
            echo '<div class="woocommerce-error">' . esc_html__( 'Invalid payment.', 'sumopaymentplans' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html__( 'My account', 'sumopaymentplans' ) . '</a></div>';
            return;
        }

        echo '<div class="sumo-pp-view-payment">';
        _sumo_pp_get_template( 'myaccount/view-payment.php', array(
            'payment_id'            => $payment_id,
            'payment'               => $payment,
            'initial_payment_order' => _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() ),
        ) );
        echo '</div>';
    }

    /**
     * My Payments > View installments.
     * 
     * @param SUMO_PP_Payment $payment
     */
    public function view_installments( $payment ) {
        $columns = sumopp_get_account_payment_installments_columns();
        if ( ! $payment->is_expected_payment_dates_modified() || $payment->has_status( 'completed' ) ) {
            unset( $columns[ 'installment-modified-expected-payment-date' ] );
        }

        _sumo_pp_get_template( 'myaccount/payment-installments.php', array(
            'payment'                 => $payment,
            'actual_payments_date'    => $payment->get_prop( 'actual_payments_date' ),
            'scheduled_payments_date' => $payment->get_prop( 'scheduled_payments_date' ),
            'modified_payment_dates'  => $payment->get_prop( 'modified_expected_payment_dates' ),
            'balance_paid_orders'     => $payment->get_balance_paid_orders(),
            'initial_payment_order'   => _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() ),
            'columns'                 => $columns,
        ) );
    }

    /**
     * Checks if a user has a certain capability.
     *
     * @param array $allcaps All capabilities.
     * @param array $caps    Capabilities.
     * @param array $args    Arguments.
     *
     * @return array The filtered array of all capabilities.
     */
    public function customer_has_capability( $allcaps, $caps, $args ) {
        if ( isset( $caps[ 0 ] ) ) {
            switch ( $caps[ 0 ] ) {
                case 'sumo-pp-view-payment':
                    $user_id = absint( $args[ 1 ] );
                    $payment = _sumo_pp_get_payment( absint( $args[ 2 ] ) );

                    if ( $payment && $user_id === $payment->get_customer_id() ) {
                        $allcaps[ 'sumo-pp-view-payment' ] = true;
                    }
                    break;
            }
        }
        return $allcaps;
    }

    public function prevent_from_outofstock_product( $is_in_stock, $product ) {
        if ( ! $is_in_stock ) {
            $balance_payable_order = _sumo_pp_get_balance_payable_order_in_pay_for_order_page();
            if ( $balance_payable_order ) {
                return true;
            }
        }

        return $is_in_stock;
    }

    /**
     * Prevent cancel option in my orders page for payment order.
     * 
     * @param array $actions
     * @param WC_Order $order
     * @return array
     */
    public function prevent_cancel_action( $actions, $order ) {
        if ( _sumo_pp_is_payment_order( $order ) ) {
            unset( $actions[ 'cancel' ] );
        }

        return $actions;
    }

    /**
     * Is validates the request to change a payment method.
     * 
     * @since 10.9.0   
     * @global array $wp
     * @return bool
     */
    public static function is_valid_add_or_change_payment_request() {
        global $wp;

        return isset( $_GET[ 'sumo_pp_payment_add_or_change_payment' ], $_GET[ '_sumo_pp_nonce' ], $_GET[ 'key' ], $wp->query_vars[ 'order-pay' ] );
    }

    /**
     * Is validates the request to change a payment method.
     * 
     * @since 10.9.0   
     * @global array $wp
     * @param int $payment_id Payment ID
     *      
     * @return bool
     */
    public static function is_valid_add_or_change_payment_request_for_payment( $payment_id ) {
        global $wp;
        if ( ! $payment_id ) {
            return false;
        }

        if ( ! wp_verify_nonce( wc_clean( wp_unslash( $_GET[ '_sumo_pp_nonce' ] ) ), $payment_id ) ) {
            return false;
        }

        $order = wc_get_order( absint( $wp->query_vars[ 'order-pay' ] ) );
        if ( ! $order || ! hash_equals( $order->get_order_key(), wc_clean( wp_unslash( $_GET[ 'key' ] ) ) ) ) {
            return false;
        }

        return true;
    }

    /**
     * Render change payment method form in my account
     * 
     * @since 10.9.0
     * @global array $wp
     */
    public static function render_change_payment_method_form() {
        global $wp;
        if ( ! self::is_valid_add_or_change_payment_request() ) {
            return;
        }

        if ( ! is_main_query() || ! in_the_loop() || ! is_page() || ! is_checkout_pay_page() ) {
            return;
        }

        ob_clean();
        // Because we've cleared the buffer, we need to re-include the opening container div.
        echo '<div class="woocommerce">';

        /**
         * Before WC pay.
         * 
         * @since 10.9.0
         */
        do_action( 'before_woocommerce_pay' );

        try {
            $payment_id = absint( wp_unslash( $_GET[ 'sumo_pp_payment_add_or_change_payment' ] ) );
            if ( ! self::is_valid_add_or_change_payment_request_for_payment( $payment_id ) ) {
                throw new Exception( __( 'Invalid request.', 'sumopaymentplans' ) );
            }

            $order   = wc_get_order( absint( $wp->query_vars[ 'order-pay' ] ) );
            $payment = _sumo_pp_get_payment( $payment_id );
            if ( ! $order || ! $payment ) {
                throw new Exception( __( 'Invalid request.', 'sumopaymentplans' ) );
            }

            $valid_payment_statuses = apply_filters( 'sumopaymentplans_add_or_change_payment_valid_statuses', array( 'in_progress', 'await_aprvl', 'await_cancl', 'pending', 'overdue' ) );
            if ( ! in_array( $payment->get_status(), $valid_payment_statuses ) ) {
                throw new Exception( __( 'Invalid request.', 'sumopaymentplans' ) );
            }

            $balance_payable_order_exists = $payment->balance_payable_order_exists();
            $balance_payable_order_id     = $payment->get_balance_payable_order_id();
            $balance_payable_order        = $balance_payable_order_exists ? wc_get_order( $balance_payable_order_id ) : false;

            if ( $order && $balance_payable_order && in_array( $payment->get_status(), array( 'failed', 'await_cancl', 'pendng_auth', 'overdue' ) ) ) {
                wp_safe_redirect( $balance_payable_order->get_checkout_payment_url() );
                exit;
            }

            wc_print_notice( __( 'Choose a new payment method.', 'sumopaymentplans' ), 'notice' );

            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            foreach ( $available_gateways as $gateway_name => $gateway ) {
                if ( ! $gateway->supports( 'sumo_pp_add_or_change_payment_method' ) ) {
                    unset( $available_gateways[ $gateway_name ] );
                }
            }

            if ( 'auto' === $payment->get_payment_mode() ) {
                $payment_button_text = apply_filters( 'sumopaymentplans_change_payment_button_text', __( 'Change payment method', 'sumopaymentplans' ) );
            } else {
                $payment_button_text = apply_filters( 'sumopaymentplans_add_payment_button_text', __( 'Add payment method', 'sumopaymentplans' ) );
            }

            _sumo_pp_get_template( 'myaccount/form-add-or-change-payment-method.php', array(
                'order'               => $order,
                'payment_id'          => $payment_id,
                'payment'             => $payment,
                'payment_button_text' => $payment_button_text,
                'available_gateways'  => $available_gateways
            ) );
        } catch ( Exception $e ) {
            wc_print_notice( $e->getMessage(), 'error' );
        }
    }

    /**
     * Change payment method page title
     *
     * @since 10.9.0    
     * @param string $title    
     * 
     * @return string
     */
    public static function change_payment_method_page_title( $title ) {
        if ( ! self::is_valid_add_or_change_payment_request() ) {
            return $title;
        }

        if ( ! is_main_query() || ! in_the_loop() || ! is_page() || ! is_checkout_pay_page() ) {
            return $title;
        }

        $payment_id = absint( wp_unslash( $_GET[ 'sumo_pp_payment_add_or_change_payment' ] ) );
        $payment    = _sumo_pp_get_payment( $payment_id );
        if ( ! self::is_valid_add_or_change_payment_request_for_payment( $payment_id ) ) {
            return $title;
        }

        if ( 'auto' === $payment->get_payment_mode() ) {
            $title = __( 'Change payment method', 'sumopaymentplans' );
        } else {
            $title = __( 'Add payment method', 'sumopaymentplans' );
        }

        return $title;
    }

    /**
     * Change Payment Method Breadcrumb
     *
     * @since 10.9.0
     * @param  string $crumbs
     * 
     * @return string     
     */
    public static function change_payment_method_breadcrumb( $crumbs ) {
        if ( ! self::is_valid_add_or_change_payment_request() ) {
            return $crumbs;
        }

        if ( ! is_main_query() || ! is_page() || ! is_checkout_pay_page() ) {
            return $crumbs;
        }

        $payment_id = absint( wp_unslash( $_GET[ 'sumo_pp_payment_add_or_change_payment' ] ) );
        $payment    = _sumo_pp_get_payment( $payment_id );
        if ( ! self::is_valid_add_or_change_payment_request_for_payment( $payment_id ) ) {
            return $crumbs;
        }

        $crumbs[ 1 ] = array(
            get_the_title( wc_get_page_id( 'myaccount' ) ),
            get_permalink( wc_get_page_id( 'myaccount' ) ),
        );

        $crumbs[ 2 ] = array(
            // translators: %s: order number.
            sprintf( __( 'Payment Plan #%s', 'sumopaymentplans' ), $payment_id ),
            esc_url( $payment->get_view_endpoint_url() ),
        );

        if ( 'auto' === $payment->get_payment_mode() ) {
            $crumbs[ 3 ] = array(
                __( 'Change payment method', 'sumopaymentplans' ),
                '',
            );
        } else {
            $crumbs[ 3 ] = array(
                __( 'Add payment method', 'sumopaymentplans' ),
                '',
            );
        }

        return $crumbs;
    }

    /**
     * Process the change payment method.
     * @since 10.9.0
     * @global array $wp Array
     * 
     * @return void
     */
    public static function process_change_payment_method() {
        global $wp;
        if ( ! self::is_valid_add_or_change_payment_request() ) {
            return;
        }

        $payment_id = absint( wp_unslash( $_GET[ 'sumo_pp_payment_add_or_change_payment' ] ) );
        if ( ! self::is_valid_add_or_change_payment_request_for_payment( $payment_id ) ) {
            return;
        }

        $payment = _sumo_pp_get_payment( $payment_id );
        $order   = wc_get_order( absint( $wp->query_vars[ 'order-pay' ] ) );
        if ( ! $payment || ! $order ) {
            return;
        }

        wc_nocache_headers();
        ob_start();

        WC()->customer->set_props(
                array(
                    'billing_country'  => $order->get_billing_country() ? $order->get_billing_country() : null,
                    'billing_state'    => $order->get_billing_state() ? $order->get_billing_state() : null,
                    'billing_postcode' => $order->get_billing_postcode() ? $order->get_billing_postcode() : null,
                    'billing_city'     => $order->get_billing_city() ? $order->get_billing_city() : null,
                )
        );

        try {
            $new_payment_method_id = isset( $_POST[ 'payment_method' ] ) ? wc_clean( wp_unslash( $_POST[ 'payment_method' ] ) ) : false;
            if ( ! $new_payment_method_id ) {
                throw new Exception( __( 'Invalid payment method.', 'sumopaymentplans' ) );
            }

            //Available payment gateways
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

            $new_payment_method = isset( $available_gateways[ $new_payment_method_id ] ) ? $available_gateways[ $new_payment_method_id ] : false;
            if ( ! $new_payment_method ) {
                throw new Exception( __( 'Invalid payment method.', 'sumopaymentplans' ) );
            }

            $new_payment_method->validate_fields();

            // Process payment for the new method
            if ( 0 === wc_notice_count( 'error' ) ) {
                $notice = 'auto' !== $payment->get_payment_mode() ? __( 'Payment method added.', 'sumopaymentplans' ) : __( 'Payment method updated.', 'sumopaymentplans' );
                $result = ( array ) apply_filters( "sumopaymentplans_process_new_payment_method_via_{$new_payment_method_id}", array(), $order, $payment );
                $result = wp_parse_args( $result, array(
                    'result'   => '',
                    'redirect' => $payment->get_view_endpoint_url(),
                        ) );

                if ( 'success' !== $result[ 'result' ] ) {
                    if ( 'auto' !== $payment->get_payment_mode() ) {
                        throw new Exception( __( 'Unable to add payment method.', 'sumopaymentplans' ) );
                    } else {
                        throw new Exception( __( 'Unable to update payment method.', 'sumopaymentplans' ) );
                    }
                }

                if ( 'success' === $result[ 'result' ] ) {
                    if ( isset( $_POST[ 'update_payment_method_for_all_valid_payments' ] ) ) {
                        $user_payment_valid_status = ( array ) apply_filters( 'sumopaymentplans_add_or_change_updated_payment_valid_statuses', array( '_sumo_pp_in_progress', '_sumo_pp_await_aprvl', '_sumo_pp_await_cancl', '_sumo_pp_pending', '_sumo_pp_overdue' ) );
                        $payment_ids               = _sumo_pp_get_payments_by_user( $order->get_customer_id(), $user_payment_valid_status );

                        if ( ! is_array( $payment_ids ) ) {
                            throw new Exception( __( 'No valid payments found.', 'sumopaymentplans' ) );
                        }

                        $payment_type = $payment->get_prop( 'payment_method' );
                        $payment_mode = $payment->get_payment_mode();
                        $customer_id  = $payment->get_prop( 'stripe_customer_id' );
                        $source_id    = $payment->get_prop( 'stripe_source_id' );

                        foreach ( $payment_ids as $payment_id ) {
                            $new_payment               = _sumo_pp_get_payment( $payment_id );
                            $new_balance_payable_order = wc_get_order( $payment->get_balance_payable_order_id() );

                            if ( $new_payment ) {
                                $new_payment->update_prop( 'payment_method', $new_payment_method_id );
                                $new_payment->set_payment_mode( $payment_mode );
                                $new_payment->update_prop( 'stripe_customer_id', $customer_id );
                                $new_payment->update_prop( 'stripe_source_id', $source_id );

                                if ( $new_balance_payable_order ) {
                                    $new_balance_payable_order->set_payment_method( $new_payment_method_id );
                                    $new_balance_payable_order->save();
                                }

                                self::maybe_schedule_next_payment_process( $payment_id );
                                /* translators: 1: payment method details */
                                $new_payment->add_payment_note( sprintf( __( 'Payment method updated. Future payments will be charged automatically %s', 'sumopaymentplans' ), $new_payment->get_payment_method_to_display( 'customer' ) ), $new_payment->get_status(), __( 'Payment Method Updated', 'sumopaymentplans' ) );
                            }
                        }

                        $notice = __( 'Payment method has been updated successfully for all of your valid payments.', 'sumopaymentplans' );
                    } else {
                        self::maybe_schedule_next_payment_process( $payment_id );
                        /* translators: 1: payment method details */
                        $payment->add_payment_note( sprintf( __( 'Payment method updated. Future payments will be charged automatically %s', 'sumopaymentplans' ), $payment->get_payment_method_to_display( 'customer' ) ), $payment->get_status(), __( 'Payment Method Updated', 'sumopaymentplans' ) );
                    }
                }

                wc_add_notice( $notice );
                wp_safe_redirect( $result[ 'redirect' ] );
                exit;
            }
        } catch ( Exception $e ) {
            wc_add_notice( $e->getMessage(), 'error' );
        }

        ob_get_clean();
    }

    /**
     * Maybe schedule next payment process.
     * 
     * @since 10.9.0
     * @param int $payment_id Payment ID
     */
    private static function maybe_schedule_next_payment_process( $payment_id ) {
        $payment = _sumo_pp_get_payment( $payment_id );
        if ( $payment->balance_payable_order_exists() ) {
            $balance_payable_order_id = $payment->get_balance_payable_order_id();
            $scheduler                = _sumo_pp_get_job_scheduler( $payment );
            $scheduler->unset_jobs();
            $next_due_on              = $payment->get_prop( 'next_payment_date' );

            if ( 'auto' === $payment->get_payment_mode() ) {
                $scheduler->schedule_automatic_pay( $balance_payable_order_id, $next_due_on );
                if ( 'payment-plans' === $payment->get_payment_type() ) {
                    $scheduler->schedule_reminder( $balance_payable_order_id, $next_due_on, 'payment_plan_auto_charge_reminder' );
                } else {
                    $scheduler->schedule_reminder( $balance_payable_order_id, $next_due_on, 'deposit_balance_payment_auto_charge_reminder' );
                }
            } else {
                $scheduler->schedule_next_eligible_payment_failed_status( $balance_payable_order_id, $next_due_on );
                if ( 'payment-plans' === $payment->get_payment_type() ) {
                    $scheduler->schedule_reminder( $balance_payable_order_id, $next_due_on, 'payment_plan_invoice' );
                } else {
                    $scheduler->schedule_reminder( $balance_payable_order_id, $next_due_on, 'deposit_balance_payment_invoice' );
                }
            }
        }
    }
}
