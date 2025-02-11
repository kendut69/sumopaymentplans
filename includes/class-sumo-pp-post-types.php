<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Post Types
 * 
 * Registers post types
 * 
 * @class SUMO_PP_Post_Types
 * @package Class
 */
class SUMO_PP_Post_Types {

	/**
	 * Init SUMO_PP_Post_Types.
	 */
	public static function init() {
		add_action( 'init', __CLASS__ . '::register_post_types' );
		add_action( 'init', __CLASS__ . '::register_post_status' );
	}

	/**
	 * Register our custom post types.
	 */
	public static function register_post_types() {

		if ( ! post_type_exists( 'sumo_pp_payments' ) ) {
			register_post_type( 'sumo_pp_payments', array(
				'labels'          => array(
					'name'               => __( 'Payments', 'sumopaymentplans' ),
					'singular_name'      => _x( 'Payment', 'singular name', 'sumopaymentplans' ),
					'menu_name'          => _x( 'Payments', 'admin menu', 'sumopaymentplans' ),
					'add_new'            => __( 'Add payment', 'sumopaymentplans' ),
					'add_new_item'       => __( 'Add new payment', 'sumopaymentplans' ),
					'new_item'           => __( 'New payment', 'sumopaymentplans' ),
					'edit_item'          => __( 'Edit payment', 'sumopaymentplans' ),
					'view_item'          => __( 'View payment', 'sumopaymentplans' ),
					'search_items'       => __( 'Search payments', 'sumopaymentplans' ),
					'not_found'          => __( 'No payment found.', 'sumopaymentplans' ),
					'not_found_in_trash' => __( 'No payment found in trash.', 'sumopaymentplans' ),
				),
				'description'     => __( 'This is where store payments are stored.', 'sumopaymentplans' ),
				'public'          => false,
				'show_ui'         => true,
				'capability_type' => 'post',
				'show_in_menu'    => 'sumopaymentplans',
				'rewrite'         => false,
				'has_archive'     => false,
				'supports'        => false,
				'map_meta_cap'    => true,
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
			) );
		}

		if ( ! post_type_exists( 'sumo_payment_plans' ) ) {
			register_post_type( 'sumo_payment_plans', array(
				'labels'          => array(
					'name'               => __( 'Payment Plans', 'sumopaymentplans' ),
					'singular_name'      => _x( 'Payment Plan', 'singular name', 'sumopaymentplans' ),
					'menu_name'          => _x( 'Payment Plans', 'admin menu', 'sumopaymentplans' ),
					'add_new'            => __( 'Add plan', 'sumopaymentplans' ),
					'add_new_item'       => __( 'Add new plan', 'sumopaymentplans' ),
					'new_item'           => __( 'New plan', 'sumopaymentplans' ),
					'edit_item'          => __( 'Edit plan', 'sumopaymentplans' ),
					'view_item'          => __( 'View plan', 'sumopaymentplans' ),
					'search_items'       => __( 'Search plans', 'sumopaymentplans' ),
					'not_found'          => __( 'No plan found.', 'sumopaymentplans' ),
					'not_found_in_trash' => __( 'No plan found in trash.', 'sumopaymentplans' ),
				),
				'description'     => __( 'This is where payment plans are stored.', 'sumopaymentplans' ),
				'public'          => false,
				'show_ui'         => true,
				'capability_type' => 'post',
				'show_in_menu'    => 'sumopaymentplans',
				'rewrite'         => false,
				'has_archive'     => false,
				'supports'        => array( 'title' ),
				'map_meta_cap'    => true,
			) );
		}

		if ( ! post_type_exists( 'sumo_pp_cron_jobs' ) ) {
			register_post_type( 'sumo_pp_cron_jobs', array(
				'labels'              => array(
					'name'         => __( 'Cron jobs', 'sumopaymentplans' ),
					'menu_name'    => _x( 'Cron jobs', 'admin menu', 'sumopaymentplans' ),
					'search_items' => __( 'Search cron jobs', 'sumopaymentplans' ),
					'not_found'    => __( 'No cron job found.', 'sumopaymentplans' ),
				),
				'description'         => __( 'This is where scheduled cron jobs are stored.', 'sumopaymentplans' ),
				'public'              => false,
				'capability_type'     => 'post',
				'show_ui'             => apply_filters( 'sumopaymentplans_show_cron_jobs_post_type_ui', false ),
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_in_menu'        => 'sumopaymentplans',
				'hierarchical'        => false,
				'show_in_nav_menus'   => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => false,
				'has_archive'         => false,
				'map_meta_cap'        => true,
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
			) );
		}

		if ( ! post_type_exists( 'sumo_pp_masterlog' ) ) {
			register_post_type( 'sumo_pp_masterlog', array(
				'labels'          => array(
					'name'         => __( 'Master log', 'sumopaymentplans' ),
					'menu_name'    => _x( 'Master log', 'admin menu', 'sumopaymentplans' ),
					'search_items' => __( 'Search log', 'sumopaymentplans' ),
					'not_found'    => __( 'No logs found.', 'sumopaymentplans' ),
				),
				'description'     => __( 'This is where payment logs are stored.', 'sumopaymentplans' ),
				'public'          => false,
				'show_ui'         => apply_filters( 'sumopaymentplans_show_masterlog_post_type_ui', false ),
				'capability_type' => 'post',
				'show_in_menu'    => 'sumopaymentplans',
				'rewrite'         => false,
				'has_archive'     => false,
				'supports'        => false,
				'map_meta_cap'    => true,
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
			) );
		}
	}

	/**
	 * Register our custom post statuses
	 */
	public static function register_post_status() {
		$our_statuses = array(
			SUMO_PP_PLUGIN_PREFIX . 'pending'     => array(
				'label'                     => _x( 'Pending', 'payments status name', 'sumopaymentplans' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: status name */
				'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>' ),
			),
			SUMO_PP_PLUGIN_PREFIX . 'await_aprvl' => array(
				'label'                     => _x( 'Awaiting Admin Approval', 'payments status name', 'sumopaymentplans' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: status name */
				'label_count'               => _n_noop( 'Awaiting Admin Approval <span class="count">(%s)</span>', 'Awaiting Admin Approval <span class="count">(%s)</span>' ),
			),
			SUMO_PP_PLUGIN_PREFIX . 'in_progress' => array(
				'label'                     => _x( 'In Progress', 'payments status name', 'sumopaymentplans' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: status name */
				'label_count'               => _n_noop( 'In Progress <span class="count">(%s)</span>', 'In Progress <span class="count">(%s)</span>' ),
			),
			SUMO_PP_PLUGIN_PREFIX . 'completed'   => array(
				'label'                     => _x( 'Completed', 'payments status name', 'sumopaymentplans' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: status name */
				'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>' ),
			),
			SUMO_PP_PLUGIN_PREFIX . 'overdue'     => array(
				'label'                     => _x( 'Overdue', 'payments status name', 'sumopaymentplans' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: status name */
				'label_count'               => _n_noop( 'Overdue <span class="count">(%s)</span>', 'Overdue <span class="count">(%s)</span>' ),
			),
			SUMO_PP_PLUGIN_PREFIX . 'failed'      => array(
				'label'                     => _x( 'Failed', 'payments status name', 'sumopaymentplans' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: status name */
				'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>' ),
			),
			SUMO_PP_PLUGIN_PREFIX . 'await_cancl' => array(
				'label'                     => _x( 'Awaiting Cancel By Admin', 'payments status name', 'sumopaymentplans' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: status name */
				'label_count'               => _n_noop( 'Awaiting Cancel By Admin <span class="count">(%s)</span>', 'Awaiting Cancel By Admin <span class="count">(%s)</span>' ),
			),
			SUMO_PP_PLUGIN_PREFIX . 'cancelled'   => array(
				'label'                     => _x( 'Cancelled', 'payments status name', 'sumopaymentplans' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: status name */
				'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>' ),
			),
			SUMO_PP_PLUGIN_PREFIX . 'pendng_auth' => array(
				'label'                     => _x( 'Pending Authorization', 'payments status name', 'sumopaymentplans' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: status name */
				'label_count'               => _n_noop( 'Pending Authorization <span class="count">(%s)</span>', 'Pending Authorization <span class="count">(%s)</span>' ),
			),
		);

		foreach ( $our_statuses as $status => $status_display_name ) {
			register_post_status( $status, $status_display_name );
		}
	}
}

SUMO_PP_Post_Types::init();
