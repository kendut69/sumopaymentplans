<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 * 
 * @class SUMO_PP_Privacy
 * @package Class
 */
class SUMO_PP_Privacy {

	/**
	 * This is a list of exporters.
	 *
	 * @var array
	 */
	protected static $exporters = array() ;

	/**
	 * This is a list of erasers.
	 *
	 * @var array
	 */
	protected static $erasers = array() ;

	/**
	 * Limit background process to number of batches to avoid timeouts
	 *
	 * @var int 
	 */
	protected static $batch_limit = 10 ;

	/**
	 * Force erase personal data from user.
	 *
	 * @var bool 
	 */
	protected static $force_erase_personal_data = false ;

	/**
	 * Init SUMO_PP_Privacy.
	 */
	public static function init() {
		self::$force_erase_personal_data = 'yes' === get_option( SUMO_PP_PLUGIN_PREFIX . 'erasure_request_removes_payment_data', 'no' ) ;

		add_action( 'admin_init', __CLASS__ . '::add_privacy_message' ) ;

		self::add_exporter( 'sumopaymentplans-customer-payments', __( 'Customer Payments', 'sumopaymentplans' ), __CLASS__ . '::payment_data_exporter' ) ;
		self::add_eraser( 'sumopaymentplans-customer-payments', __( 'Customer Payments', 'sumopaymentplans' ), __CLASS__ . '::payment_data_eraser' ) ;
		self::add_eraser( 'sumopaymentplans-customer-payment-logs', __( 'Customer Payment Logs', 'sumopaymentplans' ), __CLASS__ . '::payment_log_data_eraser' ) ;

		add_filter( 'wp_privacy_personal_data_exporters', __CLASS__ . '::register_exporters', 6 ) ;
		add_filter( 'wp_privacy_personal_data_erasers', __CLASS__ . '::register_erasers' ) ;

		//Prevent payment order from WP data erasure
		add_filter( 'woocommerce_privacy_erase_order_personal_data', __CLASS__ . '::prevent_payment_order_from_erasure', 99, 2 ) ;

		//Add the following hooks when the corresponding hook named 'woocommerce_cleanup_personal_data' is fired
		add_filter( 'woocommerce_trash_pending_orders_query_args', __CLASS__ . '::prevent_payment_orders_from_anonymization', 99, 2 ) ;
		add_filter( 'woocommerce_trash_failed_orders_query_args', __CLASS__ . '::prevent_payment_orders_from_anonymization', 99, 2 ) ;
		add_filter( 'woocommerce_trash_cancelled_orders_query_args', __CLASS__ . '::prevent_payment_orders_from_anonymization', 99, 2 ) ;
		add_filter( 'woocommerce_anonymize_completed_orders_query_args', __CLASS__ . '::prevent_payment_orders_from_anonymization', 99, 2 ) ;
	}

	/**
	 * Get plugin name
	 * 
	 * @return string
	 */
	public static function get_plugin_name() {
		$plugin = get_plugin_data( SUMO_PP_PLUGIN_FILE ) ;
		return $plugin[ 'Name' ] ;
	}

	/**
	 * Adds the privacy message on SUMO PaymentPlans privacy page.
	 */
	public static function add_privacy_message() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = self::get_privacy_message() ;

			if ( $content ) {
				wp_add_privacy_policy_content( self::get_plugin_name(), $content ) ;
			}
		}
	}

	/**
	 * Integrate this exporter implementation within the WordPress core exporters.
	 *
	 * @param array $exporters List of exporter callbacks.
	 * @return array
	 */
	public static function register_exporters( $exporters = array() ) {
		foreach ( self::$exporters as $id => $exporter ) {
			$exporters[ $id ] = $exporter ;
		}

		return $exporters ;
	}

	/**
	 * Integrate this eraser implementation within the WordPress core erasers.
	 *
	 * @param array $erasers List of eraser callbacks.
	 * @return array
	 */
	public static function register_erasers( $erasers = array() ) {
		foreach ( self::$erasers as $id => $eraser ) {
			$erasers[ $id ] = $eraser ;
		}

		return $erasers ;
	}

	/**
	 * Add exporter to list of exporters.
	 *
	 * @param string $id       ID of the Exporter.
	 * @param string $name     Exporter name.
	 * @param string $callback Exporter callback.
	 */
	public static function add_exporter( $id, $name, $callback ) {
		self::$exporters[ $id ] = array(
			'exporter_friendly_name' => $name,
			'callback'               => $callback,
				) ;

		return self::$exporters ;
	}

	/**
	 * Add eraser to list of erasers.
	 *
	 * @param string $id       ID of the Eraser.
	 * @param string $name     Exporter name.
	 * @param string $callback Exporter callback.
	 */
	public static function add_eraser( $id, $name, $callback ) {
		self::$erasers[ $id ] = array(
			'eraser_friendly_name' => $name,
			'callback'             => $callback,
				) ;

		return self::$erasers ;
	}

	/**
	 * Add privacy policy content for the privacy policy page.
	 */
	public static function get_privacy_message() {
		ob_start() ;
		?>
		<p>
			<?php esc_html_e( 'This includes the basics of what personal data your store may be collecting, storing and sharing. Depending on what settings are enabled and which additional plugins are used, the specific information shared by your store will vary.', 'sumopaymentplans' ) ; ?>
		</p>
		<h2><?php esc_html_e( 'What the Plugin does', 'sumopaymentplans' ) ; ?></h2>
		<p>
			<?php esc_html_e( 'Using this plugin, you can create and sell deposit payments, installment payments, etc on your WooCommerce shop.', 'sumopaymentplans' ) ; ?>
		</p>
		<h2><?php esc_html_e( 'What we collect and share', 'sumopaymentplans' ) ; ?></h2>
		<h2><?php esc_html_e( 'Email ID', 'sumopaymentplans' ) ; ?></h2>
		<ul>
			<li>
				<?php esc_html_e( '- Used for tracking the user', 'sumopaymentplans' ) ; ?>
			</li>
			<li>
				<?php esc_html_e( '- Used for sending payment plan emails to the user', 'sumopaymentplans' ) ; ?>
			</li>
		</ul>
		<h2><?php esc_html_e( 'User ID', 'sumopaymentplans' ) ; ?></h2>
		<ul>
			<li>
				<?php esc_html_e( '- Used for tracking the previous purchase of the user', 'sumopaymentplans' ) ; ?>
			</li>            
		</ul>
		<h2><?php esc_html_e( 'User Object', 'sumopaymentplans' ) ; ?></h2>
		<ul>
			<li>
				<?php esc_html_e( '- Used as a backup for future use', 'sumopaymentplans' ) ; ?>
			</li>            
		</ul>
		<h2><?php esc_html_e( 'User Name', 'sumopaymentplans' ) ; ?></h2>
		<ul>
			<li>
				<?php esc_html_e( '- Used in payment plan logs', 'sumopaymentplans' ) ; ?>
			</li>            
		</ul>
		<?php
		return apply_filters( 'sumopaymentplans_privacy_policy_content', ob_get_clean() ) ;
	}

	/**
	 * Prevent payment order from force erasure of order by WordPress data erasure.
	 * 
	 * @param bool $erasure_enabled
	 * @param WC_Order object $order
	 * @return bool false to prevent payment order
	 */
	public static function prevent_payment_order_from_erasure( $erasure_enabled, $order ) {
		if ( ! self::$force_erase_personal_data && _sumo_pp_is_payment_order( $order ) ) {
			return false ;
		}

		return $erasure_enabled ;
	}

	/**
	 * For a given query, prevent payment orders from anonymization of orders.
	 *
	 * @param array $query_args Query.
	 * @return array
	 */
	public static function prevent_payment_orders_from_anonymization( $query_args ) {
		if ( ! is_array( $query_args ) || ! $query_args ) {
			return $query_args ;
		}

		$query_args[ 'meta_key' ]     = 'is_sumo_pp_order' ;
		$query_args[ 'meta_compare' ] = 'NOT EXISTS' ;

		return $query_args ;
	}

	/**
	 * Finds and exports data which could be used to identify a person from SUMO PaymentPlans data associated with an email address.
	 *
	 * Payments are exported in blocks of 10 to avoid timeouts.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function payment_data_exporter( $email_address, $page ) {
		$done           = false ;
		$data_to_export = array() ;
		$user           = get_user_by( 'email', $email_address ) ; // Check if user has an ID in the DB to load stored personal data.

		if ( $user instanceof WP_User ) {
			$payments = _sumo_pp()->query->get( array(
				'type'       => 'sumo_pp_payments',
				'status'     => array_keys( _sumo_pp_get_payment_statuses() ),
				'limit'      => self::$batch_limit,
				'page'       => absint( $page ),
				'meta_key'   => '_customer_email',
				'meta_value' => $email_address,
					) ) ;

			if ( 0 < count( $payments ) ) {
				foreach ( $payments as $payment_id ) {
					$payment          = _sumo_pp_get_payment( $payment_id ) ;
					$data_to_export[] = array(
						'group_id'    => 'sumo_payment_plans',
						'group_label' => __( 'SUMO Payment Plans', 'sumopaymentplans' ),
						'item_id'     => "payment-{$payment->id}",
						'data'        => self::get_payment_personal_data( $payment ),
							) ;
				}
				$done = 10 > count( $payments ) ;
			} else {
				$done = true ;
			}
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
				) ;
	}

	/**
	 * Finds and erases data which could be used to identify a person from SUMO PaymentPlans data assocated with an email address.
	 *
	 * Payments are erased in blocks of 10 to avoid timeouts.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function payment_data_eraser( $email_address, $page ) {
		$user     = get_user_by( 'email', $email_address ) ; // Check if user has an ID in the DB to load stored personal data.
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
				) ;

		if ( $user instanceof WP_User ) {
			$payments = _sumo_pp()->query->get( array(
				'type'       => 'sumo_pp_payments',
				'status'     => array_keys( _sumo_pp_get_payment_statuses() ),
				'limit'      => self::$batch_limit,
				'page'       => absint( $page ),
				'meta_key'   => '_customer_email',
				'meta_value' => $email_address,
					) ) ;

			if ( 0 < count( $payments ) ) {
				foreach ( $payments as $payment_id ) {
					$payment = _sumo_pp_get_payment( $payment_id ) ;

					if ( apply_filters( 'sumopaymentplans_privacy_erase_payment_personal_data', self::$force_erase_personal_data, $payment ) ) {
						self::remove_payment_personal_data( $payment ) ;

						/* Translators: %s Payment number. */
						$response[ 'messages' ][]    = sprintf( __( 'Removed personal data from payment %s.', 'sumopaymentplans' ), $payment ) ;
						$response[ 'items_removed' ] = true ;
					} else {
						/* Translators: %s Payment number. */
						$response[ 'messages' ][]     = sprintf( __( 'Personal data within payment %s has been retained.', 'sumopaymentplans' ), $payment ) ;
						$response[ 'items_retained' ] = true ;
					}
				}
				$response[ 'done' ] = 10 > count( $payments ) ;
			} else {
				$response[ 'done' ] = true ;
			}
		}

		return $response ;
	}

	/**
	 * Finds and erases data which could be used to identify a person from SUMO PaymentPlans log data assocated with an email address.
	 *
	 * Payment Logs are erased in blocks of 10 to avoid timeouts.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function payment_log_data_eraser( $email_address, $page ) {
		$user     = get_user_by( 'email', $email_address ) ; // Check if user has an ID in the DB to load stored personal data.
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
				) ;

		if ( $user instanceof WP_User ) {
			$payment_logs = _sumo_pp()->query->get( array(
				'type'       => 'sumo_pp_masterlog',
				'status'     => 'publish',
				'limit'      => self::$batch_limit,
				'page'       => absint( $page ),
				'meta_key'   => '_user_name',
				'meta_value' => $user->display_name,
					) ) ;

			if ( 0 < count( $payment_logs ) ) {
				foreach ( $payment_logs as $log_id ) {
					if ( apply_filters( 'sumopaymentplans_privacy_erase_payment_log_personal_data', self::$force_erase_personal_data, $log_id ) ) {
						self::remove_payment_log_personal_data( $log_id ) ;

						/* Translators: %s Payment log id. */
						$response[ 'messages' ][]    = sprintf( __( 'Removed personal data from payment log %s.', 'sumopaymentplans' ), $log_id ) ;
						$response[ 'items_removed' ] = true ;
					} else {
						/* Translators: %s Payment log id. */
						$response[ 'messages' ][]     = sprintf( __( 'Personal data within payment log %s has been retained.', 'sumopaymentplans' ), $log_id ) ;
						$response[ 'items_retained' ] = true ;
					}
				}
				$response[ 'done' ] = 10 > count( $payment_logs ) ;
			} else {
				$response[ 'done' ] = true ;
			}
		}

		return $response ;
	}

	/**
	 * Get personal data (key/value pairs) for an Payment.
	 *
	 * @param object $payment Payment post.
	 * @return array
	 */
	public static function get_payment_personal_data( $payment ) {
		$personal_data   = array() ;
		$props_to_export = apply_filters( 'sumopaymentplans_privacy_export_payment_personal_data_props', array(
			'payment_number'           => __( 'Payment Identification Number', 'sumopaymentplans' ),
			'product_name'             => __( 'Product Name', 'sumopaymentplans' ),
			'payment_type'             => __( 'Payment Type', 'sumopaymentplans' ),
			'payment_plan'             => __( 'Payment Plan', 'sumopaymentplans' ),
			'payment_start_date'       => __( 'Payment Start Date', 'sumopaymentplans' ),
			'payment_ending_date'      => __( 'Payment Ending Date', 'sumopaymentplans' ),
			'next_payment_date'        => __( 'Next Payment Date', 'sumopaymentplans' ),
			'remaining_installments'   => __( 'Remaining Installments', 'sumopaymentplans' ),
			'total_payable_amount'     => __( 'Total Payable Amount', 'sumopaymentplans' ),
			'remaining_payable_amount' => __( 'Remaining Payable Amount', 'sumopaymentplans' ),
			'next_installment_amount'  => __( 'Next Installment Amount', 'sumopaymentplans' ),
			'customer_email'           => __( 'Customer Email Address', 'sumopaymentplans' ),
				), $payment ) ;

		foreach ( $props_to_export as $prop => $name ) {
			$value = '' ;

			switch ( $prop ) {
				case 'product_name':
					$value = $payment->get_formatted_product_name() ;
					break ;
				case 'payment_type':
					$value = $payment->get_payment_type( true ) ;
					break ;
				case 'payment_plan':
					if ( 'payment-plans' === $payment->get_payment_type() ) {
						$value = $payment->get_plan( 'name' ) ;
					} else {
						$value = '--' ;
					}
					break ;
				case 'payment_start_date':
					$value = $payment->get_prop( 'payment_start_date' ) ? _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_start_date' ), 'admin' ) : '--' ;
					break ;
				case 'payment_ending_date':
					if ( 'payment-plans' === $payment->get_payment_type() ) {
						$value = $payment->get_prop( 'payment_end_date' ) ? _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_end_date' ), 'admin' ) : '--' ;
					} else {
						$value = '--' ;
					}
					break ;
				case 'next_payment_date':
					if ( $payment->get_prop( 'next_payment_date' ) ) {
						$value = _sumo_pp_get_date_to_display( $payment->get_prop( 'next_payment_date' ), 'admin' ) ;
					} else {
						$value = '--' ;
					}
					break ;
				case 'total_payable_amount':
					$value = wc_format_decimal( $payment->get_total_payable_amount() ) ;
					break ;
				case 'remaining_payable_amount':
					$value = wc_format_decimal( $payment->get_prop( 'remaining_payable_amount' ) ) ;
					break ;
				case 'next_installment_amount':
					$value = wc_format_decimal( $payment->get_prop( 'next_installment_amount' ) ) ;
					break ;
				default:
					$value = $payment->get_prop( "{$prop}" ) ;
					break ;
			}

			$value = apply_filters( 'sumopaymentplans_privacy_export_payment_personal_data_prop', $value, $prop, $payment ) ;

			if ( $value ) {
				$personal_data[] = array(
					'name'  => $name,
					'value' => $value,
						) ;
			}
		}

		/**
		 * Allow extensions to register their own personal data for this payment for the export.
		 *
		 * @param array $personal_data Array of name value pairs to expose in the export.
		 * @param object $payment
		 */
		$personal_data = apply_filters( 'sumopaymentplans_privacy_export_payment_personal_data', $personal_data, $payment ) ;

		return $personal_data ;
	}

	/**
	 * Remove personal data specific to Payment.
	 * 
	 * @param object $payment Payment post.
	 */
	public static function remove_payment_personal_data( $payment ) {
		$anonymized_data = array() ;

		/**
		 * Allow extensions to remove their own personal data for this payment first, so payment data is still available.
		 */
		do_action( 'sumopaymentplans_privacy_before_remove_payment_personal_data', $payment ) ;

		/**
		 * Expose props and data types we'll be anonymizing.
		 */
		$props_to_remove = apply_filters( 'sumopaymentplans_privacy_remove_payment_personal_data_props', array(
			'customer_email' => 'email',
			'get_customer'   => 'object',
				), $payment ) ;

		if ( ! empty( $props_to_remove ) && is_array( $props_to_remove ) ) {
			foreach ( $props_to_remove as $prop => $data_type ) {
				// Get the current value.
				$value = $payment->get_prop( "{$prop}" ) ;

				// If the value is empty, it does not need to be anonymized.
				if ( empty( $value ) || empty( $data_type ) ) {
					continue ;
				}

				$anon_value = function_exists( 'wp_privacy_anonymize_data' ) ? wp_privacy_anonymize_data( $data_type, $value ) : '' ;

				/**
				 * Expose a way to control the anonymized value of a prop via 3rd party code.
				 */
				$anonymized_data[ $prop ] = apply_filters( 'sumopaymentplans_privacy_remove_payment_personal_data_prop_value', $anon_value, $prop, $value, $data_type, $payment ) ;
			}
		}

		$notes = $payment->get_payment_notes() ;
		foreach ( $notes as $note ) {
			wp_delete_comment( $note->id, true ) ;
		}

		//Cancel anonymized payments
		$payment->cancel_payment( array(
			'content' => __( 'Personal data removed.', 'sumopaymentplans' ),
			'status'  => 'success',
			'message' => __( 'Anonymized', 'sumopaymentplans' ),
		) ) ;

		// Set all new props and persist the new data to the database.
		foreach ( $anonymized_data as $prop => $anon_value ) {
			if ( $anon_value ) {
				update_post_meta( $payment->id, "_{$prop}", $anon_value ) ;
			} else {
				delete_post_meta( $payment->id, "_{$prop}" ) ;
			}
		}

		update_post_meta( $payment->id, '_anonymized', 'yes' ) ;

		/**
		 * Allow extensions to remove their own personal data for this payment.
		 */
		do_action( 'sumopaymentplans_privacy_remove_payment_personal_data', $payment ) ;
	}

	/**
	 * Remove personal data specific to Payment log.
	 * 
	 * @param int $log_id Payment log post ID.
	 */
	public static function remove_payment_log_personal_data( $log_id ) {
		$anonymized_data = array() ;

		/**
		 * Allow extensions to remove their own personal data for this payment log first, so payment log data is still available.
		 */
		do_action( 'sumopaymentplans_privacy_before_remove_payment_log_personal_data', $log_id ) ;

		/**
		 * Expose props and data types we'll be anonymizing.
		 */
		$props_to_remove = apply_filters( 'sumopaymentplans_privacy_remove_payment_log_personal_data_props', array(
			'user_name' => 'text',
				), $log_id ) ;

		if ( ! empty( $props_to_remove ) && is_array( $props_to_remove ) ) {
			foreach ( $props_to_remove as $prop => $data_type ) {
				// Get the current value.
				$value = get_post_meta( $log_id, "_{$prop}", true ) ;

				// If the value is empty, it does not need to be anonymized.
				if ( empty( $value ) || empty( $data_type ) ) {
					continue ;
				}

				$anon_value = function_exists( 'wp_privacy_anonymize_data' ) ? wp_privacy_anonymize_data( $data_type, $value ) : '' ;

				/**
				 * Expose a way to control the anonymized value of a prop via 3rd party code.
				 */
				$anonymized_data[ $prop ] = apply_filters( 'sumopaymentplans_privacy_remove_payment_log_personal_data_prop_value', $anon_value, $prop, $value, $data_type, $log_id ) ;
			}
		}

		// Set all new props and persist the new data to the database.
		foreach ( $anonymized_data as $prop => $anon_value ) {
			if ( $anon_value ) {
				update_post_meta( $log_id, "_{$prop}", $anon_value ) ;
			} else {
				delete_post_meta( $log_id, "_{$prop}" ) ;
			}
		}

		update_post_meta( $log_id, '_anonymized', 'yes' ) ;

		/**
		 * Allow extensions to remove their own personal data for this payment log.
		 */
		do_action( 'sumopaymentplans_privacy_remove_payment_log_personal_data', $log_id ) ;
	}
}

SUMO_PP_Privacy::init() ;
