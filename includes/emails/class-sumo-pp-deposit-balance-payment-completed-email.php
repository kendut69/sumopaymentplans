<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Deposit_Balance_Payment_Completed_Email
 * @package Class
 */
class SUMO_PP_Deposit_Balance_Payment_Completed_Email extends SUMO_PP_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = '_sumo_pp_deposit_balance_payment_completed' ;
		$this->name           = 'deposit_balance_payment_completed' ;
		$this->customer_email = true ;
		$this->title          = __( 'Balance Payment Completed - Deposit', 'sumopaymentplans' ) ;
		$this->description    = addslashes( __( 'Balance Payment Completed - Deposit will be sent to the customers when the balance payment for the product purchase has been completed successfully', 'sumopaymentplans' ) ) ;

		$this->template_html  = 'emails/sumo-pp-deposit-balance-payment-completed.php' ;
		$this->template_plain = 'emails/plain/sumo-pp-deposit-balance-payment-completed.php' ;

		$this->subject = __( '[{site_title}] - Balance Payment Completed for {product_name}', 'sumopaymentplans' ) ;
		$this->heading = __( 'Balance Payment Completed for {product_name}', 'sumopaymentplans' ) ;

		$this->subject_paid = $this->subject ;
		$this->heading_paid = $this->heading ;

		// Call parent constructor
		parent::__construct() ;
	}
}

return new SUMO_PP_Deposit_Balance_Payment_Completed_Email() ;
