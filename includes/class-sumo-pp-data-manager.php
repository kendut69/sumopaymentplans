<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * SUMO Payment Plans data manager.
 * 
 * @class SUMO_PP_Data_Manager
 * @package Class
 */
class SUMO_PP_Data_Manager {

    protected static $payment_data_props = array(
        'product_id'               => null,
        'product_qty'              => null,
        'activate_payment'         => null,
        'charge_shipping_during'   => null,
        'down_payment'             => null,
        'base_price'               => null,
        'next_payment_date'        => null,
        'next_installment_amount'  => null,
        'total_payable_amount'     => null,
        'remaining_payable_amount' => null,
        'payment_product_props'    => null,
        'payment_plan_props'       => null,
        'item_meta'                => null,
        'discount_amount'          => null,
        'discount_tax'             => null,
    );

    public static function get_default_props() {
        return array_map( '__return_null', self::$payment_data_props );
    }

    public static function get_payment_data( $args ) {
        $args = wp_parse_args( $args, array(
            'product_props'          => null,
            'plan_props'             => null,
            'deposited_amount'       => null,
            'base_price'             => null,
            'calc_deposit'           => false,
            'qty'                    => 1,
            'activate_payment'       => 'auto',
            'charge_shipping_during' => 'initial-payment',
            'item_meta'              => null,
            'discount_amount'        => null,
            'discount_tax'           => null,
                ) );

        self::$payment_data_props[ 'payment_product_props' ] = is_array( $args[ 'product_props' ] ) ? $args[ 'product_props' ] : _sumo_pp()->product->get_props( $args[ 'product_props' ] );

        if (
                empty( self::$payment_data_props[ 'payment_product_props' ] ) ||
                ! _sumo_pp()->product->is_payment_product( self::$payment_data_props[ 'payment_product_props' ] )
        ) {
            return self::get_default_props();
        }

        self::$payment_data_props[ 'payment_plan_props' ] = is_array( $args[ 'plan_props' ] ) ? $args[ 'plan_props' ] : _sumo_pp()->plan->get_props( $args[ 'plan_props' ] );

        if (
                'payment-plans' === self::$payment_data_props[ 'payment_product_props' ][ 'payment_type' ] &&
                empty( self::$payment_data_props[ 'payment_plan_props' ][ 'plan_id' ] )
        ) {
            return self::get_default_props();
        }

        if ( is_numeric( $args[ 'base_price' ] ) ) {
            self::$payment_data_props[ 'base_price' ]                               = floatval( $args[ 'base_price' ] );
            self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ] = floatval( $args[ 'base_price' ] );
        }

        self::$payment_data_props[ 'product_id' ]             = self::$payment_data_props[ 'payment_product_props' ][ 'product_id' ];
        self::$payment_data_props[ 'product_qty' ]            = absint( $args[ 'qty' ] ? $args[ 'qty' ] : 1 );
        self::$payment_data_props[ 'item_meta' ]              = $args[ 'item_meta' ];
        self::$payment_data_props[ 'activate_payment' ]       = $args[ 'activate_payment' ];
        self::$payment_data_props[ 'charge_shipping_during' ] = $args[ 'charge_shipping_during' ];
        self::$payment_data_props[ 'discount_amount' ]        = $args[ 'discount_amount' ];
        self::$payment_data_props[ 'discount_tax' ]           = $args[ 'discount_tax' ];

        unset( self::$payment_data_props[ 'item_meta' ][ 'sumopaymentplans' ] );

        switch ( self::$payment_data_props[ 'payment_product_props' ][ 'payment_type' ] ) {
            case 'payment-plans':
                self::$payment_data_props[ 'down_payment' ]             = _sumo_pp()->plan->get_prop( 'down_payment', array(
                    'props'         => self::$payment_data_props[ 'payment_plan_props' ],
                    'product_price' => self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ],
                    'qty'           => self::$payment_data_props[ 'product_qty' ],
                        ) );
                self::$payment_data_props[ 'next_payment_date' ]        = _sumo_pp()->plan->get_prop( 'next_payment_on', array(
                    'props'         => self::$payment_data_props[ 'payment_plan_props' ],
                    'product_price' => self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ],
                    'qty'           => self::$payment_data_props[ 'product_qty' ],
                        ) );
                self::$payment_data_props[ 'next_installment_amount' ]  = _sumo_pp()->plan->get_prop( 'next_installment_amount', array(
                    'props'         => self::$payment_data_props[ 'payment_plan_props' ],
                    'product_price' => self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ],
                    'qty'           => self::$payment_data_props[ 'product_qty' ],
                        ) );
                self::$payment_data_props[ 'total_payable_amount' ]     = _sumo_pp()->plan->get_prop( 'total_payable', array(
                    'props'         => self::$payment_data_props[ 'payment_plan_props' ],
                    'product_price' => self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ],
                    'qty'           => self::$payment_data_props[ 'product_qty' ],
                        ) );
                self::$payment_data_props[ 'remaining_payable_amount' ] = _sumo_pp()->plan->get_prop( 'balance_payable', array(
                    'props'         => self::$payment_data_props[ 'payment_plan_props' ],
                    'product_price' => self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ],
                    'qty'           => self::$payment_data_props[ 'product_qty' ],
                        ) );
                break;
            case 'pay-in-deposit':
                if ( $args[ 'calc_deposit' ] ) {
                    if ( 'pre-defined' === _sumo_pp()->product->get_prop( 'deposit_type', array(
                                'props' => self::$payment_data_props[ 'payment_product_props' ],
                            ) )
                    ) {
                        $args[ 'deposited_amount' ] = floatval( _sumo_pp()->product->get_fixed_deposit_amount( self::$payment_data_props[ 'payment_product_props' ] ) );
                        $args[ 'deposited_amount' ] *= self::$payment_data_props[ 'product_qty' ];
                    }
                }

                self::$payment_data_props[ 'down_payment' ]             = floatval( $args[ 'deposited_amount' ] );
                self::$payment_data_props[ 'next_payment_date' ]        = _sumo_pp()->product->get_prop( 'next_payment_on', array(
                    'props' => self::$payment_data_props[ 'payment_product_props' ],
                    'qty'   => self::$payment_data_props[ 'product_qty' ],
                        ) );
                self::$payment_data_props[ 'total_payable_amount' ]     = _sumo_pp()->product->get_prop( 'total_payable', array(
                    'props' => self::$payment_data_props[ 'payment_product_props' ],
                    'qty'   => self::$payment_data_props[ 'product_qty' ],
                        ) );
                self::$payment_data_props[ 'next_installment_amount' ]  = _sumo_pp()->product->get_prop( 'balance_payable', array(
                    'props'            => self::$payment_data_props[ 'payment_product_props' ],
                    'qty'              => self::$payment_data_props[ 'product_qty' ],
                    'deposited_amount' => self::$payment_data_props[ 'down_payment' ],
                        ) );
                self::$payment_data_props[ 'remaining_payable_amount' ] = self::$payment_data_props[ 'next_installment_amount' ];
                break;
        }

        self::$payment_data_props = wp_parse_args( self::$payment_data_props, self::get_default_props() );
        return self::$payment_data_props;
    }
}
