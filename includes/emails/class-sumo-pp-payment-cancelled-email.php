<?php

/**
 * Payment Cancelled - Email.
 * 
 * @class SUMO_PP_Payment_Cancelled_Email
 * @package Class
 */
class SUMO_PP_Payment_Cancelled_Email extends SUMO_PP_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = '_sumo_pp_payment_cancelled' ;
		$this->name           = 'payment_cancelled' ;
		$this->customer_email = true ;
		$this->title          = __( 'Payment Cancelled', 'sumopaymentplans' ) ;
		$this->description    = addslashes( __( 'Payment Cancelled will be sent to the customers when the user has not paid their balance payments within the due date.', 'sumopaymentplans' ) ) ;

		$this->template_html  = 'emails/sumo-pp-payment-cancelled.php' ;
		$this->template_plain = 'emails/plain/sumo-pp-payment-cancelled.php' ;

		$this->subject = __( '[{site_title}] - Payment Cancelled for {product_name}', 'sumopaymentplans' ) ;
		$this->heading = __( 'Payment Cancelled for {product_name}', 'sumopaymentplans' ) ;

		$this->subject_paid = $this->subject ;
		$this->heading_paid = $this->heading ;

		// Call parent constructor
		parent::__construct() ;
	}
}

return new SUMO_PP_Payment_Cancelled_Email() ;
