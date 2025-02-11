<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Handle YITH WooCommerce Bookings Compatibility.
 * 
 * @class SUMO_PP_YITH_WC_Bookings
 * @package Class
 */
class SUMO_PP_YITH_WC_Bookings {

	/**
	 * Init SUMO_PP_YITH_WC_Bookings.
	 */
	public static function init() {
		add_action( 'woocommerce_before_add_to_cart_form', __CLASS__ . '::remove_default_deposit_form', 999 ) ;
		add_action( 'woocommerce_after_add_to_cart_button', __CLASS__ . '::render_deposit_form', 999 ) ;
		add_filter( 'yith_wcbk_product_form_get_booking_data', __CLASS__ . '::get_booking_cost', 999, 4 ) ;
		add_filter( 'sumopaymentplans_get_product_props', __CLASS__ . '::set_booking_cost', 999, 1 ) ;
		add_filter( 'sumopaymentplans_add_cart_item_data', __CLASS__ . '::add_booking_cost', 999 ) ;
	}

	public static function can_add_booking_cost( $product_props ) {
		if ( 'booking' === $product_props[ 'product_type' ] && 'pay-in-deposit' === $product_props[ 'payment_type' ] ) {
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

	public static function get_booking_cost_from_session( $product_id = null ) {
		$booking_cost = WC()->session->get( '_sumo_pp_yith_wc_booking_cost' ) ;

		if ( isset( $booking_cost[ $product_id ] ) ) {
			return $booking_cost[ $product_id ] ;
		}

		return is_array( $booking_cost ) ? $booking_cost : array() ;
	}

	public static function maybe_clear_booking_session( $product_id ) {
		if ( ! is_callable( array( WC()->session, 'set' ) ) ) {
			return ;
		}

		$booking_cost_from_session = self::get_booking_cost_from_session() ;

		unset( $booking_cost_from_session[ $product_id ] ) ;

		WC()->session->set( '_sumo_pp_yith_wc_booking_cost', $booking_cost_from_session ) ;
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

	public static function get_booking_cost( $booking_data, $product, $bookable_args, $request ) {
		if ( ! is_callable( array( WC()->session, 'set' ) ) || ! $product || ! isset( $booking_data[ 'totals' ][ 'base_price' ][ 'value' ] ) ) {
			return $booking_data ;
		}

		$product_props = _sumo_pp()->product->get_props( $product ) ;

		self::maybe_clear_booking_session( $product_props[ 'product_id' ] ) ;

		if ( is_numeric( $booking_data[ 'totals' ][ 'base_price' ][ 'value' ] ) && self::can_add_booking_cost( $product_props ) ) {
			WC()->session->set( '_sumo_pp_yith_wc_booking_cost', array(
				$product_props[ 'product_id' ] => $booking_data[ 'totals' ][ 'base_price' ][ 'value' ],
			) ) ;
		}

		return $booking_data ;
	}

	public static function set_booking_cost( $product_props ) {
		if ( ! is_callable( array( WC()->session, 'set' ) ) ) {
			return $product_props ;
		}

		$booking_cost = self::get_booking_cost_from_session( $product_props[ 'product_id' ] ) ;

		if ( self::can_add_booking_cost( $product_props ) && is_numeric( $booking_cost ) ) {
			$product_props[ 'product_price' ] = $booking_cost ;
		} else {
			self::maybe_clear_booking_session( $product_props[ 'product_id' ] ) ;
		}

		return $product_props ;
	}

	public static function add_booking_cost( $cart_payment_item_data ) {
		$booking_cost = self::get_booking_cost_from_session( $cart_payment_item_data[ 'product_id' ] ) ;

		if ( self::can_add_booking_cost( $cart_payment_item_data[ 'payment_product_props' ] ) && is_numeric( $booking_cost ) ) {
			$cart_payment_item_data[ 'base_price' ] = $booking_cost ;
		}

		return $cart_payment_item_data ;
	}
}

SUMO_PP_YITH_WC_Bookings::init() ;
