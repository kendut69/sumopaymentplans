/* global sumo_pp_admin_exporter_params, ajaxurl */

jQuery( function( $ ) {

    // sumo_pp_admin_exporter_params is required to continue, ensure the object exists
    if ( typeof sumo_pp_admin_exporter_params === 'undefined' ) {
        return false;
    }

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

    var $exporter_div = $( '.sumo-pp-payment-exporter-wrapper' ).closest( 'div' );

    $( '[name="payment_from_date"]' ).datepicker( {
        changeMonth : true,
        dateFormat : 'yy-mm-dd',
        numberOfMonths : 1,
        showButtonPanel : true,
        defaultDate : '',
        showOn : 'focus',
        buttonImageOnly : true,
        onClose : function( selectedDate ) {
            var maxDate = new Date( Date.parse( selectedDate ) );
            maxDate.setDate( maxDate.getDate() + 1 );
            $( '[name="payment_to_date"]' ).datepicker( 'option', 'minDate', maxDate );
        }
    } );

    $( '[name="payment_to_date"]' ).datepicker( {
        changeMonth : true,
        dateFormat : 'yy-mm-dd',
        numberOfMonths : 1,
        showButtonPanel : true,
        defaultDate : '',
        showOn : 'focus',
        buttonImageOnly : true,
    } );

    $( document ).on( 'click', 'form.sumo-pp-payment-exporter > div.export-actions > input', function() {
        $( this ).closest( 'form' ).find( '#exported_data' ).val( '' );

        $.blockUI.defaults.overlayCSS.cursor = 'wait';
        block( $exporter_div );

        $.ajax( {
            type : 'POST',
            url : sumo_pp_admin_exporter_params.wp_ajax_url,
            data : {
                action : '_sumo_pp_init_data_export',
                security : sumo_pp_admin_exporter_params.exporter_nonce,
                exportDataBy : $( this ).closest( 'form' ).serialize(),
            },
            success : function( response ) {
                if ( 'done' === response.export ) {
                    window.location = response.redirect_url;
                } else if ( 'processing' === response.export ) {
                    var i, j = 1, chunkedData, chunk = 10;

                    for ( i = 0, j = response.original_data.length; i < j; i += chunk ) {
                        chunkedData = response.original_data.slice( i, i + chunk );
                        processExport( response.original_data.length, chunkedData );
                    }
                } else {
                    window.location = response.redirect_url;
                }
            },
            complete : function() {
                unblock( $exporter_div );
            }
        } );
    } );

    function processExport( originalDataLength, chunkedData ) {
        $.ajax( {
            type : 'POST',
            url : sumo_pp_admin_exporter_params.wp_ajax_url,
            async : false,
            dataType : 'json',
            data : {
                action : '_sumo_pp_handle_exported_data',
                security : sumo_pp_admin_exporter_params.exporter_nonce,
                originalDataLength : originalDataLength,
                chunkedData : chunkedData,
                generated_data : $( 'form.sumo-pp-payment-exporter' ).find( '#exported_data' ).val(),
            },
            success : function( response ) {
                if ( 'done' === response.export ) {
                    window.location = response.redirect_url;
                } else if ( 'processing' === response.export ) {
                    $( 'form.sumo-pp-payment-exporter' ).find( '#exported_data' ).val( JSON.stringify( response.generated_data ) );
                } else {
                    window.location = response.redirect_url;
                }
            }
        } );
    }
} );
