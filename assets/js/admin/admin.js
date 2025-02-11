/* global sumo_pp_admin_params, ajaxurl */

jQuery( function( $ ) {

    // sumo_pp_admin_params is required to continue, ensure the object exists
    if ( typeof sumo_pp_admin_params === 'undefined' ) {
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

    var $notes_div = $( '#_sumo_pp_payment_notes' ).closest( 'div' );

    var paymentPlans = {
        options : $( '.sumo-pp-plan-options-inside table.plan_options' ),
        plans : $( '.sumo-pp-plan-options-inside table.payment_plans' ),
        hidden : $( '#_sumo_pp_hidden_datas' ),
        init : function() {
            paymentPlans.options
                    .on( 'change', 'tr.price_type select', this.togglePriceType )
                    .on( 'change', 'tr.pay_balance_type select', this.togglePayBalanceType )
                    .on( 'change', 'tr.sync select', this.toggleSync )
                    .on( 'change', 'tr.installments_type select', this.toggleInstallmentsType )
                    .on( 'change', 'input.fixed_no_of_installments', this.toggleFixedNoOfInstallments )
                    .on( 'change', 'input.fixed_payment_amount', this.toggleFixedNoOfInstallments )
                    .on( 'change', 'input.fixed_duration_length', this.toggleFixedNoOfInstallments )
                    .on( 'change', 'select.fixed_duration_period', this.toggleFixedNoOfInstallments );

            paymentPlans.plans
                    .on( 'click', 'a.add', this.addInstallment )
                    .on( 'click', 'a.remove_row', this.removeInstallment )
                    .on( 'change', '.payment_amount', this.setTotalPayable );

            $( document ).on( 'change', 'p.balance_payable_orders_creation > select', this.toggleOrdersCreation );

            this.getSync();
            this.mayBeGetScheduledDatePicker();
            this.getOrdersCreation();
        },
        togglePriceType : function( evt ) {
            var $this = $( evt.currentTarget ), $price_type, $payment_amount;

            $price_type = paymentPlans.getPriceType();

            if ( 'fixed-price' === $this.val() ) {
                $payment_amount = $price_type + paymentPlans.plans.find( 'span.total_payment_amount' ).text().replace( $price_type, '' ).replace( '%', '' );
            } else {
                $payment_amount = paymentPlans.plans.find( 'span.total_payment_amount' ).text().replace( $price_type, '' ).replace( paymentPlans.hidden.data( 'currency_symbol' ), '' ) + $price_type;
            }

            paymentPlans.plans.find( 'tbody tr' ).each( function() {
                $( this ).find( 'td:eq(0) span' ).text( $price_type );
            } );

            paymentPlans.options.find( 'input.fixed_payment_amount' ).closest( 'tr' ).find( 'span' ).text( $price_type );
            paymentPlans.plans.find( 'span.total_payment_amount' ).text( $payment_amount );
        },
        togglePayBalanceType : function( evt ) {
            paymentPlans.getPayBalanceType();
        },
        toggleSync : function( evt ) {
            paymentPlans.getSync();
        },
        toggleInstallmentsType : function() {
            paymentPlans.getInstallmentsType();
        },
        toggleFixedNoOfInstallments : function() {
            if ( parseInt( paymentPlans.options.find( 'input.fixed_no_of_installments' ).val() ) > 0 ) {
                paymentPlans.plans.find( 'tbody tr' ).slice( 1 ).each( function() {
                    $( this ).remove();
                } );

                for ( var tr = 0; tr < parseInt( paymentPlans.options.find( 'input.fixed_no_of_installments' ).val() ); tr ++ ) {
                    paymentPlans.addingFixedInstallments = true;
                    paymentPlans.addInstallment();
                }
            }

            paymentPlans.setTotalPayable();
        },
        toggleOrdersCreation : function() {
            paymentPlans.getOrdersCreation();
        },
        getPayBalanceType : function( evt ) {
            paymentPlans.options.find( 'tr.installments_type' ).hide();
            paymentPlans.options.find( 'tr.installments_type_fields' ).hide();
            paymentPlans.options.find( 'tr.installments_type_fields' ).find( '.fixed_duration_length' ).removeAttr( 'min' );
            paymentPlans.mayBeGetScheduledDatePicker();

            if ( 'after' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() ) {
                paymentPlans.plans.find( '.duration_length' ).each( function() {
                    if ( 0 === parseFloat( $( this ).val() || 0 ) ) {
                        $( this ).val( '1' );
                    }
                } );

                paymentPlans.options.find( 'tr.installments_type' ).show();
                paymentPlans.getInstallmentsType();
            }

            if ( 'after' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() || 'enabled' === paymentPlans.options.find( 'tr.sync select' ).val() ) {
                paymentPlans.plans.find( 'tbody tr:eq(0)' ).show();

                if ( 'enabled' === paymentPlans.options.find( 'tr.sync select' ).val() ) {
                    paymentPlans.plans.find( 'tbody tr:eq(0) td:eq(1)' ).hide();
                    paymentPlans.plans.find( 'tbody tr:eq(0) td:eq(2) span' ).show();
                } else {
                    paymentPlans.plans.find( 'tbody tr:eq(0) td:eq(1)' ).show();
                    paymentPlans.plans.find( 'tbody tr:eq(0) td:eq(2) span' ).hide();
                }
            } else {
                paymentPlans.plans.find( 'tbody tr:eq(0)' ).hide();
            }

            paymentPlans.plans.find( 'tbody tr' ).slice( ( 'after' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() || 'enabled' === paymentPlans.options.find( 'tr.sync select' ).val() ) ? 1 : 0 ).each( function() {
                $( this ).find( 'td' ).show();

                if ( 'after' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() ) {
                    $( this ).find( 'td:eq(1) div.pay_balance_by_after' ).show();
                    $( this ).find( 'td:eq(1) div.pay_balance_by_before' ).hide();
                    $( this ).find( 'td:eq(1)' ).find( '.duration_length' ).prop( 'min', '1' );
                } else {
                    $( this ).find( 'td:eq(1) div.pay_balance_by_before' ).show();
                    $( this ).find( 'td:eq(1) div.pay_balance_by_after' ).hide();
                    $( this ).find( 'td:eq(1)' ).find( '.duration_length' ).removeAttr( 'min' );
                }
            } );

            paymentPlans.setTotalPayable();
        },
        getSync : function() {
            if ( 'enabled' === paymentPlans.options.find( 'tr.sync select' ).val() ) {
                paymentPlans.options.find( 'tr.sync_fields' ).show();
                paymentPlans.options.find( 'tr.pay_balance_type' ).hide();
                paymentPlans.options.find( 'tr.sync_fields td:eq(1)' ).append( '<input type="hidden" name="_sumo_pp_pay_balance_type" value="before">' );
                paymentPlans.options.find( 'tr.installments_type' ).show();
                paymentPlans.getInstallmentsType();

                paymentPlans.plans.find( 'thead tr th:eq(1)' ).hide();
                paymentPlans.plans.find( 'tbody tr:eq(0)' ).show();
                paymentPlans.plans.find( 'tbody tr:eq(0) td:eq(1)' ).hide();
                paymentPlans.plans.find( 'tbody tr:eq(0) td:eq(2) span' ).show();

                paymentPlans.plans.find( 'tbody tr' ).slice( 1 ).each( function() {
                    $( this ).find( 'td' ).show();
                    $( this ).find( 'td:eq(1)' ).hide();
                    $( this ).find( 'td:eq(1)' ).find( '.duration_length' ).removeAttr( 'min' );
                } );

                paymentPlans.plans.find( 'tfoot tr th:eq(1)' ).attr( 'colspan', '2' );
            } else {
                paymentPlans.options.find( 'tr.sync_fields' ).hide();
                paymentPlans.options.find( 'tr.pay_balance_type' ).show();
                paymentPlans.options.find( 'tr.sync_fields td:eq(1) input[name="_sumo_pp_pay_balance_type"]' ).remove();

                paymentPlans.plans.find( 'thead tr th:eq(1)' ).show();

                if ( 'after' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() ) {
                    paymentPlans.options.find( 'tr.installments_type' ).show();
                    paymentPlans.getInstallmentsType();
                    paymentPlans.plans.find( 'tbody tr:eq(0)' ).show();
                    paymentPlans.plans.find( 'tbody tr:eq(0) td:eq(1)' ).show();
                    paymentPlans.plans.find( 'tbody tr:eq(0) td:eq(2) span' ).hide();
                } else {
                    paymentPlans.options.find( 'tr.installments_type' ).hide();
                    paymentPlans.options.find( 'tr.installments_type_fields' ).hide();
                    paymentPlans.options.find( 'tr.installments_type_fields' ).find( '.fixed_duration_length' ).removeAttr( 'min' );
                    paymentPlans.plans.find( 'tbody tr:eq(0)' ).hide();
                }

                paymentPlans.plans.find( 'tbody tr' ).slice( 1 ).each( function() {
                    $( this ).find( 'td' ).show();

                    if ( 'after' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() ) {
                        $( this ).find( 'td:eq(1) div.pay_balance_by_after' ).show();
                        $( this ).find( 'td:eq(1) div.pay_balance_by_before' ).hide();
                        $( this ).find( 'td:eq(1)' ).find( '.duration_length' ).prop( 'min', '1' );
                    } else {
                        $( this ).find( 'td:eq(1) div.pay_balance_by_before' ).show();
                        $( this ).find( 'td:eq(1) div.pay_balance_by_after' ).hide();
                        $( this ).find( 'td:eq(1)' ).find( '.duration_length' ).removeAttr( 'min' );
                    }
                } );

                paymentPlans.plans.find( 'tfoot tr th:eq(1)' ).attr( 'colspan', '3' );
            }

            paymentPlans.setTotalPayable();
        },
        getInstallmentsType : function() {
            paymentPlans.options.find( 'tr.installments_type_fields' ).hide();
            paymentPlans.options.find( 'tr.installments_type_fields' ).find( '.fixed_duration_length' ).removeAttr( 'min' );

            if ( 'fixed' === paymentPlans.options.find( 'tr.installments_type select' ).val() ) {
                paymentPlans.options.find( 'tr.installments_type_fields' ).show();
                paymentPlans.options.find( 'tr.installments_type_fields' ).find( '.fixed_duration_length' ).prop( 'min', '1' );

                if ( 'after' !== paymentPlans.options.find( 'tr.pay_balance_type select' ).val() || 'enabled' === paymentPlans.options.find( 'tr.sync select' ).val() ) {
                    paymentPlans.options.find( 'tr.installments_type_fields' ).find( '.fixed_duration_period' ).closest( 'tr' ).hide();
                    paymentPlans.options.find( 'tr.installments_type_fields' ).find( '.fixed_duration_length' ).removeAttr( 'min' );
                }
            }
        },
        getPriceType : function() {
            var $price_type;
            if ( 'fixed-price' === paymentPlans.options.find( 'tr.price_type select' ).val() ) {
                $price_type = paymentPlans.hidden.data( 'currency_symbol' );
            } else {
                $price_type = '%';
            }
            return $price_type;
        },
        getDurationOptions : function( selected ) {
            var period_options = '';

            $.each( sumo_pp_admin_params.duration_options, function( value, label ) {
                if ( selected === value ) {
                    period_options += '<option value="' + value.toString() + '" selected="selected">' + label.toString() + '</option>'
                } else {
                    period_options += '<option value="' + value.toString() + '">' + label.toString() + '</option>'
                }
            } );

            return period_options;
        },
        getTotalPayableAmount : function() {
            var total = 0, sliceBy = 0;

            if ( 'before' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() && 'enabled' !== paymentPlans.options.find( 'tr.sync select' ).val() ) {
                sliceBy = 1;
            }

            paymentPlans.plans.find( '.payment_amount' ).slice( sliceBy ).each( function() {
                total = total + parseFloat( $( this ).val() || 0 );
            } );
            return total;
        },
        getTotalPayableToDisplay : function() {
            var $payment_amount;
            if ( 'fixed-price' === paymentPlans.options.find( 'tr.price_type select' ).val() ) {
                $payment_amount = paymentPlans.getPriceType() + paymentPlans.getTotalPayableAmount().toFixed( sumo_pp_admin_params.price_dp );
            } else {
                $payment_amount = paymentPlans.getTotalPayableAmount().toFixed( sumo_pp_admin_params.price_dp ) + paymentPlans.getPriceType();
            }
            return $payment_amount;
        },
        getOrdersCreation : function() {
            if ( 'immediately_after_payment' === $( 'p.balance_payable_orders_creation > select' ).val() ) {
                $( 'p.next_payment_date' ).slideDown();
            } else {
                $( 'p.next_payment_date' ).slideUp();
            }
        },
        mayBeGetScheduledDatePicker : function() {
            if ( 'before' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() ) {
                $( '.scheduled_date' ).datepicker( {
                    minDate : 0,
                    changeMonth : true,
                    dateFormat : 'yy-mm-dd',
                    numberOfMonths : 1,
                    showButtonPanel : true,
                    defaultDate : '',
                    showOn : 'focus',
                    buttonImageOnly : true
                } );
            }
        },
        addInstallment : function( evt ) {
            var rowID = paymentPlans.plans.find( 'tbody tr' ).length;

            if ( 'after' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() ) {
                paymentPlans.plans.find( '.duration_length' ).each( function() {
                    if ( 0 === parseFloat( $( this ).val() || 0 ) ) {
                        $( this ).val( '1' );
                    }
                } );
            }

            var amount = '', dlength = 1, dperiod = 'days';
            if ( true === paymentPlans.addingFixedInstallments ) {
                amount = paymentPlans.options.find( 'input.fixed_payment_amount' ).val();
                dlength = paymentPlans.options.find( 'input.fixed_duration_length' ).val();
                dperiod = paymentPlans.options.find( 'select.fixed_duration_period' ).val();
            }

            $( '<tr>\n\
                <td>\n\
                    <input class="payment_amount" type="number" min="0.00" step="0.01" name="_sumo_pp_scheduled_payment[' + rowID + ']" value="' + amount + '"/><span>' + paymentPlans.getPriceType() + '</span>\n\
                </td>\n\
                <td style="' + ( 'enabled' === paymentPlans.options.find( 'tr.sync select' ).val() ? 'display:none;' : '' ) + '">\n\
                    <div class="pay_balance_by_after" style="' + ( 'after' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() ? '' : 'display:none;' ) + '">\n\
                        After\n\
                        <input class="duration_length" type="number" min="1" name="_sumo_pp_scheduled_duration_length[' + rowID + ']" value="' + dlength + '"/>\n\
                        <select class="duration_period" name="_sumo_pp_scheduled_period[' + rowID + ']">' + paymentPlans.getDurationOptions( dperiod ) + '</select>\n\
                    </div>\n\
                    <div class="pay_balance_by_before" style="' + ( 'before' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() ? '' : 'display:none;' ) + '">\n\
                        <input class="scheduled_date" type="text" name="_sumo_pp_scheduled_date[' + rowID + ']" value=""/>\n\
                    </div>\n\
                </td>\n\
                <td><a href="#" class="remove_row button">X</a></td>\n\
            </tr>' ).appendTo( paymentPlans.plans.find( 'tbody' ) );

            paymentPlans.mayBeGetScheduledDatePicker();
            paymentPlans.addingFixedInstallments = false;
            return false;
        },
        removeInstallment : function( evt ) {
            var $this = $( evt.currentTarget );

            $this.closest( 'tr' ).remove();
            paymentPlans.setTotalPayable();

            if ( 'after' === paymentPlans.options.find( 'tr.pay_balance_type select' ).val() ) {
                paymentPlans.plans.find( '.duration_length' ).each( function( rowID ) {
                    if ( 0 === parseFloat( $( this ).val() || 0 ) && parseInt( paymentPlans.plans.find( '.duration_length' ).length - 1 ) === rowID ) {
                        $( this ).val( '' );
                    } else if ( 0 === parseFloat( $( this ).val() || 0 ) ) {
                        $( this ).val( '1' );
                    }
                } );
            }
            return false;
        },
        setTotalPayable : function() {
            paymentPlans.plans.find( 'span.total_payment_amount' ).text( paymentPlans.getTotalPayableToDisplay() );
        },
    };

    if ( 'yes' === sumo_pp_admin_params.is_paymentplans_page ) {
        paymentPlans.init();
    }

    $( '.sumo-pp-installment_orders .expected_payment_date' ).datetimepicker( {
        minDate : 0,
        changeMonth : true,
        changeYear : true,
        dateFormat : 'yy-mm-dd',
        timeFormat : 'HH:mm',
        numberOfMonths : 1,
        showButtonPanel : true,
        showOn : 'focus',
        buttonImageOnly : true
    } );

    $( document ).on( 'click', '#_sumo_pp_payment_notes a.add_note', function( evt ) {
        evt.preventDefault();
        var $content = $( '#payment_note' ).val();
        var $post_id = $( this ).attr( 'data-id' );

        $.blockUI.defaults.overlayCSS.cursor = 'wait';
        block( $notes_div );

        $.ajax( {
            type : 'POST',
            url : sumo_pp_admin_params.wp_ajax_url,
            data : {
                action : '_sumo_pp_add_payment_note',
                security : sumo_pp_admin_params.add_note_nonce,
                content : $content,
                post_id : $post_id
            },
            success : function( data ) {
                $( '#_sumo_pp_payment_notes ul.payment_notes' ).prepend( data );
                $( '#_sumo_pp_payment_notes #payment_note' ).val( '' );
            },
            complete : function() {
                unblock( $notes_div );
            }
        } );
    } );

    $( document ).on( 'click', '#_sumo_pp_payment_notes a.delete_note', function() {
        var $this = $( this );
        var $note_to_delete = $this.parent().parent().attr( 'rel' );

        $.blockUI.defaults.overlayCSS.cursor = 'wait';
        block( $notes_div );

        $.ajax( {
            type : 'POST',
            url : sumo_pp_admin_params.wp_ajax_url,
            data : {
                action : '_sumo_pp_delete_payment_note',
                security : sumo_pp_admin_params.delete_note_nonce,
                delete_id : $note_to_delete
            },
            success : function( data ) {
                if ( data === true ) {
                    $this.parent().parent().remove();
                }
            },
            complete : function() {
                unblock( $notes_div );
            }
        } );
        return false;
    } );

    $( document ).on( 'click', '.sumo-pp-payment-data div.view_next_payable_order > a', function( evt ) {
        evt.preventDefault();
        $( '.sumo-pp-payment-data div.view_next_payable_order > p' ).slideToggle();
    } );

    $( document ).on( 'click', '.sumo-pp-installment_orders a.edit-installment-date,.sumo-pp-installment_orders a.edit-installment-amount', function( evt ) {
        evt.preventDefault();

        $( this ).closest( 'p' ).find( 'input' ).toggle();
    } );

    $( '.post-type-sumo_pp_payments' ).find( 'form' ).on( 'submit', function( evt ) {
        if ( 1 >= $( '.sumo-pp-installment_orders' ).find( '.expected_payment_date' ).length ) {
            return true;
        }

        var static_time = 0, err = false;
        $( '.sumo-pp-installment_orders' ).find( '.expected_payment_date' ).each( function() {
            var new_date = Date.parse( this.value ),
                    new_time = ( new_date / 1000 );

            if ( static_time >= new_time ) {
                err = true;
                return false;
            } else {
                static_time = new_time;
            }
        } );

        if ( true === err ) {
            window.alert( sumo_pp_admin_params.warning_dates_not_in_order )
            return false;
        }
    } );
} );
