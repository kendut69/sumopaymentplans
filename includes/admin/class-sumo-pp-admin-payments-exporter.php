<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle Payments Exporter.
 * 
 * @class SUMO_PP_Payments_Exporter
 * @package Class
 */
class SUMO_PP_Payments_Exporter {

	/**
	 * Exporter page.
	 *
	 * @var string
	 */
	public static $exporter_page = 'sumo-pp-payments-exporter';

	/**
	 * Init SUMO_PP_Payments_Exporter.
	 */
	public static function init() {
		add_action( 'admin_head', __CLASS__ . '::hide_from_menus' );
		add_action( 'admin_init', __CLASS__ . '::download_export_file' );
	}

	/**
	 * Get exporter page url
	 *
	 * @return string
	 */
	public static function get_exporter_page_url() {
		return admin_url( 'admin.php?page=' . self::$exporter_page );
	}

	/**
	 * Get exported data download url
	 *
	 * @param mixed $generated_data
	 * @return string
	 */
	public static function get_download_url( $generated_data ) {
		self::set_transient( $generated_data );

		return add_query_arg( array(
			'nonce'  => wp_create_nonce( 'sumo-pp-payments-exporter' ),
			'action' => 'download_payments_csv',
				), self::get_exporter_page_url() );
	}

	/**
	 * Save expoted data as transient
	 */
	public static function set_transient( $generated_data ) {
		delete_transient( '_sumo_pp_exported_data' );
		set_transient( '_sumo_pp_exported_data', is_array( $generated_data ) ? $generated_data : array(), 60 );
	}

	/**
	 * Export page UI.
	 */
	public static function render_exporter_html_fields() {
		include 'views/html-admin-payment-exporter.php';
	}

	/**
	 * Generate the CSV file.
	 */
	public static function download_export_file() {
		if (
				! isset( $_GET[ 'action' ], $_GET[ 'nonce' ] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET[ 'nonce' ] ) ), 'sumo-pp-payments-exporter' ) ||
				'download_payments_csv' !== sanitize_title( wp_unslash( $_GET[ 'action' ] ) )
		) {
			return;
		}

		$field_datas = get_transient( '_sumo_pp_exported_data' );

		ob_end_clean();
		header( 'Content-type: text/csv' );
		header( 'Content-Disposition: attachment; filename=sumo-payments-' . date_i18n( 'Y-m-d H:i:s' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$handle        = fopen( 'php://output', 'w' );
		$delimiter     = apply_filters( 'sumopaymentplans_export_csv_delimiter', ',' );
		$enclosure     = apply_filters( 'sumopaymentplans_export_csv_enclosure', '"' );
		$field_heading = apply_filters( 'sumopaymentplans_export_csv_headings', array(
			__( 'Payment Status', 'sumopaymentplans' ),
			__( 'Payment Identification Number', 'sumopaymentplans' ),
			__( 'Product Name', 'sumopaymentplans' ),
			__( 'Order ID', 'sumopaymentplans' ),
			__( 'Buyer Email', 'sumopaymentplans' ),
			__( 'Billing Name', 'sumopaymentplans' ),
			__( 'Payment Type', 'sumopaymentplans' ),
			__( 'Payment Plan', 'sumopaymentplans' ),
			__( 'Remaining Installments', 'sumopaymentplans' ),
			__( 'Remaining Payable Amount', 'sumopaymentplans' ),
			__( 'Next Installment Amount', 'sumopaymentplans' ),
			__( 'Payment Start Date', 'sumopaymentplans' ),
			__( 'Next Payment Date', 'sumopaymentplans' ),
			__( 'Payment Ending Date', 'sumopaymentplans' ),
			__( 'Previous Payment Date', 'sumopaymentplans' ),
				) );
		$field_datas   = apply_filters( 'sumopaymentplans_export_csv_field_datas', $field_datas );

		fputcsv( $handle, $field_heading, $delimiter, $enclosure ); // here you can change delimiter/enclosure

		if ( is_array( $field_datas ) && $field_datas ) {
			foreach ( $field_datas as $field_data ) {
				fputcsv( $handle, $field_data, $delimiter, $enclosure ); // here you can change delimiter/enclosure
			}
		}

		fclose( $handle );
		exit();
	}

	/**
	 * Hide menu items from view so the pages exist, but the menu items do not.
	 */
	public static function hide_from_menus() {
		global $submenu;

		if ( isset( $submenu[ 'sumopaymentplans' ] ) ) {
			foreach ( $submenu[ 'sumopaymentplans' ] as $key => $menu ) {
				if ( self::$exporter_page === $menu[ 2 ] ) {
					unset( $submenu[ 'sumopaymentplans' ][ $key ] );
				}
			}
		}
	}

	/**
	 * Generate data to export.
	 *
	 * @param mixed $data
	 * @return array
	 */
	public static function generate_data( $data ) {
		if ( ! $data ) {
			return array();
		}

		$payment = _sumo_pp_get_payment( $data );
		if ( ! $payment ) {
			return array();
		}

		$initial_payment_order = _sumo_pp_maybe_get_order_instance( $payment->get_initial_payment_order_id() );

		return apply_filters( 'sumopaymentplans_export_csv_data', array(
			esc_attr( $payment->get_status_label() ),
			'#' . $payment->get_payment_number(),
			$payment->get_formatted_product_name( array( 'esc_html' => true ) ),
			$payment->get_initial_payment_order_id(),
			$payment->get_customer_email(),
			$initial_payment_order ? ( $initial_payment_order->get_billing_first_name() . ' ' . $initial_payment_order->get_billing_last_name() ) : 'N/A',
			$payment->get_payment_type( true ),
			'payment-plans' === $payment->get_payment_type() ? $payment->get_plan( 'name' ) : '--',
			$payment->get_prop( 'remaining_installments' ),
			$payment->get_prop( 'remaining_payable_amount' ) . ( $initial_payment_order ? ' ' . $initial_payment_order->get_currency() : '' ),
			$payment->get_prop( 'next_installment_amount' ) . ( $initial_payment_order ? ' ' . $initial_payment_order->get_currency() : '' ),
			$payment->get_prop( 'payment_start_date' ) ? _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_start_date' ), 'admin' ) : '--',
			$payment->get_prop( 'next_payment_date' ) ? _sumo_pp_get_date_to_display( $payment->get_prop( 'next_payment_date' ), 'admin' ) : '--',
			'payment-plans' === $payment->get_payment_type() && $payment->get_prop( 'payment_end_date' ) ? _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_end_date' ), 'admin' ) : '--',
			$payment->get_prop( 'last_payment_date' ) ? _sumo_pp_get_date_to_display( $payment->get_prop( 'last_payment_date' ), 'admin' ) : '--',
				), $payment, $initial_payment_order );
	}
}

SUMO_PP_Payments_Exporter::init();
