<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<ul class="order_actions submitbox">
	<li class="wide" id="_sumo_pp_payment_actions">
		<select name="_sumo_pp_payment_actions" class="wc-enhanced-select wide">
			<option value=""><?php esc_html_e( 'Actions', 'sumopaymentplans' ) ; ?></option>
			<optgroup label="<?php esc_html_e( 'Resend payment emails', 'sumopaymentplans' ) ; ?>">
				<?php
				$mails            = _sumo_pp()->mailer->get_emails() ;
				$available_emails = array( 'payment_schedule', 'payment_cancelled' ) ;

				switch ( $payment->get_status() ) {
					case 'in_progress':
					case 'await_cancl':
					case 'cancelled':
					case 'failed':
						if ( $payment->balance_payable_order_exists() ) {
							if ( 'pay-in-deposit' === $payment->get_payment_type() ) {
								$available_emails[] = 'auto' === $payment->get_payment_mode() ? 'deposit_balance_payment_auto_charge_reminder' : 'deposit_balance_payment_invoice' ;
							} else {
								$available_emails[] = 'auto' === $payment->get_payment_mode() ? 'payment_plan_auto_charge_reminder' : 'payment_plan_invoice' ;
							}
						}
						break ;
					case 'pendng_auth':
						$available_emails[] = 'payment_pending_auth' ;
						break ;
					case 'overdue':
						if ( 'pay-in-deposit' === $payment->get_payment_type() ) {
							$available_emails[] = 'deposit_balance_payment_overdue' ;
						} else {
							$available_emails[] = 'payment_plan_overdue' ;
						}
						break ;
				}

				foreach ( $mails as $mail ) {
					if ( ! isset( $mail->id ) ) {
						continue ;
					}

					if ( in_array( str_replace( SUMO_PP_PLUGIN_PREFIX, '', $mail->id ), $available_emails ) ) {
						echo '<option value="send_email_' . esc_attr( $mail->id ) . '">' . esc_html( $mail->title ) . '</option>' ;
					}
				}
				?>
			</optgroup>
		</select>
	</li>
	<li class="wide">
		<div id="delete-action">
			<?php
			if ( current_user_can( 'delete_post', $post->ID ) ) {
				if ( ! EMPTY_TRASH_DAYS ) {
					$delete_text = __( 'Delete Permanently', 'sumopaymentplans' ) ;
				} else {
					$delete_text = __( 'Move to Trash', 'sumopaymentplans' ) ;
				}
				?>
				<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ) ; ?>"><?php echo esc_html( $delete_text ) ; ?></a>
				<?php
			}
			?>
		</div>
		<input type="submit" class="button save_payments save_order button-primary tips" name="save" value="
		<?php
			   /* translators: 1: post type name */
			   printf( esc_html__( 'Save %s', 'sumopaymentplans' ), esc_html( get_post_type_object( $post->post_type )->labels->singular_name ) ) ;
		?>
			   " data-tip="
			   <?php
				/* translators: 1: post type name */
				printf( esc_html__( 'Save/update the %s', 'sumopaymentplans' ), esc_html( get_post_type_object( $post->post_type )->labels->singular_name ) ) ;
				?>
			   " />
	</li>
</ul>
