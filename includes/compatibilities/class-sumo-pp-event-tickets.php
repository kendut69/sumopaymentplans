<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Handle Payments with Event Tickets powered by Modern Tribe, Inc.
 * 
 * @class SUMO_PP_Event_Tickets
 * @package Class
 */
class SUMO_PP_Event_Tickets {

	/**
	 * Init SUMO_PP_Event_Tickets.
	 */
	public static function init() {
		add_action( 'wootickets_tickets_after_quantity_input', __CLASS__ . '::render_deposit_form', 10, 2 ) ;
	}

	public static function render_deposit_form( $ticket, $product ) {
		if ( _sumo_pp()->product->is_payment_product( $product ) ) {
			echo '<br>' ;

			_sumo_pp()->product->get_deposit_form( null, false, '', true ) ;
		}
	}
}

SUMO_PP_Event_Tickets::init() ;
