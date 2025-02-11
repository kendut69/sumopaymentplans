<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<ul class="payment_notes">
	<?php
	foreach ( $notes as $note ) {
		include 'html-admin-payment-note.php'  ;
	}
	?>
</ul>

<div class="add_payment_note">
	<h4><?php esc_html_e( 'Add note', 'sumopaymentplans' ) ; ?></h4>
	<p><textarea type="text" id="payment_note" class="input-text" cols="20" rows="3"></textarea></p>
	<p><a href="#" class="add_note button" data-id="<?php echo esc_attr( $post->ID ) ; ?>"><?php esc_html_e( 'Add', 'sumopaymentplans' ) ; ?></a></p>
</div>
