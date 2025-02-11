<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle Payment scheduler
 * 
 * @class SUMO_PP_Job_Scheduler
 * @package Class
 */
class SUMO_PP_Job_Scheduler extends SUMO_PP_Abstract_Job_Scheduler {

	/**
	 * Get available schedules per day
	 * Ex. 
	 * 1) if $no_of_days === 1 && $times === 2 then get timestamp 2 times for 1 day
	 * 2) if $no_of_days === 2 && $times === 2 then get timestamp 2 times for each day
	 * 
	 * @return array()
	 */
	public function get_schedules_per_day( $no_of_days, $times ) {
		$schedules_per_day = array();

		for ( $n = 0; $n < $no_of_days; $n++ ) {
			for ( $t = 1; $t <= $times; $t++ ) {

				$time             = _sumo_pp_get_timestamp( "+$n days" );
				$day_start_time   = _sumo_pp_get_timestamp( gmdate( 'Y-m-d 00:00:00', $time ) );
				$day_end_time     = _sumo_pp_get_timestamp( gmdate( 'Y-m-d 23:59:59', $time ) );
				$one_day_interval = $day_end_time - $time;

				if ( $n > 0 ) {
					$one_day_interval = $day_end_time - $day_start_time;
					$time             = $day_start_time;
				}

				$timestamp = $one_day_interval ? $time + ( $one_day_interval / $t ) : 0;

				if ( $timestamp >= _sumo_pp_get_timestamp() ) {
					$schedules_per_day[] = $timestamp;
				}
			}
		}

		if ( $schedules_per_day ) {
			$schedules_per_day = array_unique( $schedules_per_day );
			sort( $schedules_per_day );
		}

		return $schedules_per_day;
	}

	/**
	 * Get available schedules before end time
	 * Ex. 
	 * 1) if $days_count = array(4,3,2,1) && $total_days === 2 then get timestamp before end time for last 2 days
	 * 2) if $days_count = array(3,2,1) && $total_days === 5 then get timestamp before end time for last 3 days
	 * 
	 * @return array
	 */
	public function get_schedules_before_end_time( $schedule_end, $days_count, $total_days ) {
		$schedules_before_time = array();

		if ( empty( $days_count ) || ! is_array( $days_count ) ) {
			return $schedules_before_time;
		}

		foreach ( $days_count as $day_count ) {
			if ( ! $day_count ) {
				continue;
			}

			if ( $total_days >= $day_count ) {
				$timestamp = strtotime( "-{$day_count} days", $schedule_end );

				if ( $timestamp >= _sumo_pp_get_timestamp() ) {
					$schedules_before_time[] = $timestamp;
				}
			}
		}

		if ( $schedules_before_time ) {
			$schedules_before_time = array_unique( $schedules_before_time );
			sort( $schedules_before_time );
		}

		return $schedules_before_time;
	}

	/**
	 * Get available schedules after current time
	 * Ex. 
	 * 1) if $days_count = array(1,2,3,4) && $total_days === 2 then get timestamp after current time for first 2 days
	 * 2) if $days_count = array(1,2,3) && $total_days === 5 then get timestamp after current time for first 3 days
	 * 
	 * @return array
	 */
	public function get_schedules_after_current_time( $schedule_end, $days_count, $total_days ) {
		$schedules_after_time = array();

		if ( empty( $days_count ) || ! is_array( $days_count ) ) {
			return $schedules_after_time;
		}

		foreach ( $days_count as $day_count ) {
			if ( ! $day_count ) {
				continue;
			}

			if ( $total_days >= $day_count ) {
				$timestamp = strtotime( "+{$day_count} days", _sumo_pp_get_timestamp() );

				if ( $timestamp <= absint( $schedule_end ) ) {
					$schedules_after_time[] = $timestamp;
				}
			}
		}

		if ( $schedules_after_time ) {
			$schedules_after_time = array_unique( $schedules_after_time );
			sort( $schedules_after_time );
		}

		return $schedules_after_time;
	}

	/**
	 * Schedule Balance payment Order Creation.
	 */
	public function schedule_balance_payable_order( $next_payment_date ) {
		//Check whether to Schedule this Cron.
		if ( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job', true, 'create_balance_payable_order', $this->payment->id ) ) {
			return false;
		}

		$next_payment_cycle_days = _sumo_pp_get_payment_cycle_in_days( null, null, $next_payment_date );
		$no_of_days_before       = absint( $this->payment->get_option( 'create_next_payable_order_before', '1' ) );

		if ( $next_payment_cycle_days < $no_of_days_before ) {
			$no_of_days_before = $next_payment_cycle_days;
		}

		//Get Timestamp for next balance payable order to be Happened.
		$timestamp = _sumo_pp_get_timestamp( "$next_payment_date -$no_of_days_before days" );

		return $this->create_job( $timestamp, 'create_balance_payable_order', array(
					'next_payment_on' => $next_payment_date,
				) );
	}

	/**
	 * Schedule Payment Reminders.
	 */
	public function schedule_reminder( $balance_payable_order_id, $remind_ending_timestamp, $mail_template_id ) {
		//Check whether to Schedule this Cron.
		if ( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job', true, 'notify_reminder', $this->payment->id ) ) {
			return false;
		}

		$remind_from_time        = _sumo_pp_get_timestamp();
		$remind_ending_timestamp = _sumo_pp_get_timestamp( $remind_ending_timestamp );
		$total_days_to_notify    = ceil( ( $remind_ending_timestamp - $remind_from_time ) / 86400 );
		$reminder_intervals      = _sumo_pp_get_reminder_intervals( $mail_template_id, $this->payment );

		$scheduled = false;
		if ( isset( $reminder_intervals[ 'times-per-day' ] ) ) {//Schedule by times per day
			$schedules_per_day = $this->get_schedules_per_day( $total_days_to_notify, $reminder_intervals[ 'times-per-day' ] );

			if ( $schedules_per_day ) {
				foreach ( $schedules_per_day as $notify_time ) {
					if ( $this->create_job( $notify_time, 'notify_reminder', array(
								'balance_payable_order_id' => absint( $balance_payable_order_id ),
								'mail_template_id'         => $mail_template_id,
							) )
					) {
						$scheduled = true;
					}
				}
			}
		} else if ( isset( $reminder_intervals[ 'no-of-days' ] ) ) {//Schedule by comma separated days
			if ( isset( $reminder_intervals[ 'schedule-type' ] ) && 'after-date' === $reminder_intervals[ 'schedule-type' ] ) {
				$schedules = $this->get_schedules_after_current_time( $remind_ending_timestamp, $reminder_intervals[ 'no-of-days' ], $total_days_to_notify );
			} else {
				$schedules = $this->get_schedules_before_end_time( $remind_ending_timestamp, $reminder_intervals[ 'no-of-days' ], $total_days_to_notify );
			}

			if ( $schedules ) {
				foreach ( $schedules as $notify_time ) {
					if ( $this->create_job( $notify_time, 'notify_reminder', array(
								'balance_payable_order_id' => absint( $balance_payable_order_id ),
								'mail_template_id'         => $mail_template_id,
							) )
					) {
						$scheduled = true;
					}
				}
			}
		}

		if ( ! $scheduled ) {
			$scheduled = $this->create_job( _sumo_pp_get_timestamp(), 'notify_reminder', array(
				'balance_payable_order_id' => absint( $balance_payable_order_id ),
				'mail_template_id'         => $mail_template_id,
					) );
		}
		return $scheduled;
	}

	/**
	 * Schedule Automatic Payment
	 */
	public function schedule_automatic_pay( $balance_payable_order_id, $payment_date ) {
		//Check whether to Schedule this Cron.
		if ( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job', true, 'automatic_pay', $this->payment->id ) ) {
			return false;
		}

		$next_eligible_status = _sumo_pp_get_next_eligible_payment_failed_status( $this->payment );
		switch ( $next_eligible_status ) {
			case 'overdue':
				$charging_days       = absint( $this->payment->get_option( 'specified_overdue_days', '0' ) );
				$retry_times_per_day = absint( $this->payment->get_option( 'automatic_payment_retries_during_overdue', '2' ) );
				break;
			default:
				$charging_days       = 0;
				$retry_times_per_day = 0;
				break;
		}

		return $this->create_job( _sumo_pp_get_timestamp( $payment_date ), 'automatic_pay', array(
					'balance_payable_order_id' => absint( $balance_payable_order_id ),
					'next_eligible_status'     => $next_eligible_status,
					'charging_days'            => $charging_days,
					'retry_times_per_day'      => $retry_times_per_day,
				) );
	}

	/**
	 * Schedule Next eligible payment failed status
	 */
	public function schedule_next_eligible_payment_failed_status( $balance_payable_order_id, $next_action_on = '', $args = array() ) {

		$next_eligible_status = _sumo_pp_get_next_eligible_payment_failed_status( $this->payment, $next_action_on );
		$charging_days        = isset( $args[ 'charging_days' ] ) && is_numeric( $args[ 'charging_days' ] ) ? absint( $args[ 'charging_days' ] ) : '';
		$retry_times_per_day  = isset( $args[ 'retry_times_per_day' ] ) && is_numeric( $args[ 'retry_times_per_day' ] ) ? absint( $args[ 'retry_times_per_day' ] ) : '';

		switch ( $this->payment->get_status() ) {
			case 'pending':
			case 'in_progress':
			case 'pendng_auth':
				switch ( $next_eligible_status ) {
					case 'overdue':
						$this->schedule_overdue_notify( $balance_payable_order_id, $next_action_on, $charging_days );
						break;
					case 'await_cancl':
						$this->schedule_awaiting_cancel_notify( $balance_payable_order_id, $next_action_on );
						break;
					case 'cancelled':
						$this->schedule_cancelled_notify( $balance_payable_order_id, $next_action_on );
						break;
				}
				break;
			case 'overdue':
				switch ( $next_eligible_status ) {
					case 'await_cancl':
						if ( 'auto' === $this->payment->get_payment_mode() ) {
							$this->schedule_payment_retries( $balance_payable_order_id, $next_eligible_status, $charging_days, $retry_times_per_day );
						} else {
							$this->schedule_awaiting_cancel_notify( $balance_payable_order_id, $next_action_on );
						}
						break;
					case 'cancelled':
						if ( 'auto' === $this->payment->get_payment_mode() ) {
							$this->schedule_payment_retries( $balance_payable_order_id, $next_eligible_status, $charging_days, $retry_times_per_day );
						} else {
							$this->schedule_cancelled_notify( $balance_payable_order_id, $next_action_on );
						}
						break;
				}
				break;
		}
	}

	/**
	 * Schedule Automatic Payment retries
	 */
	public function schedule_payment_retries( $balance_payable_order_id, $next_eligible_status = '', $charging_days = 0, $retry_times_per_day = 0 ) {
		//Check whether to Schedule this Cron.
		if ( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job', true, 'retry_payment', $this->payment->id ) ) {
			return false;
		}

		$charging_days       = absint( $charging_days );
		$retry_times_per_day = absint( $retry_times_per_day );

		if ( 0 === $charging_days ) {
			return false;
		} else if ( 0 === $retry_times_per_day ) {
			return $this->create_job( _sumo_pp_get_timestamp( "+{$charging_days} days" ), "retry_payment_in_{$this->payment->get_status()}", array(
						'balance_payable_order_id' => absint( $balance_payable_order_id ),
						'next_eligible_status'     => $next_eligible_status,
						'retry_count'              => 0,
						'total_retries'            => 0,
					) );
		}

		$schedules_per_day = $this->get_schedules_per_day( $charging_days, $retry_times_per_day );

		if ( empty( $schedules_per_day ) ) {
			return false;
		}

		$total_retries = count( $schedules_per_day );
		$scheduled     = false;
		foreach ( $schedules_per_day as $retry_count => $retry_time ) {
			//may be Last retry
			if ( $total_retries - 1 === $retry_count ) {
				if ( $this->create_job( $retry_time, "retry_payment_in_{$this->payment->get_status()}", array(
							'balance_payable_order_id' => absint( $balance_payable_order_id ),
							'next_eligible_status'     => $next_eligible_status,
							'retry_count'              => 1 + $retry_count,
							'total_retries'            => $total_retries,
						) )
				) {
					$scheduled = true;
				}
			} elseif ( $this->create_job( $retry_time, "retry_payment_in_{$this->payment->get_status()}", array(
							'balance_payable_order_id' => absint( $balance_payable_order_id ),
							'next_eligible_status'     => $this->payment->get_status(),
							'retry_count'              => 1 + $retry_count,
							'total_retries'            => $total_retries,
						) )
				) {

					$scheduled = true;
			}
		}
		return $scheduled;
	}

	/**
	 * Schedule Overdue Payment
	 */
	public function schedule_overdue_notify( $balance_payable_order_id, $overdue_on, $overdue_days = '' ) {
		//Check whether to Schedule this Cron.
		if ( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job', true, 'notify_overdue', $this->payment->id ) ) {
			return false;
		}

		return $this->create_job( _sumo_pp_get_timestamp( $overdue_on ), 'notify_overdue', array(
					'balance_payable_order_id' => absint( $balance_payable_order_id ),
					'charging_days'            => is_numeric( $overdue_days ) ? absint( $overdue_days ) : absint( $this->payment->get_option( 'specified_overdue_days', '0' ) ),
				) );
	}

	/**
	 * Schedule Admin Awaiting Cancel Payment
	 */
	public function schedule_awaiting_cancel_notify( $balance_payable_order_id, $awaiting_cancel_on ) {

		//Check whether to Schedule this Cron.
		if ( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job', true, 'notify_awaiting_cancel', $this->payment->id ) ) {
			return false;
		}

		return $this->create_job( _sumo_pp_get_timestamp( $awaiting_cancel_on ), 'notify_awaiting_cancel', array(
					'balance_payable_order_id' => absint( $balance_payable_order_id ),
				) );
	}

	/**
	 * Schedule Cancelled Payment
	 */
	public function schedule_cancelled_notify( $balance_payable_order_id, $cancel_on ) {
		//Check whether to Schedule this Cron.
		if ( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job', true, 'notify_cancelled', $this->payment->id ) ) {
			return false;
		}

		return $this->create_job( _sumo_pp_get_timestamp( $cancel_on ), 'notify_cancelled', array(
					'balance_payable_order_id' => absint( $balance_payable_order_id ),
				) );
	}
}
