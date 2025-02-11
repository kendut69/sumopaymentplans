<?php
/**
 * Output Cart > Cart Balance Payable details.
 *
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/checkout/cart-balance-payable.php.
 * 
 * @since 10.9.0
 */
defined( 'ABSPATH' ) || exit;

if ( $remaining_payable_amount > 0 ) :
    ?>
    <tr class="_sumo_pp_balance_payable_amount">
        <th><?php echo wp_kses_post( get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payable_amount_label' ) ); ?></th>
        <td data-title= <?php echo esc_attr( get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payable_amount_label' ) ); ?>>
            <strong><?php echo wp_kses_post( wc_price( $remaining_payable_amount ) . $tax_label ); ?></strong>
        </td>
    </tr>
    <br>
<?php endif; ?>

<?php if ( $total_payable_amount > 0 ) : ?>
    <tr class="_sumo_pp_total_payable_amount">
        <th><?php echo wp_kses_post( get_option( SUMO_PP_PLUGIN_PREFIX . 'total_payable_amount_label' ) ); ?></th>
        <td data-title= <?php echo esc_attr( get_option( SUMO_PP_PLUGIN_PREFIX . 'total_payable_amount_label' ) ); ?>>
            <p>
                <strong><?php echo wp_kses_post( wc_price( $total_payable_amount ) . $tax_label ); ?></strong>
            </p>
        </td>
    </tr>
<?php endif; ?>
