<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle SUMO Payment Plans Ajax Event.
 * 
 * @class SUMO_PP_Ajax
 * @package Class
 */
class SUMO_PP_Ajax {

    /**
     * Init SUMO_PP_Ajax.
     */
    public static function init() {
        //Get Ajax Events.
        $ajax_events = array(
            'add_payment_note'                  => false,
            'delete_payment_note'               => false,
            'get_wc_booking_deposit_fields'     => true,
            'get_payment_plan_search_field'     => false,
            'checkout_orderpp'                  => true,
            'pay_remaining_custom_installments' => false,
            'init_data_export'                  => false,
            'handle_exported_data'              => false,
            'bulk_update_products'              => false,
            'bulk_update_payments'              => false,
            'json_search_payment_plans'         => false,
            'json_search_customers_by_email'    => false,
        );

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( "wp_ajax__sumo_pp_{$ajax_event}", __CLASS__ . "::{$ajax_event}" );

            if ( $nopriv ) {
                add_action( "wp_ajax_nopriv__sumo_pp_{$ajax_event}", __CLASS__ . "::{$ajax_event}" );
            }
        }
    }

    /**
     * Admin manually add payment notes.
     */
    public static function add_payment_note() {
        check_ajax_referer( 'sumo-pp-add-payment-note', 'security' );

        if ( ! empty( $_POST[ 'post_id' ] ) && ! empty( $_POST[ 'content' ] ) ) {
            $payment = _sumo_pp_get_payment( absint( wp_unslash( $_POST[ 'post_id' ] ) ) );
            $note    = $payment->add_payment_note( wc_clean( wp_unslash( $_POST[ 'content' ] ) ), 'pending', __( 'Admin Manually Added Note', 'sumopaymentplans' ) );
            $note    = $payment->get_payment_note( $note );

            if ( $note ) {
                include 'admin/views/html-admin-payment-note.php';
            }
        }
        die();
    }

    /**
     * Admin manually delete payment notes.
     */
    public static function delete_payment_note() {
        check_ajax_referer( 'sumo-pp-delete-payment-note', 'security' );

        if ( ! empty( $_POST[ 'delete_id' ] ) ) {
            wp_send_json( wp_delete_comment( absint( wp_unslash( $_POST[ 'delete_id' ] ) ), true ) );
        }
        die();
    }

    public static function get_wc_booking_deposit_fields() {
        check_ajax_referer( 'sumo-pp-get-payment-type-fields', 'security' );

        if ( ! empty( $_POST[ 'product' ] ) ) {
            $product_props        = _sumo_pp()->product->get_props( absint( wp_unslash( $_POST[ 'product' ] ) ) );
            $can_add_booking_cost = false;

            if ( class_exists( 'SUMO_PP_WC_Bookings' ) && SUMO_PP_WC_Bookings::can_add_booking_cost( $product_props ) ) {
                $can_add_booking_cost = true;
            } else if ( class_exists( 'SUMO_PP_YITH_WC_Bookings' ) && SUMO_PP_YITH_WC_Bookings::can_add_booking_cost( $product_props ) ) {
                $can_add_booking_cost = true;
            } else if ( class_exists( 'SUMO_PP_SUMOBookings' ) && SUMO_PP_SUMOBookings::can_add_booking_cost( $product_props ) ) {
                $can_add_booking_cost = true;
            }

            if ( $can_add_booking_cost ) {
                wp_send_json( array(
                    'result' => 'success',
                    'html'   => _sumo_pp()->product->get_deposit_form(),
                ) );
            }
        }

        wp_send_json( array(
            'result' => 'failure',
            'html'   => '',
        ) );
    }

    /**
     * Get Payment Plan search field
     */
    public static function get_payment_plan_search_field() {
        check_ajax_referer( 'sumo-pp-search-payment-plan', 'security' );

        $posted = $_POST;
        wp_send_json( array(
            'search_field' => _sumo_pp_wc_search_field( array(
                'class'       => 'wc-product-search',
                'action'      => '_sumo_pp_json_search_payment_plans',
                'id'          => isset( $posted[ 'loop' ] ) ? "selected_{$posted[ 'col' ]}_payment_plan_{$posted[ 'rowID' ]}{$posted[ 'loop' ]}" : "selected_{$posted[ 'col' ]}_payment_plan_{$posted[ 'rowID' ]}",
                'name'        => isset( $posted[ 'loop' ] ) ? "_sumo_pp_selected_plans[{$posted[ 'loop' ]}][{$posted[ 'col' ]}][{$posted[ 'rowID' ]}]" : "_sumo_pp_selected_plans[{$posted[ 'col' ]}][{$posted[ 'rowID' ]}]",
                'type'        => 'payment_plans',
                'selected'    => false,
                'multiple'    => false,
                'placeholder' => __( 'Search for a payment plan&hellip;', 'sumopaymentplans' ),
                    ), false ),
        ) );
    }

    /**
     * Save order paymentplan.
     */
    public static function checkout_orderpp() {
        check_ajax_referer( 'sumo-pp-checkout-orderpp', 'security' );

        _sumo_pp()->orderpp->unsubscribe();

        if ( ! empty( $_POST[ 'enabled' ] ) && 'yes' === sanitize_title( wp_unslash( $_POST[ 'enabled' ] ) ) ) {
            WC()->session->set( '_sumo_pp_orderpp_enabled', 'yes' );

            if ( ! empty( $_POST[ 'payment_type' ] ) ) {
                switch ( sanitize_title( wp_unslash( $_POST[ 'payment_type' ] ) ) ) {
                    case 'pay-in-deposit':
                        if ( isset( $_POST[ 'deposited_amount' ] ) ) {
                            WC()->session->set( '_sumo_pp_orderpp_deposited_amount', wc_clean( wp_unslash( $_POST[ 'deposited_amount' ] ) ) );
                        }
                        break;
                    case 'payment-plans':
                        if ( isset( $_POST[ 'chosen_payment_plan' ] ) ) {
                            WC()->session->set( '_sumo_pp_orderpp_chosen_payment_plan', wc_clean( wp_unslash( $_POST[ 'chosen_payment_plan' ] ) ) );
                        }
                        break;
                }
            }
        }
        die();
    }

    public static function pay_remaining_custom_installments() {
        check_ajax_referer( 'sumo-pp-myaccount', 'security' );

        $posted  = $_POST;
        $payment = _sumo_pp_get_payment( $posted[ 'payment_id' ] );

        if ( ! $payment ) {
            wp_send_json( array(
                'result'   => 'failure',
                'redirect' => $payment->get_view_endpoint_url(),
                'notice'   => __( 'Invalid Payment!!', 'sumopaymentplans' ),
            ) );
        }

        if ( 'pay-remaining' === $posted[ 'selected_installments' ] ) {
            if ( $payment->balance_payable_order_exists( 'my_account' ) ) {
                $payment->balance_payable_order->delete( true );
            }

            $next_installment_amount       = 0;
            $remaining_unpaid_installments = $payment->get_next_installment_count() + absint( $payment->get_prop( 'remaining_installments' ) );

            for ( $unpaid_installment = $payment->get_next_installment_count(); $unpaid_installment < $remaining_unpaid_installments; $unpaid_installment ++ ) {
                $next_installment_amount += $payment->get_next_installment_amount( $unpaid_installment );
            }

            $balance_payable_order_id = _sumo_pp()->order->create_balance_payable_order( $payment, array(
                'next_installment_amount' => $next_installment_amount,
                'next_installment_count'  => $remaining_unpaid_installments - 1,
                'remaining_installments'  => 0,
                'installments_included'   => absint( $payment->get_prop( 'remaining_installments' ) ),
                'created_via'             => 'my_account',
                'add_default_note'        => false,
                    ) );

            $balance_payable_order = _sumo_pp_maybe_get_order_instance( $balance_payable_order_id );
            if ( $balance_payable_order ) {
                wp_send_json( array(
                    'result'   => 'success',
                    'redirect' => esc_url_raw( $balance_payable_order->get_checkout_payment_url() ),
                ) );
            }
        } else if ( is_numeric( $posted[ 'selected_installments' ] ) ) {
            $selected_installments_count = absint( $posted[ 'selected_installments' ] );

            if ( 0 === $selected_installments_count && $payment->balance_payable_order_exists() ) {
                $balance_payable_order = $payment->balance_payable_order;
            } else {
                if ( $payment->balance_payable_order_exists( 'my_account' ) ) {
                    $payment->balance_payable_order->delete( true );
                }

                $next_installment_amount      = 0;
                $next_installment_count       = $payment->get_next_installment_count();
                $selected_unpaid_installments = $next_installment_count + $selected_installments_count;

                for ( $unpaid_installment = $payment->get_next_installment_count(); $unpaid_installment <= $selected_unpaid_installments; $unpaid_installment ++ ) {
                    $next_installment_amount += $payment->get_next_installment_amount( $unpaid_installment );
                    $next_installment_count  = $unpaid_installment;
                }

                $balance_payable_order_id = _sumo_pp()->order->create_balance_payable_order( $payment, array(
                    'next_installment_amount' => $next_installment_amount,
                    'next_installment_count'  => $next_installment_count,
                    'remaining_installments'  => $payment->get_remaining_installments( $next_installment_count ),
                    'installments_included'   => 1 + $selected_installments_count,
                    'created_via'             => 'my_account',
                    'add_default_note'        => false,
                        ) );

                $balance_payable_order = _sumo_pp_maybe_get_order_instance( $balance_payable_order_id );
            }

            if ( $balance_payable_order ) {
                wp_send_json( array(
                    'result'   => 'success',
                    'redirect' => esc_url_raw( $balance_payable_order->get_checkout_payment_url() ),
                ) );
            }
        }
    }

    /**
     * Init data export
     */
    public static function init_data_export() {
        check_ajax_referer( 'sumo-pp-payments-exporter', 'security' );

        $posted        = $_POST;
        $export_databy = array();
        parse_str( $posted[ 'exportDataBy' ], $export_databy );

        $json_args = array();
        $args      = array(
            'type'     => 'sumo_pp_payments',
            'status'   => array_keys( _sumo_pp_get_payment_statuses() ),
            'order_by' => 'DESC',
        );

        if ( ! empty( $export_databy ) ) {
            if ( ! empty( $export_databy[ 'payment_statuses' ] ) ) {
                $args[ 'status' ] = $export_databy[ 'payment_statuses' ];
            }

            if ( ! empty( $export_databy[ 'payment_from_date' ] ) ) {
                $to_date              = ! empty( $export_databy[ 'payment_to_date' ] ) ? strtotime( $export_databy[ 'payment_to_date' ] ) : strtotime( gmdate( 'Y-m-d' ) );
                $args[ 'date_query' ] = array(
                    array(
                        'after'     => gmdate( 'Y-m-d', strtotime( $export_databy[ 'payment_from_date' ] ) ),
                        'before'    => array(
                            'year'  => gmdate( 'Y', $to_date ),
                            'month' => gmdate( 'm', $to_date ),
                            'day'   => gmdate( 'd', $to_date ),
                        ),
                        'inclusive' => true,
                    ),
                );
            }

            $meta_query = array();
            if ( ! empty( $export_databy[ 'payment_products' ] ) ) {
                $meta_query[] = array(
                    'key'     => '_product_id',
                    'value'   => ( array ) $export_databy[ 'payment_products' ],
                    'compare' => 'IN',
                );
            }

            if ( ! empty( $export_databy[ 'payment_types' ] ) ) {
                $meta_query[] = array(
                    'key'     => '_payment_type',
                    'value'   => ( array ) $export_databy[ 'payment_types' ],
                    'compare' => 'IN',
                );
            }

            if ( ! empty( $export_databy[ 'payment_plans' ] ) ) {
                $meta_query[] = array(
                    'key'     => '_plan_id',
                    'value'   => ( array ) $export_databy[ 'payment_plans' ],
                    'compare' => 'IN',
                );
            }

            if ( ! empty( $export_databy[ 'payment_buyers' ] ) ) {
                $meta_query[] = array(
                    'key'     => '_customer_email',
                    'value'   => ( array ) $export_databy[ 'payment_buyers' ],
                    'compare' => 'IN',
                );
            }

            if ( ! empty( $meta_query ) ) {
                $args[ 'meta_query' ] = array( 'relation' => 'AND' ) + $meta_query;
            }
        }

        $payments = _sumo_pp()->query->get( $args );

        if ( count( $payments ) <= 1 ) {
            $json_args[ 'export' ]         = 'done';
            $json_args[ 'generated_data' ] = array_map( array( 'SUMO_PP_Payments_Exporter', 'generate_data' ), $payments );
            $json_args[ 'redirect_url' ]   = SUMO_PP_Payments_Exporter::get_download_url( $json_args[ 'generated_data' ] );
        } else {
            $json_args[ 'export' ]        = 'processing';
            $json_args[ 'original_data' ] = $payments;
        }

        wp_send_json( wp_parse_args( $json_args, array(
            'export'         => '',
            'generated_data' => array(),
            'original_data'  => array(),
            'redirect_url'   => SUMO_PP_Payments_Exporter::get_exporter_page_url(),
        ) ) );
    }

    /**
     * Handle exported data
     */
    public static function handle_exported_data() {
        check_ajax_referer( 'sumo-pp-payments-exporter', 'security' );

        $posted                        = $_POST;
        $json_args                     = array();
        $pre_generated_data            = json_decode( stripslashes( $posted[ 'generated_data' ] ) );
        $new_generated_data            = array_map( array( 'SUMO_PP_Payments_Exporter', 'generate_data' ), array_filter( ( array ) $posted[ 'chunkedData' ] ) );
        $json_args[ 'generated_data' ] = array_values( array_filter( array_merge( array_filter( ( array ) $pre_generated_data ), $new_generated_data ) ) );

        if ( absint( $posted[ 'originalDataLength' ] ) === count( $json_args[ 'generated_data' ] ) ) {
            $json_args[ 'export' ]       = 'done';
            $json_args[ 'redirect_url' ] = SUMO_PP_Payments_Exporter::get_download_url( $json_args[ 'generated_data' ] );
        }

        wp_send_json( wp_parse_args( $json_args, array(
            'export'         => 'processing',
            'generated_data' => array(),
            'original_data'  => array(),
            'redirect_url'   => SUMO_PP_Payments_Exporter::get_exporter_page_url(),
        ) ) );
    }

    /**
     * Process products bulk update.
     */
    public static function bulk_update_products() {
        check_ajax_referer( 'products-bulk-update', 'security' );

        $posted = $_POST;
        $data   = array();
        parse_str( $posted[ 'data' ], $data );

        if ( empty( $data[ 'get_product_select_type' ] ) ) {
            wp_send_json_error( array(
                'itemsCount'  => 0,
                'errorNotice' => __( 'Something went wrong while updating the products!!', 'sumopaymentplans' ),
            ) );
        }

        //Save the settings
        update_option( 'bulk_sumo_pp_get_product_select_type', wc_clean( $data[ 'get_product_select_type' ] ) );
        update_option( 'bulk_sumo_pp_get_included_categories',  ! empty( $data[ 'get_included_categories' ] ) ? wc_clean( $data[ 'get_included_categories' ] ) : array()  );
        update_option( 'bulk_sumo_pp_get_excluded_categories',  ! empty( $data[ 'get_excluded_categories' ] ) ? wc_clean( $data[ 'get_excluded_categories' ] ) : array()  );
        update_option( 'bulk_sumo_pp_get_included_products',  ! empty( $data[ 'get_included_products' ] ) ? wc_clean( is_array( $data[ 'get_included_products' ] ) ? $data[ 'get_included_products' ] : explode( ',', $data[ 'get_included_products' ] )  ) : array()  );
        update_option( 'bulk_sumo_pp_get_excluded_products',  ! empty( $data[ 'get_excluded_products' ] ) ? wc_clean( is_array( $data[ 'get_excluded_products' ] ) ? $data[ 'get_excluded_products' ] : explode( ',', $data[ 'get_excluded_products' ] )  ) : array()  );

        foreach ( SUMO_PP_Admin_Product::get_payment_fields() as $field_name => $type ) {
            $meta_key         = SUMO_PP_PLUGIN_PREFIX . $field_name;
            $posted_meta_data = isset( $data[ "$meta_key" ] ) ? $data[ "$meta_key" ] : '';

            if ( 'price' === $type ) {
                $posted_meta_data = wc_format_decimal( $posted_meta_data );
            }

            if ( 'selected_plans' === $field_name && is_array( $posted_meta_data ) ) {
                $selected_plans = array();
                foreach ( array( 'col_1', 'col_2' ) as $column_id ) {
                    $selected_plans[ $column_id ] = ! empty( $posted_meta_data[ $column_id ] ) && is_array( $posted_meta_data[ $column_id ] ) ? array_map( 'implode', ( array_values( $posted_meta_data[ $column_id ] ) ) ) : array();
                }
                $posted_meta_data = $selected_plans;
            }

            update_option( "bulk{$meta_key}", wc_clean( $posted_meta_data ) );
        }

        $found_products = array();
        switch ( get_option( 'bulk_sumo_pp_get_product_select_type' ) ) {
            case 'all-products':
                $products = new WP_Query( array(
                    'post_type'      => array( 'product', 'product_variation' ),
                    'posts_per_page' => '-1',
                    'post_status'    => 'publish',
                    'fields'         => 'ids',
                    'cache_results'  => false,
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'product_type',
                            'field'    => 'slug',
                            'terms'    => array( 'variable', 'grouped' ),
                            'operator' => 'NOT IN',
                        ),
                    ),
                        ) );

                if ( ! empty( $products->posts ) ) {
                    $found_products = $products->posts;
                }
                break;
            case 'included-products':
                $found_products = array_map( 'absint', get_option( 'bulk_sumo_pp_get_included_products', array() ) );
                break;
            case 'excluded-products':
                $products       = new WP_Query( array(
                    'post_type'      => array( 'product', 'product_variation' ),
                    'posts_per_page' => '-1',
                    'post_status'    => 'publish',
                    'post__not_in'   => array_map( 'absint', get_option( 'bulk_sumo_pp_get_excluded_products', array() ) ),
                    'fields'         => 'ids',
                    'cache_results'  => false,
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'product_type',
                            'field'    => 'slug',
                            'terms'    => array( 'variable', 'grouped' ),
                            'operator' => 'NOT IN',
                        ),
                    ),
                        ) );

                if ( ! empty( $products->posts ) ) {
                    $found_products = $products->posts;
                }
                break;
            case 'included-categories':
                $products = new WP_Query( array(
                    'post_type'      => array( 'product', 'product_variation' ),
                    'post_status'    => 'publish',
                    'posts_per_page' => '-1',
                    'fields'         => 'ids',
                    'cache_results'  => false,
                    'tax_query'      => array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => array_map( 'absint', get_option( 'bulk_sumo_pp_get_included_categories', array() ) ),
                            'operator' => 'IN',
                        ),
                        array(
                            'taxonomy' => 'product_type',
                            'field'    => 'slug',
                            'terms'    => array( 'grouped' ),
                            'operator' => 'NOT IN',
                        ),
                    ),
                        ) );

                if ( ! empty( $products->posts ) ) {
                    $found_products = $products->posts;
                }
                break;
            case 'excluded-categories':
                $products = new WP_Query( array(
                    'post_type'      => array( 'product', 'product_variation' ),
                    'post_status'    => 'publish',
                    'posts_per_page' => '-1',
                    'fields'         => 'ids',
                    'cache_results'  => false,
                    'tax_query'      => array(
                        array(
                            'relation' => 'AND',
                            array(
                                'taxonomy' => 'product_cat',
                                'field'    => 'term_id',
                                'terms'    => array_map( 'absint', get_option( 'bulk_sumo_pp_get_excluded_categories', array() ) ),
                                'operator' => 'NOT IN',
                            ),
                            array(
                                'taxonomy' => 'product_type',
                                'field'    => 'slug',
                                'terms'    => array( 'grouped' ),
                                'operator' => 'NOT IN',
                            ),
                        ),
                    ),
                        ) );

                if ( ! empty( $products->posts ) ) {
                    $found_products = $products->posts;
                }
                break;
        }

        if ( empty( $found_products ) ) {
            wp_send_json_error( array(
                'itemsCount'  => 0,
                'errorNotice' => __( 'No products found to update.', 'sumopaymentplans' ),
            ) );
        }

        set_transient( '_sumo_pp_bulk_update_found_products', $found_products, time() + 60 );

        $background_updates = get_option( '_sumo_pp_background_updates', array() );
        $job_id             = WC()->queue()->schedule_single( time(), 'sumopaymentplans_find_products_to_bulk_update', array(), 'sumopaymentplans-product-bulk-updates' );

        if ( ! $job_id || ! is_numeric( $job_id ) ) {
            wp_send_json_error( array(
                'itemsCount'  => count( $found_products ),
                'errorNotice' => __( 'Something went wrong while updating the products!!', 'sumopaymentplans' ),
            ) );
        }

        if ( WC()->queue()->get_next( 'sumopaymentplans_find_products_to_bulk_update', null, 'sumopaymentplans-product-bulk-updates' ) ) {
            $background_updates[ 'product_update' ] = array(
                'action_status'  => 'in_progress',
                'current_action' => 'sumopaymentplans_find_products_to_bulk_update',
                'next_action'    => 'sumopaymentplans_update_products_in_bulk',
                'action_group'   => 'sumopaymentplans-product-bulk-updates'
            );
        } else {
            unset( $background_updates[ 'product_update' ] );
        }

        update_option( '_sumo_pp_background_updates', $background_updates );
        wp_send_json_success( array(
            'itemsCount'    => count( $found_products ),
            /* translators: 1: products count */
            'successNotice' => sprintf( __( '%s product(s) found. Product is updating in the background. The product update process may take a little while, so please be patient.', 'sumopaymentplans' ), count( $found_products ) ),
        ) );
    }

    /**
     * Process payments bulk update.
     */
    public static function bulk_update_payments() {
        check_ajax_referer( 'payments-bulk-update', 'security' );

        $posted = $_POST;
        $data   = array();
        parse_str( $posted[ 'data' ], $data );

        if ( empty( $data[ '_deleted_product' ] ) || empty( $data[ '_replace_product' ] ) ) {
            wp_send_json_error( array(
                'itemsCount'  => 0,
                'errorNotice' => __( 'Something went wrong while replacing the product with the deleted product!!', 'sumopaymentplans' ),
            ) );
        }

        $deleted_product_id = absint( $data[ '_deleted_product' ] );
        $replace_product_id = absint( is_array( $data[ '_replace_product' ] ) ? current( $data[ '_replace_product' ] ) : $data[ '_replace_product' ] );
        $replace_product    = wc_get_product( $replace_product_id );
        $deleted_product    = wc_get_product( $deleted_product_id );

        if ( $deleted_product || ! $replace_product ) {
            wp_send_json_error( array(
                'itemsCount'  => 0,
                'errorNotice' => __( 'Something went wrong while replacing the product with the deleted product!!', 'sumopaymentplans' ),
            ) );
        }

        global $wpdb;
        $_wpdb          = &$wpdb;
        $found_payments = $_wpdb->get_col(
                $_wpdb->prepare( "
                            SELECT DISTINCT posts.ID as pid FROM {$_wpdb->posts} as posts
                            INNER JOIN {$_wpdb->postmeta} as pm on (pm.post_id = posts.ID AND pm.meta_key = '_product_id' AND pm.meta_value = '%s')
                            INNER JOIN {$_wpdb->postmeta} as pm2 on (pm2.post_id = posts.ID AND pm2.meta_key = '_product_type' AND pm2.meta_value != 'order')
                            AND posts.post_type = 'sumo_pp_payments' AND posts.post_status IN ('" . implode( "','", array_map( 'esc_sql', array_keys( _sumo_pp_get_payment_statuses() ) ) ) . "')"
                        , esc_sql( $deleted_product_id )
                ) );

        if ( empty( $found_payments ) ) {
            wp_send_json_error( array(
                'itemsCount'  => 0,
                'errorNotice' => __( 'No payments found!!', 'sumopaymentplans' ),
            ) );
        }

        set_transient( '_sumo_pp_bulk_update_found_payments', $found_payments, time() + 60 );

        $background_updates = get_option( '_sumo_pp_background_updates', array() );
        $args               = array( 'deleted_product_id' => $deleted_product_id, 'replace_product_id' => $replace_product_id );
        $job_id             = WC()->queue()->schedule_single( time(), 'sumopaymentplans_find_payments_to_bulk_update', $args, 'sumopaymentplans-payment-bulk-updates' );

        if ( ! $job_id || ! is_numeric( $job_id ) ) {
            wp_send_json_error( array(
                'itemsCount'  => count( $found_payments ),
                'errorNotice' => __( 'Something went wrong while replacing the product with the deleted product!!', 'sumopaymentplans' ),
            ) );
        }

        if ( WC()->queue()->get_next( 'sumopaymentplans_find_payments_to_bulk_update', $args, 'sumopaymentplans-payment-bulk-updates' ) ) {
            $background_updates[ 'payment_update' ] = array(
                'action_status'  => 'in_progress',
                'current_action' => 'sumopaymentplans_find_payments_to_bulk_update',
                'next_action'    => 'sumopaymentplans_update_payments_in_bulk',
                'action_group'   => 'sumopaymentplans-payment-bulk-updates'
            );
        } else {
            unset( $background_updates[ 'payment_update' ] );
        }

        update_option( '_sumo_pp_background_updates', $background_updates );
        wp_send_json_success( array(
            'itemsCount'    => count( $found_payments ),
            /* translators: 1: payments count */
            'successNotice' => sprintf( __( '%s payment(s) found. Product is replacing with the deleted product in the background. The product replace process may take a little while, so please be patient.', 'sumopaymentplans' ), count( $found_payments ) ),
        ) );
    }

    /**
     * Search for payment plans
     */
    public static function json_search_payment_plans() {
        ob_start();

        $requested = $_GET;
        $term      = ( string ) wc_clean( stripslashes( isset( $requested[ 'term' ] ) ? $requested[ 'term' ] : '' ) );
        $exclude   = array();

        if ( isset( $requested[ 'exclude' ] ) && ! empty( $requested[ 'exclude' ] ) ) {
            $exclude = array_map( 'intval', explode( ',', $requested[ 'exclude' ] ) );
        }

        $args = array(
            'type'    => 'sumo_payment_plans',
            'status'  => array( 'publish', 'future', 'private' ),
            'return'  => 'posts',
            'order'   => 'ASC',
            'orderby' => 'parent title',
            's'       => $term,
            'exclude' => $exclude,
        );

        if ( is_numeric( $term ) ) {
            unset( $args[ 's' ] );
            $args[ 'post__in' ] = array( ( int ) $term );
        }

        $posts       = _sumo_pp()->query->get( $args );
        $found_plans = array();

        if ( ! empty( $posts ) ) {
            foreach ( $posts as $post ) {
                $found_plans[ $post->ID ] = sprintf( '(#%s) %s', $post->ID, $post->post_title );
            }
        }
        wp_send_json( $found_plans );
    }

    /**
     * Search for customers by email and return json.
     */
    public static function json_search_customers_by_email() {
        ob_start();

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_die( -1 );
        }

        $requested = $_GET;
        $term      = wc_clean( wp_unslash( $requested[ 'term' ] ) );
        $limit     = '';

        if ( empty( $term ) ) {
            wp_die();
        }

        $ids = array();
        // Search by ID.
        if ( is_numeric( $term ) ) {
            $customer = new WC_Customer( intval( $term ) );

            // Customer does not exists.
            if ( 0 !== $customer->get_id() ) {
                $ids = array( $customer->get_id() );
            }
        }

        // Usernames can be numeric so we first check that no users was found by ID before searching for numeric username, this prevents performance issues with ID lookups.
        if ( empty( $ids ) ) {
            $data_store = WC_Data_Store::load( 'customer' );

            // If search is smaller than 3 characters, limit result set to avoid
            // too many rows being returned.
            if ( 3 > strlen( $term ) ) {
                $limit = 20;
            }
            $ids = $data_store->search_customers( $term, $limit );
        }

        $found_customers = array();
        if ( ! empty( $requested[ 'exclude' ] ) ) {
            $ids = array_diff( $ids, ( array ) $requested[ 'exclude' ] );
        }

        foreach ( $ids as $id ) {
            $customer = new WC_Customer( $id );

            /* translators: 1: user display name 2: user ID 3: user email */
            $found_customers[ $customer->get_email() ] = sprintf( esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'sumopaymentplans' ), $customer->get_first_name() . ' ' . $customer->get_last_name(), $customer->get_id(), $customer->get_email() );
        }

        wp_send_json( $found_customers );
    }
}

SUMO_PP_Ajax::init();
