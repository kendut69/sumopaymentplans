<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<li rel="<?php echo absint( $note->id ) ; ?>" class="<?php echo isset( $note->meta[ 'comment_status' ] ) ? esc_attr( implode( $note->meta[ 'comment_status' ] ) ) : 'pending' ; ?>">
	<div class="note_content">
		<?php echo wp_kses_post( wpautop( wptexturize( $note->content ) ) ) ; ?>
	</div>
	<p class="meta">
		<abbr class="exact-date" title="<?php echo esc_attr( _sumo_pp_get_date_to_display( $note->date_created, 'admin' ) ) ; ?>"><?php echo esc_html( _sumo_pp_get_date_to_display( $note->date_created, 'admin' ) ) ; ?></abbr>
		<?php
		if ( 'system' !== $note->added_by ) :
			/* translators: %s: note author */
			echo esc_html( sprintf( ' ' . __( 'by %s', 'sumopaymentplans' ), $note->added_by ) ) ;
		endif ;
		?>
		<a href="#" class="delete_note"><?php esc_html_e( 'Delete note', 'sumopaymentplans' ) ; ?></a>
	</p>
</li>
