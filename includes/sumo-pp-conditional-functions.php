<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Check the currently installed WC version.
 *
 * @param string $comparison_opr The possible operators are: <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne respectively.
  This parameter is case-sensitive, values should be lowercase
 * @param string $version
 * @return bool
 */
function _sumo_pp_is_wc_version( $comparison_opr, $version ) {
    return defined( 'WC_VERSION' ) && version_compare( WC_VERSION, $version, $comparison_opr );
}

/**
 * Returns true when viewing the my payments page.
 *
 * @return bool
 */
function _sumo_pp_is_my_payments_page() {
    return wc_post_content_has_shortcode( 'sumo_pp_my_payments' );
}

/**
 * Check whether the cart contains payment item?
 * 
 * @return bool
 */
function _sumo_pp_cart_contains_payment() {
    return _sumo_pp()->cart->cart_contains_payment();
}

/**
 * Check whether the order is payment order?
 * 
 * @return bool
 */
function _sumo_pp_is_payment_order( $order ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );
    return $order && 'yes' === $order->get_meta( 'is_sumo_pp_order', true );
}

/**
 * Check whether the order contains payment data?
 * 
 * @param mixed $order
 * @return bool
 */
function _sumo_pp_order_has_payment_data( $order ) {
    $payment_data = _sumo_pp_get_payment_data_from_order( $order );
    return ! empty( $payment_data );
}

/**
 * Check whether the order is parent order?
 * 
 * @param mixed $order
 * @return bool
 */
function _sumo_pp_is_parent_order( $order ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );
    return $order && 0 === $order->get_parent_id();
}

/**
 * Check whether the order is child order?
 * 
 * @param mixed $order
 * @return bool
 */
function _sumo_pp_is_child_order( $order ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );
    return $order && $order->get_parent_id() > 0;
}

/**
 * Check whether the order is deposit order?
 * 
 * @param mixed $order
 * @return bool
 */
function _sumo_pp_is_deposit_order( $order ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );
    return _sumo_pp_is_payment_order( $order ) && _sumo_pp_is_parent_order( $order );
}

/**
 * Check whether the order is balance order?
 * 
 * @param mixed $order
 * @return bool
 */
function _sumo_pp_is_balance_payment_order( $order ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );
    return _sumo_pp_is_payment_order( $order ) && _sumo_pp_is_child_order( $order );
}

/**
 * Check whether the order paymentplan is created with multiple line item?
 * 
 * @param mixed $order
 * @return bool
 */
function _sumo_pp_is_orderpp_created_by_multiple( $order ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );
    return $order && 'yes' === $order->get_meta( 'is_sumo_pp_orderpp', true );
}

/**
 * Check whether the order is final order?
 * 
 * @param mixed $order
 * @return bool
 */
function _sumo_pp_is_final_payment_order( $order ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );

    if ( ! _sumo_pp_is_balance_payment_order( $order ) ) {
        return false;
    }

    $payment = _sumo_pp_get_payment( $order->get_meta( '_payment_id', true ) );
    if ( $payment ) {
        if ( $payment->has_status( 'completed' ) ) {
            return true;
        }

        return 1 === $payment->get_remaining_installments();
    }

    return false;
}

/**
 * Check whether the order has fully paid?
 * 
 * @param mixed $order
 * @return bool
 */
function _sumo_pp_is_order_paid( $order ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );
    if ( ! $order ) {
        return false;
    }

    return 'yes' === $order->get_meta( '_sumo_pp_order_paid', true ) || $order->has_status( array( 'completed', 'processing' ) );
}

/**
 * Check whether the order is valid to pay?
 * 
 * @param mixed $order
 * @return bool
 */
function _sumo_pp_is_valid_order_to_pay( $order ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );

    if ( ! $order || _sumo_pp_is_order_paid( $order ) || $order->has_status( 'refunded' ) ) {
        return false;
    }

    if ( _sumo_pp_is_child_order( $order ) ) {
        $parent_order = _sumo_pp_maybe_get_order_instance( $order->get_parent_id() );

        if ( $parent_order && $parent_order->has_status( 'refunded' ) ) {
            return false;
        }
    }

    return true;
}

/**
 * Check whether the payment should be cancelled immediately upon payment failure.
 * 
 * @return bool
 */
function _sumo_pp_cancel_payment_immediately() {
    return 'auto' === get_option( SUMO_PP_PLUGIN_PREFIX . 'cancel_payments_after_balance_payment_due_date', 'after_admin_approval' ) ? true : false;
}

function _sumo_pp_user_can_purchase_payment( $user = null, $args = array() ) {
    include_once ABSPATH . 'wp-includes/pluggable.php';

    $args = wp_parse_args( $args, array(
        'limit_by'            => get_option( SUMO_PP_PLUGIN_PREFIX . 'show_deposit_r_payment_plans_for', 'all_users' ),
        'filtered_users'      => ( array ) get_option( SUMO_PP_PLUGIN_PREFIX . 'get_limited_users_of_payment_product' ),
        'filtered_user_roles' => ( array ) get_option( SUMO_PP_PLUGIN_PREFIX . 'get_limited_userroles_of_payment_product' ),
            ) );

    if ( is_numeric( $user ) && $user ) {
        $user_id = $user;
    } elseif ( isset( $user->ID ) ) {
        $user_id = $user->ID;
    } else {
        $user_id = get_current_user_id();
    }
    $user = get_user_by( 'id', $user_id );

    switch ( $args[ 'limit_by' ] ) {
        case 'all_users':
            return true;
        case 'include_users':
            if ( ! $user ) {
                return false;
            }

            $filtered_user_mails = array();
            foreach ( $args[ 'filtered_users' ] as $_id ) {
                $_user = get_user_by( 'id', $_id );

                if ( ! $_user ) {
                    continue;
                }

                $filtered_user_mails[] = $_user->data->user_email;
            }

            if ( in_array( $user->data->user_email, $filtered_user_mails ) ) {
                return true;
            }
            break;
        case 'exclude_users':
            if ( ! $user ) {
                return false;
            }

            $filtered_user_mails = array();
            foreach ( $args[ 'filtered_users' ] as $_id ) {
                $_user = get_user_by( 'id', $_id );

                if ( ! $_user ) {
                    continue;
                }

                $filtered_user_mails[] = $_user->data->user_email;
            }

            if ( ! in_array( $user->data->user_email, $filtered_user_mails ) ) {
                return true;
            }
            break;
        case 'include_user_role':
            if ( $user ) {
                if ( isset( $user->roles[ 0 ] ) && in_array( $user->roles[ 0 ], $args[ 'filtered_user_roles' ] ) ) {
                    return true;
                }
            } elseif ( in_array( 'guest', $args[ 'filtered_user_roles' ] ) ) {
                return true;
            }
            break;
        case 'exclude_user_role':
            if ( $user ) {
                if ( isset( $user->roles[ 0 ] ) && ! in_array( $user->roles[ 0 ], $args[ 'filtered_user_roles' ] ) ) {
                    return true;
                }
            } elseif ( ! in_array( 'guest', $args[ 'filtered_user_roles' ] ) ) {
                return true;
            }
            break;
    }

    return false;
}

/**
 * Determines if the request is a non-legacy REST API request.
 *
 * This function is a compatibility wrapper for WC()->is_rest_api_request() which was introduced in WC 3.6.
 *
 * @return bool True if it's a REST API request, false otherwise.
 */
function _sumo_pp_is_rest_api_request() {
    if ( function_exists( 'WC' ) && is_callable( array( WC(), 'is_rest_api_request' ) ) ) {
        return WC()->is_rest_api_request();
    }

    if ( empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
        return false;
    }

    $rest_prefix         = trailingslashit( rest_get_url_prefix() );
    $is_rest_api_request = ( false !== strpos( $_SERVER[ 'REQUEST_URI' ], $rest_prefix ) );

    return apply_filters( 'woocommerce_is_rest_api_request', $is_rest_api_request );
}

/**
 * Determines if the current request is to any or a specific Checkout blocks REST API endpoint.
 *
 * @see Automattic\WooCommerce\Blocks\StoreApi\RoutesController::initialize() for a list of routes.
 *
 * @param string $endpoint The checkout/checkout blocks endpoint. Optional. Can be empty (any checkout blocks API) or a specific endpoint ('checkout', 'cart', 'products' etc)
 * @return bool Whether the current request is for a cart/checkout blocks REST API endpoint.
 */
function _sumo_pp_is_checkout_blocks_api_request( $endpoint = '' ) {
    if ( ! _sumo_pp_is_rest_api_request() || empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
        return false;
    }

    $endpoint    = empty( $endpoint ) ? '' : '/' . $endpoint;
    $rest_prefix = trailingslashit( rest_get_url_prefix() );
    $request_uri = esc_url_raw( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) );

    return false !== strpos( $request_uri, $rest_prefix . 'wc/store' . $endpoint );
}
