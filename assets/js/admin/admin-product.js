/* global sumo_pp_admin_product_params, ajaxurl */

jQuery( function( $ ) {

    // sumo_pp_admin_product_params is required to continue, ensure the object exists
    if ( typeof sumo_pp_admin_product_params === 'undefined' ) {
        return false;
    }

    var _sumo_pp_product = {
        /**
         * Initialize Payment Plans Product settings UI events.
         */
        init : function() {
            this.trigger_on_page_load();

            $( document ).on( 'change', '#product-type', this.on_change_product_type );
            $( document ).on( 'woocommerce_variations_save_variations_button', this.validate_save_variations );
            $( 'form#post' ).on( 'submit', this.validate_on_submit );
        },
        trigger_on_page_load : function( ) {

            this.on_load_events( $( '#product-type' ).val() );
        },
        on_change_product_type : function( evt ) {
            var $product_type = $( evt.currentTarget ).val();

            _sumo_pp_product.on_load_events( $product_type );
        },
        on_load_events : function( $product_type ) {
            $product_type = $product_type || '';

            switch ( $product_type ) {
                case 'variable':
                    $( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function( evt ) {
                        _sumo_pp_product.on_load_variations( evt );
                    } );

                    $( document.body ).on( 'woocommerce_variations_added', function( evt, qty ) {
                        _sumo_pp_product.on_load_variations( evt, qty );
                    } );
                    break;
                default:
                    _sumo_pp_simple_product.init();
                    break;
            }
        },
        on_load_variations : function( evt, qty ) {
            qty = qty || 0;

            var $wrapper = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
                    variation_count = parseInt( $wrapper.attr( 'data-total' ), 10 ) + qty;

            for ( var i = 0; i < sumo_pp_admin_product_params.variations_per_page; i ++ ) {
                ( function( i ) {
                    _sumo_pp_variation_product.init( i );
                } )( i );
            }
        },
        validate_on_submit : function() {
            switch ( $( '#product-type' ).val() ) {
                case 'variable':
                    return _sumo_pp_product.validate_save_variations();
                    break;
                default:
                    return  _sumo_pp_product.validate_save_product();
                    break;
            }
            return true;
        },
        validate_save_product : function( variation_index ) {
            variation_index = variation_index || 0;

            var mayBeVariation = 'variable' === $( '#product-type' ).val() ? variation_index : '',
                    errFields = [ ],
                    availableErrFields = [
                        '#_sumo_pp_fixed_deposit_price' + mayBeVariation,
                        '#_sumo_pp_fixed_deposit_percent' + mayBeVariation,
                        '#_sumo_pp_min_deposit' + mayBeVariation,
                        '#_sumo_pp_max_deposit' + mayBeVariation,
                        '#_sumo_pp_pay_balance_before' + mayBeVariation,
                    ],
                    prevField;

            if ( $( '#_sumo_pp_enable_sumopaymentplans' + mayBeVariation ).is( ':checked' ) && 'pay-in-deposit' === $( '#_sumo_pp_payment_type' + mayBeVariation ).val() && ! $( '#_sumo_pp_apply_global_settings' + mayBeVariation ).is( ':checked' ) ) {
                if ( 'pre-defined' === $( '#_sumo_pp_deposit_type' + mayBeVariation ).val() ) {
                    prevField = '#_sumo_pp_deposit_price_type' + mayBeVariation;

                    if ( 'fixed-price' === $( '#_sumo_pp_deposit_price_type' + mayBeVariation ).val() ) {
                        if ( '' === mayBeVariation ) {
                            var $regularPrice = parseFloat( $( '#_regular_price' ).val().replace( sumo_pp_admin_product_params.decimal_sep, '.' ) ),
                                    $salePrice = parseFloat( $( '#_sale_price' ).val().replace( sumo_pp_admin_product_params.decimal_sep, '.' ) );
                        } else {
                            $regularPrice = parseFloat( $( '#variable_regular_price_' + mayBeVariation ).val().replace( sumo_pp_admin_product_params.decimal_sep, '.' ) ),
                                    $salePrice = parseFloat( $( '#variable_sale_price' + mayBeVariation ).val().replace( sumo_pp_admin_product_params.decimal_sep, '.' ) );
                        }

                        var $subscriptionPrice = isNaN( $salePrice ) ? $regularPrice : $salePrice,
                                $depositPrice = parseFloat( $( '#_sumo_pp_fixed_deposit_price' + mayBeVariation ).val().replace( sumo_pp_admin_product_params.decimal_sep, '.' ) );

                        if ( isNaN( $depositPrice ) || $subscriptionPrice <= $depositPrice || 0 >= $depositPrice ) {
                            if ( '' !== mayBeVariation ) {
                                $( '#_sumo_pp_fixed_deposit_price' + mayBeVariation ).closest( '.wc-metabox > .wc-metabox-content' ).show();
                            }

                            errFields.push( '#_sumo_pp_fixed_deposit_price' + mayBeVariation );
                        }
                    } else {
                        if ( $( '#_sumo_pp_fixed_deposit_percent' + mayBeVariation ).val() >= 100 || '' === $( '#_sumo_pp_fixed_deposit_percent' + mayBeVariation ).val() || 0 >= $( '#_sumo_pp_fixed_deposit_percent' + mayBeVariation ).val() ) {
                            if ( '' !== mayBeVariation ) {
                                $( '#_sumo_pp_fixed_deposit_percent' + mayBeVariation ).closest( '.wc-metabox > .wc-metabox-content' ).show();
                            }

                            errFields.push( '#_sumo_pp_fixed_deposit_percent' + mayBeVariation );
                        }
                    }
                } else {
                    prevField = '#_sumo_pp_deposit_type' + mayBeVariation;

                    var $minDeposit = parseFloat( $( '#_sumo_pp_min_deposit' + mayBeVariation ).val() ),
                            $maxDeposit = parseFloat( $( '#_sumo_pp_max_deposit' + mayBeVariation ).val() );

                    if ( isNaN( $minDeposit ) || isNaN( $maxDeposit ) || 0 >= $minDeposit || 0 >= $maxDeposit || ( $minDeposit > $maxDeposit ) || $minDeposit >= 100 || $maxDeposit >= 100 ) {
                        if ( '' !== mayBeVariation ) {
                            $( '#_sumo_pp_min_deposit' + mayBeVariation ).closest( '.wc-metabox > .wc-metabox-content' ).show();
                        }

                        errFields.push( '#_sumo_pp_min_deposit' + mayBeVariation );
                        errFields.push( '#_sumo_pp_max_deposit' + mayBeVariation );
                    }
                }

                if ( 'before' === $( '#_sumo_pp_pay_balance_type' + mayBeVariation ).val() ) {
                    if ( '' === $( '#_sumo_pp_pay_balance_before' + mayBeVariation ).val() || ( new Date( $( '#_sumo_pp_pay_balance_before' + mayBeVariation ).val() ) <= new Date() ) ) {
                        if ( '' !== mayBeVariation ) {
                            $( '#_sumo_pp_pay_balance_before' + mayBeVariation ).closest( '.wc-metabox > .wc-metabox-content' ).show();
                        }

                        errFields.push( '#_sumo_pp_pay_balance_before' + mayBeVariation );
                    }
                }

                if ( 'yes' === $( '#enable_sumo_bookings' ).val() && '' === $( '#_sumo_pp_pay_balance_before_booked_date' ).val() ) {
                    errFields.push( '#_sumo_pp_pay_balance_before_booked_date' );
                }
            }
            return _sumo_pp_product.needs_update( errFields, prevField, availableErrFields );
        },
        validate_save_variations : function() {

            var $wrapper = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
                    variation_count = parseInt( $wrapper.attr( 'data-total' ), 10 ),
                    variations = $( '#variable_product_options' ).find( '.woocommerce_variations .variation-needs-update' ),
                    needs_update = true;

            $( 'li.variations_tab a' ).trigger( 'click' );
            $( '.wc-metaboxes-wrapper, .expand_all' ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .wc-metabox-content' ).hide();

            for ( var i = 0; i < sumo_pp_admin_product_params.variations_per_page; i ++ ) {
                ( function( i ) {
                    if ( ! _sumo_pp_product.validate_save_product( i ) ) {
                        variations.removeClass( 'variation-needs-update' );
                        needs_update = false;
                        return false;
                    }
                } )( i );
            }
            if ( ! needs_update ) {
                $( 'button.cancel-variation-changes, button.save-variation-changes' ).removeAttr( 'disabled' );
            }
            return needs_update;
        },
        needs_update : function( errFields, prevField, resetErrFields ) {
            resetErrFields = resetErrFields || [ ];
            var needs_update = true;

            if ( errFields.length === 0 ) {
                return needs_update;
            }
            if ( $.isArray( resetErrFields ) ) {
                $.each( resetErrFields, function( index, reseterrField ) {
                    $( reseterrField ).css( { "border" : '' } );
                } );
            }

            if ( $.isArray( errFields ) ) {
                $.each( errFields, function( index, errField ) {
                    if ( $( errField ).is( ':visible' ) ) {
                        needs_update = false;
                        $( errField ).css( { "border" : '#FF0000 1px solid' } );
                    }
                } );

                if ( ! needs_update ) {
                    $( 'html,body' ).animate( {
                        scrollTop : $( prevField ).closest( 'p' ).offset().top
                    }, 1200 );
                }
            } else {
                if ( $( errFields ).is( ':visible' ) ) {
                    needs_update = false;
                    $( errFields ).css( { "border" : '#FF0000 1px solid' } );
                }

                if ( ! needs_update ) {
                    $( 'html,body' ).animate( {
                        scrollTop : $( prevField ).closest( 'p' ).offset().top
                    }, 1200 );
                }
            }
            return needs_update;
        },
        sortable : function() {
            $( 'table.sumo-pp-selected-plans tbody' ).sortable( {
                items : 'tr',
                cursor : 'move',
                axis : 'y',
                handle : 'td.sort',
                scrollSensitivity : 40,
                helper : function( event, ui ) {
                    ui.children().each( function() {
                        $( this ).width( $( this ).width() );
                    } );
                    ui.css( 'left', '0' );
                    return ui;
                },
                start : function( event, ui ) {
                    ui.item.css( 'background-color', '#f6f6f6' );
                },
                stop : function( event, ui ) {
                    ui.item.removeAttr( 'style' );
                }
            } );
        },
    };

    // Date picker fields.
    function date_picker_select( datepicker ) {
        var option = $( datepicker ).next().is( '.hasDatepicker' ) ? 'minDate' : 'maxDate',
                otherDateField = 'minDate' === option ? $( datepicker ).next() : $( datepicker ).prev(),
                date = $( datepicker ).datepicker( 'getDate' );

        $( otherDateField ).datepicker( 'option', option, date );
        $( datepicker ).change();
    }

    var _sumo_pp_simple_product = {
        /**
         * Payment Plans Product Actions.
         */
        init : function() {

            this.trigger_on_page_load();

            $( document ).on( 'change', '#_sumo_pp_enable_sumopaymentplans', this.toggle_payment_settings );
            $( document ).on( 'change', '#_sumo_pp_payment_type', this.toggle_payment_type );
            $( document ).on( 'click', '._sumo_pp_schedule_availability', this.toggle_schedule_availability );
            $( document ).on( 'click', '._sumo_pp_cancel_availability', this.toggle_cancel_availability );
            $( document ).on( 'change', '#_sumo_pp_apply_global_settings', this.toggle_global_settings );
            $( document ).on( 'change', '#_sumo_pp_deposit_type', this.toggle_deposit_type );
            $( document ).on( 'change', '#_sumo_pp_deposit_price_type', this.toggle_deposit_price_type );
            $( document ).on( 'change', '#_sumo_pp_user_defined_deposit_type', this.toggle_user_defined_deposit_type );
            $( document ).on( 'change', '#_sumo_pp_pay_balance_type', this.toggle_pay_balance_type );
            $( document ).on( 'change', '#enable_sumo_bookings', this.toggle_upon_booking );
            $( document ).on( 'change', '#_sumo_wcpo_enable_sumopreorders', this.toggle_preorder_method );
            $( document ).on( 'change', '#_sumo_wcpo_preorder_method', this.toggle_preorder_method );
            $( document ).on( 'click', '#_sumo_pp_add_col_1_plan', this.paymentPlansSelector.onClickAddColumn1Plan );
            $( document ).on( 'click', '#_sumo_pp_add_col_2_plan', this.paymentPlansSelector.onClickAddColumn2Plan );
            $( 'table._sumo_pp_selected_plans' ).on( 'click', 'a.remove_row', this.paymentPlansSelector.onClickRemovePlan );
        },
        trigger_on_page_load : function() {

            this.get_payment_settings( $( '#_sumo_pp_enable_sumopaymentplans' ).is( ':checked' ) );

            $( '#_sumo_pp_pay_balance_before' ).datepicker( {
                minDate : 0,
                changeMonth : true,
                dateFormat : 'yy-mm-dd',
                numberOfMonths : 1,
                showButtonPanel : true,
                defaultDate : '',
                showOn : 'focus',
                buttonImageOnly : true
            } );

            $( '._sumo_pp_availability_dates_field' ).each( function() {
                $( this ).find( 'input' ).datepicker( {
                    defaultDate : '',
                    dateFormat : 'yy-mm-dd',
                    numberOfMonths : 1,
                    showButtonPanel : true,
                    onSelect : function() {
                        date_picker_select( $( this ) );
                    }
                } );
                $( this ).find( 'input' ).each( function() {
                    date_picker_select( $( this ) );
                } );
            } );

            _sumo_pp_product.sortable();
        },
        toggle_payment_settings : function( evt ) {
            var $payment_settings_enabled = $( evt.currentTarget ).is( ':checked' );

            _sumo_pp_simple_product.get_payment_settings( $payment_settings_enabled );
        },
        toggle_payment_type : function( evt ) {
            var $payment_type = $( evt.currentTarget ).val();

            _sumo_pp_simple_product.get_payment_type( $payment_type );
        },
        toggle_schedule_availability : function() {
            _sumo_pp_simple_product.get_schedule_availability();
            return false;
        },
        toggle_cancel_availability : function() {
            _sumo_pp_simple_product.get_cancel_availability();
            return false;
        },
        toggle_global_settings : function( evt ) {
            var $apply_global_settings = $( evt.currentTarget ).is( ':checked' );

            _sumo_pp_simple_product.get_global_settings( $apply_global_settings );
        },
        toggle_deposit_type : function( evt ) {
            var $deposit_type = $( evt.currentTarget ).val();

            _sumo_pp_simple_product.get_deposit_type( $deposit_type );
        },
        toggle_deposit_price_type : function( evt ) {
            var $deposit_price_type = $( evt.currentTarget ).val();

            _sumo_pp_simple_product.get_deposit_price_type( $deposit_price_type );
        },
        toggle_user_defined_deposit_type : function( evt ) {
            var $user_defined_deposit_type = $( evt.currentTarget ).val();

            _sumo_pp_simple_product.get_user_defined_deposit_type( $user_defined_deposit_type );
        },
        toggle_pay_balance_type : function( evt ) {
            var $pay_balance_type = $( evt.currentTarget ).val();

            _sumo_pp_simple_product.get_pay_balance( $pay_balance_type );
        },
        toggle_upon_booking : function( evt ) {
            var $booking_status = $( evt.currentTarget ).val();

            _sumo_pp_simple_product.get_booking( $booking_status );
        },
        toggle_preorder_method : function( evt ) {
            _sumo_pp_simple_product.get_payment_settings( $( '#_sumo_pp_enable_sumopaymentplans' ).is( ':checked' ) );
        },
        get_payment_settings : function( $payment_settings_enabled ) {
            $payment_settings_enabled = $payment_settings_enabled || '';

            if ( $payment_settings_enabled === true ) {
                $( '._sumo_pp_fields' ).show();
                _sumo_pp_simple_product.get_payment_type( $( '#_sumo_pp_payment_type' ).val() );
            } else {
                $( '._sumo_pp_fields' ).hide();
            }
        },
        get_payment_type : function( $payment_type, $do_not_apply_gobal ) {
            $payment_type = $payment_type || 'payment-plans';
            $do_not_apply_gobal = $do_not_apply_gobal || false;

            $( '#_sumo_pp_deposit_type' ).closest( 'p' ).hide();
            $( '#_sumo_pp_deposit_price_type' ).closest( 'p' ).hide();
            $( '#_sumo_pp_pay_balance_type' ).closest( 'p' ).hide();
            $( '#_sumo_pp_set_expired_deposit_payment_as' ).closest( 'p' ).hide();
            $( '#_sumo_pp_fixed_deposit_price' ).closest( 'p' ).hide();
            $( '#_sumo_pp_fixed_deposit_percent' ).closest( 'p' ).hide();
            $( '#_sumo_pp_user_defined_deposit_type' ).closest( 'p' ).hide();
            $( '#_sumo_pp_min_user_defined_deposit_price' ).closest( 'p' ).hide();
            $( '#_sumo_pp_max_user_defined_deposit_price' ).closest( 'p' ).hide();
            $( '#_sumo_pp_min_deposit' ).closest( 'p' ).hide();
            $( '#_sumo_pp_max_deposit' ).closest( 'p' ).hide();
            _sumo_pp_simple_product.paymentPlansSelector.show();

            if ( 'pay-in-deposit' === $payment_type ) {
                $( '#_sumo_pp_deposit_type' ).closest( 'p' ).show();
                $( '#_sumo_pp_deposit_price_type' ).closest( 'p' ).show();
                $( '#_sumo_pp_pay_balance_type' ).closest( 'p' ).show();
                $( '#_sumo_pp_fixed_deposit_price' ).closest( 'p' ).show();
                $( '#_sumo_pp_fixed_deposit_percent' ).closest( 'p' ).show();
                $( '#_sumo_pp_user_defined_deposit_type' ).closest( 'p' ).show();
                $( '#_sumo_pp_min_user_defined_deposit_price' ).closest( 'p' ).show();
                $( '#_sumo_pp_max_user_defined_deposit_price' ).closest( 'p' ).show();
                $( '#_sumo_pp_min_deposit' ).closest( 'p' ).show();
                $( '#_sumo_pp_max_deposit' ).closest( 'p' ).show();
                _sumo_pp_simple_product.paymentPlansSelector.hide();
                _sumo_pp_simple_product.get_deposit_type( $( '#_sumo_pp_deposit_type' ).val() );
            }

            if ( false === $do_not_apply_gobal ) {
                _sumo_pp_simple_product.get_global_settings( $( '#_sumo_pp_apply_global_settings' ).is( ':checked' ) );
            }

            $( '._sumo_pp_availability_dates_field' ).each( function() {
                var $these_dates = $( this );
                var schedule_set = false;

                $these_dates.find( 'input' ).each( function() {
                    if ( '' !== $( this ).val() ) {
                        schedule_set = true;
                    }
                } );

                if ( schedule_set ) {
                    _sumo_pp_simple_product.get_schedule_availability();
                } else {
                    _sumo_pp_simple_product.get_cancel_availability();
                }
            } );
        },
        get_schedule_availability : function() {
            $( '._sumo_pp_schedule_availability' ).hide();
            $( '._sumo_pp_availability_dates_field' ).show();
            $( '._sumo_pp_cancel_availability' ).show();
        },
        get_cancel_availability : function() {
            $( '._sumo_pp_cancel_availability' ).hide();
            $( '._sumo_pp_schedule_availability' ).show();
            $( '._sumo_pp_availability_dates_field' ).hide();
            $( '._sumo_pp_availability_dates_field' ).find( 'input' ).val( '' );
        },
        get_global_settings : function( $apply_global_settings ) {
            $apply_global_settings = $apply_global_settings || '';

            if ( true === $apply_global_settings ) {
                $( '#_sumo_pp_force_deposit' ).closest( 'p' ).hide();
                $( '#_sumo_pp_deposit_type' ).closest( 'p' ).hide();
                $( '#_sumo_pp_deposit_price_type' ).closest( 'p' ).hide();
                $( '#_sumo_pp_pay_balance_type' ).closest( 'p' ).hide();
                $( '#_sumo_pp_set_expired_deposit_payment_as' ).closest( 'p' ).hide();
                $( '#_sumo_pp_fixed_deposit_price' ).closest( 'p' ).hide();
                $( '#_sumo_pp_fixed_deposit_percent' ).closest( 'p' ).hide();
                $( '#_sumo_pp_user_defined_deposit_type' ).closest( 'p' ).hide();
                $( '#_sumo_pp_min_user_defined_deposit_price' ).closest( 'p' ).hide();
                $( '#_sumo_pp_max_user_defined_deposit_price' ).closest( 'p' ).hide();
                $( '#_sumo_pp_min_deposit' ).closest( 'p' ).hide();
                $( '#_sumo_pp_max_deposit' ).closest( 'p' ).hide();
                _sumo_pp_simple_product.paymentPlansSelector.hide();
            } else {
                $( '#_sumo_pp_force_deposit' ).closest( 'p' ).show();
                _sumo_pp_simple_product.get_payment_type( $( '#_sumo_pp_payment_type' ).val(), true );
            }
        },
        get_deposit_type : function( $deposit_type ) {
            $deposit_type = $deposit_type || 'user-defined';

            $( '#_sumo_pp_deposit_price_type' ).closest( 'p' ).hide();
            $( '#_sumo_pp_fixed_deposit_price' ).closest( 'p' ).hide();
            $( '#_sumo_pp_fixed_deposit_percent' ).closest( 'p' ).hide();
            $( '#_sumo_pp_user_defined_deposit_type' ).closest( 'p' ).show();
            $( '#_sumo_pp_min_user_defined_deposit_price' ).closest( 'p' ).show();
            $( '#_sumo_pp_max_user_defined_deposit_price' ).closest( 'p' ).show();
            $( '#_sumo_pp_min_deposit' ).closest( 'p' ).show();
            $( '#_sumo_pp_max_deposit' ).closest( 'p' ).show();
            _sumo_pp_simple_product.get_user_defined_deposit_type( $( '#_sumo_pp_user_defined_deposit_type' ).val() );

            if ( 'pre-defined' === $deposit_type ) {
                $( '#_sumo_pp_deposit_price_type' ).closest( 'p' ).show();
                $( '#_sumo_pp_fixed_deposit_price' ).closest( 'p' ).show();
                $( '#_sumo_pp_fixed_deposit_percent' ).closest( 'p' ).show();
                $( '#_sumo_pp_user_defined_deposit_type' ).closest( 'p' ).hide();
                $( '#_sumo_pp_min_user_defined_deposit_price' ).closest( 'p' ).hide();
                $( '#_sumo_pp_max_user_defined_deposit_price' ).closest( 'p' ).hide();
                $( '#_sumo_pp_min_deposit' ).closest( 'p' ).hide();
                $( '#_sumo_pp_max_deposit' ).closest( 'p' ).hide();
                _sumo_pp_simple_product.get_deposit_price_type( $( '#_sumo_pp_deposit_price_type' ).val() );
            }

            if ( $( 'p' ).find( '#_sumo_wcpo_enable_sumopreorders' ).is( ':checked' ) && 'pay-infront' === $( 'p' ).find( '#_sumo_wcpo_preorder_method' ).val() ) {
                $( '#_sumo_pp_pay_balance_type' ).closest( 'p' ).hide();
                $( '#_sumo_pp_pay_balance_after' ).closest( 'span' ).hide();
                $( '#_sumo_pp_pay_balance_before' ).closest( 'span' ).hide();
                $( '#_sumo_pp_set_expired_deposit_payment_as' ).closest( 'p' ).hide();
                $( '#_sumo_pp_pay_balance_before_booked_date' ).closest( 'span' ).hide();
            } else {
                $( '#_sumo_pp_pay_balance_type' ).closest( 'p' ).show();
                _sumo_pp_simple_product.get_pay_balance( $( '#_sumo_pp_pay_balance_type' ).val() );
            }
        },
        get_deposit_price_type : function( $deposit_price_type ) {
            $deposit_price_type = $deposit_price_type || 'percent-of-product-price';

            $( '#_sumo_pp_fixed_deposit_price' ).closest( 'p' ).hide();
            $( '#_sumo_pp_fixed_deposit_percent' ).closest( 'p' ).show();

            if ( 'fixed-price' === $deposit_price_type ) {
                $( '#_sumo_pp_fixed_deposit_price' ).closest( 'p' ).show();
                $( '#_sumo_pp_fixed_deposit_percent' ).closest( 'p' ).hide();
            }
        },
        get_user_defined_deposit_type : function( $user_defined_deposit_type ) {
            $user_defined_deposit_type = $user_defined_deposit_type || 'percent-of-product-price';

            $( '#_sumo_pp_min_deposit' ).closest( 'p' ).show();
            $( '#_sumo_pp_max_deposit' ).closest( 'p' ).show();
            $( '#_sumo_pp_min_user_defined_deposit_price' ).closest( 'p' ).hide();
            $( '#_sumo_pp_max_user_defined_deposit_price' ).closest( 'p' ).hide();

            if ( 'fixed-price' === $user_defined_deposit_type ) {
                $( '#_sumo_pp_min_user_defined_deposit_price' ).closest( 'p' ).show();
                $( '#_sumo_pp_max_user_defined_deposit_price' ).closest( 'p' ).show();
                $( '#_sumo_pp_min_deposit' ).closest( 'p' ).hide();
                $( '#_sumo_pp_max_deposit' ).closest( 'p' ).hide();
            }
        },
        get_pay_balance : function( $pay_balance_type ) {
            $pay_balance_type = $pay_balance_type || 'after';

            $( '#_sumo_pp_pay_balance_after' ).closest( 'span' ).show();
            $( '#_sumo_pp_pay_balance_before' ).closest( 'span' ).hide();
            $( '#_sumo_pp_set_expired_deposit_payment_as' ).closest( 'p' ).hide();
            $( '#_sumo_pp_pay_balance_before_booked_date' ).closest( 'span' ).hide();

            if ( 'before' === $pay_balance_type ) {
                $( '#_sumo_pp_pay_balance_after' ).closest( 'span' ).hide();
                $( '#_sumo_pp_pay_balance_before' ).closest( 'span' ).show();
                $( '#_sumo_pp_set_expired_deposit_payment_as' ).closest( 'p' ).show();

                if ( 'yes' === $( '#enable_sumo_bookings' ).val() ) {
                    $( '#_sumo_pp_set_expired_deposit_payment_as' ).closest( 'p' ).hide();
                    $( '#_sumo_pp_pay_balance_before' ).closest( 'span' ).hide();
                    $( '#_sumo_pp_pay_balance_before_booked_date' ).closest( 'span' ).show();
                    $( '#_sumo_pp_pay_balance_before_booked_date' ).attr( 'style', 'width:20%;display:block;' );
                }
            }
        },
        get_booking : function( $booking_status ) {
            _sumo_pp_simple_product.get_pay_balance( $( '#_sumo_pp_pay_balance_type' ).val() );
        },
        paymentPlansSelector : {
            onClickAddColumn1Plan : function( evt ) {
                evt.stopImmediatePropagation();
                evt.preventDefault();
                _sumo_pp_simple_product.paymentPlansSelector.addPlanSearchField( 'col_1' );
            },
            onClickAddColumn2Plan : function( evt ) {
                evt.stopImmediatePropagation();
                evt.preventDefault();
                _sumo_pp_simple_product.paymentPlansSelector.addPlanSearchField( 'col_2' );
            },
            onClickRemovePlan : function( evt ) {
                $( this ).closest( 'tr' ).remove();
                return false;
            },
            addPlanSearchField : function( col ) {
                var rowID = $( 'table._sumo_pp_selected_col_' + col + '_plans' ).find( 'tbody tr' ).length;
                $( '.spinner' ).addClass( 'is-active' );

                $.ajax( {
                    type : 'POST',
                    url : ajaxurl,
                    data : {
                        action : '_sumo_pp_get_payment_plan_search_field',
                        security : sumo_pp_admin_product_params.plan_search_nonce,
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
                        $( '.spinner' ).removeClass( 'is-active' );
                    }
                } );
                return false;
            },
            hide : function() {
                $( '._sumo_pp_add_plans' ).closest( 'p' ).hide();
                $( '._sumo_pp_selected_plans' ).hide();
            },
            show : function() {
                $( '._sumo_pp_add_plans' ).closest( 'p' ).show();
                $( '._sumo_pp_selected_plans' ).show();
            },
        },
    };

    var _sumo_pp_variation_product = {
        /**
         * Payment Plans Variation Actions.
         */
        init : function( variation_row_index ) {

            this.trigger_on_page_load( variation_row_index );

            $( document ).on( 'change', '#_sumo_pp_enable_sumopaymentplans' + variation_row_index, { i : variation_row_index }, this.toggle_payment_settings );
            $( document ).on( 'change', '#_sumo_pp_payment_type' + variation_row_index, { i : variation_row_index }, this.toggle_payment_type );
            $( document ).on( 'click', '._sumo_pp_schedule_availability' + variation_row_index, { i : variation_row_index }, this.toggle_schedule_availability );
            $( document ).on( 'click', '._sumo_pp_cancel_availability' + variation_row_index, { i : variation_row_index }, this.toggle_cancel_availability );
            $( document ).on( 'change', '#_sumo_pp_apply_global_settings' + variation_row_index, { i : variation_row_index }, this.toggle_global_settings );
            $( document ).on( 'change', '#_sumo_pp_deposit_type' + variation_row_index, { i : variation_row_index }, this.toggle_deposit_type );
            $( document ).on( 'change', '#_sumo_pp_deposit_price_type' + variation_row_index, { i : variation_row_index }, this.toggle_deposit_price_type );
            $( document ).on( 'change', '#_sumo_pp_user_defined_deposit_type' + variation_row_index, { i : variation_row_index }, this.toggle_user_defined_deposit_type );
            $( document ).on( 'change', '#_sumo_pp_pay_balance_type' + variation_row_index, { i : variation_row_index }, this.toggle_pay_balance_type );
            $( document ).on( 'change', '#_sumo_wcpo_enable_sumopreorders' + variation_row_index, { i : variation_row_index }, this.toggle_preorder_method );
            $( document ).on( 'change', '#_sumo_wcpo_preorder_method' + variation_row_index, { i : variation_row_index }, this.toggle_preorder_method );
            $( document ).on( 'click', '#_sumo_pp_add_col_1_plan' + variation_row_index, { i : variation_row_index }, this.paymentPlansSelector.onClickAddColumn1Plan );
            $( document ).on( 'click', '#_sumo_pp_add_col_2_plan' + variation_row_index, { i : variation_row_index }, this.paymentPlansSelector.onClickAddColumn2Plan );
            $( 'table._sumo_pp_selected_plans' + variation_row_index ).on( 'click', 'a.remove_row', { i : variation_row_index }, this.paymentPlansSelector.onClickRemovePlan );
        },
        trigger_on_page_load : function( i ) {

            this.get_payment_settings( $( '#_sumo_pp_enable_sumopaymentplans' + i ).is( ':checked' ), i );

            $( '#_sumo_pp_pay_balance_before' + i ).datepicker( {
                minDate : 0,
                changeMonth : true,
                dateFormat : 'yy-mm-dd',
                numberOfMonths : 1,
                showButtonPanel : true,
                defaultDate : '',
                showOn : 'focus',
                buttonImageOnly : true
            } );

            $( '._sumo_pp_availability_dates_field' + i ).each( function() {
                $( this ).find( 'input' ).datepicker( {
                    defaultDate : '',
                    dateFormat : 'yy-mm-dd',
                    numberOfMonths : 1,
                    showButtonPanel : true,
                    onSelect : function() {
                        date_picker_select( $( this ) );
                    }
                } );
                $( this ).find( 'input' ).each( function() {
                    date_picker_select( $( this ) );
                } );
            } );

            _sumo_pp_product.sortable();
        },
        toggle_payment_settings : function( evt ) {
            var $payment_settings_enabled = $( evt.currentTarget ).is( ':checked' );

            _sumo_pp_variation_product.get_payment_settings( $payment_settings_enabled, evt.data.i );
        },
        toggle_payment_type : function( evt ) {
            var $payment_type = $( evt.currentTarget ).val();

            _sumo_pp_variation_product.get_payment_type( $payment_type, evt.data.i );
        },
        toggle_schedule_availability : function( evt ) {
            _sumo_pp_variation_product.get_schedule_availability( evt.data.i );
            return false;
        },
        toggle_cancel_availability : function( evt ) {
            _sumo_pp_variation_product.get_cancel_availability( evt.data.i );
            return false;
        },
        toggle_global_settings : function( evt ) {
            var $apply_global_settings = $( evt.currentTarget ).is( ':checked' );

            _sumo_pp_variation_product.get_global_settings( $apply_global_settings, evt.data.i );
        },
        toggle_deposit_type : function( evt ) {
            var $deposit_type = $( evt.currentTarget ).val();

            _sumo_pp_variation_product.get_deposit_type( $deposit_type, evt.data.i );
        },
        toggle_deposit_price_type : function( evt ) {
            var $deposit_price_type = $( evt.currentTarget ).val();

            _sumo_pp_variation_product.get_deposit_price_type( $deposit_price_type, evt.data.i );
        },
        toggle_user_defined_deposit_type : function( evt ) {
            var $user_defined_deposit_type = $( evt.currentTarget ).val();

            _sumo_pp_variation_product.get_user_defined_deposit_type( $user_defined_deposit_type, evt.data.i );
        },
        toggle_pay_balance_type : function( evt ) {
            var $pay_balance_type = $( evt.currentTarget ).val();

            _sumo_pp_variation_product.get_pay_balance( $pay_balance_type, evt.data.i );
        },
        toggle_preorder_method : function( evt ) {
            _sumo_pp_variation_product.get_payment_settings( $( '#_sumo_pp_enable_sumopaymentplans' + evt.data.i ).is( ':checked' ), evt.data.i );
        },
        get_payment_settings : function( $payment_settings_enabled, i ) {
            $payment_settings_enabled = $payment_settings_enabled || '';

            if ( $payment_settings_enabled === true ) {
                $( '._sumo_pp_fields' + i ).show();
                _sumo_pp_variation_product.get_payment_type( $( '#_sumo_pp_payment_type' + i ).val(), i );
            } else {
                $( '._sumo_pp_fields' + i ).hide();
            }
        },
        get_payment_type : function( $payment_type, i, $do_not_apply_gobal ) {
            $payment_type = $payment_type || 'payment-plans';
            $do_not_apply_gobal = $do_not_apply_gobal || false;

            $( '#_sumo_pp_deposit_type' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_deposit_price_type' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_pay_balance_type' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_set_expired_deposit_payment_as' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_fixed_deposit_price' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_fixed_deposit_percent' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_user_defined_deposit_type' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_min_user_defined_deposit_price' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_max_user_defined_deposit_price' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_min_deposit' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_max_deposit' + i ).closest( 'p' ).hide();
            _sumo_pp_variation_product.paymentPlansSelector.show( i );

            if ( 'pay-in-deposit' === $payment_type ) {
                $( '#_sumo_pp_deposit_type' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_deposit_price_type' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_pay_balance_type' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_fixed_deposit_price' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_fixed_deposit_percent' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_user_defined_deposit_type' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_min_user_defined_deposit_price' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_max_user_defined_deposit_price' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_min_deposit' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_max_deposit' + i ).closest( 'p' ).show();
                _sumo_pp_variation_product.paymentPlansSelector.hide( i );
                _sumo_pp_variation_product.get_deposit_type( $( '#_sumo_pp_deposit_type' + i ).val(), i );
            }

            if ( false === $do_not_apply_gobal ) {
                _sumo_pp_variation_product.get_global_settings( $( '#_sumo_pp_apply_global_settings' + i ).is( ':checked' ), i );
            }

            $( '._sumo_pp_availability_dates_field' + i ).each( function() {
                var $these_dates = $( this );
                var schedule_set = false;

                $these_dates.find( 'input' ).each( function() {
                    if ( '' !== $( this ).val() ) {
                        schedule_set = true;
                    }
                } );

                if ( schedule_set ) {
                    _sumo_pp_variation_product.get_schedule_availability( i );
                } else {
                    _sumo_pp_variation_product.get_cancel_availability( i );
                }
            } );
        },
        get_schedule_availability : function( i ) {
            $( '._sumo_pp_schedule_availability' + i ).hide();
            $( '._sumo_pp_availability_dates_field' + i ).show();
            $( '._sumo_pp_cancel_availability' + i ).show();
        },
        get_cancel_availability : function( i ) {
            $( '._sumo_pp_cancel_availability' + i ).hide();
            $( '._sumo_pp_schedule_availability' + i ).show();
            $( '._sumo_pp_availability_dates_field' + i ).hide();
            $( '._sumo_pp_availability_dates_field' + i ).find( 'input' ).val( '' );
        },
        get_global_settings : function( $apply_global_settings, i ) {
            $apply_global_settings = $apply_global_settings || '';

            if ( true === $apply_global_settings ) {
                $( '#_sumo_pp_force_deposit' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_deposit_type' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_deposit_price_type' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_pay_balance_type' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_set_expired_deposit_payment_as' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_fixed_deposit_price' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_fixed_deposit_percent' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_user_defined_deposit_type' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_min_user_defined_deposit_price' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_max_user_defined_deposit_price' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_min_deposit' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_max_deposit' + i ).closest( 'p' ).hide();
                _sumo_pp_variation_product.paymentPlansSelector.hide( i );
            } else {
                $( '#_sumo_pp_force_deposit' + i ).closest( 'p' ).show();
                _sumo_pp_variation_product.get_payment_type( $( '#_sumo_pp_payment_type' + i ).val(), i, true );
            }
        },
        get_deposit_type : function( $deposit_type, i ) {
            $deposit_type = $deposit_type || 'user-defined';

            $( '#_sumo_pp_deposit_price_type' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_fixed_deposit_price' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_fixed_deposit_percent' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_user_defined_deposit_type' + i ).closest( 'p' ).show();
            $( '#_sumo_pp_min_user_defined_deposit_price' + i ).closest( 'p' ).show();
            $( '#_sumo_pp_max_user_defined_deposit_price' + i ).closest( 'p' ).show();
            $( '#_sumo_pp_min_deposit' + i ).closest( 'p' ).show();
            $( '#_sumo_pp_max_deposit' + i ).closest( 'p' ).show();
            _sumo_pp_variation_product.get_user_defined_deposit_type( $( '#_sumo_pp_user_defined_deposit_type' + i ).val(), i );

            if ( 'pre-defined' === $deposit_type ) {
                $( '#_sumo_pp_deposit_price_type' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_fixed_deposit_price' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_fixed_deposit_percent' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_user_defined_deposit_type' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_min_user_defined_deposit_price' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_max_user_defined_deposit_price' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_min_deposit' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_max_deposit' + i ).closest( 'p' ).hide();
                _sumo_pp_variation_product.get_deposit_price_type( $( '#_sumo_pp_deposit_price_type' + i ).val(), i );
            }

            if ( $( 'p' ).find( '#_sumo_wcpo_enable_sumopreorders' + i ).is( ':checked' ) && 'pay-infront' === $( 'p' ).find( '#_sumo_wcpo_preorder_method' + i ).val() ) {
                $( '#_sumo_pp_pay_balance_type' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_pay_balance_after' + i ).closest( 'span' ).hide();
                $( '#_sumo_pp_pay_balance_before' + i ).closest( 'span' ).hide();
                $( '#_sumo_pp_set_expired_deposit_payment_as' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_pay_balance_before_booked_date' + i ).closest( 'span' ).hide();
            } else {
                $( '#_sumo_pp_pay_balance_type' + i ).closest( 'p' ).show();
                _sumo_pp_variation_product.get_pay_balance( $( '#_sumo_pp_pay_balance_type' + i ).val(), i );
            }
        },
        get_deposit_price_type : function( $deposit_price_type, i ) {
            $deposit_price_type = $deposit_price_type || 'percent-of-product-price';

            $( '#_sumo_pp_fixed_deposit_price' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_fixed_deposit_percent' + i ).closest( 'p' ).show();

            if ( 'fixed-price' === $deposit_price_type ) {
                $( '#_sumo_pp_fixed_deposit_price' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_fixed_deposit_percent' + i ).closest( 'p' ).hide();
            }
        },
        get_user_defined_deposit_type : function( $user_defined_deposit_type, i ) {
            $user_defined_deposit_type = $user_defined_deposit_type || 'percent-of-product-price';

            $( '#_sumo_pp_min_deposit' + i ).closest( 'p' ).show();
            $( '#_sumo_pp_max_deposit' + i ).closest( 'p' ).show();
            $( '#_sumo_pp_min_user_defined_deposit_price' + i ).closest( 'p' ).hide();
            $( '#_sumo_pp_max_user_defined_deposit_price' + i ).closest( 'p' ).hide();

            if ( 'fixed-price' === $user_defined_deposit_type ) {
                $( '#_sumo_pp_min_user_defined_deposit_price' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_max_user_defined_deposit_price' + i ).closest( 'p' ).show();
                $( '#_sumo_pp_min_deposit' + i ).closest( 'p' ).hide();
                $( '#_sumo_pp_max_deposit' + i ).closest( 'p' ).hide();
            }
        },
        get_pay_balance : function( $pay_balance_type, i ) {
            $pay_balance_type = $pay_balance_type || 'after';

            $( '#_sumo_pp_pay_balance_after' + i ).closest( 'span' ).show();
            $( '#_sumo_pp_pay_balance_before' + i ).closest( 'span' ).hide();
            $( '#_sumo_pp_set_expired_deposit_payment_as' + i ).closest( 'p' ).hide();

            if ( 'before' === $pay_balance_type ) {
                $( '#_sumo_pp_pay_balance_after' + i ).closest( 'span' ).hide();
                $( '#_sumo_pp_pay_balance_before' + i ).closest( 'span' ).show();
                $( '#_sumo_pp_set_expired_deposit_payment_as' + i ).closest( 'p' ).show();
            }
        },
        paymentPlansSelector : {
            onClickAddColumn1Plan : function( evt ) {
                evt.stopImmediatePropagation();
                evt.preventDefault();
                _sumo_pp_variation_product.paymentPlansSelector.addPlanSearchField( 'col_1', evt.data.i );
            },
            onClickAddColumn2Plan : function( evt ) {
                evt.stopImmediatePropagation();
                evt.preventDefault();
                _sumo_pp_variation_product.paymentPlansSelector.addPlanSearchField( 'col_2', evt.data.i );
            },
            onClickRemovePlan : function( evt ) {
                $( this ).closest( 'tr' ).remove();
                return false;
            },
            addPlanSearchField : function( col, i ) {
                var rowID = $( 'table._sumo_pp_selected_col_' + col + '_plans' + i ).find( 'tbody tr' ).length;
                $( '.spinner' ).addClass( 'is-active' );

                $.ajax( {
                    type : 'POST',
                    url : ajaxurl,
                    data : {
                        action : '_sumo_pp_get_payment_plan_search_field',
                        security : sumo_pp_admin_product_params.plan_search_nonce,
                        rowID : rowID,
                        col : col,
                        loop : i,
                    },
                    success : function( data ) {

                        if ( typeof data !== 'undefined' ) {
                            $( '<tr><td class="sort" width="1%"></td>\n\
                                    <td>' + data.search_field + '</td><td><a href="#" class="remove_row button">X</a></td>\n\
                                    </tr>' ).appendTo( $( 'table._sumo_pp_selected_col_' + col + '_plans' + i ).find( 'tbody' ) );
                            $( document.body ).trigger( 'wc-enhanced-select-init' );
                        }
                    },
                    complete : function() {
                        $( '.spinner' ).removeClass( 'is-active' );
                    }
                } );
                return false;
            },
            hide : function( i ) {
                $( '._sumo_pp_add_plans' + i ).closest( 'p' ).hide();
                $( '._sumo_pp_selected_plans' + i ).hide();
            },
            show : function( i ) {
                $( '._sumo_pp_add_plans' + i ).closest( 'p' ).show();
                $( '._sumo_pp_selected_plans' + i ).show();
            },
        },
    };

    _sumo_pp_product.init();

} );
