jQuery( function( $ ) {

    var orderpp = {
        init : function() {
            this.trigger_on_page_load();

            $( document ).on( 'change', '#_sumo_pp_enable_order_payment_plan', this.toggle_payment_settings );
            $( document ).on( 'change', '#_sumo_pp_order_payment_type', this.toggle_payment_type );
            $( document ).on( 'change', '#_sumo_pp_show_order_payment_plan_for', this.toggle_user_filter );
            $( document ).on( 'change', '#_sumo_pp_order_payment_plan_deposit_price_type', this.toggle_deposit_price_type );
            $( document ).on( 'change', '#_sumo_pp_get_order_payment_plan_products_select_type', this.toggle_products_select_type );
            $( document ).on( 'change', '#_sumo_pp_order_payment_plan_user_defined_deposit_type', this.toggle_user_defined_deposit_type );
            $( document ).on( 'change', '#_sumo_pp_apply_global_settings_for_order_payment_plan', this.toggle_global_settings );
            $( document ).on( 'change', '#_sumo_pp_order_payment_plan_deposit_type', this.toggle_deposit_type );
            $( document ).on( 'change', '#_sumo_pp_order_payment_plan_pay_balance_type', this.toggle_pay_balance_type );
        },
        trigger_on_page_load : function() {
            $( '#_sumo_pp_order_payment_plan_pay_balance_before' ).datepicker( {
                minDate : 0,
                changeMonth : true,
                dateFormat : 'yy-mm-dd',
                numberOfMonths : 1,
                showButtonPanel : true,
                defaultDate : '',
                showOn : 'focus',
                buttonImageOnly : true
            } );
            this.get_payment_settings( $( '#_sumo_pp_enable_order_payment_plan' ).is( ':checked' ) );
            this.get_products_select_type( $( '#_sumo_pp_get_order_payment_plan_products_select_type' ).val() );
        },
        toggle_payment_settings : function( evt ) {
            var $payment_settings_enabled = $( evt.currentTarget ).is( ':checked' );

            orderpp.get_payment_settings( $payment_settings_enabled );
        },
        toggle_payment_type : function( evt ) {
            var $payment_type = $( evt.currentTarget ).val();

            orderpp.get_payment_type( $payment_type );
        },
        toggle_user_filter : function( evt ) {
            var $filter = $( evt.currentTarget ).val();

            orderpp.get_user_filter( $filter );
        },
        toggle_deposit_price_type : function( evt ) {
            var $deposit_price_type = $( evt.currentTarget ).val();

            orderpp.get_deposit_price_type( $deposit_price_type );
        },
        toggle_products_select_type : function( evt ) {
            var $type = $( evt.currentTarget ).val();

            orderpp.get_products_select_type( $type );
        },
        toggle_global_settings : function( evt ) {
            var $apply_global_settings = $( evt.currentTarget ).is( ':checked' );

            orderpp.get_global_settings( $apply_global_settings );
        },
        toggle_deposit_type : function( evt ) {
            var $deposit_type = $( evt.currentTarget ).val();

            orderpp.get_deposit_type( $deposit_type );
        },
        toggle_user_defined_deposit_type : function( evt ) {
            var $user_defined_deposit_type = $( evt.currentTarget ).val();

            orderpp.get_user_defined_deposit_type( $user_defined_deposit_type );
        },
        toggle_pay_balance_type : function( evt ) {
            var $pay_balance_type = $( evt.currentTarget ).val();

            orderpp.get_pay_balance( $pay_balance_type );
        },
        get_user_filter : function( $filter ) {
            $( '#_sumo_pp_get_limited_userroles_of_order_payment_plan' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_get_limited_users_of_order_payment_plan' ).closest( 'tr' ).hide();

            if ( $.inArray( $filter, Array( 'include_users', 'exclude_users' ) ) !== - 1 ) {
                $( '#_sumo_pp_get_limited_users_of_order_payment_plan' ).closest( 'tr' ).show();
            } else if ( $.inArray( $filter, Array( 'include_user_role', 'exclude_user_role' ) ) !== - 1 ) {
                $( '#_sumo_pp_get_limited_userroles_of_order_payment_plan' ).closest( 'tr' ).show();
            }
        },
        get_deposit_price_type : function( $deposit_price_type ) {
            $deposit_price_type = $deposit_price_type || 'percent-of-product-price';

            $( '#_sumo_pp_fixed_order_payment_plan_deposit_price' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_fixed_order_payment_plan_deposit_percent' ).closest( 'tr' ).hide();

            if ( 'fixed-price' === $deposit_price_type ) {
                $( '#_sumo_pp_fixed_order_payment_plan_deposit_price' ).closest( 'tr' ).show();
            } else {
                $( '#_sumo_pp_fixed_order_payment_plan_deposit_percent' ).closest( 'tr' ).show();
            }
        },
        get_payment_settings : function( $payment_settings_enabled ) {
            $payment_settings_enabled = $payment_settings_enabled || '';

            if ( $payment_settings_enabled === true ) {
                $( '#_sumo_pp_get_order_payment_plan_products_select_type' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_type' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_plan_in_cart' ).closest( 'tr' ).show();
                $( '#_sumo_pp_apply_global_settings_for_order_payment_plan' ).closest( 'tr' ).show();
                $( '#_sumo_pp_force_order_payment_plan' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_plan_deposit_type' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_plan_user_defined_deposit_type' ).closest( 'tr' ).show();
                $( '#_sumo_pp_min_order_payment_plan_deposit' ).closest( 'tr' ).show();
                $( '#_sumo_pp_max_order_payment_plan_deposit' ).closest( 'tr' ).show();
                $( '#_sumo_pp_min_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).show();
                $( '#_sumo_pp_max_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).show();
                $( '#_sumo_pp_selected_plans_for_order_payment_plan' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_plan_product_label' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_plan_label' ).closest( 'tr' ).show();
                $( '#_sumo_pp_show_order_payment_plan_for' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_plan_form_position' ).closest( 'tr' ).show();
                $( '#_sumo_pp_min_order_total_to_display_order_payment_plan' ).closest( 'tr' ).show();
                $( '#_sumo_pp_max_order_total_to_display_order_payment_plan' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_plan_charge_shipping_during' ).closest( 'tr' ).show();
                orderpp.get_deposit_price_type( $( '#_sumo_pp_order_payment_plan_deposit_price_type' ).val() );
                orderpp.get_payment_type( $( '#_sumo_pp_order_payment_type' ).val() );
                orderpp.get_products_select_type( $( '#_sumo_pp_get_order_payment_plan_products_select_type' ).val() );
                orderpp.get_user_filter( $( '#_sumo_pp_show_order_payment_plan_for' ).val() );
            } else {
                $( '#_sumo_pp_get_order_payment_plan_products_select_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_get_included_products' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_in_cart' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_get_excluded_products' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_get_included_categories' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_get_excluded_categories' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_deposit_price_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_fixed_order_payment_plan_deposit_percent' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_fixed_order_payment_plan_deposit_price' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_apply_global_settings_for_order_payment_plan' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_force_order_payment_plan' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_deposit_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_user_defined_deposit_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_min_order_payment_plan_deposit' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_max_order_payment_plan_deposit' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_min_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_max_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_selected_plans_for_order_payment_plan' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_product_label' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_label' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_pay_balance_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_get_limited_userroles_of_order_payment_plan' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_show_order_payment_plan_for' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_get_limited_userroles_of_order_payment_plan' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_get_limited_users_of_order_payment_plan' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_form_position' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_min_order_total_to_display_order_payment_plan' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_max_order_total_to_display_order_payment_plan' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_charge_shipping_during' ).closest( 'tr' ).hide();
            }
        },
        get_products_select_type : function( $type ) {
            $type = $type || '';

            $( '#_sumo_pp_get_included_products' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_get_excluded_products' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_get_included_categories' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_get_excluded_categories' ).closest( 'tr' ).hide();

            switch ( $type ) {
                case 'included_products':
                    $( '#_sumo_pp_get_included_products' ).closest( 'tr' ).show();
                    break;
                case 'excluded_products':
                    $( '#_sumo_pp_get_excluded_products' ).closest( 'tr' ).show();
                    break;
                case 'included_categories':
                    $( '#_sumo_pp_get_included_categories' ).closest( 'tr' ).show();
                    break;
                case 'excluded_categories':
                    $( '#_sumo_pp_get_excluded_categories' ).closest( 'tr' ).show();
                    break;
            }
        },
        get_payment_type : function( $payment_type, $do_not_apply_gobal ) {
            $payment_type = $payment_type || 'payment-plans';
            $do_not_apply_gobal = $do_not_apply_gobal || false;

            $( '#_sumo_pp_order_payment_plan_deposit_type' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_order_payment_plan_deposit_price_type' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_fixed_order_payment_plan_deposit_percent' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_fixed_order_payment_plan_deposit_price' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_order_payment_plan_user_defined_deposit_type' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_min_order_payment_plan_deposit' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_max_order_payment_plan_deposit' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_min_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_max_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_selected_plans_for_order_payment_plan' ).closest( 'tr' ).show();
            $( '#_sumo_pp_order_payment_plan_pay_balance_type' ).closest( 'tr' ).hide();

            if ( 'pay-in-deposit' === $payment_type ) {
                $( '#_sumo_pp_order_payment_plan_deposit_type' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_plan_user_defined_deposit_type' ).closest( 'tr' ).show();
                $( '#_sumo_pp_min_order_payment_plan_deposit' ).closest( 'tr' ).show();
                $( '#_sumo_pp_max_order_payment_plan_deposit' ).closest( 'tr' ).show();
                $( '#_sumo_pp_min_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).show();
                $( '#_sumo_pp_max_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_plan_pay_balance_type' ).closest( 'tr' ).show();
                $( '#_sumo_pp_selected_plans_for_order_payment_plan' ).closest( 'tr' ).hide();
                orderpp.get_deposit_type( $( '#_sumo_pp_order_payment_plan_deposit_type' ).val() );
                orderpp.get_deposit_price_type( $( '#_sumo_pp_order_payment_plan_deposit_price_type' ).val() );
            }
            if ( false === $do_not_apply_gobal ) {
                orderpp.get_global_settings( $( '#_sumo_pp_apply_global_settings_for_order_payment_plan' ).is( ':checked' ) );
            }
        },
        get_global_settings : function( $apply_global_settings ) {
            $apply_global_settings = $apply_global_settings || '';

            if ( true === $apply_global_settings ) {
                $( '#_sumo_pp_order_payment_plan_deposit_price_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_fixed_order_payment_plan_deposit_percent' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_fixed_order_payment_plan_deposit_price' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_force_order_payment_plan' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_deposit_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_user_defined_deposit_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_min_order_payment_plan_deposit' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_max_order_payment_plan_deposit' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_min_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_max_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_pay_balance_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_selected_plans_for_order_payment_plan' ).closest( 'tr' ).hide();
            } else {
                $( '#_sumo_pp_force_order_payment_plan' ).closest( 'tr' ).show();
                orderpp.get_payment_type( $( '#_sumo_pp_order_payment_type' ).val(), true );
            }
        },
        get_deposit_type : function( $deposit_type ) {
            $deposit_type = $deposit_type || 'user-defined';

            if ( 'pre-defined' === $deposit_type ) {
                $( '#_sumo_pp_order_payment_plan_user_defined_deposit_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_min_order_payment_plan_deposit' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_max_order_payment_plan_deposit' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_min_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_max_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_order_payment_plan_deposit_price_type' ).closest( 'tr' ).show();
                orderpp.get_deposit_price_type( $( '#_sumo_pp_order_payment_plan_deposit_price_type' ).val() );
            } else {
                $( '#_sumo_pp_order_payment_plan_user_defined_deposit_type' ).closest( 'tr' ).show();
                $( '#_sumo_pp_order_payment_plan_deposit_price_type' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_fixed_order_payment_plan_deposit_percent' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_fixed_order_payment_plan_deposit_price' ).closest( 'tr' ).hide();
                orderpp.get_user_defined_deposit_type( $( '#_sumo_pp_order_payment_plan_user_defined_deposit_type' ).val() );
            }

            $( '#_sumo_pp_order_payment_plan_pay_balance_type' ).closest( 'tr' ).show();
            orderpp.get_pay_balance( $( '#_sumo_pp_order_payment_plan_pay_balance_type' ).val() );
        },
        get_user_defined_deposit_type : function( $user_defined_deposit_type ) {
            $user_defined_deposit_type = $user_defined_deposit_type || 'percent-of-product-price';

            $( '#_sumo_pp_min_order_payment_plan_deposit' ).closest( 'tr' ).show();
            $( '#_sumo_pp_max_order_payment_plan_deposit' ).closest( 'tr' ).show();
            $( '#_sumo_pp_min_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).hide();
            $( '#_sumo_pp_max_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).hide();

            if ( 'fixed-price' === $user_defined_deposit_type ) {
                $( '#_sumo_pp_min_order_payment_plan_deposit' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_max_order_payment_plan_deposit' ).closest( 'tr' ).hide();
                $( '#_sumo_pp_min_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).show();
                $( '#_sumo_pp_max_order_payment_plan_user_defined_deposit_price' ).closest( 'tr' ).show();
            }
        },
        get_pay_balance : function( $pay_balance_type ) {
            $pay_balance_type = $pay_balance_type || 'after';

            $( '#_sumo_pp_order_payment_plan_pay_balance_after' ).show();
            $( '#_sumo_pp_order_payment_plan_pay_balance_before' ).hide();

            if ( 'before' === $pay_balance_type ) {
                $( '#_sumo_pp_order_payment_plan_pay_balance_after' ).hide();
                $( '#_sumo_pp_order_payment_plan_pay_balance_before' ).show();
            }
        },
    };
    orderpp.init();
} );
