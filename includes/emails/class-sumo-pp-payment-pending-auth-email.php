<?php

/**
 * Payment Pending Authorization - Email.
 * 
 * @class SUMO_PP_Payment_Pending_Auth_Email
 * @package Class
 */
class SUMO_PP_Payment_Pending_Auth_Email extends SUMO_PP_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = '_sumo_pp_payment_pending_auth' ;
		$this->name           = 'payment_pending_auth' ;
		$this->customer_email = true ;
		$this->title          = __( 'Payment Pending Authorization', 'sumopaymentplans' ) ;
		$this->description    = addslashes( __( 'Payment Pending Authorization Email notification will be sent to the customer when authorized card is declined by the bank.', 'sumopaymentplans' ) ) ;

		$this->template_html  = 'emails/sumo-pp-payment-pending-auth.php' ;
		$this->template_plain = 'emails/plain/sumo-pp-payment-pending-auth.php' ;

		$this->subject = __( '[{site_title}] - Payment Pending Authorization for Payment #{payment_no}', 'sumopaymentplans' ) ;
		$this->heading = __( 'Payment Pending Authorization for Payment #{payment_no}', 'sumopaymentplans' ) ;

		$this->subject_paid = $this->subject ;
		$this->heading_paid = $this->heading ;

		// Call parent constuctor
		parent::__construct() ;
	}

	/**
	 * Get content args.
	 *
	 * @return array
	 */
	public function get_content_args() {
		$content_args = parent::get_content_args() ;

		$next_action_on     = $this->scheduler->get_next_scheduled_job( 'notify_overdue' ) ;
		$next_action_status = 'overdue' ;

		if ( ! $next_action_on ) {
			$next_action_on     = $this->scheduler->get_next_scheduled_job( 'notify_awaiting_cancel' ) ;
			$next_action_status = 'cancelled' ;
		}

		if ( ! $next_action_on ) {
			$next_action_on     = $this->scheduler->get_next_scheduled_job( 'notify_cancelled' ) ;
			$next_action_status = 'cancelled' ;
		}

		if ( $next_action_on ) {
			$content_args[ 'next_action_on' ]     = _sumo_pp_get_date_to_display( $next_action_on, 'email' ) ;
			$content_args[ 'next_action_status' ] = _sumo_pp_get_payment_status_name( $next_action_status ) ;
		}
		return $content_args ;
	}
}

return new SUMO_PP_Payment_Pending_Auth_Email() ;
