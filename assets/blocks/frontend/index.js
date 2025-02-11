( ( ) => {
    'use strict';

    var external_plugins = window["wp"]["plugins"];
    var external_element = window["wp"]["element"];
    var external_blocks = window["wp"]["blocks"];
    var external_blockEditor = window["wp"]["blockEditor"];
    var external_i18n = window["wp"]["i18n"];
    var external_data = window["wp"]["data"];
    var external_compose = window["wp"]["compose"];
    var external_components = window["wp"]["components"];
    var external_primitives = window["wp"]["primitives"];
    var external_htmlEntities = window["wp"]["htmlEntities"];
    var external_wc_blocksCheckout = window["wc"]["blocksCheckout"];
    var external_wc_priceFormat = window["wc"]["priceFormat"];
    var external_wc_settings = window["wc"]["wcSettings"];    
    var external_wc_BlocksRegistry = window["wc"]["wcBlocksRegistry"];
    
    const paymentMethod = external_wc_settings.getPaymentMethodData( "sumo_pp_paypal_reference_txns" ),
            label = external_htmlEntities.decodeEntities( null !== paymentMethod ? paymentMethod.title : "" ),
            description = external_htmlEntities.decodeEntities( null !== paymentMethod ? paymentMethod.description : "" );
            
    const paymentMethodContent = function( e ) {
        return external_element.createElement( external_element.RawHTML, null, description );
    };
        
    var callBack = {
        ourData : null,
        isOurs : function( e ) {
            // Bail out early.
            if ( undefined === e['sumopaymentplans'] ) {
                return false;
            }

            callBack.ourData = e['sumopaymentplans'];
            return true;
        },
        cartFilters : {
            cartItemClass : function( v, e, a ) {
                if ( callBack.isOurs( e ) && callBack.ourData.is_sumopp ) {
                    v = 'sumopp';
                }

                return v;
            },
            totalLabel : function( v, e, a ) {
                if ( callBack.isOurs( e ) && callBack.ourData.is_sumopp ) {
                    v = external_i18n.__( "Payable Now", 'sumopaymentplans' );
                }

                return v;
            }
        },
        cartElements : {
            init : function( e ) {
                if ( callBack.isOurs( e.extensions ) && callBack.ourData.is_sumopp ) {
                    return external_element.createElement( "div", null, external_element.createElement( callBack.cartElements.paymentDetails.total ),
                            external_element.createElement( callBack.cartElements.paymentDetails.futurePayments ) );
                } else if ( callBack.isOurs( e.extensions ) && callBack.ourData.product_paymentplan_enabled ) {
                    return external_element.createElement( "div", null, external_element.createElement( callBack.cartElements.balanceDetails.balance ),
                            );
                }

                return null;
            },
            paymentDetails : {
                total : function( e ) {
                    return external_element.createElement( external_wc_blocksCheckout.TotalsWrapper, null,
                            external_element.createElement( external_wc_blocksCheckout.TotalsItem, {
                                className : "sumo-pp-order-paymentplan-total-payable-panel__title",
                                currency : external_wc_priceFormat.getCurrencyFromPriceResponse(),
                                label : external_i18n.__( "Total", 'sumopaymentplans' ),
                                value : parseInt( callBack.ourData.order_paymentplan_totals.total_payable_amount, 10 )
                            } ) );
                },
                futurePayments : function( e ) {
                    return external_element.createElement( external_wc_blocksCheckout.TotalsWrapper, { className : "sumo-pp-order-paymentplan-future-payments-panel" },
                            external_element.createElement( external_wc_blocksCheckout.TotalsItem, {
                                className : "sumo-pp-order-paymentplan-future-payments-panel__title",
                                label : external_i18n.__( "Payment Details:", 'sumopaymentplans' ),
                                value : external_element.createElement( external_element.RawHTML, null, callBack.ourData.order_paymentplan_future_payments_info )
                            } ) );
                }
            },
            balanceDetails : {
                balance : function( e ) {
                    return external_element.createElement( external_wc_blocksCheckout.TotalsWrapper, null,
                            external_element.createElement( external_wc_blocksCheckout.TotalsItem, {
                                className : "sumo-pp-product-paymentplan-total-payable-panel__title",
                                currency : external_wc_priceFormat.getCurrencyFromPriceResponse(),
                                label : external_i18n.__( "Installment Details :", 'sumopaymentplans' ),
                                value : external_element.createElement( external_element.RawHTML, null, callBack.ourData.product_paymentplan_future_payments_info )
                            } ) );
                },
            }
        },
        cartBlocks : {
            init : function( e ) {
                if ( callBack.isOurs( e.extensions ) && callBack.ourData.order_paymentplan_enabled ) {
                    return external_element.createElement( callBack.cartBlocks.orderPaymentplan.init, e );
                }

                return null;
            },
            orderPaymentplan : {
                cartSchema : JSON.parse( "{\"name\":\"woocommerce/cart-order-summary-sumo-pp-order-paymentplan-block\",\"icon\":\"schedule\",\"keywords\":[\"payment\",\"plan\"],\"version\":\"1.0.0\",\"title\":\"Order Paymentplan\",\"description\":\"Shows the order paymentplan form.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-totals-block\"],\"textdomain\":\"sumopaymentplans\",\"apiVersion\":2}" ),
                checkoutSchema : JSON.parse( "{\"name\":\"woocommerce/checkout-order-summary-sumo-pp-order-paymentplan-block\",\"icon\":\"schedule\",\"keywords\":[\"payment\",\"plan\"],\"version\":\"1.0.0\",\"title\":\"Order Paymentplan\",\"description\":\"Shows the order paymentplan form.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-totals-block\"],\"textdomain\":\"sumopaymentplans\",\"apiVersion\":2}" ),
                isLoading : false,
                setLoading : null,
                init : function( e ) {
                    return external_element.createElement( external_element.Fragment, null,
                            external_element.createElement( callBack.cartBlocks.orderPaymentplan.form, null ) );
                },
                form : function() {
                    [ callBack.cartBlocks.orderPaymentplan.isLoading, callBack.cartBlocks.orderPaymentplan.setLoading ] = external_element.useState( false );

                    return external_element.createElement( external_wc_blocksCheckout.TotalsWrapper, { className : "sumo-pp-order-paymentplan-form-wrapper " + ( callBack.cartBlocks.orderPaymentplan.isLoading ? "sumo-pp-component--disabled" : "" ) },
                            external_element.createElement( external_element.RawHTML, null, callBack.ourData.order_paymentplan_form_html ) );
                },
            }
        },
        render : function( ) {
            return external_element.createElement( external_element.Fragment, null,
                    external_element.createElement( external_wc_blocksCheckout.ExperimentalOrderMeta, null,
                            external_element.createElement( callBack.cartElements.init, null ) ) );
        }
    };

    jQuery( document ).on( 'sumopp_order_paymentplan_updated', function( e ) {
        callBack.cartBlocks.orderPaymentplan.setLoading( true );
        jQuery( '._sumo_pp_orderpp_fields select,._sumo_pp_orderpp_fields input' ).prop( 'disabled', true );
        external_wc_blocksCheckout.extensionCartUpdate( {
            namespace : 'sumopaymentplans',
            data : {
                action : 'refresh_cart'
            }
        } ).then( function( e ) {
            callBack.isOurs( e.extensions );

            if ( ! callBack.ourData.is_sumopp ) {
                jQuery( '._sumo_pp_orderpp_fields tr:eq(1)' ).slideUp();
            }
        } ).finally( function() {
            callBack.cartBlocks.orderPaymentplan.setLoading( false );
            jQuery( '._sumo_pp_orderpp_fields select,._sumo_pp_orderpp_fields input' ).prop( 'disabled', false );
        } );
    } );

    external_wc_blocksCheckout.registerCheckoutFilters( 'sumopaymentplans', {
        cartItemClass : callBack.cartFilters.cartItemClass,
        totalLabel : callBack.cartFilters.totalLabel
    } );

    external_wc_blocksCheckout.registerCheckoutBlock( {
        metadata : callBack.cartBlocks.orderPaymentplan.cartSchema,
        component : callBack.cartBlocks.init
    } );

    external_wc_blocksCheckout.registerCheckoutBlock( {
        metadata : callBack.cartBlocks.orderPaymentplan.checkoutSchema,
        component : callBack.cartBlocks.init
    } );

    external_plugins.registerPlugin( "sumopaymentplans", {
        render : callBack.render,
        scope : "woocommerce-checkout"
    } );
    
    external_wc_BlocksRegistry.registerPaymentMethod( {
        name : "sumo_pp_paypal_reference_txns",
        label : label,
        ariaLabel : label,
        content : external_element.createElement( paymentMethodContent ),
        edit : external_element.createElement( paymentMethodContent ),
        canMakePayment : ( e ) => {
            return null !== paymentMethod ? paymentMethod.is_available : false;
        },
        supports : {
            features : null !== paymentMethod ? paymentMethod.supports : [ ]
        }
    } );
    
} )( );