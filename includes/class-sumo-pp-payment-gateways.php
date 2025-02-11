<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Load Payment Gateways
 * 
 * @class SUMO_PP_Payment_Gateways
 * @package Class
 */
class SUMO_PP_Payment_Gateways {

    /**
     * Check if our payment gateways are loaded in to the WC checkout.
     *
     * @var bool 
     */
    private static $gateways_loaded = false;

    /**
     * Get loaded automatic payment gateways
     *
     * @var array 
     */
    protected static $auto_payment_gateways;

    /**
     * Check if Automatic payment gateways enabled
     *
     * @var bool 
     */
    protected static $auto_payment_gateways_enabled;

    /**
     * Check if Manual payment gateways enabled
     *
     * @var bool 
     */
    protected static $manual_payment_gateways_enabled;

    /**
     * Get mode of payment gateway
     *
     * @var mixed 
     */
    protected static $auto_payment_gateway_mode;

    /**
     * Get the disabled payment gateways in checkout
     *
     * @var array 
     */
    protected static $disabled_payment_gateways;

    /**
     * The single instance of the class.
     */
    protected static $instance = null;

    /**
     * Show payment gateways when order amount is zero
     *
     * @var bool 
     */
    protected static $show_gateways_when_order_amt_zero;

    /**
     * Get payment gateways to hide when order amount is zero
     *
     * @var array 
     */
    protected static $gateways_to_hide_when_order_amt_zero;

    /**
     * Load deprecated gateways
     * 
     * @var array 
     */
    protected static $deprecated_gateways = array();

    /**
     * Create instance for SUMO_PP_Payment_Gateways.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Init SUMO_PP_Payment_Gateways.
     */
    public function init() {
        add_action( 'plugins_loaded', array( $this, 'load_payment_gateways' ), 20 );
        add_filter( 'woocommerce_payment_gateways', array( $this, 'add_payment_gateways' ), 999 );
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'set_payment_gateways' ), 999 );
        add_filter( 'woocommerce_cart_needs_payment', array( $this, 'need_payment_gateways' ), 999, 2 );
        add_filter( 'woocommerce_order_needs_payment', array( $this, 'order_needs_payment' ), 999, 2 );
        add_filter( 'woocommerce_gateway_description', array( $this, 'maybe_render_payment_mode_selector' ), 10, 2 );
        add_action( 'woocommerce_blocks_loaded', array( $this, 'add_gateways_blocks_support' ));
    }

    /**
     * Check if Automatic payment gateways enabled
     *
     * @return bool 
     */
    public function auto_payment_gateways_enabled() {
        if ( is_bool( self::$auto_payment_gateways_enabled ) ) {
            return self::$auto_payment_gateways_enabled;
        }

        self::$auto_payment_gateways_enabled = 'yes' === get_option( SUMO_PP_PLUGIN_PREFIX . 'enable_automatic_payment_gateways', 'yes' ) ? true : false;
        return self::$auto_payment_gateways_enabled;
    }

    /**
     * Check if Manual payment gateways enabled
     *
     * @return bool 
     */
    public function manual_payment_gateways_enabled() {
        if ( is_bool( self::$manual_payment_gateways_enabled ) ) {
            return self::$manual_payment_gateways_enabled;
        }

        self::$manual_payment_gateways_enabled = get_option( SUMO_PP_PLUGIN_PREFIX . 'enable_manual_payment_gateways', 'yes' );
        return self::$manual_payment_gateways_enabled;
    }

    /**
     * Get mode of payment gateway
     *
     * @return string 
     */
    public function get_mode_of_payment_gateway() {
        if ( ! is_null( self::$auto_payment_gateway_mode ) ) {
            return self::$auto_payment_gateway_mode;
        }

        self::$auto_payment_gateway_mode = get_option( SUMO_PP_PLUGIN_PREFIX . 'automatic_payment_gateway_mode', 'auto-or-manual' );
        return self::$auto_payment_gateway_mode;
    }

    /**
     * Get the disabled payment gateways in checkout
     *
     * @return array 
     */
    public function get_disabled_payment_gateways() {
        if ( is_array( self::$disabled_payment_gateways ) ) {
            return self::$disabled_payment_gateways;
        }

        self::$disabled_payment_gateways = get_option( SUMO_PP_PLUGIN_PREFIX . 'disabled_payment_gateways', array() );
        return self::$disabled_payment_gateways;
    }

    /**
     * Show payment gateways when order amount is zero
     * 
     * @return bool
     */
    public function show_gateways_when_order_amt_zero() {
        if ( ! is_null( self::$show_gateways_when_order_amt_zero ) ) {
            return self::$show_gateways_when_order_amt_zero;
        }

        self::$show_gateways_when_order_amt_zero = 'yes' === get_option( SUMO_PP_PLUGIN_PREFIX . 'show_payment_gateways_when_order_amt_zero', 'no' ) ? true : false;
        return self::$show_gateways_when_order_amt_zero;
    }

    /**
     * Get payment gateways to hide when order amount is zero
     *
     * @return array 
     */
    public function get_gateways_to_hide_when_order_amt_zero() {
        if ( is_array( self::$gateways_to_hide_when_order_amt_zero ) ) {
            return self::$gateways_to_hide_when_order_amt_zero;
        }

        self::$gateways_to_hide_when_order_amt_zero = get_option( SUMO_PP_PLUGIN_PREFIX . 'gateways_to_hide_when_order_amt_zero', array() );
        return self::$gateways_to_hide_when_order_amt_zero;
    }

    /**
     * Get loaded automatic payment gateways
     *
     * @return array 
     */
    public function get_auto_payment_gateways() {
        self::$auto_payment_gateway = array( 'sumo_pp_paypal_reference_txns', 'stripe' );
        return self::$auto_payment_gateways;
    }

    /**
     * Get the Customer chosen payment mode in checkout
     * 
     * @param string $gateway_id
     * @return bool
     */
    public function get_chosen_mode_of_payment( $gateway_id ) {
        if ( $this->auto_payment_gateways_enabled() && _sumo_pp()->checkout->checkout_contains_payments() ) {
            if (
                    'force-auto' === $this->get_mode_of_payment_gateway() ||
                    ( 'auto-or-manual' === $this->get_mode_of_payment_gateway() && isset( $_REQUEST[ "sumo-pp-{$gateway_id}-auto-payment-enabled" ] ) && 'yes' === $_REQUEST[ "sumo-pp-{$gateway_id}-auto-payment-enabled" ] )
            ) {
                return 'auto';
            }
        }

        return 'manual';
    }

    /**
     * Check whether the cart/order total is zero in checkout
     *
     * @return bool
     */
    public function checkout_has_order_total_zero() {
        $order_id = _sumo_pp_get_balance_payable_order_in_pay_for_order_page();

        if ( $order_id ) {
            if ( _sumo_pp_maybe_get_order_instance( $order_id )->get_total() <= 0 ) {
                return true;
            }
        } else if ( isset( WC()->cart->total ) && WC()->cart->total <= 0 ) {
            return true;
        }

        return false;
    }

    /**
     * Get payment gateways to load in to the WC checkout
     */
    public function load_payment_gateways() {
        if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
            return;
        }

        if ( ! self::$gateways_loaded ) {
            include_once 'gateways/wc-stripe/class-sumo-pp-wc-stripe-gateway.php';
            include_once 'gateways/paypal/class-sumo-pp-paypal-reference-transaction-gateway.php';

            self::$gateways_loaded = true;
        }

        // Deprecated
        if ( empty( self::$deprecated_gateways ) ) {
            self::$deprecated_gateways[] = include_once 'deprecated/gateways/sumo-stripe/class-sumo-pp-stripe-gateway.php';
        }
    }

    /**
     * Add payment gateways awaiting to load
     * 
     * @param object $gateways
     * @return array
     */
    public function add_payment_gateways( $gateways ) {
        foreach ( $gateways as $key => $method ) {
            if ( ! self::$gateways_loaded && class_exists( 'WC_Stripe' ) && class_exists( 'SUMO_PP_WC_Stripe_UPE_Payment_Gateway_Helper' ) && ( 'WC_Stripe_UPE_Payment_Gateway' === $method || $method instanceof WC_Stripe_UPE_Payment_Gateway ) ) {
                $gateways[ $key ] = new SUMO_PP_WC_Stripe_UPE_Payment_Gateway_Helper();
            }
        }

        if ( self::$gateways_loaded ) {
            $gateways[] = 'SUMO_PP_Paypal_Reference_Transactions';
        }

        return $gateways;
    }

    /**
     * Show payment gateways for payment plans in cart when Order amount is 0
     *
     * @param bool $bool
     * @param object $cart
     * @return bool
     */
    public function need_payment_gateways( $bool, $cart ) {
        if ( $cart->total <= 0 && _sumo_pp()->checkout->checkout_contains_payments() ) {
            return $this->show_gateways_when_order_amt_zero() ? true : false;
        }

        return $bool;
    }

    /**
     * Check if the order needs payment when the checkout is $0.
     *
     * @param bool $needs_payment
     * @return bool
     */
    public function order_needs_payment( $needs_payment, $order ) {
        $order = wc_get_order( $order );

        if ( $order && $order->get_total() <= 0 && _sumo_pp_order_has_payment_data( $order ) ) {
            if ( ! is_null( WC()->cart ) ) {
                $needs_payment = $this->show_gateways_when_order_amt_zero() ? true : false;
            } else if ( $order->has_status( array( 'checkout-draft', 'pending', 'failed' ) ) ) {
                $needs_payment = true;
            }
        }

        return $needs_payment;
    }

    /**
     * Check whether specific payment gateway is needed in checkout
     * 
     * @param WC_Payment_Gateway $gateway
     * @return bool
     */
    public function need_payment_gateway( $gateway ) {
        $need = true;

        if ( _sumo_pp()->checkout->checkout_contains_payments() ) {
            // This is high priority to disable any payment gateways
            if ( in_array( $gateway->id, ( array ) $this->get_disabled_payment_gateways() ) ) {
                $need = false;
            } else if ( $this->show_gateways_when_order_amt_zero() && in_array( $gateway->id, $this->get_gateways_to_hide_when_order_amt_zero() ) && $this->checkout_has_order_total_zero() ) {
                $need = false;
            } else if ( ! $this->auto_payment_gateways_enabled() ) {
                // Do not allow automatic payment gateways
                if ( $gateway->supports( 'sumo_paymentplans' ) ) {
                    $need = false;
                }
            } else {
                // Allow only automatic payment gateways
                if ( 'force-auto' === $this->get_mode_of_payment_gateway() && ! $gateway->supports( 'sumo_paymentplans' ) ) {
                    $need = false;
                }
            }
        }

        return apply_filters( 'sumopaymentplans_need_payment_gateway', $need, $gateway );
    }

    /**
     * Handle payment gateways in checkout
     * 
     * @param array $_available_gateways
     * @return array
     */
    public function set_payment_gateways( $_available_gateways ) {
        if ( is_admin() ) {
            return $_available_gateways;
        }

        foreach ( $_available_gateways as $gateway_name => $gateway ) {
            if ( ! isset( $gateway->id ) ) {
                continue;
            }

            if ( ! $this->need_payment_gateway( $gateway ) ) {
                unset( $_available_gateways[ $gateway_name ] );
            }
        }

        return $_available_gateways;
    }

    /**
     * Maybe get the payment mode for gateway.
     * 
     * @param string $gateway_id
     * @param bool $echo
     * @return string
     */
    public function maybe_get_payment_mode_for_gateway( $gateway_id, $echo = true ) {
        if ( ! $this->auto_payment_gateways_enabled() ) {
            return '';
        }

        if ( 'auto-or-manual' !== $this->get_mode_of_payment_gateway() ) {
            return '';
        }

        if ( isset( $_GET[ 'pay_for_order' ] ) ) {
            if ( ! _sumo_pp_get_balance_payable_order_in_pay_for_order_page() ) {
                return '';
            }
        } else if ( ! _sumo_pp()->checkout->checkout_contains_payments() ) {
            return '';
        }

        if ( ! apply_filters( "sumopaymentplans_allow_payment_mode_selection_in_{$gateway_id}_payment_gateway", false ) ) {
            return '';
        }

        ob_start();
        ?>
        <p class="sumo_pp_payment_mode_selection">
            <input type="checkbox" name="sumo-pp-<?php echo esc_attr( $gateway_id ); ?>-auto-payment-enabled" value="yes"/> <?php esc_html_e( 'Enable Automatic Payments', 'sumopaymentplans' ) ?>
        </p>
        <?php
        if ( $echo ) {
            ob_end_flush();
        } else {
            return ob_get_clean();
        }
    }

    /**
     * Maybe render checkbox to select the mode of payment in automatic payment gateways by the customer
     * 
     * @param string $description
     * @param string $gateway_id
     * @return string
     */
    public function maybe_render_payment_mode_selector( $description, $gateway_id ) {
        $description .= $this->maybe_get_payment_mode_for_gateway( $gateway_id, false );
        return $description;
    }

    /**
     * Hook in Blocks integration. This action is called in a callback on plugins loaded.
     * 
     * @since 11.0.0
     */
    public function add_gateways_blocks_support() {
        if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            include_once 'gateways/paypal/class-sumo-pp-paypal-reference-transaction-gateway-blocks-support.php';

            add_action( 'woocommerce_blocks_payment_method_type_registration', array( self::instance(), 'register_gateways_blocks_support' ), 5 );
        }
    }

    /**
     * Priority is important here because this ensures this integration is registered before the WooCommerce Blocks built-in SUMO_PP_Payment_Gateways gateway registration.
     * Blocks code has a check in place to only register if SUMO payment plan paypal reference transaction gateway is not already registered.
     * 
     * @since 11.0.0
     * @param object $payment_method_registry
     */
    public function register_gateways_blocks_support( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
        if ( ! class_exists( 'SUMO_PP_Payment_Gateways' ) ) {
            return;
        }

        $container = Automattic\WooCommerce\Blocks\Package::container();
        $container->register(
                SUMO_PP_Paypal_Reference_Transactions_Blocks_Support::class,
                function() {
                    return new SUMO_PP_Paypal_Reference_Transactions_Blocks_Support();
                }
        );
        $payment_method_registry->register( $container->get( SUMO_PP_Paypal_Reference_Transactions_Blocks_Support::class ) );
    }

}
