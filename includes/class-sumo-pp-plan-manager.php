<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Manage payment plan product
 * 
 * @class SUMO_PP_Plan_Manager
 * @package Class
 */
class SUMO_PP_Plan_Manager {

	protected static $props = array(
		'plan_id'                         => null,
		'plan_name'                       => null,
		'plan_price_type'                 => null,
		'installments_type'               => null,
		'pay_balance_type'                => null,
		'sync'                            => null,
		'sync_start_date'                 => null,
		'sync_month_duration'             => null,
		'plan_description'                => null,
		'initial_payment'                 => null,
		'next_payment_on'                 => null,
		'payment_schedules'               => null,
		'balance_payable_orders_creation' => null,
		'next_payment_date_based_on'      => null,
		'payment_started'                 => null,
		'payment_ended'                   => null,
		'fixed_no_of_installments'        => null,
		'fixed_payment_amount'            => null,
		'fixed_duration_length'           => null,
		'fixed_duration_period'           => null,
			) ;
	protected static $when_prev_ins_pay_with_current_ins ;
	protected static $when_plans_are_hidden ;

	/**
	 * The single instance of the class.
	 */
	protected static $instance = null ;

	/**
	 * Create instance for SUMO_PP_Plan_Manager.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self() ;
		}
		return self::$instance ;
	}

	/**
	 * Init SUMO_PP_Plan_Manager.
	 */
	public function init() {
		add_filter( 'sumopaymentplans_get_product_props', array( $this, 'contains_valid_plan' ), 99 ) ;
		add_filter( 'sumopaymentplans_get_orderpp_props', array( $this, 'contains_valid_plan' ), 99 ) ;
	}

	public function get_default_props() {
		return array_map( '__return_null', self::$props ) ;
	}

	public function when_prev_ins_pay_with_current_ins() {
		if ( ! is_null( self::$when_prev_ins_pay_with_current_ins ) ) {
			return self::$when_prev_ins_pay_with_current_ins ;
		}

		if ( 'sum-up-with-previous-installments' === get_option( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_behaviour_after_initial_payment_date', 'sum-up-with-previous-installments' ) ) {
			self::$when_prev_ins_pay_with_current_ins = get_option( SUMO_PP_PLUGIN_PREFIX . 'when_no_payment_plans_are_available', 'disable-payment-plan' ) ;
		}

		return self::$when_prev_ins_pay_with_current_ins ;
	}

	public function when_plans_are_hidden() {
		if ( ! is_null( self::$when_plans_are_hidden ) ) {
			return self::$when_plans_are_hidden ;
		}

		if ( 'hide-payment-plan' === get_option( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_behaviour_after_initial_payment_date', 'sum-up-with-previous-installments' ) ) {
			self::$when_plans_are_hidden = get_option( SUMO_PP_PLUGIN_PREFIX . 'when_no_payment_plans_are_available', 'disable-payment-plan' ) ;
		}

		return self::$when_plans_are_hidden ;
	}

	public function get_props( $plan_id ) {
		if ( ! is_numeric( $plan_id ) || ! $plan_id ) {
			return $this->get_default_props() ;
		}

		if ( 'publish' !== get_post_status( $plan_id ) ) {
			return $this->get_default_props() ;
		}

		$props                        = array() ;
		$props[ 'payment_schedules' ] = get_post_meta( $plan_id, '_payment_schedules', true ) ;

		if ( ! is_array( $props[ 'payment_schedules' ] ) ) {
			return $this->get_default_props() ;
		}

		$props[ 'plan_id' ]                         = absint( $plan_id ) ;
		$props[ 'plan_name' ]                       = get_the_title( $plan_id ) ;
		$props[ 'plan_description' ]                = get_post_meta( $plan_id, '_plan_description', true ) ;
		$props[ 'plan_price_type' ]                 = get_post_meta( $plan_id, '_price_type', true ) ? get_post_meta( $plan_id, '_price_type', true ) : 'percent' ;
		$props[ 'installments_type' ]               = get_post_meta( $plan_id, '_installments_type', true ) ? get_post_meta( $plan_id, '_installments_type', true ) : 'variable' ;
		$props[ 'sync' ]                            = get_post_meta( $plan_id, '_sync', true ) ;
		$props[ 'initial_payment' ]                 = floatval( get_post_meta( $plan_id, '_initial_payment', true ) ) ;
		$props[ 'balance_payable_orders_creation' ] = get_post_meta( $plan_id, '_balance_payable_orders_creation', true ) ;
		$props[ 'next_payment_date_based_on' ]      = get_post_meta( $plan_id, '_next_payment_date_based_on', true ) ;
		$props[ 'fixed_no_of_installments' ]        = absint( get_post_meta( $plan_id, '_fixed_no_of_installments', true ) ) ;
		$props[ 'fixed_payment_amount' ]            = floatval( get_post_meta( $plan_id, '_fixed_payment_amount', true ) ) ;
		$props[ 'fixed_duration_length' ]           = get_post_meta( $plan_id, '_fixed_duration_length', true ) ;
		$props[ 'fixed_duration_period' ]           = get_post_meta( $plan_id, '_fixed_duration_period', true ) ;

		if ( 'enabled' === $props[ 'sync' ] ) {
			$props[ 'pay_balance_type' ] = 'before' ;
		} else {
			$props[ 'pay_balance_type' ] = get_post_meta( $plan_id, '_pay_balance_type', true ) ? get_post_meta( $plan_id, '_pay_balance_type', true ) : 'after' ;
		}

		if ( 'before' === $props[ 'pay_balance_type' ] ) {
			if ( 'enabled' === $props[ 'sync' ] ) {
				$props[ 'sync_start_date' ] = get_post_meta( $plan_id, '_sync_start_date', true ) ;

				if ( ! empty( $props[ 'sync_start_date' ][ 'day' ] ) && ! empty( $props[ 'sync_start_date' ][ 'month' ] ) && ! empty( $props[ 'sync_start_date' ][ 'year' ] ) ) {
					$sync_start_time                = _sumo_pp_get_timestamp( $props[ 'sync_start_date' ][ 'day' ] . '-' . $props[ 'sync_start_date' ][ 'month' ] . '-' . $props[ 'sync_start_date' ][ 'year' ], 0, true ) ;
					$props[ 'sync_month_duration' ] = absint( get_post_meta( $plan_id, '_sync_month_duration', true ) ) ;

					if ( $sync_start_time < _sumo_pp_get_timestamp( 0, 0, true ) ) {
						$finding_sync_date = 1 ;
						for ( $i = 0 ; $i < $finding_sync_date ; $i++ ) {
							if ( $sync_start_time > _sumo_pp_get_timestamp( 0, 0, true ) ) {
								break ;
							} else {
								$finding_sync_date++ ;
								$sync_start_time = _sumo_pp_get_timestamp( '+' . $props[ 'sync_month_duration' ] . ' month', $sync_start_time, true ) ;
							}
						}
					}

					$from_when = $sync_start_time ;
					for ( $ins = 0 ; $ins < count( $props[ 'payment_schedules' ] ) ; $ins++ ) {
						$props[ 'payment_schedules' ][ $ins ][ 'scheduled_date' ] = _sumo_pp_get_date( $from_when ) ;
						$from_when                                                = _sumo_pp_get_timestamp( '+' . $props[ 'sync_month_duration' ] . ' month', $from_when, true ) ;
					}

					if ( _sumo_pp_get_timestamp( 0, 0, true ) === $sync_start_time ) {
						reset( $props[ 'payment_schedules' ] ) ;

						$initial_payment = current( $props[ 'payment_schedules' ] ) ;
						if ( ! empty( $initial_payment[ 'scheduled_payment' ] ) ) {
							$props[ 'initial_payment' ] = floatval( $initial_payment[ 'scheduled_payment' ] ) ;
						}

						unset( $props[ 'payment_schedules' ][ 0 ] ) ;
					}
				} else {
					$props[ 'payment_schedules' ] = array() ;
				}
			} else {
				$payment_start_date = current( $props[ 'payment_schedules' ] ) ;
				$payment_start_date = ! empty( $payment_start_date[ 'scheduled_date' ] ) ? _sumo_pp_get_timestamp( $payment_start_date[ 'scheduled_date' ], 0, true ) : 0 ;

				if ( $payment_start_date && _sumo_pp_get_timestamp( 0, 0, true ) > $payment_start_date ) {
					$props[ 'payment_started' ] = true ;
				}

				$props[ 'initial_payment' ] = 0 ;
				foreach ( $props[ 'payment_schedules' ] as $installment => $schedule ) {
					if ( ! empty( $schedule[ 'scheduled_date' ] ) ) {
						if ( _sumo_pp_get_timestamp( 0, 0, true ) > _sumo_pp_get_timestamp( $schedule[ 'scheduled_date' ], 0, true ) ) {
							$props[ 'initial_payment' ] += floatval( $schedule[ 'scheduled_payment' ] ) ;
							unset( $props[ 'payment_schedules' ][ $installment ] ) ;
						} else if ( _sumo_pp_get_timestamp( $schedule[ 'scheduled_date' ], 0, true ) >= _sumo_pp_get_timestamp( 0, 0, true ) ) {
							$props[ 'initial_payment' ] += floatval( $schedule[ 'scheduled_payment' ] ) ;
							unset( $props[ 'payment_schedules' ][ $installment ] ) ;
							break ;
						}
					} else {
						unset( $props[ 'payment_schedules' ][ $installment ] ) ;
					}
				}
				reset( $props[ 'payment_schedules' ] ) ;

				$payment_end_date = end( $props[ 'payment_schedules' ] ) ;
				$payment_end_date = ! empty( $payment_end_date[ 'scheduled_date' ] ) ? _sumo_pp_get_timestamp( $payment_end_date[ 'scheduled_date' ], 0, true ) : 0 ;

				if ( $payment_end_date && _sumo_pp_get_timestamp( 0, 0, true ) >= $payment_end_date ) {
					$props[ 'payment_ended' ] = true ;
				}
			}
		} else {
			$from_when = 0 ;
			foreach ( $props[ 'payment_schedules' ] as $installment => $schedule ) {
				if ( isset( $schedule[ 'scheduled_duration_length' ], $schedule[ 'scheduled_period' ] ) ) {
					$scheduled_payment_cycle = _sumo_pp_get_payment_cycle_in_days( $schedule[ 'scheduled_duration_length' ], $schedule[ 'scheduled_period' ] ) ;

					if ( $scheduled_payment_cycle > 0 ) {
						if ( 'after_admin_approval' !== get_option( SUMO_PP_PLUGIN_PREFIX . 'activate_payments', 'auto' ) ) {
							$from_when                                                        = _sumo_pp_get_timestamp( "+{$scheduled_payment_cycle} days", $from_when ) ;
							$props[ 'payment_schedules' ][ $installment ][ 'scheduled_date' ] = _sumo_pp_get_date( $from_when ) ;
						}
					} else {
						$props[ 'payment_schedules' ][ $installment ][ 'scheduled_date' ] = '' ;
					}
				}
			}
		}
		reset( $props[ 'payment_schedules' ] ) ;

		if ( apply_filters( 'sumopaymentplans_end_installment_at_end_of_the_day', false, 'plan' ) ) {
			$schedules = $props[ 'payment_schedules' ] ;

			foreach ( $schedules as $installment => $schedule ) {
				if ( ! empty( $schedule[ 'scheduled_date' ] ) && '00:00:00' === gmdate( 'H:i:s', _sumo_pp_get_timestamp( $schedule[ 'scheduled_date' ] ) ) ) {
					$props[ 'payment_schedules' ][ $installment ][ 'scheduled_date' ] = _sumo_pp_get_date( _sumo_pp_get_timestamp( "{$schedule[ 'scheduled_date' ]} 23:59:59" ) ) ;
				}
			}
		}

		$next_payment_date = current( $props[ 'payment_schedules' ] ) ;
		if ( ! empty( $next_payment_date[ 'scheduled_date' ] ) ) {
			$props[ 'next_payment_on' ] = _sumo_pp_get_date( $next_payment_date[ 'scheduled_date' ] ) ;
		}

		$props[ 'payment_schedules' ] = array_values( $props[ 'payment_schedules' ] ) ;

		self::$props = wp_parse_args( ( array ) apply_filters( 'sumopaymentplans_get_payment_plan_props', $props ), $this->get_default_props() ) ;
		return self::$props ;
	}

	public function get_cached_props() {
		return self::$props ;
	}

	public function get_prop( $context, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'props'         => self::$props,
			'product_price' => 0,
			'qty'           => 1,
				) ) ;

		if ( ! is_array( $args[ 'props' ] ) ) {
			$args[ 'props' ] = $this->get_props( $args[ 'props' ] ) ;
		}

		if ( empty( $args[ 'props' ][ 'plan_id' ] ) || ! is_numeric( $args[ 'props' ][ 'plan_id' ] ) ) {
			return null ;
		}

		if ( isset( $args[ 'props' ][ $context ] ) ) {
			return $args[ 'props' ][ $context ] ;
		}

		$product_amount  = $args[ 'product_price' ] * $args[ 'qty' ] ;
		$down_payment    = floatval( $args[ 'props' ][ 'initial_payment' ] ) ;
		$initial_payment = floatval( $args[ 'props' ][ 'initial_payment' ] ) ;

		if ( 'fixed-price' === $args[ 'props' ][ 'plan_price_type' ] ) {
			$total_payable_amount = $initial_payment * $args[ 'qty' ] ;
		} else {
			$total_payable_amount = ( $initial_payment * $product_amount ) / 100 ;
			$down_payment         = ( $initial_payment * $args[ 'product_price' ] ) / 100 ;
		}

		$balance_payable_amount  = 0 ;
		$next_installment_amount = null ;
		if ( is_array( $args[ 'props' ][ 'payment_schedules' ] ) ) {
			foreach ( $args[ 'props' ][ 'payment_schedules' ] as $schedule ) {
				if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
					continue ;
				}
				$next_scheduled_payment = floatval( $schedule[ 'scheduled_payment' ] ) ;

				if ( 'fixed-price' === $args[ 'props' ][ 'plan_price_type' ] ) {
					$balance_payable_amount += ( $next_scheduled_payment * $args[ 'qty' ] ) ;
				} else {
					$balance_payable_amount += ( $next_scheduled_payment * $product_amount ) / 100 ;
				}

				if ( is_null( $next_installment_amount ) ) {
					$next_installment_amount = $balance_payable_amount ;
				}
			}
		}
		$total_payable_amount += $balance_payable_amount ;

		switch ( $context ) {
			case 'down_payment':
				return $down_payment ;
			case 'next_installment_amount':
				return $next_installment_amount ;
			case 'total_payable':
				return $total_payable_amount ;
			case 'balance_payable':
				return $balance_payable_amount ;
		}

		return null ;
	}

	public function contains_valid_plan( $props ) {
		if ( ! empty( $props[ 'selected_plans' ] ) ) {
			$product_type = 'yes' !== $props[ 'apply_global_settings' ] ? $props[ 'product_type' ] : '' ;

			switch ( $product_type ) {
				case 'order':
					foreach ( $props[ 'selected_plans' ] as $row => $plan_id ) {
						$plan_props = $this->get_props( $plan_id ) ;

						if (
								empty( $plan_props[ 'payment_schedules' ] ) ||
								( 'disable-payment-plan' === $this->when_plans_are_hidden() && $plan_props[ 'payment_started' ] ) ||
								( 'disable-payment-plan' === $this->when_prev_ins_pay_with_current_ins() && $plan_props[ 'payment_ended' ] )
						) {
							unset( $props[ 'selected_plans' ][ $row ] ) ;
						}
					}
					$props[ 'selected_plans' ] = array_values( $props[ 'selected_plans' ] ) ;
					break ;
				default:
					foreach ( $props[ 'selected_plans' ] as $col => $plans ) {
						foreach ( $plans as $row => $plan_id ) {
							$plan_props = $this->get_props( $plan_id ) ;

							if (
									empty( $plan_props[ 'payment_schedules' ] ) ||
									( in_array( $this->when_plans_are_hidden(), array( 'disable-payment-plan', 'set-as-out-of-stock' ) ) && $plan_props[ 'payment_started' ] ) ||
									( in_array( $this->when_prev_ins_pay_with_current_ins(), array( 'disable-payment-plan', 'set-as-out-of-stock' ) ) && $plan_props[ 'payment_ended' ] )
							) {
								unset( $props[ 'selected_plans' ][ $col ][ $row ] ) ;
							}
						}
						$props[ 'selected_plans' ][ $col ] = array_values( $props[ 'selected_plans' ][ $col ] ) ;

						if ( empty( $props[ 'selected_plans' ][ $col ] ) ) {
							unset( $props[ 'selected_plans' ][ $col ] ) ;
						}
					}
					break ;
			}

			if ( empty( $props[ 'selected_plans' ] ) ) {
				$props = array() ;
			}
		}

		return $props ;
	}
}
