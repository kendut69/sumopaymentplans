( () => {
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
    var external_wc_blocksCheckout = window["wc"]["blocksCheckout"];
    var external_wc_priceFormat = window["wc"]["priceFormat"];
    var external_wc_settings = window["wc"]["wcSettings"];

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
        cartBlocks : {
            orderPaymentplan : {
                cartSchema : JSON.parse( "{\"name\":\"woocommerce/cart-order-summary-sumo-pp-order-paymentplan-block\",\"icon\":\"schedule\",\"keywords\":[\"payment\",\"plan\"],\"version\":\"1.0.0\",\"title\":\"Order Paymentplan\",\"description\":\"Shows the order paymentplan form.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-totals-block\"],\"textdomain\":\"sumopaymentplans\",\"apiVersion\":2}" ),
                checkoutSchema : JSON.parse( "{\"name\":\"woocommerce/checkout-order-summary-sumo-pp-order-paymentplan-block\",\"icon\":\"schedule\",\"keywords\":[\"payment\",\"plan\"],\"version\":\"1.0.0\",\"title\":\"Order Paymentplan\",\"description\":\"Shows the order paymentplan form.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-totals-block\"],\"textdomain\":\"sumopaymentplans\",\"apiVersion\":2}" ),
                init : function() {
                    return external_element.createElement( external_wc_blocksCheckout.TotalsWrapper, { className : "sumo-pp-order-paymentplan-form-wrapper" },
                            external_element.createElement( callBack.cartBlocks.orderPaymentplan.subscribeOption ) );
                },
                edit : function( e ) {
                    return external_element.createElement( "div", external_blockEditor.useBlockProps(),
                            external_element.createElement( callBack.cartBlocks.orderPaymentplan.init ) );
                },
                save : function( e ) {
                    return external_element.createElement( "div", external_blockEditor.useBlockProps.save() );
                },
                subscribeOption : function( e ) {
                    return external_element.createElement( external_wc_blocksCheckout.TotalsItem, {
                        className : "sumo-pp-order-paymentplan-form-wrapper__subscribe-option",
                        label : external_element.createElement( external_wc_blocksCheckout.CheckboxControl, {
                            className : "sumo-pp-subscribe-order",
                            disabled : true
                        }, external_i18n.__( "Enable Order Subscription", 'sumopaymentplans' ) ) } );
                }
            }
        }
    };

    // Register Block in the Editor.
    external_blocks.registerBlockType( callBack.cartBlocks.orderPaymentplan.cartSchema.name, {
        title : callBack.cartBlocks.orderPaymentplan.cartSchema.title, // Localize title using wp.i18n.__()
        version : callBack.cartBlocks.orderPaymentplan.cartSchema.version,
        description : callBack.cartBlocks.orderPaymentplan.cartSchema.description,
        category : callBack.cartBlocks.orderPaymentplan.cartSchema.category, // Category Options: common, formatting, layout, widgets, embed
        supports : callBack.cartBlocks.orderPaymentplan.cartSchema.supports,
        icon : callBack.cartBlocks.orderPaymentplan.cartSchema.icon, // Dashicons Options – https://goo.gl/aTM1DQ
        keywords : callBack.cartBlocks.orderPaymentplan.cartSchema.keywords, // Limit to 3 Keywords / Phrases
        parent : callBack.cartBlocks.orderPaymentplan.cartSchema.parent,
        textdomain : callBack.cartBlocks.orderPaymentplan.cartSchema.textdomain,
        apiVersion : callBack.cartBlocks.orderPaymentplan.cartSchema.apiVersion,
        attributes : callBack.cartBlocks.orderPaymentplan.cartSchema.attributes, // Attributes set for each piece of dynamic data used in your block
        edit : callBack.cartBlocks.orderPaymentplan.edit, // Determines what is displayed in the editor
        save : callBack.cartBlocks.orderPaymentplan.save // Determines what is displayed on the frontend
    } );

    external_blocks.registerBlockType( callBack.cartBlocks.orderPaymentplan.checkoutSchema.name, {
        title : callBack.cartBlocks.orderPaymentplan.checkoutSchema.title, // Localize title using wp.i18n.__()
        version : callBack.cartBlocks.orderPaymentplan.checkoutSchema.version,
        description : callBack.cartBlocks.orderPaymentplan.checkoutSchema.description,
        category : callBack.cartBlocks.orderPaymentplan.checkoutSchema.category, // Category Options: common, formatting, layout, widgets, embed
        supports : callBack.cartBlocks.orderPaymentplan.checkoutSchema.supports,
        icon : callBack.cartBlocks.orderPaymentplan.checkoutSchema.icon, // Dashicons Options – https://goo.gl/aTM1DQ
        keywords : callBack.cartBlocks.orderPaymentplan.checkoutSchema.keywords, // Limit to 3 Keywords / Phrases
        parent : callBack.cartBlocks.orderPaymentplan.checkoutSchema.parent,
        textdomain : callBack.cartBlocks.orderPaymentplan.checkoutSchema.textdomain,
        apiVersion : callBack.cartBlocks.orderPaymentplan.checkoutSchema.apiVersion,
        attributes : callBack.cartBlocks.orderPaymentplan.checkoutSchema.attributes, // Attributes set for each piece of dynamic data used in your block
        edit : callBack.cartBlocks.orderPaymentplan.edit, // Determines what is displayed in the editor
        save : callBack.cartBlocks.orderPaymentplan.save // Determines what is displayed on the frontend
    } );
} )();