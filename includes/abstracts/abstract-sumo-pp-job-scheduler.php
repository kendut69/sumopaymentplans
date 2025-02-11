<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Abstract Job Scheduler
 * 
 * @abstract SUMO_PP_Abstract_Job_Scheduler
 */
abstract class SUMO_PP_Abstract_Job_Scheduler {

	/**
	 * The Job Scheduler post ID. Each job identifier.
	 * 
	 * @var int 
	 */
	protected $job_id = 0 ;

	/**
	 * The Payment post
	 * 
	 * @var object 
	 */
	protected $payment ;

	/**
	 * Scheduled Jobs for the Payment
	 * 
	 * @var array 
	 */
	public $jobs = array() ;

	/**
	 * Post Type for Job Schedulers
	 * 
	 * @var array 
	 */
	protected $post_type = 'sumo_pp_cron_jobs' ;

	/**
	 * Job Schedulers query
	 * 
	 * @var WP_Query object 
	 */
	public $query ;

	/**
	 * Constructor.
	 */
	public function __construct( $payment ) {
		$this->populate( $payment ) ;
	}

	/**
	 * Populate the Job Scheduler
	 * 
	 * @param object $payment The Payment post
	 */
	protected function populate( $payment ) {
		if ( ! $payment ) {
			return false ;
		}

		$this->payment = $payment ;
		$this->init_job() ;
		$this->job_id  = $this->get_job_id() ;
		$this->jobs    = $this->get_jobs() ;
	}

	/**
	 * Init Job Scheduler for the Payment
	 */
	public function init_job() {
		//Fire once for the Payment
		if ( ! $this->exists() ) {
			$this->job_id = wp_insert_post( array(
				'post_type'     => $this->post_type,
				'post_status'   => 'publish',
				'post_date'     => _sumo_pp_get_date(),
				'post_date_gmt' => _sumo_pp_get_date(),
				'post_author'   => 1,
				'post_title'    => __( 'Payment Job Schedulers', 'sumopaymentplans' ),
					) ) ;

			add_post_meta( $this->job_id, '_payment_id', $this->payment->id ) ;
		}
	}

	/**
	 * Get Job Scheduler ID
	 */
	public function get_job_id() {

		if ( $this->exists() ) {
			$this->get_jobs_query() ;

			foreach ( $this->query->posts as $job ) {
				$this->job_id = $job->ID ;
				break ;
			}
		}

		return $this->job_id ;
	}

	/**
	 * Check whether Job Scheduler exists
	 * 
	 * @return boolean
	 */
	public function exists() {
		if ( ! $this->get_jobs_query() ) {
			return false ;
		}

		return $this->query->have_posts() ;
	}

	/**
	 * Get Job Schedulers WP_Query data
	 * 
	 * @return \WP_Query|boolean
	 */
	public function get_jobs_query() {
		if ( ! $this->payment ) {
			return false ;
		}

		$this->query = _sumo_pp()->query->get( array(
			'type'         => $this->post_type,
			'status'       => 'publish',
			'limit'        => 1,
			'return'       => 'q',
			'meta_key'     => '_payment_id',
			'meta_value'   => $this->payment->id,
			'meta_compare' => '=',
				) ) ;
		return $this->query ;
	}

	/**
	 * Get Cron Scheduled Jobs associated for the Payment
	 * 
	 * @return array
	 */
	public function get_jobs() {
		$jobs = get_post_meta( $this->job_id, '_scheduled_jobs', true ) ;

		if ( isset( $jobs[ $this->payment->id ] ) && is_array( $jobs[ $this->payment->id ] ) ) {
			$this->jobs = $jobs[ $this->payment->id ] ;
		}

		return $this->jobs ;
	}

	/**
	 * Create Job Scheduler to Schedule. It may be elapsed by wp_schedule_event
	 * 
	 * @param int $timestamp
	 * @param string $job_name
	 * @param array $args
	 * @return boolean true on success
	 */
	public function create_job( $timestamp, $job_name, $args = array() ) {

		if ( ! is_numeric( $timestamp ) || ! $timestamp || ! is_array( $args ) ) {
			return false ;
		}

		$new_arg    = array( absint( $timestamp ) => $args ) ;
		$this->jobs = $this->get_jobs() ;

		if ( $this->exists() ) {
			//may the Job has multiple timestamps so that we are doing this way 
			if ( isset( $this->jobs[ $job_name ] ) && is_array( $this->jobs[ $job_name ] ) ) {
				$this->jobs[ $job_name ] += $new_arg ;
			} else {
				//may the new Job is registering
				$this->jobs[ $job_name ] = $new_arg ;
			}

			if ( $this->update_jobs() ) {
				return true ;
			}
		}

		return false ;
	}

	/**
	 * Update Job Schedulers
	 * 
	 * @return boolean true on success
	 */
	public function update_jobs() {

		if ( ! is_array( $this->jobs ) ) {
			return ;
		}

		update_post_meta( $this->job_id, '_scheduled_jobs', array(
			$this->payment->id => $this->jobs,
		) ) ;

		return true ;
	}

	/**
	 * UnSchedule Job Schedulers.
	 * 
	 * @param array $jobs unsetting every Job Schedulers if left empty
	 */
	public function unset_jobs( $jobs = array() ) {

		if ( empty( $jobs ) ) {
			$jobs = _sumo_pp_get_scheduler_jobs() ;
		}

		$jobs       = ( array ) $jobs ;
		$this->jobs = $this->get_jobs() ;

		if ( $this->exists() ) {
			foreach ( $this->jobs as $job_name => $job_args ) {
				if ( in_array( $job_name, $jobs ) ) {
					unset( $this->jobs[ $job_name ] ) ;
				}
			}

			$this->update_jobs() ;
		}
	}

	public function get_next_scheduled_job( $job_name, $display = false ) {
		if ( ! isset( $this->jobs[ $job_name ] ) ) {
			return false ;
		}

		$scheduled = array_filter( array_keys( $this->jobs[ $job_name ] ) ) ;
		sort( $scheduled ) ;

		$scheduled_timestamp = current( $scheduled ) ;

		if ( ! $scheduled_timestamp ) {
			return false ;
		}

		if ( $display ) {
			return _sumo_pp_get_date_to_display( $scheduled_timestamp, 'admin' ) ;
		}

		return $scheduled_timestamp ;
	}
}
