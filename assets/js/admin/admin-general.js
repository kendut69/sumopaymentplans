/* global ajaxurl, sumo_pp_admin_general_params */

jQuery( function( $ ) {
    $( '#_sumo_pp_deposit_type' ).change( function() {
        $( '#_sumo_pp_min_deposit' ).closest( 'tr' ).hide();
        $( '#_sumo_pp_max_deposit' ).closest( 'tr' ).hide();
        $( '#_sumo_pp_deposit_price_type' ).closest( 'tr' ).show();
        $( '#_sumo_pp_fixed_deposit_price' ).closest( 'tr' ).hide();
        $( '#_sumo_pp_fixed_deposit_percent' ).closest( 'tr' ).hide();

        if ( 'user-defined' === this.value ) {
            $( '#_sumo_pp_min_deposit' ).closest( 'tr' ).show();
            $( '#_sumo_pp_max_deposit' ).closest( 'tr' ).show();
            $( '#_sumo_pp_deposit_price_type' ).closest( 'tr' ).hide();
        } else {
            $( '#_sumo_pp_deposit_price_type' ).change();
        }
    } ).change();

    $( '#_sumo_pp_deposit_price_type' ).change( function() {
        $( '#_sumo_pp_fixed_deposit_percent' ).closest( 'tr' ).hide();
        $( '#_sumo_pp_fixed_deposit_price' ).closest( 'tr' ).hide();

        if ( 'fixed-price' === this.value ) {
            $( '#_sumo_pp_fixed_deposit_price' ).closest( 'tr' ).show();
        } else {
            $( '#_sumo_pp_fixed_deposit_percent' ).closest( 'tr' ).show();
        }
    } ).change();

    $( '#_sumo_pp_products_that_can_be_placed_in_an_order' ).change( function() {
        $( '#_sumo_pp_charge_shipping_during' ).closest( 'tr' ).hide();
        $( '#_sumo_pp_allow_payment_plans_in_cart_as' ).closest( 'tr' ).show();

        if ( 'single-payment' === this.value ) {
            $( '#_sumo_pp_charge_shipping_during' ).closest( 'tr' ).show();
            $( '#_sumo_pp_allow_payment_plans_in_cart_as' ).closest( 'tr' ).hide();
        }
    } ).change();

    $( '#_sumo_pp_show_deposit_r_payment_plans_for' ).change( function() {
        $( '#_sumo_pp_get_limited_userroles_of_payment_product' ).closest( 'tr' ).hide();
        $( '#_sumo_pp_get_limited_users_of_payment_product' ).closest( 'tr' ).hide();

        if ( $.inArray( this.value, Array( 'include_users', 'exclude_users' ) ) !== - 1 ) {
            $( '#_sumo_pp_get_limited_users_of_payment_product' ).closest( 'tr' ).show();
        } else if ( $.inArray( this.value, Array( 'include_user_role', 'exclude_user_role' ) ) !== - 1 ) {
            $( '#_sumo_pp_get_limited_userroles_of_payment_product' ).closest( 'tr' ).show();
        }
    } ).change();

    $( '#_sumo_pp_payment_plan_add_to_cart_via_href' ).change( function() {
        $( '#_sumo_pp_after_hyperlink_clicked_redirect_to' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#_sumo_pp_after_hyperlink_clicked_redirect_to' ).closest( 'tr' ).show();
        }
    } ).change();

    $( '#_sumo_pp_automatic_payment_gateway_mode' ).change( function() {
        $( '#_sumo_pp_enable_manual_payment_gateways' ).closest( 'tr' ).hide();

        if ( 'auto-or-manual' === this.value || 'force-manual' === this.value ) {
            $( '#_sumo_pp_enable_manual_payment_gateways' ).closest( 'tr' ).show();
        }
    } );

    $( '#_sumo_pp_enable_automatic_payment_gateways' ).change( function() {
        $( '#_sumo_pp_automatic_payment_gateway_mode' ).closest( 'tr' ).hide();
        $( '#_sumo_pp_enable_manual_payment_gateways' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#_sumo_pp_automatic_payment_gateway_mode' ).closest( 'tr' ).show();
            $( '#_sumo_pp_automatic_payment_gateway_mode' ).change();
        }
    } ).change();

    $( '#_sumo_pp_show_payment_gateways_when_order_amt_zero' ).change( function() {
        $( '#_sumo_pp_gateways_to_hide_when_order_amt_zero' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#_sumo_pp_gateways_to_hide_when_order_amt_zero' ).closest( 'tr' ).show();
        }
    } ).change();

    $( '#_sumo_pp_show_timezone_in' ).change( function() {
        if ( 'wordpress' === this.value ) {
            $( this ).closest( 'td' ).find( '.description' ).show();
        } else {
            $( this ).closest( 'td' ).find( '.description' ).hide();
        }
    } ).change();

    var paymentPlansSelector = {
        init : function() {
            $( document ).on( 'click', '#_sumo_pp_add_col_1_plan', this.onClickAddColumn1Plan );
            $( document ).on( 'click', '#_sumo_pp_add_col_2_plan', this.onClickAddColumn2Plan );
            $( 'table._sumo_pp_selected_plans' ).on( 'click', 'a.remove_row', this.onClickRemovePlan );
        },
        onClickAddColumn1Plan : function( evt ) {
            evt.stopImmediatePropagation();
            evt.preventDefault();
            paymentPlansSelector.addPlanSearchField( $( evt.currentTarget ), 'col_1' );
        },
        onClickAddColumn2Plan : function( evt ) {
            evt.stopImmediatePropagation();
            evt.preventDefault();
            paymentPlansSelector.addPlanSearchField( $( evt.currentTarget ), 'col_2' );
        },
        onClickRemovePlan : function( evt ) {
            $( this ).closest( 'tr' ).remove();
            return false;
        },
        addPlanSearchField : function( $this, col ) {
            var rowID = $( 'table._sumo_pp_selected_col_' + col + '_plans' ).find( 'tbody tr' ).length;

            $this.closest( 'span' ).find( '.spinner' ).addClass( 'is-active' );
            $.ajax( {
                type : 'POST',
                url : ajaxurl,
                data : {
                    action : '_sumo_pp_get_payment_plan_search_field',
                    security : sumo_pp_admin_general_params.plan_search_nonce,
                    rowID : rowID,
                    col : col,
                },
                success : function( data ) {
                    if ( typeof data !== 'undefined' ) {
                        $( '<tr><td class="sort" width="1%"></td>\n\
                                    <td>' + data.search_field + '</td><td><a href="#" class="remove_row button">X</a></td>\n\
                                    </tr>' ).appendTo( $( 'table._sumo_pp_selected_col_' + col + '_plans' ).find( 'tbody' ) );
                        $( document.body ).trigger( 'wc-enhanced-select-init' );
                    }
                },
                complete : function() {
                    $this.closest( 'span' ).find( '.spinner' ).removeClass( 'is-active' );
                }
            } );
            return false;
        },
        hide : function() {
            $( '._sumo_pp_add_plans' ).closest( 'tr' ).hide();
            $( '._sumo_pp_selected_plans' ).hide();
        },
        show : function() {
            $( '._sumo_pp_add_plans' ).closest( 'tr' ).show();
            $( '._sumo_pp_selected_plans' ).show();
        },
    };
    paymentPlansSelector.init();
} );
