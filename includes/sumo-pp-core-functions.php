<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

include_once 'sumo-pp-order-functions.php';
include_once 'sumo-pp-conditional-functions.php';
include_once 'sumo-pp-template-functions.php';
include_once 'deprecated/sumo-pp-deprecated-functions.php';

add_action( 'sumopaymentplans_payment_is_cancelled', '_sumo_pp_switch_to_manual_payment_mode', 99 );
add_action( 'sumopaymentplans_payment_is_failed', '_sumo_pp_switch_to_manual_payment_mode', 99 );
add_action( 'sumopaymentplans_payment_is_in_pending_authorization', '_sumo_pp_switch_to_manual_payment_mode', 99 );
add_action( 'sumopaymentplans_payment_is_completed', '_sumo_pp_switch_to_manual_payment_mode', 99 );

function _sumo_pp_get_payment( $payment ) {
    $payment = new SUMO_PP_Payment( $payment );
    return $payment->exists() ? $payment : false;
}

function _sumo_pp_get_job_scheduler( $payment ) {
    return new SUMO_PP_Job_Scheduler( $payment );
}

function _sumo_pp_get_payment_statuses() {
    $payment_statuses = array(
        SUMO_PP_PLUGIN_PREFIX . 'pending'     => __( 'Pending', 'sumopaymentplans' ),
        SUMO_PP_PLUGIN_PREFIX . 'await_aprvl' => __( 'Awaiting Admin Approval', 'sumopaymentplans' ),
        SUMO_PP_PLUGIN_PREFIX . 'in_progress' => __( 'In Progress', 'sumopaymentplans' ),
        SUMO_PP_PLUGIN_PREFIX . 'completed'   => __( 'Completed', 'sumopaymentplans' ),
        SUMO_PP_PLUGIN_PREFIX . 'overdue'     => __( 'Overdue', 'sumopaymentplans' ),
        SUMO_PP_PLUGIN_PREFIX . 'failed'      => __( 'Failed', 'sumopaymentplans' ),
        SUMO_PP_PLUGIN_PREFIX . 'await_cancl' => __( 'Awaiting Cancel By Admin', 'sumopaymentplans' ),
        SUMO_PP_PLUGIN_PREFIX . 'cancelled'   => __( 'Cancelled', 'sumopaymentplans' ),
        SUMO_PP_PLUGIN_PREFIX . 'pendng_auth' => __( 'Pending Authorization', 'sumopaymentplans' ),
    );

    return $payment_statuses;
}

function _sumo_pp_get_payment_status_name( $status ) {
    $statuses = _sumo_pp_get_payment_statuses();
    $status   = SUMO_PP_PLUGIN_PREFIX === substr( $status, 0, 9 ) ? substr( $status, 9 ) : $status;
    $status   = isset( $statuses[ SUMO_PP_PLUGIN_PREFIX . $status ] ) ? $statuses[ SUMO_PP_PLUGIN_PREFIX . $status ] : $status;
    return $status;
}

function _sumo_pp_get_scheduler_jobs() {
    return array(
        'create_balance_payable_order',
        'automatic_pay',
        'notify_reminder',
        'notify_overdue',
        'retry_payment_in_overdue',
        'notify_awaiting_cancel',
        'notify_cancelled',
    );
}

/**
 * Get date. Format date/time as GMT/UTC
 * If parameters nothing is given then it returns the current date in Y-m-d H:i:s format.
 * 
 * @param int|string $time should be Date/Timestamp.
 * @param int $base_time
 * @param boolean $exclude_hh_mm_ss
 * @param string $format
 * @return string
 */
function _sumo_pp_get_date( $time = 0, $base_time = 0, $exclude_hh_mm_ss = false, $format = 'Y-m-d' ) {
    $timestamp = time();

    if ( is_numeric( $time ) && $time ) {
        $timestamp = $time;
    } else if ( is_string( $time ) && $time ) {
        $timestamp = strtotime( $time );

        if ( is_numeric( $base_time ) && $base_time ) {
            $timestamp = strtotime( $time, $base_time );
        }
    }

    if ( ! $format ) {
        $format = 'Y-m-d';
    }

    if ( $exclude_hh_mm_ss ) {
        return gmdate( "$format", $timestamp );
    }

    return gmdate( "{$format} H:i:s", $timestamp );
}

/**
 * Get Timestamp. Format date/time as GMT/UTC
 * If parameters nothing is given then it returns the current timestamp.
 * 
 * @param int|string $date should be Date/Timestamp 
 * @param int $base_time
 * @param boolean $exclude_hh_mm_ss
 * @return int
 */
function _sumo_pp_get_timestamp( $date = '', $base_time = 0, $exclude_hh_mm_ss = false ) {
    $formatted_date = _sumo_pp_get_date( $date, $base_time, $exclude_hh_mm_ss );

    return strtotime( "{$formatted_date} UTC" );
}

/**
 * Get formatted date for display purpose.
 *
 * @param int|string $date
 * @return string
 */
function _sumo_pp_get_date_to_display( $date, $where = 'frontend' ) {
    $date               = _sumo_pp_get_date( $date );
    $display_time       = apply_filters( 'sumopaymentplans_display_time', ( 'admin' === $where ? true : ( 'frontend' === $where && 'enable' === get_option( SUMO_PP_PLUGIN_PREFIX . 'show_time_in_frontend', 'enable' ) ) ) );
    $date_format        = '' !== get_option( 'date_format' ) ? get_option( 'date_format' ) : 'F j, Y';
    $time_format        = $display_time ? ( '' !== get_option( 'time_format' ) ? get_option( 'time_format' ) : 'g:i a' ) : '';
    $wp_timezone_offset = 'wordpress' === get_option( SUMO_PP_PLUGIN_PREFIX . 'show_timezone_in', 'wordpress' ) ? ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) : 0;

    if ( empty( $time_format ) ) {
        return date_i18n( "{$date_format}", strtotime( $date ) + $wp_timezone_offset );
    }

    return date_i18n( "{$date_format} {$time_format}", strtotime( $date ) + $wp_timezone_offset );
}

/**
 * Format the Date difference from Future date to Curent date.
 *
 * @param int|string $future_date
 * @return string
 */
function _sumo_pp_get_date_difference( $future_date = null ) {
    if ( ! $future_date ) {
        return '';
    }

    $now = new DateTime();

    if ( is_numeric( $future_date ) && $future_date <= _sumo_pp_get_timestamp() ) {
        $interval    = abs( wp_next_scheduled( 'sumopaymentplans_cron_interval' ) - _sumo_pp_get_timestamp() );
        $future_date = wp_next_scheduled( 'sumopaymentplans_cron_interval' );

        //Elapse Time
        if ( $interval < 2 || ( $interval > 290 && $interval <= 300 ) ) {
            return '<b>now</b>';
        }
    }

    if ( is_string( $future_date ) ) {
        $future_date = new DateTime( $future_date );
    } elseif ( is_numeric( $future_date ) ) {
        $future_date = new DateTime( gmdate( 'Y-m-d H:i:s', $future_date ) );
    }

    if ( $future_date ) {
        $interval = $future_date->diff( $now );
        return $interval->format( '<b>%a</b> day(s), <b>%H</b> hour(s), <b>%I</b> minute(s), <b>%S</b> second(s)' );
    }

    return 'now';
}

/**
 * Switch to manual payment mode
 */
function _sumo_pp_switch_to_manual_payment_mode( $payment_id ) {
    $payment = _sumo_pp_get_payment( $payment_id );

    if ( doing_action( 'sumopaymentplans_payment_is_completed' ) ) {
        $payment->set_payment_mode( 'manual', false );
    } else {
        $payment->set_payment_mode( 'manual' );
    }

    $payment->delete_prop( 'stripe_customer_id' );
    $payment->delete_prop( 'stripe_payment_method' );
    $payment->delete_prop( 'stripe_source_id' );
    $payment->delete_prop( 'paypal_billing_agreement_id' );
}

/**
 * Get multiple reminder intervals
 *
 * @param string Mail template ID
 * @return array
 */
function _sumo_pp_get_reminder_intervals( $template_id, $payment ) {
    $intervals                 = array();
    $intervals[ 'no-of-days' ] = '1';

    switch ( $template_id ) {
        case 'payment_plan_invoice':
        case 'deposit_balance_payment_invoice':
            $intervals[ 'schedule-type' ] = 'before-date';
            $intervals[ 'no-of-days' ]    = $payment->get_option( 'notify_invoice_before', '3,2,1' );
            break;
        case 'payment_plan_auto_charge_reminder':
        case 'deposit_balance_payment_auto_charge_reminder':
            $intervals[ 'schedule-type' ] = 'before-date';
            $intervals[ 'no-of-days' ]    = $payment->get_option( 'notify_auto_charge_reminder_before', '3,2,1' );
            break;
        case 'payment_plan_overdue':
        case 'deposit_balance_payment_overdue':
            $intervals[ 'schedule-type' ] = 'after-date';
            $intervals[ 'no-of-days' ]    = $payment->get_option( 'notify_overdue_before', '1' );
            break;
        case 'payment_pending_auth':
            $payment_method               = $payment->get_payment_method();
            $intervals[ 'times-per-day' ] = apply_filters( "sumopaymentplans_{$payment_method}_remind_pending_auth_times_per_day", 2, $payment );
            break;
    }

    if ( isset( $intervals[ 'no-of-days' ] ) ) {
        $intervals[ 'no-of-days' ] = array_map( 'absint', explode( ',', $intervals[ 'no-of-days' ] ) );
    } else {
        $intervals[ 'times-per-day' ] = absint( $intervals[ 'times-per-day' ] );
    }

    return $intervals;
}

function _sumo_pp_get_next_eligible_payment_failed_status( $payment, $next_action_on = '' ) {
    $next_eligible_status = 'cancelled';

    switch ( $payment->get_status() ) {
        case 'pending':
        case 'in_progress':
        case 'pendng_auth':
            if ( absint( $payment->get_option( 'specified_overdue_days', '0' ) ) > 0 ) {
                $next_eligible_status = 'overdue';
            }
            break;
    }

    if ( '' !== $next_action_on && $payment->get_remaining_installments() > 1 ) {
        $next_installment_date = _sumo_pp_get_timestamp( $payment->get_next_payment_date( $payment->get_next_of_next_installment_count() ) );

        if ( _sumo_pp_get_timestamp( $next_action_on ) >= $next_installment_date ) {
            $next_eligible_status = 'cancelled';
        }
    }

    if ( 'cancelled' === $next_eligible_status && ! _sumo_pp_cancel_payment_immediately() ) {
        $next_eligible_status = 'await_cancl';
    }

    return apply_filters( 'sumopaymentplans_get_next_eligible_payment_failed_status', $next_eligible_status, $payment );
}

function _sumo_pp_get_payment_plan_names( $args = array() ) {
    $plan_names    = array();
    $payment_plans = _sumo_pp()->query->get( wp_parse_args( $args, array(
        'type'   => 'sumo_payment_plans',
        'status' => 'publish',
        'return' => 'posts',
            ) ) );

    if ( $payment_plans ) {
        foreach ( $payment_plans as $plan ) {
            $plan_names[ $plan->ID ] = $plan->post_title;
        }
    }

    return $plan_names;
}

function _sumo_pp_get_duration_options() {
    return array(
        'days'   => __( 'Day(s)', 'sumopaymentplans' ),
        'weeks'  => __( 'Week(s)', 'sumopaymentplans' ),
        'months' => __( 'Month(s)', 'sumopaymentplans' ),
        'years'  => __( 'Year(s)', 'sumopaymentplans' ),
    );
}

function _sumo_pp_get_month_options() {
    return array(
        1  => __( 'January', 'sumopaymentplans' ),
        2  => __( 'February', 'sumopaymentplans' ),
        3  => __( 'March', 'sumopaymentplans' ),
        4  => __( 'April', 'sumopaymentplans' ),
        5  => __( 'May', 'sumopaymentplans' ),
        6  => __( 'June', 'sumopaymentplans' ),
        7  => __( 'July', 'sumopaymentplans' ),
        8  => __( 'August', 'sumopaymentplans' ),
        9  => __( 'September', 'sumopaymentplans' ),
        10 => __( 'October', 'sumopaymentplans' ),
        11 => __( 'November', 'sumopaymentplans' ),
        12 => __( 'December', 'sumopaymentplans' ),
    );
}

function _sumo_pp_get_posts( $args = array() ) {
    return _sumo_pp()->query->get( $args );
}

function _sumo_pp_get_active_payment_gateways() {
    $payment_gateways = array();

    if ( is_null( WC()->payment_gateways ) ) {
        return $payment_gateways;
    }

    $available_gateways = WC()->payment_gateways->payment_gateways();
    foreach ( $available_gateways as $key => $gateway ) {
        $payment_gateways[ $key ] = $gateway->title;
    }

    return $payment_gateways;
}

/**
 * Get WP User roles
 *
 * @global object $wp_roles
 * @param bool $include_guest
 * @return array
 */
function _sumo_pp_get_user_roles( $include_guest = false ) {
    global $wp_roles;

    $user_role_key  = array();
    $user_role_name = array();

    foreach ( $wp_roles->roles as $_user_role_key => $user_role ) {
        $user_role_key[]  = $_user_role_key;
        $user_role_name[] = $user_role[ 'name' ];
    }
    $user_roles = array_combine( ( array ) $user_role_key, ( array ) $user_role_name );

    if ( $include_guest ) {
        $user_roles = array_merge( $user_roles, array( 'guest' => 'Guest' ) );
    }

    return $user_roles;
}

function _sumo_pp_get_product_categories() {
    $categories   = array();
    $categoryid   = array();
    $categoryname = array();

    $listcategories = get_terms( 'product_cat' );

    if ( is_array( $listcategories ) ) {
        foreach ( $listcategories as $category ) {
            $categoryname[] = $category->name;
            $categoryid[]   = $category->term_id;
        }
    }

    if ( $categoryid && $categoryname ) {
        $categories = array_combine( ( array ) $categoryid, ( array ) $categoryname );
    }

    return $categories;
}

/**
 * Get payment interval cycle in days.
 *
 * @return int
 */
function _sumo_pp_get_payment_cycle_in_days( $payment_length = null, $payment_period = null, $next_payment_date = null ) {
    if ( ! is_null( $next_payment_date ) ) {
        $current_time      = _sumo_pp_get_timestamp();
        $next_payment_time = _sumo_pp_get_timestamp( $next_payment_date );
        $payment_cycle     = absint( $next_payment_time - $current_time );
    } else {
        $payment_length = absint( $payment_length );

        switch ( $payment_period ) {
            case 'years':
                $payment_cycle = 31556926 * $payment_length;
                break;
            case 'months':
                $payment_cycle = 2629743 * $payment_length;
                break;
            case 'weeks':
                $payment_cycle = 604800 * $payment_length;
                break;
            default:
                $payment_cycle = 86400 * $payment_length;
                break;
        }
    }

    return ceil( $payment_cycle / 86400 );
}

/**
 * Get current admin page URL.
 *
 * Returns an empty string if it cannot generate a URL.
 *
 * @return string
 */
function _sumo_pp_get_current_admin_url() {
    if ( function_exists( 'wc_get_current_admin_url' ) ) {
        return wc_get_current_admin_url();
    }

    $uri = isset( $_SERVER[ 'REQUEST_URI' ] ) ? esc_url_raw( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) ) : '';
    $uri = preg_replace( '|^.*/wp-admin/|i', '', $uri );

    if ( ! $uri ) {
        return '';
    }

    return remove_query_arg( array( '_wpnonce', '_wc_notice_nonce', 'wc_db_update', 'wc_db_update_nonce', 'wc-hide-notice' ), admin_url( $uri ) );
}

/**
 * Get balance payable order from Pay for Order page
 *
 * @global object $wp
 * @return int
 */
function _sumo_pp_get_balance_payable_order_in_pay_for_order_page() {
    global $wp;

    if ( ! isset( $_GET[ 'pay_for_order' ] ) || ! isset( $_GET[ 'key' ] ) ) {
        return 0;
    }
    if ( _sumo_pp_is_balance_payment_order( $wp->query_vars[ 'order-pay' ] ) ) {
        return $wp->query_vars[ 'order-pay' ];
    }

    return 0;
}

function _sumo_pp_get_shortcodes_from_cart_r_checkout( $values ) {
    $shortcodes = array(
        '[sumo_pp_next_payment_date]'          => '',
        '[sumo_pp_next_installment_amount]'    => '',
        '[sumo_pp_current_installment_amount]' => '',
        '[sumo_pp_payment_plan_name]'          => '',
        '[sumo_pp_payment_plan_desc]'          => '',
    );

    $product = ! empty( $values[ 'product_id' ] ) ? wc_get_product( $values[ 'product_id' ] ) : false;

    if ( isset( $values[ 'discount_amount' ] ) && is_numeric( $values[ 'discount_amount' ] ) ) {
        $total_payable_amount = ( $values[ 'total_payable_amount' ] - $values[ 'discount_amount' ] );
    } else {
        $total_payable_amount = $values[ 'total_payable_amount' ];
    }

    $shortcodes[ '[sumo_pp_total_payable]' ]   = wc_price( $product ? wc_get_price_to_display( $product, array(
                'qty'   => 1,
                'price' => $total_payable_amount,
            ) ) : $total_payable_amount );
    $shortcodes[ '[sumo_pp_balance_payable]' ] = wc_price( $product ? wc_get_price_to_display( $product, array(
                'qty'   => 1,
                'price' => $values[ 'remaining_payable_amount' ],
            ) ) : $values[ 'remaining_payable_amount' ] );

    if ( isset( $values[ 'payment_product_props' ][ 'payment_type' ] ) ) {
        $values[ 'payment_type' ] = $values[ 'payment_product_props' ][ 'payment_type' ];
    }

    if ( is_numeric( $values[ 'down_payment' ] ) ) {
        $shortcodes[ '[sumo_pp_current_installment_amount]' ] = wc_price( $product ? wc_get_price_to_display( $product, array(
                    'qty'   => $values[ 'product_qty' ],
                    'price' => $values[ 'down_payment' ],
                ) ) : ( $values[ 'down_payment' ] * $values[ 'product_qty' ] ) );
    }

    if ( 'payment-plans' === $values[ 'payment_type' ] ) {
        if ( ! isset( $values[ 'next_installment_amount' ] ) && isset( $values[ 'payment_product_props' ] ) ) {
            $shortcodes[ '[sumo_pp_next_installment_amount]' ] = wc_price( $product ? wc_get_price_to_display( $product, array(
                        'qty'   => $values[ 'product_qty' ],
                        'price' => _sumo_pp()->plan->get_prop( 'next_installment_amount', array(
                            'props'         => $values[ 'payment_plan_props' ],
                            'product_price' => $values[ 'payment_product_props' ][ 'product_price' ],
                        ) ),
                    ) ) : _sumo_pp()->plan->get_prop( 'next_installment_amount', array(
                        'props'         => $values[ 'payment_plan_props' ],
                        'product_price' => $values[ 'payment_product_props' ][ 'product_price' ],
                        'qty'           => $values[ 'product_qty' ],
                    ) ) );
        } else {
            $shortcodes[ '[sumo_pp_next_installment_amount]' ] = wc_price( $product ? wc_get_price_to_display( $product, array(
                        'qty'   => 1,
                        'price' => $values[ 'next_installment_amount' ],
                    ) ) : $values[ 'next_installment_amount' ] );
        }
    }

    if ( $values[ 'next_payment_date' ] ) {
        $shortcodes[ '[sumo_pp_next_payment_date]' ] = _sumo_pp_get_date_to_display( $values[ 'next_payment_date' ] );
    } else if ( 'after_admin_approval' === $values[ 'activate_payment' ] ) {
        $shortcodes[ '[sumo_pp_next_payment_date]' ] = __( 'After Admin Approval', 'sumopaymentplans' );
    }

    if ( 'payment-plans' === $values[ 'payment_type' ] ) {
        $shortcodes[ '[sumo_pp_payment_plan_name]' ] = get_the_title( $values[ 'payment_plan_props' ][ 'plan_id' ] );
        $shortcodes[ '[sumo_pp_payment_plan_desc]' ] = $values[ 'payment_plan_props' ][ 'plan_description' ];

        if ( ! empty( $values[ 'payment_plan_props' ][ 'plan_description' ] ) ) {
            $shortcodes[ '[sumo_pp_payment_plan_desc]' ] .= '<br>';
        }
    }

    return array(
        'find'    => array_keys( $shortcodes ),
        'replace' => array_values( $shortcodes ),
        'content' => $shortcodes,
    );
}

/**
 * Get the product add to cart url.
 * 
 * @param WC_product $product
 * @return string
 */
function _sumo_pp_product_add_to_cart_url( $product ) {
    remove_filter( 'woocommerce_product_add_to_cart_url', array( _sumo_pp()->product, 'redirect_to_single_product' ), 99, 2 );
    $url = $product->add_to_cart_url();
    add_filter( 'woocommerce_product_add_to_cart_url', array( _sumo_pp()->product, 'redirect_to_single_product' ), 99, 2 );
    return $url;
}

/**
 * Format product price.
 *
 * @param WC_Product $product Product Object.
 * @param string  $price Price.
 * @return string
 */
function _sumo_pp_format_product_price( $product, $price ) {
    if ( ! is_a( $product, 'WC_Product' ) ) {
        return wc_price( $price );
    }

    $price_html = wc_price( wc_get_price_to_display( $product, array( 'price' => $price, 'qty' => 1 ) ) );
    return $price_html . $product->get_price_suffix();
}

/**
 * Get the plan orders creation.
 * 
 * @param SUMO_PP_Payment $payment
 * @return string
 */
function _sumo_pp_get_plan_orders_creation( $payment ) {
    if ( apply_filters( 'sumopaymentplans_use_plan_orders_creation_from_plan', true ) ) {
        return get_post_meta( $payment->get_prop( 'plan_id' ), '_balance_payable_orders_creation', true );
    }

    return $payment->get_prop( 'balance_payable_orders_creation' );
}

/**
 * Display WC search fieldset with respect to products and variations/customer
 * 
 * @param array $args
 * @param bool $echo
 * @return string echo search field
 */
function _sumo_pp_wc_search_field( $args = array(), $echo = true ) {
    $args = wp_parse_args( $args, array(
        'class'       => '',
        'id'          => '',
        'name'        => '',
        'type'        => '',
        'action'      => '',
        'title'       => '',
        'placeholder' => '',
        'css'         => 'width: 50%;',
        'multiple'    => true,
        'allow_clear' => true,
        'selected'    => true,
        'options'     => array(),
            ) );

    ob_start();
    if ( '' !== $args[ 'title' ] ) {
        ?>
        <tr valign="top">
            <th class="titledesc" scope="row">
                <label for="<?php echo esc_attr( $args[ 'id' ] ); ?>"><?php echo esc_attr( $args[ 'title' ] ); ?></label>
            </th>
            <td class="forminp forminp-select">
                <?php
            }
            ?>
            <select 
            <?php echo $args[ 'multiple' ] ? 'multiple="multiple"' : ''; ?> 
                name="<?php echo esc_attr( '' !== $args[ 'name' ] ? $args[ 'name' ] : $args[ 'id' ]  ); ?>[]" 
                id="<?php echo esc_attr( $args[ 'id' ] ); ?>" 
                class="<?php echo esc_attr( $args[ 'class' ] ); ?>" 
                data-action="<?php echo esc_attr( $args[ 'action' ] ); ?>" 
                data-placeholder="<?php echo esc_attr( $args[ 'placeholder' ] ); ?>" 
                <?php echo $args[ 'allow_clear' ] ? 'data-allow_clear="true"' : ''; ?> 
                style="<?php echo esc_attr( $args[ 'css' ] ); ?>">
                    <?php
                    if ( is_array( $args[ 'options' ] ) ) {
                        foreach ( $args[ 'options' ] as $id ) {
                            $option_value = '';

                            switch ( $args[ 'type' ] ) {
                                case 'product':
                                    $product = wc_get_product( $id );
                                    if ( $product ) {
                                        $option_value = $product->get_formatted_name();
                                    }
                                    break;
                                case 'customer':
                                    $user = get_user_by( 'id', $id );
                                    if ( $user ) {
                                        $option_value = esc_html( $user->display_name ) . '(#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')';
                                    }
                                    break;
                                default:
                                    $post = get_post( $id );
                                    if ( $post ) {
                                        /* translators: 1: post ID 2: post title */
                                        $option_value = sprintf( '(#%s) %s', $post->ID, wp_kses_post( $post->post_title ) );
                                    }
                                    break;
                            }

                            if ( $option_value ) {
                                ?>
                            <option value="<?php echo esc_attr( $id ); ?>" <?php echo $args[ 'selected' ] ? 'selected="selected"' : ''; ?>><?php echo wp_kses_post( $option_value ); ?></option>
                            <?php
                        }
                    }
                }
                ?>
            </select>
            <?php
            if ( '' !== $args[ 'title' ] ) {
                ?>
            </td>
        </tr>
        <?php
    }

    if ( $echo ) {
        ob_end_flush();
    } else {
        return ob_get_clean();
    }
}

/**
 * Get Payments by User
 *
 * @param int $user_id
 * @param string $status
 * @param int $limit
 * @return array
 */
function _sumo_pp_get_payments_by_user( $user_id, $status = '', $limit = -1 ) {
    $user_id = absint( $user_id );
    if ( ! $user_id ) {
        return array();
    }

    if ( empty( $status ) ) {
        $status = array_keys( _sumo_pp_get_payment_statuses() );
    } else {
        $status = is_array( $status ) ? $status : ( array ) $status;
    }

    $payment_ids = get_posts( array(
        'post_type'      => 'sumo_pp_payments',
        'posts_per_page' => $limit,
        'fields'         => 'ids',
        'post_status'    => $status,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_customer_id',
                'value'   => $user_id,
                'type'    => 'numeric',
                'compare' => '=',
            ),
        ),
            ) );

    return $payment_ids;
}
