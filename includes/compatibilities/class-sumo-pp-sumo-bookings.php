<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Handle SUMO Bookings Compatibility.
 * 
 * @class SUMO_PP_SUMOBookings
 * @package Class
 */
class SUMO_PP_SUMOBookings {

	/**
	 * Init SUMO_PP_SUMOBookings.
	 */
	public static function init() {
		add_action( 'woocommerce_before_add_to_cart_form', __CLASS__ . '::remove_default_deposit_form', 999 ) ;
		add_action( 'woocommerce_after_add_to_cart_button', __CLASS__ . '::render_deposit_form', 999 ) ;
		add_action( 'sumo_bookings_after_price_calculated', __CLASS__ . '::get_booking_data', 999, 2 ) ;
		add_filter( 'sumopaymentplans_get_product_props', __CLASS__ . '::set_booking_cost', 999, 1 ) ;
		add_action( 'sumopaymentplans_payment_is_cancelled', __CLASS__ . '::payment_is_cancelled', 999, 3 ) ;
		add_filter( 'sumopaymentplans_get_next_eligible_payment_failed_status', __CLASS__ . '::schedule_as_cancelled', 10, 2 ) ;
	}

	public static function can_add_booking_cost( $product_props ) {
		if (
				! is_null( $product_props[ 'product_id' ] ) &&
				function_exists( 'is_sumo_bookings_product' ) && is_sumo_bookings_product( $product_props[ 'product_id' ] ) &&
				function_exists( 'sumo_bookings_requires_confirmation' ) && ! sumo_bookings_requires_confirmation( $product_props[ 'product_id' ] ) &&
				'pay-in-deposit' === $product_props[ 'payment_type' ]
		) {
			if ( 'pre-defined' === $product_props[ 'deposit_type' ] ) {
				if ( 'percent-of-product-price' === $product_props[ 'deposit_price_type' ] ) {
					return true ;
				}
			} else {
				return true ;
			}
		}
		return false ;
	}

	public static function get_booking_data_from_session( $product_id = null ) {
		$booking_data = WC()->session->get( '_sumo_pp_sumo_booking_data' ) ;

		if ( isset( $booking_data[ $product_id ] ) ) {
			return $booking_data[ $product_id ] ;
		}

		return is_array( $booking_data ) ? $booking_data : array() ;
	}

	public static function maybe_clear_booking_session( $product_id ) {

		if ( ! is_callable( array( WC()->session, 'set' ) ) ) {
			return ;
		}

		$booking_cost_from_session = self::get_booking_data_from_session() ;

		unset( $booking_cost_from_session[ $product_id ] ) ;

		WC()->session->set( '_sumo_pp_sumo_booking_data', $booking_cost_from_session ) ;
	}

	public static function remove_default_deposit_form() {
		global $post ;

		if ( isset( $post->ID ) ) {
			self::maybe_clear_booking_session( $post->ID ) ;

			$product_props = _sumo_pp()->product->get_props( $post->ID ) ;

			if ( self::can_add_booking_cost( $product_props ) ) {
				remove_action( 'woocommerce_before_add_to_cart_button', array( _sumo_pp()->product, 'render_deposit_form' ) ) ;
			}
		}
	}

	public static function render_deposit_form() {
		global $post ;

		if ( isset( $post->ID ) ) {
			$product_props = _sumo_pp()->product->get_props( $post->ID ) ;

			if ( self::can_add_booking_cost( $product_props ) ) {
				echo '<span id="_sumo_pp_wc_booking_deposit_fields"></span>' ;

				_sumo_pp()->product->get_deposit_form( null, false, '_sumo_pp_wc_booking_deposit_fields', true ) ;
			}
		}
	}

	public static function get_booking_data( $booking_cost, $product_id ) {
		if ( ! is_callable( array( WC()->session, 'set' ) ) ) {
			return ;
		}

		if ( is_numeric( $product_id ) && $product_id ) {
			$product_props = _sumo_pp()->product->get_props( $product_id ) ;

			self::maybe_clear_booking_session( $product_props[ 'product_id' ] ) ;

			if ( self::can_add_booking_cost( $product_props ) && is_numeric( $booking_cost ) ) {
				$start_date = 0 ;

				if ( ! empty( $_REQUEST[ 'selected_month' ] ) ) {
					$start_date = wc_clean( wp_unslash( $_REQUEST[ 'selected_month' ] ) ) ;
				} else if ( isset( $_REQUEST[ 'selected_date' ] ) ) {
					$start_date = wc_clean( wp_unslash( $_REQUEST[ 'selected_date' ] ) ) ;
				}

				WC()->session->set( '_sumo_pp_sumo_booking_data', array(
					$product_props[ 'product_id' ] => array(
						'booking_cost'       => $booking_cost,
						'booking_start_date' => _sumo_pp_get_timestamp( $start_date ),
					),
				) ) ;
			}
		}
	}

	public static function set_booking_cost( $product_props ) {
		if ( ! is_callable( array( WC()->session, 'set' ) ) ) {
			return $product_props ;
		}

		$booking_data = self::get_booking_data_from_session( $product_props[ 'product_id' ] ) ;

		if ( self::can_add_booking_cost( $product_props ) ) {
			if ( 'before' === $product_props[ 'pay_balance_type' ] ) {
				$end_payment_before_days = absint( get_post_meta( $product_props[ 'product_id' ], '_sumo_pp_pay_balance_before_booked_date', true ) ) ;

				if ( isset( $booking_data[ 'booking_start_date' ] ) && is_numeric( $booking_data[ 'booking_start_date' ] ) ) {
					$product_props[ 'booking_payment_end_date' ] = $end_payment_before_days > 0 ? _sumo_pp_get_timestamp( "-{$end_payment_before_days} days", $booking_data[ 'booking_start_date' ] ) : $booking_data[ 'booking_start_date' ] ;
					$product_props[ 'pay_balance_before' ]       = _sumo_pp_get_date( $product_props[ 'booking_payment_end_date' ] ) ;
				} else {
					$product_props[ 'booking_payment_end_date' ] = null ;
				}
			}

			if ( isset( $booking_data[ 'booking_cost' ] ) && is_numeric( $booking_data[ 'booking_cost' ] ) ) {
				$product_props[ 'product_price' ] = floatval( $booking_data[ 'booking_cost' ] ) ;
			}
		} else {
			self::maybe_clear_booking_session( $product_props[ 'product_id' ] ) ;
		}

		return $product_props ;
	}

	public static function payment_is_cancelled( $payment_id, $order_id, $order_type ) {
		$payment = _sumo_pp_get_payment( $payment_id ) ;

		if ( 'pay-in-deposit' === $payment->get_payment_type() && 'before' === $payment->get_pay_balance_type() ) {
			$payment_item_meta = $payment->get_prop( 'item_meta' ) ;

			if ( isset( $payment_item_meta[ 'sumo_bookings' ][ 'booking_id' ] ) && function_exists( 'sumo_bookings_cancel_booking' ) ) {
				sumo_bookings_cancel_booking( $payment_item_meta[ 'sumo_bookings' ][ 'booking_id' ] ) ;
			}
		}
	}

	public static function schedule_as_cancelled( $next_eligible_status, $payment ) {
		if ( 'before' === $payment->get_pay_balance_type() ) {
			$payment_item_meta = $payment->get_prop( 'item_meta' ) ;

			if ( isset( $payment_item_meta[ 'sumo_bookings' ][ 'booking_id' ] ) ) {
				//Payment End date = Next Payment date
				$next_eligible_status = 'cancelled' ;
			}
		}

		return $next_eligible_status ;
	}
}

SUMO_PP_SUMOBookings::init() ;
