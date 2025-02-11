/* global sumo_pp_myaccount_params */

jQuery( function( $ ) {
    // sumo_pp_myaccount_params is required to continue, ensure the object exists
    if ( typeof sumo_pp_myaccount_params === 'undefined' ) {
        return false;
    }

    var $page = $( '.sumo-pp-view-payment' );

    var is_blocked = function( $node ) {
        return $node.is( '.processing' ) || $node.parents( '.processing' ).length;
    };

    /**
     * Block a node visually for processing.
     *
     * @param {JQuery Object} $node
     */
    var block = function( $node ) {
        if ( ! is_blocked( $node ) ) {
            $node.addClass( 'processing' ).block( {
                message : null,
                overlayCSS : {
                    background : '#fff',
                    opacity : 0.6
                }
            } );
        }
    };

    /**
     * Unblock a node after processing is complete.
     *
     * @param {JQuery Object} $node
     */
    var unblock = function( $node ) {
        $node.removeClass( 'processing' ).unblock();
    };

    var my_account = {
        /**
         * Manage My Payments Table Editable UI events.
         */
        init : function() {
            $( document ).on( 'click', 'div.pay_installments input', this.payInstallments );
            $( document ).on( 'click', 'table.payment_activities #prevent_more_notes', this.preventMorePaymentNotes );
        },
        payInstallments : function( evt ) {
            var $this = $( evt.currentTarget );
            $.blockUI.defaults.overlayCSS.cursor = 'wait';
            block( $page );

            $.ajax( {
                type : 'POST',
                url : sumo_pp_myaccount_params.wp_ajax_url,
                data : {
                    action : '_sumo_pp_pay_remaining_custom_installments',
                    security : sumo_pp_myaccount_params.myaccount_nonce,
                    payment_id : $( '.sumo-pp-view-payment table.payment_details' ).data( 'payment_id' ),
                    selected_installments : $( 'div.pay_installments' ).find( 'select' ).val(),
                },
                success : function( data ) {
                    if ( 'undefined' !== typeof data.result ) {
                        window.location.href = data.redirect;
                    }
                },
                complete : function() {
                    unblock( $page );
                }
            } );
        },
        preventMorePaymentNotes : function( evt ) {
            var $this = $( evt.currentTarget );

            if ( 'more' === $this.attr( 'data-flag' ) ) {
                $this.text( sumo_pp_myaccount_params.show_less_notes_label );
                $this.attr( 'data-flag', 'less' );

                $( '._alert_box' ).slideDown();
            } else {
                $this.text( sumo_pp_myaccount_params.show_more_notes_label );
                $this.attr( 'data-flag', 'more' );

                $( '._alert_box' ).css( 'display', 'none' );
                $( '.default_notes0' ).slideDown();
                $( '.default_notes1' ).slideDown();
                $( '.default_notes2' ).slideDown();
            }
        }
    };

    if ( $page.length ) {
        my_account.init();
    }
} );
