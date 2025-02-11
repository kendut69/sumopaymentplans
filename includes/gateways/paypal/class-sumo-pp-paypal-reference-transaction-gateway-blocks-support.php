<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

/**
 * SUMO_PP_Paypal_Reference_Transactions_Blocks_Support class.
 *
 * @since 11.0.0
 * @extends AbstractPaymentMethodType
 */
final class SUMO_PP_Paypal_Reference_Transactions_Blocks_Support extends AbstractPaymentMethodType {

    /**
     * Payment method name/id/slug (matches id in SUMO_PP_Paypal_Reference_Transactions in core).
     *
     * @since 11.0.0
     * @var string
     */
    protected $name = 'sumo_pp_paypal_reference_txns';

    /**
     * Gets the gateway.
     * 
     * @since 11.0.0
     * @var SUMO_PP_Paypal_Reference_Transactions
     */
    private $gateway;

    /**
     * Initializes the payment method type.
     * 
     * @since 11.0.0
     */
    public function initialize() {
        $this->settings = get_option( "woocommerce_{$this->name}_settings", array() );

        if ( ! $this->gateway ) {
            $gateways = WC()->payment_gateways->payment_gateways();

            if ( isset( $gateways[ $this->name ] ) ) {
                $this->gateway = $gateways[ $this->name ];
            }
        }
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @since 11.0.0
     * @return boolean
     */
    public function is_active() {
        return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Get the file modified time as a cache buster if we're in dev mode.
     *
     * @param string $file Local path to the file.
     * @since 11.0.0
     * @return string The cache buster value to use for the given file.
     */
    protected function get_file_version( $file ) {
        if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
            return filemtime( $file );
        }
        return SUMO_PP_PLUGIN_VERSION;
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @since 11.0.0
     * @return array
     */
    public function get_payment_method_script_handles() {
        $script_path = 'blocks/frontend/index.js';
        $style_path  = 'blocks/frontend/index.css';

        $script_url = SUMO_PP_PLUGIN_URL . "/assets/{$script_path}";
        $style_url  = SUMO_PP_PLUGIN_URL . "/assets/{$style_path}";

        $script_asset_path = SUMO_PP_PLUGIN_DIR . 'assets/blocks/frontend/index.asset.php';
        $style_asset_path  = SUMO_PP_PLUGIN_DIR . 'assets/blocks/frontend/index.css';

        $script_asset = file_exists( $script_asset_path ) ? require $script_asset_path : array(
            'dependencies' => array(),
            'version'      => $this->get_file_version( $script_asset_path ),
        );

        wp_enqueue_style(
                'sumo-pp-blocks-integration',
                $style_url,
                array(),
                $this->get_file_version( $style_asset_path )
        );

        wp_register_script(
                'sumo-pp-blocks-integration',
                $script_url,
                $script_asset[ 'dependencies' ],
                $script_asset[ 'version' ],
                true
        );

        wp_set_script_translations(
                'sumo-pp-blocks-integration',
                'sumopaymentplans',
        );

        return array( 'sumo-pp-blocks-integration' );
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @since 11.0.0
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title'        => $this->get_setting( 'title' ),
            'description'  => $this->gateway ? $this->gateway->get_description() : $this->get_setting( 'description' ),
            'is_available' => $this->gateway ? $this->gateway->is_available() : false,
            'supports'     => $this->get_supported_features(),
        );
    }

    /**
     * Returns an array of supported features.
     *
     * @since 11.0.0
     * @return array
     */
    public function get_supported_features() {
        $supports = array();

        if ( $this->gateway ) {
            $supports = array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) );
        }

        return $supports;
    }

}
