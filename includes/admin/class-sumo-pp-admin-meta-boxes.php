<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle Admin Metaboxes
 * 
 * @class SUMO_PP_Admin_Metaboxes
 * @package Class
 */
class SUMO_PP_Admin_Metaboxes {

    /**
     * SUMO_PP_Admin_Metaboxes constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ) );
        add_action( 'admin_head', array( $this, 'add_metaboxes_position' ), 99999 );
        add_action( 'post_updated_messages', array( $this, 'get_admin_post_messages' ) );
        add_action( 'woocommerce_order_item_line_item_html', array( $this, 'render_payment_plan_and_deposit_by_item' ), 20, 3 );
        add_action( 'woocommerce_admin_order_items_after_line_items', array( $this, 'render_payment_plan_and_deposit_by_order' ), 20, 1 );
        add_action( 'save_post', array( $this, 'save' ), 1, 3 );
    }

    /**
     * Add Metaboxes.
     *
     * @global object $post
     */
    public function add_meta_boxes() {
        global $post, $theorder;
        $order_id = $theorder instanceof WC_Order ? $theorder->get_id() : ( $post ? $post->ID : 0 );

        add_meta_box( '_sumo_pp_plan_description', __( 'Plan Description', 'sumopaymentplans' ), array( $this, 'render_plan_description' ), 'sumo_payment_plans', 'normal', 'high' );
        add_meta_box( '_sumo_pp_plan_creation', __( 'Payment Plan', 'sumopaymentplans' ), array( $this, 'render_plan_creation' ), 'sumo_payment_plans', 'normal', 'low' );
        add_meta_box( '_sumo_pp_plan_balance_payable_orders_creation', __( 'Payment Plan Orders Creation', 'sumopaymentplans' ), array( $this, 'render_plan_orders_creation' ), 'sumo_payment_plans', 'side', 'low' );

        if ( $post && 'enabled' === get_post_meta( $post->ID, '_sync', true ) ) {
            add_meta_box( '_sumo_pp_plan_synced_payment_dates', __( 'Synced Payment Dates', 'sumopaymentplans' ), array( $this, 'render_plan_synced_payment_dates' ), 'sumo_payment_plans', 'side', 'low' );
        }

        add_meta_box( '_sumo_pp_payment_details', __( 'Payment Details', 'sumopaymentplans' ), array( $this, 'render_payment_details' ), 'sumo_pp_payments', 'normal', 'high' );
        add_meta_box( '_sumo_pp_payment_notes', __( 'Payment Logs', 'sumopaymentplans' ), array( $this, 'render_payment_notes' ), 'sumo_pp_payments', 'side', 'low' );
        add_meta_box( '_sumo_pp_payment_actions', __( 'Actions', 'sumopaymentplans' ), array( $this, 'render_payment_actions' ), 'sumo_pp_payments', 'side', 'high' );
        add_meta_box( '_sumo_pp_payment_orders', __( 'Payment Orders', 'sumopaymentplans' ), array( $this, 'render_payment_orders' ), 'sumo_pp_payments', 'normal', 'default' );
        add_meta_box( 'woocommerce-order-items', __( 'Payment Item', 'sumopaymentplans' ), array( $this, 'render_payment_item' ), 'sumo_pp_payments', 'normal', 'default' );

        // Only display the meta box if an order relates to a Payment.
        if ( $order_id && 'shop_order' === WC_Data_Store::load( 'order' )->get_order_type( $order_id ) && _sumo_pp_is_payment_order( $order_id ) ) {
            add_meta_box( '_sumo_pp_related_orders', __( 'Related Orders', 'sumopaymentplans' ), array( $this, 'render_related_orders' ), wc_get_page_screen_id( 'shop-order' ), 'normal', 'low' );
        }
    }

    /**
     * Remove Metaboxes.
     */
    public function remove_meta_boxes() {
        remove_meta_box( 'commentsdiv', 'sumo_payment_plans', 'normal' );
        remove_meta_box( 'commentsdiv', 'sumo_pp_payments', 'normal' );
        remove_meta_box( 'submitdiv', 'sumo_pp_payments', 'side' );
    }

    /**
     * Set default Payment Plans metaboxes positions
     */
    public function add_metaboxes_position() {
        if ( 'sumo_pp_payments' === get_post_type() ) {
            $user = wp_get_current_user();
            if ( ! $user ) {
                return;
            }

            if ( false === get_user_option( 'meta-box-order_sumo_pp_payments', $user->ID ) ) {
                delete_user_option( $user->ID, 'meta-box-order_sumo_pp_payments', true );
                update_user_option( $user->ID, 'meta-box-order_sumo_pp_payments', array(
                    'side'     => '_sumo_pp_payment_actions,_sumo_pp_payment_notes',
                    'normal'   => '_sumo_pp_payment_details,_sumo_pp_payment_orders',
                    'advanced' => '',
                        ), true );
            }
            if ( false === get_user_option( 'screen_layout_sumo_pp_payments', $user->ID ) ) {
                delete_user_option( $user->ID, 'screen_layout_sumo_pp_payments', true );
                update_user_option( $user->ID, 'screen_layout_sumo_pp_payments', 'auto', true );
            }
        }
    }

    /**
     * Display updated Payment Plans post message.
     *
     * @param array $messages
     * @return array
     */
    public function get_admin_post_messages( $messages ) {
        $messages[ 'sumo_payment_plans' ] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Plan updated.', 'sumopaymentplans' ),
            2 => __( 'Custom field(s) updated.', 'sumopaymentplans' ),
            4 => __( 'Plan updated.', 'sumopaymentplans' ),
        );
        $messages[ 'sumo_pp_payments' ]   = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Payment updated.', 'sumopaymentplans' ),
            2 => __( 'Custom field(s) updated.', 'sumopaymentplans' ),
            4 => __( 'Payment updated.', 'sumopaymentplans' ),
        );

        return $messages;
    }

    public function render_plan_description( $post ) {
        echo '<p>';
        echo '<textarea cols="90" rows="5" name="_sumo_pp_plan_description" placeholder="' . esc_attr__( 'Describe this plan about to customers', 'sumopaymentplans' ) . '">' . esc_html( get_post_meta( $post->ID, '_plan_description', true ) ) . '</textarea>';
        echo '</p>';
    }

    public function render_payment_details( $post ) {
        $payment               = _sumo_pp_get_payment( $post->ID );
        $balance_payable_order = _sumo_pp_maybe_get_order_instance( $payment->get_balance_payable_order_id() );
        $initial_payment_order = _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() );

        wp_nonce_field( '_sumo_pp_save_data', '_sumo_pp_meta_nonce' );
        include 'views/html-admin-payment-details.php';
    }

    public function render_plan_creation( $post ) {
        $sync                     = get_post_meta( $post->ID, '_sync', true );
        $price_type               = get_post_meta( $post->ID, '_price_type', true );
        $pay_balance_type         = 'enabled' === $sync ? 'before' : get_post_meta( $post->ID, '_pay_balance_type', true );
        $installments_type        = get_post_meta( $post->ID, '_installments_type', true );
        $initial_payment          = get_post_meta( $post->ID, '_initial_payment', true );
        $payment_schedules        = get_post_meta( $post->ID, '_payment_schedules', true );
        $total_payment_amount     = 'before' === $pay_balance_type && 'enabled' !== $sync ? 0 : floatval( $initial_payment );
        $sync_start_date          = get_post_meta( $post->ID, '_sync_start_date', true );
        $fixed_no_of_installments = get_post_meta( $post->ID, '_fixed_no_of_installments', true );
        $fixed_payment_amount     = get_post_meta( $post->ID, '_fixed_payment_amount', true );
        $fixed_duration_length    = get_post_meta( $post->ID, '_fixed_duration_length', true );
        $fixed_duration_period    = get_post_meta( $post->ID, '_fixed_duration_period', true );

        wp_nonce_field( '_sumo_pp_save_data', '_sumo_pp_meta_nonce' );
        include 'views/html-admin-plan-table.php';
    }

    public function render_plan_orders_creation( $post ) {
        $balance_payable_orders_creation = get_post_meta( $post->ID, '_balance_payable_orders_creation', true );
        $next_payment_date_based_on      = get_post_meta( $post->ID, '_next_payment_date_based_on', true );
        include 'views/html-admin-plan-orders-creation.php';
    }

    public function render_plan_synced_payment_dates( $post ) {
        $plan_props = _sumo_pp()->plan->get_props( $post->ID );
        foreach ( $plan_props[ 'payment_schedules' ] as $installment => $schedule ) {
            if ( isset( $schedule[ 'scheduled_date' ] ) ) {
                echo esc_html( _sumo_pp_get_date( $schedule[ 'scheduled_date' ] ) ) . '<br>';
            }
        }
        echo '.....';
    }

    public function render_payment_notes( $post ) {
        $payment = _sumo_pp_get_payment( $post );
        $notes   = $payment->get_payment_notes();
        include 'views/html-admin-payment-notes.php';
    }

    public function render_payment_actions( $post ) {
        $payment = _sumo_pp_get_payment( $post );
        include 'views/html-admin-payment-actions.php';
    }

    public function render_payment_orders( $post ) {
        $payment = _sumo_pp_get_payment( $post );
        include 'views/html-admin-payment-installments.php';
    }

    public function render_payment_item( $post ) {
        $payment              = _sumo_pp_get_payment( $post );
        $order_id             = $payment->get_initial_payment_order_id();
        $_item_product        = wc_get_product( $payment->get_product_id() );
        $is_order_paymentplan = 'order' === $payment->get_product_type();
        $order                = _sumo_pp_maybe_get_order_instance( $order_id );

        if ( $order && ( $is_order_paymentplan || $_item_product ) ) {
            include 'views/html-admin-payment-order-items.php';
        } else {
            echo wp_kses_post( "<H3>&nbsp;Product ID: #{$payment->get_product_id()}(deleted)</H3>" );
        }
    }

    public function render_payment_plan_and_deposit_by_item( $item_id, $item, $order ) {
        $order = _sumo_pp_maybe_get_order_instance( $order );

        if ( $order ) {
            $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();

            if (
                    _sumo_pp()->product->is_payment_product( $product_id ) &&
                    ! _sumo_pp_is_payment_order( $order )
            ) {
                $product_type   = _sumo_pp()->product->get_prop( 'product_type' );
                $payment_type   = _sumo_pp()->product->get_prop( 'payment_type' );
                $selected_plans = _sumo_pp()->product->get_prop( 'selected_plans' );
                include 'views/html-admin-payment-plan-and-deposit.php';
            }
        }
    }

    public function render_payment_plan_and_deposit_by_order( $order_id ) {
        $order = _sumo_pp_maybe_get_order_instance( $order_id );

        if ( ! $order || _sumo_pp_is_payment_order( $order ) || _sumo_pp_order_has_payment_data( $order ) || count( $order->get_items() ) <= 0 || $order->get_total() <= 0 ) {
            return;
        }

        $order_contains_payment_item = false;
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();

            if ( _sumo_pp()->product->is_payment_product( $product_id ) ) {
                $order_contains_payment_item = true;
                break;
            }
        }

        if ( ! $order_contains_payment_item ) {
            $option_props = _sumo_pp()->orderpp->get_option_props();

            if ( $option_props[ 'orderpp_enabled' ] && 'order' === $option_props[ 'product_type' ] ) {
                $product_type   = $option_props[ 'product_type' ];
                $payment_type   = $option_props[ 'payment_type' ];
                $selected_plans = $option_props[ 'selected_plans' ];
                include 'views/html-admin-payment-plan-and-deposit.php';
            }
        }
    }

    public function render_related_orders( $post ) {
        if ( $post instanceof WC_Order ) {
            $order_id = $post->get_id();
        } else {
            $order_id = $post->ID;
        }

        $current_order = _sumo_pp_maybe_get_order_instance( $order_id );
        if ( ! $current_order ) {
            return;
        }

        $payments = _sumo_pp()->query->get( array(
            'type'       => 'sumo_pp_payments',
            'status'     => array_keys( _sumo_pp_get_payment_statuses() ),
            'meta_key'   => '_initial_payment_order_id',
            'meta_value' => _sumo_pp_get_parent_order_id( $current_order ),
                ) );

        if ( empty( $payments ) ) {
            return;
        }

        $related_orders = array();
        if ( _sumo_pp_is_parent_order( $current_order ) ) {
            $balance_payable_orders = array();

            foreach ( $payments as $payment_id ) {
                $payment = _sumo_pp_get_payment( $payment_id );
                if ( ! $payment ) {
                    continue;
                }

                $related_orders[ $payment_id ] = array(
                    'order_id'     => $payment->get_payment_number(),
                    'relation'     => __( 'Payment', 'sumopaymentplans' ),
                    'date'         => $payment->get_prop( 'payment_start_date' ),
                    'status'       => $payment->get_status( true ),
                    'status_label' => $payment->get_status_label(),
                    'total'        => wc_price( $payment->get_total_payable_amount(), array( 'currency' => $current_order->get_currency() ) ),
                );

                $balance_payable_orders = array_merge( $payment->get_balance_paid_orders(), ( array ) $payment->get_balance_payable_order_id() );
                if ( empty( $balance_payable_orders ) ) {
                    continue;
                }

                foreach ( $balance_payable_orders as $balance_payable_order_id ) {
                    $balance_order = _sumo_pp_maybe_get_order_instance( $balance_payable_order_id );
                    if ( ! $balance_order ) {
                        continue;
                    }

                    $related_orders[ $balance_payable_order_id ] = array(
                        'order_id'     => $balance_payable_order_id,
                        /* translators: 1: Payment link */
                        'relation'     => sprintf( __( 'Balance Payable Order of Payment %s', 'sumopaymentplans' ), "<a href='" . esc_url( admin_url( "post.php?post={$payment_id}&action=edit" ) ) . "'>#{$payment->get_payment_number()}</a>" ),
                        'date'         => $balance_order->get_date_created()->date( 'Y-m-d H:i:s' ),
                        'status'       => $balance_order->get_status(),
                        'status_label' => wc_get_order_status_name( $balance_order->get_status() ),
                        'total'        => $balance_order->get_formatted_order_total(),
                    );
                }
            }
        } else if ( _sumo_pp_is_child_order( $current_order ) ) {
            $payment = _sumo_pp_get_payment( $current_order->get_meta( '_payment_id', true ) );
            if ( ! $payment ) {
                return;
            }

            $related_orders[ $payment->id ] = array(
                'order_id'     => $payment->get_payment_number(),
                'relation'     => __( 'Payment', 'sumopaymentplans' ),
                'date'         => $payment->get_prop( 'payment_start_date' ),
                'status'       => $payment->get_status( true ),
                'status_label' => $payment->get_status_label(),
                'total'        => wc_price( $payment->get_total_payable_amount(), array( 'currency' => $current_order->get_currency() ) ),
            );

            $parent_order_id = _sumo_pp_get_parent_order_id( $current_order );
            $parent_order    = _sumo_pp_maybe_get_order_instance( $parent_order_id );

            if ( $parent_order ) {
                $payments_link = array();

                foreach ( $payments as $_payment_id ) {
                    $_payment = _sumo_pp_get_payment( $_payment_id );
                    if ( $_payment ) {
                        $payments_link[] = "<a href='" . esc_url( admin_url( "post.php?post={$_payment_id}&action=edit" ) ) . "'>#{$_payment->get_payment_number()}</a>";
                    }
                }

                $related_orders[ $parent_order_id ] = array(
                    'order_id'     => $parent_order_id,
                    /* translators: 1: Payments link */
                    'relation'     => sprintf( __( 'Deposit Order of Payment %s', 'sumopaymentplans' ), wp_kses_post( implode( ', ', $payments_link ) ) ),
                    'date'         => $parent_order->get_date_created()->date( 'Y-m-d H:i:s' ),
                    'status'       => $parent_order->get_status(),
                    'status_label' => wc_get_order_status_name( $parent_order->get_status() ),
                    'total'        => $parent_order->get_formatted_order_total(),
                );
            }

            $balance_payable_orders = array_merge( $payment->get_balance_paid_orders(), ( array ) $payment->get_balance_payable_order_id() );
            if ( empty( $balance_payable_orders ) ) {
                return;
            }

            foreach ( $balance_payable_orders as $balance_payable_order_id ) {
                $balance_order = _sumo_pp_maybe_get_order_instance( $balance_payable_order_id );
                if ( ! $balance_order ) {
                    continue;
                }

                $related_orders[ $balance_payable_order_id ] = array(
                    'order_id'     => $balance_payable_order_id,
                    /* translators: 1: Payment link */
                    'relation'     => sprintf( __( 'Balance Payable Order of Payment %s', 'sumopaymentplans' ), "<a href='" . esc_url( admin_url( "post.php?post={$payment->id}&action=edit" ) ) . "'>#{$payment->get_payment_number()}</a>" ),
                    'date'         => $balance_order->get_date_created()->date( 'Y-m-d H:i:s' ),
                    'status'       => $balance_order->get_status(),
                    'status_label' => wc_get_order_status_name( $balance_order->get_status() ),
                    'total'        => $balance_order->get_formatted_order_total(),
                );
            }
        }

        $related_orders = apply_filters( 'sumopaymentplans_admin_related_orders_to_display', $related_orders );
        include 'views/html-admin-payment-related-orders.php';
    }

    /**
     * Save data.
     *
     * @param int $post_id The post ID.
     * @param object $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function save( $post_id, $post, $update ) {
        // $post_id and $post are required
        if ( empty( $post_id ) || empty( $post ) ) {
            return;
        }

        // Dont' save meta boxes for revisions or autosaves
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return;
        }

        // Check the nonce
        if ( ! isset( $_POST[ '_sumo_pp_meta_nonce' ] ) || empty( $_POST[ '_sumo_pp_meta_nonce' ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ '_sumo_pp_meta_nonce' ] ) ), '_sumo_pp_save_data' ) ) {
            return;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if ( empty( $_POST[ 'post_ID' ] ) || $_POST[ 'post_ID' ] != $post_id ) {
            return;
        }

        // Check user has permission to edit
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $posted = $_POST;
        switch ( $post->post_type ) {
            case 'sumo_payment_plans':
                $payment_schedules = array();

                if ( ! empty( $posted[ '_sumo_pp_scheduled_payment' ] ) ) {
                    $scheduled_payment = array_map( 'wc_clean', $posted[ '_sumo_pp_scheduled_payment' ] );

                    foreach ( $scheduled_payment as $i => $payment ) {
                        if ( ! isset( $scheduled_payment[ $i ] ) ) {
                            continue;
                        }

                        if ( 'before' === $posted[ '_sumo_pp_pay_balance_type' ] || 'enabled' === $posted[ '_sumo_pp_sync' ] ) {
                            $posted[ '_sumo_pp_pay_balance_type' ] = 'before';

                            if ( 'enabled' === $posted[ '_sumo_pp_sync' ] ) {
                                $payment_schedules[] = array(
                                    'scheduled_payment' => $scheduled_payment[ $i ],
                                    'scheduled_date'    => '',
                                );
                            } else if ( isset( $posted[ '_sumo_pp_scheduled_date' ][ $i ] ) ) {
                                $scheduled_date      = array_map( 'wc_clean', $posted[ '_sumo_pp_scheduled_date' ] );
                                $payment_schedules[] = array(
                                    'scheduled_payment' => $scheduled_payment[ $i ],
                                    'scheduled_date'    => $scheduled_date[ $i ],
                                );
                            }
                        } else if ( isset( $posted[ '_sumo_pp_scheduled_duration_length' ][ $i ], $posted[ '_sumo_pp_scheduled_period' ][ $i ] ) ) {
                            $scheduled_duration_length = array_map( 'absint', $posted[ '_sumo_pp_scheduled_duration_length' ] );
                            $scheduled_period          = array_map( 'wc_clean', $posted[ '_sumo_pp_scheduled_period' ] );
                            $payment_schedules[]       = array(
                                'scheduled_payment'         => $scheduled_payment[ $i ],
                                'scheduled_duration_length' => $scheduled_duration_length[ $i ],
                                'scheduled_period'          => $scheduled_period[ $i ],
                            );
                        }
                    }
                }

                update_post_meta( $post_id, '_payment_schedules', $payment_schedules );
                update_post_meta( $post_id, '_price_type', $posted[ '_sumo_pp_price_type' ] );
                update_post_meta( $post_id, '_pay_balance_type', $posted[ '_sumo_pp_pay_balance_type' ] );
                update_post_meta( $post_id, '_installments_type', $posted[ '_sumo_pp_installments_type' ] );
                update_post_meta( $post_id, '_sync', $posted[ '_sumo_pp_sync' ] );
                update_post_meta( $post_id, '_sync_start_date', $posted[ '_sumo_pp_sync_start_date' ] );
                update_post_meta( $post_id, '_sync_month_duration', $posted[ '_sumo_pp_sync_month_duration' ] );
                update_post_meta( $post_id, '_plan_description', isset( $posted[ '_sumo_pp_plan_description' ] ) ? $posted[ '_sumo_pp_plan_description' ] : ''  );
                update_post_meta( $post_id, '_initial_payment', isset( $posted[ '_sumo_pp_initial_payment' ] ) ? floatval( wc_clean( $posted[ '_sumo_pp_initial_payment' ] ) ) : '0'  );
                update_post_meta( $post_id, '_balance_payable_orders_creation', $posted[ '_sumo_pp_balance_payable_orders_creation' ] );
                update_post_meta( $post_id, '_next_payment_date_based_on', $posted[ '_sumo_pp_next_payment_date_based_on' ] );
                update_post_meta( $post_id, '_fixed_no_of_installments', $posted[ '_sumo_pp_fixed_no_of_installments' ] );
                update_post_meta( $post_id, '_fixed_payment_amount', $posted[ '_sumo_pp_fixed_payment_amount' ] );
                update_post_meta( $post_id, '_fixed_duration_length', $posted[ '_sumo_pp_fixed_duration_length' ] );
                update_post_meta( $post_id, '_fixed_duration_period', $posted[ '_sumo_pp_fixed_duration_period' ] );
                break;
            case 'sumo_pp_payments':
                $payment = _sumo_pp_get_payment( $post_id );

                if ( ! empty( $posted[ 'expected_payment_date' ] ) ) {
                    if ( 'payment-plans' === $payment->get_payment_type() ) {
                        $new_scheduled      = $payment->get_prop( 'scheduled_payments_date' );
                        $existing_scheduled = $new_scheduled;

                        if ( is_array( $existing_scheduled ) ) {
                            foreach ( $existing_scheduled as $installment => $schedule ) {
                                if ( isset( $posted[ 'expected_payment_date' ][ $installment ] ) && ! empty( $posted[ 'expected_payment_date' ][ $installment ] ) ) {
                                    $new_scheduled[ $installment ] = _sumo_pp_get_timestamp( $posted[ 'expected_payment_date' ][ $installment ] );
                                }
                            }
                        }

                        if ( $existing_scheduled !== $new_scheduled ) {
                            $payment->update_prop( 'scheduled_payments_date', $new_scheduled );
                            $payment->update_prop( 'next_payment_date', $payment->get_next_payment_date() );
                            $payment->update_prop( 'payment_end_date', $payment->get_payment_end_date() );

                            $next_payment_date = $payment->get_prop( 'next_payment_date' );
                            if ( $next_payment_date ) {
                                $scheduler = _sumo_pp_get_job_scheduler( $payment );
                                $scheduler->unset_jobs();
                                $scheduler->schedule_balance_payable_order( $next_payment_date );
                            }
                        }
                    } else {
                        $old_next_payment_date = $payment->get_prop( 'next_payment_date' );
                        $new_next_payment_date = $posted[ 'expected_payment_date' ];

                        if ( ! $old_next_payment_date || ( _sumo_pp_get_timestamp( $old_next_payment_date ) !== _sumo_pp_get_timestamp( $new_next_payment_date ) ) ) {
                            $payment->update_prop( 'next_payment_date', _sumo_pp_get_date( $new_next_payment_date ) );
                            $payment->update_prop( 'payment_end_date', '' );

                            $next_payment_date = $payment->get_prop( 'next_payment_date' );
                            if ( $next_payment_date ) {
                                $scheduler                = _sumo_pp_get_job_scheduler( $payment );
                                $scheduler->unset_jobs();
                                $balance_payable_order_id = $payment->get_balance_payable_order_id();

                                if ( 'auto' === $payment->get_payment_mode() ) {
                                    $scheduler->schedule_automatic_pay( $balance_payable_order_id, $next_payment_date );
                                    $scheduler->schedule_reminder( $balance_payable_order_id, $next_payment_date, 'deposit_balance_payment_auto_charge_reminder' );
                                } else {
                                    $scheduler->schedule_next_eligible_payment_failed_status( $balance_payable_order_id, $next_payment_date );
                                    $scheduler->schedule_reminder( $balance_payable_order_id, $next_payment_date, 'deposit_balance_payment_invoice' );
                                }
                            }
                        }
                    }
                }

                if ( ! empty( $posted[ 'expected_installment_amount' ] ) && 'payment-plans' === $payment->get_payment_type() ) {
                    $new_payment_scheduled      = $payment->get_prop( 'payment_schedules' );
                    $existing_payment_scheduled = $new_payment_scheduled;

                    //Convert the percentage payment scheduled into fixed price.
                    if ( 'percent' === $payment->get_plan_price_type() ) {
                        foreach ( $existing_payment_scheduled as $installment => $scheduled ) {
                            $new_payment_scheduled[ $installment ][ 'scheduled_payment' ] = $payment->get_product_price() * floatval( $scheduled[ 'scheduled_payment' ] ) / 100;
                        }

                        $payment->update_prop( 'initial_payment', ( $payment->get_product_price() * floatval( $payment->get_prop( 'initial_payment' ) ) ) / 100 );
                        $payment->update_prop( 'plan_price_type', 'fixed-price' );
                    }

                    if ( 'fixed-price' === $payment->get_plan_price_type() ) {
                        foreach ( $existing_payment_scheduled as $installment => $scheduled ) {
                            if ( isset( $posted[ 'expected_installment_amount' ][ $installment ] ) && is_numeric( $posted[ 'expected_installment_amount' ][ $installment ] ) ) {
                                $new_payment_scheduled[ $installment ][ 'scheduled_payment' ] = floatval( $posted[ 'expected_installment_amount' ][ $installment ] );
                            }
                        }

                        if ( $existing_payment_scheduled !== $new_payment_scheduled ) {
                            $payment->update_prop( 'payment_schedules', $new_payment_scheduled );
                            $payment->update_prop( 'next_installment_amount', $payment->get_next_installment_amount() );
                            $payment->update_prop( 'remaining_payable_amount', $payment->get_remaining_payable_amount() );
                            $payment->update_prop( 'total_payable_amount', $payment->get_total_payable_amount() );
                        }
                    }
                }

                if ( ! empty( $posted[ '_sumo_pp_payment_status' ] ) ) {
                    switch ( str_replace( SUMO_PP_PLUGIN_PREFIX, '', $posted[ '_sumo_pp_payment_status' ] ) ) {
                        case 'in_progress':
                            $payment->process_initial_payment( array(
                                /* translators: 1: initial order */
                                'content' => sprintf( __( 'Payment is approved by Admin. Initial payment of order#%s is paid. Payment is in progress', 'sumopaymentplans' ), $payment->get_initial_payment_order_id() ),
                                'status'  => 'success',
                                'message' => __( 'Initial Payment Success', 'sumopaymentplans' ),
                                    ), true );
                            break;
                        case 'completed':
                            $balance_payable_order = wc_get_order( $payment->get_balance_payable_order_id() );
                            $balance_payable_order = $balance_payable_order ? $balance_payable_order : wc_get_order( $payment->get_initial_payment_order_id() );

                            $payment->payment_complete( $balance_payable_order, array(
                                'content' => __( 'Admin manually completed the payment.', 'sumopaymentplans' ),
                                'status'  => 'success',
                                'message' => __( 'Balance Payment Completed', 'sumopaymentplans' ),
                            ) );
                            break;
                        case 'cancelled':
                            $payment->cancel_payment( array(
                                'content' => __( 'Admin manually cancelled the payment.', 'sumopaymentplans' ),
                                'status'  => 'success',
                                'message' => __( 'Balance Payment Cancelled', 'sumopaymentplans' ),
                            ) );
                            break;
                    }
                }
                if ( ! empty( $posted[ '_sumo_pp_customer_email' ] ) && $posted[ '_sumo_pp_customer_email' ] !== $payment->get_customer_email() ) {
                    if ( ! filter_var( $posted[ '_sumo_pp_customer_email' ], FILTER_VALIDATE_EMAIL ) === false ) {

                        $payment->update_prop( 'customer_email', $posted[ '_sumo_pp_customer_email' ] );
                        /* translators: 1: customer email */
                        $payment->add_payment_note( sprintf( __( 'Admin has changed the payment customer email to %s. Customer will be notified via email by this Mail ID only.', 'sumopaymentplans' ), $posted[ '_sumo_pp_customer_email' ] ), 'success', __( 'Customer Email Changed Manually', 'sumopaymentplans' ) );
                    }
                }
                if ( ! empty( $posted[ '_sumo_pp_payment_actions' ] ) ) {
                    $action = wc_clean( $posted[ '_sumo_pp_payment_actions' ] );

                    if ( strstr( $action, 'send_email_' ) ) {
                        // Ensure gateways are loaded in case they need to insert data into the emails
                        WC()->payment_gateways();
                        WC()->shipping();

                        $template_id = str_replace( SUMO_PP_PLUGIN_PREFIX, '', str_replace( 'send_email_', '', $action ) );
                        $order_id    = in_array( $template_id, array(
                                    'deposit_balance_payment_invoice',
                                    'deposit_balance_payment_auto_charge_reminder',
                                    'payment_pending_auth',
                                    'deposit_balance_payment_overdue',
                                    'payment_plan_invoice',
                                    'payment_plan_auto_charge_reminder',
                                    'payment_plan_overdue',
                                ) ) ? $payment->get_balance_payable_order_id() : $payment->get_initial_payment_order_id();

                        // Trigger mailer.
                        if ( $order_id ) {
                            _sumo_pp()->mailer->send( $template_id, $order_id, $payment, true );
                        }
                    }
                }
                break;
        }
    }
}

new SUMO_PP_Admin_Metaboxes();
