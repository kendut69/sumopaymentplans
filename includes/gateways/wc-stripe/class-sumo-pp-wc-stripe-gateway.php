<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Gateway_Stripe' ) ) {
    return;
}

if ( ! class_exists( 'SUMO_PP_WC_Stripe_Gateway' ) ) {

    /**
     * Handles the WC Stripe Automatic Payments
     * 
     * @class SUMO_PP_WC_Stripe_Gateway
     * @package Class
     */
    class SUMO_PP_WC_Stripe_Gateway {

        /**
         * Get the WC Stripe Gateway
         * 
         * @var WC_Gateway_Stripe 
         */
        protected static $stripe;

        const GATEWAY_ID = 'stripe';

        /**
         * Init SUMO_PP_WC_Stripe_Gateway.
         */
        public static function init() {
            add_filter( 'woocommerce_payment_gateway_supports', __CLASS__ . '::supports', 99, 3 );
            add_filter( 'wc_stripe_description', __CLASS__ . '::maybe_render_payment_mode_selector', 99, 2 );
            add_filter( 'wc_stripe_display_save_payment_method_checkbox', __CLASS__ . '::maybe_hide_save_checkbox', 99 );
            add_action( 'sumopaymentplans_before_update_payments_for_order', __CLASS__ . '::process_initial_payment_order_success', 10, 2 );
            add_action( 'woocommerce_order_status_changed', __CLASS__ . '::process_balance_payment_order_success' );
            add_filter( 'wc_stripe_payment_metadata', __CLASS__ . '::add_payment_metadata', 10, 2 );
            add_filter( 'wc_stripe_force_save_source', __CLASS__ . '::force_save_source', 99 );
            add_filter( 'wc_stripe_generate_create_intent_request', __CLASS__ . '::setup_future_usage', 99, 2 );

            add_filter( 'sumopaymentplans_auto_charge_stripe_balance_payment', __CLASS__ . '::process_installment_payment', 10, 3 );
            add_action( 'sumopaymentplans_wc_stripe_requires_authentication', __CLASS__ . '::prepare_customer_to_authorize_payment', 10, 2 );
            // Render paymentplan active payment method
            add_filter( 'sumopaymentplans_payment_method_display', __CLASS__ . '::render_active_payment_method', 10, 2 );

            // Process change payment method payment
            add_filter( 'sumopaymentplans_process_new_payment_method_via_stripe', __CLASS__ . '::process_change_payment_method', 10, 3 );
            add_action( 'sumopaymentplans_add_or_change_payment_method_before_submit', __CLASS__ . '::prepare_change_payment_method_form' );
        }

        /**
         * Get the Stripe instance.
         */
        public static function get_stripe_instance() {
            if ( is_null( self::$stripe ) && WC()->payment_gateways() ) {
                $payment_gateways = WC()->payment_gateways->payment_gateways();

                if ( isset( $payment_gateways[ 'stripe' ] ) && is_a( $payment_gateways[ 'stripe' ], 'WC_Gateway_Stripe' ) ) {
                    self::$stripe = $payment_gateways[ 'stripe' ];
                }
            }

            return self::$stripe;
        }

        /**
         * Add gateway supports.
         * 
         * @param bool $bool
         * @param string $feature
         * @param WC_Payment_Gateway $gateway
         * @return bool
         */
        public static function supports( $bool, $feature, $gateway ) {
            if ( self::GATEWAY_ID === $gateway->id && in_array( $feature, array( 'sumo_paymentplans', 'sumo_pp_add_or_change_payment_method' ) ) ) {
                $bool = true;
            }

            return $bool;
        }

        /**
         * Render checkbox to select the mode of payment in automatic payment gateways by the customer
         * 
         * @param string $description
         * @param string $gateway_id
         * @return string
         */
        public static function maybe_render_payment_mode_selector( $description, $gateway_id ) {
            add_filter( 'sumopaymentplans_allow_payment_mode_selection_in_stripe_payment_gateway', '__return_true' );

            return _sumo_pp()->gateways->maybe_render_payment_mode_selector( $description, $gateway_id );
        }

        /**
         * Checks to see if we need to hide the save checkbox field.
         * Because when cart contains a payment plans/deposit product, it will save regardless.
         */
        public static function maybe_hide_save_checkbox( $display_tokenization ) {
            if ( _sumo_pp()->gateways->auto_payment_gateways_enabled() && _sumo_pp()->checkout->checkout_contains_payments() ) {
                $display_tokenization = false;
            }

            return $display_tokenization;
        }

        /**
         * Save Stripe Source and Customer after initial payment order success
         */
        public static function process_initial_payment_order_success( $payments, $order ) {
            $order = wc_get_order( $order );

            if ( ! _sumo_pp_is_parent_order( $order ) || self::GATEWAY_ID !== $order->get_payment_method() ) {
                return;
            }

            $stripe_customer_id = $order->get_meta( '_stripe_customer_id', true );
            $stripe_source_id   = $order->get_meta( '_stripe_source_id', true );
            $payment_type       = $order->get_meta( '_sumo_pp_payment_mode', true );

            foreach ( $payments as $payment_id ) {
                $payment = _sumo_pp_get_payment( $payment_id );
                $payment->update_prop( 'payment_method', 'stripe' );
                $payment->update_prop( 'payment_method_title', self::get_stripe_instance()->get_title() );
                $payment->set_payment_mode( empty( $payment_type ) ? 'manual' : $payment_type );
                $payment->update_prop( 'stripe_customer_id', $stripe_customer_id );
                $payment->update_prop( 'stripe_source_id', $stripe_source_id );
            }
        }

        /**
         * Save Stripe Source and Customer after balance payment order success
         */
        public static function process_balance_payment_order_success( $order_id ) {
            if ( ! isset( $_REQUEST[ 'payment_method' ] ) ) {
                return;
            }

            $payment_method_id = wc_clean( wp_unslash( $_REQUEST[ 'payment_method' ] ) );
            $order             = wc_get_order( $order_id );

            if ( ! $order || self::GATEWAY_ID !== $payment_method_id || 0 === $order->get_parent_id() ) {
                return;
            }

            if ( self::GATEWAY_ID !== $order->get_payment_method() ) {
                return;
            }

            $payment = _sumo_pp_get_payment( $order->get_meta( '_payment_id', true ) );
            if ( ! $payment ) {
                return;
            }

            $stripe_customer_id = $order->get_meta( '_stripe_customer_id', true );
            $stripe_source_id   = $order->get_meta( '_stripe_source_id', true );

            $order->set_payment_method( self::GATEWAY_ID );
            $order->set_payment_method_title( self::get_stripe_instance()->get_title() );
            $order->save();

            $payment->update_prop( 'payment_method', self::GATEWAY_ID );
            $payment->update_prop( 'payment_method_title', self::get_stripe_instance()->get_title() );
            $payment->set_payment_mode( $order->get_meta( '_sumo_pp_payment_mode', true ) );
            $payment->update_prop( 'stripe_customer_id', $stripe_customer_id );
            $payment->update_prop( 'stripe_source_id', $stripe_source_id );
        }

        /**
         * Add payment metadata to Stripe.
         */
        public static function add_payment_metadata( $metadata, $order ) {
            $order = _sumo_pp_maybe_get_order_instance( $order );

            if ( _sumo_pp_order_has_payment_data( $order ) || _sumo_pp_is_payment_order( $order ) ) {
                if ( _sumo_pp_is_child_order( $order ) ) {
                    $payment = _sumo_pp_get_payment( $order->get_meta( '_payment_id', true ) );

                    if ( $payment ) {
                        $metadata[ 'sumo_payment' ] = '#' . $payment->get_payment_number();

                        if ( 'payment-plans' === $payment->get_payment_type() ) {
                            $metadata[ 'sumo_payment_plan' ] = $payment->get_plan( 'name' );
                        }
                    }

                    $metadata[ 'sumo_order_type' ] = 'balance';
                } else {
                    $metadata[ 'sumo_order_type' ] = 'deposit';
                }

                $metadata[ 'sumo_desposit_order' ] = '#' . _sumo_pp_get_parent_order_id( $order );
                $metadata[ 'sumo_payment_mode' ]   = 'automatic';
                $metadata[ 'sumo_payment_via' ]    = 'SUMO Payment Plans';
                $metadata[ 'site_url' ]            = esc_url( get_site_url() );
            }

            return $metadata;
        }

        /**
         * Check if the gateway requires auto renewals.
         * 
         * @param bool $force_save
         * @return bool
         */
        public static function force_save_source( $force_save ) {
            if ( 'auto' === _sumo_pp()->gateways->get_chosen_mode_of_payment( self::GATEWAY_ID ) ) {
                $force_save = true;
            }

            return $force_save;
        }

        /**
         * Attach payment method to Stripe Customer.
         * 
         * @param array $request
         * @param WC_Order $order
         * @return array 
         */
        public static function setup_future_usage( $request, $order ) {
            if ( _sumo_pp_order_has_payment_data( $order ) && _sumo_pp_is_parent_order( $order ) ) {
                $request[ 'setup_future_usage' ] = 'off_session';
            }

            return $request;
        }

        /**
         * Checks if a order already failed because a manual authentication is required.
         *
         * @param WC_Order $order
         * @return bool
         */
        public static function has_authentication_already_failed( $order, $payment ) {
            $existing_intent = self::get_stripe_instance()->get_intent_from_order( $order );

            if (
                    ! $existing_intent || 'requires_payment_method' !== $existing_intent->status || empty( $existing_intent->last_payment_error ) || 'authentication_required' !== $existing_intent->last_payment_error->code
            ) {
                return false;
            }

            // Make sure all emails are instantiated.
            WC_Emails::instance();

            /**
             * A payment attempt failed because SCA authentication is required.
             *
             * @param WC_Order $order The order that is being renewed.
             */
            do_action( 'sumopaymentplans_wc_stripe_requires_authentication', $payment, $order );

            // Fail the payment attempt (order would be currently pending because of retry rules).
            $charge    = end( $existing_intent->charges->data );
            $charge_id = $charge->id;
            /* translators: %s is the stripe charge Id */
            $order->update_status( 'failed', sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'woocommerce-gateway-stripe' ), $charge_id ) );
            return true;
        }

        /**
         * Force passing of Idempotency key to headers.
         * 
         * @param array $parsed_args
         * @return array
         */
        public static function force_allow_idempotency_key( $parsed_args ) {
            if (
                    isset( $parsed_args[ 'headers' ][ 'Stripe-Version' ] ) &&
                    ! isset( $parsed_args[ 'headers' ][ 'Idempotency-Key' ] ) &&
                    ! empty( $parsed_args[ 'body' ] )
            ) {
                $body = $parsed_args[ 'body' ];

                if ( isset( $body[ 'metadata' ][ 'sumo_payment_via' ] ) && 'SUMO Payment Plans' === $body[ 'metadata' ][ 'sumo_payment_via' ] ) {
                    $customer                                      = ! empty( $body[ 'customer' ] ) ? $body[ 'customer' ] : '';
                    $source                                        = ! empty( $body[ 'source' ] ) ? $body[ 'source' ] : $customer;
                    $parsed_args[ 'headers' ][ 'Idempotency-Key' ] = apply_filters( 'wc_stripe_idempotency_key', $body[ 'metadata' ][ 'order_id' ] . '-' . $source, $body );
                }
            }

            return $parsed_args;
        }

        /**
         * Charge the customer automatically to pay their balance payment.
         * 
         * @param bool $bool
         * @param SUMO_PP_Payment $payment
         * @param WC_Order $order
         * @return bool
         */
        public static function process_installment_payment( $bool, $payment, $order, $retry = false, $previous_error = false ) {

            try {
                $customer_id = $payment->get_prop( 'stripe_customer_id' );
                $source_id   = $payment->get_prop( 'stripe_source_id' );

                $order->set_payment_method( self::GATEWAY_ID );
                $order->set_payment_method_title( self::get_stripe_instance()->get_title() );
                $order->update_meta_data( '_stripe_customer_id', $customer_id );
                $order->update_meta_data( '_stripe_source_id', $source_id );
                $order->save();

                // Check for an existing intent, which is associated with the order.
                if ( self::has_authentication_already_failed( $order, $payment ) ) {
                    return false;
                }

                $prepared_source = self::get_stripe_instance()->prepare_order_source( $order );
                $source_object   = $prepared_source->source_object;

                if ( ! $prepared_source->customer ) {
                    throw new WC_Stripe_Exception( 'Failed to process installment for order ' . $order->get_id() . '. Stripe customer id is missing in the order', __( 'Customer not found', 'sumopaymentplans' ) );
                }

                /*
                 * If we're doing a retry and source is chargeable, we need to pass
                 * a different idempotency key and retry for success.
                 */
                if ( is_object( $source_object ) && empty( $source_object->error ) && self::get_stripe_instance()->need_update_idempotency_key( $source_object, $previous_error ) ) {
                    add_filter( 'wc_stripe_idempotency_key', array( self::get_stripe_instance(), 'change_idempotency_key' ), 10, 2 );
                }

                if ( $retry && ( false === $previous_error || self::get_stripe_instance()->is_no_such_source_error( $previous_error ) || self::get_stripe_instance()->is_no_linked_source_error( $previous_error ) ) && apply_filters( 'sumopaymentplans_wc_stripe_use_default_customer_source', true ) ) {
                    // Passing empty source will charge customer default.
                    $prepared_source->source = '';

                    $payment->add_payment_note( __( 'Start retrying balance payment with the default card.', 'sumopaymentplans' ), 'failure', __( 'Stripe Retry Balance Payment', 'sumopaymentplans' ) );
                }

                self::get_stripe_instance()->lock_order_payment( $order );

                add_filter( 'http_request_args', 'SUMO_PP_WC_Stripe_Gateway::force_allow_idempotency_key' );
                $response                   = self::get_stripe_instance()->create_and_confirm_intent_for_off_session( $order, $prepared_source, $order->get_total() );
                $is_authentication_required = self::get_stripe_instance()->is_authentication_required_for_payment( $response );
                remove_filter( 'http_request_args', 'SUMO_PP_WC_Stripe_Gateway::force_allow_idempotency_key' );

                if ( ! empty( $response->error ) && ! $is_authentication_required ) {
                    if ( ! $retry && self::get_stripe_instance()->is_retryable_error( $response->error ) ) {
                        return self::process_installment_payment( $bool, $payment, $order, true, $response->error );
                    }

                    $localized_messages = WC_Stripe_Helper::get_localized_messages();

                    if ( 'card_error' === $response->error->type ) {
                        $localized_message = isset( $localized_messages[ $response->error->code ] ) ? $localized_messages[ $response->error->code ] : $response->error->message;
                    } else {
                        $localized_message = isset( $localized_messages[ $response->error->type ] ) ? $localized_messages[ $response->error->type ] : $response->error->message;
                    }

                    $order->add_order_note( $localized_message );

                    throw new WC_Stripe_Exception( print_r( $response, true ), $localized_message );
                }

                if ( $is_authentication_required ) {
                    if ( ! $retry ) {
                        return self::process_installment_payment( $bool, $payment, $order, true, $response->error );
                    }

                    $charge = end( $response->error->payment_intent->charges->data );

                    /* translators: 1: Stripe charge id */
                    $payment->add_payment_note( sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'sumopaymentplans' ), $charge->id ), 'failure', __( 'Stripe Requires Authentication', 'sumopaymentplans' ) );
                    /* translators: 1: Stripe charge id */
                    $order->add_order_note( sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'sumopaymentplans' ), $charge->id ) );
                    $order->set_transaction_id( $charge->id );
                    /* translators: %s is the charge Id */
                    $order->update_status( 'failed', sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'woocommerce-gateway-stripe' ), $charge->id ) );
                    $order->save();

                    do_action( 'sumopaymentplans_wc_stripe_requires_authentication', $payment, $order );
                    return false;
                }

                // The charge was successfully captured
                do_action( 'wc_gateway_stripe_process_payment', $response, $order );

                if ( is_callable( array( self::get_stripe_instance(), 'get_latest_charge_from_intent' ) ) ) {
                    $latest_charge = self::get_stripe_instance()->get_latest_charge_from_intent( $response );

                    if ( ! empty( $latest_charge ) ) {
                        $response = $latest_charge;
                    }
                } else if ( isset( $response->charges->data ) ) {
                    $response = end( $response->charges->data );
                }

                self::get_stripe_instance()->process_response( $response, $order );

                do_action( 'sumopaymentplans_wc_stripe_balance_payment_successful', $payment, $order );
                return true;
            } catch ( WC_Stripe_Exception $e ) {
                WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() );

                do_action( 'wc_gateway_stripe_process_payment_error', $e, $order );

                if ( $e->getLocalizedMessage() ) {
                    $order->add_order_note( $e->getLocalizedMessage() );
                    /* translators: 1: Stripe localized message */
                    $payment->add_payment_note( sprintf( __( 'Stripe: <b>%s</b>', 'sumopaymentplans' ), $e->getLocalizedMessage() ), 'failure', __( 'Stripe Request Failed', 'sumopaymentplans' ) );
                }

                /* translators: error message */
                $order->update_status( 'failed' );
            }

            return false;
        }

        /**
         * Prepare the customer to bring it 'OnSession' to complete their balance payment
         */
        public static function prepare_customer_to_authorize_payment( $payment, $order ) {
            if ( $payment->has_status( 'pendng_auth' ) ) {
                return;
            }

            $order->add_meta_data( '_sumo_pp_wc_stripe_authentication_required', 'yes', true );
            $order->save();

            $pendingAuthPeriod = absint( apply_filters( 'sumopaymentplans_wc_stripe_pending_auth_period', 1, $payment, $order ) );
            $payment->update_status( 'pendng_auth' );
            /* translators: 1: order id */
            $payment->add_payment_note( sprintf( __( 'Payment automatically changed to Pending Authorization. Since the balance payment of Order #%s is not being paid so far.', 'sumopaymentplans' ), $order->get_order_number() ), 'pending', __( 'Stripe Authorization is Pending', 'sumopaymentplans' ) );

            $pending_auth_timegap = _sumo_pp_get_timestamp( "+{$pendingAuthPeriod} days", _sumo_pp_get_timestamp( $payment->get_prop( 'next_payment_date' ) ) );

            if ( $payment->get_remaining_installments() > 1 ) {
                $next_installment_date = _sumo_pp_get_timestamp( $payment->get_next_payment_date( $payment->get_next_of_next_installment_count() ) );

                if ( $pending_auth_timegap >= $next_installment_date ) {
                    $pending_auth_timegap = $next_installment_date;
                }
            }

            $scheduler = _sumo_pp_get_job_scheduler( $payment );
            $scheduler->unset_jobs();
            remove_filter( 'sumopaymentplans_get_next_eligible_payment_failed_status', __CLASS__ . '::prevent_payment_from_cancel', 99, 2 );
            $scheduler->schedule_next_eligible_payment_failed_status( $order->get_id(), $pending_auth_timegap );
            add_filter( 'sumopaymentplans_get_next_eligible_payment_failed_status', __CLASS__ . '::prevent_payment_from_cancel', 99, 2 );
            $scheduler->schedule_reminder( $order->get_id(), $pending_auth_timegap, 'payment_pending_auth' );

            do_action( 'sumopaymentplans_payment_is_in_pending_authorization', $payment->id, $order->get_id(), 'balance-payment-order' );
        }

        /**
         * Render active payment method
         * 
         * @since 10.9.0
         * @param string $payment_method_to_display Payment Method
         * @param object $payment SUMO_PP_Payment
         * @return string
         */
        public static function render_active_payment_method( $payment_method_to_display, $payment ) {
            $customer_id     = $payment->get_prop( 'stripe_customer_id' );
            $source_id       = $payment->get_prop( 'stripe_source_id' );
            $stripe_customer = new WC_Stripe_Customer();
            $stripe_customer->set_id( $customer_id );

            foreach ( $stripe_customer->get_payment_methods( 'card' ) as $source ) {

                if ( $source->id === $source_id ) {
                    $card = false;
                    if ( isset( $source->type ) && 'card' === $source->type ) {
                        $card = $source->card;
                    } elseif ( isset( $source->object ) && 'card' === $source->object ) {
                        $card = $source;
                    }

                    if ( $card ) {
                        /* translators: 1) card brand 2) last 4 digits */
                        $payment_method_to_display = sprintf( __( 'Via %1$s card ending in %2$s', 'sumopaymentplans' ), ( isset( $card->brand ) ? $card->brand : __( 'N/A', 'sumopaymentplans' ) ), $card->last4 );
                    }

                    break;
                }
            }

            return $payment_method_to_display;
        }

        /**
         * Hold the payment until the payment is approved by the customer and so do not cancel the payment
         */
        public static function prevent_payment_from_cancel( $next_eligible_status, $payment ) {
            if ( $payment->has_status( 'pendng_auth' ) ) {
                $next_eligible_status = '';
            }

            return $next_eligible_status;
        }

        /**
         * To determine proper SCA handling in "Add/Change payment method" form.
         */
        public static function prepare_change_payment_method_form() {
            echo '<input type="hidden" id="wc-stripe-change-payment-method" />';
        }

        /**
         * Process change payment method
         * 
         * @since 10.9.0
         * @param array $result
         * @param object $order WC_Order Object
         * @param object $payment Payment
         * @return Exception
         */
        public static function process_change_payment_method( $result, $order, $payment ) {
            try {
                $prepared_source = self::get_stripe_instance()->prepare_source( get_current_user_id(), true );
                $payment_method  = $prepared_source->source;

                if ( ! empty( $_POST[ 'wc-stripe-is-deferred-intent' ] ) ) {
                    $payment_method       = empty( $payment_method ) ? sanitize_text_field( wp_unslash( $_POST[ 'wc-stripe-payment-method' ] ) ) : $payment_method;
                    $payment_method_types = substr( $payment_method, 0, 7 ) === 'stripe_' ? substr( $payment_method, 7 ) : 'card';

                    $payment_information = array(
                        'payment_method'        => $payment_method,
                        'selected_payment_type' => $payment_method_types,
                        'customer'              => $prepared_source->customer,
                        'confirm'               => 'true',
                        'return_url'            => $payment->get_view_endpoint_url()
                    );

                    $setup_intent = self::get_stripe_instance()->intent_controller->create_and_confirm_setup_intent( $payment_information );
                    if ( ! empty( $setup_intent->error ) ) {
                        // Add the setup intent information to the order meta, if one was created despite the error.
                        if ( ! empty( $setup_intent->error->payment_intent ) ) {
                            self::get_stripe_instance()->save_intent_to_order( $order, $setup_intent->error->payment_intent );
                        }

                        throw new WC_Stripe_Exception(
                                        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                                        print_r( $setup_intent, true ),
                                        __( 'Sorry, we are unable to process your payment at this time. Please retry later.', 'sumopaymentplans' )
                        );
                    }
                } else {
                    self::get_stripe_instance()->maybe_disallow_prepaid_card( $prepared_source );
                    self::get_stripe_instance()->check_source( $prepared_source );
                    self::get_stripe_instance()->save_source_to_order( $order, $prepared_source );
                }

                $payment->update_prop( 'payment_method', self::GATEWAY_ID );
                $payment->set_payment_mode( 'auto' );
                $payment->update_prop( 'stripe_customer_id', $prepared_source->customer );
                $payment->update_prop( 'stripe_source_id', $payment_method );
                $order->set_payment_method( self::GATEWAY_ID );
                $order->save();

                /**
                 * New payment method processed successful.
                 * 
                 * @param array $prepared_source 
                 * @param object $order 
                 * @since 10.9.0
                 */
                do_action( 'sumopaymentplans_wc_stripe_new_payment_method_processed_successful', $prepared_source, $order );

                return array(
                    'result' => 'success',
                );
            } catch ( WC_Stripe_Exception $e ) {
                wc_add_notice( $e->getLocalizedMessage(), 'error' );
                WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() );
            }

            return $result;
        }
    }

    SUMO_PP_WC_Stripe_Gateway::init();
}

if ( class_exists( 'WC_Stripe_UPE_Payment_Gateway' ) ) {

    /**
     * WC_Payment_Gateway class wrapper.
     * 
     * @class SUMO_PP_WC_Stripe_UPE_Payment_Gateway_Helper
     */
    class SUMO_PP_WC_Stripe_UPE_Payment_Gateway_Helper extends WC_Stripe_UPE_Payment_Gateway {

        /**
         * Renders the UPE input fields needed to get the user's payment information on the checkout page
         */
        public function payment_fields() {
            parent::payment_fields();
            add_filter( 'sumopaymentplans_allow_payment_mode_selection_in_stripe_payment_gateway', '__return_true' );
            _sumo_pp()->gateways->maybe_get_payment_mode_for_gateway( $this->id );
        }

        /**
         * Checks if the setting to allow the user to save cards is enabled.
         *
         * @return bool Whether the setting to allow saved cards is enabled or not.
         */
        public function is_saved_cards_enabled() {
            if ( _sumo_pp()->checkout->checkout_contains_payments() ) {
                return false;
            }

            return parent::is_saved_cards_enabled();
        }
    }

}
