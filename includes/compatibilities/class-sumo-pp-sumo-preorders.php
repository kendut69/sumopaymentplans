<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle SUMO Pre-Orders Compatibility.
 * 
 * @class SUMO_PP_SUMOPreOrders
 * @package Class
 */
class SUMO_PP_SUMOPreOrders {

    /**
     * Init SUMO_PP_SUMOPreOrders.
     */
    public static function init() {
        add_filter( 'sumopaymentplans_get_product_props', __CLASS__ . '::alter_product_props', 999 );
        add_action( 'woocommerce_before_calculate_totals', __CLASS__ . '::refresh_cart', 1000 );
        add_action( 'sumopaymentplans_payment_is_cancelled', __CLASS__ . '::payment_is_cancelled', 999 );
        add_filter( 'sumopaymentplans_get_next_eligible_payment_failed_status', __CLASS__ . '::schedule_as_cancelled', 10, 2 );
    }

    public static function alter_product_props( $product_props ) {
        if ( is_null( $product_props[ 'product_id' ] ) ) {
            return $product_props;
        }

        $preorder_product_props = _sumo_wcpo()->product->get_props( $product_props[ 'product_id' ] );

        if ( ! is_null( $preorder_product_props[ 'product_available_on' ] ) ) {
            $product_props[ 'sumopreorder_product' ] = 'yes';
            $product_props[ 'pay_balance_type' ]     = 'before';
            $product_props[ 'pay_balance_before' ]   = $preorder_product_props[ 'product_available_on' ];
        }

        return $product_props;
    }

    public static function refresh_cart() {
        foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
            if ( ! empty( $cart_item[ 'sumopreorders' ] ) && ! empty( $cart_item[ 'sumopaymentplans' ] ) ) {
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumopreorders' ][ 'initial_preorder_price' ] = $cart_item[ 'sumopaymentplans' ][ 'down_payment' ];
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumopaymentplans' ][ 'base_price' ]          = $cart_item[ 'sumopaymentplans' ][ 'payment_product_props' ][ 'product_price' ];
            }
        }
    }

    public static function payment_is_cancelled( $payment_id ) {
        $payment = _sumo_pp_get_payment( $payment_id );

        if (
                'pay-in-deposit' === $payment->get_payment_type() &&
                'before' === $payment->get_pay_balance_type() &&
                'yes' === $payment->get_prop( 'sumopreorder_product' ) &&
                function_exists( '_sumo_wcpo_get_preorder' )
        ) {
            $preordered_order = _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() );
            $preorders        = _sumo_wcpo_get_posts( array(
                'post_type'   => 'sumo_wcpo_preorders',
                'post_status' => array( '_sumo_wcpo_pending', '_sumo_wcpo_progress' ),
                'meta_key'    => '_preordered_order_id',
                'meta_value'  => $payment->get_initial_payment_order_id(),
                    ) );

            if ( $preordered_order ) {
                $preorder = _sumo_wcpo_get_preorder( isset( $preorders[ 0 ] ) ? $preorders[ 0 ] : 0 );

                if ( $preorder ) {
                    $preordered_order->update_status( 'cancelled' );
                }
            }
        }
    }

    public static function schedule_as_cancelled( $next_eligible_status, $payment ) {
        if ( 'before' === $payment->get_pay_balance_type() ) {
            if ( 'yes' === $payment->get_prop( 'sumopreorder_product' ) ) {
                //Product Release Date = Next Payment date
                $next_eligible_status = 'cancelled';
            }
        }

        return $next_eligible_status;
    }
}

SUMO_PP_SUMOPreOrders::init();
