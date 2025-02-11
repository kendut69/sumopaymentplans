<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Log Payment activities
 * 
 * @class SUMO_PP_WC_Logger
 * @package Class
 */
class SUMO_PP_WC_Logger {

	protected static $log = false ;

	/**
	 * Format the message and return the Structured data
	 *
	 * @param array|object|string $message
	 * @return JSON
	 */
	public static function format_message( $message, $map ) {
		$map_data = '' ;

		if ( $map ) {
			$map_data = json_encode( $map ) ;
		}

		return $map_data . json_encode( $message, JSON_PRETTY_PRINT ) ;
	}

	/**
	 * Save Payment Log On WooCommerce Logger
	 */
	public static function log( $message, $map = '', $source = 'sumopaymentplans-log', $level = 'info', $context = array() ) {
		$message = self::format_message( $message, $map ) ;

		if ( empty( self::$log ) ) {
			self::$log = new WC_Logger() ;
		}

		if ( empty( $context ) ) {
			$context = array( 'source' => $source, '_legacy' => true ) ;
		}

		$implements = class_implements( 'WC_Logger' ) ;

		if ( is_array( $implements ) && in_array( 'WC_Logger_Interface', $implements ) ) {
			self::$log->log( $level, $message, $context ) ;
		} else {
			self::$log->add( $source, $message ) ;
		}
	}
}
