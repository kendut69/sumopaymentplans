<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Manage payment product.
 * 
 * @class SUMO_PP_Product_Manager
 * @package Class
 */
class SUMO_PP_Product_Manager {

	/**
	 * Core props.
	 * 
	 * @var array
	 */
	protected static $props = array(
		'product_id'                     => null,
		'product_price'                  => null,
		'product_type'                   => null,
		'payment_type'                   => null,
		'apply_global_settings'          => null,
		'force_deposit'                  => null,
		'default_selected_type'          => null,
		'deposit_type'                   => null,
		'is_available'                   => null,
		'deposit_price_type'             => null,
		'fixed_deposit_price'            => null,
		'fixed_deposit_percent'          => null,
		'user_defined_deposit_type'      => null,
		'min_user_defined_deposit_price' => null,
		'max_user_defined_deposit_price' => null,
		'min_deposit'                    => null,
		'max_deposit'                    => null,
		'pay_balance_type'               => null,
		'pay_balance_after'              => null,
		'pay_balance_before'             => null,
		'set_expired_deposit_payment_as' => null,
		'selected_plans'                 => null,
	);

	/**
	 * Check whether the Current user can pay with Deposits
	 */
	protected static $current_user_can_purchase = null;

	/**
	 * The single instance of the class.
	 */
	protected static $instance = null;

	/**
	 * Create instance for SUMO_PP_Product_Manager.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init SUMO_PP_Product_Manager
	 */
	public function init() {
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'alter_add_to_cart_text' ), 99, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_args', array( $this, 'prevent_ajax_add_to_cart' ), 99, 2 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'redirect_to_single_product' ), 99, 2 );

		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_deposit_form' ) );

		add_filter( 'woocommerce_product_is_in_stock', array( $this, 'check_product_is_in_stock' ), 99, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_custom_styles' ) );
		add_action( 'wp_loaded', array( $this, 'maybe_redirect_after_added_to_cart' ), 99 );

		include_once 'class-sumo-pp-variation-deposit-form.php';
	}

	public function current_user_can_purchase() {
		if ( is_bool( self::$current_user_can_purchase ) ) {
			return self::$current_user_can_purchase;
		}

		self::$current_user_can_purchase = _sumo_pp_user_can_purchase_payment( get_current_user_id() );
		return self::$current_user_can_purchase;
	}

	public function get_default_props() {
		return array_map( '__return_null', self::$props );
	}

	public function get_props( $product, $user_id = null ) {
		if ( $user_id ) {
			if ( ! _sumo_pp_user_can_purchase_payment( $user_id ) ) {
				return $this->get_default_props();
			}
		} else if ( ! $this->current_user_can_purchase() ) {
			return $this->get_default_props();
		}

		$product = wc_get_product( $product );

		if ( ! $product || 'yes' !== get_post_meta( $product->get_id(), '_sumo_pp_enable_sumopaymentplans', true ) ) {
			return $this->get_default_props();
		}

		$props                            = array();
		$props[ 'product_id' ]            = $product->get_id();
		$props[ 'payment_type' ]          = get_post_meta( $props[ 'product_id' ], '_sumo_pp_payment_type', true );
		$props[ 'default_selected_type' ] = get_post_meta( $props[ 'product_id' ], '_sumo_pp_default_selected_type', true );
		$props[ 'apply_global_settings' ] = 'yes' === get_post_meta( $props[ 'product_id' ], '_sumo_pp_apply_global_settings', true );
		$props[ 'is_available' ]          = true;

		if ( empty( $props[ 'default_selected_type' ] ) ) {
			$props[ 'default_selected_type' ] = 'full-pay';
		}

		$from = get_post_meta( $props[ 'product_id' ], '_sumo_pp_available_dates_from', true );
		$to   = get_post_meta( $props[ 'product_id' ], '_sumo_pp_available_dates_to', true );

		if ( '' !== $from || '' !== $to ) {
			$props[ 'is_available' ] = false;

			if ( '' !== $from && '' !== $to ) {
				if ( current_time( 'timestamp' ) >= _sumo_pp_get_timestamp( $from ) && current_time( 'timestamp' ) <= _sumo_pp_get_timestamp( $to ) ) {
					$props[ 'is_available' ] = true;
				}
			} else if ( '' !== $from ) {
				if ( current_time( 'timestamp' ) >= _sumo_pp_get_timestamp( $from ) ) {
					$props[ 'is_available' ] = true;
				}
			} else if ( current_time( 'timestamp' ) <= _sumo_pp_get_timestamp( $to ) ) {
				$props[ 'is_available' ] = true;
			}
		}

		if ( 'pay-in-deposit' === $props[ 'payment_type' ] ) {
			$props[ 'deposit_type' ] = $props[ 'apply_global_settings' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'deposit_type', 'pre-defined' ) : get_post_meta( $props[ 'product_id' ], '_sumo_pp_deposit_type', true );

			if ( 'user-defined' === $props[ 'deposit_type' ] ) {
				$props[ 'user_defined_deposit_type' ] = $props[ 'apply_global_settings' ] ? 'percent-of-product-price' : get_post_meta( $props[ 'product_id' ], '_sumo_pp_user_defined_deposit_type', true );

				if ( 'fixed-price' === $props[ 'user_defined_deposit_type' ] && ! $props[ 'apply_global_settings' ] ) {
					$props[ 'min_user_defined_deposit_price' ] = floatval( get_post_meta( $props[ 'product_id' ], '_sumo_pp_min_user_defined_deposit_price', true ) );
					$props[ 'max_user_defined_deposit_price' ] = floatval( get_post_meta( $props[ 'product_id' ], '_sumo_pp_max_user_defined_deposit_price', true ) );
				} else {
					$props[ 'min_deposit' ] = $props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'min_deposit', '0.01' ) ) : floatval( get_post_meta( $props[ 'product_id' ], '_sumo_pp_min_deposit', true ) );
					$props[ 'max_deposit' ] = $props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'max_deposit', '99.99' ) ) : floatval( get_post_meta( $props[ 'product_id' ], '_sumo_pp_max_deposit', true ) );
				}
			} else {
				$props[ 'deposit_price_type' ] = $props[ 'apply_global_settings' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'deposit_price_type', 'percent-of-product-price' ) : get_post_meta( $props[ 'product_id' ], '_sumo_pp_deposit_price_type', true );

				if ( 'percent-of-product-price' === $props[ 'deposit_price_type' ] ) {
					$props[ 'fixed_deposit_percent' ] = $props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_percent', '50' ) ) : floatval( get_post_meta( $props[ 'product_id' ], '_sumo_pp_fixed_deposit_percent', true ) );
				} else {
					$props[ 'fixed_deposit_price' ] = $props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_price', '0' ) ) : floatval( get_post_meta( $props[ 'product_id' ], '_sumo_pp_fixed_deposit_price', true ) );
				}
			}
			if ( $props[ 'apply_global_settings' ] ) {
				$props[ 'pay_balance_type' ]  = 'after';
				$props[ 'pay_balance_after' ] = false === get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' ) ? absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_balance_after' ) ) : absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' ) );
			} else {
				$props[ 'pay_balance_type' ] = '' === get_post_meta( $props[ 'product_id' ], '_sumo_pp_pay_balance_type', true ) ? 'after' : get_post_meta( $props[ 'product_id' ], '_sumo_pp_pay_balance_type', true );

				if ( 'after' === $props[ 'pay_balance_type' ] ) {
					$props[ 'pay_balance_after' ] = '' === get_post_meta( $props[ 'product_id' ], '_sumo_pp_balance_payment_due', true ) ? absint( get_post_meta( $props[ 'product_id' ], '_sumo_pp_pay_balance_after', true ) ) : absint( get_post_meta( $props[ 'product_id' ], '_sumo_pp_balance_payment_due', true ) );
				} else {
					$props[ 'pay_balance_before' ]             = get_post_meta( $props[ 'product_id' ], '_sumo_pp_pay_balance_before', true );
					$props[ 'set_expired_deposit_payment_as' ] = get_post_meta( $props[ 'product_id' ], '_sumo_pp_set_expired_deposit_payment_as', true );

					if ( $props[ 'pay_balance_before' ] && apply_filters( 'sumopaymentplans_end_installment_at_end_of_the_day', false, 'deposit' ) ) {
						$props[ 'pay_balance_before' ] .= ' 23:59:59';
					}
				}
			}
		} else if ( 'payment-plans' === $props[ 'payment_type' ] ) {
			$props[ 'selected_plans' ] = $props[ 'apply_global_settings' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'selected_plans', array() ) : get_post_meta( $props[ 'product_id' ], '_sumo_pp_selected_plans', true );
			$props[ 'selected_plans' ] = is_array( $props[ 'selected_plans' ] ) ? $props[ 'selected_plans' ] : array();
		}

		if ( 'sale-price' === get_option( SUMO_PP_PLUGIN_PREFIX . 'calc_deposits_r_payment_plans_price_based_on', 'sale-price' ) ) {
			$props[ 'product_price' ] = $product->get_price();
		} else {
			$props[ 'product_price' ] = $product->get_regular_price();
		}

		$props[ 'product_price_html' ]    = wc_price( $props[ 'product_price' ] );
		$props[ 'is_on_sale' ]            = $product->is_on_sale() ? 'yes' : '';
		$props[ 'product_type' ]          = $product->get_type();
		$props[ 'force_deposit' ]         = $props[ 'apply_global_settings' ] ? ( 'payment-plans' === $props[ 'payment_type' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'force_payment_plan', 'no' ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'force_deposit', 'no' ) ) : get_post_meta( $props[ 'product_id' ], '_sumo_pp_force_deposit', true );
		$props[ 'apply_global_settings' ] = $props[ 'apply_global_settings' ] ? 'yes' : 'no';

		self::$props = wp_parse_args( ( array ) apply_filters( 'sumopaymentplans_get_product_props', $props ), $this->get_default_props() );
		return self::$props;
	}

	public function get_cached_props() {
		return self::$props;
	}

	public function get_prop( $context, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'props'            => self::$props,
			'plan_props'       => array(),
			'deposited_amount' => 0,
			'qty'              => 1,
				) );

		if ( ! is_array( $args[ 'props' ] ) ) {
			$args[ 'props' ] = $this->get_props( $args[ 'props' ] );
		}

		if ( empty( $args[ 'props' ][ 'payment_type' ] ) ) {
			return null;
		}

		switch ( $args[ 'props' ][ 'payment_type' ] ) {
			case 'payment-plans':
				$prop = _sumo_pp()->plan->get_prop( $context, array(
					'props'         => $args[ 'plan_props' ],
					'product_price' => $args[ 'props' ][ 'product_price' ],
					'qty'           => $args[ 'qty' ],
						) );

				if ( is_null( $prop ) ) {
					if ( isset( $args[ 'props' ][ $context ] ) ) {
						return $args[ 'props' ][ $context ];
					}
				} else {
					return $prop;
				}
				break;
			case 'pay-in-deposit':
				switch ( $context ) {
					case 'total_payable':
						return $args[ 'props' ][ 'product_price' ] * $args[ 'qty' ];
					case 'balance_payable':
						$total_payable    = $args[ 'props' ][ 'product_price' ] * $args[ 'qty' ];
						$deposited_amount = floatval( $args[ 'deposited_amount' ] ) * $args[ 'qty' ];
						return max( $deposited_amount, $total_payable ) - min( $deposited_amount, $total_payable );
					case 'next_payment_on':
						if ( 'before' === $args[ 'props' ][ 'pay_balance_type' ] ) {
							return _sumo_pp_get_date( $args[ 'props' ][ 'pay_balance_before' ] );
						} else {
							$pay_balance_after = $args[ 'props' ][ 'pay_balance_after' ]; //in days
							return $pay_balance_after > 0 && 'after_admin_approval' !== get_option( SUMO_PP_PLUGIN_PREFIX . 'activate_payments', 'auto' ) ? _sumo_pp_get_date( "+{$pay_balance_after} days" ) : '';
						}
					default:
						if ( isset( $args[ 'props' ][ $context ] ) ) {
							return $args[ 'props' ][ $context ];
						}
				}
				break;
		}

		return null;
	}

	public function get_fixed_deposit_amount( $props = null ) {
		if ( is_null( $props ) ) {
			$props = self::$props;
		}

		if (
				'pay-in-deposit' === $props[ 'payment_type' ] &&
				'pre-defined' === $props[ 'deposit_type' ]
		) {
			if ( 'fixed-price' === $props[ 'deposit_price_type' ] ) {
				return $props[ 'fixed_deposit_price' ];
			}
			if ( ! is_null( $props[ 'product_price' ] ) ) {
				return ( $props[ 'product_price' ] * $props[ 'fixed_deposit_percent' ] ) / 100;
			}
		}

		return 0;
	}

	public function get_user_defined_deposit_amount_range( $props = null ) {
		if ( is_null( $props ) ) {
			$props = self::$props;
		}

		$min_amount = 0;
		$max_amount = 0;
		if (
				'pay-in-deposit' === $props[ 'payment_type' ] &&
				'user-defined' === $props[ 'deposit_type' ]
		) {
			if ( ! is_null( $props[ 'product_price' ] ) ) {
				if ( 'fixed-price' === $props[ 'user_defined_deposit_type' ] ) {
					$min_amount = $props[ 'min_user_defined_deposit_price' ];
					$max_amount = $props[ 'max_user_defined_deposit_price' ];
				} else {
					$min_amount = ( $props[ 'product_price' ] * $props[ 'min_deposit' ] ) / 100;
					$max_amount = ( $props[ 'product_price' ] * $props[ 'max_deposit' ] ) / 100;
				}
			}
		}

		return array(
			'min' => round( $min_amount, 2 ),
			'max' => round( $max_amount, 2 ),
		);
	}

	public function get_deposit_form( $props = null, $hide_if_variation = false, $class = '', $echo = false ) {
		if ( is_null( $props ) ) {
			$props = self::$props;
		}

		switch ( $props[ 'payment_type' ] ) {
			case 'pay-in-deposit':
				if ( 'before' === $props[ 'pay_balance_type' ] ) {
					if ( isset( $props[ 'booking_payment_end_date' ] ) ) {
						if ( $props[ 'booking_payment_end_date' ] && $props[ 'booking_payment_end_date' ] <= _sumo_pp_get_timestamp( 0, 0, true ) ) {
							return;
						}
						//ELSE display payment deposit fields. may be it is SUMO Booking product
					} else if ( _sumo_pp_get_timestamp( $props[ 'pay_balance_before' ], 0, true ) <= _sumo_pp_get_timestamp( 0, 0, true ) ) {
						return;
					}
				}

				$deposit_amount_range = $this->get_user_defined_deposit_amount_range( $props );

				ob_start();
				_sumo_pp_get_template( 'single-product/deposits/form.php', array(
					'product_props'              => $props,
					'min_deposit_price'          => $deposit_amount_range[ 'min' ],
					'max_deposit_price'          => $deposit_amount_range[ 'max' ],
					'fixed_deposit_price'        => $this->get_fixed_deposit_amount( $props ),
					'pay_in_full_label'          => get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_in_full_label' ),
					'pay_a_deposit_amount_label' => get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_a_deposit_amount_label' ),
					'hide_if_variation'          => $hide_if_variation,
					'class'                      => $class,
				) );

				if ( $echo ) {
					ob_end_flush();
				} else {
					return ob_get_clean();
				}
				break;
			case 'payment-plans':
				if ( empty( $props[ 'selected_plans' ] ) ) {
					return;
				}

				ob_start();
				_sumo_pp_get_template( 'single-product/payment-plans/form.php', array(
					'product_props'                  => $props,
					'product'                        => wc_get_product( $props[ 'product_id' ] ),
					'pay_in_full_label'              => get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_in_full_label' ),
					'pay_with_payment_plans_label'   => get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_with_payment_plans_label' ),
					'activate_payments'              => get_option( SUMO_PP_PLUGIN_PREFIX . 'activate_payments', 'auto' ),
					'display_add_to_cart_plan_link'  => get_option( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_add_to_cart_via_href' ),
					'added_to_cart_plan_redirect_to' => get_option( SUMO_PP_PLUGIN_PREFIX . 'after_hyperlink_clicked_redirect_to', 'product' ),
					'hide_if_variation'              => $hide_if_variation,
					'class'                          => $class,
				) );

				if ( $echo ) {
					ob_end_flush();
				} else {
					return ob_get_clean();
				}
				break;
		}

		return '';
	}

	public function is_payment_product( $props ) {
		if ( ! empty( $props ) ) {
			$payment_type = $this->get_prop( 'payment_type', array( 'props' => $props ) );
			$is_available = $this->get_prop( 'is_available', array( 'props' => $props ) );
		} else {
			$payment_type = $this->get_prop( 'payment_type' );
			$is_available = $this->get_prop( 'is_available' );
		}

		return $is_available && in_array( $payment_type, array( 'pay-in-deposit', 'payment-plans' ) );
	}

	public function alter_add_to_cart_text( $text, $product ) {
		if ( $this->is_payment_product( $product ) && ! in_array( self::$props[ 'product_type' ], array( 'variable', 'variation' ) ) ) {
			return get_option( SUMO_PP_PLUGIN_PREFIX . 'add_to_cart_label' );
		}

		return $text;
	}

	public function prevent_ajax_add_to_cart( $args, $product ) {
		if ( isset( $args[ 'class' ] ) && $this->is_payment_product( $product ) ) {
			$args[ 'class' ] = str_replace( 'ajax_add_to_cart', '', $args[ 'class' ] );
		}

		return $args;
	}

	public function redirect_to_single_product( $add_to_cart_url, $product ) {
		if ( $this->is_payment_product( $product ) ) {
			return get_permalink( $this->get_prop( 'product_id' ) );
		}

		return $add_to_cart_url;
	}

	/**
	 * Apply custom styles
	 */
	public function add_custom_styles() {
		if ( '' === get_option( SUMO_PP_PLUGIN_PREFIX . 'custom_css', '' ) ) {
			return;
		}

		wp_register_style( 'sumo-pp-custom-styles', false, array(), SUMO_PP_PLUGIN_VERSION );
		wp_enqueue_style( 'sumo-pp-custom-styles' );
		wp_add_inline_style( 'sumo-pp-custom-styles', get_option( SUMO_PP_PLUGIN_PREFIX . 'custom_css' ) );
	}

	public function render_deposit_form() {
		global $product, $post;

		if ( ! is_callable( array( $product, 'is_type' ) ) ) {
			if ( ! isset( $post->ID ) ) {
				return;
			}

			$product = wc_get_product( $post->ID );

			if ( ! $product ) {
				return;
			}
		}

		if ( $product->is_type( array( 'variable', 'variation' ) ) ) {
			return;
		}

		if ( $this->is_payment_product( $product ) ) {
			$this->get_deposit_form( null, false, '', true );
		}
	}

	public function check_product_is_in_stock( $is_in_stock, $product = '' ) {
		if ( 'set-as-out-of-stock' === _sumo_pp()->plan->when_plans_are_hidden() ) {
			remove_filter( 'sumopaymentplans_get_product_props', array( _sumo_pp()->plan, 'contains_valid_plan' ), 99 );
			$this->get_props( $product );
			add_filter( 'sumopaymentplans_get_product_props', array( _sumo_pp()->plan, 'contains_valid_plan' ), 99 );

			if ( 'payment-plans' === $this->get_prop( 'payment_type' ) ) {
				$selected_plans  = $this->get_prop( 'selected_plans' );
				$payment_ended   = array();
				$payment_started = array();

				foreach ( $selected_plans as $col => $plans ) {
					foreach ( $plans as $row => $plan_id ) {
						if ( _sumo_pp()->plan->when_plans_are_hidden() ) {
							$payment_started[ $plan_id ] = _sumo_pp()->plan->get_prop( 'payment_started', array( 'props' => $plan_id ) ) ? 'yes' : null;

							if ( is_null( $payment_started[ $plan_id ] ) ) {
								break;
							}
						} else if ( _sumo_pp()->plan->when_prev_ins_pay_with_current_ins() ) {
							$payment_ended[ $plan_id ] = _sumo_pp()->plan->get_prop( 'payment_ended', array( 'props' => $plan_id ) ) ? 'yes' : null;

							if ( is_null( $payment_ended[ $plan_id ] ) ) {
								break;
							}
						}
					}
				}

				if ( ! empty( $payment_started ) && ! in_array( null, $payment_started ) && in_array( 'yes', $payment_started ) ) {
					return false;
				} else if ( ! empty( $payment_ended ) && ! in_array( null, $payment_ended ) && in_array( 'yes', $payment_ended ) ) {
					return false;
				}
			}
		}

		if ( is_product() ) {
			if ( empty( self::$props[ 'product_id' ] ) ) {
				$this->get_props( $product );
			}

			if (
					'pay-in-deposit' === $this->get_prop( 'payment_type' ) &&
					'before' === $this->get_prop( 'pay_balance_type' ) &&
					'out-of-stock' === $this->get_prop( 'set_expired_deposit_payment_as' )
			) {
				if ( isset( self::$props[ 'booking_payment_end_date' ] ) ) {
					//may be it is SUMO Booking product
					return true;
				} else if ( _sumo_pp_get_timestamp( $this->get_prop( 'pay_balance_before' ), 0, true ) <= _sumo_pp_get_timestamp( 0, 0, true ) ) {
					return false;
				}
			}
		}

		return $is_in_stock;
	}

	public function maybe_redirect_after_added_to_cart() {
		if ( ! isset( $_GET[ 'add-to-cart' ] ) || ! isset( $_GET[ '_sumo_pp_payment_type' ] ) ) {
			return;
		}

		$added_to_cart_plan_redirect_to = get_option( SUMO_PP_PLUGIN_PREFIX . 'after_hyperlink_clicked_redirect_to', 'product' );

		if ( 'product' !== $added_to_cart_plan_redirect_to && 'payment-plans' === $_GET[ '_sumo_pp_payment_type' ] ) {
			wp_safe_redirect( wc_get_page_permalink( $added_to_cart_plan_redirect_to ) );
			exit;
		}
	}
}
