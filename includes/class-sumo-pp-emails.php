<?php

defined( 'ABSPATH' ) || exit;

/**
 * Emails class.
 */
class SUMO_PP_Emails {

	/**
	 * Email notification classes
	 *
	 * @var WC_Email[]
	 */
	protected $emails = array();

	/**
	 * Available email notification classes to load
	 * 
	 * @var WC_Email::id => WC_Email class
	 */
	protected $email_classes = array(
		'payment_schedule'                             => 'SUMO_PP_Payment_Schedule_Email',
		'payment_plan_invoice'                         => 'SUMO_PP_Payment_Plan_Invoice_Email',
		'payment_plan_auto_charge_reminder'            => 'SUMO_PP_Payment_Plan_Auto_Charge_Reminder_Email',
		'payment_plan_success'                         => 'SUMO_PP_Payment_Plan_Success_Email',
		'payment_plan_completed'                       => 'SUMO_PP_Payment_Plan_Completed_Email',
		'payment_plan_overdue'                         => 'SUMO_PP_Payment_Plan_Overdue_Email',
		'deposit_balance_payment_invoice'              => 'SUMO_PP_Deposit_Balance_Payment_Invoice_Email',
		'deposit_balance_payment_auto_charge_reminder' => 'SUMO_PP_Deposit_Balance_Payment_Auto_Charge_Reminder_Email',
		'deposit_balance_payment_completed'            => 'SUMO_PP_Deposit_Balance_Payment_Completed_Email',
		'deposit_balance_payment_overdue'              => 'SUMO_PP_Deposit_Balance_Payment_Overdue_Email',
		'payment_pending_auth'                         => 'SUMO_PP_Payment_Pending_Auth_Email',
		'payment_awaiting_cancel'                      => 'SUMO_PP_Payment_Awaiting_Cancel_Email',
		'payment_cancelled'                            => 'SUMO_PP_Payment_Cancelled_Email',
	);

	/**
	 * The single instance of the class
	 *
	 * @var SUMO_PP_Emails
	 */
	protected static $_instance = null;

	/**
	 * Main SUMO_PP_Emails Instance.
	 * Ensures only one instance of SUMO_PP_Emails is loaded or can be loaded.
	 * 
	 * @return SUMO_PP_Emails Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Init the email class hooks in all emails that can be sent.
	 */
	public function init() {
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_classes' ) );
		add_filter( 'woocommerce_template_directory', array( $this, 'set_template_directory' ), 10, 2 );
		add_filter( 'woocommerce_template_path', array( $this, 'set_template_path' ) );
		add_action( 'admin_init', array( $this, 'hide_plain_text_template' ) );
		add_filter( 'woocommerce_email_enabled_new_order', array( $this, 'wc_email_handler' ), 99, 2 );
		add_filter( 'woocommerce_email_enabled_customer_completed_order', array( $this, 'wc_email_handler' ), 99, 2 );
		add_filter( 'woocommerce_email_enabled_customer_processing_order', array( $this, 'wc_email_handler' ), 99, 2 );
		add_action( 'sumopaymentplans_email_installments_table', array( $this, 'payment_installments_details' ), 10, 4 );
	}

	/**
	 * Load our email classes.
	 */
	public function add_email_classes( $emails ) {
		if ( ! empty( $this->emails ) ) {
			return $emails + $this->emails;
		}

		// Include email classes.
		include_once 'abstracts/abstract-sumo-pp-email.php';

		foreach ( $this->email_classes as $id => $class ) {
			$file_name = 'class-' . strtolower( str_replace( '_', '-', $class ) );
			$path      = SUMO_PP_PLUGIN_DIR . "includes/emails/{$file_name}.php";

			if ( is_readable( $path ) ) {
				$this->emails[ $class ] = include $path ;
			}
		}

		return $emails + $this->emails;
	}

	/**
	 * Hide Template - Plain text
	 */
	public function hide_plain_text_template() {
		$this->load_mailer();

		if ( isset( $_GET[ 'section' ] ) && in_array( $_GET[ 'section' ], array_map( 'strtolower', array_keys( $this->emails ) ) ) ) {
			echo '<style>div.template_plain{display:none;}</style>';
		}
	}

	/**
	 * Check if we need to send WC emails
	 */
	public function wc_email_handler( $bool, $order ) {
		$order                    = _sumo_pp_maybe_get_order_instance( $order );
		$disabled_wc_order_emails = get_option( '_sumo_pp_disabled_wc_order_emails', array() );

		if ( ! empty( $disabled_wc_order_emails ) && ( _sumo_pp_is_payment_order( $order ) || _sumo_pp_order_has_payment_data( $order ) ) ) {
			if ( 'woocommerce_email_enabled_new_order' === current_filter() ) {
				if ( in_array( 'new', $disabled_wc_order_emails ) ) {
					return false;
				}
			} else if ( $order->has_status( $disabled_wc_order_emails ) ) {
				return false;
			}
		}

		return $bool;
	}

	/**
	 * Set our email templates directory.
	 * 
	 * @return string
	 */
	public function set_template_directory( $template_directory, $template ) {
		$templates = array_map( array( $this, 'get_template_name' ), array_keys( $this->email_classes ) );

		foreach ( $templates as $name ) {
			if ( in_array( $template, array(
						"emails/sumo-pp-{$name}.php",
						"emails/plain/sumo-pp-{$name}.php",
					) )
			) {
				return untrailingslashit( SUMO_PP_PLUGIN_BASENAME_DIR );
			}
		}

		return $template_directory;
	}

	/**
	 * Set our template path.
	 *
	 * @return string
	 */
	public function set_template_path( $path ) {
		if ( isset( $_REQUEST[ 'template_html_code' ] ) || isset( $_REQUEST[ 'template_plain_code' ] ) ) {
			if ( isset( $_GET[ 'section' ] ) && in_array( $_GET[ 'section' ], array_map( 'strtolower', array_values( $this->email_classes ) ) ) ) {
				$path = SUMO_PP_PLUGIN_BASENAME_DIR;
			}
		}

		return $path;
	}

	/**
	 * Get the template name from email ID
	 */
	public function get_template_name( $id ) {
		return str_replace( '_', '-', $id );
	}

	/**
	 * Load WC Mailer.
	 */
	public function load_mailer() {
		WC()->mailer();
	}

	/**
	 * Are emails available
	 *
	 * @return WC_Email class
	 */
	public function available() {
		$this->load_mailer();

		return ! empty( $this->emails ) ? true : false;
	}

	/**
	 * Return the email class
	 *
	 * @return WC_Email class
	 */
	public function get_email_class( $id ) {
		$id = strtolower( $id );

		if ( false !== stripos( $id, '_sumo_pp_' ) ) {
			$id = ltrim( $id, '_sumo_pp_' );
		}

		return isset( $this->email_classes[ $id ] ) ? $this->email_classes[ $id ] : null;
	}

	/**
	 * Return the emails
	 *
	 * @return WC_Email[]
	 */
	public function get_emails() {
		$this->load_mailer();

		return $this->emails;
	}

	/**
	 * Return the email
	 *
	 * @return WC_Email
	 */
	public function get_email( $id ) {
		$this->load_mailer();
		$class = $this->get_email_class( $id );

		return isset( $this->emails[ $class ] ) ? $this->emails[ $class ] : null;
	}

	/**
	 * Send the email.
	 *
	 * @param WC_Email::id $id
	 * @param WC_Order::id $order_id
	 * @param SUMO_PP_Payment $payment
	 * @param bool $manual Is manually sending ?
	 * @return bool
	 */
	public function send( $id, $order_id, $payment = false, $manual = false ) {
		$email = $this->get_email( $id );

		if ( is_null( $email ) ) {
			return false;
		}

		if ( $payment ) {
			$payment->set_email_sending_flag();
			$is_email_sent = $email->trigger( $order_id, $payment );

			if ( $is_email_sent && $email->is_customer_email() ) {
				/* translators: 1: order id */
				$text = $manual ? ' ' : sprintf( __( ' for an Order #%s ', 'sumopaymentplans' ), $order_id );
				$payment->add_payment_note(
						/* translators: 1: email title 2: concat text 3: recipients */
						sprintf( __( '%1$s email is created%2$sand has been sent to %3$s.', 'sumopaymentplans' ), $email->title, $text, $email->get_recipient() )
						, 'success',
						/* translators: 1: email title */
						sprintf( __( '%s Email Sent', 'sumopaymentplans' ), $email->title )
				);
			}

			$payment->set_email_sent_flag();
		} else {
			$is_email_sent = $email->trigger( $order_id );
		}

		return $is_email_sent;
	}

	/**
	 * Get the payment installments details.
	 * 
	 * @param SUMO_PP_Payment $payment
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 * @param WC_Email $email 
	 */
	public function payment_installments_details( $payment, $sent_to_admin = false, $plain_text = false, $email = '' ) {
		$columns = sumopp_get_email_payment_installments_columns();

		if ( 'payment_schedule' === $email->name ) {
			unset( $columns[ 'installment-modified-expected-payment-date' ], $columns[ 'installment-actual-payment-date' ], $columns[ 'installment-order-number' ] );
		}

		if ( ! $payment->is_expected_payment_dates_modified() || $payment->has_status( 'completed' ) ) {
			unset( $columns[ 'installment-modified-expected-payment-date' ] );
		}

		_sumo_pp_get_template( 'emails/email-payment-installments.php', array(
			'payment'                 => $payment,
			'actual_payments_date'    => $payment->get_prop( 'actual_payments_date' ),
			'scheduled_payments_date' => $payment->get_prop( 'scheduled_payments_date' ),
			'modified_payment_dates'  => $payment->get_prop( 'modified_expected_payment_dates' ),
			'balance_paid_orders'     => $payment->get_balance_paid_orders(),
			'initial_payment_order'   => _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() ),
			'columns'                 => $columns,
			'sent_to_admin'           => $sent_to_admin,
			'plain_text'              => $plain_text,
			'email'                   => $email,
		) );
	}
}
