<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle payment plans enqueues.
 * 
 * @class SUMO_PP_Enqueues
 * @package Class
 */
class SUMO_PP_Enqueues {

    /**
     * Menu Title
     * 
     * @var String
     */
    public static $menu_title;

    /**
     * Init SUMO_PP_Enqueues.
     */
    public static function init() {
        self::$menu_title = sanitize_title( __( 'SUMO Payment Plans', 'sumopaymentplans' ) );

        add_action( 'admin_enqueue_scripts', __CLASS__ . '::admin_script', 11 );
        add_action( 'admin_enqueue_scripts', __CLASS__ . '::admin_style', 11 );
        add_action( 'wp_enqueue_scripts', __CLASS__ . '::frontend_script', 11 );
        add_action( 'wp_enqueue_scripts', __CLASS__ . '::frontend_style', 11 );
        add_filter( 'woocommerce_screen_ids', __CLASS__ . '::load_wc_enqueues', 1 );
    }

    /**
     * Register and enqueue a script for use.
     *
     * @uses   wp_enqueue_script()
     * @param  string   $handle
     * @param  string   $path
     * @param  array   $localize_data
     * @param  string[] $deps
     * @param  string   $version
     * @param  boolean  $in_footer
     */
    public static function enqueue_script( $handle, $path = '', $localize_data = array(), $deps = array( 'jquery' ), $version = SUMO_PP_PLUGIN_VERSION, $in_footer = false ) {
        wp_register_script( $handle, $path, $deps, $version, $in_footer );

        $name = str_replace( '-', '_', $handle );
        wp_localize_script( $handle, "{$name}_params", $localize_data );
        wp_enqueue_script( $handle );
    }

    /**
     * Register and enqueue a styles for use.
     *
     * @uses   wp_enqueue_style()
     * @param  string   $handle
     * @param  string   $path
     * @param  string[] $deps
     * @param  string   $version
     * @param  string   $media
     * @param  boolean  $has_rtl
     */
    public static function enqueue_style( $handle, $path = '', $deps = array(), $version = SUMO_PP_PLUGIN_VERSION, $media = 'all', $has_rtl = false ) {
        wp_register_style( $handle, $path, $deps, $version, $media, $has_rtl );
        wp_enqueue_style( $handle );
    }

    /**
     * Return asset URL.
     *
     * @param string $path
     * @return string
     */
    public static function get_asset_url( $path ) {
        return SUMO_PP_PLUGIN_URL . "/assets/{$path}";
    }

    /**
     * Enqueue datetime picker.
     */
    public static function enqueue_datetimepicker() {
        self::enqueue_script( 'jquery-ui-timepicker-addon', self::get_asset_url( 'js/datetimepicker/jquery-ui-timepicker-addon.js' ) );
        self::enqueue_style( 'jquery-ui-timepicker-addon', self::get_asset_url( 'js/datetimepicker/jquery-ui-timepicker-addon.css' ) );
    }

    /**
     * Perform script localization in backend.
     */
    public static function admin_script() {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        switch ( $screen_id ) {
            case 'sumo_pp_payments':
            case 'sumo_payment_plans':
            case 'edit-sumo_pp_payments':
            case 'edit-sumo_payment_plans':
                self::enqueue_script( 'sumo-pp-admin', self::get_asset_url( 'js/admin/admin.js' ), array(
                    'wp_ajax_url'                => admin_url( 'admin-ajax.php' ),
                    'duration_options'           => _sumo_pp_get_duration_options(),
                    'is_paymentplans_page'       => in_array( $screen_id, array( 'sumo_payment_plans', 'edit-sumo_payment_plans' ) ) ? 'yes' : '',
                    'price_dp'                   => wc_get_price_decimals(),
                    'add_note_nonce'             => wp_create_nonce( 'sumo-pp-add-payment-note' ),
                    'delete_note_nonce'          => wp_create_nonce( 'sumo-pp-delete-payment-note' ),
                    'warning_dates_not_in_order' => __( 'You cannot save when the installment dates are not in order. Please make the changes and try again.', 'sumopaymentplans' ),
                        ), array( 'jquery-ui-datepicker', 'jquery-tiptip' ) );

                self::enqueue_datetimepicker();
                // Disable WP Auto Save on Edit Page.
                wp_dequeue_script( 'autosave' );
                break;
            case 'product':
                self::enqueue_script( 'sumo-pp-admin-product', self::get_asset_url( 'js/admin/admin-product.js' ), array(
                    'decimal_sep'         => get_option( 'woocommerce_price_decimal_sep', '.' ),
                    'plan_search_nonce'   => wp_create_nonce( 'sumo-pp-search-payment-plan' ),
                    'variations_per_page' => absint( apply_filters( 'woocommerce_admin_meta_boxes_variations_per_page', 15 ) ),
                ) );
                break;
            case self::$menu_title . '_page_sumo-pp-payments-exporter':
                self::enqueue_script( 'sumo-pp-admin-exporter', self::get_asset_url( 'js/admin/admin-exporter.js' ), array(
                    'wp_ajax_url'    => admin_url( 'admin-ajax.php' ),
                    'exporter_nonce' => wp_create_nonce( 'sumo-pp-payments-exporter' ),
                        ), array( 'jquery-ui-datepicker' ) );
                break;
        }

        if ( self::$menu_title . '_page_sumo_pp_settings' === $screen_id ) {
            switch ( isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : '' ) {
                case 'orderpp':
                    self::enqueue_script( 'sumo-pp-admin-orderpp', self::get_asset_url( 'js/admin/admin-orderpp.js' ), array(), array( 'jquery-ui-datepicker' ) );
                    break;
                case 'bulk_action':
                    self::enqueue_script( 'sumo-pp-admin-bulk-action', self::get_asset_url( 'js/admin/admin-bulk-action.js' ), array(
                        'wp_ajax_url'       => admin_url( 'admin-ajax.php' ),
                        'products_nonce'    => wp_create_nonce( 'products-bulk-update' ),
                        'payments_nonce'    => wp_create_nonce( 'payments-bulk-update' ),
                        'plan_search_nonce' => wp_create_nonce( 'sumo-pp-search-payment-plan' ),
                            ), array( 'jquery-ui-datepicker' ) );
                    break;
                default:
                    self::enqueue_script( 'sumo-pp-admin-general', self::get_asset_url( 'js/admin/admin-general.js' ), array(
                        'plan_search_nonce' => wp_create_nonce( 'sumo-pp-search-payment-plan' ),
                    ) );
                    break;
            }
        }

        if ( 'woocommerce_page_wc-settings' === $screen_id ) {
            self::enqueue_script( 'sumo-pp-admin-settings-wc', self::get_asset_url( 'js/admin/admin-settings-wc.js' ) );
        }
    }

    /**
     * Load style in backend.
     */
    public static function admin_style() {
        $screen       = get_current_screen();
        $screen_id    = $screen ? $screen->id : '';
        $wc_screen_id = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );

        if ( in_array( $screen_id, array( self::$menu_title . '_page_sumo_pp_settings', 'sumo_pp_payments', 'sumo_payment_plans', $wc_screen_id . '_page_wc-orders', 'shop_order', 'edit-sumo_pp_payments', 'edit-sumo_payment_plans' ) ) ) {
            self::enqueue_style( 'sumo-pp-admin', self::get_asset_url( 'css/admin.css' ) );
        }
    }

    /**
     * Perform script localization in frontend.
     *
     * @global object $post
     */
    public static function frontend_script() {
        global $post;

        self::enqueue_script( 'sumo-pp-single-product', self::get_asset_url( 'js/frontend/single-product.js' ), array(
            'wp_ajax_url'                         => admin_url( 'admin-ajax.php' ),
            'product'                             => isset( $post->ID ) ? $post->ID : false,
            'get_wc_booking_deposit_fields_nonce' => wp_create_nonce( 'sumo-pp-get-payment-type-fields' ),
            'hide_product_price'                  => get_option( SUMO_PP_PLUGIN_PREFIX . 'hide_product_price_for_payment_plans', 'no' ),
            'price_based_on'                      => get_option( SUMO_PP_PLUGIN_PREFIX . 'calc_deposits_r_payment_plans_price_based_on', 'sale-price' ),
            'variation_deposit_form_template'     => SUMO_PP_Variation_Deposit_Form::get_template(),
        ) );

        self::enqueue_script( 'sumo-pp-checkout', self::get_asset_url( 'js/frontend/checkout.js' ), array(
            'wp_ajax_url'                                 => admin_url( 'admin-ajax.php' ),
            'is_user_logged_in'                           => is_user_logged_in(),
            'orderpp_nonce'                               => wp_create_nonce( 'sumo-pp-checkout-orderpp' ),
            'can_user_deposit_payment'                    => _sumo_pp()->orderpp->can_user_deposit_payment(),
            'maybe_prevent_from_hiding_guest_signup_form' => 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) && 'yes' !== get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
            'current_page'                                => is_checkout() ? 'checkout' : 'cart',
        ) );

        self::enqueue_script( 'sumo-pp-myaccount', self::get_asset_url( 'js/frontend/myaccount.js' ), array(
            'wp_ajax_url'           => admin_url( 'admin-ajax.php' ),
            'show_more_notes_label' => __( 'Show More', 'sumopaymentplans' ),
            'show_less_notes_label' => __( 'Show Less', 'sumopaymentplans' ),
            'myaccount_nonce'       => wp_create_nonce( 'sumo-pp-myaccount' ),
        ) );
    }

    /**
     * Load style in frontend.
     */
    public static function frontend_style() {
        self::enqueue_style( 'sumo-pp-frontend', self::get_asset_url( 'css/frontend.css' ) );
    }

    /**
     * Load WooCommerce enqueues.
     *
     * @global object $typenow
     * @param array $screen_ids
     * @return array
     */
    public static function load_wc_enqueues( $screen_ids ) {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if ( in_array( $screen_id, array( 'sumo_pp_payments', 'edit-sumo_pp_payments', 'sumo_payment_plans', 'edit-sumo_payment_plans', self::$menu_title . '_page_sumo_pp_settings', self::$menu_title . '_page_sumo-pp-payments-exporter' ) ) ) {
            $screen_ids[] = $screen_id;
        }

        return $screen_ids;
    }

}

SUMO_PP_Enqueues::init();
