<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Payment_Plan_Completed_Email
 * @package Class
 */
class SUMO_PP_Payment_Plan_Completed_Email extends SUMO_PP_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = '_sumo_pp_payment_plan_completed' ;
		$this->name           = 'payment_plan_completed' ;
		$this->customer_email = true ;
		$this->title          = __( 'Payment Completed – Payment Plan', 'sumopaymentplans' ) ;
		$this->description    = addslashes( __( 'Payment Completed – Payment Plan will be sent to the customers when the Payment Schedule for the Payment Plan has been completed successfully.', 'sumopaymentplans' ) ) ;

		$this->template_html  = 'emails/sumo-pp-payment-plan-completed.php' ;
		$this->template_plain = 'emails/plain/sumo-pp-payment-plan-completed.php' ;

		$this->subject = __( '[{site_title}] - Payment Schedule for {product_name} has been Completed', 'sumopaymentplans' ) ;
		$this->heading = __( 'Payment Schedule for {product_name} has been Completed', 'sumopaymentplans' ) ;

		$this->subject_paid = $this->subject ;
		$this->heading_paid = $this->heading ;

		// Call parent constructor
		parent::__construct() ;
	}
}

return new SUMO_PP_Payment_Plan_Completed_Email() ;
