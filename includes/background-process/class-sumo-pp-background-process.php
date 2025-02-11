<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle when Recurrence cron gets elapsed
 * 
 * @class SUMO_PP_Background_Process
 * @package Class
 */
class SUMO_PP_Background_Process {

    /**
     * Cron Interval in Seconds.
     * 
     * @var int
     */
    private static $cron_interval = SUMO_PP_PLUGIN_CRON_INTERVAL;

    /**
     * Cron hook identifier
     *
     * @var mixed
     */
    protected static $cron_hook_identifier;

    /**
     * Cron interval identifier
     *
     * @var mixed
     */
    protected static $cron_interval_identifier;

    /**
     * Get the cached order ID which are in progress.
     * 
     * @var array
     */
    protected static $order_in_progress = array();

    /**
     * Init SUMO_PP_Background_Process
     */
    public static function init() {
        self::$cron_hook_identifier     = 'sumopaymentplans_background_updater';
        self::$cron_interval_identifier = 'sumopaymentplans_cron_interval';

        self::schedule_event();
        self::handle_cron_healthcheck();
    }

    /**
     * Schedule event
     */
    protected static function schedule_event() {
        //may be preventing the recurrence Cron interval not to be greater than SUMO_PP_PLUGIN_CRON_INTERVAL
        if ( ( wp_next_scheduled( self::$cron_hook_identifier ) - _sumo_pp_get_timestamp() ) > self::$cron_interval ) {
            wp_clear_scheduled_hook( self::$cron_hook_identifier );
        }

        //Schedule Recurrence Cron job
        if ( ! wp_next_scheduled( self::$cron_hook_identifier ) ) {
            wp_schedule_event( _sumo_pp_get_timestamp() + self::$cron_interval, self::$cron_interval_identifier, self::$cron_hook_identifier );
        }
    }

    /**
     * Handle cron healthcheck
     */
    protected static function handle_cron_healthcheck() {
        //Fire when Recurrence cron gets elapsed
        add_action( self::$cron_hook_identifier, array( __CLASS__, 'run' ) );

        // Fire Scheduled Cron Hooks. $payment_jobs as job name => do some job
        foreach ( _sumo_pp_get_scheduler_jobs() as $job ) {
            add_action( "sumopaymentplans_fire_{$job}", __CLASS__ . "::{$job}" );
        }

        add_action( 'sumopaymentplans_find_products_to_bulk_update', __CLASS__ . '::find_products_to_bulk_update' );
        add_action( 'sumopaymentplans_update_products_in_bulk', __CLASS__ . '::update_products_in_bulk' );

        add_action( 'sumopaymentplans_find_payments_to_bulk_update', __CLASS__ . '::find_payments_to_bulk_update', 10, 2 );
        add_action( 'sumopaymentplans_update_payments_in_bulk', __CLASS__ . '::update_payments_in_bulk', 10, 3 );
    }

    /**
     * Schedule cron healthcheck
     *
     * @param mixed $schedules Schedules.
     * @return mixed
     */
    public static function cron_schedules( $schedules ) {
        $schedules[ self::$cron_interval_identifier ] = array(
            'interval' => self::$cron_interval,
            /* translators: 1: cron interval */
            'display'  => sprintf( __( 'Every %d Minutes', 'sumopaymentplans' ), self::$cron_interval / 60 ),
        );

        return $schedules;
    }

    /**
     * Fire when recurrence Cron gets Elapsed
     * 
     * Background process.
     */
    public static function run() {
        $cron_jobs = _sumo_pp()->query->get( array(
            'type'   => 'sumo_pp_cron_jobs',
            'status' => 'publish',
                ) );

        if ( empty( $cron_jobs ) ) {
            return;
        }

        //Loop through each Scheduled Job Query post and check whether time gets elapsed
        foreach ( $cron_jobs as $job_id ) {
            $jobs = get_post_meta( $job_id, '_scheduled_jobs', true );

            if ( ! is_array( $jobs ) ) {
                continue;
            }

            foreach ( $jobs as $payment_id => $payment_jobs ) {
                foreach ( $payment_jobs as $job_name => $job_args ) {
                    if ( ! is_array( $job_args ) ) {
                        continue;
                    }

                    foreach ( $job_args as $job_timestamp => $args ) {
                        if ( ! is_int( $job_timestamp ) || ! $job_timestamp ) {
                            continue;
                        }
                        //When the time gets elapsed then do the corresponding job.
                        if ( _sumo_pp_get_timestamp() >= $job_timestamp ) {
                            do_action( "sumopaymentplans_fire_{$job_name}", array_merge( array(
                                'payment_id' => $payment_id,
                                            ), $args ) );

                            //Refresh job.
                            $jobs = get_post_meta( $job_id, '_scheduled_jobs', true );

                            //Clear the Job when the corresponding job is done.
                            if ( did_action( "sumopaymentplans_fire_{$job_name}" ) ) {
                                unset( $jobs[ $payment_id ][ $job_name ][ $job_timestamp ] );
                            }
                        }
                    }
                    //Flush the meta once the timestamp is not available for the specific job
                    if ( empty( $jobs[ $payment_id ][ $job_name ] ) ) {
                        unset( $jobs[ $payment_id ][ $job_name ] );
                    }
                }
            }
            //Get updated scheduled jobs.
            if ( is_array( $jobs ) ) {
                update_post_meta( $job_id, '_scheduled_jobs', $jobs );
            }
        }
    }

    /**
     * Cancel Process
     *
     * Clear cronjob.
     */
    public static function cancel() {
        wp_clear_scheduled_hook( self::$cron_hook_identifier );
    }

    /**
     * Create Balance Payable Order for the Payment
     *
     * @param array $args
     */
    public static function create_balance_payable_order( $args ) {
        $args = wp_parse_args( $args, array(
            'payment_id'      => 0,
            'next_payment_on' => '',
                ) );

        $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] );
        if ( ! $payment || ! $payment->has_status( array( 'pending', 'in_progress', 'await_cancl' ) ) ) {
            return;
        }

        if ( $payment->balance_payable_order_exists() ) {
            $balance_payable_order_id = $payment->get_balance_payable_order_id();
        } else {
            $balance_payable_order_id = _sumo_pp()->order->create_balance_payable_order( $payment );
        }

        $scheduler = _sumo_pp_get_job_scheduler( $payment );
        $scheduler->unset_jobs();

        if ( 'auto' === $payment->get_payment_mode() ) {
            $scheduler->schedule_automatic_pay( $balance_payable_order_id, $args[ 'next_payment_on' ] );

            if ( 'payment-plans' === $payment->get_payment_type() ) {
                $scheduler->schedule_reminder( $balance_payable_order_id, $args[ 'next_payment_on' ], 'payment_plan_auto_charge_reminder' );
            } else {
                $scheduler->schedule_reminder( $balance_payable_order_id, $args[ 'next_payment_on' ], 'deposit_balance_payment_auto_charge_reminder' );
            }
        } else {
            $scheduler->schedule_next_eligible_payment_failed_status( $balance_payable_order_id, $args[ 'next_payment_on' ] );

            if ( 'payment-plans' === $payment->get_payment_type() ) {
                $scheduler->schedule_reminder( $balance_payable_order_id, $args[ 'next_payment_on' ], 'payment_plan_invoice' );
            } else {
                $scheduler->schedule_reminder( $balance_payable_order_id, $args[ 'next_payment_on' ], 'deposit_balance_payment_invoice' );
            }
        }
    }

    /**
     * Charge the balance payment automatically
     * 
     * @param array $args
     */
    public static function automatic_pay( $args ) {
        $args = wp_parse_args( $args, array(
            'payment_id'               => 0,
            'balance_payable_order_id' => 0,
            'next_eligible_status'     => '',
            'charging_days'            => 0,
            'retry_times_per_day'      => 0,
            'retry_count'              => 0,
            'total_retries'            => 0,
                ) );

        $balance_payable_order = _sumo_pp_maybe_get_order_instance( $args[ 'balance_payable_order_id' ] );
        if ( ! $balance_payable_order ) {
            return;
        }

        if ( ! _sumo_pp_is_valid_order_to_pay( $balance_payable_order ) ) {
            return;
        }

        if ( ! empty( self::$order_in_progress[ $balance_payable_order->get_id() ] ) ) {
            return; // Bail if the order is in progress already.
        }

        self::$order_in_progress[ $balance_payable_order->get_id() ] = true;

        if ( $balance_payable_order->get_total() <= 0 ) {
            //Auto complete the payment.
            $balance_payable_order->payment_complete();
            return;
        }

        $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] );
        if ( ! $payment || 'auto' !== $payment->get_payment_mode() ) {
            return;
        }

        try {
            WC()->payment_gateways();

            $result = apply_filters( "sumopaymentplans_auto_charge_{$payment->get_payment_method()}_balance_payment", false, $payment, $balance_payable_order );

            if ( is_wp_error( $result ) ) {
                throw new Exception( $result->get_error_message() );
            }

            if ( ! $result ) {
                /* translators: 1: order id */
                throw new Exception( sprintf( __( 'Failed to charge the balance payable Order #%s automatically.', 'sumopaymentplans' ), $balance_payable_order->get_id() ) );
            }

            do_action( 'sumopaymentplans_automatic_payment_success', $payment, $balance_payable_order );
        } catch ( Exception $e ) {
            $payment->add_payment_note( $e->getMessage(), 'failure', __( 'Automatic Payment Failed', 'sumopaymentplans' ) );

            if ( $args[ 'total_retries' ] > 0 ) {
                /* translators: 1: order id 2: retry count */
                $payment->add_payment_note( sprintf( __( 'Automatically retried the balance payment of Order#%1$s %2$s time(s) in Overdue.', 'sumopaymentplans' ), $args[ 'balance_payable_order_id' ], $args[ 'retry_count' ] ), 'pending', __( 'Retry Overdue Payment', 'sumopaymentplans' ) );
            }

            do_action( 'sumopaymentplans_automatic_payment_failed', $payment, $balance_payable_order );

            switch ( apply_filters( 'sumopaymentplans_get_next_eligible_payment_failed_status', $args[ 'next_eligible_status' ], $payment ) ) {
                case 'overdue':
                    if ( $payment->has_status( array( 'pending', 'in_progress' ) ) ) {
                        self::notify_overdue( $args );
                    }
                    break;
                case 'await_cancl':
                    if ( $payment->has_status( array( 'pending', 'in_progress', 'pendng_auth', 'overdue' ) ) ) {
                        self::notify_awaiting_cancel( $args );
                    }
                    break;
                case 'cancelled':
                    if ( $payment->has_status( array( 'pending', 'in_progress', 'pendng_auth', 'overdue' ) ) ) {
                        self::notify_cancelled( $args );
                    }
                    break;
            }
        }
    }

    /**
     * Create Single/Multiple Reminder
     *
     * @param array $args
     */
    public static function notify_reminder( $args ) {
        $args = wp_parse_args( $args, array(
            'payment_id'               => 0,
            'balance_payable_order_id' => 0,
            'mail_template_id'         => '',
                ) );

        $balance_payable_order = _sumo_pp_maybe_get_order_instance( $args[ 'balance_payable_order_id' ] );
        if ( ! $balance_payable_order ) {
            return;
        }

        if ( ! _sumo_pp_is_valid_order_to_pay( $balance_payable_order ) || $balance_payable_order->get_total() <= 0 ) {
            return;
        }

        $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] );
        if ( ! $payment ) {
            return;
        }

        switch ( $args[ 'mail_template_id' ] ) {
            case 'payment_plan_invoice':
            case 'deposit_balance_payment_invoice':
            case 'payment_plan_auto_charge_reminder':
            case 'deposit_balance_payment_auto_charge_reminder':
                if ( $payment->has_status( array( 'pending', 'in_progress', 'await_cancl' ) ) ) {
                    //Trigger email
                    _sumo_pp()->mailer->send( $args[ 'mail_template_id' ], $args[ 'balance_payable_order_id' ], $payment );
                }
                break;
            case 'payment_pending_auth':
                if ( $payment->has_status( 'pendng_auth' ) ) {
                    //Trigger email
                    _sumo_pp()->mailer->send( $args[ 'mail_template_id' ], $args[ 'balance_payable_order_id' ], $payment );
                }
                break;
            case 'payment_plan_overdue':
            case 'deposit_balance_payment_overdue':
                if ( $payment->has_status( 'overdue' ) ) {
                    //Trigger email
                    _sumo_pp()->mailer->send( $args[ 'mail_template_id' ], $args[ 'balance_payable_order_id' ], $payment );
                }
                break;
        }
    }

    /**
     * Notify Payment as Overdue
     *
     * @param array $args
     */
    public static function notify_overdue( $args ) {
        $args = wp_parse_args( $args, array(
            'payment_id'               => 0,
            'balance_payable_order_id' => 0,
            'charging_days'            => '',
            'retry_times_per_day'      => '',
            'overdue_date_till'        => '', //deprecated
                ) );

        $balance_payable_order = _sumo_pp_maybe_get_order_instance( $args[ 'balance_payable_order_id' ] );
        if ( ! $balance_payable_order ) {
            return;
        }

        if ( ! _sumo_pp_is_valid_order_to_pay( $balance_payable_order ) ) {
            return;
        }

        if ( $balance_payable_order->get_total() <= 0 ) {
            //Auto complete the payment.
            $balance_payable_order->payment_complete();
            return;
        }

        $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] );
        if ( ! $payment || ! $payment->has_status( array( 'pending', 'in_progress', 'pendng_auth' ) ) ) {
            return;
        }

        $payment->update_status( 'overdue' );
        $payment->update_prop( 'next_payment_date', '' );

        if ( 'auto' === $payment->get_payment_mode() ) {
            /* translators: 1: order id */
            $payment->add_payment_note( sprintf( __( 'Payment automatically changed to Overdue. Since the balance payment of Order #%s is not paid so far.', 'sumopaymentplans' ), $args[ 'balance_payable_order_id' ] ), 'pending', __( 'Overdue Payment', 'sumopaymentplans' ) );
        } else {
            /* translators: 1: order id */
            $payment->add_payment_note( sprintf( __( 'Balance payment of order#%s is not paid so far. Payment is in Overdue.', 'sumopaymentplans' ), $args[ 'balance_payable_order_id' ] ), 'pending', __( 'Overdue Payment', 'sumopaymentplans' ) );
        }

        if ( is_numeric( $args[ 'charging_days' ] ) ) {
            $overdue_timegap = _sumo_pp_get_timestamp( "+{$args[ 'charging_days' ]} days" );
        } else {
            $overdue_timegap = _sumo_pp_get_timestamp( $args[ 'overdue_date_till' ] ); //deprecated
        }

        if ( $payment->get_remaining_installments() > 1 ) {
            $next_installment_date = _sumo_pp_get_timestamp( $payment->get_next_payment_date( $payment->get_next_of_next_installment_count() ) );

            if ( $overdue_timegap >= $next_installment_date ) {
                $overdue_timegap = $next_installment_date;
            }
        }

        $mail_id = 'payment-plans' === $payment->get_payment_type() ? 'payment_plan_overdue' : 'deposit_balance_payment_overdue';

        $scheduler = _sumo_pp_get_job_scheduler( $payment );
        $scheduler->unset_jobs();
        $scheduler->schedule_next_eligible_payment_failed_status( $args[ 'balance_payable_order_id' ], $overdue_timegap, $args );
        $scheduler->schedule_reminder( $args[ 'balance_payable_order_id' ], $overdue_timegap, $mail_id );

        if ( apply_filters( 'sumopaymentplans_send_instant_email_overdue', true, $args ) ) {
            _sumo_pp()->mailer->send( $mail_id, $args[ 'balance_payable_order_id' ], $payment );
        }

        do_action( 'sumopaymentplans_payment_is_overdue', $payment->id, $args[ 'balance_payable_order_id' ], 'balance-payment-order' );
    }

    /**
     * Retry Payment in Overdue
     *
     * @param array $args
     */
    public static function retry_payment_in_overdue( $args ) {
        self::automatic_pay( $args );
    }

    /**
     * Notify Payment as Awaiting Admin to Cancel
     *
     * @param array $args
     */
    public static function notify_awaiting_cancel( $args ) {
        $args = wp_parse_args( $args, array(
            'payment_id'               => 0,
            'balance_payable_order_id' => 0,
                ) );

        $balance_payable_order = _sumo_pp_maybe_get_order_instance( $args[ 'balance_payable_order_id' ] );
        if ( ! $balance_payable_order ) {
            return;
        }

        if ( ! _sumo_pp_is_valid_order_to_pay( $balance_payable_order ) ) {
            return;
        }

        if ( $balance_payable_order->get_total() <= 0 ) {
            //Auto complete the payment.
            $balance_payable_order->payment_complete();
            return;
        }

        $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] );
        if ( ! $payment || ! $payment->has_status( array( 'pending', 'in_progress', 'overdue', 'pendng_auth' ) ) ) {
            return;
        }

        $payment->update_status( 'await_cancl' );
        $payment->update_prop( 'next_payment_date', '' );

        $scheduler = _sumo_pp_get_job_scheduler( $payment );
        $scheduler->unset_jobs();

        if ( 'payment-plans' === $payment->get_payment_type() ) {
            _sumo_pp()->mailer->send( 'payment_plan_invoice', $args[ 'balance_payable_order_id' ], $payment );
        } else {
            _sumo_pp()->mailer->send( 'deposit_balance_payment_invoice', $args[ 'balance_payable_order_id' ], $payment );
        }

        _sumo_pp()->mailer->send( 'payment_awaiting_cancel', $args[ 'balance_payable_order_id' ], $payment );

        do_action( 'sumopaymentplans_payment_awaiting_cancel', $payment->id, $args[ 'balance_payable_order_id' ], 'balance-payment-order' );
    }

    /**
     * Notify Payment as Cancelled
     *
     * @param array $args
     */
    public static function notify_cancelled( $args ) {
        $args = wp_parse_args( $args, array(
            'payment_id'               => 0,
            'balance_payable_order_id' => 0,
                ) );

        //BKWD CMPT
        if ( ! _sumo_pp_cancel_payment_immediately() ) {
            self::notify_awaiting_cancel( $args );
            return;
        }

        $balance_payable_order = _sumo_pp_maybe_get_order_instance( $args[ 'balance_payable_order_id' ] );
        if ( ! $balance_payable_order ) {
            return;
        }

        if ( ! _sumo_pp_is_valid_order_to_pay( $balance_payable_order ) ) {
            return;
        }

        if ( $balance_payable_order->get_total() <= 0 ) {
            //Auto complete the payment.
            $balance_payable_order->payment_complete();
            return;
        }

        $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] );
        if ( ! $payment || ! $payment->has_status( array( 'pending', 'in_progress', 'overdue', 'pendng_auth' ) ) ) {
            return;
        }

        $payment->cancel_payment( array(
            /* translators: 1: order id */
            'content' => sprintf( __( 'Balance payment of order#%s is not paid so far. Payment is Cancelled.', 'sumopaymentplans' ), $args[ 'balance_payable_order_id' ] ),
            'status'  => 'success',
            'message' => __( 'Balance Payment Cancelled', 'sumopaymentplans' ),
        ) );
    }

    /**
     * Find products to update in bulk
     */
    public static function find_products_to_bulk_update() {
        $found_products = get_transient( '_sumo_pp_bulk_update_found_products' );
        if ( empty( $found_products ) || ! is_array( $found_products ) ) {
            return;
        }

        $found_products = array_filter( array_chunk( $found_products, 10 ) );
        foreach ( $found_products as $index => $chunked_products ) {
            WC()->queue()->schedule_single(
                    time() + $index, 'sumopaymentplans_update_products_in_bulk', array(
                'products' => $chunked_products,
                    ), 'sumopaymentplans-product-bulk-updates' );
        }
    }

    /**
     * Start bulk updation of products.
     */
    public static function update_products_in_bulk( $products ) {
        $product_props = array();

        foreach ( SUMO_PP_Admin_Product::get_payment_fields() as $field_name => $type ) {
            $meta_key                   = SUMO_PP_PLUGIN_PREFIX . $field_name;
            $product_props[ $meta_key ] = get_option( "bulk{$meta_key}" );
        }

        foreach ( $products as $product_id ) {
            $_product = wc_get_product( $product_id );
            if ( ! $_product ) {
                continue;
            }

            switch ( $_product->get_type() ) {
                case 'simple':
                case 'variation':
                    SUMO_PP_Admin_Product::save_meta( $product_id, '', $product_props );
                    break;
                case 'variable':
                    $variations = get_children( array(
                        'post_parent' => $product_id,
                        'post_type'   => 'product_variation',
                        'fields'      => 'ids',
                        'post_status' => array( 'publish', 'private' ),
                        'numberposts' => -1,
                            ) );

                    if ( empty( $variations ) ) {
                        continue 2;
                    }

                    foreach ( $variations as $variation_id ) {
                        if ( $variation_id ) {
                            SUMO_PP_Admin_Product::save_meta( $variation_id, '', $product_props );
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Find payments to update in bulk
     */
    public static function find_payments_to_bulk_update( $deleted_product_id, $replace_product_id ) {
        $found_payments = get_transient( '_sumo_pp_bulk_update_found_payments' );
        if ( empty( $found_payments ) || ! is_array( $found_payments ) ) {
            return;
        }

        $found_payments = array_filter( array_chunk( $found_payments, 10 ) );
        foreach ( $found_payments as $index => $chunked_payments ) {
            WC()->queue()->schedule_single(
                    time() + $index, 'sumopaymentplans_update_payments_in_bulk', array(
                'payments'           => $chunked_payments,
                'deleted_product_id' => $deleted_product_id,
                'replace_product_id' => $replace_product_id,
                    ), 'sumopaymentplans-payment-bulk-updates' );
        }
    }

    /**
     * Start bulk updation of payments.
     */
    public static function update_payments_in_bulk( $payments, $deleted_product_id, $replace_product_id ) {
        $replace_product = wc_get_product( $replace_product_id );
        if ( ! $replace_product ) {
            return;
        }

        foreach ( $payments as $payment_id ) {
            $payment = _sumo_pp_get_payment( $payment_id );
            if ( ! $payment ) {
                continue;
            }

            $initial_payment_order = wc_get_order( $payment->get_initial_payment_order_id() );
            if ( ! $initial_payment_order ) {
                continue;
            }

            foreach ( $initial_payment_order->get_items() as $_item ) {
                $old_product_id = $_item[ 'variation_id' ] > 0 ? $_item[ 'variation_id' ] : $_item[ 'product_id' ];

                if ( empty( $_item[ '_sumo_pp_payment_data' ] ) ) {
                    continue;
                }

                if ( empty( $old_product_id ) || $old_product_id == $deleted_product_id ) {
                    $_item->set_product_id( $replace_product_id );
                    $_item->set_name( $replace_product->get_name() );
                    $_item->save();
                }
            }

            $payment->update_prop( 'product_id', $replace_product_id );

            if ( $payment->balance_payable_order_exists() ) {
                $old_payable_order_id = $payment->get_balance_payable_order_id();

                $payment->delete_prop( 'balance_payable_order_id' );
                $payment->delete_prop( 'balance_payable_order_props' );
                $payment->balance_payable_order->delete( true );

                $new_payable_order_id = _sumo_pp()->order->create_balance_payable_order( $payment, array( 'add_default_note' => false ) );

                $payment->add_payment_note( sprintf( __( 'Installment order #%1$s has been deleted and replaced with a new order #%2$s.', 'sumopaymentplans' ), $old_payable_order_id, $new_payable_order_id ), 'pending', __( 'Balance Payable Order Deleted', 'sumopaymentplans' ) );
            }
        }
    }
}
