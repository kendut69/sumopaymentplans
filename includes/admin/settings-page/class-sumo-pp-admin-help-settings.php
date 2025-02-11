<?php

/**
 * Help Tab.
 * 
 * @class SUMO_PP_Help_Settings
 * @package Class
 */
class SUMO_PP_Help_Settings extends SUMO_PP_Abstract_Settings {

	/**
	 * SUMO_PP_Help_Settings constructor.
	 */
	public function __construct() {

		$this->id       = 'help' ;
		$this->label    = __( 'Help', 'sumopaymentplans' ) ;
		$this->settings = $this->get_settings() ;
		$this->init() ;

		add_action( 'sumopaymentplans_reset_' . $this->id, '__return_false' ) ;
		add_action( 'sumopaymentplans_submit_' . $this->id, '__return_false' ) ;
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section ;

		return apply_filters( 'sumopaymentplans_get_' . $this->id . '_settings', array(
			array(
				'name' => __( 'Documentation', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'documentation',
				'desc' => __( 'The documentation file can be found inside the documentation folder  which you will find when you unzip the downloaded zip file.', 'sumopaymentplans' ),
			),
			array(
				'name' => __( 'Help', 'sumopaymentplans' ),
				'type' => 'title',
				'id'   => $this->prefix . 'help',
				'desc' => __( 'If you need Help, please <a href="http://support.fantasticplugins.com" target="_blank" > register and open a support ticket</a>', 'sumopaymentplans' ),
			),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'help' ),
			array( 'type' => 'sectionend', 'id' => $this->prefix . 'documentation' ),
				) ) ;
	}
}

return new SUMO_PP_Help_Settings() ;
