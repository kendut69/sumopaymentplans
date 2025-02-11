<?php
/**
 * Shows an order item.
 */
defined( 'ABSPATH' ) || exit ;

$product               = $item->get_product() ;
$meta_data             = $item->get_formatted_meta_data( '' ) ;
$product_link          = $product ? admin_url( 'post.php?post=' . $item->get_product_id() . '&action=edit' ) : '' ;
$thumbnail             = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '' ;
$row_class             = apply_filters( 'woocommerce_admin_html_order_item_class', ! empty( $class ) ? $class : '', $item, $order ) ;
$hidden_order_itemmeta = apply_filters( 'woocommerce_hidden_order_itemmeta', array(
	'_qty',
	'_tax_class',
	'_product_id',
	'_variation_id',
	'_line_subtotal',
	'_line_subtotal_tax',
	'_line_total',
	'_line_tax',
	'method_id',
	'cost',
	'_reduced_stock',
		) ) ;
?>
<tr class="item <?php echo esc_attr( apply_filters( 'woocommerce_admin_html_order_item_class', ( ! empty( $class ) ? $class : '' ), $item, $order ) ) ; ?>" data-order_item_id="<?php echo esc_attr( $item_id ) ; ?>">
	<td class="thumb">
		<?php if ( $_product ) : ?>
			<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $_product->get_id() ) . '&action=edit' ) ) ; ?>" class="tips" data-tip="
			<?php
			echo '<strong>' . esc_html__( 'Product ID:', 'sumopaymentplans' ) . '</strong> ' . absint( $item[ 'product_id' ] ) ;

			if ( ! empty( $item[ 'variation_id' ] ) && 'product_variation' === get_post_type( $item[ 'variation_id' ] ) ) {
				echo '<br/><strong>' . esc_html__( 'Variation ID:', 'sumopaymentplans' ) . '</strong> ' . absint( $item[ 'variation_id' ] ) ;
			} elseif ( ! empty( $item[ 'variation_id' ] ) ) {
				echo '<br/><strong>' . esc_html__( 'Variation ID:', 'sumopaymentplans' ) . '</strong> ' . absint( $item[ 'variation_id' ] ) . ' (' . esc_html__( 'No longer exists', 'sumopaymentplans' ) . ')' ;
			}

			if ( $_product && $_product->get_sku() ) {
				echo '<br/><strong>' . esc_html__( 'Product SKU:', 'sumopaymentplans' ) . '</strong> ' . esc_html( $_product->get_sku() ) ;
			}

			if ( $_product && 'variation' === $_product->get_type() ) {
				echo '<br/>' . wp_kses_post( wc_get_formatted_variation( $_product->get_variation_attributes(), true ) ) ;
			}
			?>
			   ">
				   <?php echo wp_kses_post( $_product->get_image( array( 40, 40 ), array( 'title' => '' ) ) ) ; ?>
			</a>
		<?php endif ; ?>
	</td>
	<td class="name" data-sort-value="<?php echo esc_attr( $item[ 'name' ] ) ; ?>">

		<?php echo ( $_product && $_product->get_sku() ) ? esc_html( $_product->get_sku() ) . ' &ndash; ' : '' ; ?>

		<?php if ( $_product ) : ?>
			<a target="_blank" href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $_product->get_parent_id() ? $_product->get_parent_id() : $_product->get_id()  ) . '&action=edit' ) ) ; ?>">
				<?php echo esc_html( $item[ 'name' ] ) ; ?>
			</a>
		<?php else : ?>
			<?php echo esc_html( $item[ 'name' ] ) ; ?>
		<?php endif ; ?>
		<input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr( $item_id ) ; ?>" />
		<input type="hidden" name="order_item_tax_class[<?php echo absint( $item_id ) ; ?>]" value="<?php echo isset( $item[ 'tax_class' ] ) ? esc_attr( $item[ 'tax_class' ] ) : '' ; ?>" />
		<div class="view">
			<?php if ( $meta_data ) : ?>
				<table cellspacing="0" class="display_meta">
					<?php
					foreach ( $meta_data as $meta_id => $meta ) :
						if ( in_array( $meta->key, $hidden_order_itemmeta, true ) ) {
							continue ;
						}
						?>
						<tr>
							<th><?php echo wp_kses_post( $meta->display_key ) ; ?>:</th>
							<td><?php echo wp_kses_post( force_balance_tags( $meta->display_value ) ) ; ?></td>
						</tr>
					<?php endforeach ; ?>
				</table>
			<?php endif ; ?>
		</div>
	</td>

	<td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr( $order->get_item_subtotal( $item, false, true ) ) ; ?>">
		<div class="view">
			<?php
			if ( isset( $item[ 'line_total' ] ) ) {
				if ( isset( $item[ 'line_subtotal' ] ) && round( ( float ) $item[ 'line_subtotal' ], 2 ) != round( ( float ) $item[ 'line_total' ], 2 ) ) {
					echo '<del>' . wp_kses_post( wc_price( $order->get_item_subtotal( $item, false, true ), array( 'currency' => $order->get_currency() ) ) ) . '</del> ' ;
				}

				echo '<b>' . wp_kses_post( wc_price( $order->get_item_total( $item, false, true ), array( 'currency' => $order->get_currency() ) ) ) . '</b>' ;
			}
			?>
		</div>
	</td>

	<td class="quantity" width="1%">
		<div class="view">
			<?php
			echo '<small class="times">&times;</small> ' . ( isset( $item[ 'qty' ] ) ? esc_html( $item[ 'qty' ] ) : '1' ) ;
			?>
		</div>
	</td>

	<td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr( isset( $item[ 'line_total' ] ) ? $item[ 'line_total' ] : ''  ) ; ?>">
		<div class="view">
			<?php
			if ( isset( $item[ 'line_total' ] ) ) {
				echo '<b>' . wp_kses_post( wc_price( $item[ 'line_total' ], array( 'currency' => $order->get_currency() ) ) ) . '</b>' ;
			}
			if ( isset( $item[ 'line_subtotal' ] ) && round( ( float ) $item[ 'line_subtotal' ], 2 ) != round( ( float ) $item[ 'line_total' ], 2 ) ) {
				echo '<br><span class="wc-order-item-discount">-' . wp_kses_post( wc_price( wc_format_decimal( $item[ 'line_subtotal' ] - $item[ 'line_total' ], '' ), array( 'currency' => $order->get_currency() ) ) ) . '</span>' ;
			}
			?>
		</div>
	</td>

	<?php
	if ( empty( $legacy_order ) && wc_tax_enabled() ) :
		$line_tax_data = isset( $item[ 'line_tax_data' ] ) ? $item[ 'line_tax_data' ] : '' ;
		$tax_data      = maybe_unserialize( $line_tax_data ) ;

		foreach ( $order_taxes as $tax_item ) :
			$tax_item_id        = $tax_item[ 'rate_id' ] ;
			$shipping_tax_total = $calculate_shipping && isset( $tax_item[ 'shipping_tax_total' ] ) ? $tax_item[ 'shipping_tax_total' ] : 0 ;
			$tax_item_total     = isset( $tax_data[ 'total' ][ $tax_item_id ] ) ? $tax_data[ 'total' ][ $tax_item_id ] : 0 ;
			$tax_item_subtotal  = isset( $tax_data[ 'subtotal' ][ $tax_item_id ] ) ? $tax_data[ 'subtotal' ][ $tax_item_id ] : 0 ;
			?>
			<td class="line_tax" width="1%">
				<div class="view">
					<?php
					if ( $tax_item_total ) {
						if ( isset( $tax_item_subtotal ) && round( ( float ) $tax_item_subtotal, 2 ) != round( ( float ) $tax_item_total, 2 ) ) {
							echo '<del>' . wp_kses_post( wc_price( wc_round_tax_total( $tax_item_subtotal ), array( 'currency' => $order->get_currency() ) ) ) . '</del> ' ;
						}

						$tax_total += $tax_item_total + $shipping_tax_total ;
						echo '<b>' . wp_kses_post( wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_currency() ) ) ) . '</b>' ;
					} else {
						echo '&ndash;' ;
					}
					?>
				</div>
			</td>
			<?php
			$calculate_shipping = false ;
		endforeach ;
	endif ;
	?>
</tr>
