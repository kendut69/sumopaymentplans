<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Get SUMO Payment Plans templates.
 *
 * @param string $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: 'SUMO_PP_PLUGIN_BASENAME_DIR')
 * @param string $default_path (default: SUMO_PP_PLUGIN_TEMPLATE_PATH)
 */
function _sumo_pp_get_template( $template_name, $args = array(), $template_path = SUMO_PP_PLUGIN_BASENAME_DIR, $default_path = SUMO_PP_PLUGIN_TEMPLATE_PATH ) {
	if ( ! $template_name ) {
		return ;
	}

	wc_get_template( $template_name, $args, $template_path, $default_path ) ;
}

/**
 * Get My Account > Payment > Installments columns.
 *
 * @return array
 */
function sumopp_get_account_payment_installments_columns() {
	$columns = apply_filters( 'sumopaymentplans_account_payment_installments_columns', array(
		'installment-payment-of-product'             => __( 'Payments', 'sumopaymentplans' ),
		'installment-amount'                         => __( 'Installment Amount', 'sumopaymentplans' ),
		'installment-expected-payment-date'          => __( 'Expected Payment Date', 'sumopaymentplans' ),
		'installment-modified-expected-payment-date' => __( 'Modified Expected Payment Date', 'sumopaymentplans' ),
		'installment-actual-payment-date'            => __( 'Actual Payment Date', 'sumopaymentplans' ),
		'installment-order-number'                   => __( 'Order Number', 'sumopaymentplans' ),
			) ) ;

	return $columns ;
}

/**
 * Get Email > Payment Installments columns.
 *
 * @return array
 */
function sumopp_get_email_payment_installments_columns() {
	$columns = apply_filters( 'sumopaymentplans_email_payment_installments_columns', array(
		'installment-payment-of-product'             => __( 'Payments', 'sumopaymentplans' ),
		'installment-amount'                         => __( 'Installment Amount', 'sumopaymentplans' ),
		'installment-expected-payment-date'          => __( 'Expected Payment Date', 'sumopaymentplans' ),
		'installment-modified-expected-payment-date' => __( 'Modified Expected Payment Date', 'sumopaymentplans' ),
		'installment-actual-payment-date'            => __( 'Actual Payment Date', 'sumopaymentplans' ),
		'installment-order-number'                   => __( 'Order Number', 'sumopaymentplans' ),
			) ) ;

	return $columns ;
}

/**
 * Get view more template HTML.
 * 
 * @param mixed $product
 * @param mixed $plan
 * @return string
 */
function sumopp_get_view_more_template_html( $product, $plan ) {
	$html          = '' ;
	$product_props = is_array( $product ) ? $product : _sumo_pp()->product->get_props( $product ) ;
	$plan_props    = is_array( $plan ) ? $plan : _sumo_pp()->plan->get_props( $plan ) ;

	if ( ! empty( $product_props ) && ! empty( $plan_props[ 'payment_schedules' ] ) ) {
		ob_start() ;
		_sumo_pp_get_template( 'single-product/payment-plans/view-more.php', array(
			'product_props' => $product_props,
			'plan_props'    => $plan_props,
		) ) ;
		$contents = ob_get_clean() ;

		$html .= '<div class="_sumo_pp_plan_view_more">' ;
		$html .= '<p><a href="#">' . esc_html__( 'View more', 'sumopaymentplans' ) . '</a></p>' ;
		$html .= $contents ;
		$html .= '</div>' ;
	}

	return $html ;
}

/**
 * Get view more template by the cart item HTML.
 * 
 * @param array $cart_item
 * @return string
 */
function sumopp_get_view_more_template_by_cart_html( $cart_item ) {
	$html = '' ;

	if ( ! is_array( $cart_item ) || empty( $cart_item[ 'sumopaymentplans' ] ) ) {
		return $html ;
	}

	if ( ! empty( $cart_item[ 'sumopaymentplans' ][ 'payment_plan_props' ] ) && ! empty( $cart_item[ 'sumopaymentplans' ][ 'payment_plan_props' ][ 'payment_schedules' ] ) ) {
		$html .= sumopp_get_view_more_template_html( $cart_item[ 'sumopaymentplans' ][ 'payment_product_props' ], $cart_item[ 'sumopaymentplans' ][ 'payment_plan_props' ] ) ;
	}

	return $html ;
}

/**
 * Output the payment status HTML.
 * 
 * @param SUMO_PP_Payment $payment
 * @param string $page
 */
function _sumo_pp_payment_status_html( $payment, $page = 'frontend' ) {
	if ( 'frontend' === $page && $payment->has_status( 'await_cancl' ) ) {
		/* translators: 1: status name 2: status label */
		printf( '<div class="sumo-pp-payment-status-wrap"><mark class="%s"/>%s</mark></div>', esc_attr( SUMO_PP_PLUGIN_PREFIX . 'overdue' ), esc_html( _sumo_pp_get_payment_status_name( SUMO_PP_PLUGIN_PREFIX . 'overdue' ) ) ) ;
	} else {
		/* translators: 1: status name 2: status label */
		printf( '<div class="sumo-pp-payment-status-wrap"><mark class="%s"/>%s</mark></div>', esc_attr( $payment->get_status( true ) ), esc_html( $payment->get_status_label() ) ) ;
	}
}

/**
 * Output the deposit payment HTML.
 * 
 * @param SUMO_PP_Payment $payment
 * @param string $currency
 * @param string $page
 */
function _sumo_pp_deposit_amount_html( $payment, $currency, $page = 'frontend' ) {
	$discount_amount = $payment->get_prop( 'discount_amount' ) ;

	if ( 'pay-in-deposit' === $payment->get_payment_type() ) {
		$down_payment = $payment->get_down_payment() ;
	} elseif ( 'fixed-price' === $payment->get_plan_price_type() ) {
			$down_payment = floatval( $payment->get_prop( 'initial_payment' ) ) * $payment->get_product_qty() ;
	} else {
		$down_payment = ( ( floatval( $payment->get_prop( 'initial_payment' ) ) * $payment->get_product_price() ) / 100 ) * $payment->get_product_qty() ;
	}

	if ( ! is_numeric( $discount_amount ) ) {
		echo wp_kses_post( wc_price( $down_payment, array( 'currency' => $currency ) ) ) ;
	} else {
		echo '<del>' . wp_kses_post( wc_price( $down_payment, array( 'currency' => $currency ) ) ) . '</del>' ;
		echo wp_kses_post( wc_price( $down_payment - $discount_amount, array( 'currency' => $currency ) ) ) ;
	}

	echo ' x' . esc_html( $payment->get_product_qty() ) ;
}

/**
 * Maybe render payments schedule.
 *
 * @since 11.0.0
 * @param int $order Order Object.
 */
function _sumo_pp_maybe_render_payments_schedule( $order ) {
    //Return if not object.
    if ( ! $order ) {
        return;
    }

    $payment_ids = _sumo_pp_get_payment_ids_from_order( $order );
    // Return if empty payment IDs.
    if ( empty( $payment_ids ) ) {
        return;
    }

    _sumo_pp_get_template(
            'payments-schedule.php',
            array(
                'payment_ids' => $payment_ids,
                'has_payment' => 0 < count( $payment_ids ),
            )
    );
}

add_action( 'woocommerce_order_details_after_order_table', '_sumo_pp_maybe_render_payments_schedule', 10, 1 );
