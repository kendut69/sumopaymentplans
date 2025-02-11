<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * SUMO Payment Plans Admin
 * 
 * @class SUMO_PP_Admin
 * @package Class
 */
class SUMO_PP_Admin {

    /**
     * Init SUMO_PP_Admin.
     */
    public static function init() {
        add_action( 'init', __CLASS__ . '::includes' );
        add_action( 'admin_menu', __CLASS__ . '::admin_menus' );
        add_action( 'admin_notices', __CLASS__ . '::background_updates_notice' );
        add_action( 'admin_init', __CLASS__ . '::dismiss_background_updates_notice' );
    }

    /**
     * Include any classes we need within admin.
     */
    public static function includes() {
        include_once 'class-sumo-pp-admin-post-types.php';
        include_once 'class-sumo-pp-admin-meta-boxes.php';
        include_once 'class-sumo-pp-admin-product.php';
        include_once 'class-sumo-pp-admin-settings.php';
        include_once 'class-sumo-pp-admin-payments-exporter.php';
    }

    /**
     * Add admin menu pages.
     */
    public static function admin_menus() {
        add_menu_page( __( 'SUMO Payment Plans', 'sumopaymentplans' ), __( 'SUMO Payment Plans', 'sumopaymentplans' ), 'manage_woocommerce', 'sumopaymentplans', null, SUMO_PP_PLUGIN_URL . '/assets/images/dashicon.png', '56.5' );
        add_submenu_page( 'sumopaymentplans', __( 'Settings', 'sumopaymentplans' ), __( 'Settings', 'sumopaymentplans' ), 'manage_woocommerce', 'sumo_pp_settings', 'SUMO_PP_Admin_Settings::output' );
        add_submenu_page( 'sumopaymentplans', __( 'Payment Export', 'sumopaymentplans' ), __( 'Payment Export', 'sumopaymentplans' ), 'manage_woocommerce', SUMO_PP_Payments_Exporter::$exporter_page, 'SUMO_PP_Payments_Exporter::render_exporter_html_fields' );
    }

    /**
     * Print the background updates notice to Admin.
     */
    public static function background_updates_notice() {
        $background_updates = get_option( '_sumo_pp_background_updates', array() );
        if ( empty( $background_updates ) ) {
            return;
        }

        $notice_updates = $background_updates;
        foreach ( $background_updates as $action_key => $action ) {
            if ( ! empty( $action[ 'current_action' ] ) && is_null( WC()->queue()->get_next( $action[ 'current_action' ], null, $action[ 'action_group' ] ) ) ) {
                $notice_updates[ $action_key ][ 'current_action' ] = '';
            }

            if ( ! empty( $action[ 'next_action' ] ) && WC()->queue()->get_next( $action[ 'next_action' ], null, $action[ 'action_group' ] ) ) {
                $notice_updates[ $action_key ][ 'action_status' ]  = 'in_progress';
                $notice_updates[ $action_key ][ 'current_action' ] = $action[ 'next_action' ];
                $notice_updates[ $action_key ][ 'next_action' ]    = '';
            }

            if ( empty( $notice_updates[ $action_key ][ 'current_action' ] ) ) {
                $notice_updates[ $action_key ][ 'action_status' ]  = 'completed';
                $notice_updates[ $action_key ][ 'current_action' ] = '';
                $notice_updates[ $action_key ][ 'next_action' ]    = '';
            }
        }

        update_option( '_sumo_pp_background_updates', $notice_updates );

        if ( ! empty( $notice_updates ) ) {
            include_once('views/html-admin-background-updates-notice.php');
        }
    }

    /**
     * Redirect to advanced settings page.
     */
    public static function dismiss_background_updates_notice() {
        if ( empty( $_GET[ 'sumo_pp_action' ] ) || empty( $_GET[ 'sumo_pp_background_updates_nonce' ] ) ) {
            return;
        }

        if ( 'dismiss_background_updates_notice' === $_GET[ 'sumo_pp_action' ] && wp_verify_nonce( wc_clean( wp_unslash( $_GET[ 'sumo_pp_background_updates_nonce' ] ) ), 'sumo-pp-background-updates' ) ) {
            delete_option( '_sumo_pp_background_updates' );
            wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'sumo_pp_action', 'sumo_pp_background_updates_nonce' ) ) ) );
            exit;
        }
    }

}

SUMO_PP_Admin::init();
