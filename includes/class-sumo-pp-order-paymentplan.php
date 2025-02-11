<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle Normal products in cart as Order PaymentPlan in Checkout.
 * 
 * @class SUMO_PP_Order_PaymentPlan
 * @package Class
 */
class SUMO_PP_Order_PaymentPlan {

    /**
     * Get the mode of display.
     * 
     * @var string
     */
    public static $display_mode;

    /**
     * Check whether the customer can proceed to deposit/payment plans in their checkout
     *
     * @var bool 
     */
    protected static $can_user_deposit_payment;
    protected static $session_props = array(
        'product_type'                   => null,
        'product_price'                  => null,
        'product_qty'                    => null,
        'payment_type'                   => null,
        'down_payment'                   => null,
        'activate_payment'               => null,
        'next_payment_date'              => null,
        'next_installment_amount'        => null,
        'total_payable_amount'           => null,
        'remaining_payable_amount'       => null,
        'apply_global_settings'          => null,
        'force_deposit'                  => null,
        'deposit_type'                   => null,
        'deposit_price_type'             => null,
        'fixed_deposit_percent'          => null,
        'fixed_deposit_price'            => null,
        'user_defined_deposit_type'      => null,
        'min_user_defined_deposit_price' => null,
        'max_user_defined_deposit_price' => null,
        'min_deposit'                    => null,
        'max_deposit'                    => null,
        'pay_balance_type'               => null,
        'pay_balance_after'              => null,
        'pay_balance_before'             => null,
        'selected_plans'                 => null,
        'order_items'                    => null,
        'payment_plan_props'             => null,
    );
    protected static $option_props  = array(
        'orderpp_enabled'                => null,
        'product_type'                   => null,
        'payment_type'                   => null,
        'apply_global_settings'          => null,
        'force_deposit'                  => null,
        'products_select_type'           => null,
        'included_products'              => null,
        'excluded_products'              => null,
        'included_categories'            => null,
        'excluded_categories'            => null,
        'deposit_type'                   => null,
        'deposit_price_type'             => null,
        'fixed_deposit_percent'          => null,
        'fixed_deposit_price'            => null,
        'user_defined_deposit_type'      => null,
        'min_user_defined_deposit_price' => null,
        'max_user_defined_deposit_price' => null,
        'min_deposit'                    => null,
        'max_deposit'                    => null,
        'pay_balance_type'               => null,
        'pay_balance_after'              => null,
        'pay_balance_before'             => null,
        'selected_plans'                 => null,
        'min_order_total'                => null,
        'max_order_total'                => null,
        'shipping_during'                => null,
        'labels'                         => null,
    );

    /**
     * Check whether the Current user can pay with Deposits
     */
    protected static $current_user_can_purchase = null;

    /**
     * The single instance of the class.
     */
    protected static $instance = null;

    /**
     * Form to render Order PaymentPlan
     */
    protected static $form = null;

    /**
     * Form to render Order PaymentPlan in cart
     */
    protected static $display_form_in_cart = null;

    /**
     * Create instance for SUMO_PP_Order_PaymentPlan.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get form to render Order PaymentPlan
     */
    public function get_form() {
        if ( is_null( self::$form ) ) {
            self::$form = get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_form_position', 'checkout_order_review' );
        }

        return self::$form;
    }

    /**
     * Can render Order PaymentPlan form in Cart
     */
    public static function show_form_in_cart() {
        if ( is_null( self::$display_form_in_cart ) ) {
            self::$display_form_in_cart = 'yes' === get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_in_cart', 'no' ) ? true : false;
        }
        return self::$display_form_in_cart;
    }

    /**
     * Init SUMO_PP_Order_PaymentPlan.
     */
    public function init() {
        if ( ! is_admin() ) {
            add_action( 'wp_loaded', array( $this, 'get_option_props' ), 0 );
        }

        if ( self::show_form_in_cart() ) {
            add_action( 'woocommerce_before_cart_totals', array( $this, 'render_plan_selector' ) );
            add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'render_review_payment' ), 999 );
        }

        add_action( 'woocommerce_' . $this->get_form(), array( $this, 'render_plan_selector' ) );
        add_action( 'woocommerce_review_order_after_order_total', array( $this, 'render_review_payment' ), 999 );
        add_filter( 'woocommerce_cart_totals_order_total_html', array( $this, 'render_payable_now' ), 10 );
        add_filter( 'woocommerce_get_order_item_totals', array( $this, 'render_payment_details' ), 999, 2 );
        add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'render_order_item_meta_key' ), 999, 3 );

        add_action( 'wp_loaded', array( $this, 'get_payment_from_session' ), 20 );
        add_action( 'woocommerce_after_calculate_totals', array( $this, 'get_payment_from_session' ), 20 );
        add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'maybe_unsubscribe' ) );
        add_action( 'woocommerce_cart_emptied', array( $this, 'unsubscribe' ) );
        add_filter( 'woocommerce_cart_get_total', array( $this, 'set_cart_total' ), 999, 1 );
        add_filter( 'woocommerce_cart_total', array( $this, 'set_total_payable_amount' ), 999, 1 );
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout' ), 999, 2 );
        add_filter( 'woocommerce_calculated_total', array( $this, 'prevent_shipping_charges_in_initial_order' ), 99 );

        add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_order_items' ), 999 );
        add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'add_order_items' ), 999 ); // May be create the payment plan meta based on block checkout.

        add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'maybe_save_order_total' ), 999, 2 ); // Calculate totals when updating an order. 
        add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'save_order_total' ), 9999 ); // Save order total on payment plan block checkout.

        add_action( 'woocommerce_grant_product_download_permissions', array( $this, 'grant_product_download_permissions' ), 99 );
        add_action( 'woocommerce_reduce_order_stock', array( $this, 'reduce_stock_levels' ) );
    }

    public function current_user_can_purchase() {
        if ( is_bool( self::$current_user_can_purchase ) ) {
            return self::$current_user_can_purchase;
        }

        self::$current_user_can_purchase = _sumo_pp_user_can_purchase_payment( get_current_user_id(), array(
            'limit_by'            => get_option( SUMO_PP_PLUGIN_PREFIX . 'show_order_payment_plan_for', 'all_users' ),
            'filtered_users'      => ( array ) get_option( SUMO_PP_PLUGIN_PREFIX . 'get_limited_users_of_order_payment_plan' ),
            'filtered_user_roles' => ( array ) get_option( SUMO_PP_PLUGIN_PREFIX . 'get_limited_userroles_of_order_payment_plan' ),
                ) );

        return self::$current_user_can_purchase;
    }

    public function charge_shipping_during() {
        if ( ! empty( self::$option_props ) && WC()->cart->needs_shipping() && ( WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() ) > 0 ) {
            return self::$option_props[ 'shipping_during' ];
        }

        return 'initial-payment';
    }

    /**
     * Check whether the cart contains valid products to perform Order PaymentPlan by the user.
     * 
     * @return bool
     */
    public function cart_contains_valid_products() {
        $parent_items_in_cart = array();
        $items_in_cart        = array();
        if ( ! is_null( WC()->cart ) ) {
            foreach ( WC()->cart->cart_contents as $item ) {
                $parent_items_in_cart[] = $item[ 'product_id' ];
                $items_in_cart[]        = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ];
            }
        }

        $valid = true;
        switch ( self::$option_props[ 'products_select_type' ] ) {
            case 'included_products':
                $valid = 0 === count( array_diff( $items_in_cart, self::$option_props[ 'included_products' ] ) ) ? true : false;

                if ( ! $valid ) {
                    $valid = 0 === count( array_diff( $parent_items_in_cart, self::$option_props[ 'included_products' ] ) ) ? true : false;
                }
                break;
            case 'excluded_products':
                $valid = 0 === count( array_intersect( $items_in_cart, self::$option_props[ 'excluded_products' ] ) ) ? true : false;

                if ( ! $valid ) {
                    $valid = 0 === count( array_intersect( $parent_items_in_cart, self::$option_props[ 'excluded_products' ] ) ) ? true : false;
                }
                break;
            case 'included_categories':
                $products = new WP_Query( array(
                    'post_type'      => 'product',
                    'post_status'    => 'publish',
                    'posts_per_page' => '-1',
                    'fields'         => 'ids',
                    'cache_results'  => false,
                    'tax_query'      => array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => array_map( 'absint', self::$option_props[ 'included_categories' ] ),
                            'operator' => 'IN',
                        ),
                        array(
                            'taxonomy' => 'product_type',
                            'field'    => 'slug',
                            'terms'    => array( 'grouped' ),
                            'operator' => 'NOT IN',
                        ),
                    ),
                        ) );

                $found_products = ! empty( $products->posts ) ? $products->posts : array();
                $valid          = 0 === count( array_diff( $parent_items_in_cart, $found_products ) ) ? true : false;
                break;
            case 'excluded_categories':
                $products       = new WP_Query( array(
                    'post_type'      => 'product',
                    'post_status'    => 'publish',
                    'posts_per_page' => '-1',
                    'fields'         => 'ids',
                    'cache_results'  => false,
                    'tax_query'      => array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => array_map( 'absint', self::$option_props[ 'excluded_categories' ] ),
                            'operator' => 'IN',
                        ),
                        array(
                            'taxonomy' => 'product_type',
                            'field'    => 'slug',
                            'terms'    => array( 'grouped' ),
                            'operator' => 'NOT IN',
                        ),
                    ),
                        ) );

                $found_products = ! empty( $products->posts ) ? $products->posts : array();
                $valid          = 0 === count( array_intersect( $parent_items_in_cart, $found_products ) ) ? true : false;
                break;
        }

        return $valid;
    }

    public function get_default_props( $props ) {
        return array_map( '__return_null', $props );
    }

    public function get_option_props() {
        if ( is_bool( self::$option_props[ 'orderpp_enabled' ] ) ) {
            return self::$option_props;
        }

        $option_props                      = array();
        $option_props[ 'orderpp_enabled' ] = 'yes' === get_option( SUMO_PP_PLUGIN_PREFIX . 'enable_order_payment_plan', 'no' ) ? true : false;

        if ( ! $option_props[ 'orderpp_enabled' ] || ! $this->current_user_can_purchase() ) {
            return self::$option_props;
        }

        self::$display_mode = get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_display_mode', 'multiple' );

        $option_props[ 'product_type' ]          = 'order';
        $option_props[ 'payment_type' ]          = get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_type', 'pay-in-deposit' );
        $option_props[ 'apply_global_settings' ] = 'yes' === get_option( SUMO_PP_PLUGIN_PREFIX . 'apply_global_settings_for_order_payment_plan', 'no' );
        $option_props[ 'force_deposit' ]         = $option_props[ 'apply_global_settings' ] ? ( 'payment-plans' === $option_props[ 'payment_type' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'force_payment_plan', 'no' ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'force_deposit', 'no' ) ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'force_order_payment_plan', 'no' );
        $option_props[ 'products_select_type' ]  = get_option( SUMO_PP_PLUGIN_PREFIX . 'get_order_payment_plan_products_select_type', 'all_products' );
        $option_props[ 'included_products' ]     = false === get_option( SUMO_PP_PLUGIN_PREFIX . 'get_included_products_of_order_payment_plan' ) ? get_option( SUMO_PP_PLUGIN_PREFIX . 'get_selected_products_of_order_payment_plan', array() ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'get_included_products_of_order_payment_plan', array() );
        $option_props[ 'excluded_products' ]     = get_option( SUMO_PP_PLUGIN_PREFIX . 'get_excluded_products_of_order_payment_plan', array() );
        $option_props[ 'included_categories' ]   = get_option( SUMO_PP_PLUGIN_PREFIX . 'get_included_categories_of_order_payment_plan', array() );
        $option_props[ 'excluded_categories' ]   = get_option( SUMO_PP_PLUGIN_PREFIX . 'get_excluded_categories_of_order_payment_plan', array() );

        if ( 'pay-in-deposit' === $option_props[ 'payment_type' ] ) {
            $option_props[ 'deposit_type' ] = $option_props[ 'apply_global_settings' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'deposit_type', 'pre-defined' ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_deposit_type', 'pre-defined' );

            if ( 'user-defined' === $option_props[ 'deposit_type' ] ) {
                $option_props[ 'user_defined_deposit_type' ] = $option_props[ 'apply_global_settings' ] ? 'percent-of-product-price' : get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_user_defined_deposit_type', 'percent-of-product-price' );

                if ( 'fixed-price' === $option_props[ 'user_defined_deposit_type' ] && ! $option_props[ 'apply_global_settings' ] ) {
                    $option_props[ 'min_user_defined_deposit_price' ] = floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'min_order_payment_plan_user_defined_deposit_price' ) );
                    $option_props[ 'max_user_defined_deposit_price' ] = floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'max_order_payment_plan_user_defined_deposit_price' ) );
                } else {
                    $option_props[ 'min_deposit' ] = $option_props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'min_deposit', '0.01' ) ) : floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'min_order_payment_plan_deposit', '0.01' ) );
                    $option_props[ 'max_deposit' ] = $option_props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'max_deposit', '99.99' ) ) : floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'max_order_payment_plan_deposit', '99.99' ) );
                }
            } else {
                $option_props[ 'deposit_price_type' ] = $option_props[ 'apply_global_settings' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'deposit_price_type', 'percent-of-product-price' ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_deposit_price_type', 'percent-of-product-price' );

                if ( 'fixed-price' === $option_props[ 'deposit_price_type' ] ) {
                    $option_props[ 'fixed_deposit_price' ] = $option_props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_price', '0' ) ) : floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'fixed_order_payment_plan_deposit_price', '0' ) );
                } else {
                    $option_props[ 'fixed_deposit_percent' ] = $option_props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_percent', '50' ) ) : floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'fixed_order_payment_plan_deposit_percent', '50' ) );
                }
            }
            if ( $option_props[ 'apply_global_settings' ] ) {
                $option_props[ 'pay_balance_type' ]  = 'after';
                $option_props[ 'pay_balance_after' ] = false === get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' ) ? absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_balance_after' ) ) : absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' ) );
            } else {
                $option_props[ 'pay_balance_type' ] = get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_pay_balance_type', 'after' );

                if ( 'after' === $option_props[ 'pay_balance_type' ] ) {
                    $option_props[ 'pay_balance_after' ] = absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_pay_balance_after' ) );
                } else {
                    $option_props[ 'pay_balance_before' ] = get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_pay_balance_before' );

                    if ( $option_props[ 'pay_balance_before' ] && apply_filters( 'sumopaymentplans_end_installment_at_end_of_the_day', false, 'deposit' ) ) {
                        $option_props[ 'pay_balance_before' ] .= ' 23:59:59';
                    }

                    if ( _sumo_pp_get_timestamp( $option_props[ 'pay_balance_before' ], 0, true ) <= _sumo_pp_get_timestamp( 0, 0, true ) ) {
                        return;
                    }
                }
            }
        } else if ( 'payment-plans' === $option_props[ 'payment_type' ] ) {
            $option_props[ 'selected_plans' ] = $option_props[ 'apply_global_settings' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'selected_plans', array() ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'selected_plans_for_order_payment_plan', array() );
            $option_props[ 'selected_plans' ] = is_array( $option_props[ 'selected_plans' ] ) ? $option_props[ 'selected_plans' ] : array();
        }

        $option_props[ 'apply_global_settings' ] = $option_props[ 'apply_global_settings' ] ? 'yes' : 'no';
        $option_props[ 'min_order_total' ]       = get_option( SUMO_PP_PLUGIN_PREFIX . 'min_order_total_to_display_order_payment_plan' );
        $option_props[ 'max_order_total' ]       = get_option( SUMO_PP_PLUGIN_PREFIX . 'max_order_total_to_display_order_payment_plan' );
        $option_props[ 'shipping_during' ]       = get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_charge_shipping_during', 'initial-payment' );
        $option_props[ 'labels' ]                = array(
            'enable'         => get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_label' ),
            'deposit_amount' => get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_a_deposit_amount_label' ),
            'payment_plans'  => get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_with_payment_plans_label' ),
        );

        self::$option_props = wp_parse_args( ( array ) apply_filters( 'sumopaymentplans_get_orderpp_props', $option_props ), $this->get_default_props( self::$option_props ) );
        return self::$option_props;
    }

    public function is_cart_total_valid() {
        $bool = true;

        if ( is_numeric( self::$option_props[ 'min_order_total' ] ) && is_numeric( self::$option_props[ 'max_order_total' ] ) ) {
            $bool = $this->get_total_payable_amount( null, false ) >= floatval( self::$option_props[ 'min_order_total' ] ) && $this->get_total_payable_amount( null, false ) <= floatval( self::$option_props[ 'max_order_total' ] );
        } else if ( is_numeric( self::$option_props[ 'max_order_total' ] ) ) {
            $bool = $this->get_total_payable_amount( null, false ) <= floatval( self::$option_props[ 'max_order_total' ] );
        } else if ( is_numeric( self::$option_props[ 'min_order_total' ] ) ) {
            $bool = $this->get_total_payable_amount( null, false ) >= floatval( self::$option_props[ 'min_order_total' ] );
        }

        return $bool;
    }

    public function can_user_deposit_payment() {
        if ( is_bool( self::$can_user_deposit_payment ) ) {
            return self::$can_user_deposit_payment;
        }

        if (
                WC()->cart && ! empty( WC()->cart->cart_contents ) &&
                self::$option_props[ 'orderpp_enabled' ] &&
                $this->is_cart_total_valid()
        ) {
            self::$can_user_deposit_payment = $this->cart_contains_valid_products();

            foreach ( WC()->cart->cart_contents as $cart_item ) {
                if ( empty( $cart_item[ 'product_id' ] ) ) {
                    continue;
                }

                $product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ];

                if ( ! empty( $cart_item[ 'sumopaymentplans' ][ 'payment_product_props' ][ 'payment_type' ] ) ) {
                    self::$can_user_deposit_payment = false;
                    break;
                } else if ( class_exists( 'SUMOSubscriptions' ) && function_exists( 'sumo_is_subscription_product' ) && sumo_is_subscription_product( $product_id ) ) {
                    self::$can_user_deposit_payment = false;
                    break;
                } else if ( class_exists( 'SUMOMemberships' ) && function_exists( 'sumo_is_membership_product' ) && sumo_is_membership_product( $product_id ) ) {
                    self::$can_user_deposit_payment = false;
                    break;
                }
            }
        }

        return self::$can_user_deposit_payment;
    }

    public function is_enabled() {
        if ( ! $this->can_user_deposit_payment() ) {
            return false;
        }

        $this->get_session_props();
        return 'order' === self::$session_props[ 'product_type' ];
    }

    public function get_prop( $context, $props = array() ) {
        if ( empty( $props ) ) {
            if ( ! empty( WC()->cart->sumopaymentplans[ 'order' ] ) ) {
                $props = WC()->cart->sumopaymentplans[ 'order' ];
            }
        }

        if ( ! is_array( $props ) || empty( $props[ 'payment_type' ] ) ) {
            return null;
        }

        $props = wp_parse_args( $props, $this->get_default_props( self::$session_props ) );

        switch ( $props[ 'payment_type' ] ) {
            case 'payment-plans':
                $prop = _sumo_pp()->plan->get_prop( $context, array(
                    'props'         => $props[ 'payment_plan_props' ],
                    'product_price' => $props[ 'product_price' ],
                        ) );

                if ( is_null( $prop ) ) {
                    if ( isset( $props[ $context ] ) ) {
                        return $props[ $context ];
                    }
                } else {
                    return $prop;
                }
                break;
            case 'pay-in-deposit':
                switch ( $context ) {
                    case 'total_payable':
                        return $props[ 'product_price' ];
                    case 'balance_payable':
                        return max( $props[ 'down_payment' ], $props[ 'product_price' ] ) - min( $props[ 'down_payment' ], $props[ 'product_price' ] );
                    case 'next_payment_on':
                        if ( 'before' === $props[ 'pay_balance_type' ] ) {
                            return _sumo_pp_get_date( $props[ 'pay_balance_before' ] );
                        } else {
                            $pay_balance_after = $props[ 'pay_balance_after' ]; //in days
                            return $pay_balance_after > 0 && 'after_admin_approval' !== $props[ 'activate_payment' ] ? _sumo_pp_get_date( "+{$pay_balance_after} days" ) : '';
                        }
                    default:
                        if ( isset( $props[ $context ] ) ) {
                            return $props[ $context ];
                        }
                }
                break;
        }

        return null;
    }

    public function get_session_props( $cart_session = true ) {
        $session_props = null;

        if ( $cart_session ) {
            if ( ! empty( WC()->cart->sumopaymentplans[ 'order' ] ) ) {
                $session_props = WC()->cart->sumopaymentplans[ 'order' ];
            }
        } else {
            $session_props = self::$session_props;
        }

        self::$session_props = wp_parse_args( is_array( $session_props ) ? $session_props : array(), $this->get_default_props( self::$session_props ) );
        return self::$session_props;
    }

    public function get_total_payable_amount( $props = null, $calc_initial_shipping = true ) {
        if ( isset( $props[ 'product_price' ] ) ) {
            return floatval( $props[ 'product_price' ] );
        }

        if ( _sumo_pp_is_wc_version( '<', '3.2' ) ) {
            $cart_total = WC()->cart->total;
        } else {
            remove_filter( 'woocommerce_cart_get_total', array( $this, 'set_cart_total' ), 999, 1 );
            $cart_total = WC()->cart->get_total( '' );
            add_filter( 'woocommerce_cart_get_total', array( $this, 'set_cart_total' ), 999, 1 );
        }

        if ( $calc_initial_shipping && 'initial-payment' === $this->charge_shipping_during() ) {
            $cart_total -= ( WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() );
            $cart_total = max( 0, $cart_total );
        }

        return floatval( $cart_total );
    }

    public function get_fixed_deposit_amount( $props = null, $calc_initial_shipping = true ) {
        if ( is_null( $props ) ) {
            $props = self::$option_props;
        }

        $fixed_deposit_amount = 0;
        if (
                'pay-in-deposit' === $props[ 'payment_type' ] &&
                'pre-defined' === $props[ 'deposit_type' ]
        ) {
            $cart_total = $this->get_total_payable_amount( $props );
            if ( $cart_total ) {
                if ( 'fixed-price' === $props[ 'deposit_price_type' ] ) {
                    $fixed_deposit_amount = $props[ 'fixed_deposit_price' ];
                } else {
                    $fixed_deposit_amount = ( $cart_total * floatval( $props[ 'fixed_deposit_percent' ] ) ) / 100;
                }

                if ( $calc_initial_shipping && 'initial-payment' === $this->charge_shipping_during() ) {
                    $fixed_deposit_amount += ( WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() );
                }
            }
        }

        return $fixed_deposit_amount;
    }

    public function get_user_defined_deposit_amount_range( $props = null ) {
        if ( is_null( $props ) ) {
            $props = self::$option_props;
        }

        $min_amount = 0;
        $max_amount = 0;
        if (
                'pay-in-deposit' === $props[ 'payment_type' ] &&
                'user-defined' === $props[ 'deposit_type' ]
        ) {
            $cart_total = $this->get_total_payable_amount();
            if ( $cart_total ) {
                if ( 'fixed-price' === $props[ 'user_defined_deposit_type' ] ) {
                    $min_amount = $props[ 'min_user_defined_deposit_price' ];
                    $max_amount = $props[ 'max_user_defined_deposit_price' ];
                } else {
                    $min_amount = ( $cart_total * floatval( $props[ 'min_deposit' ] ) ) / 100;
                    $max_amount = ( $cart_total * floatval( $props[ 'max_deposit' ] ) ) / 100;
                }
            }
        }

        return array(
            'min' => round( $min_amount, 2 ),
            'max' => round( $max_amount, 2 ),
        );
    }

    public function get_payment_info_to_display( $session_props, $context = 'default' ) {
        if ( ! empty( $session_props[ 'payment_type' ] ) ) {
            $payment_data = $session_props;
        }

        if ( empty( $payment_data ) ) {
            return '';
        }

        $shortcodes = _sumo_pp_get_shortcodes_from_cart_r_checkout( $payment_data );

        $info = '';
        switch ( $context ) {
            case 'balance_payable':
                $info = str_replace( $shortcodes[ 'find' ], $shortcodes[ 'replace' ], get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payable_label' ) );
                break;
            default:
                if ( 'payment-plans' === $payment_data[ 'payment_type' ] ) {
                    $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_label' );

                    if ( $label && false === strpos( $label, '[' ) && false === strpos( $label, ']' ) ) {
                        /* translators: 1: label 2: plan name */
                        $info .= sprintf( __( '<p><strong>%1$s</strong> <br>%2$s</p>', 'sumopaymentplans' ), $label, $shortcodes[ 'content' ][ '[sumo_pp_payment_plan_name]' ] );
                    } else {
                        $info .= str_replace( $shortcodes[ 'find' ], $shortcodes[ 'replace' ], $label );
                    }

                    if ( $shortcodes[ 'content' ][ '[sumo_pp_payment_plan_desc]' ] ) {
                        $info .= str_replace( $shortcodes[ 'find' ], $shortcodes[ 'replace' ], get_option( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_desc_label' ) );
                    }

                    $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'next_payment_date_label' );
                    if ( 'enabled' === $payment_data[ 'payment_plan_props' ][ 'sync' ] && $payment_data[ 'down_payment' ] <= 0 ) {
                        $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'first_payment_on_label' );
                    }
                } else {
                    $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'next_payment_date_label' );
                    if ( 'before' === $payment_data[ 'pay_balance_type' ] ) {
                        $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due_date_label' );
                    }
                }

                $info .= str_replace( $shortcodes[ 'find' ], $shortcodes[ 'replace' ], get_option( SUMO_PP_PLUGIN_PREFIX . 'total_payable_label' ) );

                if ( 'payment-plans' === $payment_data[ 'payment_type' ] ) {
                    $info .= str_replace( $shortcodes[ 'find' ], $shortcodes[ 'replace' ], get_option( SUMO_PP_PLUGIN_PREFIX . 'next_installment_amount_label' ) );
                }

                if ( $shortcodes[ 'content' ][ '[sumo_pp_next_payment_date]' ] ) {
                    if ( $label && false === strpos( $label, '[' ) && false === strpos( $label, ']' ) ) {
                        /* translators: 1: label 2: next payment date */
                        $info .= sprintf( __( '<br><small style="color:#777;">%1$s <strong>%2$s</strong></small>', 'sumopaymentplans' ), $label, $shortcodes[ 'content' ][ '[sumo_pp_next_payment_date]' ] );
                    } else {
                        $info .= str_replace( $shortcodes[ 'find' ], $shortcodes[ 'replace' ], $label );
                    }
                }
        }

        return $info;
    }

    public function get_plan_selector_form( $echo = false ) {
        if ( ! in_array( self::$option_props[ 'payment_type' ], array( 'pay-in-deposit', 'payment-plans' ) ) ) {
            return '';
        }

        if ( 'payment-plans' === self::$option_props[ 'payment_type' ] && empty( self::$option_props[ 'selected_plans' ] ) ) {
            return '';
        }

        $deposit_amount_range = $this->get_user_defined_deposit_amount_range();

        ob_start();
        _sumo_pp_get_template( 'checkout/order-paymentplan/form.php', array(
            'enabled'             => $this->is_enabled(),
            'option_props'        => self::$option_props,
            'session_props'       => self::$session_props,
            'min_deposit_price'   => $deposit_amount_range[ 'min' ],
            'max_deposit_price'   => $deposit_amount_range[ 'max' ],
            'fixed_deposit_price' => $this->get_fixed_deposit_amount(),
            'object'              => $this,
        ) );

        if ( $echo ) {
            ob_end_flush();
        } else {
            return ob_get_clean();
        }
    }

    public function get_review_payment_info( $echo = false, $is_checkout_blocks = false ) {
        $shipping_total = WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax();

        $session_props                           = self::$session_props;
        $session_props[ 'total_payable_amount' ] += $shipping_total;

        if ( 'final-payment' === $this->charge_shipping_during() ) {
            $session_props[ 'remaining_payable_amount' ] += $shipping_total;
        } else {
            $session_props[ 'down_payment' ] += $shipping_total;
        }

        ob_start();
        _sumo_pp_get_template( 'checkout/order-paymentplan/review-payment.php', array(
            'down_payment'                   => $session_props[ 'down_payment' ],
            'payment_info'                   => $this->get_payment_info_to_display( $session_props ),
            'balance_payable'                => $this->get_payment_info_to_display( $session_props, 'balance_payable' ),
            'is_checkout_blocks_api_request' => $is_checkout_blocks
        ) );

        if ( $echo ) {
            ob_end_flush();
        } else {
            return ob_get_clean();
        }
    }

    public function render_plan_selector() {
        if ( ! $this->can_user_deposit_payment() ) {
            return;
        }

        $this->get_plan_selector_form( true );
    }

    public function render_review_payment() {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $this->get_review_payment_info( true );
    }

    public function render_payable_now( $total ) {
        if ( 'final-payment' === $this->charge_shipping_during() && $this->is_enabled() ) {
            $total .= '<div>';
            $total .= '<small style="color:#777;font-size:smaller;">';
            /* translators: 1: currency symbol 2: shipping total */
            $total .= sprintf( __( '(Shipping amount <strong>%1$s%2$s</strong> will be calculated during final payment)', 'sumopaymentplans' ), get_woocommerce_currency_symbol(), WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() );
            $total .= '</small>';
            $total .= '</div>';
        }

        return $total;
    }

    public function set_session( $args ) {
        $this->get_option_props();

        if ( ! self::$option_props[ 'orderpp_enabled' ] ) {
            return false;
        }

        $args = wp_parse_args( $args, array(
            'order_items'            => array(),
            'plan_props'             => null,
            'down_payment'           => null,
            'order_total'            => 0,
            'deposited_amount'       => 0,
            'charge_shipping_during' => 'initial-payment',
                ) );

        $session_props                             = array();
        $session_props[ 'product_type' ]           = 'order';
        $session_props[ 'product_price' ]          = $args[ 'order_total' ];
        $session_props[ 'product_qty' ]            = 1;
        $session_props[ 'order_items' ]            = $args[ 'order_items' ];
        $session_props[ 'charge_shipping_during' ] = $args[ 'charge_shipping_during' ];
        $session_props[ 'activate_payment' ]       = get_option( SUMO_PP_PLUGIN_PREFIX . 'activate_payments', 'auto' );

        foreach ( self::$option_props as $option => $option_val ) {
            if ( in_array( $option, array( 'orderpp_enabled', 'labels', 'min_order_total', 'max_order_total', 'shipping_during' ) ) ) {
                continue;
            }

            $session_props[ $option ] = $option_val;
        }

        if ( is_numeric( $args[ 'plan_props' ] ) ) {
            $session_props[ 'payment_plan_props' ] = _sumo_pp()->plan->get_props( $args[ 'plan_props' ] );
        }

        if ( is_numeric( $args[ 'down_payment' ] ) ) {
            $session_props[ 'down_payment' ] = $args[ 'down_payment' ];
        } elseif ( 'payment-plans' === $session_props[ 'payment_type' ] ) {
            if ( empty( $session_props[ 'payment_plan_props' ][ 'payment_schedules' ] ) ) {
                return false;
            }

            $session_props[ 'down_payment' ] = $this->get_prop( 'down_payment', $session_props );
        } else {
            $session_props[ 'down_payment' ] = 'user-defined' === $session_props[ 'deposit_type' ] ? floatval( $args[ 'deposited_amount' ] ) : $this->get_fixed_deposit_amount( $session_props, false );
        }

        $session_props[ 'next_payment_date' ]        = $this->get_prop( 'next_payment_on', $session_props );
        $session_props[ 'next_installment_amount' ]  = $this->get_prop( 'next_installment_amount', $session_props );
        $session_props[ 'total_payable_amount' ]     = $this->get_prop( 'total_payable', $session_props );
        $session_props[ 'remaining_payable_amount' ] = $this->get_prop( 'balance_payable', $session_props );
        self::$session_props                         = wp_parse_args( $session_props, $this->get_default_props( self::$session_props ) );
        return true;
    }

    public function get_payment_from_session() {
        if ( ! did_action( 'woocommerce_loaded' ) || ! isset( WC()->cart ) ) {
            return;
        }

        if ( ! $this->can_user_deposit_payment() ) {
            return;
        }

        WC()->cart->sumopaymentplans = array();

        if ( 'yes' !== WC()->session->get( '_sumo_pp_orderpp_enabled' ) ) {
            return;
        }

        $props = array(
            'plan_props'             => WC()->session->get( '_sumo_pp_orderpp_chosen_payment_plan' ),
            'deposited_amount'       => WC()->session->get( '_sumo_pp_orderpp_deposited_amount' ),
            'order_total'            => $this->get_total_payable_amount(),
            'charge_shipping_during' => $this->charge_shipping_during(),
        );

        foreach ( WC()->cart->cart_contents as $item ) {
            if ( empty( $item[ 'product_id' ] ) ) {
                continue;
            }

            $item_id                            = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ];
            $props[ 'order_items' ][ $item_id ] = array(
                'price'             => $item[ 'data' ]->get_price(),
                'qty'               => $item[ 'quantity' ],
                'line_subtotal'     => $item[ 'line_subtotal' ],
                'line_subtotal_tax' => $item[ 'line_subtotal_tax' ],
                'line_total'        => $item[ 'line_total' ],
                'line_tax'          => $item[ 'line_tax' ],
            );
        }

        $props = apply_filters( 'sumopaymentplans_get_order_pp_from_cart_session', $props );

        if ( $this->set_session( $props ) ) {
            WC()->cart->sumopaymentplans[ 'order' ] = $this->get_session_props( false );
        }
    }

    /**
     * Maybe unsubscribe.
     */
    public function maybe_unsubscribe( $cart ) {
        if ( $cart->is_empty() ) {
            $this->unsubscribe();
        }
    }

    /**
     * Unsubscribe.
     */
    public function unsubscribe() {
        WC()->session->__unset( '_sumo_pp_orderpp_enabled' );
        WC()->session->__unset( '_sumo_pp_orderpp_deposited_amount' );
        WC()->session->__unset( '_sumo_pp_orderpp_chosen_payment_plan' );
    }

    public function set_cart_total( $total ) {
        if ( $this->is_enabled() ) {
            $total = self::$session_props[ 'down_payment' ];

            if ( 'initial-payment' === $this->charge_shipping_during() ) {
                $shipping_total = WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax();
                $total          += $shipping_total;
            }
        }

        return $total;
    }

    public function set_total_payable_amount( $total ) {
        if (
                is_checkout() &&
                $this->can_user_deposit_payment() &&
                isset( self::$session_props[ 'product_type' ] ) &&
                'order' === self::$session_props[ 'product_type' ]
        ) {
            $total = wc_price( self::$session_props[ 'total_payable_amount' ] );
        }

        return $total;
    }

    public function validate_checkout( $data, $errors = '' ) {
        if ( empty( $errors ) || ! $this->is_enabled() || 'pay-in-deposit' !== self::$session_props[ 'payment_type' ] ) {
            return;
        }

        if ( ! is_numeric( self::$session_props[ 'down_payment' ] ) ) {
            /* translators: 1: deposit amount */
            $errors->add( 'required-field', sprintf( __( '<strong>%s</strong> is a required field.', 'sumopaymentplans' ), self::$option_props[ 'labels' ][ 'deposit_amount' ] ) );
        } else if ( 'user-defined' === self::$session_props[ 'deposit_type' ] ) {
            $deposit_amount = $this->get_user_defined_deposit_amount_range();

            if ( $deposit_amount[ 'max' ] ) {
                if ( self::$session_props[ 'down_payment' ] < $deposit_amount[ 'min' ] || self::$session_props[ 'down_payment' ] > $deposit_amount[ 'max' ] ) {
                    /* translators: 1: min deposit amount 2: max deposit amount */
                    $errors->add( 'required-field', sprintf( __( 'Deposited amount should be between <strong>%1$s</strong> and <strong>%2$s</strong>.', 'sumopaymentplans' ), wc_price( $deposit_amount[ 'min' ] ), wc_price( $deposit_amount[ 'max' ] ) ) );
                }
            } elseif ( self::$session_props[ 'down_payment' ] < $deposit_amount[ 'min' ] ) {
                /* translators: 1: min deposit amount */
                $errors->add( 'required-field', sprintf( __( 'Deposit amount should not be less than <strong>%s</strong>.', 'sumopaymentplans' ), wc_price( $deposit_amount[ 'min' ] ) ) );
            }
        }
    }

    public function prevent_shipping_charges_in_initial_order( $total ) {
        if ( 'final-payment' === $this->charge_shipping_during() && $this->is_enabled() ) {
            $shipping_total = WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax();
            $total          = max( $total, $shipping_total ) - min( $total, $shipping_total );
        }

        return $total;
    }

    public function add_order_items( $order_id ) {
        $order = is_object( $order_id ) ? $order_id : wc_get_order( $order_id );
        if ( empty( self::$session_props[ 'product_type' ] ) || 'order' !== self::$session_props[ 'product_type' ] ) {
            return;
        }

        $payment_order = _sumo_pp_maybe_get_order_instance( $order );

        if ( 'single' === self::$display_mode ) {
            $order_item_data = array();

            foreach ( WC()->cart->get_cart() as $item ) {
                if ( empty( $item[ 'data' ] ) ) {
                    continue;
                }

                $order_item_data[] = array( 'product' => $item[ 'data' ], 'order_item' => $item );
            }

            foreach ( $payment_order->get_items() as $item ) {
                if ( empty( $item[ 'item_meta' ] ) ) {
                    continue;
                }

                $order_item_data[] = $item[ 'item_meta' ];
            }

            $payment_order->remove_order_items( 'line_item' );

            if ( empty( $order_item_data ) ) {
                return;
            }

            $item_data = current( $order_item_data );

            $this->add_items_to_order( $payment_order, $item_data[ 'product' ], array(
                'order_item_data' => $order_item_data,
            ) );
        } else {
            $payment_order->update_meta_data( 'is_sumo_pp_orderpp', 'yes' );
            $payment_order->update_meta_data( '_sumo_pp_payment_data', self::$session_props );
            $payment_order->save();
        }
    }

    public function render_payment_details( $total_rows, $order ) {
        $order = _sumo_pp_maybe_get_order_instance( $order );

        if ( ! $order ) {
            return $total_rows;
        }

        $shipping_amount = _sumo_pp_maybe_get_shipping_amount_for_order( $order );

        if ( _sumo_pp_is_parent_order( $order ) ) {
            $total_payable_amount = null;

            $payment_data = _sumo_pp_get_payment_data_by_item_type( $order, 'order' );
            if ( $payment_data ) {
                $total_payable_amount = $payment_data[ 'total_payable_amount' ];
            } else if ( _sumo_pp_is_payment_order( $order ) && ! _sumo_pp_is_orderpp_created_by_multiple( $order ) ) {
                //BKWD CMPT < 3.1
                $payment = _sumo_pp_get_payment( _sumo_pp_get_payment_id_from_order( $order ) );

                if ( $payment && 'order' === $payment->get_product_type() ) {
                    $total_payable_amount = $payment->get_total_payable_amount();
                }
            }

            if ( is_numeric( $total_payable_amount ) ) {
                $total_rows[ 'order_total' ][ 'value' ]       = wc_price( $total_payable_amount + $shipping_amount[ 'for_total_payable' ], array( 'currency' => $order->get_currency() ) );
                $total_rows[ '_sumo_pp_paid_now' ][ 'label' ] = __( 'Paid Now', 'sumopaymentplans' );
                $total_rows[ '_sumo_pp_paid_now' ][ 'value' ] = wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) );

                if ( _sumo_pp_is_orderpp_created_by_multiple( $order ) ) {
                    $payment_details = '';

                    if ( 'payment-plans' === $payment_data[ 'payment_type' ] ) {
                        $payment_details .= __( 'Payment Plan', 'sumopaymentplans' );
                        $payment_details .= ': ';
                        $payment_details .= get_the_title( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] );
                        $payment_details .= '<br>';
                    }

                    if ( ! empty( $payment_data[ 'total_payable_amount' ] ) ) {
                        $payment_details .= __( 'Total payable', 'sumopaymentplans' );
                        $payment_details .= ': ';
                        $payment_details .= wc_price( $payment_data[ 'total_payable_amount' ] + $shipping_amount[ 'for_total_payable' ], array( 'currency' => $order->get_currency() ) );
                        $payment_details .= '<br>';
                    }

                    if ( ! empty( $payment_data[ 'remaining_payable_amount' ] ) ) {
                        $payment_details = __( 'Balance Payable', 'sumopaymentplans' );
                        $payment_details .= ': ';
                        $payment_details .= wc_price( $payment_data[ 'remaining_payable_amount' ] + $shipping_amount[ 'for_balance_payable' ], array( 'currency' => $order->get_currency() ) );
                        $payment_details .= '<br>';
                    }

                    $next_payment_date = '';
                    if ( $payment_data[ 'next_payment_date' ] ) {
                        $next_payment_date = _sumo_pp_get_date_to_display( $payment_data[ 'next_payment_date' ] );
                    } else if ( 'after_admin_approval' === $payment_data[ 'activate_payment' ] ) {
                        $next_payment_date = __( 'After Admin Approval', 'sumopaymentplans' );
                    }

                    if ( ! empty( $next_payment_date ) ) {
                        $payment_details .= __( 'Next Payment Date', 'sumopaymentplans' );
                        $payment_details .= ': ';
                        $payment_details .= $next_payment_date;
                    }

                    if ( '' !== $payment_details ) {
                        $total_rows[ '_sumo_pp_payment_details' ][ 'label' ] = __( 'Payment Details', 'sumopaymentplans' );
                        $total_rows[ '_sumo_pp_payment_details' ][ 'value' ] = $payment_details;
                    }
                }
            }
        } elseif ( _sumo_pp_is_orderpp_created_by_multiple( $order ) ) {
            $payment_data = $order->get_meta( '_sumo_pp_payment_data', true );

            $payment_details = __( 'Balance Payable', 'sumopaymentplans' );
            $payment_details .= ': ';
            $payment_details .= apply_filters( 'sumopaymentplans_order_balance_payable_to_display', wc_price( $payment_data[ 'remaining_payable_amount' ] + $shipping_amount[ 'for_balance_payable' ], array( 'currency' => $order->get_currency() ) ), $order );
            $payment_details .= '<br>';

            $payment_details .= __( 'Total payable', 'sumopaymentplans' );
            $payment_details .= ': ';
            $payment_details .= wc_price( $payment_data[ 'total_payable_amount' ] + $shipping_amount[ 'for_total_payable' ], array( 'currency' => $order->get_currency() ) );
            $payment_details .= '<br>';

            if ( 'payment-plans' === $payment_data[ 'payment_type' ] ) {
                $payment_details .= __( 'Payment Plan', 'sumopaymentplans' );
                $payment_details .= ': ';
                $payment_details .= get_the_title( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] );
                $payment_details .= '<br>';
            }

            if ( $payment_data[ 'remaining_installments' ] > 1 ) {
                $payment_details .= __( 'Next installment amount', 'sumopaymentplans' );
                $payment_details .= ': ';
                $payment_details .= wc_price( $payment_data[ 'next_installment_amount' ], array( 'currency' => $order->get_currency() ) );
                $payment_details .= '<br>';

                $payment_details .= __( 'Next Payment Date', 'sumopaymentplans' );
                $payment_details .= ': ';
                $payment_details .= _sumo_pp_get_date_to_display( $payment_data[ 'next_payment_date' ] );
            }

            if ( '' !== $payment_details ) {
                $total_rows[ '_sumo_pp_payment_details' ][ 'label' ] = __( 'Payment Details', 'sumopaymentplans' );
                $total_rows[ '_sumo_pp_payment_details' ][ 'value' ] = $payment_details;
            }
        }

        return $total_rows;
    }

    public function maybe_save_order_total( $tax, $order ) {
        $this->save_order_total( $order );
    }

    public function save_order_total( $order ) {
        $order = _sumo_pp_maybe_get_order_instance( $order );

        if ( _sumo_pp_is_orderpp_created_by_multiple( $order ) ) {
            $payment_data = $order->get_meta( '_sumo_pp_payment_data', true );

            if ( _sumo_pp_is_parent_order( $order ) ) {
                $order->set_total( wc_format_decimal( $payment_data[ 'down_payment' ] ) );
            } else {
                $payment        = _sumo_pp_get_payment( _sumo_pp_get_payment_id_from_order( $order ) );
                $payable_amount = $payment_data[ 'payable_amount' ];

                if ( $payment && 'final-payment' === $payment->charge_shipping_during() && 1 === $payment->get_remaining_installments() ) {
                    $initial_payment_order = _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() );

                    if ( $initial_payment_order ) {
                        $payable_amount += $initial_payment_order->get_shipping_total() + $initial_payment_order->get_shipping_tax();
                    }
                }

                $order->set_total( wc_format_decimal( $payable_amount ) );
            }

            $order->save();
        }
    }

    public function add_items_to_order( $payment_order, $product, $args = array() ) {
        $args = wp_parse_args( $args, array(
            'session_props'    => self::$session_props,
            'line_total'       => $payment_order->get_total(),
            'order_item_data'  => array(),
            'add_payment_meta' => true,
                ) );

        $item_id = $payment_order->add_product( false, 1, array(
            'name'      => get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_label' ),
            'variation' => array(),
            'subtotal'  => wc_get_price_excluding_tax( $product, array(
                'qty'   => 1,
                'price' => wc_format_decimal( $args[ 'line_total' ] ),
            ) ),
            'total'     => wc_get_price_excluding_tax( $product, array(
                'qty'   => 1,
                'price' => wc_format_decimal( $args[ 'line_total' ] ),
            ) ),
                ) );

        if ( ! $item_id || is_wp_error( $item_id ) ) {
            return 0;
        }

        if ( ! empty( $args[ 'order_item_data' ] ) ) {
            foreach ( $args[ 'order_item_data' ] as $item_data ) {
                if ( ! is_array( $item_data ) ) {
                    continue;
                }

                if ( isset( $item_data[ 'product' ] ) ) {
                    if ( $item_data[ 'product' ]->is_visible() ) {
                        $product_name = sprintf( '<a href="%s">%s</a>', esc_url( $item_data[ 'product' ]->get_permalink() ), $item_data[ 'product' ]->get_name() );
                    } else {
                        $product_name = $item_data[ 'product' ]->get_name();
                    }

                    wc_add_order_item_meta( $item_id, $product_name, '&nbsp;x' . ( is_array( $item_data[ 'order_item' ] ) ? $item_data[ 'order_item' ][ 'quantity' ] : $item_data[ 'order_item' ]->get_quantity() ) );

                    if ( $item_data[ 'product' ]->is_type( 'variation' ) ) {
                        foreach ( $item_data[ 'product' ]->get_attributes() as $key => $value ) {
                            wc_add_order_item_meta( $item_id, str_repeat( '&nbsp;', 7 ) . str_replace( 'attribute_', '', $key ), $value );
                        }
                    }
                } else {
                    foreach ( $item_data as $meta_key => $meta_value ) {
                        wc_add_order_item_meta( $item_id, $meta_key, $meta_value );
                    }
                }
            }
        }

        if ( $args[ 'add_payment_meta' ] ) {
            _sumo_pp()->order_item->add_order_item_payment_meta( $item_id, $args[ 'session_props' ] );
        }

        return $item_id;
    }

    public function render_order_item_meta_key( $display_key, $meta, $order_item ) {
        $order = _sumo_pp_maybe_get_order_instance( $order_item->get_order_id() );

        if ( ! _sumo_pp_is_orderpp_created_by_multiple( $order ) && _sumo_pp_get_payment_data_by_item_type( $order, 'order' ) ) {
            return $meta->key;
        }

        return $display_key;
    }

    public function grant_product_download_permissions( $order_id ) {
        $order = _sumo_pp_maybe_get_order_instance( $order_id );

        if ( ! $order || _sumo_pp_is_orderpp_created_by_multiple( $order ) || 1 !== count( $order->get_items() ) ) {
            return;
        }

        $payment_data = _sumo_pp_get_payment_data_by_item_type( $order, 'order' );
        if ( ! $payment_data ) {
            return;
        }

        if ( empty( $payment_data[ 'order_items' ] ) ) {
            return;
        }

        foreach ( $payment_data[ 'order_items' ] as $product_id => $data ) {
            $product = wc_get_product( $product_id );

            if ( $product && $product->exists() && $product->is_downloadable() ) {
                $downloads = $product->get_downloads();

                foreach ( array_keys( $downloads ) as $download_id ) {
                    wc_downloadable_file_permission( $download_id, $product, $order, $data[ 'qty' ] );
                }
            }
        }
    }

    public function reduce_stock_levels( $order_id ) {
        $order = _sumo_pp_maybe_get_order_instance( $order_id );

        if ( ! $order || _sumo_pp_is_orderpp_created_by_multiple( $order ) || 1 !== count( $order->get_items() ) ) {
            return;
        }

        $payment_data = _sumo_pp_get_payment_data_by_item_type( $order, 'order' );
        if ( ! $payment_data ) {
            return;
        }

        if ( empty( $payment_data[ 'order_items' ] ) ) {
            return;
        }

        $changes = array();
        foreach ( $payment_data[ 'order_items' ] as $product_id => $data ) {
            $product = wc_get_product( $product_id );

            if ( ! $product ) {
                continue;
            }

            $qty       = absint( $data[ 'qty' ] );
            $new_stock = wc_update_product_stock( $product, $qty, 'decrease' );

            if ( is_wp_error( $new_stock ) ) {
                /* translators: 1: product name */
                $order->add_order_note( sprintf( __( 'Unable to reduce stock for item %s.', 'woocommerce' ), $product->get_formatted_name() ) );
                continue;
            }

            $changes[] = array(
                'product' => $product,
                'from'    => $new_stock + $qty,
                'to'      => $new_stock,
            );
        }

        wc_trigger_stock_change_notifications( $order, $changes );
    }
}
