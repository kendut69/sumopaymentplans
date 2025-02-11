<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle WC Smart coupons 
 * 
 * @class SUMO_PP_WC_Smart_Coupons
 * Plugin Name - WooCommerce Smart Coupons
 * Plugin version - 8.23.0
 * Plugin Author - StoreApps
 * @package Class
 */
class SUMO_PP_WC_Smart_Coupons {

    /**
     * Init SUMO_PP_WC_Smart_Coupons.
     */
    public static function init() {
        // Get order pp from cart session
        add_filter( 'sumopaymentplans_get_order_pp_from_cart_session', __CLASS__ . '::get_discount_amount_for_order_pp', 10, 1 );
    }

    /**
     * Set order pp payment form cart session
     * 
     * @since 10.8.8
     * @param array $props Cart Session Props
     * @return array
     */
    public static function get_discount_amount_for_order_pp( $props ) {
        $coupons = is_object( WC()->cart ) ? WC()->cart->get_coupons() : array();
        if ( ! is_array( $coupons ) ) {
            return $props;
        }

        foreach ( $coupons as $coupon_code => $coupon ) {
            $discount_type = is_object( $coupon ) ? $coupon->get_discount_type() : '';
            if ( 'smart_coupon' === $discount_type ) {
                $props[ 'order_total' ] -= $coupon->get_amount();
            }
        }

        return $props;
    }
}

SUMO_PP_WC_Smart_Coupons::init();
