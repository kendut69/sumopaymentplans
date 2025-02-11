<?php

/**
 * PayPal Reference Transaction API Request Class
 * 
 * @class       SUMO_PP_Paypal_Reference_Transaction_API_Request
 * @package    Class
 */
class SUMO_PP_Paypal_Reference_Transaction_API_Request {

    /**
     * PayPal Reference Transactions gateway
     *
     * @var object
     */
    public $gateway;

    /**
     * Sandbox
     *
     * @var bool
     */
    public $sandbox = false;

    /**
     * Endpoint URL
     *
     * @var string
     */
    public $endpoint = '';

    /**
     * Token URL
     *
     * @var string
     */
    public $token_url = '';

    /**
     * Request parameters
     * 
     * @var array 
     */
    private $parameters = array();

    /**
     * NVP API version
     */
    const VERSION = '86';

    /**
     * Construct an PayPal Reference Transaction request object
     *
     * @param SUMO_PP_Paypal_Reference_Transactions $gateway
     */
    public function __construct( SUMO_PP_Paypal_Reference_Transactions $gateway ) {
        $this->gateway = $gateway;
        $this->sandbox = $this->gateway->sandbox;

        if ( $this->sandbox ) {
            $this->endpoint  = 'https://api-3t.sandbox.paypal.com/nvp';
            $this->token_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout';
        } else {
            $this->endpoint  = 'https://api-3t.paypal.com/nvp';
            $this->token_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout';
        }

        $this->add_params( array(
            'USER'      => $this->sandbox ? $this->gateway->get_option( 'sandbox_api_username' ) : $this->gateway->get_option( 'api_username' ),
            'PWD'       => $this->sandbox ? $this->gateway->get_option( 'sandbox_api_password' ) : $this->gateway->get_option( 'api_password' ),
            'SIGNATURE' => $this->sandbox ? $this->gateway->get_option( 'sandbox_api_signature' ) : $this->gateway->get_option( 'api_signature' ),
            'VERSION'   => self::VERSION,
        ) );
    }

    /**
     * Sets up the SetExpressCheckout transaction
     * 
     * @param array $args
     */
    public function setExpressCheckout( $args ) {
        // translators: placeholder is blogname
        $default_description = sprintf( _x( 'Orders with %s', 'data sent to paypal', 'sumopaymentplans' ), get_bloginfo( 'name' ) );
        $args                = wp_parse_args( $args, array(
            'billing_type'        => 'MerchantInitiatedBilling',
            'currency'            => get_woocommerce_currency(),
            // translators: placeholder is for blog name
            'billing_description' => apply_filters( 'sumopaymentplans_paypal_billing_agreement_description', $default_description, $args ),
            'maximum_amount'      => null,
            'no_shipping'         => 1,
            'landing_page'        => 'login',
            'custom'              => '',
            'order'               => false,
                ) );

        if ( in_array( $args[ 'billing_type' ], array( 'MerchantInitiatedBilling', 'MerchantInitiatedBillingSingleAgreement' ) ) ) {
            $this->add_param( 'L_BILLINGTYPE0', $args[ 'billing_type' ] );
        }

        $this->add_params( array(
            'METHOD'                         => 'SetExpressCheckout',
            'L_BILLINGAGREEMENTDESCRIPTION0' => $this->get_paypal_item_name( $args[ 'billing_description' ] ),
            'L_BILLINGAGREEMENTCUSTOM0'      => $args[ 'custom' ],
            'RETURNURL'                      => $args[ 'return_url' ],
            'CANCELURL'                      => $args[ 'cancel_url' ],
            'LANDINGPAGE'                    => ( 'login' == $args[ 'landing_page' ] ) ? 'Login' : 'Billing',
            'NOSHIPPING'                     => $args[ 'no_shipping' ],
            'MAXAMT'                         => $args[ 'maximum_amount' ],
        ) );

        if ( is_a( $args[ 'order' ], 'WC_Order' ) ) {
            $this->add_param( 'PAYMENTREQUESTID', $args[ 'order' ]->get_id() );

            if ( $args[ 'order' ]->get_total() <= 0 ) {
                $this->add_params( array(
                    'PAYMENTREQUEST_0_AMT'           => 0,
                    'PAYMENTREQUEST_0_ITEMAMT'       => 0,
                    'PAYMENTREQUEST_0_SHIPPINGAMT'   => 0,
                    'PAYMENTREQUEST_0_TAXAMT'        => 0,
                    'PAYMENTREQUEST_0_CURRENCYCODE'  => $args[ 'currency' ],
                    'PAYMENTREQUEST_0_CUSTOM'        => $args[ 'custom' ],
                    'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
                ) );
            } else {
                $this->add_payment_request_params( $args[ 'order' ] );
            }
        }
    }

    /**
     * Sets up the DoExpressCheckoutPayment
     * 
     * @param string $token PayPal Express Checkout token returned by SetExpressCheckout operation
     * @param WC_Order $order order object
     * @param string $payer_id
     */
    public function doExpressCheckoutPayment( $token, WC_Order $order, $payer_id ) {
        $this->add_params( array(
            'METHOD'  => 'DoExpressCheckoutPayment',
            'TOKEN'   => $token,
            'PAYERID' => $payer_id,
        ) );

        $this->add_payment_request_params( $order );
    }

    /**
     * Charge a payment against a reference token. Processes a payment from a buyer's account, which is identified by a previous transaction.
     * 
     * @param string $reference_id
     * @param WC_Order $order
     */
    public function doReferenceTransaction( $reference_id, $order ) {
        $this->add_params( array(
            'METHOD'        => 'DoReferenceTransaction',
            'REFERENCEID'   => $reference_id,
            'PAYMENTACTION' => 'Sale',
        ) );

        $this->add_payment_request_params( $order );
    }

    /**
     * Create a billing agreement, required when a subscription sign-up has no initial payment
     * 
     * @param string $token
     */
    public function createBillingAgreement( $token ) {
        $this->add_params( array(
            'METHOD' => 'CreateBillingAgreement',
            'TOKEN'  => $token,
        ) );
    }

    /**
     * Get info about the buyer & transaction from PayPal
     * 
     * @param string $token
     */
    public function getExpressCheckoutDetails( $token ) {
        $this->add_params( array(
            'METHOD' => 'GetExpressCheckoutDetails',
            'TOKEN'  => $token,
        ) );
    }

    /**
     * Get info about the billing agreement
     * 
     * @param string $reference_id
     */
    public function getBillingAgreementDetails( $reference_id ) {
        $this->add_params( array(
            'METHOD'      => 'BillAgreementUpdate',
            'REFERENCEID' => $reference_id,
        ) );
    }

    /**
     * Cancel the billing agreement.
     * 
     * @param string $reference_id
     */
    public function cancelBillingAgreement( $reference_id ) {
        $this->add_params( array(
            'METHOD'                 => 'BillAgreementUpdate',
            'REFERENCEID'            => $reference_id,
            'BILLINGAGREEMENTSTATUS' => 'Canceled',
        ) );
    }

    /**
     * Add a parameter
     *
     * @param string $key
     * @param string|int $value
     */
    private function add_param( $key, $value ) {
        $this->parameters[ $key ] = $value;
    }

    /**
     * Add multiple parameters
     *
     * @param array $params
     */
    private function add_params( array $params ) {
        foreach ( $params as $key => $value ) {
            $this->add_param( $key, $value );
        }
    }

    /**
     * Returns the array of request parameters.
     *
     * @return array
     */
    public function get_params() {
        $this->parameters = apply_filters( 'sumopaymentplans_paypal_reference_transaction_request_params', $this->parameters, $this );

        // validate parameters
        foreach ( $this->parameters as $key => $value ) {
            // remove unused params
            if ( '' === $value || is_null( $value ) ) {
                unset( $this->parameters[ $key ] );
            }
        }

        return $this->parameters;
    }

    /**
     * Limit the length of item names to be within the allowed 127 character range.
     *
     * @param  string $item_name
     * @return string
     */
    private function get_paypal_item_name( $item_name ) {
        if ( ! $item_name ) {
            return '';
        }

        $item_name = html_entity_decode( $item_name );

        if ( strlen( $item_name ) > 127 ) {
            $item_name = substr( $item_name, 0, 124 ) . '...';
        }

        if ( ! $item_name ) {
            return '';
        }

        return html_entity_decode( $item_name, ENT_NOQUOTES, 'UTF-8' );
    }

    /**
     * Return the item description.
     * 
     * @param array $item
     * @param WC_Product $product
     * @return string
     */
    private function get_item_description( $item, $product ) {
        $item_desc = array();

        //      foreach ( $item->get_formatted_meta_data() as $meta ) {
        //          $item_desc[] = sprintf( '%s: %s', $meta->display_key, $meta->display_value ) ;
        //      }

        if ( ! empty( $item_desc ) ) {
            $item_desc = implode( ', ', $item_desc );
        } else {
            /* translators: 1: product SKU */
            $item_desc = $product->get_sku() ? sprintf( __( 'SKU: %s', 'sumopaymentplans' ), $product->get_sku() ) : null;
        }

        return $this->get_paypal_item_name( $item_desc );
    }

    /**
     * Set up the payment details.
     * 
     * @param WC_Order $order order object
     */
    private function add_payment_request_params( WC_Order $order ) {
        $order_subtotal = 0;
        $item_count     = 0;
        $order_items    = array();

        // Add line items
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = new WC_Product( $item[ 'product_id' ] );

            $order_items[] = array(
                'NAME'    => $this->get_paypal_item_name( $product->get_title() ),
                'DESC'    => $this->get_item_description( $item, $product ),
                'AMT'     => $this->round( $order->get_item_subtotal( $item ) ),
                'QTY'     => ( ! empty( $item[ 'qty' ] ) ) ? absint( $item[ 'qty' ] ) : 1,
                'ITEMURL' => $product->get_permalink(),
            );

            $order_subtotal += $order->get_line_total( $item );
        }

        // Add fees
        foreach ( $order->get_fees() as $fee ) {
            $order_items[] = array(
                'NAME' => $this->get_paypal_item_name( $fee[ 'name' ] ),
                'AMT'  => $this->round( $fee[ 'line_total' ] ),
                'QTY'  => 1,
            );

            $order_subtotal += $order->get_line_total( $fee );
        }

        // Add discounts
        if ( $order->get_total_discount() > 0 ) {
            $order_items[] = array(
                'NAME' => __( 'Total Discount', 'sumopaymentplans' ),
                'QTY'  => 1,
                'AMT'  => - $this->round( $order->get_total_discount() ),
            );
        }

        // Add individual order items parameters
        foreach ( $order_items as $item ) {
            foreach ( $item as $key => $value ) {
                $this->add_param( "L_PAYMENTREQUEST_0_{$key}{$item_count}", $value );
            }

            $item_count ++;
        }

        // Add order level parameters
        $payment_params = array(
            'AMT'           => $this->round( $order->get_total() ),
            'CURRENCYCODE'  => $order->get_currency(),
            'ITEMAMT'       => $this->round( $order_subtotal ),
            'TAXAMT'        => $this->round( $order->get_total_tax() ),
            'SHIPPINGAMT'   => $this->round( $order->get_total_shipping() ),
            'PAYMENTACTION' => 'Sale',
            'CUSTOM'        => $order->get_id(),
        );

        foreach ( $payment_params as $key => $value ) {
            $this->add_param( "PAYMENTREQUEST_0_{$key}", $value );
        }

        $this->add_params( array(
            'AMT'          => $this->round( $order->get_total() ),
            'CURRENCYCODE' => $order->get_currency(),
        ) );
    }

    /**
     * Round a float.
     * 
     * @param float $number
     * @param int $precision
     * @return float
     */
    private function round( $number, $precision = 2 ) {
        return round( ( float ) $number, $precision );
    }

    /**
     * Perform the request and return the parsed response.
     * 
     * @param array $headers
     * @return array
     */
    public function perform_request( $headers = array() ) {
        $ch     = curl_init();
        $header = array();

        curl_setopt( $ch, CURLOPT_URL, $this->endpoint );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSLVERSION, 6 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $this->get_params() ) );

        if ( ! empty( $headers ) ) {
            foreach ( $headers as $name => $value ) {
                $header[] = "{$name}: $value";
            }

            curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
        } else {
            curl_setopt( $ch, CURLOPT_HEADER, false );
        }

        $response = curl_exec( $ch );

        curl_close( $ch );

        parse_str( $response, $parsed_response );

        return $parsed_response;
    }
}
