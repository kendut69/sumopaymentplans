<?php

/**
 * Bulk Action Settings.
 * 
 * @class SUMO_PP_Bulk_Action_Settings
 * @package Class
 */
class SUMO_PP_Bulk_Action_Settings extends SUMO_PP_Abstract_Settings {

    /**
     * SUMO_PP_Bulk_Action_Settings constructor.
     */
    public function __construct() {

        $this->id            = 'bulk_action';
        $this->label         = __( 'Bulk Action', 'sumopaymentplans' );
        $this->custom_fields = array(
            'get_tab_description_for_products',
            'get_product_select_type',
            'get_include_product_selector',
            'get_exclude_product_selector',
            'get_include_product_category_selector',
            'get_exclude_product_category_selector',
            'get_sumopaymentplans_status',
            'get_payment_type',
            'get_apply_global_level_settings',
            'get_force_deposit_r_payment_plans',
            'get_deposit_type',
            'get_deposit_price_type',
            'get_deposit_amount',
            'get_deposit_percentage',
            'get_user_defined_deposit_type',
            'get_min_user_defined_deposit_price',
            'get_max_user_defined_deposit_price',
            'get_min_deposit',
            'get_max_deposit',
            'get_pay_balance_type',
            'get_after_balance_payment_due_date',
            'get_selected_plans',
            'get_update_button_for_products',
            'get_tab_description_for_deleted_product_replacement',
            'get_deleted_product',
            'get_replace_product',
            'get_update_button_for_payments'
        );
        $this->settings      = $this->get_settings();
        $this->init();

        add_action( 'sumopaymentplans_submit_' . $this->id, '__return_false' );
        add_action( 'sumopaymentplans_reset_' . $this->id, '__return_false' );
    }

    /**
     * Get settings array.
     *
     * @return array
     */
    public function get_settings() {
        global $current_section;

        return apply_filters( 'sumopaymentplans_get_' . $this->id . '_settings', array(
            array(
                'name' => __( 'Payment Plans Product Bulk Update Settings', 'sumopaymentplans' ),
                'type' => 'title',
                'id'   => $this->prefix . 'bulk_action_settings',
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_tab_description_for_products' ),
            ),
            array(
                'name' => __( 'Product Bulk Update', 'sumopaymentplans' ),
                'type' => 'title',
                'id'   => $this->prefix . 'bulk_update_product_settings',
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_product_select_type' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_include_product_selector' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_exclude_product_selector' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_include_product_category_selector' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_exclude_product_category_selector' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_sumopaymentplans_status' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_payment_type' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_apply_global_level_settings' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_force_deposit_r_payment_plans' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_deposit_type' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_deposit_price_type' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_deposit_amount' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_deposit_percentage' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_user_defined_deposit_type' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_min_user_defined_deposit_price' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_max_user_defined_deposit_price' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_min_deposit' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_max_deposit' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_pay_balance_type' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_after_balance_payment_due_date' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_selected_plans' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_update_button_for_products' ),
            ),
            array( 'type' => 'sectionend', 'id' => $this->prefix . 'bulk_update_product_settings' ),
            array(
                'name' => __( 'Troubleshoot - Deleted Product Replacement', 'sumopaymentplans' ),
                'type' => 'title',
                'id'   => $this->prefix . 'deleted_product_replacement_settings',
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_tab_description_for_deleted_product_replacement' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_deleted_product' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_replace_product' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_update_button_for_payments' ),
            ),
            array( 'type' => 'sectionend', 'id' => $this->prefix . 'deleted_product_replacement_settings' ),
            array( 'type' => 'sectionend', 'id' => $this->prefix . 'bulk_action_settings' ),
                ) );
    }

    /**
     * Custom type field.
     */
    public function get_tab_description_for_products() {
        ?>
        <tr>
            <?php echo esc_html_e( 'Using these settings you can customize/modify the payment plans information in your site.', 'sumopaymentplans' ); ?>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_product_select_type() {
        ?>
        <tr>
            <th>
                <?php esc_html_e( 'Select Products/Categories', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <select name="get_product_select_type" id="get_product_select_type">
                    <option value="all-products" <?php selected( 'pay-in-deposit' === get_option( "bulk{$this->prefix}get_product_select_type", 'all-products' ), true ); ?>><?php esc_html_e( 'All Products', 'sumopaymentplans' ); ?></option>
                    <option value="included-products" <?php selected( 'included-products' === get_option( "bulk{$this->prefix}get_product_select_type", 'all-products' ), true ); ?>><?php esc_html_e( 'Include Products', 'sumopaymentplans' ); ?></option>
                    <option value="excluded-products" <?php selected( 'excluded-products' === get_option( "bulk{$this->prefix}get_product_select_type", 'all-products' ), true ); ?>><?php esc_html_e( 'Exclude Products', 'sumopaymentplans' ); ?></option>
                    <option value="included-categories" <?php selected( 'included-categories' === get_option( "bulk{$this->prefix}get_product_select_type", 'all-products' ), true ); ?>><?php esc_html_e( 'Include Categories', 'sumopaymentplans' ); ?></option>
                    <option value="excluded-categories" <?php selected( 'excluded-categories' === get_option( "bulk{$this->prefix}get_product_select_type", 'all-products' ), true ); ?>><?php esc_html_e( 'Exclude Categories', 'sumopaymentplans' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_include_product_selector() {
        _sumo_pp_wc_search_field( array(
            'class'       => 'wc-product-search',
            'id'          => 'get_included_products',
            'type'        => 'product',
            'action'      => 'woocommerce_json_search_products_and_variations',
            'title'       => __( 'Select Product(s) to Include', 'sumopaymentplans' ),
            'placeholder' => __( 'Search for a product&hellip;', 'sumopaymentplans' ),
            'options'     => get_option( "bulk{$this->prefix}get_included_products", array() ),
        ) );
    }

    /**
     * Custom type field.
     */
    public function get_exclude_product_selector() {
        _sumo_pp_wc_search_field( array(
            'class'       => 'wc-product-search',
            'id'          => 'get_excluded_products',
            'type'        => 'product',
            'action'      => 'woocommerce_json_search_products_and_variations',
            'title'       => __( 'Select Product(s) to Exclude', 'sumopaymentplans' ),
            'placeholder' => __( 'Search for a product&hellip;', 'sumopaymentplans' ),
            'options'     => get_option( "bulk{$this->prefix}get_excluded_products", array() ),
        ) );
    }

    /**
     * Custom type field.
     */
    public function get_include_product_category_selector() {
        ?>
        <tr>
            <th>
                <?php esc_html_e( 'Select Categorie(s) to Include', 'sumopaymentplans' ); ?>
            </th>
            <td>                
                <select name="get_included_categories[]" class="wc-enhanced-select" id="get_included_categories" multiple="multiple" style="min-width:350px;">
                    <?php
                    $option_value = get_option( "bulk{$this->prefix}get_included_categories", array() );

                    foreach ( _sumo_pp_get_product_categories() as $key => $val ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"
                        <?php
                        if ( is_array( $option_value ) ) {
                            selected( in_array( ( string ) $key, $option_value, true ), true );
                        } else {
                            selected( $option_value, ( string ) $key );
                        }
                        ?>
                                >
                                    <?php echo esc_html( $val ); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_exclude_product_category_selector() {
        ?>
        <tr>
            <th>
                <?php esc_html_e( 'Select Categorie(s) to Exclude', 'sumopaymentplans' ); ?>
            </th>
            <td>                
                <select name="get_excluded_categories[]" class="wc-enhanced-select" id="get_excluded_categories" multiple="multiple" style="min-width:350px;">
                    <?php
                    $option_value = get_option( "bulk{$this->prefix}get_excluded_categories", array() );

                    foreach ( _sumo_pp_get_product_categories() as $key => $val ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"
                        <?php
                        if ( is_array( $option_value ) ) {
                            selected( in_array( ( string ) $key, $option_value, true ), true );
                        } else {
                            selected( $option_value, ( string ) $key );
                        }
                        ?>
                                >
                                    <?php echo esc_html( $val ); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_sumopaymentplans_status() {
        ?>
        <tr>
            <th>
                <?php esc_html_e( 'SUMO Payment Plans', 'sumopaymentplans' ); ?>
            </th>
            <td>               
                <input type="checkbox" name="_sumo_pp_enable_sumopaymentplans" id="enable_sumopaymentplans" value="yes" <?php checked( 'yes' === get_option( "bulk{$this->prefix}enable_sumopaymentplans", 'no' ), true ); ?>> 
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_payment_type() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Payment Type', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <select name="_sumo_pp_payment_type" id="payment_type">
                    <option value="pay-in-deposit" <?php selected( 'pay-in-deposit' === get_option( "bulk{$this->prefix}payment_type", 'pay-in-deposit' ), true ); ?>><?php esc_html_e( 'Pay a Deposit Amount', 'sumopaymentplans' ); ?></option>
                    <option value="payment-plans" <?php selected( 'payment-plans' === get_option( "bulk{$this->prefix}payment_type", 'pay-in-deposit' ), true ); ?>><?php esc_html_e( 'Pay with Payment Plans', 'sumopaymentplans' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_apply_global_level_settings() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Apply Global Level Settings', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <input type="checkbox" name="_sumo_pp_apply_global_settings" id="apply_global_settings" value="yes" <?php checked( 'yes' === get_option( "bulk{$this->prefix}apply_global_settings", 'no' ), true ); ?>>                 
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_force_deposit_r_payment_plans() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Force Deposit/Payment Plans', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <input type="checkbox" name="_sumo_pp_force_deposit" id="force_deposit" value="yes" <?php checked( 'yes' === get_option( "bulk{$this->prefix}force_deposit", 'no' ), true ); ?>>                                 
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_deposit_type() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Deposit Type', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <select name="_sumo_pp_deposit_type" id="deposit_type">
                    <option value="pre-defined" <?php selected( 'pre-defined' === get_option( "bulk{$this->prefix}deposit_type", 'pre-defined' ), true ); ?>><?php esc_html_e( 'Predefined Deposit Amount', 'sumopaymentplans' ); ?></option>
                    <option value="user-defined" <?php selected( 'user-defined' === get_option( "bulk{$this->prefix}deposit_type", 'pre-defined' ), true ); ?>><?php esc_html_e( 'User Defined Deposit Amount', 'sumopaymentplans' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_deposit_price_type() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Deposit Price Type', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <select name="_sumo_pp_deposit_price_type" id="deposit_price_type">
                    <option value="fixed-price" <?php selected( 'fixed-price' === get_option( "bulk{$this->prefix}deposit_price_type", 'fixed-price' ), true ); ?>><?php esc_html_e( 'Fixed Price', 'sumopaymentplans' ); ?></option>
                    <option value="percent-of-product-price" <?php selected( 'percent-of-product-price' === get_option( "bulk{$this->prefix}deposit_price_type", 'fixed-price' ), true ); ?>><?php esc_html_e( 'Percentage of Product Price', 'sumopaymentplans' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_deposit_amount() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Deposit Amount', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <input name="_sumo_pp_fixed_deposit_price" id="fixed_deposit_price" type="number" min="0.01" step="0.01" value="<?php echo esc_attr( get_option( "bulk{$this->prefix}fixed_deposit_price", '0.01' ) ); ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_deposit_percentage() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Deposit Percentage', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <input name="_sumo_pp_fixed_deposit_percent" id="fixed_deposit_percent" type="number" min="0.01" max="99.99" step="0.01" value="<?php echo esc_attr( get_option( "bulk{$this->prefix}fixed_deposit_percent", '0.01' ) ); ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_user_defined_deposit_type() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'User Defined Deposit Type', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <select name="_sumo_pp_user_defined_deposit_type" id="user_defined_deposit_type">
                    <option value="percent-of-product-price" <?php selected( 'percent-of-product-price' === get_option( "bulk{$this->prefix}user_defined_deposit_type", 'percent-of-product-price' ), true ); ?>><?php esc_html_e( 'Percentage of Product Price', 'sumopaymentplans' ); ?></option>
                    <option value="fixed-price" <?php selected( 'fixed-price' === get_option( "bulk{$this->prefix}user_defined_deposit_type", 'percent-of-product-price' ), true ); ?>><?php esc_html_e( 'Fixed Price', 'sumopaymentplans' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_min_user_defined_deposit_price() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Minimum Deposit Price', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <input name="_sumo_pp_min_user_defined_deposit_price" id="min_user_defined_deposit_price" type="text" value="<?php echo esc_attr( get_option( "bulk{$this->prefix}min_user_defined_deposit_price" ) ); ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_max_user_defined_deposit_price() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Maximum Deposit Price', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <input name="_sumo_pp_max_user_defined_deposit_price" id="max_user_defined_deposit_price" type="text" value="<?php echo esc_attr( get_option( "bulk{$this->prefix}max_user_defined_deposit_price" ) ); ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_min_deposit() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Minimum Deposit(%)', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <input name="_sumo_pp_min_deposit" id="min_deposit" type="number" min="0.01" max="99.99" step="0.01" value="<?php echo esc_attr( get_option( "bulk{$this->prefix}min_deposit", '0.01' ) ); ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_max_deposit() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Maximum Deposit(%)', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <input name="_sumo_pp_max_deposit" id="max_deposit" type="number" min="0.01" max="99.99" step="0.01" value="<?php echo esc_attr( get_option( "bulk{$this->prefix}max_deposit", '0.01' ) ); ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_pay_balance_type() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Deposit Balance Payment Due Date', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <select name="_sumo_pp_pay_balance_type" id="pay_balance_type" style="width:95px;">
                    <option value="after" <?php selected( 'after' === get_option( "bulk{$this->prefix}pay_balance_type", 'after' ), true ); ?>><?php esc_html_e( 'After', 'sumopaymentplans' ); ?></option>
                    <option value="before" <?php selected( 'before' === get_option( "bulk{$this->prefix}pay_balance_type", 'after' ), true ); ?>><?php esc_html_e( 'Before', 'sumopaymentplans' ); ?></option>
                </select>
                <input name="_sumo_pp_pay_balance_after" id="pay_balance_after" type="number" value="<?php echo esc_attr( get_option( "bulk{$this->prefix}pay_balance_after", '1' ) ); ?>" style="width:150px;"/>
                <input name="_sumo_pp_pay_balance_before" id="pay_balance_before" type="text" placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumopaymentplans' ); ?>" value="<?php echo esc_attr( get_option( "bulk{$this->prefix}pay_balance_before", '' ) ); ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_after_balance_payment_due_date() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'After Balance Payment Due Date', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <select name="_sumo_pp_set_expired_deposit_payment_as" id="set_expired_deposit_payment_as">
                    <option value="normal" <?php selected( 'normal' === get_option( "bulk{$this->prefix}set_expired_deposit_payment_as", 'normal' ), true ); ?>><?php esc_html_e( 'Disable SUMO Payment Plans', 'sumopaymentplans' ); ?></option>
                    <option value="out-of-stock" <?php selected( 'out-of-stock' === get_option( "bulk{$this->prefix}set_expired_deposit_payment_as", 'normal' ), true ); ?>><?php esc_html_e( 'Set Product as Out of Stock', 'sumopaymentplans' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_selected_plans() {
        ?>
        <tr class="bulk-update-products-field-row">
            <th>
                <?php esc_html_e( 'Select Plans', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <span class="_sumo_pp_add_plans">
                    <span class="woocommerce-help-tip" data-tip="<?php esc_html_e( 'Select the layout as per your theme preference', 'sumopaymentplans' ); ?>"></span>
                    <a href="#" class="button" id="_sumo_pp_add_col_1_plan"><?php esc_html_e( 'Add Row for Column 1', 'sumopaymentplans' ); ?></a>
                    <a href="#" class="button" id="_sumo_pp_add_col_2_plan"><?php esc_html_e( 'Add Row for Column 2', 'sumopaymentplans' ); ?></a>
                    <span class="spinner"></span>
                </span>
                <?php
                $selected_plans = get_option( "bulk{$this->prefix}selected_plans", array() );
                $selected_plans = is_array( $selected_plans ) && ! empty( $selected_plans ) ? $selected_plans : array( 'col_1' => array(), 'col_2' => array() );
                ?>
                <div class="sumo-pp-selected-plans">
                    <?php
                    foreach ( $selected_plans as $column_id => $selected_datas ) {
                        $inline_style = 'col_1' === $column_id ? 'width:49%;display:block;float:left;clear:none;' : 'width:49%;display:block;float:right;clear:none;margin-right:10px;';
                        ?>
                        <table class="widefat wc_input_table wc_gateways sortable <?php echo esc_attr( "_sumo_pp_selected_col_{$column_id}_plans" ); ?> _sumo_pp_selected_plans _sumo_pp_fields" style="<?php echo esc_attr( $inline_style ); ?>">
                            <tbody class="selected_plans">
                                <?php
                                if ( is_array( $selected_datas ) && ! empty( $selected_datas ) ) {
                                    foreach ( $selected_datas as $row_id => $selected_data ) {
                                        echo '<tr><td class="sort" width="1%"></td><td>';
                                        _sumo_pp_wc_search_field( array(
                                            'class'       => 'wc-product-search',
                                            'action'      => '_sumo_pp_json_search_payment_plans',
                                            'id'          => "selected_{$column_id}_payment_plan_{$row_id}",
                                            'name'        => "_sumo_pp_selected_plans[{$column_id}][{$row_id}]",
                                            'type'        => 'payment_plans',
                                            'multiple'    => false,
                                            'options'     => ( array ) $selected_data,
                                            'placeholder' => __( 'Search for a payment plan&hellip;', 'sumopaymentplans' ),
                                        ) );
                                        echo '</td><td><a href="#" class="remove_row button">X</a></td></tr>';
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php
                    }
                    ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_update_button_for_products() {
        ?>
        <tr class="bulk-update-products">
            <td>
                <input type="button" id="bulk_update_products" class="button-primary" value="<?php esc_html_e( 'Save and Update', 'sumopaymentplans' ); ?>" />
                <span class="spinner"></span>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_tab_description_for_deleted_product_replacement() {
        ?>
        <p>
            <?php echo esc_html_e( "If product linked with an ongoing plan was deleted, then future installments won't work properly. In that case, replace the deleted product with a new product using the below options.", 'sumopaymentplans' ); ?>
        </p>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_deleted_product() {
        ?>
        <tr class="bulk-update-payments-field-row">
            <th>
                <?php esc_html_e( 'Deleted Product ID', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <input name="_deleted_product" type="number" min="0" value="" />
                <p class="description"><?php esc_html_e( 'This can be found in "SUMO Payment Plans > Payments" under "Product Name" column of the respective payment.', 'sumopaymentplans' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_replace_product() {
        ?>
        <tr class="bulk-update-payments-field-row">
            <th>
                <?php esc_html_e( 'Product to be Replaced', 'sumopaymentplans' ); ?>
            </th>
            <td>
                <?php
                _sumo_pp_wc_search_field( array(
                    'class'       => 'wc-product-search',
                    'id'          => '_replace_product',
                    'type'        => 'product',
                    'multiple'    => false,
                    'action'      => 'woocommerce_json_search_products_and_variations',
                    'placeholder' => __( 'Search for a product&hellip;', 'sumopaymentplans' ),
                ) );
                ?>
                <p class="description"><?php esc_html_e( 'This product will be replaced with the deleted product.', 'sumopaymentplans' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_update_button_for_payments() {
        ?>
        <tr class="bulk-update-payments">
            <td>
                <input type="button" id="bulk_update_payments" class="button-primary" value="<?php esc_html_e( 'Update', 'sumopaymentplans' ); ?>" />
                <span class="spinner"></span>
            </td>
        </tr>
        <?php
    }

}

return new SUMO_PP_Bulk_Action_Settings();
