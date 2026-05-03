<?php

/**
 * WooCommerce B2B Functions
 *
 * @version 4.0.4
 */

defined( 'ABSPATH' ) || exit;

// Check if WooCommerce B2B plugins are installed and enabled (not pluggable)
function wcb2b_is_active( $plugin ) {
    if ( 'sales-agents' == $plugin ) {
        return in_array( 'woocommerce-b2b-sales-agents/woocommerce-b2b-sales-agents.php', get_option( 'active_plugins' ) );
    } elseif ( 'warehouses' == $plugin ) {
        return in_array( 'woocommerce-b2b-warehouses/woocommerce-b2b-warehouses.php', get_option( 'active_plugins' ) );
    }
    return false;
}

// Verify if user has specific roles
function wcb2b_has_role( $user_id, $role ) {
    $default = get_option( 'wcb2b_has_role_' . $role, array( $role ) );
    if ( ! is_array( $default ) ) {
        $default = array($role);
    }
    $roles = ( new WP_User( $user_id ) )->roles;

    return ! empty( array_intersect( $default, $roles ) );
}

// Retrieve user group
function wcb2b_get_customer_group( $uid = false, $original = false ) {
    // Default GUEST group
    $group_id = get_option( 'wcb2b_guest_group' );
    // Requested specific user, otherwise current user
    $user_id = get_current_user_id();
    if ( false !== $uid ) {
        $user_id = $uid;
    }
    if ( 0 !== $user_id ) {
        // Check user role
        if ( ! wcb2b_has_role( $user_id, 'customer' ) ) {
            $group_id = false; // Not appliable
        } else {
            // Get user group if any
            if ( $user_group_id = get_the_author_meta( 'wcb2b_group', $user_id ) ) {
                // Check if group exists or is deleted precedently
                if ( false !== get_post_status( $user_group_id ) ) {
                    $group_id = $user_group_id;
                }
            }
        }
    }
    if ( $original ) {
        return $group_id;
    }
    return apply_filters( 'wcb2b_get_customer_group', $group_id, $user_id );
}

// List of WooCommerce pages
function wcb2b_get_wc_pages() {
    return array_filter( array(
        get_option( 'woocommerce_shop_page_id' ),
        get_option( 'woocommerce_cart_page_id' ),
        get_option( 'woocommerce_checkout_page_id' ),
        get_option( 'woocommerce_pay_page_id' ),
        get_option( 'woocommerce_thanks_page_id' ),
        get_option( 'woocommerce_edit_address_page_id' ),
        get_option( 'woocommerce_view_order_page_id' ),
        get_option( 'woocommerce_terms_page_id' )
    ) );
}

// Build product shipping table
function wcb2b_shipping_table_content() {
    $zones = WC_Shipping_Zones::get_zones();
    usort( $zones, function( $a, $b ) {
        return strcmp( $a['zone_name'], $b['zone_name'] );
    } );

    if ( $customer_group_id = wcb2b_get_customer_group() ) {
        if ( ! $group_shippings = get_post_meta( $customer_group_id, 'wcb2b_group_shippings', true ) ) {
            $group_shippings = array();
        }

        $shippings = array();
        foreach ( $zones as $zone_id => $zone ) {
            $shippings[$zone_id] = array(
                'name' => $zone['zone_name'],
                'methods' => array()
            );
            $shipping_methods = $zone['shipping_methods'];
            foreach ( $shipping_methods as $method ) {
                $key = sprintf( '%s:%s', $method->id, $method->instance_id);
                if ( ! in_array( $key, $group_shippings ) ) {
                    $instance = $method->instance_settings;
                    $cost = $instance['cost'] ?? $instance['min_amount'];

                    $shippings[$zone_id]['methods'][] = array(
                        'title' => $instance['title'],
                        'cost' => wc_price( $cost )
                    );
                }
            }
        }

        ob_start();
        wc_get_template( 'single-product/tabs/shippings.php', array(
            'shippings' => $shippings
        ), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
        $html = ob_get_contents();
        ob_end_clean();
        echo $html;
    }
}

// Build product purchase history table
function wcb2b_purchase_history_table_content() {
    global $product;

    $purchases = array();
    $orders = wc_get_orders( array(
        'limit'       => -1,
        'customer_id' => get_current_user_id(),
        'type'        => 'shop_order',
        'status'      => 'wc-completed'
    ) );
    foreach ( $orders as $order ) {
        foreach ( $order->get_items() as $item ) {
            if ( $product->get_ID() == $item['product_id'] ) {
                $purchases[] = array(
                    'id'       => $order->get_ID(),
                    'url'      => esc_url( $order->get_view_order_url() ),
                    'date'     => date_i18n( get_option( 'date_format' ), strtotime( $order->get_date_completed() ) ),
                    'quantity' => $item->get_quantity()
                );
            }
        }
    }

    ob_start();
    wc_get_template( 'single-product/tabs/purchase-history.php', array(
        'purchases' => $purchases
    ), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
    $html = ob_get_contents();
    ob_end_clean();
    echo $html;
}
