jQuery( function( $ ) {
    $( '#woocommerce_sumo_pp_paypal_reference_txns_testmode' ).change( function() {
        if ( this.checked ) {
            $( '#woocommerce_sumo_pp_paypal_reference_txns_sandbox_api_username' ).closest( 'tr' ).show();
            $( '#woocommerce_sumo_pp_paypal_reference_txns_sandbox_api_password' ).closest( 'tr' ).show();
            $( '#woocommerce_sumo_pp_paypal_reference_txns_sandbox_api_signature' ).closest( 'tr' ).show();
            $( '#woocommerce_sumo_pp_paypal_reference_txns_api_username' ).closest( 'tr' ).hide();
            $( '#woocommerce_sumo_pp_paypal_reference_txns_api_password' ).closest( 'tr' ).hide();
            $( '#woocommerce_sumo_pp_paypal_reference_txns_api_signature' ).closest( 'tr' ).hide();
        } else {
            $( '#woocommerce_sumo_pp_paypal_reference_txns_api_username' ).closest( 'tr' ).show();
            $( '#woocommerce_sumo_pp_paypal_reference_txns_api_password' ).closest( 'tr' ).show();
            $( '#woocommerce_sumo_pp_paypal_reference_txns_api_signature' ).closest( 'tr' ).show();
            $( '#woocommerce_sumo_pp_paypal_reference_txns_sandbox_api_username' ).closest( 'tr' ).hide();
            $( '#woocommerce_sumo_pp_paypal_reference_txns_sandbox_api_password' ).closest( 'tr' ).hide();
            $( '#woocommerce_sumo_pp_paypal_reference_txns_sandbox_api_signature' ).closest( 'tr' ).hide();
        }
    } ).change();
} );
