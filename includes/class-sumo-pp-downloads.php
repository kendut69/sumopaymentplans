<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Downloads handler
 * 
 * Handle digital payments downloads.
 * 
 * @class SUMO_PP_Downloads
 * @package Class
 */
class SUMO_PP_Downloads {

    /**
     * Init SUMO_PP_Downloads.
     */
    public static function init() {
        add_action( 'woocommerce_store_api_checkout_update_order_meta', __CLASS__ . '::maybe_set_download_permission', 20, 1 );
        add_action( 'woocommerce_checkout_update_order_meta', __CLASS__ . '::maybe_set_download_permission', 20 );
        add_filter( 'woocommerce_downloadable_file_permission', __CLASS__ . '::maybe_grant_downloadable_file_permission', 999, 3 );
        add_filter( 'woocommerce_order_is_download_permitted', __CLASS__ . '::is_download_permitted', 999, 2 );
    }

    /**
     * Maybe set the download permission in order which will be used later.
     * 
     * @param int $order_id
     */
    public static function maybe_set_download_permission( $order_id ) {
        $order = _sumo_pp_maybe_get_order_instance( $order_id );

        if ( ! _sumo_pp_order_has_payment_data( $order ) ) {
            return;
        }

        $value = 'initial-payment';
        foreach ( $order->get_items() as $item ) {
            $product = wc_get_product( $item [ 'product_id' ] );

            if ( $product && $product->is_downloadable() ) {
                $value = get_option( SUMO_PP_PLUGIN_PREFIX . 'grant_permission_to_download_after', 'initial-payment' );
                break;
            }
        }

        $order->update_meta_data( '_sumo_pp_grant_permission_to_download_after', $value );
        $order->save();
    }

    /**
     * Grant downloadable product access to the file either in the initial order or the final order.
     * 
     * @param WC_Customer_Download $download
     * @param WC_Product $product
     * @param WC_Order $order
     * @return WC_Customer_Download
     */
    public static function maybe_grant_downloadable_file_permission( $download, $product, $order ) {
        $order = _sumo_pp_maybe_get_order_instance( $order );

        if ( _sumo_pp_is_parent_order( $order ) ) {
            if ( ! _sumo_pp_order_has_payment_data( $order ) ) {
                return $download;
            }

            if ( 'final-payment' !== self::grant_permission_after( $order ) ) {
                return $download;
            }

            foreach ( $order->get_items() as $item ) {
                $product_id = absint( $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] );

                if ( $product_id === $product->get_id() && ! empty( $item[ '_sumo_pp_payment_data' ] ) ) {
                    $download = new WC_Customer_Download();
                    break;
                }
            }
        } elseif ( _sumo_pp_is_payment_order( $order ) ) {
            if ( 'final-payment' !== self::grant_permission_after( $order ) || 0 !== absint( $order->get_meta( '_sumo_pp_remaining_installments', true ) ) ) {
                $download = new WC_Customer_Download();
            }
        }

        return $download;
    }

    /**
     * Check if permission granted after initial payment or final payment?
     * 
     * @param mixed $order
     * @return string
     */
    public static function grant_permission_after( $order ) {
        $parent_order_id = _sumo_pp_get_parent_order_id( $order );
        $parent_order    = _sumo_pp_maybe_get_order_instance( $parent_order_id );
        return $parent_order ? $parent_order->get_meta( '_sumo_pp_grant_permission_to_download_after', true ) : '';
    }

    /**
     * Checks if product download is permitted.
     *
     * @return bool
     */
    public static function is_download_permitted( $bool, $order ) {
        if ( ! $bool ) {
            return $bool;
        }

        $order = _sumo_pp_maybe_get_order_instance( $order );

        if ( _sumo_pp_is_parent_order( $order ) ) {
            if ( _sumo_pp_order_has_payment_data( $order ) && 'final-payment' === self::grant_permission_after( $order ) ) {
                $bool = false;
            }
        } elseif ( _sumo_pp_is_payment_order( $order ) ) {
            if ( 'final-payment' !== self::grant_permission_after( $order ) || 0 !== absint( $order->get_meta( '_sumo_pp_remaining_installments', true ) ) ) {
                $bool = false;
            }
        }

        return $bool;
    }
}

SUMO_PP_Downloads::init();
