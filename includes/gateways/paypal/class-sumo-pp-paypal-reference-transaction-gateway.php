<?php
defined( 'ABSPATH' ) || exit;

/**
 * Register new Payment Gateway id of PayPal Reference Transactions.
 * 
 * @class SUMO_PP_Paypal_Reference_Transactions
 * @package Class
 */
class SUMO_PP_Paypal_Reference_Transactions extends WC_Payment_Gateway {

    /**
     * Whether or not logging is enabled
     *
     * @var bool
     */
    public static $log_enabled = true;

    /**
     * Logger instance
     *
     * @var WC_Logger
     */
    public static $log;

    /**
     * Sandbox.
     *
     * @var bool
     */
    public $sandbox;

    /**
     * API user.
     *
     * @var string
     */
    public $api_user;

    /**
     * API pwd.
     *
     * @var string
     */
    public $api_pwd;

    /**
     * API signature.
     *
     * @var string
     */
    public $api_signature;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id                 = 'sumo_pp_paypal_reference_txns';
        $this->has_fields         = true;
        $this->method_title       = __( 'SUMO Payment Plans - PayPal Reference Transactions', 'sumopaymentplans' );
        $this->method_description = __( 'SUMO Payment Plans - PayPal Reference Transactions is a part of Express Checkout that provides option to create Recurring Profile.', 'sumopaymentplans' );
        $this->supports           = array(
            'products',
            'sumo_paymentplans',
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title         = $this->get_option( 'title' );
        $this->description   = $this->get_option( 'description' );
        $this->sandbox       = 'yes' === $this->get_option( 'testmode', 'no' );
        $this->api_user      = $this->get_option( 'api_user' );
        $this->api_pwd       = $this->get_option( 'api_pwd' );
        $this->api_signature = $this->get_option( 'api_signature' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'admin_notices', array( $this, 'permission_needed_notice_to_admin' ) );
        add_filter( 'sumopaymentplans_need_payment_gateway', array( $this, 'need_payment_gateway' ), 10, 2 );
        add_action( 'woocommerce_api_' . $this->id, array( $this, 'handle_wc_api' ) );
        add_action( 'valid-paypal-standard-ipn-request', array( $this, 'process_ipn_request' ) );
        add_action( 'sumopaymentplans_before_update_payments_for_order', array( $this, 'process_initial_payment_order_success' ), 10, 2 );
        add_action( 'sumopaymentplans_process_paypal_success_response', array( $this, 'process_balance_payment_order_success' ), 10, 2 );
        add_filter( "sumopaymentplans_auto_charge_{$this->id}_balance_payment", array( $this, 'charge_balance_payment' ), 10, 3 );

        include_once 'includes/class-sumo-pp-paypal-reference-transaction-api-request.php';
    }

    /**
     * Initialize Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = include 'includes/settings-paypal.php';
    }

    /**
     * Logging method.
     *
     * @param string $message Log message.
     * @param string $level Optional. Default 'info'. Possible values:
     *                      emergency|alert|critical|error|warning|notice|info|debug.
     */
    public static function log( $message, $level = 'info' ) {
        if ( self::$log_enabled ) {
            if ( empty( self::$log ) ) {
                self::$log = wc_get_logger();
            }
            self::$log->log( $level, $message, array( 'source' => 'sumo-pp-paypal-reference-txns' ) );
        }
    }

    /**
     * Get the transaction URL.
     *
     * @param  WC_Order $order Order object.
     * @return string
     */
    public function get_transaction_url( $order ) {
        if ( $this->sandbox ) {
            $this->view_transaction_url = 'https://www.sandbox.paypal.com/activity/payment/%s';
        } else {
            $this->view_transaction_url = 'https://www.paypal.com/activity/payment/%s';
        }
        return parent::get_transaction_url( $order );
    }

    /**
     * Get the wc-api URL to redirect to
     * 
     * @param int $order_id The Order post ID
     * @return string
     */
    public function get_callback_url( $order_id ) {
        $request_url = WC()->api_request_url( $this->id );

        $url = esc_url_raw( add_query_arg( array(
            'order_id' => $order_id,
            'action'   => 'sumo_pp_create_billing_agreement',
                        ), $request_url ) );
        return $url;
    }

    /**
     * Check account for reference transaction support
     */
    public function are_reference_transactions_enabled() {
        $request = new SUMO_PP_Paypal_Reference_Transaction_API_Request( $this );
        $request->setExpressCheckout( array(
            'return_url'          => site_url(),
            'cancel_url'          => site_url(),
            'billing_description' => 'Check Reference Transaction Enabled or Not',
        ) );

        $response = $request->perform_request();
        $enabled  = $this->has_api_error( $response ) ? false : true;

        return $enabled;
    }

    /**
     * Check whether API credentials are left blank?
     * 
     * @return bool
     */
    public function are_api_credentials_empty() {
        $request = new SUMO_PP_Paypal_Reference_Transaction_API_Request( $this );
        $params  = $request->get_params();

        if ( empty( $params ) ) {
            return true;
        }

        if ( empty( $params[ 'USER' ] ) && empty( $params[ 'PWD' ] ) && empty( $params[ 'SIGNATURE' ] ) ) {
            return true;
        }

        return false;
    }

    /**
     * Add payment fields.
     */
    public function payment_fields() {
        if ( $this->supports( 'sumo_paymentplans' ) ) {
            add_filter( "sumopaymentplans_allow_payment_mode_selection_in_{$this->id}_payment_gateway", '__return_true' );
        }

        parent::payment_fields();
    }

    /**
     * Checks if response contains an API error code
     *
     * @return bool true if has API error, false otherwise
     */
    public function has_api_error( $response ) {
        if ( ! isset( $response[ 'ACK' ] ) ) {
            return true;
        }

        return ( 'Success' !== $response[ 'ACK' ] && 'SuccessWithWarning' !== $response[ 'ACK' ] );
    }

    /**
     * Error handler.
     * 
     * @param array $response
     * @throws Exception
     */
    public function maybe_throw_error( $response ) {
        if ( $this->has_api_error( $response ) ) {
            if ( isset( $response[ 'L_LONGMESSAGE0' ] ) ) {
                $error_code   = isset( $response[ 'L_ERRORCODE0' ] ) ? '#' . $response[ 'L_ERRORCODE0' ] : '';
                $long_message = $error_code . ' ' . $response[ 'L_LONGMESSAGE0' ];
            } else {
                $long_message = __( 'Payment processing failed!!', 'sumopaymentplans' );
            }

            self::log( 'PayPal Response: ' . wc_print_r( $response, true ) );

            throw new Exception( $long_message );
        }
    }

    /**
     * Notice to admin for additional permission needed from PayPal in order to support reference transaction.
     */
    public function permission_needed_notice_to_admin() {
        if ( 'yes' !== $this->enabled ) {
            return;
        }

        if ( ! isset( $_GET[ 'page' ] ) || ! isset( $_GET[ 'tab' ] ) || ! isset( $_GET[ 'section' ] ) || 'wc-settings' !== $_GET[ 'page' ] || 'checkout' !== $_GET[ 'tab' ] || $this->id !== $_GET[ 'section' ] ) {
            return;
        }

        if ( $this->are_api_credentials_empty() ) {
            return;
        }

        if ( isset( $_GET[ 'sumo_pp_check_permission_enabled' ] ) ) {
            $enabled = $this->are_reference_transactions_enabled();

            if ( $enabled ) {
                ?>
                <div class="updated notice is-dismissible">
                    <p><?php esc_html_e( 'Reference Transactions has been enabled with your API Credentials.', 'sumopaymentplans' ); ?></p>
                </div>
                <?php
            } else {
                ?>
                <div class="error is-dismissible">
                    <p>
                        <?php
                        /* translators: 1: method title */
                        printf( esc_html__( 'Reference Transactions is not enabled with your API credentials and hence %s gateway will not be displayed on the Checkout page. Kindly contact PayPal to enable Reference Transactions for your account.', 'sumopaymentplans' ), esc_html( $this->method_title ) );
                        ?>
                    </p>
                </div>
                <?php
            }

            update_option( 'sumo_pp_paypal_reference_txns_permission_enabled', $enabled ? 'yes' : ''  );
        } else {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php
                    /* translators: 1: check permission url 2: method title */
                    printf( esc_html__( 'Check here to know whether Reference Transactions can be enabled with your API Credentials %1$s. If your credentials are not enabled then, %2$s gateway will not be displayed on the Checkout page.', 'sumopaymentplans' ), '<a href="' . esc_url_raw( add_query_arg( 'sumo_pp_check_permission_enabled', '1' ) ) . '">' . esc_html__( 'Click here', 'sumopaymentplans' ) . '</a>', esc_html( $this->method_title ) );
                    ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Show payment gateway only when the Reference Transaction permission has got from PayPal.
     * 
     * @param bool $need
     * @param WC_Payment_Gateway $gateway
     * @return bool
     */
    public function need_payment_gateway( $need, $gateway ) {
        if ( $need ) {
            if ( $this->id !== $gateway->id || $this->are_api_credentials_empty() ) {
                return $need;
            }

            $permission_enabled_cache = get_option( 'sumo_pp_paypal_reference_txns_permission_enabled' );

            if ( 'yes' === $permission_enabled_cache ) {
                return $need;
            }

            if ( false === $permission_enabled_cache && $this->are_reference_transactions_enabled() ) {
                update_option( 'sumo_pp_paypal_reference_txns_permission_enabled', 'yes' );
                return $need;
            }

            return false;
        }

        return $need;
    }

    /**
     * Process the payment and return the result.
     *
     * @param  int $order_id Order ID.
     * @return array
     */
    public function process_payment( $order_id ) {

        try {
            $order                 = _sumo_pp_maybe_get_order_instance( $order_id );
            $auto_payments_enabled = _sumo_pp_order_has_payment_data( $order ) && 'auto' === _sumo_pp()->gateways->get_chosen_mode_of_payment( $this->id ) ? true : false;
            $request               = new SUMO_PP_Paypal_Reference_Transaction_API_Request( $this );

            $request->setExpressCheckout( array(
                'billing_type' => $auto_payments_enabled ? 'MerchantInitiatedBilling' : '',
                'return_url'   => $this->get_callback_url( $order_id ),
                'cancel_url'   => esc_url_raw( $order->get_cancel_order_url_raw() ),
                'custom'       => $order_id,
                'order'        => $order,
            ) );

            $response = $request->perform_request();

            $this->maybe_throw_error( $response );

            // Reduce stock levels
            wc_reduce_stock_levels( $order );

            // Remove cart
            WC()->cart->empty_cart();

            return array(
                'result'   => 'success',
                'redirect' => esc_url_raw( add_query_arg( array( 'token' => $response[ 'TOKEN' ] ), $request->token_url ) ),
            );
        } catch ( Exception $e ) {
            if ( ! empty( $e ) ) {
                wc_add_notice( $e->getMessage(), 'error' );
            }
        }

        // If we reached this point then there were errors
        return array(
            'result'   => 'failure',
            'redirect' => $this->get_return_url( $order ),
        );
    }

    /**
     * Handle WC API requests where we need to run a reference transaction API operation
     */
    public function handle_wc_api() {
        if ( ! isset( $_GET[ 'action' ] ) || ! isset( $_GET[ 'token' ] ) ) {
            return;
        }

        if ( 'sumo_pp_create_billing_agreement' !== sanitize_title( wp_unslash( $_GET[ 'action' ] ) ) ) {
            return;
        }

        try {
            $request  = new SUMO_PP_Paypal_Reference_Transaction_API_Request( $this );
            $request->getExpressCheckoutDetails( wc_clean( wp_unslash( $_GET[ 'token' ] ) ) );
            $response = $request->perform_request();
            $this->maybe_throw_error( $response );

            $order = _sumo_pp_maybe_get_order_instance( absint( $response[ 'CUSTOM' ] ) );
            if ( ! $order ) {
                throw new Exception( __( 'Unable to find order for PayPal billing agreement.', 'sumopaymentplans' ) );
            }

            if ( '1' === $response[ 'BILLINGAGREEMENTACCEPTEDSTATUS' ] ) {
                if ( $order->get_total() > 0 ) {
                    $request->doExpressCheckoutPayment( $response[ 'TOKEN' ], $order, $response[ 'PAYERID' ] );
                } else {
                    $request->createBillingAgreement( $response[ 'TOKEN' ] );
                }
            } else {
                $request->doExpressCheckoutPayment( $response[ 'TOKEN' ], $order, $response[ 'PAYERID' ] );
            }

            $response = $request->perform_request();

            $this->maybe_throw_error( $response );

            if ( ! empty( $response[ 'BILLINGAGREEMENTID' ] ) ) {
                $order->update_meta_data( '_sumo_pp_payment_mode', 'auto' );
                $order->update_meta_data( '_sumo_pp_paypal_billing_agreement_id', $response[ 'BILLINGAGREEMENTID' ] );
                $order->save();
            }

            do_action( 'sumopaymentplans_process_paypal_success_response', $response, $order );

            /* translators: 1: transaction ID */
            $order->add_order_note( sprintf( __( 'PayPal payment approved (ID: %s)', 'sumopaymentplans' ), $response[ 'PAYMENTINFO_0_TRANSACTIONID' ] ) );
            $order->payment_complete( $response[ 'PAYMENTINFO_0_TRANSACTIONID' ] );

            wp_safe_redirect( $order->get_checkout_order_received_url() );
            exit;
        } catch ( Exception $e ) {
            wc_add_notice( $e->getMessage(), 'error' );
            wp_redirect( wc_get_cart_url() );
            exit;
        }
    }

    /**
     * When a PayPal IPN messaged is received for a subscription transaction do some actions.
     */
    public function process_ipn_request( $transaction_details ) {
        if ( ! isset( $transaction_details[ 'txn_type' ] ) ) {
            return;
        }

        switch ( $transaction_details[ 'txn_type' ] ) {
            case 'mp_cancel':
                $custom = json_decode( $transaction_details[ 'custom' ] );

                if ( $custom && is_object( $custom ) ) {
                    $order = _sumo_pp_maybe_get_order_instance( absint( $custom->order_id ) );

                    if ( ! _sumo_pp_is_payment_order( $order ) ) {
                        return;
                    }

                    self::log( 'PayPal IPN Response: ' . wc_print_r( $transaction_details, true ) );

                    $payments = _sumo_pp()->query->get( array(
                        'type'       => 'sumo_pp_payments',
                        'status'     => array( '_sumo_pp_pending', '_sumo_pp_await_aprvl', '_sumo_pp_in_progress', '_sumo_pp_overdue', '_sumo_pp_await_cancl' ),
                        'meta_key'   => '_initial_payment_order_id',
                        'meta_value' => _sumo_pp_get_parent_order_id( $order ),
                            ) );

                    foreach ( $payments as $payment_id ) {
                        $payment = _sumo_pp_get_payment( $payment_id );

                        if ( 'auto' === $payment->get_payment_mode() && $this->id === $payment->get_payment_method() ) {
                            $payment->cancel_payment( array(
                                'content' => __( 'Billing agreement cancelled at PayPal. Payment is cancelled.', 'sumopaymentplans' ),
                                'status'  => 'success',
                                'message' => __( 'Payment Cancelled', 'sumopaymentplans' ),
                            ) );
                        }
                    }
                }
                break;
        }
    }

    /**
     * Save PayPal reference ID after initial payment order success
     */
    public function process_initial_payment_order_success( $payments, $order ) {
        $order = wc_get_order( $order );

        if ( ! _sumo_pp_is_parent_order( $order ) || $this->id !== $order->get_payment_method() ) {
            return;
        }

        $payment_type         = $order->get_meta( '_sumo_pp_payment_mode', true );
        $billing_agreement_id = $order->get_meta( '_sumo_pp_paypal_billing_agreement_id', true );

        foreach ( $payments as $payment_id ) {
            $payment = _sumo_pp_get_payment( $payment_id );
            $payment->update_prop( 'payment_method', $this->id );
            $payment->update_prop( 'payment_method_title', $this->get_title() );
            $payment->set_payment_mode( empty( $payment_type ) ? 'manual' : $payment_type  );
            $payment->update_prop( 'paypal_billing_agreement_id', $billing_agreement_id );
        }
    }

    /**
     * Save PayPal reference ID after balance payment order success
     */
    public function process_balance_payment_order_success( $response, $order ) {
        if ( 0 === $order->get_parent_id() ) {
            return;
        }

        $payment = _sumo_pp_get_payment( $order->get_meta( '_payment_id', true ) );
        if ( ! $payment ) {
            return;
        }

        $order->set_payment_method( $this->id );
        $order->set_payment_method_title( $this->get_title() );
        $order->save();

        $payment->update_prop( 'payment_method', $this->id );
        $payment->update_prop( 'payment_method_title', $this->get_title() );
        $payment->set_payment_mode( $order->get_meta( '_sumo_pp_payment_mode', true ) );
        $payment->update_prop( 'paypal_billing_agreement_id',  ! empty( $response[ 'BILLINGAGREEMENTID' ] ) ? $response[ 'BILLINGAGREEMENTID' ] : ''  );
    }

    /**
     * Charge the customer automatically to pay their balance payment.
     * 
     * Charge a payment against a reference token
     * 
     * @param bool $bool
     * @param SUMO_PP_Payment $payment
     * @param WC_Order $order
     * @return bool
     */
    public function charge_balance_payment( $bool, $payment, $order ) {
        $reference_id = $payment->get_prop( 'paypal_billing_agreement_id' );

        try {
            $request = new SUMO_PP_Paypal_Reference_Transaction_API_Request( $this );
            $request->doReferenceTransaction( $reference_id, $order );

            $response = $request->perform_request();

            $this->maybe_throw_error( $response );

            $order->set_payment_method( $this->id );
            $order->set_payment_method_title( $this->get_title() );
            $order->save();

            if ( in_array( $response[ 'PAYMENTSTATUS' ], array( 'Completed', 'Processed' ) ) ) {
                /* translators: 1: transaction ID */
                $order->add_order_note( sprintf( __( 'PayPal payment approved (ID: %s)', 'sumopaymentplans' ), $response[ 'TRANSACTIONID' ] ) );
                $order->payment_complete( $response[ 'TRANSACTIONID' ] );

                do_action( 'sumopaymentplans_paypal_balance_payment_successful', $payment, $order );
            }
        } catch ( Exception $e ) {
            /* translators: 1: error message */
            $payment->add_payment_note( sprintf( __( 'PayPal error: %s', 'sumopaymentplans' ), $e->getMessage() ), 'failure', __( 'PayPal Request Failed', 'sumopaymentplans' ) );
            /* translators: 1: error message */
            $order->add_order_note( sprintf( __( 'PayPal error: %s', 'sumopaymentplans' ), $e->getMessage() ) );
            return false;
        }

        return true;
    }
}
