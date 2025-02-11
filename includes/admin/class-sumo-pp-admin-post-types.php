<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Post Types Admin
 * 
 * @class SUMO_PP_Admin_Post_Types
 * @package Class
 */
class SUMO_PP_Admin_Post_Types {

    protected static $custom_post_types = array(
        'sumo_payment_plans' => 'payment_plans',
        'sumo_pp_payments'   => 'payments',
        'sumo_pp_masterlog'  => 'masterlog',
        'sumo_pp_cron_jobs'  => 'cron_jobs',
    );

    /**
     * Init SUMO_PP_Admin_Post_Types.
     */
    public static function init() {
        foreach ( self::$custom_post_types as $post_type => $meant_for ) {
            $manage = 'sumo_pp_masterlog' === $post_type ? false : true;
            if ( ! apply_filters( "sumopaymentplans_manage_{$post_type}", $manage ) ) {
                continue;
            }

            add_filter( "manage_{$post_type}_posts_columns", __CLASS__ . "::define_{$meant_for}_columns" );
            add_filter( "manage_edit-{$post_type}_sortable_columns", __CLASS__ . '::define_sortable_columns' );
            add_filter( "bulk_actions-edit-{$post_type}", __CLASS__ . '::define_bulk_actions' );
            add_action( "manage_{$post_type}_posts_custom_column", __CLASS__ . "::render_{$meant_for}_columns", 10, 2 );
        }

        add_filter( 'manage_shop_order_posts_columns', __CLASS__ . '::define_shop_order_columns', 11 );
        add_filter( 'woocommerce_shop_order_list_table_columns', __CLASS__ . '::define_shop_order_columns', 11 );

        add_action( 'manage_shop_order_posts_custom_column', __CLASS__ . '::render_shop_order_columns', 11, 2 );
        add_action( 'woocommerce_shop_order_list_table_custom_column', __CLASS__ . '::render_shop_order_columns', 11, 2 );

        add_filter( 'wp_untrash_post_status', array( __CLASS__, 'wp_untrash_post_status' ), 10, 3 );
        add_filter( 'enter_title_here', __CLASS__ . '::enter_title_here', 1, 2 );
        add_filter( 'post_row_actions', __CLASS__ . '::row_actions', 99, 2 );
        add_action( 'manage_posts_extra_tablenav', __CLASS__ . '::extra_tablenav' );
        add_filter( 'request', __CLASS__ . '::request_query' );
        add_action( 'admin_init', __CLASS__ . '::admin_actions' );

        add_filter( 'get_search_query', __CLASS__ . '::search_label' );
        add_filter( 'query_vars', __CLASS__ . '::add_custom_query_var' );
        add_action( 'parse_query', __CLASS__ . '::search_custom_fields' );
        add_action( 'restrict_manage_posts', __CLASS__ . '::render_search_filters' );
    }

    /**
     * Define which payment plan columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_payment_plans_columns( $existing_columns ) {
        $columns = array(
            'cb'               => $existing_columns[ 'cb' ],
            'plan_name'        => __( 'Payment Plan Name', 'sumopaymentplans' ),
            'plan_description' => __( 'Payment Plan Description', 'sumopaymentplans' ),
        );
        return $columns;
    }

    /**
     * Define which payment columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_payments_columns( $existing_columns ) {
        $columns = array(
            'cb'                       => $existing_columns[ 'cb' ],
            'payment_status'           => __( 'Payment Status', 'sumopaymentplans' ),
            'payment_number'           => __( 'Payment Identification Number', 'sumopaymentplans' ),
            'product_name'             => __( 'Product Name', 'sumopaymentplans' ),
            'order_id'                 => __( 'Order ID', 'sumopaymentplans' ),
            'buyer_email'              => __( 'Buyer Email', 'sumopaymentplans' ),
            'billing_name'             => __( 'Billing Name', 'sumopaymentplans' ),
            'payment_type'             => __( 'Payment Type', 'sumopaymentplans' ),
            'payment_plan'             => __( 'Payment Plan', 'sumopaymentplans' ),
            'payment_mode'             => __( 'Payment Mode', 'sumopaymentplans' ),
            'remaining_installments'   => __( 'Remaining Installments', 'sumopaymentplans' ),
            'remaining_payable_amount' => __( 'Remaining Payable Amount', 'sumopaymentplans' ),
            'next_installment_amount'  => __( 'Next Installment Amount', 'sumopaymentplans' ),
            'payment_start_date'       => __( 'Payment Start Date', 'sumopaymentplans' ),
            'next_payment_date'        => __( 'Next Payment Date', 'sumopaymentplans' ),
            'payment_ending_date'      => __( 'Payment Ending Date', 'sumopaymentplans' ),
            'last_payment_date'        => __( 'Previous Payment Date', 'sumopaymentplans' ),
        );
        return $columns;
    }

    /**
     * Define which masterlog columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_masterlog_columns( $existing_columns ) {
        $columns = array(
            'cb'             => $existing_columns[ 'cb' ],
            'status'         => __( 'Status', 'sumopaymentplans' ),
            'message'        => __( 'Message', 'sumopaymentplans' ),
            'user_name'      => __( 'User Name', 'sumopaymentplans' ),
            'payment_number' => __( 'Payment Number', 'sumopaymentplans' ),
            'product_name'   => __( 'Product Name', 'sumopaymentplans' ),
            'payment_plan'   => __( 'Payment Plan', 'sumopaymentplans' ),
            'order_id'       => __( 'Order ID', 'sumopaymentplans' ),
            'log'            => __( 'Log', 'sumopaymentplans' ),
            'date'           => __( 'Date', 'sumopaymentplans' ),
        );
        return $columns;
    }

    /**
     * Define which cron job columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_cron_jobs_columns( $existing_columns ) {
        $columns = array(
            'cb'             => $existing_columns[ 'cb' ],
            'job_id'         => __( 'Job ID', 'sumopaymentplans' ),
            'payment_number' => __( 'Payment Number', 'sumopaymentplans' ),
            'job_name'       => __( 'Job Name', 'sumopaymentplans' ),
            'next_run'       => __( 'Next Run', 'sumopaymentplans' ),
            'args'           => __( 'Arguments', 'sumopaymentplans' ),
        );
        return $columns;
    }

    /**
     * Define which wc order columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_shop_order_columns( $existing_columns ) {
        $existing_columns[ '_sumo_pp_payment_info' ] = __( 'Payment Info', 'sumopaymentplans' );
        return $existing_columns;
    }

    /**
     * Define which columns are sortable.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_sortable_columns( $existing_columns ) {
        global $current_screen;

        if ( ! isset( $current_screen->post_type ) ) {
            return $existing_columns;
        }

        $columns = array();
        switch ( $current_screen->post_type ) {
            case 'sumo_payment_plans':
                $columns = array(
                    'plan_name' => 'title',
                );
                break;
            case 'sumo_pp_payments':
                $columns = array(
                    'payment_number'          => 'ID',
                    'payment_type'            => 'payment_type',
                    'order_id'                => 'initial_payment_order_id',
                    'remaining_installments'  => 'remaining_installments',
                    'next_installment_amount' => 'next_installment_amount',
                    'buyer_email'             => 'customer_email',
                    'next_payment_date'       => 'next_payment_date',
                    'last_payment_date'       => 'last_payment_date',
                    'payment_ending_date'     => 'payment_end_date',
                );
                break;
            case 'sumo_pp_masterlog':
                $columns = array(
                    'payment_number' => 'payment_number',
                    'order_id'       => 'payment_order_id',
                    'user_name'      => 'user_name',
                );
                break;
            case 'sumo_pp_cron_jobs':
                $columns = array(
                    'job_id'         => 'ID',
                    'payment_number' => 'ID',
                );
                break;
        }

        return wp_parse_args( $columns, $existing_columns );
    }

    /**
     * Define bulk actions.
     *
     * @param array $actions Existing actions.
     * @return array
     */
    public static function define_bulk_actions( $actions ) {
        unset( $actions[ 'edit' ] );
        return $actions;
    }

    /**
     * Render individual payment plan columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_payment_plans_columns( $column, $post_id ) {

        switch ( $column ) {
            case 'plan_name':
                echo '<a href="' . esc_url( admin_url( "post.php?post={$post_id}&action=edit" ) ) . '">' . wp_kses_post( get_the_title( $post_id ) ) . '</a>';
                break;
            case 'plan_description':
                $description = get_post_meta( $post_id, '_plan_description', true );
                echo ! empty( $description ) ? wp_kses_post( $description ) : '--';
                break;
        }
    }

    /**
     * Render individual payment columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_payments_columns( $column, $post_id ) {
        $payment               = new SUMO_PP_Payment( $post_id );
        $initial_payment_order = _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() );

        switch ( $column ) {
            case 'payment_status':
                _sumo_pp_payment_status_html( $payment, 'admin' );
                break;
            case 'payment_number':
                echo '<a href="' . esc_url( admin_url( "post.php?post={$post_id}&action=edit" ) ) . '">#' . esc_html( $payment->get_payment_number() ) . '</a>';
                break;
            case 'product_name':
                echo wp_kses_post( $payment->get_formatted_product_name( array( 'page' => 'admin' ) ) );
                break;
            case 'order_id':
                echo '<a href="' . esc_url( admin_url( "post.php?post={$payment->get_initial_payment_order_id()}&action=edit" ) ) . '">#' . esc_html( $payment->get_initial_payment_order_id() ) . '</a>';
                break;
            case 'buyer_email':
                echo wp_kses_post( $payment->get_customer_email() );
                break;
            case 'billing_name':
                if ( $initial_payment_order ) {
                    echo esc_html( $initial_payment_order->get_billing_first_name() . ' ' . $initial_payment_order->get_billing_last_name() );
                } else {
                    echo 'N/A';
                }
                break;
            case 'payment_type':
                echo esc_html( $payment->get_payment_type( true ) );
                break;
            case 'payment_plan':
                if ( 'payment-plans' === $payment->get_payment_type() ) {
                    echo '<a href="' . esc_url( admin_url( "post.php?post={$payment->get_plan( 'ID' )}&action=edit" ) ) . '">' . wp_kses_post( $payment->get_plan( 'name' ) ) . '</a>';
                } else {
                    echo '--';
                }
                break;
            case 'payment_mode':
                'auto' === $payment->get_payment_mode() ? esc_html_e( 'Automatic', 'sumopaymentplans' ) : esc_html_e( 'Manual', 'sumopaymentplans' );
                break;
            case 'remaining_installments':
                echo absint( $payment->get_prop( 'remaining_installments' ) );
                break;
            case 'remaining_payable_amount':
                echo wp_kses_post( wc_price( $payment->get_prop( 'remaining_payable_amount' ), array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) );
                break;
            case 'next_installment_amount':
                echo wp_kses_post( wc_price( $payment->get_prop( 'next_installment_amount' ), array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) );
                break;
            case 'payment_start_date':
                echo $payment->get_prop( 'payment_start_date' ) ? esc_html( _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_start_date' ), 'admin' ) ) : '--';
                break;
            case 'next_payment_date':
                echo $payment->get_prop( 'next_payment_date' ) ? esc_html( _sumo_pp_get_date_to_display( $payment->get_prop( 'next_payment_date' ), 'admin' ) ) : '--';
                break;
            case 'payment_ending_date':
                echo 'payment-plans' === $payment->get_payment_type() && $payment->get_prop( 'payment_end_date' ) ? esc_html( _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_end_date' ), 'admin' ) ) : '--';
                break;
            case 'last_payment_date':
                echo $payment->get_prop( 'last_payment_date' ) ? esc_html( _sumo_pp_get_date_to_display( $payment->get_prop( 'last_payment_date' ), 'admin' ) ) : '--';
                break;
        }
    }

    /**
     * Render individual masterlog columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_masterlog_columns( $column, $post_id ) {
        $payment = _sumo_pp_get_payment( get_post_meta( $post_id, '_payment_id', true ) );

        switch ( $column ) {
            case 'status':
                $status = get_post_meta( $post_id, '_status', true );

                if ( in_array( $status, array( 'success', 'pending' ) ) ) {
                    echo '<div style="background-color: #259e12;width:50px;height:20px;text-align:center;color:#ffffff;padding:5px;">Success</div>';
                } else {
                    echo '<div style="background-color: #ef381c;width:50px;height:20px;text-align:center;color:#ffffff;padding:5px;">Failure</div>';
                }
                break;
            case 'message':
                echo wp_kses_post( get_post_meta( $post_id, '_message', true ) );
                break;
            case 'user_name':
                echo esc_html( get_post_meta( $post_id, '_user_name', true ) );
                break;
            case 'payment_number':
                $payment_id     = get_post_meta( $post_id, '_payment_id', true );
                $payment_number = get_post_meta( $post_id, '_payment_number', true );

                echo '<a href="' . esc_url( admin_url( "post.php?post={$payment_id}&action=edit" ) ) . '">#' . esc_html( $payment_number ) . '</a>';
                break;
            case 'payment_plan':
                $payment_id = get_post_meta( $post_id, '_payment_id', true );

                if ( 'payment-plans' === get_post_meta( $payment_id, '_payment_type', true ) ) {
                    $plan_id = get_post_meta( $payment_id, '_plan_id', true );

                    echo '<a href="' . esc_url( admin_url( "post.php?post={$plan_id}&action=edit" ) ) . '">' . wp_kses_post( get_the_title( $plan_id ) ) . '</a>';
                } else {
                    echo '--';
                }
                break;
            case 'product_name':
                echo $payment ? wp_kses_post( $payment->get_formatted_product_name( array( 'page' => 'admin' ) ) ) : '';
                break;
            case 'order_id':
                $payment_order_id = get_post_meta( $post_id, '_payment_order_id', true );

                echo '<a href="' . esc_url( admin_url( "post.php?post={$payment_order_id}&action=edit" ) ) . '">#' . esc_html( $payment_order_id ) . '</a>';
                break;
            case 'log':
                echo wp_kses_post( get_post_meta( $post_id, '_log', true ) );
                break;
        }
    }

    /**
     * Render individual cron job columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_cron_jobs_columns( $column, $post_id ) {
        $payment_id = absint( get_post_meta( $post_id, '_payment_id', true ) );
        $jobs       = get_post_meta( $post_id, '_scheduled_jobs', true );

        $job_name  = array();
        $next_run  = array();
        $arguments = array();

        if ( isset( $jobs[ $payment_id ] ) && is_array( $jobs[ $payment_id ] ) ) {
            foreach ( $jobs[ $payment_id ] as $_job_name => $args ) {
                if ( ! is_array( $args ) ) {
                    continue;
                }

                $job_name[] = $_job_name;

                $job_time = '';
                foreach ( $args as $job_timestamp => $job_args ) {
                    if ( ! is_numeric( $job_timestamp ) ) {
                        continue;
                    }

                    $job_time .= _sumo_pp_get_date( $job_timestamp ) . nl2br( "\n[" . _sumo_pp_get_date_difference( $job_timestamp ) . "]\n\n" );
                }
                $next_run[] = $job_time;

                $arg = '';
                foreach ( $args as $job_timestamp => $job_args ) {
                    if ( ! is_array( $job_args ) ) {
                        continue;
                    }
                    $arg .= '"' . implode( ', ', $job_args ) . '",&nbsp;<br>';
                }
                if ( '' !== $arg ) {
                    $arguments[] = $arg;
                }
            }
        }

        switch ( $column ) {
            case 'job_id':
                echo '#' . esc_html( $post_id );
                break;
            case 'payment_number':
                $payment_number = get_post_meta( $payment_id, '_payment_number', true );

                echo '<a href="' . esc_url( admin_url( "post.php?post={$payment_id}&action=edit" ) ) . '">#' . esc_html( $payment_number ) . '</a>';
                break;
            case 'job_name':
                echo $job_name ? wp_kses_post( implode( ',' . str_repeat( '</br>', 4 ), $job_name ) ) : 'None';
                break;
            case 'next_run':
                echo $next_run ? '<b>*</b>' . wp_kses_post( implode( '<b>*</b> ', $next_run ) ) : 'None';
                break;
            case 'args':
                echo $arguments ? wp_kses_post( implode( str_repeat( '</br>', 4 ), $arguments ) ) : 'None';
                break;
        }
    }

    /**
     * Render individual wc order columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_shop_order_columns( $column, $post_id ) {

        switch ( $column ) {
            case '_sumo_pp_payment_info':
                $order = _sumo_pp_maybe_get_order_instance( $post_id );

                if ( _sumo_pp_is_payment_order( $order ) ) {
                    if ( _sumo_pp_is_parent_order( $order ) ) {
                        $payments = _sumo_pp()->query->get( array(
                            'type'         => 'sumo_pp_payments',
                            'status'       => array_keys( _sumo_pp_get_payment_statuses() ),
                            'meta_key'     => '_initial_payment_order_id',
                            'meta_value'   => $order->get_id(),
                            'meta_compare' => '=',
                                ) );

                        if ( ! empty( $payments ) ) {
                            $payments_link = array();
                            foreach ( $payments as $payment_id ) {
                                $payment = _sumo_pp_get_payment( $payment_id );

                                if ( $payment ) {
                                    $payments_link[] = "<a href='" . esc_url( admin_url( "post.php?post={$payment->id}&action=edit" ) ) . "'>#{$payment->get_payment_number()}</a>";
                                }
                            }

                            if ( ! empty( $payments_link ) ) {
                                if ( count( $payments_link ) > 1 ) {
                                    /* translators: 1: payment number admin url */
                                    printf( esc_html__( 'This Order is linked with payment(s) %s', 'sumopaymentplans' ), wp_kses_post( implode( ', ', $payments_link ) ) );
                                } else {
                                    /* translators: 1: payment number admin url */
                                    printf( esc_html__( 'This Order is linked with payment%s', 'sumopaymentplans' ), wp_kses_post( implode( ',', $payments_link ) ) );
                                }

                                echo wp_kses_post( __( '<br><b>Relationship:</b> Parent Order', 'sumopaymentplans' ) );
                            }
                        }
                    } else if ( _sumo_pp_is_child_order( $order ) ) {
                        $payment = _sumo_pp_get_payment( $order->get_meta( '_payment_id', true ) );

                        if ( $payment ) {
                            /* translators: 1: payment number admin url */
                            printf( esc_html__( 'This Order is linked with payment%s', 'sumopaymentplans' ), "<a href='" . esc_url( admin_url( "post.php?post={$payment->id}&action=edit" ) ) . "'>#" . esc_html( $payment->get_payment_number() ) . '</a>' );

                            echo wp_kses_post( __( '<br><b>Relationship:</b> Balance Payment Order<br>', 'sumopaymentplans' ) );

                            $renewal_orders = $payment->get_balance_paid_orders();
                            if ( $renewal_orders ) {
                                foreach ( $renewal_orders as $installment => $order_id ) {
                                    if ( $order_id === $order->get_id() ) {
                                        /* translators: 1: installment number */
                                        echo wp_kses_post( sprintf( __( '<b>Installment:</b> %s', 'sumopaymentplans' ), $installment + 1 ) );
                                    }
                                }
                            }
                        }
                    }
                } else {
                    echo '--';
                }
                break;
        }
    }

    /**
     * Ensure statuses are correctly reassigned when restoring our posts.
     *
     * @param string $new_status      The new status of the post being restored.
     * @param int    $post_id         The ID of the post being restored.
     * @param string $previous_status The status of the post at the point where it was trashed.
     * @return string
     */
    public static function wp_untrash_post_status( $new_status, $post_id, $previous_status ) {
        if ( in_array( get_post_type( $post_id ), array_keys( self::$custom_post_types ), true ) ) {
            $new_status = $previous_status;
        }

        return $new_status;
    }

    /**
     * Change title boxes in admin.
     * 
     * @param  string $text
     * @param  object $post
     * @return string
     */
    public static function enter_title_here( $text, $post ) {
        switch ( $post->post_type ) {
            case 'sumo_payment_plans':
                $text = __( 'Plan name', 'sumopaymentplans' );
                break;
        }
        return $text;
    }

    /**
     * Set row actions.
     *
     * @param array   $actions Array of actions.
     * @param WP_Post $post Current post object.
     * @return array
     */
    public static function row_actions( $actions, $post ) {
        switch ( $post->post_type ) {
            case 'sumo_pp_payments':
                unset( $actions[ 'inline hide-if-no-js' ], $actions[ 'view' ] );

                $payment = _sumo_pp_get_payment( $post );
                if ( $payment ) {
                    if ( $payment->has_status( 'await_aprvl' ) ) {
                        $actions[ 'approve-payment' ] = sprintf( '<span class="edit"><a href="%s" aria-label="Approve">Approve</a></span>', admin_url( "edit.php?post_type=sumo_pp_payments&payment_id={$payment->id}&action=approve&_sumo_pp_nonce=" . wp_create_nonce( "{$payment->id}" ) ) );
                    }
                    if ( $payment->has_status( 'await_cancl' ) ) {
                        $actions[ 'approve-cancel' ] = sprintf( '<span class="edit"><a href="%s" aria-label="Cancel">Cancel</a></span>', admin_url( "edit.php?post_type=sumo_pp_payments&payment_id={$payment->id}&action=cancel&_sumo_pp_nonce=" . wp_create_nonce( "{$payment->id}" ) ) );
                    }
                }
                break;
            case 'sumo_payment_plans':
                if ( $post ) {
                    $actions[ 'duplicate' ] = sprintf( '<span class="duplicate"><a href="%s" aria-label="Cancel">%s</a></span>', admin_url( "edit.php?post_type=sumo_payment_plans&payment_plan_id={$post->ID}&action=duplicate&_sumo_pp_nonce=" . wp_create_nonce( "{$post->ID}" ) ), esc_html__( 'Duplicate', 'sumopaymentplans' ) );
                }

                unset( $actions[ 'inline hide-if-no-js' ], $actions[ 'view' ], $actions[ 'edit' ] );
                break;
            case 'sumo_pp_masterlog':
            case 'sumo_pp_cron_jobs':
                unset( $actions[ 'inline hide-if-no-js' ], $actions[ 'view' ], $actions[ 'edit' ] );
                break;
        }
        return $actions;
    }

    /**
     * Render blank slate.
     * 
     * @param string $which String which tablenav is being shown.
     */
    public static function extra_tablenav( $which ) {
        if ( 'top' === $which && 'sumo_pp_payments' === get_post_type() ) {
            echo '<a class="button-primary" target="blank" href="' . esc_url( SUMO_PP_Payments_Exporter::get_exporter_page_url() ) . '">' . esc_html__( 'Export', 'sumopaymentplans' ) . '</a>';
        }
    }

    /**
     * Handle any filters.
     *
     * @param array $query_vars Query vars.
     * @return array
     */
    public static function request_query( $query_vars ) {
        global $typenow;

        if ( ! in_array( $typenow, array_keys( self::$custom_post_types ) ) ) {
            return $query_vars;
        }

        //Sorting
        if ( empty( $query_vars[ 'orderby' ] ) ) {
            $query_vars[ 'orderby' ] = 'ID';
        }

        if ( empty( $query_vars[ 'order' ] ) ) {
            $query_vars[ 'order' ] = 'DESC';
        }

        if ( ! empty( $query_vars[ 'orderby' ] ) ) {
            switch ( $query_vars[ 'orderby' ] ) {
                case 'next_payment_date':
                case 'last_payment_date':
                case 'payment_end_date':
                    $query_vars[ 'meta_key' ]  = "_{$query_vars[ 'orderby' ]}";
                    $query_vars[ 'meta_type' ] = 'DATETIME';
                    $query_vars[ 'orderby' ]   = 'meta_value';
                    break;
                case 'customer_email':
                case 'user_name':
                case 'payment_type':
                case 'payment_number':
                    $query_vars[ 'meta_key' ]  = "_{$query_vars[ 'orderby' ]}";
                    $query_vars[ 'orderby' ]   = 'meta_value';
                    break;
                case 'initial_payment_order_id':
                case 'payment_order_id':
                case 'remaining_installments':
                case 'next_installment_amount':
                    $query_vars[ 'meta_key' ]  = "_{$query_vars[ 'orderby' ]}";
                    $query_vars[ 'orderby' ]   = 'meta_value_num';
                    break;
            }
        }

        if ( ! empty( $_REQUEST[ '_sumo_pp_get_filtered_product' ][ 0 ] ) ) {
            if ( ! empty( $_REQUEST[ '_sumo_pp_get_filtered_plan' ][ 0 ] ) ) {
                $query_vars[ 'meta_query' ] = array(
                    'relation' => 'AND',
                    array(
                        'key'   => '_product_id',
                        'value' => absint( $_REQUEST[ '_sumo_pp_get_filtered_product' ][ 0 ] ),
                    ),
                    array(
                        'key'   => '_plan_id',
                        'value' => absint( $_REQUEST[ '_sumo_pp_get_filtered_plan' ][ 0 ] ),
                    ),
                );
            } else {
                $query_vars[ 'meta_key' ]   = '_product_id';
                $query_vars[ 'meta_value' ] = absint( $_REQUEST[ '_sumo_pp_get_filtered_product' ][ 0 ] );
            }
        } else if ( ! empty( $_REQUEST[ '_sumo_pp_get_filtered_plan' ][ 0 ] ) ) {
            $query_vars[ 'meta_key' ]   = '_plan_id';
            $query_vars[ 'meta_value' ] = absint( $_REQUEST[ '_sumo_pp_get_filtered_plan' ][ 0 ] );
        }
        return $query_vars;
    }

    public static function admin_actions() {
        $requested = $_GET;

        if ( empty( $requested[ '_sumo_pp_nonce' ] ) || empty( $requested[ 'action' ] ) || empty( $requested[ 'post_type' ] ) ) {
            return;
        }

        if ( 'sumo_pp_payments' === $requested[ 'post_type' ] ) {
            if ( empty( $requested[ 'payment_id' ] ) || ! wp_verify_nonce( $requested[ '_sumo_pp_nonce' ], $requested[ 'payment_id' ] ) ) {
                return;
            }

            $payment = _sumo_pp_get_payment( $requested[ 'payment_id' ] );
            switch ( $requested[ 'action' ] ) {
                case 'approve':
                    $payment->process_initial_payment(
                            array(
                                /* translators: 1: initial payment order id */
                                'content' => sprintf( __( 'Payment is approved by Admin. Initial payment of order#%s is paid. Payment is in progress', 'sumopaymentplans' ), $payment->get_initial_payment_order_id() ),
                                'status'  => 'success',
                                'message' => __( 'Initial Payment Success', 'sumopaymentplans' ),
                            ),
                            true
                    );
                    break;
                case 'cancel':
                    $payment->cancel_payment(
                            array(
                                'content' => __( 'Admin manually cancelled the payment.', 'sumopaymentplans' ),
                                'status'  => 'success',
                                'message' => __( 'Balance Payment Cancelled', 'sumopaymentplans' ),
                            )
                    );
                    break;
            }

            wp_safe_redirect( esc_url_raw( admin_url( 'edit.php?post_type=sumo_pp_payments' ) ) );
            exit;
        } else if ( 'sumo_payment_plans' === $requested[ 'post_type' ] ) {
            if ( empty( $requested[ 'payment_plan_id' ] ) || ! wp_verify_nonce( $requested[ '_sumo_pp_nonce' ], $requested[ 'payment_plan_id' ] ) ) {
                return;
            }

            switch ( $requested[ 'action' ] ) {
                case 'duplicate':
                    $post_id         = $requested[ 'payment_plan_id' ];
                    $post            = get_post( $post_id );
                    $current_user    = wp_get_current_user();
                    $new_post_author = $current_user->ID;

                    if ( $post ) {
                        $args = array(
                            'comment_status' => $post->comment_status,
                            'ping_status'    => $post->ping_status,
                            'post_author'    => $new_post_author,
                            'post_content'   => $post->post_content,
                            'post_excerpt'   => $post->post_excerpt,
                            'post_name'      => $post->post_title,
                            'post_parent'    => $post->post_parent,
                            'post_password'  => $post->post_password,
                            'post_status'    => 'draft',
                            /* translators: %s: Post Title */
                            'post_title'     => sprintf( __( '%s (Copy)', 'sumopaymentplans' ), esc_html( $post->post_title ) ),
                            'post_type'      => $post->post_type,
                            'to_ping'        => $post->to_ping,
                            'menu_order'     => $post->menu_order,
                        );

                        $new_post_id = wp_insert_post( $args );
                        $post_meta   = get_post_meta( $post_id );

                        if ( $post_meta ) {
                            foreach ( $post_meta as $meta_key => $meta_values ) {
                                foreach ( $meta_values as $meta_value ) {
                                    add_post_meta( $new_post_id, $meta_key, $meta_value );
                                }
                            }
                        }

                        wp_safe_redirect( esc_url_raw( admin_url( 'post.php?action=edit&post=' . $new_post_id ) ) );
                        exit;
                    } else {
                        wp_die( 'Post creation failed, could not find original post.' );
                    }
                    break;
            }
        }
    }

    /**
     * Change the label when searching index.
     *
     * @param mixed $query Current search query.
     * @return string
     */
    public static function search_label( $query ) {
        global $pagenow, $typenow;

        if ( 'edit.php' !== $pagenow || ! in_array( $typenow, array_keys( self::$custom_post_types ) ) || ! get_query_var( "{$typenow}_search" ) || ! isset( $_GET[ 's' ] ) ) { // WPCS: input var ok.
            return $query;
        }

        return wc_clean( wp_unslash( $_GET[ 's' ] ) ); // WPCS: input var ok, sanitization ok.
    }

    /**
     * Query vars for custom searches.
     *
     * @param mixed $public_query_vars Array of query vars.
     * @return array
     */
    public static function add_custom_query_var( $public_query_vars ) {
        return array_merge( $public_query_vars, array_map( function( $type ) {
                    return "{$type}_search";
                }, array_keys( self::$custom_post_types ) ) );
    }

    /**
     * Search custom fields as well as content.
     *
     * @param WP_Query $wp Query object.
     */
    public static function search_custom_fields( $wp ) {
        global $pagenow, $wpdb;

        if ( 'edit.php' !== $pagenow || empty( $wp->query_vars[ 's' ] ) || ! in_array( $wp->query_vars[ 'post_type' ], array_keys( self::$custom_post_types ) ) || ! isset( $_GET[ 's' ] ) ) { // WPCS: input var ok.
            return;
        }

        $wpdb_ref = &$wpdb;
        $term     = str_replace( '#', '', wc_clean( wp_unslash( $wp->query_vars[ 's' ] ) ) );
        $post_ids = array();

        switch ( $wp->query_vars[ 'post_type' ] ) {
            case 'sumo_payment_plans':
                $search_fields = array(
                    '_plan_description',
                );
                break;
            case 'sumo_pp_payments':
                $search_fields = array(
                    '_payment_number',
                    '_product_id',
                    '_initial_payment_order_id',
                    '_balance_payable_order_id',
                    '_customer_email',
                    '_product_type',
                    '_payment_type',
                    '_plan_id',
                );

                $order_search_fields = array(
                    '_billing_address_index',
                    '_billing_first_name',
                    '_billing_last_name',
                );

                if ( ! is_numeric( $term ) ) {
                    if ( false !== stripos( 'pay in deposit', $term ) ) {
                        $term = 'pay-in-deposit';
                    }

                    if ( false !== stripos( 'payment plans', $term ) ) {
                        $term = 'payment-plans';
                    }

                    if ( false !== stripos( get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_label' ), $term ) ) {
                        $term = 'order';
                    }
                }
                break;
            case 'sumo_pp_masterlog':
                $search_fields = array(
                    '_message',
                    '_user_name',
                    '_payment_id',
                    '_payment_number',
                    '_payment_order_id',
                    '_log',
                );
                break;
            case 'sumo_pp_cron_jobs':
                $search_fields = array(
                    '_payment_id',
                );
                break;
        }

        if ( empty( $search_fields ) ) {
            return;
        }

        if ( is_numeric( $term ) ) {
            $post_ids = array_unique(
                    array_merge( array( absint( $term ) ), $wpdb_ref->get_col(
                                    $wpdb_ref->prepare(
                                            "SELECT DISTINCT p1.post_id FROM {$wpdb_ref->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $search_fields ) ) . "')", '%' . $wpdb_ref->esc_like( wc_clean( $term ) ) . '%'
                                    )
                            )
                    ) );
        } else {
            //may be payment is searched based on billing details so that we are using as like WC Order search
            if ( ! empty( $order_search_fields ) ) {
                $maybe_order_ids = array_unique(
                        $wpdb_ref->get_col(
                                $wpdb_ref->prepare(
                                        "SELECT DISTINCT p1.post_id FROM {$wpdb_ref->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $order_search_fields ) ) . "')", '%' . $wpdb_ref->esc_like( wc_clean( $term ) ) . '%'
                                )
                        ) );

                $post_ids = $wpdb_ref->get_col(
                        $wpdb_ref->prepare(
                                "SELECT DISTINCT p1.post_id FROM {$wpdb_ref->postmeta} p1 WHERE p1.meta_key LIKE %s AND p1.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $maybe_order_ids ) ) . "')", '_initial_payment_order_id'
                        ) );
            }

            $post_ids = array_unique(
                    array_merge(
                            $post_ids, $wpdb_ref->get_col(
                                    $wpdb_ref->prepare(
                                            "SELECT DISTINCT p1.post_id FROM {$wpdb_ref->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $search_fields ) ) . "')", '%' . $wpdb_ref->esc_like( wc_clean( $term ) ) . '%'
                                    )
                            )
                    ) );
        }

        if ( ! empty( $post_ids ) ) {
            // Remove "s" - we don't want to search payment name.
            unset( $wp->query_vars[ 's' ] );

            // so we know we're doing this.
            $wp->query_vars[ "{$wp->query_vars[ 'post_type' ]}_search" ] = true;

            // Search by found posts.
            $wp->query_vars[ 'post__in' ] = array_merge( $post_ids, array( 0 ) );
        }
    }

    /**
     * Render search filters.
     */
    public static function render_search_filters() {
        global $typenow;

        if ( 'sumo_pp_payments' === $typenow ) {
            _sumo_pp_wc_search_field( array(
                'class'       => 'wc-product-search',
                'name'        => '_sumo_pp_get_filtered_product',
                'type'        => 'product',
                'css'         => 'width: 35%;',
                'multiple'    => false,
                'options'     => ! empty( $_GET[ '_sumo_pp_get_filtered_product' ][ 0 ] ) ? wc_clean( $_GET[ '_sumo_pp_get_filtered_product' ] ) : array(),
                'action'      => 'woocommerce_json_search_products_and_variations',
                'placeholder' => __( 'Search for a product&hellip;', 'sumopaymentplans' ),
            ) );

            _sumo_pp_wc_search_field( array(
                'class'       => 'wc-product-search',
                'name'        => '_sumo_pp_get_filtered_plan',
                'type'        => 'payment_plans',
                'css'         => 'width: 35%;',
                'multiple'    => false,
                'options'     => ! empty( $_GET[ '_sumo_pp_get_filtered_plan' ][ 0 ] ) ? wc_clean( $_GET[ '_sumo_pp_get_filtered_plan' ] ) : array(),
                'action'      => '_sumo_pp_json_search_payment_plans',
                'placeholder' => __( 'Search for a payment plan&hellip;', 'sumopaymentplans' ),
            ) );
        }
    }
}

SUMO_PP_Admin_Post_Types::init();
