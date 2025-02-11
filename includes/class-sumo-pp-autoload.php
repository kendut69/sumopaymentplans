<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * SUMO Payment Plans Autoloader.
 */
class SUMO_PP_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '' ;

	/**
	 * Construct SUMO_PP_Autoloader
	 */
	public function __construct() {
		$this->include_path = SUMO_PP_PLUGIN_DIR . 'includes/' ;

		spl_autoload_register( array( $this, 'autoload' ) ) ;
	}

	/**
	 * Auto-load SUMO classes on demand to reduce memory consumption.
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class ) ;

		//Make sure our classes are going to load
		if ( 0 !== strpos( $class, 'sumo_pp_' ) ) {
			return ;
		}

		$file = 'class-' . str_replace( '_', '-', $class ) . '.php' ; //Retrieve file name from class name
		$path = $this->include_path . $file ;

		if ( 0 === strpos( $class, 'sumo_pp_background_' ) ) {
			$path = $this->include_path . 'background-process/' . $file ;
		} else if ( 0 === strpos( $class, 'sumo_pp_admin_' ) ) {
			$path = $this->include_path . 'admin/' . $file ;
		} else if ( 0 === strpos( $class, 'sumo_pp_wc_' ) ) {
			$path = $this->include_path . 'log-handlers/' . $file ;
		} else if ( 0 === strpos( $class, 'sumo_pp_deprecated' ) ) {
			$path = $this->include_path . 'deprecated/' . $file ;
		}

		//Include a class file.
		if ( $path && is_readable( $path ) ) {
			include_once $path ;
		}
	}
}

new SUMO_PP_Autoloader() ;
