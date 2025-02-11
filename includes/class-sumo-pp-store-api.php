<?php

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

/**
 * Store API integration class.
 *
 * @class SUMO_PP_Store_API
 * @package Class
 */
class SUMO_PP_Store_API {

    /**
     * Plugin identifier, unique to each plugin.
     *
     * @var string
     */
    const IDENTIFIER = 'sumopaymentplans';

    /**
     * Bootstraps the class and hooks required data.
     */
    public static function init() {
        self::extend_store();
    }

    /**
     * Register cart data handler.
     */
    public static function extend_store() {
        if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
            return;
        }

        woocommerce_store_api_register_endpoint_data(
                array(
                    'endpoint'        => CartItemSchema::IDENTIFIER,
                    'namespace'       => self::IDENTIFIER,
                    'data_callback'   => array( __CLASS__, 'extend_cart_item_data' ),
                    'schema_callback' => array( __CLASS__, 'extend_cart_item_schema' ),
                    'schema_type'     => ARRAY_A,
                )
        );

        woocommerce_store_api_register_endpoint_data(
                array(
                    'endpoint'        => CartSchema::IDENTIFIER,
                    'namespace'       => self::IDENTIFIER,
                    'data_callback'   => array( __CLASS__, 'extend_cart_data' ),
                    'schema_callback' => array( __CLASS__, 'extend_cart_schema' ),
                    'schema_type'     => ARRAY_A,
                )
        );

        woocommerce_store_api_register_update_callback(
                array(
                    'namespace' => self::IDENTIFIER,
                    'callback'  => array( __CLASS__, 'handle_update_endpoint' ),
                )
        );
    }

    /**
     * Register payment plan product data into cart/items endpoint.
     *
     * @param  array $cart_item Current cart item data.
     * @return array $item_data Registered deposits product data.
     */
    public static function extend_cart_item_data( $cart_item ) {
        return self::extend_cart_data( $cart_item );
    }

    /**
     * Adds extension data to cart route responses.
     *
     * @return array
     */
    public static function extend_cart_data( $cart_item = null ) {
        $cart_data = array(
            'is_sumopp'                                => false,
            'order_paymentplan_enabled'                => false,
            'order_paymentplan_form_html'              => null,
            'order_paymentplan_future_payments_info'   => '',
            'order_paymentplan_totals'                 => array(),
            'product_paymentplan_future_payments_info' => '',
			'product_paymentplan_enabled'              => false,
        );

        $money_formatter    = woocommerce_store_api_get_formatter( 'money' );
        $currency_formatter = woocommerce_store_api_get_formatter( 'currency' );

        if ( _sumo_pp()->orderpp->can_user_deposit_payment() ) {
            $cart_data[ 'order_paymentplan_enabled' ]   = true;
            $cart_data[ 'is_sumopp' ]                   = _sumo_pp()->orderpp->is_enabled();
            $cart_data[ 'order_paymentplan_form_html' ] = _sumo_pp()->orderpp->get_plan_selector_form();

            if ( $cart_data[ 'is_sumopp' ] ) {
                $cart_data[ 'order_paymentplan_future_payments_info' ] = _sumo_pp()->orderpp->get_review_payment_info( false, true );
                $cart_data[ 'order_paymentplan_totals' ]               = $currency_formatter->format(
                        array(
                            'down_payment'         => $money_formatter->format( WC()->cart->sumopaymentplans[ 'order' ][ 'down_payment' ] ),
                            'total_payable_amount' => $money_formatter->format( WC()->cart->sumopaymentplans[ 'order' ][ 'total_payable_amount' ] ),
                        )
                );
            }
        } elseif( _sumo_pp()->cart->cart_contains_payment() ) {
			$cart_data['product_paymentplan_enabled']              = true ;
			$cart_data['product_paymentplan_future_payments_info'] = _sumo_pp()->cart->get_cart_balance_payable( false );
		}

        return $cart_data;
    }

    /**
     * Handle our actions through StoreAPI.
     *
     * @param array $args
     */
    public static function handle_update_endpoint( $args ) {
        
    }

    /**
     * Register payment plan product schema into cart/items endpoint.
     *
     * @return array Registered schema.
     */
    public static function extend_cart_item_schema() {
        return array();
    }

    /**
     * Register schema into cart endpoint.
     *
     * @return  array  Registered schema.
     */
    public static function extend_cart_schema() {
        return array();
    }
}
