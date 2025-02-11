<?php
$admin_dismiss_url = add_query_arg( array( 'sumo_pp_action' => 'dismiss_background_updates_notice', 'sumo_pp_background_updates_nonce' => wp_create_nonce( 'sumo-pp-background-updates' ) ), _sumo_pp_get_current_admin_url() );
?>
<div id="message" class="updated woocommerce-message">
    <h4><?php esc_html_e( 'SUMO Payment Plans Background Process Status', 'sumopaymentplans' ); ?></h4>
    <p>
        <?php
        foreach ( $notice_updates as $action_key => $_action ) {
            switch ( $action_key ) {
                case 'product_update':
                    if ( 'completed' === $_action[ 'action_status' ] ) {
                        esc_html_e( '- The plugin settings configuration was successfully updated.', 'sumopaymentplans' );
                    } else {
                        if ( 'sumopaymentplans_find_products_to_bulk_update' === $_action[ 'current_action' ] ) {
                            esc_html_e( '- Preparing to assign the payment plans settings to selected product(s)/categories by a bulk update.', 'sumopaymentplans' );
                        } else {
                            esc_html_e( '- The plugin settings configuration is updating.', 'sumopaymentplans' );
                        }
                    }
                    break;
                case 'payment_update':
                    if ( 'completed' === $_action[ 'action_status' ] ) {
                        esc_html_e( '- Deleted product was successfully replaced with the new product in the payments.', 'sumopaymentplans' );
                    } else {
                        if ( 'sumopaymentplans_find_payments_to_bulk_update' === $_action[ 'current_action' ] ) {
                            esc_html_e( '- Preparing to replace the deleted product with the new product in the payments.', 'sumopaymentplans' );
                        } else {
                            esc_html_e( '- Replacing the deleted product with the new product in the payments.', 'sumopaymentplans' );
                        }
                    }
                    break;
            }

            if ( 'in_progress' === $_action[ 'action_status' ] ) {
                ?>
                &nbsp;<a href="<?php echo esc_url( admin_url( "admin.php?page=wc-status&tab=action-scheduler&s={$_action[ 'current_action' ]}&status=pending" ) ); ?>"><?php esc_html_e( 'View progress &rarr;', 'sumopaymentplans' ); ?></a>
                <?php
            }

            echo '<br>';
        }
        ?>
        <a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( $admin_dismiss_url ); ?>"><?php esc_html_e( 'dismiss', 'sumopaymentplans' ); ?></a>
    </p>
</div>
