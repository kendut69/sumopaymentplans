<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

function _sumo_pp_get_order( $order ) {
	$order = new SUMO_PP_Deprecated_Order( $order ) ;
	return $order->exists() ? $order : false ;
}

function _sumo_pp_payment_exists( $payment_id ) {
	return _sumo_pp_get_payment( $payment_id ) ? true : false ;
}

function _sumo_pp_get_payment_number( $payment_id ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return $payment->get_payment_number() ;
	}

	return 0 ;
}

function _sumo_pp_payment_has_status( $payment_id, $status ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return $payment->has_status( $status ) ;
	}

	return false ;
}

function _sumo_pp_get_formatted_payment_product_title( $payment_id, $args = array() ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return $payment->get_formatted_product_name( $args ) ;
	}

	return '' ;
}

function _sumo_pp_payment_has_next_installment( $payment_id ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return $payment->has_next_installment() ;
	}

	return false ;
}

function _sumo_pp_get_payment_status( $payment_id ) {
	$payment_status       = '' ;
	$payment_status_label = '' ;
	$payment              = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		$payment_status       = $payment->get_status( true ) ;
		$payment_status_label = $payment->get_status_label() ;
	}

	return array(
		'label' => $payment_status_label,
		'name'  => $payment_status,
			) ;
}

function _sumo_pp_update_payment_status( $payment_id, $payment_status ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return $payment->update_status( $payment_status ) ;
	}

	return false ;
}

function _sumo_pp_get_payment_end_date( $payment_id ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return $payment->get_payment_end_date() ;
	}

	return '' ;
}

function _sumo_pp_get_next_payment_date( $payment_id, $next_of_next = false ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		if ( $next_of_next ) {
			$installment = $payment->get_next_of_next_installment_count() ;
		} else {
			$installment = null ;
		}

		return $payment->get_next_payment_date( $installment ) ;
	}

	return '' ;
}

function _sumo_pp_is_balance_payable_order_exists( $payment_id ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return $payment->balance_payable_order_exists() ;
	}

	return false ;
}

function _sumo_pp_get_balance_paid_orders( $payment_id ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return $payment->get_balance_paid_orders() ;
	}

	return array() ;
}

function _sumo_pp_get_next_installment_amount( $payment_id, $next_of_next = false ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		if ( $next_of_next ) {
			$installment = $payment->get_next_of_next_installment_count() ;
		} else {
			$installment = null ;
		}

		return $payment->get_next_installment_amount( $installment ) ;
	}

	return 0 ;
}

function _sumo_pp_get_remaining_installments( $payment_id ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return $payment->get_remaining_installments() ;
	}

	return 0 ;
}

function _sumo_pp_get_remaining_payable_amount( $payment_id, $next_of_next = false ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		if ( $next_of_next ) {
			$installment = $payment->get_next_of_next_installment_count() ;
		} else {
			$installment = null ;
		}

		return $payment->get_remaining_payable_amount( $installment ) ;
	}

	return 0 ;
}

function _sumo_pp_get_total_payable_amount( $payment_id ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return $payment->get_total_payable_amoun() ;
	}

	return 0 ;
}

function _sumo_pp_get_payment_notes( $args = array() ) {
	$payment = _sumo_pp_get_payment( $args[ 'payment_id' ] ) ;

	if ( $payment ) {
		return $payment->get_payment_notes( $args ) ;
	}

	return 0 ;
}

function _sumo_pp_send_payment_email( $payment_id, $template_id, $order_id, $manual = false ) {
	$payment = _sumo_pp_get_payment( $payment_id ) ;

	if ( $payment ) {
		return _sumo_pp()->mailer->send( $template_id, $order_id, $payment, $manual ) ;
	}

	return false ;
}

function _sumo_pp_get_initial_payment_order( $order, $check_in_child = true ) {
	return _sumo_pp_get_parent_order_id( $order ) ;
}

function _sumo_pp_get_cart_data( $product = null, $customer_id = 0 ) {
	if ( _sumo_pp()->cart->cart_contains_payment() ) {
		if ( ! is_null( $product ) ) {
			$product_id = 0 ;
			if ( is_callable( array( $product, 'get_id' ) ) ) {
				$product_id = $product->get_id() ;
			} else {
				$product = wc_get_product( $product ) ;
				if ( $product ) {
					$product_id = $product->get_id() ;
				}
			}

			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				if ( ! empty( $cart_item[ 'sumopaymentplans' ][ 'product_id' ] ) && $product_id === $cart_item[ 'sumopaymentplans' ][ 'product_id' ] ) {
					return $cart_item[ 'sumopaymentplans' ] ;
				}
			}
		} else {
			$item_session = array() ;
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				if ( ! empty( $cart_item[ 'sumopaymentplans' ][ 'product_id' ] ) ) {
					$item_session[ $cart_item[ 'sumopaymentplans' ][ 'product_id' ] ] = $cart_item[ 'sumopaymentplans' ] ;
				}
			}

			return $item_session ;
		}
	}

	return null ;
}

function _sumo_pp_cart_has_payment_items() {
	return _sumo_pp()->cart->cart_contains_payment() ;
}

function _sumo_pp_is_payment_product( $product, $customer_id = 0 ) {
	return _sumo_pp()->cart->is_payment_item( $product ) ;
}

function _sumo_pp_get_payment_data( $product = null ) {
	$meta_data = array() ;
	$payment   = _sumo_pp()->cart->is_payment_item( $product ) ;

	if ( empty( $payment[ 'payment_product_props' ][ 'payment_type' ] ) ) {
		return $meta_data ;
	}

	if ( 'payment-plans' === $payment[ 'payment_product_props' ][ 'payment_type' ] ) {
		$meta_data[ 'plan_name' ]        = _sumo_pp()->cart->get_payment_info_to_display( $payment, 'plan_name' ) ;
		$meta_data[ 'plan_description' ] = $payment[ 'payment_plan_props' ][ 'plan_description' ] ;
	}

	$meta_data[ 'product_price' ]        = $payment[ 'payment_product_props' ][ 'product_price' ] ;
	$meta_data[ 'total_payable_amount' ] = $payment[ 'total_payable_amount' ] ;

	return $meta_data ;
}

function _sumo_pp_get_cart_balance_payable_amount() {
	return _sumo_pp()->cart->get_cart_balance_payable_amount() ;
}

function _sumo_pp_get_cart_payment_display_string( $product ) {
	$payment = _sumo_pp()->cart->is_payment_item( $product ) ;

	if ( ! $payment ) {
		return array() ;
	}

	return array(
		'under_product_column' => _sumo_pp()->cart->get_payment_info_to_display( $payment, 'plan_name' ),
		'under_price_column'   => _sumo_pp()->cart->get_payment_info_to_display( $payment ),
		'under_total_column'   => _sumo_pp()->cart->get_payment_info_to_display( $payment, 'balance_payable' ),
			) ;
}

function _sumo_pp_set_payment_session( $_payment_data ) {
	$product_id       = isset( $_payment_data[ 'payment_product_props' ][ 'product_id' ] ) ? $_payment_data[ 'payment_product_props' ][ 'product_id' ] : null ;
	$quantity         = isset( $_payment_data[ 'product_qty' ] ) ? absint( $_payment_data[ 'product_qty' ] ) : 1 ;
	$deposited_amount = isset( $_payment_data[ 'deposited_amount' ] ) ? $_payment_data[ 'deposited_amount' ] : null ;
	$product_id       = _sumo_pp_get_product_id( $product_id ) ;

	if ( ! $product_id ) {
		return false ;
	}

	$SUMO_Payment_Plans_is_enabled = 'yes' === get_post_meta( $product_id, '_sumo_pp_enable_sumopaymentplans', true ) ;
	if ( $SUMO_Payment_Plans_is_enabled ) {
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! empty( $cart_item[ 'variation_id' ] ) ) {
				$item_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;

				if ( $item_id == $product_id ) {
					$payment_data = SUMO_PP_Data_Manager::get_payment_data( array(
								'product_props'    => $_payment_data[ 'payment_product_props' ],
								'plan_props'       => $_payment_data[ 'payment_plan_props' ],
								'deposited_amount' => $quantity,
								'qty'              => $deposited_amount,
								'item_meta'        => $cart_item,
							) ) ;

					if ( empty( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) ) {
						WC()->cart->cart_contents[ $cart_item_key ][ 'sumopaymentplans' ] = array() ;
						continue ;
					}

					switch ( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) {
						case 'payment-plans':
							if (
									empty( $payment_data[ 'payment_plan_props' ][ 'payment_schedules' ] ) ||
									empty( $payment_data[ 'payment_product_props' ][ 'selected_plans' ] )
							) {
								WC()->cart->cart_contents[ $cart_item_key ][ 'sumopaymentplans' ] = array() ;
								continue 2 ;
							}

							$plans_col_1 = ! empty( $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_1' ] ) ? $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_1' ] : array() ;
							$plans_col_2 = ! empty( $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_2' ] ) ? $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_2' ] : array() ;

							if ( ! in_array( $payment_data[ 'payment_plan_props' ][ 'plan_id' ], $plans_col_1 ) && ! in_array( $payment_data[ 'payment_plan_props' ][ 'plan_id' ], $plans_col_2 ) ) {
								WC()->cart->cart_contents[ $cart_item_key ][ 'sumopaymentplans' ] = array() ;
								continue 2 ;
							}
							break ;
						case 'pay-in-deposit':
							break ;
					}

					WC()->cart->cart_contents[ $cart_item_key ][ 'sumopaymentplans' ] = $payment_data ;
					break ;
				}
			}
		}

		return true ;
	}

	return false ;
}

function _sumo_pp_is_initial_payment_order( $order ) {
	return _sumo_pp_is_deposit_order( $order ) ;
}

function _sumo_pp_get_down_payment_to_display( $payment, $initial_payment_order ) {
	if ( is_object( $initial_payment_order ) ) {
		_sumo_pp_deposit_amount_html( $payment, $initial_payment_order->get_currency() ) ;
	}
}

function _sumo_pp_get_payment_orders_table( $payment, $args = array(), $echo = true ) {
	$payment                 = _sumo_pp_get_payment( $payment ) ;
	$args                    = wp_parse_args( $args, array(
		'class'          => '',
		'id'             => '',
		'css'            => '',
		'custom_attr'    => '',
		'th_class'       => '',
		'th_css'         => '',
		'th_custom_attr' => '',
		'th_elements'    => array(
			'payments'                       => __( 'Payments', 'sumopaymentplans' ),
			'installment-amount'             => __( 'Installment Amount', 'sumopaymentplans' ),
			'expected-payment-date'          => __( 'Expected Payment Date', 'sumopaymentplans' ),
			'modified-expected-payment-date' => __( 'Modified Expected Payment Date', 'sumopaymentplans' ),
			'actual-payment-date'            => __( 'Actual Payment Date', 'sumopaymentplans' ),
			'order-number'                   => __( 'Order Number', 'sumopaymentplans' ),
		),
		'page'           => 'frontend',
			) ) ;
	$actual_payments_date    = $payment->get_prop( 'actual_payments_date' ) ;
	$scheduled_payments_date = $payment->get_prop( 'scheduled_payments_date' ) ;
	$modified_payment_dates  = $payment->get_prop( 'modified_expected_payment_dates' ) ;
	$initial_payment_order   = _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() ) ;
	$balance_paid_orders     = $payment->get_balance_paid_orders() ;

	if ( ! $payment->is_expected_payment_dates_modified() || $payment->has_status( 'completed' ) ) {
		unset( $args[ 'th_elements' ][ 'modified-expected-payment-date' ] ) ;
	}
	$column_keys = array_keys( $args[ 'th_elements' ] ) ;

	ob_start() ;
	?>
	<table class="<?php echo esc_attr( $args[ 'class' ] ) ; ?>" <?php echo esc_attr( $args[ 'custom_attr' ] ) ; ?> style="<?php echo esc_attr( $args[ 'css' ] ) ; ?>">
		<thead>
			<tr>
				<?php foreach ( $args[ 'th_elements' ] as $column_name ) : ?>
					<th class="<?php echo esc_attr( $args[ 'th_class' ] ) ; ?>" <?php echo esc_attr( $args[ 'th_custom_attr' ] ) ; ?> style="<?php echo esc_attr( $args[ 'th_css' ] ) ; ?>"><?php echo esc_html( $column_name ) ; ?></th>
				<?php endforeach ; ?>
			</tr>
		</thead>
		<tbody>
			<?php do_action( 'sumopaymentplans_installments_table_row_start', $payment, $initial_payment_order, $args ) ; ?>
			<?php
			if ( 'pay-in-deposit' === $payment->get_payment_type() ) {
				$balance_paid_order = isset( $balance_paid_orders[ 0 ] ) ? $balance_paid_orders[ 0 ] : 0 ;

				if ( 'admin' === $args[ 'page' ] ) {
					$url = admin_url( "post.php?post={$balance_paid_order}&action=edit" ) ;
				} else {
					$url = wc_get_endpoint_url( 'view-order', $balance_paid_order, wc_get_page_permalink( 'myaccount' ) ) ;
				}
				?>
				<tr>
					<?php if ( in_array( 'payments', $column_keys ) ) { ?>
						<td>
							<?php
							if ( 'order' === $payment->get_product_type() ) {
								if ( $balance_paid_order > 0 ) {
									/* translators: 1: paid order url 2: product name */
									printf( wp_kses_post( __( '<a href="%1$s">Installment #1 of %2$s</a>', 'sumopaymentplans' ) ), esc_url( $url ), wp_kses_post( $payment->get_formatted_product_name( array( 'page' => $args[ 'page' ] ) ) ) ) ;
								} else {
									/* translators: 1: product name */
									printf( wp_kses_post( __( 'Installment #1 of %s', 'sumopaymentplans' ) ), wp_kses_post( $payment->get_formatted_product_name( array( 'page' => $args[ 'page' ] ) ) ) ) ;
								}
							} elseif ( $balance_paid_order > 0 ) {
									/* translators: 1: paid order url 2: product name */
									printf( wp_kses_post( __( '<a href="%1$s">Installment #1 of %2$s</a>&nbsp;&nbsp;x%3$s', 'sumopaymentplans' ) ), esc_url( $url ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false, 'page' => $args[ 'page' ] ) ) ), esc_html( $payment->get_product_qty() ) ) ;
							} else {
								/* translators: 1: product name */
								printf( wp_kses_post( __( 'Installment #1 of %1$s&nbsp;&nbsp;x%2$s', 'sumopaymentplans' ) ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false, 'page' => $args[ 'page' ] ) ) ), esc_html( $payment->get_product_qty() ) ) ;
							}
							?>
						</td>
					<?php } ?>
					<?php if ( in_array( 'installment-amount', $column_keys ) ) { ?>
						<td>
							<?php
							$installment_amount = wc_price( $payment->get_product_price() - $payment->get_down_payment( false ), array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;

							if ( 'order' === $payment->get_product_type() ) {
								echo wp_kses_post( $installment_amount ) ;
							} else {
								echo wp_kses_post( "{$installment_amount}&nbsp;&nbsp;x{$payment->get_product_qty()}" ) ;
							}
							?>
						</td>
					<?php } ?>
					<?php if ( in_array( 'expected-payment-date', $column_keys ) ) { ?>
						<td>
							<?php
							$installment_date  = '' ;
							$next_payment_date = $payment->get_prop( 'next_payment_date' ) ;

							if ( $next_payment_date ) {
								$installment_date = $next_payment_date ;
							} elseif ( 'before' === $payment->get_pay_balance_type() ) {
									$installment_date = _sumo_pp_get_timestamp( $payment->get_pay_balance_before() ) ;
							} elseif ( ! $payment->has_status( 'await_aprvl' ) && $payment->get_pay_balance_after() > 0 ) {
									$installment_date = _sumo_pp_get_timestamp( "+{$payment->get_pay_balance_after()} days", _sumo_pp_get_timestamp( $payment->get_prop( 'payment_start_date' ) ) ) ;
							}

							if ( '' !== $installment_date ) {
								echo esc_html( _sumo_pp_get_date_to_display( $installment_date, $args[ 'page' ] ) ) ;
							} else if ( 'admin' !== $args[ 'page' ] ) {
								echo '--' ;
							}

							if ( 'admin' === $args[ 'page' ] && 0 === $balance_paid_order ) {
								if ( '' === $installment_date ) {
									echo '<input class="expected_payment_date" type="text" name="expected_payment_date" value=""/>' ;
								} else {
									echo '<p><a href="#" class="edit-installment-date">' . esc_html__( 'Edit', 'sumopaymentplans' ) . '</a><input class="expected_payment_date" type="text" style="display:none;" name="expected_payment_date" value="' . esc_attr( _sumo_pp_get_date( $installment_date ) ) . '"/></p>' ;
								}
							}
							?>
						</td>
					<?php } ?>
					<?php if ( in_array( 'actual-payment-date', $column_keys ) ) { ?>
						<td>
							<?php
							if ( ! empty( $actual_payments_date[ 0 ] ) ) {
								echo esc_html( _sumo_pp_get_date_to_display( $actual_payments_date[ 0 ], $args[ 'page' ] ) ) ;
							} else {
								echo '--' ;
							}
							?>
						</td>
					<?php } ?>
					<?php if ( in_array( 'order-number', $column_keys ) ) { ?>
						<td>
							<?php
							if ( $balance_paid_order > 0 ) {
								/* translators: 1: paid order url 2: paid order ID */
								printf( wp_kses_post( __( '<a href="%1$s">#%2$s</a><p>Paid</p>', 'sumopaymentplans' ) ), esc_url( $url ), esc_html( $balance_paid_order ) ) ;
							} elseif ( 'admin' !== $args[ 'page' ] && $payment->balance_payable_order_exists() ) {
									/* translators: 1: invoice order url 2: invoice order ID */
									printf( wp_kses_post( __( '<a class="button" href="%1$s">Pay for #%2$s</a>', 'sumopaymentplans' ) ), esc_url( $payment->balance_payable_order->get_checkout_payment_url() ), esc_html( $payment->balance_payable_order->get_order_number() ) ) ;
							} else {
								echo '--' ;
							}
							?>
						</td>
					<?php } ?>
				</tr>
				<?php
			} elseif ( is_array( $payment->get_prop( 'payment_schedules' ) ) ) {
				foreach ( $payment->get_prop( 'payment_schedules' ) as $installment => $schedule ) {
					if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
						continue ;
					}
					$balance_paid_order = isset( $balance_paid_orders[ $installment ] ) ? $balance_paid_orders[ $installment ] : 0 ;

					if ( 'admin' === $args[ 'page' ] ) {
						$url = admin_url( "post.php?post={$balance_paid_order}&action=edit" ) ;
					} else {
						$url = wc_get_endpoint_url( 'view-order', $balance_paid_order, wc_get_page_permalink( 'myaccount' ) ) ;
					}
					?>
						<tr>
						<?php if ( in_array( 'payments', $column_keys ) ) { ?>
								<td>
									<?php
									$payment_count = $installment ;
									++$payment_count ;
									$payment_count = apply_filters( 'sumopaymentplans_installment_count', $payment_count, $args ) ;

									if ( 'order' === $payment->get_product_type() ) {
										if ( $balance_paid_order > 0 ) {
											/* translators: 1: paid order url 2: payment count 3: product name */
											printf( wp_kses_post( __( '<a href="%1$s">Installment #%2$s of %3$s</a>', 'sumopaymentplans' ) ), esc_url( $url ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name( array( 'page' => $args[ 'page' ] ) ) ) ) ;
										} else {
											/* translators: 1: payment count 2: product name */
											printf( wp_kses_post( __( 'Installment #%1$s of %2$s', 'sumopaymentplans' ) ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name( array( 'page' => $args[ 'page' ] ) ) ) ) ;
										}
									} elseif ( $balance_paid_order > 0 ) {
											/* translators: 1: paid order url 2: payment count 3: product name 4: product qty */
											printf( wp_kses_post( __( '<a href="%1$s">Installment #%2$s of %3$s</a>&nbsp;&nbsp;x%4$s', 'sumopaymentplans' ) ), esc_url( $url ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false, 'page' => $args[ 'page' ] ) ) ), esc_html( $payment->get_product_qty() ) ) ;
									} else {
										/* translators: 1: payment count 2: product name 3: product qty */
										printf( wp_kses_post( __( 'Installment #%1$s of %2$s&nbsp;&nbsp;x%3$s', 'sumopaymentplans' ) ), esc_html( $payment_count ), wp_kses_post( $payment->get_formatted_product_name( array( 'qty' => false, 'page' => $args[ 'page' ] ) ) ), esc_html( $payment->get_product_qty() ) ) ;
									}
									?>
								</td>
							<?php } ?>
							<?php if ( in_array( 'installment-amount', $column_keys ) ) { ?>
								<td>
									<?php
									if ( isset( $schedule[ 'scheduled_payment' ] ) ) {
										if ( 'fixed-price' === $payment->get_plan_price_type() ) {
											$installment_amount          = $schedule[ 'scheduled_payment' ] ;
											$formated_installment_amount = wc_price( $installment_amount, array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
										} else {
											$installment_amount          = $payment->get_product_price() * floatval( $schedule[ 'scheduled_payment' ] ) / 100 ;
											$formated_installment_amount = wc_price( $installment_amount, array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
										}
									} else {
										$installment_amount          = '0' ;
										$formated_installment_amount = wc_price( $installment_amount, array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
									}

									if ( 'order' === $payment->get_product_type() ) {
										echo wp_kses_post( $formated_installment_amount ) ;
									} else {
										echo wp_kses_post( "{$formated_installment_amount}&nbsp;&nbsp;x{$payment->get_product_qty()}" ) ;
									}

									if ( 'admin' === $args[ 'page' ] && 0 === $balance_paid_order && ! ( $payment->get_next_installment_count() === $installment && $payment->balance_payable_order_exists() ) ) {
										echo '<p><a href="#" class="edit-installment-amount">' . esc_html__( 'Edit', 'sumopaymentplans' ) . '</a><input class="expected_installment_amount" type="text" style="display:none;" name="expected_installment_amount[' . esc_attr( $installment ) . ']" value="' . esc_attr( $installment_amount ) . '"/></p>' ;
									}
									?>
								</td>
							<?php } ?>
							<?php if ( in_array( 'expected-payment-date', $column_keys ) ) { ?>
								<td>
									<?php
									if ( ! empty( $scheduled_payments_date[ $installment ] ) ) {
										echo esc_html( _sumo_pp_get_date_to_display( $scheduled_payments_date[ $installment ], $args[ 'page' ] ) ) ;

										if ( 'admin' === $args[ 'page' ] && 0 === $balance_paid_order ) {
											echo '<p><a href="#" class="edit-installment-date">' . esc_html__( 'Edit', 'sumopaymentplans' ) . '</a><input class="expected_payment_date" type="text" style="display:none;" name="expected_payment_date[' . esc_attr( $installment ) . ']" value="' . esc_attr( _sumo_pp_get_date( $scheduled_payments_date[ $installment ] ) ) . '"/></p>' ;
										}
									} else {
										echo '--' ;
									}
									?>
								</td>
							<?php } ?>
							<?php if ( in_array( 'modified-expected-payment-date', $column_keys ) ) { ?>
								<td>
									<?php
									if ( ! empty( $modified_payment_dates[ $installment ] ) ) {
										echo esc_html( _sumo_pp_get_date_to_display( $modified_payment_dates[ $installment ], $args[ 'page' ] ) ) ;
									} else {
										echo '--' ;
									}
									?>
								</td>
							<?php } ?>
							<?php if ( in_array( 'actual-payment-date', $column_keys ) ) { ?>
								<td>
									<?php
									if ( ! empty( $actual_payments_date[ $installment ] ) ) {
										echo esc_html( _sumo_pp_get_date_to_display( $actual_payments_date[ $installment ], $args[ 'page' ] ) ) ;
									} else {
										echo '--' ;
									}
									?>
								</td>
							<?php } ?>
							<?php if ( in_array( 'order-number', $column_keys ) ) { ?>
								<td>
									<?php
									if ( $balance_paid_order > 0 ) {
										/* translators: 1: paid order url 2: paid order ID */
										printf( wp_kses_post( __( '<a href="%1$s">#%2$s</a><p>Paid</p>', 'sumopaymentplans' ) ), esc_url( $url ), esc_html( $balance_paid_order ) ) ;
									} elseif ( 'admin' !== $args[ 'page' ] && empty( $balance_payable_order ) && $payment->balance_payable_order_exists() ) {
											$balance_payable_order = $payment->balance_payable_order ;
											/* translators: 1: invoice order url 2: invoice order ID */
											printf( wp_kses_post( __( '<a class="button" href="%1$s">Pay for #%2$s</a>', 'sumopaymentplans' ) ), esc_url( $payment->balance_payable_order->get_checkout_payment_url() ), esc_html( $payment->balance_payable_order->get_order_number() ) ) ;
									} else {
										echo '--' ;
									}
									?>
								</td>
							<?php } ?>
						</tr>
						<?php
				}
			}
			?>
			<?php do_action( 'sumopaymentplans_installments_table_row_end', $payment, $initial_payment_order, $args ) ; ?>
		</tbody>
	</table>
	<?php
	if ( $echo ) {
		ob_end_flush() ;
	} else {
		return ob_get_clean() ;
	}
}
