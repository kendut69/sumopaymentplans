<?php

/**
 * Register new Payment Gateway id of Stripe.
 * 
 * @class SUMO_PP_Stripe_Gateway
 * @package Class
 */
class SUMO_PP_Stripe_Gateway extends WC_Payment_Gateway {

	const STRIPE_REQUIRES_AUTH            = 100;
	const PAYMENT_RETRY_WITH_DEFAULT_CARD = 200;

	/**
	 * Check if we need to retry with the Default card
	 *
	 * @var bool 
	 */
	public $retry_failed_payment = false;

	/**
	 * Sandbox.
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Test secret key.
	 *
	 * @var string
	 */
	public $testsecretkey;

	/**
	 * Test publishable key.
	 *
	 * @var string
	 */
	public $testpublishablekey;

	/**
	 * Live secret key.
	 *
	 * @var string
	 */
	public $livesecretkey;

	/**
	 * Live publishable key.
	 *
	 * @var string
	 */
	public $livepublishablekey;

	/**
	 * Auth email reminder.
	 *
	 * @var int
	 */
	public $pendingAuthEmailReminder;

	/**
	 * Auth period.
	 *
	 * @var int
	 */
	public $pendingAuthPeriod;

	/**
	 * SUMO_PP_Stripe_Gateway constructor.
	 */
	public function __construct() {
		$this->id                       = 'sumo_pp_stripe';
		$this->method_title             = __( 'SUMO Payment Plans - Stripe', 'sumopaymentplans' );
		$this->method_description       = __( 'Take payments from your customers using Credit/Debit card', 'sumopaymentplans' );
		$this->has_fields               = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled                  = $this->get_option( 'enabled' );
		$this->title                    = $this->get_option( 'title' );
		$this->description              = $this->get_option( 'description' );
		$this->testmode                 = 'yes' === $this->get_option( 'testmode' );
		$this->testsecretkey            = $this->get_option( 'testsecretkey' );
		$this->testpublishablekey       = $this->get_option( 'testpublishablekey' );
		$this->livesecretkey            = $this->get_option( 'livesecretkey' );
		$this->livepublishablekey       = $this->get_option( 'livepublishablekey' );
		$this->pendingAuthEmailReminder = $this->get_option( 'pendingAuthEmailReminder', '2' );
		$this->pendingAuthPeriod        = absint( $this->get_option( 'pendingAuthPeriod', '1' ) );
		$this->supports                 = array(
			'products',
			'refunds',
			'sumo_paymentplans',
		);

		include_once 'class-sumo-pp-stripe-api-request.php';
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'sumopaymentplans_before_update_payments_for_order', array( $this, 'process_initial_payment_order_success' ), 10, 2 );
		add_action( 'sumopaymentplans_process_stripe_success_response', array( $this, 'process_balance_payment_order_success' ), 10, 2 );
		add_filter( "sumopaymentplans_auto_charge_{$this->id}_balance_payment", array( $this, 'charge_balance_payment' ), 10, 3 );
		add_filter( "sumopaymentplans_{$this->id}_remind_pending_auth_times_per_day", array( $this, 'get_pending_auth_times_per_day_to_remind' ) );
		add_action( 'sumopaymentplans_stripe_requires_authentication', array( $this, 'prepare_customer_to_authorize_payment' ), 10, 2 );
		add_filter( 'sumopaymentplans_get_next_eligible_payment_failed_status', array( $this, 'prevent_payment_from_cancel' ), 99, 2 );
	}

	/**
	 * Admin Settings
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                  => array(
				'title'   => __( 'Enable/Disable', 'sumopaymentplans' ),
				'type'    => 'checkbox',
				'label'   => __( 'Stripe', 'sumopaymentplans' ),
				'default' => 'no',
			),
			'title'                    => array(
				'title'       => __( 'Title:', 'sumopaymentplans' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user see during checkout.', 'sumopaymentplans' ),
				'default'     => __( 'SUMO Payment Plans - Stripe', 'sumopaymentplans' ),
			),
			'description'              => array(
				'title'    => __( 'Description', 'sumopaymentplans' ),
				'type'     => 'textarea',
				'default'  => __( 'Pay with Stripe. You can pay with your credit card, debit card and master card   ', 'sumopaymentplans' ),
				'desc_tip' => true,
			),
			'testmode'                 => array(
				'title'       => __( 'Test Mode', 'sumopaymentplans' ),
				'type'        => 'checkbox',
				'label'       => __( 'Turn on testing', 'sumopaymentplans' ),
				'description' => __( 'Use the test mode on Stripe dashboard to verify everything works before going live.', 'sumopaymentplans' ),
				'default'     => 'no',
			),
			'livepublishablekey'       => array(
				'type'    => 'text',
				'title'   => __( 'Stripe API Live Publishable key', 'sumopaymentplans' ),
				'default' => '',
			),
			'livesecretkey'            => array(
				'type'    => 'text',
				'title'   => __( 'Stripe API Live Secret key', 'sumopaymentplans' ),
				'default' => '',
			),
			'testpublishablekey'       => array(
				'type'    => 'text',
				'title'   => __( 'Stripe API Test Publishable key', 'sumopaymentplans' ),
				'default' => '',
			),
			'testsecretkey'            => array(
				'type'    => 'text',
				'title'   => __( 'Stripe API Test Secret key', 'sumopaymentplans' ),
				'default' => '',
			),
			'autoPaymentFailure'       => array(
				'title' => __( 'Automatic Payment Failure Settings', 'sumopaymentplans' ),
				'type'  => 'title',
			),
			'SCADesc'                  => array(
				'type'        => 'title',
				'description' => __( 'Some banks require customer authentication each time during a balance payment which is not controlled by Stripe. So, even if customer has authorized for future payments of deposit/payment plan, the authorization will be declined by banks. In such case, customer has to manually process their future payments. The following options controls such scenarios.', 'sumopaymentplans' ),
			),
			'pendingAuthPeriod'        => array(
				'type'              => 'number',
				'title'             => __( 'Pending Authorization Period', 'sumopaymentplans' ),
				'default'           => '1',
				'description'       => __( 'day', 'sumopaymentplans' ),
				'desc_tip'          => __( 'This option controls how long the deposit/payment plan needs to be in "Pending Authorization" status until the customer pays for the balance payment or else it was unable to charge for the payment automatically in case of automatic payments. For example, if it is set as 2 then, the deposit/payment plan will be in "Pending Authorization" status for 2 days from the payment due date.', 'sumopaymentplans' ),
				'custom_attributes' => array(
					'min' => 0,
				),
			),
			'pendingAuthEmailReminder' => array(
				'type'              => 'number',
				'title'             => __( 'Number of Emails to send during Pending Authorization', 'sumopaymentplans' ),
				'default'           => '2',
				'description'       => __( 'times per day', 'sumopaymentplans' ),
				'desc_tip'          => __( 'This option controls the number of times the deposit/payment plan emails will be send to the customer in case of a payment failure when the deposit/payment plan in Pending Authorization status.', 'sumopaymentplans' ),
				'custom_attributes' => array(
					'min' => 0,
				),
			),
		);
	}

	/**
	 * Gets the transaction URL linked to Stripe dashboard.
	 */
	public function get_transaction_url( $order ) {
		if ( $this->testmode ) {
			$this->view_transaction_url = 'https://dashboard.stripe.com/test/payments/%s';
		} else {
			$this->view_transaction_url = 'https://dashboard.stripe.com/payments/%s';
		}

		if ( 'setup_intent' === $this->get_intentObj_from_order( $order ) ) {
			$this->view_transaction_url = '';
		}

		return parent::get_transaction_url( $order );
	}

	/**
	 * Can the order be refunded via Stripe?
	 *
	 * @param  WC_Order $order
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		return $order && $order->get_transaction_id();
	}

	/**
	 * Checks if gateway should be available to use.
	 */
	public function is_available() {
		return false;
	}

	/**
	 * Process a refund if supported.
	 */
	public function process_refund( $order_id, $amount = null, $reason = null ) {
		try {
			$order = _sumo_pp_maybe_get_order_instance( $order_id );
			if ( ! $order ) {
				throw new Exception( __( 'Refund failed: Invalid order', 'sumopaymentplans' ) );
			}

			if ( ! $this->can_refund_order( $order ) ) {
				throw new Exception( __( 'Refund failed: No transaction ID', 'sumopaymentplans' ) );
			}

			SUMO_PP_Stripe_API_Request::init( $this );

			$pi = SUMO_PP_Stripe_API_Request::request( 'retrieve_pi', array( 'id' => $this->get_intent_from_order( $order ) ) );

			if ( is_wp_error( $pi ) ) {
				throw new Exception( SUMO_PP_Stripe_API_Request::get_last_error_message() );
			}

			$charge = end( $pi->charges->data );
			$refund = SUMO_PP_Stripe_API_Request::request( 'create_refund', array(
						'amount' => $amount,
						'reason' => $reason,
						'charge' => $charge->id,
					) );

			if ( is_wp_error( $refund ) ) {
				throw new Exception( SUMO_PP_Stripe_API_Request::get_last_error_message() );
			}
		} catch ( Exception $e ) {
			if ( isset( $order ) && is_a( $order, 'WC_Order' ) ) {
				$this->log_err( SUMO_PP_Stripe_API_Request::get_last_log(), array(
					'order' => $order->get_id(),
				) );
			} else {
				$this->log_err( SUMO_PP_Stripe_API_Request::get_last_log() );
			}

			return new WP_Error( 'sumo-pp-stripe-error', $e->getMessage() );
		}
		return true;
	}

	/**
	 *  Process the given response
	 */
	public function process_response( $response, $order = false ) {
		switch ( $response->status ) {
			case 'succeeded':
				do_action( 'sumopaymentplans_process_stripe_success_response', $response, $order );

				if ( $order ) {
					if ( ! _sumo_pp_is_order_paid( $order ) ) {
						$order->payment_complete( $response->id );
					}

					if ( 'setup_intent' === $response->object ) {
						$order->add_order_note( __( 'Stripe: payment complete. Customer has approved for future payments.', 'sumopaymentplans' ) );
					} else {
						$order->add_order_note( __( 'Stripe: payment complete', 'sumopaymentplans' ) );
					}

					$order->set_transaction_id( $response->id );
					$order->save();
				}
				return 'success';
				break;
			case 'processing':
				do_action( 'sumopaymentplans_process_stripe_success_response', $response, $order );

				if ( $order ) {
					if ( ! $order->has_status( 'on-hold' ) ) {
						$order->update_status( 'on-hold' );
					}

					if ( 'setup_intent' === $response->object ) {
						/* translators: 1: Stripe charge id */
						$order->add_order_note( sprintf( __( 'Stripe: awaiting confirmation by the customer to approve for future payments: %s.', 'sumopaymentplans' ), $response->id ) );
					} else {
						/* translators: 1: Stripe charge id */
						$order->add_order_note( sprintf( __( 'Stripe: awaiting payment: %s.', 'sumopaymentplans' ), $response->id ) );
					}

					$order->set_transaction_id( $response->id );
					$order->save();
				}
				return 'success';
				break;
			case 'requires_payment_method':
			case 'requires_source': // BKWD CMPT
			case 'canceled':
				$this->log_err( $response, $order ? array( 'order' => $order->get_id() ) : array()  );

				do_action( 'sumopaymentplans_process_stripe_failed_response', $response, $order );

				if ( isset( $response->last_setup_error ) ) {
					/* translators: 1: Stripe error message */
					$message = $response->last_setup_error ? sprintf( __( 'Stripe: SCA authentication failed. Reason: %s', 'sumopaymentplans' ), $response->last_setup_error->message ) : __( 'Stripe: SCA authentication failed.', 'sumopaymentplans' );
				} else if ( isset( $response->last_payment_error ) ) {
					/* translators: 1: Stripe error message */
					$message = $response->last_payment_error ? sprintf( __( 'Stripe: SCA authentication failed. Reason: %s', 'sumopaymentplans' ), $response->last_payment_error->message ) : __( 'Stripe: SCA authentication failed.', 'sumopaymentplans' );
				} else if ( isset( $response->failure_message ) ) {
					/* translators: 1: Stripe error message */
					$message = $response->failure_message ? sprintf( __( 'Stripe: payment failed. Reason: %s', 'sumopaymentplans' ), $response->failure_message ) : __( 'Stripe: payment failed.', 'sumopaymentplans' );
				} else {
					$message = __( 'Stripe: payment failed.', 'sumopaymentplans' );
				}

				if ( $order ) {
					$order->add_order_note( $message );
					$order->save();
				}

				return $message;
				break;
		}

		$this->log_err( $response, $order ? array( 'order' => $order->get_id() ) : array()  );
		return 'failure';
	}

	/**
	 * Charge the customer automatically to pay their balance payment
	 * 
	 * @param bool $bool
	 * @param SUMO_PP_Payment $payment
	 * @param WC_Order $balance_payable_order
	 * @return bool
	 */
	public function charge_balance_payment( $bool, $payment, $balance_payable_order, $retry = false ) {
		try {

			SUMO_PP_Stripe_API_Request::init( $this );

			$this->retry_failed_payment = $retry;

			$request = array(
				'customer'       => $this->get_stripe_customer_from_payment( $payment ),
				'payment_method' => $this->get_stripe_pm_from_payment( $payment ),
			);

			if ( $this->retry_failed_payment ) {
				$customer = SUMO_PP_Stripe_API_Request::request( 'retrieve_customer', array(
							'id' => $request[ 'customer' ],
						) );

				if ( is_wp_error( $customer ) ) {
					throw new Exception( SUMO_PP_Stripe_API_Request::get_last_error_message( false ) );
				}

				if ( SUMO_PP_Stripe_API_Request::is_customer_deleted( $customer ) ) {
					/* translators: 1: Stripe customer id */
					throw new Exception( sprintf( __( 'Stripe: Couldn\'t find the customer %s', 'sumopaymentplans' ), $customer->id ) );
				}

				if ( isset( $customer->invoice_settings->default_payment_method ) && $customer->invoice_settings->default_payment_method ) {
					$request[ 'payment_method' ] = $customer->invoice_settings->default_payment_method;
				} else if ( isset( $customer->default_source ) && $customer->default_source ) { // Applicable if the source set as default in Stripe Dashboard
					$request[ 'payment_method' ] = $customer->default_source;
				} else {
					throw new Exception( __( 'Stripe: Couldn\'t find any default card from the customer.', 'sumopaymentplans' ) );
				}
			}

			$this->save_stripe_pm_to_order( $balance_payable_order, $request[ 'payment_method' ] );
			$this->save_payment_mode_to_order( $balance_payable_order, 'auto' );
			$this->save_customer_to_order( $balance_payable_order, $request[ 'customer' ] );

			$request[ 'amount' ]      = $balance_payable_order->get_total();
			$request[ 'currency' ]    = $balance_payable_order->get_currency();
			$request[ 'metadata' ]    = $this->prepare_metadata_from_order( $balance_payable_order, true, $payment );
			$request[ 'shipping' ]    = wc_shipping_enabled() ? $this->prepare_userdata_from_order( $balance_payable_order, 'shipping' ) : $this->prepare_userdata_from_order( $balance_payable_order );
			$request[ 'description' ] = $this->prepare_payment_description( $balance_payable_order );
			$request[ 'confirm' ]     = true;
			$request[ 'off_session' ] = true;

			$response = SUMO_PP_Stripe_API_Request::request( 'create_pi', $request );

			if ( is_wp_error( $response ) ) {
				if ( 'authentication_required' === SUMO_PP_Stripe_API_Request::get_last_declined_code() ) {
					throw new Exception( SUMO_PP_Stripe_API_Request::get_last_error_message( false ), self::STRIPE_REQUIRES_AUTH );
				} else {
					throw new Exception( SUMO_PP_Stripe_API_Request::get_last_error_message( false ), self::PAYMENT_RETRY_WITH_DEFAULT_CARD );
				}
			}

			$this->save_intent_to_order( $balance_payable_order, $response );

			//Process pi response.
			$result = $this->process_response( $response, $balance_payable_order );

			if ( 'success' !== $result ) {
				throw new Exception( $result, self::PAYMENT_RETRY_WITH_DEFAULT_CARD );
			}

			do_action( 'sumopaymentplans_stripe_balance_payment_successful', $payment, $balance_payable_order );
		} catch ( Exception $e ) {
			/* translators: 1: Stripe error message */
			$payment->add_payment_note( sprintf( __( 'Stripe: <b>%s</b>', 'sumopaymentplans' ), $e->getMessage() ), 'failure', __( 'Stripe Request Failed', 'sumopaymentplans' ) );
			$this->log_err( SUMO_PP_Stripe_API_Request::get_last_log(), array(
				'order'   => $balance_payable_order->get_id(),
				'payment' => $payment->get_id(),
			) );

			if ( ! $this->retry_failed_payment ) {
				$payment->add_payment_note( __( 'Stripe: Start retrying payment with the default card chosen by the customer.', 'sumopaymentplans' ), 'pending', __( 'Stripe Charging Default Card', 'sumopaymentplans' ) );

				switch ( $e->getCode() ) {
					case self::PAYMENT_RETRY_WITH_DEFAULT_CARD:
					case self::STRIPE_REQUIRES_AUTH:
						return $this->charge_balance_payment( $bool, $payment, $balance_payable_order, true );
						break;
				}
			} else {
				switch ( $e->getCode() ) {
					case self::STRIPE_REQUIRES_AUTH:
						do_action( 'sumopaymentplans_stripe_requires_authentication', $payment, $balance_payable_order );
						break;
				}
			}

			return false;
		}

		return true;
	}

	/**
	 * Save Stripe customer, payment method after initial payment order success
	 */
	public function process_initial_payment_order_success( $payments, $order ) {
		$order = wc_get_order( $order );

		if ( ! _sumo_pp_is_parent_order( $order ) || $this->id !== $order->get_payment_method() ) {
			return;
		}

		foreach ( $payments as $payment_id ) {
			$payment = _sumo_pp_get_payment( $payment_id );
			$payment->update_prop( 'payment_method', $this->id );
			$payment->update_prop( 'payment_method_title', $this->get_title() );
			$payment->set_payment_mode( $this->get_payment_mode_from_order( $order ) );
			$payment->update_prop( 'stripe_customer_id', $this->get_stripe_customer_from_order( $order ) );
			$payment->update_prop( 'stripe_payment_method', $this->get_stripe_pm_from_order( $order ) );
		}
	}

	/**
	 * Save Stripe customer, payment method after balance payment order success
	 */
	public function process_balance_payment_order_success( $response, $order ) {
		if ( ! $order || 0 === $order->get_parent_id() ) {
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
		$payment->set_payment_mode( $this->get_payment_mode_from_order( $order ) );

		if ( ! $this->retry_failed_payment ) {
			$payment->update_prop( 'stripe_customer_id', $response->customer );
			$payment->update_prop( 'stripe_payment_method', $response->payment_method );
		}
	}

	/**
	 * Prepare the customer to bring it 'OnSession' to complete their balance payment
	 */
	public function prepare_customer_to_authorize_payment( $payment, $balance_payable_order ) {
		if ( ! $this->pendingAuthPeriod || ! $payment->has_status( array( 'pending', 'in_progress' ) ) ) {
			return;
		}

		$balance_payable_order->add_meta_data( '_sumo_pp_stripe_authentication_required', 'yes', true );
		$balance_payable_order->save();

		$payment->update_status( 'pendng_auth' );
		/* translators: 1: order id */
		$payment->add_payment_note( sprintf( __( 'Payment automatically changed to Pending Authorization. Since the balance payment of Order #%s is not being paid so far.', 'sumopaymentplans' ), $balance_payable_order->get_order_number() ), 'pending', __( 'Stripe Authorization is Pending', 'sumopaymentplans' ) );

		$pending_auth_timegap = _sumo_pp_get_timestamp( "+{$this->pendingAuthPeriod} days", _sumo_pp_get_timestamp( $payment->get_prop( 'next_payment_date' ) ) );

		if ( $payment->get_remaining_installments() > 1 ) {
			$next_installment_date = _sumo_pp_get_timestamp( $payment->get_next_payment_date( $payment->get_next_of_next_installment_count() ) );

			if ( $pending_auth_timegap >= $next_installment_date ) {
				$pending_auth_timegap = $next_installment_date;
			}
		}

		$scheduler = _sumo_pp_get_job_scheduler( $payment );
		$scheduler->unset_jobs();
		remove_filter( 'sumopaymentplans_get_next_eligible_payment_failed_status', array( $this, 'prevent_payment_from_cancel' ), 99, 2 );
		$scheduler->schedule_next_eligible_payment_failed_status( $balance_payable_order->get_id(), $pending_auth_timegap );
		add_filter( 'sumopaymentplans_get_next_eligible_payment_failed_status', array( $this, 'prevent_payment_from_cancel' ), 99, 2 );
		$scheduler->schedule_reminder( $balance_payable_order->get_id(), $pending_auth_timegap, 'payment_pending_auth' );

		do_action( 'sumopaymentplans_payment_is_in_pending_authorization', $payment->id, $balance_payable_order->get_id(), 'balance-payment-order' );
	}

	/**
	 * Hold the payment untill the payment is approved by the customer and so do not cancel the payment
	 */
	public function prevent_payment_from_cancel( $next_eligible_status, $payment ) {
		if ( $payment->has_status( 'pendng_auth' ) ) {
			$next_eligible_status = '';
		}

		return $next_eligible_status;
	}

	/**
	 * Return the times per day to remind users in Pending Authorization.
	 * 
	 * @return int
	 */
	public function get_pending_auth_times_per_day_to_remind( $times_per_day ) {
		return $this->pendingAuthEmailReminder;
	}

	/**
	 * Save Stripe paymentMethod in Order
	 */
	public function save_stripe_pm_to_order( $order, $pm ) {
		$order->update_meta_data( '_sumo_pp_stripe_payment_method', isset( $pm->id ) ? $pm->id : $pm  );
		$order->save();
	}

	/**
	 * Save Stripe customer in Order
	 */
	public function save_customer_to_order( $order, $customer ) {
		$order->update_meta_data( '_sumo_pp_stripe_customer_id', isset( $customer->id ) ? $customer->id : $customer  );
		$order->save();
	}

	/**
	 * Save mode of payment in Order
	 */
	public function save_payment_mode_to_order( $order, $mode ) {
		$mode = 'auto' === $mode ? 'auto' : 'manual';
		$order->update_meta_data( '_sumo_pp_payment_mode', $mode );
		$order->save();
	}

	/**
	 * Save Stripe intent in Order
	 */
	public function save_intent_to_order( $order, $intent ) {
		if ( 'payment_intent' === $intent->object ) {
			$order->update_meta_data( '_sumo_pp_stripe_pi', $intent->id );
		} else if ( 'setup_intent' === $intent->object ) {
			$order->update_meta_data( '_sumo_pp_stripe_si', $intent->id );
		}

		$order->update_meta_data( '_sumo_pp_stripe_intentObject', $intent->object );
		$order->save();
	}

	/**
	 * Prepare userdata from order
	 * 
	 * @param string $type billing|shipping
	 */
	public function prepare_userdata_from_order( $order, $type = 'billing' ) {
		$userdata = array(
			'address' => array(
				'line1'       => $order->get_meta( "_{$type}_address_1", true ),
				'line2'       => $order->get_meta( "_{$type}_address_2", true ),
				'city'        => $order->get_meta( "_{$type}_city", true ),
				'state'       => $order->get_meta( "_{$type}_state", true ),
				'postal_code' => $order->get_meta( "_{$type}_postcode", true ),
				'country'     => $order->get_meta( "_{$type}_country", true ),
			),
			'fname'   => $order->get_meta( "_{$type}_first_name", true ),
			'lname'   => $order->get_meta( "_{$type}_last_name", true ),
			'phone'   => $order->get_meta( '_billing_phone', true ),
			'email'   => $order->get_meta( '_billing_email', true ),
		);

		if ( 'shipping' === $type && empty( $userdata[ 'fname' ] ) ) {
			$userdata[ 'fname' ] = $order->get_meta( '_billing_first_name', true );
			$userdata[ 'lname' ] = $order->get_meta( '_billing_last_name', true );
		}

		return $userdata;
	}

	/**
	 * Prepare metadata to display in Stripe.
	 * May be useful to keep track the payments/orders
	 */
	public function prepare_metadata_from_order( $order, $auto_pay = false, $payment = null ) {
		$metadata = array(
			'Order' => '#' . $order->get_id(),
		);

		if ( $payment ) {
			$metadata[ 'Payment' ] = '#' . $payment->get_payment_number();
		}

		if ( $order->get_parent_id() > 0 ) {
			$metadata[ 'Order Type' ] = 'balance';
		} else {
			$metadata[ 'Order Type' ] = 'deposit';
		}

		$metadata[ 'Payment Mode' ] = $auto_pay ? 'automatic' : 'manual';
		$metadata[ 'Site Url' ]     = esc_url( get_site_url() );
		return apply_filters( 'sumopaymentplans_stripe_metadata', $metadata, $order, $payment );
	}

	/**
	 * Prepare the description for each Stripe Payment.
	 * 
	 * @param WC_Order $order
	 * @return string
	 */
	public function prepare_payment_description( $order ) {
		/* translators: 1: blog name 2: order id */
		$description = sprintf( __( '%1$s - Order %2$s', 'sumopaymentplans' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $order->get_id() );
		return apply_filters( 'sumopaymentplans_stripe_payment_description', $description, $order );
	}

	/**
	 * Stripe error logger
	 */
	public function log_err( $log, $map = array() ) {
		if ( empty( $log ) ) {
			return;
		}

		SUMO_PP_WC_Logger::log( $log, $map );
	}

	/**
	 * Get saved Stripe intent object from Order
	 */
	public function get_intentObj_from_order( $order ) {
		return $order->get_meta( '_sumo_pp_stripe_intentObject', true );
	}

	/**
	 * Get saved Stripe intent from Order
	 */
	public function get_intent_from_order( $order ) {
		$metakey = 'setup_intent' === $this->get_intentObj_from_order( $order ) ? '_sumo_pp_stripe_si' : '_sumo_pp_stripe_pi';
		return $order->get_meta( "$metakey", true );
	}

	/**
	 * Get payment mode from Order
	 */
	public function get_payment_mode_from_order( $order ) {
		$payment_type = $order->get_meta( '_sumo_pp_payment_mode', true );
		return empty( $payment_type ) ? 'manual' : $payment_type;
	}

	/**
	 * Get saved Stripe customer ID from Order
	 */
	public function get_stripe_customer_from_order( $order ) {
		return $order->get_meta( '_sumo_pp_stripe_customer_id', true );
	}

	/**
	 * Get saved Stripe paymentMethod ID from Order
	 */
	public function get_stripe_pm_from_order( $order ) {
		return $order->get_meta( '_sumo_pp_stripe_payment_method', true );
	}

	/**
	 * Get saved Stripe customer from the user
	 * 
	 * @return string
	 */
	public function get_customer_from_user( $user_id = '' ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		return get_user_meta( $user_id, '_sumo_pp_stripe_customer_id', true );
	}

	/**
	 * Get saved Stripe customer ID from payment
	 * 
	 * @return string
	 */
	public function get_stripe_customer_from_payment( $payment ) {
		return get_post_meta( $payment->get_id(), '_stripe_customer_id', true );
	}

	/**
	 * Get saved Stripe paymentMethod ID from payment
	 * 
	 * @return string
	 */
	public function get_stripe_pm_from_payment( $payment ) {
		return get_post_meta( $payment->get_id(), '_stripe_payment_method', true );
	}
}

return new SUMO_PP_Stripe_Gateway();
