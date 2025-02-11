<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Payment_Plan_Invoice_Email
 * @package Class
 */
class SUMO_PP_Payment_Plan_Invoice_Email extends SUMO_PP_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = '_sumo_pp_payment_plan_invoice' ;
		$this->name           = 'payment_plan_invoice' ;
		$this->customer_email = true ;
		$this->title          = __( 'Payment Invoice – Payment Plan', 'sumopaymentplans' ) ;
		$this->description    = addslashes( __( 'Payment Invoice – Payment Plan will be sent to the customers when their installment payment is due for their payment plan.', 'sumopaymentplans' ) ) ;

		$this->template_html  = 'emails/sumo-pp-payment-plan-invoice.php' ;
		$this->template_plain = 'emails/plain/sumo-pp-payment-plan-invoice.php' ;

		$this->subject = __( '[{site_title}] - Invoice for {product_with_installment_no}', 'sumopaymentplans' ) ;
		$this->heading = __( 'Invoice for {product_with_installment_no}', 'sumopaymentplans' ) ;

		$this->subject_paid = $this->subject ;
		$this->heading_paid = $this->heading ;

		// Call parent constructor
		parent::__construct() ;
	}

	/**
	 * Get content args.
	 *
	 * @return array
	 */
	public function get_content_args() {
		$content_args  = parent::get_content_args() ;
		$payment_count = count( $this->payment->get_balance_paid_orders() ) + 1 ;

		/* translators: 1: payment count 2: product name */
		$content_args[ 'product_title_with_installment' ] = sprintf( __( 'Installment #%1$s of %2$s', 'sumopaymentplans' ), $payment_count, $content_args[ 'product_title' ] ) ;
		return $content_args ;
	}
}

return new SUMO_PP_Payment_Plan_Invoice_Email() ;
