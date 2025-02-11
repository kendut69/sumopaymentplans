<?php

defined( 'ABSPATH' ) || exit;

/**
 * Installation related functions and actions.
 * 
 * @class SUMO_PP_Install
 * @package Class
 */
class SUMO_PP_Install {

	/**
	 * Init Install.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 9 );
		add_filter( 'plugin_action_links_' . SUMO_PP_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', __CLASS__ . '::plugin_row_meta', 10, 2 );
	}

	/**
	 * Check SUMO Payment Plans version and run updater
	 */
	public static function check_version() {
		if ( get_option( SUMO_PP_PLUGIN_PREFIX . 'version' ) !== SUMO_PP_PLUGIN_VERSION ) {
			self::install();
			do_action( 'sumopaymentplans_updated' );
		}
	}

	/**
	 * Install SUMO Payment Plans.
	 */
	public static function install() {
		if ( ! defined( 'SUMO_PP_INSTALLING' ) ) {
			define( 'SUMO_PP_INSTALLING', true );
		}

		self::create_options();
		self::update_plugin_version();

		do_action( 'sumopaymentplans_installed' );
	}

	/**
	 * Is this a brand new SUMO Payment Plans install?
	 * A brand new install has no version yet.
	 *
	 * @return bool
	 */
	private static function is_new_install() {
		return is_null( get_option( SUMO_PP_PLUGIN_PREFIX . 'version', null ) );
	}

	/**
	 * Update SUMO Payment Plans version to current.
	 */
	private static function update_plugin_version() {
		delete_option( SUMO_PP_PLUGIN_PREFIX . 'version' );
		add_option( SUMO_PP_PLUGIN_PREFIX . 'version', SUMO_PP_PLUGIN_VERSION );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {
		// Include settings so that we can run through defaults.
		include_once __DIR__ . '/admin/class-sumo-pp-admin-settings.php';
		SUMO_PP_Admin_Settings::save_default_options();
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param   mixed $links Plugin Action links
	 * @return  array
	 */
	public static function plugin_action_links( $links ) {
		$setting_page_link = '<a  href="' . esc_url( admin_url( 'admin.php?page=sumo_pp_settings' ) ) . '">' . esc_html__( 'Settings', 'sumopaymentplans' ) . '</a>';
		array_unshift( $links, $setting_page_link );
		return $links;
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param   mixed $links Plugin Row Meta
	 * @param   mixed $file  Plugin Base file
	 * @return  array
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( SUMO_PP_PLUGIN_BASENAME == $file ) {
			$row_meta = array(
				'support' => '<a href="' . esc_url( 'http://fantasticplugins.com/support/' ) . '" aria-label="' . esc_attr__( 'Support', 'sumopaymentplans' ) . '">' . esc_html__( 'Support', 'sumopaymentplans' ) . '</a>',
					);

			return array_merge( $links, $row_meta );
		}

		return ( array ) $links;
	}
}

SUMO_PP_Install::init();
