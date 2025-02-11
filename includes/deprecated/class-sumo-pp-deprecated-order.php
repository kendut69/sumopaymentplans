<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Mimic WC Order
 * 
 * @deprecated since version 8.6
 */
class SUMO_PP_Deprecated_Order {

	/**
	 * The Order.
	 * 
	 * @var WC_Order 
	 */
	public $order = false ;

	/**
	 * Mimic the WC Order.
	 * 
	 * @param mixed $order
	 */
	public function __construct( $order ) {
		$this->order = _sumo_pp_maybe_get_order_instance( $order ) ;
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'order_id' ), true ) ) {
			return $this->order ? $this->order->get_id() : 0 ;
		}
	}

	/**
	 * Auto-load in-accessible methods on demand.
	 *
	 * @param mixed $key Key name.
	 * @param mixed  $parameters Parameters.
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		if ( is_callable( array( $this->order, $method ) ) ) {
			return call_user_func_array( array( $this->order, $method ), $parameters ) ;
		}

		return null ;
	}

	public function get_pay_url() {
		return esc_url_raw( $this->get_checkout_payment_url() ) ;
	}

	public function exists() {
		return $this->order ? true : false ;
	}

	public function get_email_order_items_table( $args = array() ) {
		return wc_get_email_order_items( $this, $args ) ;
	}

	public function display_email_order_item_totals( $plain = false ) {
		$text_align  = is_rtl() ? 'right' : 'left' ;
		$item_totals = $this->get_order_item_totals() ;

		if ( $item_totals ) {
			$i = 0 ;
			foreach ( $item_totals as $total ) {
				$i++ ;
				?>
				<tr>
					<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ) ; ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : '' ; ?>"><?php echo wp_kses_post( $total[ 'label' ] ) ; ?></th>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ) ; ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : '' ; ?>"><?php echo wp_kses_post( $total[ 'value' ] ) ; ?></td>
				</tr>
				<?php
			}
		}
	}

	public function get_email_order_item_totals( $plain = false ) {
		ob_start() ;
		$this->display_email_order_item_totals( $plain ) ;
		return ob_get_clean() ;
	}
}
