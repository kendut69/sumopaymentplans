<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Abstract Payment
 * 
 * @abstract SUMO_PP_Abstract_Payment
 */
abstract class SUMO_PP_Abstract_Payment {

    public $id        = 0;
    public $payment   = false;
    public $status    = '';
    public $balance_payable_order;
    public $post_type = 'sumo_pp_payments';
    public $prefix    = SUMO_PP_PLUGIN_PREFIX;

    /**
     * Populate Payment.
     */
    protected function populate( $payment ) {
        if ( ! is_null( $payment ) ) {
            if ( is_numeric( $payment ) ) {
                $this->id = absint( $payment );
                $this->get_payment( $this->id );
            } elseif ( $payment instanceof SUMO_PP_Payment ) {
                $this->id = absint( $payment->id );
                $this->get_payment( $this->id );
            } elseif ( isset( $payment->ID ) ) {
                $this->id = absint( $payment->ID );
                $this->get_payment( $this->id );
            }
        }
    }

    public function get_payment( $id ) {
        if ( ! $id ) {
            return false;
        }

        if ( get_post_type( $id ) === $this->post_type ) {
            $this->payment = get_post( $id );
            $this->status  = $this->get_status();
            return $this;
        }
        return false;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_status( $prefix = false ) {
        return $prefix ? $this->payment->post_status : ( substr( $this->payment->post_status, 0, 9 ) === $this->prefix ? substr( $this->payment->post_status, 9 ) : $this->payment->post_status );
    }

    public function get_status_label() {
        return _sumo_pp_get_payment_status_name( $this->payment->post_status );
    }

    public function get_payment_number() {
        return $this->get_prop( 'payment_number' );
    }

    public function get_customer_id() {
        return absint( $this->get_prop( 'customer_id' ) );
    }

    public function get_customer_email() {
        return $this->get_prop( 'customer_email' );
    }

    public function get_product_id() {
        return absint( $this->get_prop( 'product_id' ) );
    }

    public function get_product_qty() {
        return absint( $this->get_prop( 'product_qty' ) ) ? absint( $this->get_prop( 'product_qty' ) ) : 1;
    }

    public function get_product_price() {
        return floatval( $this->get_prop( 'product_price' ) );
    }

    public function get_product_type() {
        return $this->get_prop( 'product_type' );
    }

    public function get_plan_price_type() {
        return $this->get_prop( 'plan_price_type' );
    }

    public function get_product_amount() {
        return $this->get_product_price() * $this->get_product_qty();
    }

    public function get_down_payment( $calc_qty = true ) {
        $down_payment = $this->is_version( '<', '3.7' ) ? $this->get_prop( 'deposited_amount' ) : $this->get_prop( 'down_payment' );

        if ( $calc_qty ) {
            return floatval( $down_payment ) * $this->get_product_qty();
        }
        return floatval( $down_payment );
    }

    public function get_payment_mode() {
        $payment_mode = $this->get_prop( 'payment_mode' );

        return empty( $payment_mode ) ? 'manual' : $payment_mode;
    }

    public function get_payment_method() {
        return $this->get_prop( 'payment_method' );
    }

    public function get_payment_method_title() {
        return $this->get_prop( 'payment_method_title' );
    }

    public function get_initial_payment_order_id() {
        return absint( $this->get_prop( 'initial_payment_order_id' ) );
    }

    public function get_balance_paid_orders() {
        return is_array( $this->get_prop( 'balance_paid_orders' ) ) ? $this->get_prop( 'balance_paid_orders' ) : array();
    }

    public function get_balance_payable_order_props() {
        return is_array( $this->get_prop( 'balance_payable_order_props' ) ) ? $this->get_prop( 'balance_payable_order_props' ) : array();
    }

    public function get_balance_payable_order_id( $created_via = 'default' ) {
        $balance_payable_order = $this->get_balance_payable_order_props();

        if ( ! empty( $balance_payable_order ) ) {
            foreach ( $balance_payable_order as $id => $data ) {
                if ( ! empty( $data[ 'created_via' ] ) && $created_via === $data[ 'created_via' ] ) {
                    return absint( $id );
                }
            }
        } else if ( 'default' === $created_via ) {
            return absint( $this->get_prop( 'balance_payable_order_id' ) );
        }
        return false;
    }

    public function charge_shipping_during() {
        $charge_shipping = $this->get_prop( 'charge_shipping_during' );

        if ( '' === $charge_shipping || ( '' === $charge_shipping && 'order' === $this->get_product_type() ) ) {
            return 'initial-payment';
        }

        $initial_payment_order = _sumo_pp_maybe_get_order_instance( $this->get_initial_payment_order_id() );
        if ( ! $initial_payment_order ) {
            return 'initial-payment';
        }

        return $charge_shipping;
    }

    public function get_formatted_product_name( $args = array() ) {
        $args = wp_parse_args( $args, array(
            'qty'      => true,
            'esc_html' => false,
            'url'      => true,
            'page'     => 'frontend',
                ) );

        $args = apply_filters( 'sumopaymentplans_get_formatted_product_name_args', $args, $this );

        if ( 'order' === $this->get_product_type() ) {
            $product_title       = get_option( $this->prefix . 'order_payment_plan_product_label' ) ? get_option( $this->prefix . 'order_payment_plan_product_label' ) : get_option( $this->prefix . 'order_payment_plan_label' );
            $item_title          = array();
            $item_title_with_url = array();

            foreach ( $this->get_prop( 'order_items' ) as $item_id => $item ) {
                $product = wc_get_product( $item_id );
                if ( ! $product ) {
                    continue;
                }

                $current_product = $product;
                if ( $product->get_parent_id() ) {
                    $parent_product = wc_get_product( $product->get_parent_id() );
                    if ( ! $parent_product ) {
                        continue;
                    }
                } else {
                    $parent_product = $product;
                }

                if ( 'admin' === $args[ 'page' ] ) {
                    $identifier = $product->get_sku() ? $product->get_sku() : '#' . $product->get_id();
                    $item_name  = sprintf( '%2$s (%1$s)', $identifier, $product->get_name() );
                } else {
                    $item_name = $product->get_name();
                }

                $meta_data = $product->is_type( 'variation' ) ? wc_get_formatted_variation( $product, true, true, true ) : '';
                if ( ! empty( $meta_data ) ) {
                    if ( $args[ 'esc_html' ] ) {
                        $item_name .= ' - ' . $meta_data;
                    } else {
                        $item_name .= ' - <span class="description">' . $meta_data . '</span>';
                    }
                }

                $maybe_with_qty = $args[ 'qty' ] ? "&nbsp;x{$item[ 'qty' ]}" : '';
                $item_title[]   = $item_name . $maybe_with_qty;

                if ( 'admin' === $args[ 'page' ] ) {
                    $item_title_with_url[] = '<a href="' . esc_url( get_edit_post_link( $parent_product->get_id() ) ) . '">' . wp_kses_post( $item_name ) . '</a>' . $maybe_with_qty;
                } else {
                    $item_title_with_url[] = '<a href="' . esc_url( get_permalink( $current_product->get_id() ) ) . '">' . wp_kses_post( $item_name ) . '</a>' . $maybe_with_qty;
                }
            }

            if ( $args[ 'esc_html' ] ) {
                $product_title .= ' (' . implode( ', ', $item_title ) . ')';
            } elseif ( $args[ 'url' ] ) {
                $product_title .= '<br>(' . implode( ',<br>', $item_title_with_url ) . ')';
            } else {
                $product_title .= '<br>(' . implode( ',<br>', $item_title ) . ')';
            }
        } else {
            $product = wc_get_product( $this->get_product_id() );
            if ( ! $product ) {
                if ( 'admin' === $args[ 'page' ] ) {
                    return sprintf( __( 'Product ID:<strong>(#%s)</strong> Deleted', 'sumopaymentplans' ), $this->get_product_id() );      
                }

                return 'N/A';
            }

            $current_product = $product;
            if ( $product->get_parent_id() ) {
                $parent_product = wc_get_product( $product->get_parent_id() );
                if ( ! $parent_product ) {
                    return 'N/A';
                }
            } else {
                $parent_product = $product;
            }

            if ( 'admin' === $args[ 'page' ] ) {
                $identifier    = $current_product->get_sku() ? $current_product->get_sku() : '#' . $current_product->get_id();
                $product_title = sprintf( '%2$s (%1$s)', $identifier, $current_product->get_name() );
            } else {
                $product_title = $current_product->get_name();
            }

            $meta_data = $current_product->is_type( 'variation' ) ? wc_get_formatted_variation( $current_product, true, true, true ) : '';
            if ( ! empty( $meta_data ) ) {
                if ( $args[ 'esc_html' ] ) {
                    $product_title .= ' - ' . $meta_data;
                } else {
                    $product_title .= ' - <span class="description">' . $meta_data . '</span>';
                }
            }

            if ( $args[ 'url' ] && ! $args[ 'esc_html' ] ) {
                if ( 'admin' === $args[ 'page' ] ) {
                    $product_title = '<a href="' . esc_url( get_edit_post_link( $parent_product->get_id() ) ) . '">' . wp_kses_post( $product_title ) . '</a>';
                } else {
                    $product_title = '<a href="' . esc_url( get_permalink( $current_product->get_id() ) ) . '">' . wp_kses_post( $product_title ) . '</a>';
                }
            }

            $product_title .= ( $args[ 'qty' ] ? "&nbsp;x{$this->get_product_qty()}" : '' );
        }

        return $product_title;
    }

    public function get_payment_type( $view = false ) {
        if ( $view ) {
            return ucfirst( str_replace( '-', ' ', $this->get_prop( 'payment_type' ) ) );
        }
        return $this->get_prop( 'payment_type' );
    }

    public function get_plan( $prop = '' ) {
        $plan_post = get_post( $this->get_prop( 'plan_id' ) );

        if ( '' !== $prop ) {
            if ( 'name' === $prop ) {
                return isset( $plan_post->post_title ) ? $plan_post->post_title : '';
            }

            return isset( $plan_post->$prop ) ? $plan_post->$prop : '';
        }

        return $plan_post;
    }

    public function get_pay_balance_type() {
        return $this->get_prop( 'pay_balance_type' );
    }

    public function get_pay_balance_before() {
        return $this->get_prop( 'pay_balance_before' );
    }

    public function get_pay_balance_after() {
        if ( $this->is_version( '<', '1.4' ) ) {
            $pay_balance_after = absint( $this->get_prop( 'balance_payment_due' ) ); //in days
        } else {
            $pay_balance_after = absint( $this->get_prop( 'pay_balance_after' ) ); //in days
        }
        return $pay_balance_after;
    }

    public function get_view_endpoint_url() {
        global $post;

        $endpoint = _sumo_pp()->query->get_query_var( 'sumo-pp-view-payment' );
        if ( _sumo_pp_is_my_payments_page() ) {
            $payment_endpoint = wc_get_endpoint_url( $endpoint, $this->id,  ! empty( $post->ID ) ? get_permalink( $post->ID ) : get_home_url() );
        } else {
            $payment_endpoint = wc_get_endpoint_url( $endpoint, $this->id, wc_get_page_permalink( 'myaccount' ) );
        }
        return $payment_endpoint;
    }

    public function get_total_installments() {
        $total_installments = 0;

        if ( 'payment-plans' === $this->get_payment_type() ) {
            $total_installments = count( array_filter( array_map( function( $schedule ) {
                                return isset( $schedule[ 'scheduled_payment' ] ) ? $schedule : null;
                            }, is_array( $this->get_prop( 'payment_schedules' ) ) ? $this->get_prop( 'payment_schedules' ) : array() ) ) );
        } else if ( 'pay-in-deposit' === $this->get_payment_type() ) {
            if ( 0 === $this->get_next_installment_count() ) {
                $total_installments = 1;
            }
        }
        return $total_installments;
    }

    public function get_next_installment_count() {
        return count( $this->get_balance_paid_orders() );
    }

    public function get_next_of_next_installment_count() {
        return 1 + $this->get_next_installment_count();
    }

    public function email_sending() {
        return '1' === $this->get_prop( 'email_sending_flag' );
    }

    public function exists() {
        if ( $this->payment && in_array( $this->payment->post_status, array_keys( _sumo_pp_get_payment_statuses() ) ) ) {
            return true;
        }
        return false;
    }

    public function has_status( $status ) {
        if ( is_array( $status ) ) {
            return in_array( $this->get_status(), $status ) || in_array( $this->get_status( true ), $status );
        }
        return $status === $this->get_status() || $status === $this->get_status( true );
    }

    public function is_version( $comparison_opr, $version ) {
        return version_compare( $this->get_prop( 'version' ), $version, $comparison_opr );
    }

    public function is_synced() {
        return 'enabled' === $this->get_prop( 'sync' );
    }

    public function has_next_installment() {
        return $this->get_remaining_installments() > 0 ? true : false;
    }

    public function payment_mode_switched() {
        return 'yes' === $this->get_prop( 'payment_mode_switched' );
    }

    public function awaiting_initial_payment() {
        return ( $this->is_synced() && $this->get_down_payment() <= 0 && $this->has_status( 'pending' ) );
    }

    public function balance_payable_order_exists( $created_via = 'default' ) {
        $this->balance_payable_order = _sumo_pp_maybe_get_order_instance( $this->get_balance_payable_order_id( $created_via ) );
        return $this->balance_payable_order && ! _sumo_pp_is_order_paid( $this->balance_payable_order );
    }

    public function is_expected_payment_dates_modified() {
        $modified_dates = $this->get_prop( 'modified_expected_payment_dates' );
        return is_array( $modified_dates ) && ! empty( $modified_dates ) ? true : false;
    }

    public function set_payment_serial_number() {
        $custom_prefix  = esc_attr( get_option( $this->prefix . 'payment_number_prefix', '' ) );
        $last_serial_no = absint( get_option( $this->prefix . 'payment_serial_number', '1' ) );
        $new_serial_no  = $last_serial_no ? 1 + $last_serial_no : 1;

        update_option( $this->prefix . 'payment_serial_number', $new_serial_no );
        return $custom_prefix . $new_serial_no;
    }

    public function set_payment_mode( $mode, $add_note = true ) {
        $this->update_prop( 'payment_mode_switched', 'no' );

        if ( $mode === $this->get_payment_mode() ) {
            return false;
        }

        if ( ! in_array( $mode, array( 'auto', 'manual' ) ) ) {
            return false;
        }

        $switched = empty( $this->get_prop( 'payment_mode' ) ) ? 'no' : 'yes';
        $this->update_prop( 'payment_mode', $mode );
        $this->update_prop( 'payment_mode_switched', $switched );

        if ( $add_note ) {
            if ( 'yes' === $switched ) {
                /* translators: 1: payment mode */
                $this->add_payment_note( sprintf( __( 'Payment mode switched to %s.', 'sumopaymentplans' ), ( 'auto' === $mode ? 'Automatic' : 'Manual' ) ), 'success', __( 'Payment Mode Switched', 'sumopaymentplans' ) );
            } else {
                /* translators: 1: payment mode */
                $this->add_payment_note( sprintf( __( 'Payment mode set to %s.', 'sumopaymentplans' ), ( 'auto' === $mode ? 'Automatic' : 'Manual' ) ), 'success', __( 'Payment Mode Initialized', 'sumopaymentplans' ) );
            }
        }
        return true;
    }

    public function set_email_sending_flag() {
        $this->add_prop( 'email_sending_flag', '1' );
    }

    public function set_email_sent_flag() {
        $this->delete_prop( 'email_sending_flag' );
    }

    public function get_prop( $context = '' ) {
        return get_post_meta( $this->id, "_{$context}", true );
    }

    public function add_prop( $context, $value ) {
        return add_post_meta( $this->id, "_{$context}", $value );
    }

    public function update_prop( $context, $value ) {
        return update_post_meta( $this->id, "_{$context}", $value );
    }

    public function delete_prop( $context ) {
        return delete_post_meta( $this->id, "_{$context}" );
    }
}
