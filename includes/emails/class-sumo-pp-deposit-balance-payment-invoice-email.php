<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Deposit_Balance_Payment_Invoice_Email
 * @package Class
 */
class SUMO_PP_Deposit_Balance_Payment_Invoice_Email extends SUMO_PP_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = '_sumo_pp_deposit_balance_payment_invoice' ;
		$this->name           = 'deposit_balance_payment_invoice' ;
		$this->customer_email = true ;
		$this->title          = __( 'Balance Payment Invoice - Deposit', 'sumopaymentplans' ) ;
		$this->description    = addslashes( __( 'Balance Payment Invoice - Deposit will be sent to the customers when the balance payment for the purchase has to be charged', 'sumopaymentplans' ) ) ;

		$this->template_html  = 'emails/sumo-pp-deposit-balance-payment-invoice.php' ;
		$this->template_plain = 'emails/plain/sumo-pp-deposit-balance-payment-invoice.php' ;

		$this->subject = __( '[{site_title}] - Invoice for Balance Payment of {product_name}', 'sumopaymentplans' ) ;
		$this->heading = __( 'Invoice for Balance Payment of {product_name}', 'sumopaymentplans' ) ;

		$this->subject_paid = $this->subject ;
		$this->heading_paid = $this->heading ;

		// Call parent constructor
		parent::__construct() ;
	}
}

return new SUMO_PP_Deposit_Balance_Payment_Invoice_Email() ;
