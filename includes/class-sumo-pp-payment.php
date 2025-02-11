<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle Payment
 * 
 * @class SUMO_PP_Payment
 * @package Class
 */
class SUMO_PP_Payment extends SUMO_PP_Abstract_Payment {

    /**
     * Cached when the initial payment in processing.
     * 
     * @var bool
     */
    protected $processing_initial_payment;

    /**
     * Cached when the balance payment in processing.
     * 
     * @var bool
     */
    protected $processing_balance_payment;

    /**
     * Construct SUMO_PP_Payment.
     */
    public function __construct( $payment ) {
        $this->populate( $payment );
    }

    public function get_next_payment_date( $installment_count = null ) {
        $next_payment_time = 0;

        if ( 'payment-plans' === $this->get_payment_type() ) {
            if ( $this->is_expected_payment_dates_modified() ) {
                $scheduled_payments_date = $this->get_prop( 'modified_expected_payment_dates' );
            } else {
                $scheduled_payments_date = $this->get_prop( 'scheduled_payments_date' );
            }
            $next_installment_count = is_numeric( $installment_count ) ? $installment_count : $this->get_next_installment_count();

            if ( isset( $scheduled_payments_date[ $next_installment_count ] ) && $scheduled_payments_date[ $next_installment_count ] > 0 ) {
                $next_payment_time = $scheduled_payments_date[ $next_installment_count ];
            }
        } else if ( 'pay-in-deposit' === $this->get_payment_type() ) {
            if ( 'before' === $this->get_pay_balance_type() ) {
                $next_payment_time = _sumo_pp_get_timestamp( $this->get_pay_balance_before() );
            } else {
                $next_payment_time = $this->get_pay_balance_after() > 0 ? _sumo_pp_get_timestamp( "+{$this->get_pay_balance_after()} days" ) : 0;
            }
        }

        return $next_payment_time ? _sumo_pp_get_date( $next_payment_time ) : '';
    }

    public function get_payment_end_date() {
        if ( 'payment-plans' === $this->get_payment_type() ) {
            $scheduled_payments_date = is_array( $this->get_prop( 'scheduled_payments_date' ) ) ? $this->get_prop( 'scheduled_payments_date' ) : array();
            $payment_end_date        = end( $scheduled_payments_date );

            return $payment_end_date ? _sumo_pp_get_date( $payment_end_date ) : '';
        }

        return '';
    }

    public function get_remaining_installments( $next_installment_count = null ) {
        $remaining_installments = 0;

        if ( 'payment-plans' === $this->get_payment_type() ) {
            $total_installments = $this->get_total_installments();

            if ( is_null( $next_installment_count ) ) {
                $next_installment_count = $this->get_next_installment_count();
            }

            $remaining_installments = max( $total_installments, $next_installment_count ) - min( $total_installments, $next_installment_count );
        } else if ( 'pay-in-deposit' === $this->get_payment_type() ) {
            if ( 0 === $this->get_next_installment_count() ) {
                $remaining_installments = 1;
            }
        }

        return $remaining_installments;
    }

    public function get_next_installment_amount( $installment_count = null ) {
        $next_installment_count = is_numeric( $installment_count ) ? $installment_count : $this->get_next_installment_count();

        if ( 'payment-plans' === $this->get_payment_type() ) {
            $payment_schedules = $this->get_prop( 'payment_schedules' );

            if ( isset( $payment_schedules[ $next_installment_count ][ 'scheduled_payment' ] ) ) {
                $payment_amount = floatval( $payment_schedules[ $next_installment_count ][ 'scheduled_payment' ] );

                if ( 'fixed-price' === $this->get_plan_price_type() ) {
                    $next_installment_amount = $payment_amount * $this->get_product_qty();
                } else {
                    $next_installment_amount = ( $this->get_product_amount() * $payment_amount ) / 100;
                }
                return $next_installment_amount;
            }
        } else if ( 'pay-in-deposit' === $this->get_payment_type() ) {
            if ( 0 === $next_installment_count ) {
                return max( $this->get_down_payment(), $this->get_product_amount() ) - min( $this->get_down_payment(), $this->get_product_amount() );
            }
        }

        return 0;
    }

    public function get_total_payable_amount() {
        $total_payable_amount = 0;

        if ( 'payment-plans' === $this->get_payment_type() ) {
            $initial_payment = floatval( $this->get_prop( 'initial_payment' ) );

            if ( 'fixed-price' === $this->get_plan_price_type() ) {
                $total_payable_amount = $initial_payment * $this->get_product_qty();
            } else {
                $total_payable_amount = ( $initial_payment * $this->get_product_amount() ) / 100;
            }

            if ( is_array( $this->get_prop( 'payment_schedules' ) ) ) {
                foreach ( $this->get_prop( 'payment_schedules' ) as $schedule ) {
                    if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
                        continue;
                    }

                    if ( 'fixed-price' === $this->get_plan_price_type() ) {
                        $total_payable_amount += ( floatval( $schedule[ 'scheduled_payment' ] ) * $this->get_product_qty() );
                    } else {
                        $total_payable_amount += ( floatval( $schedule[ 'scheduled_payment' ] ) * $this->get_product_amount() ) / 100;
                    }
                }
            }
        } else if ( 'pay-in-deposit' === $this->get_payment_type() ) {
            $total_payable_amount = $this->get_product_amount();
        }

        return $total_payable_amount;
    }

    public function get_remaining_payable_amount( $installment_count = null ) {
        $remaining_payable_amount = 0;

        if ( 'payment-plans' === $this->get_payment_type() ) {
            $next_installment_count = is_numeric( $installment_count ) ? $installment_count : $this->get_next_installment_count();

            if ( is_array( $this->get_prop( 'payment_schedules' ) ) ) {
                foreach ( $this->get_prop( 'payment_schedules' ) as $installment => $schedule ) {
                    if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
                        continue;
                    }

                    //Since $installment starts from 0 we have to do like this way
                    if ( $next_installment_count <= $installment ) {
                        if ( 'fixed-price' === $this->get_plan_price_type() ) {
                            $remaining_payable_amount += ( $this->get_product_qty() * floatval( $schedule[ 'scheduled_payment' ] ) );
                        } else {
                            $remaining_payable_amount += ( $this->get_product_amount() * floatval( $schedule[ 'scheduled_payment' ] ) ) / 100;
                        }
                    }
                }
            }
        } else if ( 'pay-in-deposit' === $this->get_payment_type() ) {
            if ( 0 === $this->get_next_installment_count() && ! $installment_count ) {
                $remaining_payable_amount = max( $this->get_down_payment(), $this->get_product_amount() ) - min( $this->get_down_payment(), $this->get_product_amount() );
            }
        }

        return $remaining_payable_amount;
    }

    public function update_scheduled_payments_date() {
        if ( 'payment-plans' === $this->get_payment_type() && is_array( $this->get_prop( 'payment_schedules' ) ) ) {
            $scheduled_payments_date = array();
            $from_time               = 0;

            foreach ( $this->get_prop( 'payment_schedules' ) as $schedule ) {
                if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
                    continue;
                }

                if ( isset( $schedule[ 'scheduled_date' ] ) ) {
                    $scheduled_payments_date[] = $schedule[ 'scheduled_date' ] ? _sumo_pp_get_timestamp( $schedule[ 'scheduled_date' ] ) : 0;
                } else if ( isset( $schedule[ 'scheduled_duration_length' ], $schedule[ 'scheduled_period' ] ) ) {
                    $scheduled_payment_cycle = _sumo_pp_get_payment_cycle_in_days( $schedule[ 'scheduled_duration_length' ], $schedule[ 'scheduled_period' ] );

                    if ( $scheduled_payment_cycle <= 0 ) {
                        $scheduled_payments_date[] = 0;
                        break;
                    }

                    $from_time                 = _sumo_pp_get_timestamp( "+{$scheduled_payment_cycle} days", $from_time );
                    $scheduled_payments_date[] = $from_time;
                }
            }

            $this->add_prop( 'scheduled_payments_date', $scheduled_payments_date );
        }
    }

    public function update_actual_payments_date() {
        $actual_payments_date = is_array( $this->get_prop( 'actual_payments_date' ) ) ? $this->get_prop( 'actual_payments_date' ) : array();

        foreach ( $this->get_balance_paid_orders() as $installment => $paid_order_id ) {
            if ( empty( $actual_payments_date[ $installment ] ) ) {
                $actual_payments_date[ $installment ] = _sumo_pp_get_timestamp();
            }
        }

        $this->update_prop( 'actual_payments_date', $actual_payments_date );
    }

    public function maybe_update_modified_expected_payment_dates( $balance_payable_order ) {
        if ( 'payment-plans' === $this->get_payment_type() && is_array( $this->get_prop( 'scheduled_payments_date' ) ) ) {
            if (
                    $this->is_expected_payment_dates_modified() ||
                    ( 'my_account' === $balance_payable_order->get_meta( "{$this->prefix}created_via", true ) && 'modified-payment-date' === $this->get_prop( 'next_payment_date_based_on' ) )
            ) {
                $paid_installment       = array();
                $upcoming_payment_dates = array();

                foreach ( $this->get_prop( 'scheduled_payments_date' ) as $payment_time ) {
                    if ( ! $payment_time || $payment_time > _sumo_pp_get_timestamp( $this->get_prop( 'next_payment_date' ) ) ) {
                        $upcoming_payment_dates[] = $payment_time;
                    }
                }

                foreach ( $this->get_balance_paid_orders() as $order_id ) {
                    if ( $order_id ) {
                        $paid_installment[] = 0;
                    }
                }

                if ( ! empty( $paid_installment ) ) {
                    $this->update_prop( 'modified_expected_payment_dates', array_merge( $paid_installment, $upcoming_payment_dates ) );
                }
            }
        }
    }

    public function update_as_paid_order( $paid_order_id ) {
        $balance_paid_orders = $this->get_balance_paid_orders();
        $paid_order          = wc_get_order( $paid_order_id );

        if ( $paid_order && 'my_account' === $paid_order->get_meta( "{$this->prefix}created_via", true ) ) {
            if ( $this->balance_payable_order_exists() ) {
                remove_action( 'woocommerce_order_status_changed', array( _sumo_pp()->order, 'update_payments' ), 20, 4 );
                $this->balance_payable_order->update_status( 'cancelled' );
                /* translators: 1: order id */
                $this->add_payment_note( sprintf( __( 'Pending Balance Payable Order#%s is Cancelled.', 'sumopaymentplans' ), $this->balance_payable_order->get_order_number() ), 'pending', __( 'Balance Payment Cancelled', 'sumopaymentplans' ) );
                add_action( 'woocommerce_order_status_changed', array( _sumo_pp()->order, 'update_payments' ), 20, 4 );
            }

            $recent_paid_order      = array();
            $next_installment_count = absint( $paid_order->get_meta( "{$this->prefix}next_installment_count", true ) );

            for ( $recently_paid_installment_count = $this->get_next_installment_count(); $recently_paid_installment_count <= $next_installment_count; $recently_paid_installment_count ++ ) {
                $recent_paid_order[] = $paid_order_id;
            }
        } else {
            $recent_paid_order = array( $paid_order_id );
        }

        if ( $this->balance_payable_order_exists( 'my_account' ) ) {
            $this->balance_payable_order->delete( true );
        }

        if ( ! empty( $balance_paid_orders ) ) {
            $this->update_prop( 'balance_paid_orders', array_merge( $balance_paid_orders, $recent_paid_order ) );
        } else {
            $this->update_prop( 'balance_paid_orders', $recent_paid_order );
        }

        $this->delete_prop( 'balance_payable_order_id' );
        $this->delete_prop( 'balance_payable_order_props' );
    }

    public function update_status( $new_status ) {
        $from_status = $this->get_status();
        $post_data   = array(
            'post_status'       => $this->prefix . $new_status,
            'post_modified'     => _sumo_pp_get_date(),
            'post_modified_gmt' => _sumo_pp_get_date(),
        );

        if ( doing_action( 'save_post' ) ) {
            $updated = $GLOBALS[ 'wpdb' ]->update( $GLOBALS[ 'wpdb' ]->posts, $post_data, array( 'ID' => $this->id ) );
            clean_post_cache( $this->id );
        } else {
            $updated = wp_update_post( array_merge( array( 'ID' => $this->id ), $post_data ) );
        }
        $this->populate( $this );
        $to_status = $this->get_status();

        if ( $from_status !== $to_status ) {
            do_action( 'sumopaymentplans_payment_status_changed', $from_status, $to_status, $this );
        }

        return $updated;
    }

    public function add_payment_note( $note, $comment_status, $comment_message ) {
        if ( '' === $note ) {
            return false;
        }

        if ( is_user_logged_in() && current_user_can( 'edit_shop_orders' ) ) {
            $user                 = get_user_by( 'id', get_current_user_id() );
            $comment_author       = $user->display_name;
            $comment_author_email = $user->user_email;
        } else {
            $comment_author       = __( 'SUMO-Payment-Plans', 'sumopaymentplans' );
            $comment_author_email = strtolower( __( 'SUMO-Payment-Plans', 'sumopaymentplans' ) ) . '@';
            $comment_author_email .= isset( $_SERVER[ 'HTTP_HOST' ] ) ? str_replace( 'www.', '', sanitize_text_field( wp_unslash( $_SERVER[ 'HTTP_HOST' ] ) ) ) : 'noreply.com';
            $comment_author_email = sanitize_email( $comment_author_email );
        }

        $comment_id = wp_insert_comment( array(
            'comment_post_ID'      => $this->id,
            'comment_author'       => $comment_author,
            'comment_author_email' => $comment_author_email,
            'comment_author_url'   => '',
            'comment_content'      => $note,
            'comment_type'         => 'payment_note',
            'comment_agent'        => 'SUMO-Payment-Plans',
            'comment_parent'       => 0,
            'comment_approved'     => 1,
            'comment_date'         => _sumo_pp_get_date(),
            'comment_meta'         => array(
                'comment_message' => $comment_message,
                'comment_status'  => $comment_status,
            ),
                ) );

        $comment = get_comment( $comment_id );
        if ( $comment && apply_filters( 'sumopaymentplans_allow_payment_log', false ) ) {
            //Insert each comment in Masterlog
            $log_id = wp_insert_post( array(
                'post_status'   => 'publish',
                'post_type'     => 'sumo_pp_masterlog',
                'post_date'     => _sumo_pp_get_date(),
                'post_date_gmt' => _sumo_pp_get_date(),
                'post_author'   => 1,
                'post_title'    => __( 'Master Log', 'sumopaymentplans' ),
                    ), true );

            if ( ! is_wp_error( $log_id ) ) {
                add_post_meta( $log_id, '_payment_id', $this->id );
                add_post_meta( $log_id, '_payment_number', $this->get_payment_number() );
                add_post_meta( $log_id, '_payment_order_id', $this->get_initial_payment_order_id() );
                add_post_meta( $log_id, '_product_id', $this->get_product_id() );
                add_post_meta( $log_id, '_user_name', $comment->comment_author );
                add_post_meta( $log_id, '_log_posted_on', $comment->comment_date );
                add_post_meta( $log_id, '_status', $comment_status );
                add_post_meta( $log_id, '_message', $comment_message );
                add_post_meta( $log_id, '_log', $comment->comment_content );
            }
        }

        return $comment_id;
    }

    /**
     * Get an payment note.
     *
     * @param  int|WP_Comment $comment Note ID.
     * @return stdClass|null  Object with payment note details or null when does not exists.
     */
    public function get_payment_note( $comment ) {
        if ( is_numeric( $comment ) ) {
            $comment = get_comment( $comment );
        }

        if ( ! is_a( $comment, 'WP_Comment' ) ) {
            return null;
        }

        return ( object ) array(
                    'id'           => absint( $comment->comment_ID ),
                    'date_created' => $comment->comment_date,
                    'content'      => $comment->comment_content,
                    'added_by'     => $comment->comment_author,
                    'added_by'     => __( 'SUMO-Payment-Plans', 'sumopaymentplans' ) === $comment->comment_author ? 'system' : $comment->comment_author,
                    'meta'         => get_comment_meta( $comment->comment_ID ),
        );
    }

    /**
     * Get payment notes.
     *
     * @param  array $args Query arguments
     * @return stdClass[]  Array of stdClass objects with payment notes details.
     */
    public function get_payment_notes( $args = array() ) {
        $key_mapping = array(
            'limit' => 'number',
        );

        foreach ( $key_mapping as $query_key => $db_key ) {
            if ( isset( $args[ $query_key ] ) ) {
                $args[ $db_key ] = $args[ $query_key ];
                unset( $args[ $query_key ] );
            }
        }

        $args[ 'post_id' ] = $this->id;
        $args[ 'orderby' ] = 'comment_ID';
        $args[ 'type' ]    = 'payment_note';
        $args[ 'status' ]  = 'approve';

        // Does not support 'count' or 'fields'.
        unset( $args[ 'count' ], $args[ 'fields' ] );

        remove_filter( 'comments_clauses', array( 'SUMO_PP_Comments', 'exclude_payment_comments' ), 10, 1 );

        $notes = get_comments( $args );

        add_filter( 'comments_clauses', array( 'SUMO_PP_Comments', 'exclude_payment_comments' ), 10, 1 );

        return array_filter( array_map( array( $this, 'get_payment_note' ), $notes ) );
    }

    public function process_initial_payment( $note = array(), $approve_by_admin = false, $status_to_update = 'in_progress' ) {
        if ( $this->processing_initial_payment ) { // Bail if the payment is in progress already.
            return false;
        }

        if ( ! $this->update_status( $status_to_update ) ) {
            return false;
        }

        $this->processing_initial_payment = true;

        $note = wp_parse_args( $note, array(
            /* translators: 1: order id */
            'content' => sprintf( __( 'Initial payment of order#%s is paid. Payment is in progress', 'sumopaymentplans' ), $this->get_initial_payment_order_id() ),
            'status'  => 'success',
            'message' => __( 'Initial Payment Success', 'sumopaymentplans' ),
                ) );
        $this->add_payment_note( $note[ 'content' ], $note[ 'status' ], $note[ 'message' ] );

        if ( 'auto' === $this->get_payment_mode() ) {
            $this->add_payment_note( __( 'Future payments will be charged as Automatic.', 'sumopaymentplans' ), 'success', __( 'Automatic Payments Approved', 'sumopaymentplans' ) );
        }

        $this->update_scheduled_payments_date();
        $next_payment_date = $this->get_prop( 'next_payment_date' ) ? $this->get_prop( 'next_payment_date' ) : $this->get_next_payment_date();

        $this->update_prop( 'payment_start_date', _sumo_pp_get_date() );
        $this->update_prop( 'last_payment_date', _sumo_pp_get_date() );
        $this->update_prop( 'payment_end_date', $this->get_payment_end_date() );
        $this->update_prop( 'next_payment_date', $next_payment_date );
        $this->update_prop( 'next_installment_amount', $this->get_next_installment_amount() );
        $this->update_prop( 'remaining_payable_amount', $this->get_remaining_payable_amount() );
        $this->update_prop( 'remaining_installments', $this->get_remaining_installments() );

        if ( $approve_by_admin ) {
            $parent_order = _sumo_pp_maybe_get_order_instance( $this->get_initial_payment_order_id() );

            foreach ( $parent_order->get_items() as $item_id => $item ) {
                $product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ];

                if ( $product_id == $this->get_product_id() && isset( $item[ 'item_meta' ] ) && is_array( $item[ 'item_meta' ] ) ) {
                    foreach ( $item[ 'item_meta' ] as $key => $value ) {
                        $due_date_label = str_replace( ':', '', get_option( $this->prefix . 'next_payment_date_label' ) );

                        if ( $key == $due_date_label ) {
                            wc_delete_order_item_meta( $item_id, $key, $value );
                            wc_add_order_item_meta( $item_id, $key, _sumo_pp_get_date_to_display( $next_payment_date ), true );
                        }
                    }
                }
            }
        }

        if ( 'pay-in-deposit' === $this->get_payment_type() ) {
            $balance_payable_order_id = _sumo_pp()->order->create_balance_payable_order( $this );

            if ( $next_payment_date ) {
                $scheduler = _sumo_pp_get_job_scheduler( $this );

                if ( 'auto' === $this->get_payment_mode() ) {
                    $scheduler->schedule_automatic_pay( $balance_payable_order_id, $next_payment_date );
                    $scheduler->schedule_reminder( $balance_payable_order_id, $next_payment_date, 'deposit_balance_payment_auto_charge_reminder' );
                } else {
                    $scheduler->schedule_next_eligible_payment_failed_status( $balance_payable_order_id, $next_payment_date );
                    $scheduler->schedule_reminder( $balance_payable_order_id, $next_payment_date, 'deposit_balance_payment_invoice' );
                }
            } else {
                _sumo_pp()->mailer->send( 'deposit_balance_payment_invoice', $balance_payable_order_id, $this );
            }
        } else {
            if ( ! $next_payment_date || 'immediately_after_payment' === _sumo_pp_get_plan_orders_creation( $this ) ) {
                $balance_payable_order_id = _sumo_pp()->order->create_balance_payable_order( $this );
            }

            if ( $next_payment_date ) {
                $scheduler = _sumo_pp_get_job_scheduler( $this );
                $scheduler->schedule_balance_payable_order( $next_payment_date );
            } else {
                _sumo_pp()->mailer->send( 'payment_plan_invoice', $balance_payable_order_id, $this );
            }
        }

        _sumo_pp()->mailer->send( 'payment_schedule', $this->get_initial_payment_order_id(), $this );

        switch ( $this->get_status() ) {
            case 'pending':
                do_action( 'sumopaymentplans_payment_in_pending', $this->id, $this->get_initial_payment_order_id(), 'initial-payment-order' );
                break;
            default:
                do_action( 'sumopaymentplans_payment_in_progress', $this->id, $this->get_initial_payment_order_id(), 'initial-payment-order' );
        }

        return true;
    }

    public function process_balance_payment( $balance_payable_order, $note = array(), $status_to_update = 'in_progress' ) {
        if ( $this->processing_balance_payment ) { // Bail if the payment is in progress already.
            return false;
        }

        if ( ! $this->update_status( $status_to_update ) ) {
            return false;
        }

        $this->processing_balance_payment = true;

        $note = wp_parse_args( $note, array(
            /* translators: 1: order id */
            'content' => sprintf( __( 'Balance payment of order#%s made successful. Remaining payment is in progress.', 'sumopaymentplans' ), $balance_payable_order->get_order_number() ),
            'status'  => 'success',
            'message' => __( 'Balance Payment Success', 'sumopaymentplans' ),
                ) );
        $this->add_payment_note( $note[ 'content' ], $note[ 'status' ], $note[ 'message' ] );

        $this->update_actual_payments_date();
        $this->maybe_update_modified_expected_payment_dates( $balance_payable_order );

        $next_payment_date = $this->get_next_payment_date();
        $this->update_prop( 'last_payment_date', _sumo_pp_get_date() );
        $this->update_prop( 'next_payment_date', $next_payment_date );
        $this->update_prop( 'next_installment_amount', $this->get_next_installment_amount() );
        $this->update_prop( 'remaining_payable_amount', $this->get_remaining_payable_amount() );
        $this->update_prop( 'remaining_installments', $this->get_remaining_installments() );

        if ( ! $next_payment_date || 'immediately_after_payment' === _sumo_pp_get_plan_orders_creation( $this ) ) {
            $balance_payable_order_id = _sumo_pp()->order->create_balance_payable_order( $this );
        }

        if ( $next_payment_date ) {
            $scheduler = _sumo_pp_get_job_scheduler( $this );
            $scheduler->unset_jobs();
            $scheduler->schedule_balance_payable_order( $next_payment_date );
        } else if ( 'payment-plans' === $this->get_payment_type() ) {
            _sumo_pp()->mailer->send( 'payment_plan_invoice', $balance_payable_order_id, $this );
        }

        _sumo_pp()->mailer->send( 'payment_plan_success', $balance_payable_order->get_id(), $this );

        do_action( 'sumopaymentplans_payment_in_progress', $this->id, $balance_payable_order->get_id(), 'balance-payment-order' );
        return true;
    }

    public function payment_complete( $balance_payable_order, $note = array() ) {
        if ( ! $this->update_status( 'completed' ) ) {
            return false;
        }

        $note = wp_parse_args( $note, array(
            /* translators: 1: order id */
            'content' => sprintf( __( 'Balance payment of order#%s made successful. Payment is completed', 'sumopaymentplans' ), $balance_payable_order->get_order_number() ),
            'status'  => 'success',
            'message' => __( 'Balance Payment Complete', 'sumopaymentplans' ),
                ) );
        $this->add_payment_note( $note[ 'content' ], $note[ 'status' ], $note[ 'message' ] );

        $this->update_actual_payments_date();

        if ( $balance_payable_order ) {
            $this->maybe_update_modified_expected_payment_dates( $balance_payable_order );
        }

        $this->update_prop( 'last_payment_date', _sumo_pp_get_date() );
        $this->update_prop( 'next_payment_date', '' );
        $this->update_prop( 'next_installment_amount', '0' );
        $this->update_prop( 'remaining_payable_amount', '0' );
        $this->update_prop( 'remaining_installments', '0' );

        $scheduler = _sumo_pp_get_job_scheduler( $this );
        $scheduler->unset_jobs();

        if ( 'pay-in-deposit' === $this->get_payment_type() ) {
            _sumo_pp()->mailer->send( 'deposit_balance_payment_completed', $balance_payable_order->get_id(), $this );
        } else {
            _sumo_pp()->mailer->send( 'payment_plan_success', $balance_payable_order->get_id(), $this );
            _sumo_pp()->mailer->send( 'payment_plan_completed', $balance_payable_order->get_id(), $this );
        }

        do_action( 'sumopaymentplans_payment_is_completed', $this->id, $balance_payable_order->get_id(), 'balance-payment-order' );
        return true;
    }

    public function cancel_payment( $note = array() ) {
        if ( ! $this->update_status( 'cancelled' ) ) {
            return false;
        }

        $note = wp_parse_args( $note, array(
            'content' => __( 'Payment is cancelled.', 'sumopaymentplans' ),
            'status'  => 'success',
            'message' => __( 'Payment Cancelled', 'sumopaymentplans' ),
                ) );
        $this->add_payment_note( $note[ 'content' ], $note[ 'status' ], $note[ 'message' ] );

        $this->update_prop( 'next_payment_date', '' );
        $this->update_prop( 'next_installment_amount', '0' );
        $this->update_prop( 'remaining_payable_amount', '0' );
        $this->update_prop( 'remaining_installments', '0' );

        $scheduler = _sumo_pp_get_job_scheduler( $this );
        $scheduler->unset_jobs();

        if ( $this->get_balance_payable_order_id() ) {
            _sumo_pp()->mailer->send( 'payment_cancelled', $this->get_balance_payable_order_id(), $this );
            do_action( 'sumopaymentplans_payment_is_cancelled', $this->id, $this->get_balance_payable_order_id(), 'balance-payment-order' );
        } else {
            _sumo_pp()->mailer->send( 'payment_cancelled', $this->get_initial_payment_order_id(), $this );
            do_action( 'sumopaymentplans_payment_is_cancelled', $this->id, $this->get_initial_payment_order_id(), 'initial-payment-order' );
        }
        return true;
    }

    public function fail_payment( $note = array() ) {
        if ( ! $this->update_status( 'failed' ) ) {
            return false;
        }

        $note = wp_parse_args( $note, array(
            'content' => __( 'Payment is failed.', 'sumopaymentplans' ),
            'status'  => 'failure',
            'message' => __( 'Payment Failed', 'sumopaymentplans' ),
                ) );
        $this->add_payment_note( $note[ 'content' ], $note[ 'status' ], $note[ 'message' ] );

        $this->update_prop( 'next_payment_date', '' );
        $this->update_prop( 'next_installment_amount', '0' );
        $this->update_prop( 'remaining_payable_amount', '0' );
        $this->update_prop( 'remaining_installments', '0' );

        $scheduler = _sumo_pp_get_job_scheduler( $this );
        $scheduler->unset_jobs();

        if ( $this->get_balance_payable_order_id() ) {
            do_action( 'sumopaymentplans_payment_is_failed', $this->id, $this->get_balance_payable_order_id(), 'balance-payment-order' );
        } else {
            do_action( 'sumopaymentplans_payment_is_failed', $this->id, $this->get_initial_payment_order_id(), 'initial-payment-order' );
        }
        return true;
    }

    public function get_option( $key, $default_value = null ) {
        if ( ! $key ) {
            return;
        }

        $meta_value = get_post_meta( $this->id, "_{$key}", true );
        return '' === $meta_value ? get_option( SUMO_PP_PLUGIN_PREFIX . $key, $default_value ) : $meta_value;
    }

    /**
     * Get Payment Method for a Payments.
     *
     * @since 10.9.0 
     * @param string $context The context the payment method 'admin' or 'customer'
     * @return string
     */
    public function get_payment_method_to_display( $context = 'admin' ) {
        $parent_order_id = $this->get_initial_payment_order_id();
        $parent_order    = wc_get_order( $parent_order_id );
        $payment_gateway = wc_get_payment_gateway_by_order( $parent_order );

        if ( 'manual' === $this->get_payment_mode() ) {
            $payment_method_to_display = __( 'Manual Payment', 'sumopaymentplans' );
        } elseif ( false !== $payment_gateway ) {
            $payment_method_to_display = $payment_gateway->get_title();
        } else {
            $payment_method_to_display = $parent_order ? $parent_order->get_payment_method_title() : '';
        }

        /**
         * Changed Payment Method Display
         * 
         * @since 10.9.0
         * @param string $payment_method_to_display
         * @param object $this SUMO_PP_Payment
         * @param string $context
         * 
         * @return string
         */
        $payment_method_to_display = apply_filters( 'sumopaymentplans_payment_method_to_display', $payment_method_to_display, $this, $context );

        if ( 'customer' === $context ) {
            // translators: %s: payment method.
            $payment_method_to_display = sprintf( __( 'Via %s', 'sumopaymentplans' ), $payment_method_to_display );

            // Only filter the result for non-manual payment mode.
            if ( 'manual' !== $this->get_payment_mode() ) {
                /**
                 * Auto Payment Method
                 * 
                 * @since 10.9.0
                 * @param string $payment_method_to_display
                 * @param object $this $payment_plan Object                 
                 * 
                 * @return string
                 */
                $payment_method_to_display = apply_filters( 'sumopaymentplans_payment_method_display', $payment_method_to_display, $this, $context );
            }
        }

        return $payment_method_to_display;
    }
}
