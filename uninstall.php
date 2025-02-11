<?php

/**
 * SUMO Payment Plans Uninstall
 *
 * Uninstalling SUMO Payment Plans deletes Cron hooks.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit ;
}

wp_clear_scheduled_hook( 'sumopaymentplans_cron_interval' ) ;
