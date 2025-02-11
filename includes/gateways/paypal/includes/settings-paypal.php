<?php

/**
 * Settings for PayPal Gateway.
 */
defined( 'ABSPATH' ) || exit ;

return array(
	'enabled'               => array(
		'title'   => __( 'Enable/Disable', 'sumopaymentplans' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable PayPal Reference Transactions', 'sumopaymentplans' ),
		'default' => 'no',
	),
	'title'                 => array(
		'title'       => __( 'Title', 'sumopaymentplans' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'sumopaymentplans' ),
		'default'     => __( 'PayPal Reference Transactions', 'sumopaymentplans' ),
		'desc_tip'    => true,
	),
	'description'           => array(
		'title'       => __( 'Description', 'sumopaymentplans' ),
		'type'        => 'textarea',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', 'sumopaymentplans' ),
		'default'     => __( 'Pay via PayPal Reference Transactions', 'sumopaymentplans' ),
	),
	'testmode'              => array(
		'title'       => __( 'PayPal Reference Transactions sandbox', 'sumopaymentplans' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable PayPal Reference Transactions sandbox', 'sumopaymentplans' ),
		'default'     => 'no',
		/* translators: %s: URL */
		'description' => sprintf( __( 'PayPal Reference Transactions sandbox can be used to test payments. Sign up for a <a href="%s">developer account</a>.', 'sumopaymentplans' ), 'https://developer.paypal.com/' ),
	),
	'api_username'          => array(
		'title'       => __( 'Live API username', 'sumopaymentplans' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal', 'sumopaymentplans' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'api_password'          => array(
		'title'       => __( 'Live API password', 'sumopaymentplans' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayPal', 'sumopaymentplans' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'api_signature'         => array(
		'title'       => __( 'Live API signature', 'sumopaymentplans' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal', 'sumopaymentplans' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'sandbox_api_username'  => array(
		'title'       => __( 'Sandbox API username', 'sumopaymentplans' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal.', 'sumopaymentplans' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'sandbox_api_password'  => array(
		'title'       => __( 'Sandbox API password', 'sumopaymentplans' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayPal.', 'sumopaymentplans' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'sandbox_api_signature' => array(
		'title'       => __( 'Sandbox API signature', 'sumopaymentplans' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayPal.', 'sumopaymentplans' ),
		'default'     => '',
		'desc_tip'    => true,
	),
		) ;
