<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Manage payment order item
 * 
 * @class SUMO_PP_Order_Item_Manager
 * @package Class
 */
class SUMO_PP_Order_Item_Manager {

    /**
     * The single instance of the class.
     */
    protected static $instance = null;

    /**
     * Create instance for SUMO_PP_Order_Item_Manager.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Init SUMO_PP_Order_Item_Manager.
     */
    public function init() {
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );
        add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'render_order_item_balance_payable' ), 99, 3 );
        add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'render_balance_payable_amount' ), 99 );
        add_action( 'woocommerce_before_save_order_item', array( $this, 'calculate_deposit_by_item' ), 20 );
        add_action( 'woocommerce_saved_order_items', array( $this, 'calculate_deposit_by_order' ), 20, 2 );
        add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_order_itemmeta' ), 20 );
    }

    public function get_order_item_balance_payable( $order, $item = null ) {
        $remaining_payable_amount = null;

        if ( _sumo_pp_is_child_order( $order ) ) {
            $remaining_payable_amount = $order->get_meta( '_sumo_pp_remaining_payable_amount', true );

            //BKWD CMPT < 5.1
            if ( ! is_numeric( $remaining_payable_amount ) ) {
                $payment = _sumo_pp_get_payment( _sumo_pp_get_payment_id_from_order( $order ) );

                if ( $payment ) {
                    if ( 'my_account' === $order->get_meta( '_sumo_pp_created_via', true ) ) {
                        $next_of_next_installment_count = 1 + absint( $order->get_meta( '_sumo_pp_next_installment_count', true ) );
                    } else {
                        $next_of_next_installment_count = $payment->get_next_of_next_installment_count();
                    }

                    $remaining_payable_amount = $payment->get_remaining_payable_amount( $next_of_next_installment_count );
                }
            }
        } else {
            if ( is_null( $item ) ) {
                $payment_data = _sumo_pp_get_payment_data_from_order( $order );
            } else {
                $payment_data = ! empty( $item[ '_sumo_pp_payment_data' ] ) ? array( $item[ '_sumo_pp_payment_data' ] ) : array();
            }

            if ( ! empty( $payment_data ) ) {
                if ( isset( $payment_data[ 'remaining_payable_amount' ] ) ) {
                    $remaining_payable_amount = $payment_data[ 'remaining_payable_amount' ];
                } else {
                    $remaining_payable_amount = 0;
                    foreach ( $payment_data as $item_id => $item ) {
                        if ( isset( $item[ 'remaining_payable_amount' ] ) ) {
                            $remaining_payable_amount += is_numeric( $item[ 'remaining_payable_amount' ] ) ? $item[ 'remaining_payable_amount' ] : 0;
                        }
                    }
                }
            } elseif ( ! _sumo_pp_is_orderpp_created_by_multiple( $order ) ) {
                //BKWD CMPT < 3.1
                if ( ! empty( $item[ 'product_id' ] ) ) {
                    $payment = _sumo_pp_get_payment( _sumo_pp_get_payment_id_from_order( $order, $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ) );
                    if ( $payment ) {
                        $remaining_payable_amount = $payment->get_remaining_payable_amount();
                    }
                } else {
                    $payment = _sumo_pp_get_payment( _sumo_pp_get_payment_id_from_order( $order ) );
                    if ( $payment ) {
                        $remaining_payable_amount = $payment->get_remaining_payable_amount();
                    }
                }
            }
        }

        return $remaining_payable_amount;
    }

    public function add_order_item_meta( $item, $cart_item_key, $cart_item, $order ) {
        if ( empty( $cart_item[ 'sumopaymentplans' ] ) ) {
            return;
        }

        $shipping_total                                            = WC()->cart->needs_shipping() && 1 === count( WC()->cart->cart_contents ) ? WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() : 0;
        $cart_item[ 'sumopaymentplans' ][ 'total_payable_amount' ] += $shipping_total;

        $this->add_order_item_payment_meta( $item, $cart_item[ 'sumopaymentplans' ] );
    }

    public function render_order_item_balance_payable( $subtotal, $item, $order ) {
        $order = _sumo_pp_maybe_get_order_instance( $order );

        if ( _sumo_pp_is_orderpp_created_by_multiple( $order ) ) {
            return $subtotal;
        }

        $remaining_payable_amount = $this->get_order_item_balance_payable( $order, $item );
        if ( ! is_numeric( $remaining_payable_amount ) ) {
            return $subtotal;
        }

        $shipping_amount = _sumo_pp_maybe_get_shipping_amount_for_order( $order );

        /* translators: 1: remaining payable amount */
        $subtotal .= sprintf( __( '<p><small style="color:#777;">Balance <strong>%s</strong> payable</small></p>', 'sumopaymentplans' ), wc_price( $remaining_payable_amount + $shipping_amount[ 'for_balance_payable' ], array( 'currency' => $order->get_currency() ) ) );
        return $subtotal;
    }

    public function render_balance_payable_amount( $order_id ) {
        $order = _sumo_pp_maybe_get_order_instance( $order_id );
        if ( ! $order ) {
            return;
        }

        $remaining_payable_amount = $this->get_order_item_balance_payable( $order );
        if ( ! is_numeric( $remaining_payable_amount ) ) {
            return;
        }

        $shipping_amount = _sumo_pp_maybe_get_shipping_amount_for_order( $order );

        echo '<tr>'
        . '<td class="label">' . esc_html__( 'Balance Payable', 'sumopaymentplans' ) . ':</td>'
        . '<td width="1%"></td>'
        . '<td class="sumo_pp_balance_payable">' . wp_kses_post( apply_filters( 'sumopaymentplans_order_balance_payable_to_display', wc_price( $remaining_payable_amount + $shipping_amount[ 'for_balance_payable' ], array( 'currency' => $order->get_currency() ) ), $order ) ) . '</td>'
        . '</tr>';

        if ( _sumo_pp_is_orderpp_created_by_multiple( $order ) ) {
            $payment_data = $order->get_meta( '_sumo_pp_payment_data', true );

            echo '<tr>'
            . '<td class="label">' . esc_html__( 'Total payable', 'sumopaymentplans' ) . ':</td>'
            . '<td width="1%"></td>'
            . '<td class="sumo_pp_total_payable">' . wp_kses_post( wc_price( $payment_data[ 'total_payable_amount' ] + $shipping_amount[ 'for_total_payable' ], array( 'currency' => $order->get_currency() ) ) ) . '</td>'
            . '</tr>';

            if ( 'payment-plans' === $payment_data[ 'payment_type' ] ) {
                echo '<tr>'
                . '<td class="label">' . esc_html__( 'Payment Plan', 'sumopaymentplans' ) . ':</td>'
                . '<td width="1%"></td>'
                . '<td class="sumo_pp_plan_title"><strong>' . esc_html( get_the_title( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] ) ) . '</strong></td>'
                . '</tr>';
            }

            if ( isset( $payment_data[ 'remaining_installments' ] ) && $payment_data[ 'remaining_installments' ] > 1 ) {
                echo '<tr>'
                . '<td class="label">' . esc_html__( 'Next installment amount', 'sumopaymentplans' ) . ':</td>'
                . '<td width="1%"></td>'
                . '<td class="sumo_pp_next_ins_amount">' . wp_kses_post( wc_price( $payment_data[ 'next_installment_amount' ], array( 'currency' => $order->get_currency() ) ) ) . '</td>'
                . '</tr>';
                echo '<tr>'
                . '<td class="label">' . esc_html__( 'Next Payment Date', 'sumopaymentplans' ) . ':</td>'
                . '<td width="1%"></td>'
                . '<td class="sumo_pp_next_payment_date"><strong>' . esc_html( _sumo_pp_get_date_to_display( $payment_data[ 'next_payment_date' ], 'admin' ) ) . '</strong></td>'
                . '</tr>';
            }
        }
    }

    public function calculate_deposit_by_item( $item ) {
        $requested = $_REQUEST;

        if ( empty( $requested[ 'items' ] ) ) {
            return;
        }

        parse_str( $requested[ 'items' ], $items );

        if ( empty( $items[ '_sumo_pp_selected_plan' ] ) && empty( $items[ '_sumo_pp_deposit_amount' ] ) ) {
            return;
        }

        $product_id        = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
        $payment_data_args = array(
            'product_props' => $product_id,
            'qty'           => $item->get_quantity(),
        );

        if ( isset( $items[ '_sumo_pp_selected_plan' ][ $product_id ] ) ) {
            if ( empty( $items[ '_sumo_pp_selected_plan' ][ $product_id ] ) ) {
                return;
            }

            $payment_data_args[ 'plan_props' ] = $items[ '_sumo_pp_selected_plan' ][ $product_id ];
        } else if ( isset( $items[ '_sumo_pp_deposit_amount' ][ $product_id ] ) ) {
            if ( ! is_numeric( $items[ '_sumo_pp_deposit_amount' ][ $product_id ] ) ) {
                return;
            }

            $payment_data_args[ 'deposited_amount' ] = floatval( $items[ '_sumo_pp_deposit_amount' ][ $product_id ] );
        }

        $payment_data_args[ 'base_price' ] = $item->get_quantity() ? floatval( $item->get_total() ) / $item->get_quantity() : 0;
        $payment_data                      = SUMO_PP_Data_Manager::get_payment_data( $payment_data_args );
        $product                           = wc_get_product( $product_id );

        if (
                ! empty( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) &&
                _sumo_pp()->product->is_payment_product( $payment_data[ 'payment_product_props' ] )
        ) {
            $item->set_total( wc_get_price_excluding_tax( $product, array( 'qty' => $item->get_quantity(), 'price' => $payment_data[ 'down_payment' ] ) ) );
            $item->set_subtotal( wc_get_price_excluding_tax( $product, array( 'qty' => $item->get_quantity(), 'price' => $payment_data[ 'down_payment' ] ) ) );
            $this->add_order_item_payment_meta( $item, $payment_data );
        }
    }

    public function calculate_deposit_by_order( $order_id, $items ) {
        $requested = $_REQUEST;

        if ( empty( $requested[ 'items' ] ) ) {
            return;
        }

        parse_str( $requested[ 'items' ], $items );

        if ( empty( $items[ '_sumo_pp_product_type' ] ) || 'order' !== $items[ '_sumo_pp_product_type' ] ) {
            return;
        }

        $payment_order     = _sumo_pp_maybe_get_order_instance( $order_id );
        $payment_data_args = array(
            'order_total' => $payment_order->get_total(),
        );

        if ( isset( $items[ '_sumo_pp_selected_plan' ] ) ) {
            if ( empty( $items[ '_sumo_pp_selected_plan' ] ) ) {
                return;
            }

            $payment_data_args[ 'plan_props' ] = $items[ '_sumo_pp_selected_plan' ];
        } else if ( isset( $items[ '_sumo_pp_deposit_amount' ] ) ) {
            if ( ! is_numeric( $items[ '_sumo_pp_deposit_amount' ] ) ) {
                return;
            }

            $payment_data_args[ 'down_payment' ] = floatval( $items[ '_sumo_pp_deposit_amount' ] );
        }

        $order_item_data = array();
        foreach ( $payment_order->get_items() as $item ) {
            $product = $item->get_product();

            if ( ! $product ) {
                continue;
            }

            $order_item_data[]                                        = array( 'product' => $product, 'order_item' => new WC_Order_Item_Product( $item->get_id() ) );
            $payment_data_args[ 'order_items' ][ $product->get_id() ] = array(
                'price'             => $product->get_price(),
                'qty'               => $item->get_quantity(),
                'line_subtotal'     => $item->get_subtotal(),
                'line_subtotal_tax' => $item->get_subtotal_tax(),
                'line_total'        => $item->get_total(),
                'line_tax'          => $item->get_total_tax(),
            );
        }

        if ( empty( $order_item_data ) ) {
            return;
        }

        _sumo_pp()->orderpp->set_session( $payment_data_args );

        $session_props = _sumo_pp()->orderpp->get_session_props( false );

        if ( 'single' === SUMO_PP_Order_PaymentPlan::$display_mode ) {
            $item_data = current( $order_item_data );
            $payment_order->remove_order_items( 'line_item' );

            _sumo_pp()->orderpp->add_items_to_order( $payment_order, $item_data[ 'product' ], array(
                'session_props'   => $session_props,
                'line_total'      => ( is_numeric( $session_props[ 'down_payment' ] ) ? $session_props[ 'down_payment' ] : 0 ),
                'order_item_data' => $order_item_data,
            ) );
        } else {
            $payment_order->update_meta_data( 'is_sumo_pp_orderpp', 'yes' );
            $payment_order->update_meta_data( '_sumo_pp_payment_data', $session_props );
            $payment_order->save();
        }
    }

    public function hide_order_itemmeta( $hidden_metas ) {
        $hidden_metas[] = '_sumo_pp_payment_id';
        return $hidden_metas;
    }

    public function add_order_item_payment_meta( $item, $payment_data ) {
        $payment_type = null;
        if ( ! empty( $payment_data[ 'payment_type' ] ) ) {
            $payment_type = $payment_data[ 'payment_type' ];
        } else if ( ! empty( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) ) {
            $payment_type = $payment_data[ 'payment_product_props' ][ 'payment_type' ];
        }

        if ( 'payment-plans' === $payment_type ) {
            $meta_key = __( 'Payment Plan', 'sumopaymentplans' );

            if ( is_numeric( $item ) ) {
                wc_delete_order_item_meta( $item, $meta_key );
                wc_add_order_item_meta( $item, $meta_key, get_the_title( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] ) );
            } else {
                $item->delete_meta_data( $meta_key );
                $item->add_meta_data( $meta_key, get_the_title( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] ) );
            }
        }

        if ( ! empty( $payment_data[ 'total_payable_amount' ] ) ) {
            $meta_key = __( 'Total payable', 'sumopaymentplans' );

            if ( isset( $payment_data[ 'discount_amount' ] ) && is_numeric( $payment_data[ 'discount_amount' ] ) ) {
                $discount_tax         = isset( $payment_data[ 'discount_tax' ] ) && is_numeric( $payment_data[ 'discount_tax' ] ) ? $payment_data[ 'discount_tax' ] : 0;
                $total_payable_amount = wc_price( $payment_data[ 'total_payable_amount' ] - $payment_data[ 'discount_amount' ] - $discount_tax );
            } else {
                $total_payable_amount = wc_price( $payment_data[ 'total_payable_amount' ] );
            }

            if ( is_numeric( $item ) ) {
                wc_delete_order_item_meta( $item, $meta_key );
                wc_add_order_item_meta( $item, $meta_key, $total_payable_amount );
            } else {
                $item->delete_meta_data( $meta_key );
                $item->add_meta_data( $meta_key, $total_payable_amount );
            }
        }

        $next_payment_date = '';
        if ( $payment_data[ 'next_payment_date' ] ) {
            $next_payment_date = _sumo_pp_get_date_to_display( $payment_data[ 'next_payment_date' ] );
        } else if ( 'after_admin_approval' === $payment_data[ 'activate_payment' ] ) {
            $next_payment_date = __( 'After Admin Approval', 'sumopaymentplans' );
        }

        if ( ! empty( $next_payment_date ) ) {
            $meta_key = __( 'Next Payment Date', 'sumopaymentplans' );

            if ( is_numeric( $item ) ) {
                wc_delete_order_item_meta( $item, $meta_key );
                wc_add_order_item_meta( $item, $meta_key, $next_payment_date );
            } else {
                $item->delete_meta_data( $meta_key );
                $item->add_meta_data( $meta_key, $next_payment_date );
            }
        }

        if ( is_numeric( $item ) ) {
            wc_delete_order_item_meta( $item, '_sumo_pp_payment_data' );
            wc_add_order_item_meta( $item, '_sumo_pp_payment_data', $payment_data, true );
        } else {
            $item->delete_meta_data( '_sumo_pp_payment_data' );
            $item->add_meta_data( '_sumo_pp_payment_data', $payment_data, true );
        }
    }

}
