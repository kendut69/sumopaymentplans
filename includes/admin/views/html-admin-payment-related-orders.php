<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

$columns = apply_filters( 'sumopaymentplans_admin_related_orders_table_columns', array(
	'order_id' => __( 'Order Number', 'sumopaymentplans' ),
	'relation' => __( 'Relationship', 'sumopaymentplans' ),
	'date'     => __( 'Date', 'sumopaymentplans' ),
	'status'   => __( 'Status', 'sumopaymentplans' ),
	'total'    => __( 'Total', 'sumopaymentplans' ),
		) ) ;
?>
<div class="sumo-pp-payment-related-orders">
	<table>
		<thead>
			<tr>
				<?php foreach ( $columns as $column_title ) { ?>
					<th><?php echo esc_html( $column_title ) ; ?></th> 
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $related_orders as $_id => $_order ) {
				?>
				<tr>
					<td><a href="<?php echo esc_url( admin_url( "post.php?post={$_id}&action=edit" ) ) ; ?>">#<?php echo esc_html( $_order[ 'order_id' ] ) ; ?></a></td>
					<td><?php echo wp_kses_post( $_order[ 'relation' ] ) ; ?></td>
					<td><?php echo wp_kses_post( $_order[ 'date' ] ) ; ?></td>
					<td>
						<div class="sumo-pp-payment-status-wrap">
							<?php
							printf( '<mark class="order-status status-%s %s"/>%s</mark>', esc_attr( $_order[ 'status' ] ), esc_attr( $_order[ 'status' ] ), esc_html( $_order[ 'status_label' ] ) ) ;
							?>
						</div>
					</td>
					<td><?php echo wp_kses_post( $_order[ 'total' ] ) ; ?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>
