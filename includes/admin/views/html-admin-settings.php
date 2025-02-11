<?php
defined( 'ABSPATH' ) || exit ;
?>
<div class="wrap woocommerce">
	<form method="post" id="mainform" action="" enctype="multipart/form-data">
		<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
		<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php
			$_tabs = apply_filters( 'sumopaymentplans_settings_tabs_array', array() ) ;

			foreach ( $_tabs as $name => $label ) {
				echo '<a href="' . esc_url( admin_url( 'admin.php?page=sumo_pp_settings&tab=' . $name ) ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>' ;
			}
			do_action( 'sumopaymentplans_settings_tabs' ) ;
			?>
		</h2>
		<?php
		switch ( $current_tab ) :
			default:
				do_action( 'sumopaymentplans_sections_' . $current_tab ) ;
				do_action( 'sumopaymentplans_settings_' . $current_tab ) ;
				break ;
		endswitch ;
		?>
		<?php if ( apply_filters( 'sumopaymentplans_submit_' . $current_tab, true ) ) : ?>
			<p class="submit">
				<?php if ( ! isset( $GLOBALS[ 'hide_save_button' ] ) ) : ?>
					<input name="save" class="button-primary" type="submit" value="<?php esc_html_e( 'Save changes', 'sumopaymentplans' ) ; ?>" />
				<?php endif ; ?>
				<input type="hidden" name="subtab" id="last_tab" />
				<?php wp_nonce_field( 'sumo-payment-plans-settings' ) ; ?>
			</p>
		<?php endif ; ?>
	</form>
	<?php if ( apply_filters( 'sumopaymentplans_reset_' . $current_tab, true ) ) : ?>
		<form method="post" id="reset_mainform" action="" enctype="multipart/form-data" style="float: left; margin-top: -52px; margin-left: 159px;">
			<input name="reset" class="button-secondary" type="submit" value="<?php esc_html_e( 'Reset', 'sumopaymentplans' ) ; ?>"/>
			<input name="reset_all" class="button-secondary" type="submit" value="<?php esc_html_e( 'Reset All', 'sumopaymentplans' ) ; ?>"/>
			<?php wp_nonce_field( 'sumo-payment-plans-reset-settings' ) ; ?>
		</form>    
	<?php endif ; ?>
</div>
