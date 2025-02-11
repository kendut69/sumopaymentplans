/* global sumo_pp_single_product_params */

jQuery( function( $ ) {

    if ( typeof sumo_pp_single_product_params === 'undefined' ) {
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

    var variation_form = {
        $form : $( '.variations_form' ),
        init : function() {
            if ( 'from-plugin' === sumo_pp_single_product_params.variation_deposit_form_template ) {
                $( document ).on( 'change', '.variations select', this.legacy.toggleVariations );
                $( document ).on( 'found_variation.wc-variation-form', { variationForm : this }, this.legacy.onFoundVariation );
                $( document ).on( 'click.wc-variation-form', '.reset_variations', { variationForm : this }, this.legacy.onResetVariation );
            } else {
                $( document ).on( 'found_variation.wc-variation-form', this.onFoundVariation );
                $( document ).on( 'reset_data', this.onResetVariation );
            }
        },
        onFoundVariation : function( evt, variation ) {
            var variation_id = $( 'input[name=variation_id]' ).val();

            if ( variation.variation_id == variation_id ) {
                variation_form.onResetVariation();

                if ( variation.sumo_pp_deposit_form ) {
                    single_product.priceHtml = '';
                    variation_form.$form.find( '.woocommerce-variation-add-to-cart' ).before( variation.sumo_pp_deposit_form );

                    $( 'input[type=radio][name="_sumo_pp_payment_type"]:checked' ).change();

                    if ( 'yes' === sumo_pp_single_product_params.hide_product_price && 'payment-plans' === $( variation.sumo_pp_deposit_form ).find( 'input[type=radio][name="_sumo_pp_payment_type"]' ).val() ) {
                        single_product.hideProductGetPrice();
                    } else {
                        single_product.showProductGetPrice();
                    }
                }
            }
        },
        onResetVariation : function( evt, variation ) {
            if ( variation_form.$form.find( '#_sumo_pp_payment_type_fields' ).length ) {
                variation_form.$form.find( '#_sumo_pp_payment_type_fields' ).remove();
            }
        },
        legacy : {
            getSingleAddToCartVariationData : function() {
                var $hidden_datas = $( 'form' ).find( '#_sumo_pp_single_variation_data' ).data();

                if ( 'undefined' !== typeof $hidden_datas ) {
                    var beforeVariationData = '',
                            afterVariationData = '';

                    $.each( $hidden_datas, function( context, data ) {
                        switch ( context ) {
                            case 'payment_type_fields_' + variation_form.legacy.variation_id:
                                beforeVariationData += data;
                                break;
                        }
                    } );

                    if ( '' !== beforeVariationData || '' !== afterVariationData ) {
                        if ( '' !== beforeVariationData ) {
                            $( 'span#_sumo_pp_before_single_variation' ).html( beforeVariationData );
                        }
                        if ( '' !== afterVariationData ) {
                            $( 'span#_sumo_pp_after_single_variation' ).html( afterVariationData );
                        }
                    }

                    if ( 'yes' === sumo_pp_single_product_params.hide_product_price && 'payment-plans' === $( 'div#_sumo_pp_payment_type_fields' ).find( 'p input[name="_sumo_pp_payment_type"][type="hidden"]' ).val() ) {
                        single_product.hideProductGetPrice();
                    } else {
                        single_product.showProductGetPrice();
                    }
                }
            },
            onFoundVariation : function( evt, variation ) {
                var variation_id = $( 'input[name=variation_id]' ).val();
                variation_form.legacy.variation_id = variation.variation_id;

                if ( variation_id == variation_form.legacy.variation_id ) {
                    variation_form.legacy.onResetVariation();

                    if ( '' !== variation_form.legacy.variation_id ) {
                        single_product.priceHtml = '';
                        variation_form.legacy.getSingleAddToCartVariationData();

                        $( 'input[type=radio][name="_sumo_pp_payment_type"]:checked' ).change();
                    }
                }
            },
            toggleVariations : function() {
                variation_form.legacy.variation_id = $( 'input[name="variation_id"]' ).val();

                if ( '' !== variation_form.legacy.variation_id ) {
                    $.each( $( 'form' ).find( '#_sumo_pp_single_variations' ).data( 'variations' ), function( index, variation_id ) {
                        if ( variation_id == variation_form.legacy.variation_id ) {
                            variation_form.legacy.getSingleAddToCartVariationData();
                        }
                    } );

                    $( 'input[type=radio][name="_sumo_pp_payment_type"]:checked' ).change();
                } else {
                    variation_form.legacy.onResetVariation();
                }
            },
            onResetVariation : function( evt, variation ) {
                $( 'span#_sumo_pp_before_single_variation, span#_sumo_pp_after_single_variation' ).html( '' );
            }
        },
    };

    var single_product = {
        depositForm : $( '#_sumo_pp_payment_type_fields' ),
        bookingDepositForm : $( '._sumo_pp_wc_booking_deposit_fields' ),
        priceHtml : '',
        /**
         * Init single product
         */
        init : function() {
            if ( single_product.bookingDepositForm.length ) {
                single_product.bookingDepositForm.insertBefore( '.single_add_to_cart_button' );
            }

            $( document ).on( 'change', 'input[type=radio][name="_sumo_pp_payment_type"]', this.togglePaymentType );
            $( document ).on( 'wc_booking_form_changed', this.bookingFormChanged );
            $( document ).on( 'sumo_bookings_calculated_price', this.bookingFormChanged );
            $( document ).on( 'yith_wcbk_form_update_response', this.bookingFormChanged );
            $( document ).on( 'click', '._sumo_pp_plan_view_more a.view-plan-more,._sumo_pp_plan_view_more a', this.planInfoModal.show );
            $( document ).on( 'click', '._sumo_pp_modal ._sumo_pp_modal-close > img', this.planInfoModal.hide );
            variation_form.init();

            $( 'input[type=radio][name="_sumo_pp_payment_type"]:checked' ).change();
        },
        togglePaymentType : function( evt ) {
            var $product_props = $( evt.currentTarget ).closest( 'div' ).data( 'product_props' ),
                    selectedPaymentType = $( evt.currentTarget ).val(),
                    isVariation = $( 'div.woocommerce-variation-price' ).find( 'span.price' ).length > 0;

            $( evt.currentTarget ).closest( 'div' ).find( 'div#_sumo_pp_plans_to_choose' ).hide();
            $( evt.currentTarget ).closest( 'div' ).find( 'div#_sumo_pp_amount_to_choose' ).hide();
            single_product.showProductGetPrice();

            switch ( selectedPaymentType ) {
                case 'payment-plans':
                    $( evt.currentTarget ).closest( 'div' ).find( 'div#_sumo_pp_plans_to_choose' ).slideDown( 'fast' );

                    if ( 'yes' === sumo_pp_single_product_params.hide_product_price ) {
                        single_product.hideProductGetPrice();
                    }
                    break;
                case 'pay-in-deposit':
                    $( evt.currentTarget ).closest( 'div' ).find( 'div#_sumo_pp_amount_to_choose' ).slideDown( 'fast' );
                    break;
            }

            if ( undefined !== $product_props.product_price_html && 'regular-price' === sumo_pp_single_product_params.price_based_on ) {
                if ( '' === single_product.priceHtml ) {
                    if ( isVariation ) {
                        single_product.priceHtml = $( 'div.woocommerce-variation-price' ).find( 'span.price' ).html();
                    } else {
                        single_product.priceHtml = $( 'div' ).find( 'p.price' ).html();
                    }
                }

                if ( isVariation ) {
                    $( 'div.woocommerce-variation-price' ).find( 'span.price' ).html( '' );
                } else {
                    $( 'div' ).find( 'p.price' ).html( '' );
                }

                if ( ( 'payment-plans' === selectedPaymentType || 'pay-in-deposit' === selectedPaymentType ) && 'yes' === $product_props.is_on_sale ) {
                    if ( isVariation ) {
                        $( 'div.woocommerce-variation-price' ).find( 'span.price' ).append( $product_props.product_price_html );
                    } else {
                        $( 'div' ).find( 'p.price' ).append( $product_props.product_price_html );
                    }
                } else {
                    if ( isVariation ) {
                        $( 'div.woocommerce-variation-price' ).find( 'span.price' ).append( single_product.priceHtml );
                    } else {
                        $( 'div' ).find( 'p.price' ).append( single_product.priceHtml );
                    }
                }
            }
        },
        showProductGetPrice : function() {
            $( 'div' ).find( 'p.price' ).slideDown( 'fast' );
            $( 'div.woocommerce-variation-price' ).find( 'span.price' ).slideDown( 'fast' );
        },
        hideProductGetPrice : function() {
            $( 'div' ).find( 'p.price' ).slideUp( 'fast' );
            $( 'div.woocommerce-variation-price' ).find( 'span.price' ).slideUp( 'fast' );
        },
        bookingFormChanged : function() {
            $.blockUI.defaults.overlayCSS.cursor = 'wait';
            block( single_product.depositForm );

            $.ajax( {
                type : 'POST',
                url : sumo_pp_single_product_params.wp_ajax_url,
                data : {
                    action : '_sumo_pp_get_wc_booking_deposit_fields',
                    security : sumo_pp_single_product_params.get_wc_booking_deposit_fields_nonce,
                    product : sumo_pp_single_product_params.product,
                },
                success : function( data ) {
                    if ( 'undefined' !== typeof data.result && 'success' === data.result ) {
                        single_product.depositForm.remove();

                        $( 'span#_sumo_pp_wc_booking_deposit_fields' ).html( data.html );
                        $( 'span#_sumo_pp_wc_booking_deposit_fields' ).insertBefore( '.single_add_to_cart_button' );
                    }
                },
                complete : function() {
                    unblock( single_product.depositForm );
                }
            } );
        },
        planInfoModal : {
            show : function( evt ) {
                evt.preventDefault();
                $( this ).closest( 'div' ).find( '._sumo_pp_modal' ).show();
            },
            hide : function() {
                $( this ).closest( '._sumo_pp_modal' ).hide();
            },
        },
    };

    single_product.init();
} );
