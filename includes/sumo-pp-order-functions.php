<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Get the Order instance.
 *
 * @param mixed $order
 * @return mixed
 */
function _sumo_pp_maybe_get_order_instance( $order ) {
    if ( ! $order ) {
        return false;
    }

    if ( is_a( $order, 'WC_Order' ) ) {
        return $order;
    }

    if ( is_a( $order, 'SUMO_PP_Deprecated_Order' ) && $order->order ) {
        return $order->order;
    }

    return wc_get_order( $order );
}

/**
 * Get parent Order ID.
 *
 * @param mixed $order
 * @return int
 */
function _sumo_pp_get_parent_order_id( $order ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );
    if ( ! $order ) {
        return 0;
    }

    return _sumo_pp_is_child_order( $order ) ? $order->get_parent_id() : $order->get_id();
}

/**
 * Get the payment data from the order.
 * 
 * @param mixed $order
 * @return array
 */
function _sumo_pp_get_payment_data_from_order( $order ) {
    $payment_data = array();
    $order        = _sumo_pp_maybe_get_order_instance( $order );

    if ( _sumo_pp_is_orderpp_created_by_multiple( $order ) ) {
        return $order->get_meta( '_sumo_pp_payment_data', true );
    }

    if ( $order ) {
        foreach ( $order->get_items() as $item_id => $item ) {
            if ( ! empty( $item[ '_sumo_pp_payment_data' ] ) ) {
                $payment_data[ $item_id ] = $item[ '_sumo_pp_payment_data' ];
            }
        }
    }

    return $payment_data;
}

/**
 * Get the payment data from the order by the item type.
 * 
 * @param mixed $order
 * @param string $item_type
 * @return array
 */
function _sumo_pp_get_payment_data_by_item_type( $order, $item_type ) {
    $order = _sumo_pp_maybe_get_order_instance( $order );

    if ( 'order' === $item_type && _sumo_pp_is_orderpp_created_by_multiple( $order ) ) {
        return $order->get_meta( '_sumo_pp_payment_data', true );
    }

    if ( $order ) {
        foreach ( $order->get_items() as $item ) {
            if ( ! empty( $item[ '_sumo_pp_payment_data' ][ 'product_type' ] ) && $item_type === $item[ '_sumo_pp_payment_data' ][ 'product_type' ] ) {
                return $item[ '_sumo_pp_payment_data' ];
            }
        }
    }

    return false;
}

/**
 * Get the payment ID from the order.
 * 
 * @param mixed $order
 * @param int $product_id
 * @return int
 */
function _sumo_pp_get_payment_id_from_order( $order, $product_id = null ) {
    $order      = _sumo_pp_maybe_get_order_instance( $order );
    $payment_id = 0;

    if ( _sumo_pp_is_deposit_order( $order ) ) {
        if ( is_numeric( $product_id ) ) {
            $meta_query_vars = array(
                'key'     => '_product_id',
                'value'   => $product_id,
                'compare' => '=',
            );
        } else {
            $meta_query_vars = array();
        }

        $result = _sumo_pp()->query->get( array(
            'type'       => 'sumo_pp_payments',
            'status'     => array_keys( _sumo_pp_get_payment_statuses() ),
            'limit'      => 1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'     => '_initial_payment_order_id',
                    'value'   => $order->get_id(),
                    'compare' => '=',
                ),
                $meta_query_vars,
            ),
                ) );

        $payment_id = ! empty( $result[ 0 ] ) ? $result[ 0 ] : 0;
    } else if ( _sumo_pp_is_balance_payment_order( $order ) ) {
        $payment_id = absint( $order->get_meta( '_payment_id', true ) );
    }

    return $payment_id;
}

/**
 * Maybe get an array of shipping amount for the order.
 * 
 * @param mixed $order
 * @return array
 */
function _sumo_pp_maybe_get_shipping_amount_for_order( $order, $payment = false ) {
    $for_total_payable   = 0;
    $for_balance_payable = 0;

    if ( ! $payment ) {
        $payment = _sumo_pp_get_payment( _sumo_pp_get_payment_id_from_order( $order ) );
    }

    if ( $payment ) {
        if ( _sumo_pp_is_parent_order( $order ) ) {
            $for_total_payable = $order->get_shipping_total() + $order->get_shipping_tax();

            if ( 'final-payment' === $payment->charge_shipping_during() ) {
                $for_balance_payable = $for_total_payable;
            }
        } else {
            $initial_payment_order = _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() );

            if ( $initial_payment_order ) {
                $for_total_payable = $initial_payment_order->get_shipping_total() + $initial_payment_order->get_shipping_tax();
            }

            if ( 'final-payment' === $payment->charge_shipping_during() && empty( $order->get_shipping_methods() ) ) {
                $for_balance_payable = $for_total_payable;
            }
        }
    }

    return array(
        'for_total_payable'   => $for_total_payable,
        'for_balance_payable' => $for_balance_payable,
    );
}

/**
 * Get the payment IDs from the order.
 * 
 * @since 11.0.0
 * @param object $order
 * @param int $product_id
 * @return array
 */
function _sumo_pp_get_payment_ids_from_order( $order, $product_id = null ) {    
    $order       = _sumo_pp_maybe_get_order_instance( $order );    
    $payment_ids = array();
    
    if ( _sumo_pp_is_deposit_order( $order ) ) {
        if ( is_numeric( $product_id ) ) {
            $meta_query_vars = array(
                'key'     => '_product_id',
                'value'   => $product_id,
                'compare' => '=',
            );
        } else {
            $meta_query_vars = array();
        }

        $result = _sumo_pp()->query->get( array(
            'type'       => 'sumo_pp_payments',
            'status'     => array_keys( _sumo_pp_get_payment_statuses() ),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'     => '_initial_payment_order_id',
                    'value'   => $order->get_id(),
                    'compare' => '=',
                ),
                $meta_query_vars,
            ),
                ) );

        $payment_ids = ! empty( $result ) ? $result : array();
    } else if ( _sumo_pp_is_balance_payment_order( $order ) ) {
        $payment_id = absint( $order->get_meta( '_payment_id', true ) );
        $payment    = _sumo_pp_get_payment( $payment_id );
        
        if ( $payment && in_array( $payment->get_status(), _sumo_pp_get_payment_statuses() ) ) {
            $payment_ids[] = $payment_id;
        }
    }

    return $payment_ids;
}
