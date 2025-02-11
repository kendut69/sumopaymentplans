<?php
/**
 * Output Deposit form.
 * 
 * This template can be overridden by copying it to yourtheme/sumopaymentplans/single-product/deposits/form.php.
 */
defined( 'ABSPATH' ) || exit;

$product_props_json = wp_json_encode( $product_props );
?>
<div class="<?php echo esc_attr( $class ); ?>" id="_sumo_pp_payment_type_fields" <?php echo $hide_if_variation ? 'style="display:none;"' : ''; ?> data-product_props="<?php echo wc_esc_json( $product_props_json ); ?>">
	<p>
		<?php if ( 'yes' !== $product_props[ 'force_deposit' ] ) { ?>
			<input type="radio" value="pay_in_full" name="_sumo_pp_payment_type" <?php checked( 'full-pay' === $product_props[ 'default_selected_type' ], true, true ); ?>/>
			<?php echo wp_kses_post( $pay_in_full_label ); ?>
		<?php } ?>
		<input type="radio" value="pay-in-deposit" name="_sumo_pp_payment_type" <?php checked( ( 'yes' === $product_props[ 'force_deposit' ] || 'deposit-pay' === $product_props[ 'default_selected_type' ] ), true, true ); ?>/>
		<?php echo wp_kses_post( $pay_a_deposit_amount_label ); ?>
		<?php do_action( 'sumopaymentplans_after_deposit_field_label', $product_props ); ?>
	</p>

	<div id="_sumo_pp_amount_to_choose" <?php echo 'yes' === $product_props[ 'force_deposit' ] ? '' : 'style="display: none;"'; ?>>
		<?php if ( 'user-defined' === $product_props[ 'deposit_type' ] ) { ?>
			<p>
				<label for="_sumo_pp_deposited_amount">
					<?php
					/* translators: 1: min deposit price 2: max deposit price */
					printf( esc_html__( 'Enter your Deposit Amount between %1$s and %2$s', 'sumopaymentplans' ), wp_kses_post( wc_price( $min_deposit_price ) ), wp_kses_post( wc_price( $max_deposit_price ) ) );
					?>
				</label>
				<input type="number" min="<?php echo floatval( $min_deposit_price ); ?>" max="<?php echo floatval( $max_deposit_price ); ?>" step="0.01" class="input-text" name="_sumo_pp_deposited_amount"/>
			</p>
		<?php } else { ?>
			<p>
				<?php echo wp_kses_post( wc_price( $fixed_deposit_price ) ); ?>
				<input type="hidden" name="_sumo_pp_deposited_amount" value="<?php echo esc_attr( $fixed_deposit_price ); ?>"/>
			</p>
		<?php } ?>
	</div>
</div>
