<?php

/**
 * WooCommerce B2B Update Functions
 *
 * @version 3.3.5
 */

defined( 'ABSPATH' ) || exit;

function wcb2b_vatnumber_required_300() {
    return update_option( 'wcb2b_vatnumber_required', 'yes' );
}

function wcb2b_fix_prices_300() {
    global $wpdb;

    $decimals_separator = wc_get_price_decimal_separator();
    $thousand_separator = wc_get_price_thousand_separator();

    $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, '" . $thousand_separator . "', '' ) WHERE meta_key = 'wcb2b_product_group_prices';" );
    $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, '" . $decimals_separator . "', '.' ) WHERE meta_key = 'wcb2b_product_group_prices';" );
    return true;
}

function wcb2b_set_page_visibility_301() {
    if ( $pages = get_pages() ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $pages as $page ) {
            if ( ! metadata_exists( 'post', $page->ID, 'wcb2b_group_visibility' ) ) {
                add_post_meta( $page->ID, 'wcb2b_group_visibility', $ids, true );
            }
        }
    }
    return true;
}

function wcb2b_set_product_cat_visibility_301() {
    if ( $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $terms as $term ) {
            if ( ! metadata_exists( 'term', $term->term_id, 'wcb2b_group_visibility' ) ) {
                add_term_meta( $term->term_id, 'wcb2b_group_visibility', $ids, true );
            }
        }
    }
    return true;
}

function wcb2b_remove_deprecated_options_307() {
    return delete_option( 'wcb2b_enable_quotations' );
}

function wcb2b_set_packages_by_group_310() {
    global $wpdb;
    $results = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = 'wcb2b_step'", OBJECT );
    $groups = wp_list_pluck( wcb2b_get_groups()->posts, 'ID' );
    foreach ( $results as $result ) {
        if ( metadata_exists( 'post', $result->post_id, 'wcb2b_product_group_packages' ) ) {
            continue;
        }
        $values = array_fill_keys( $groups, $result->meta_value );
        $product = wc_get_product( $result->post_id );

        if ( in_array( $product->get_type(), array( 'simple' ) ) ) {
            add_post_meta( $result->post_id, 'wcb2b_product_group_packages', $values, true );
        }
        if ( in_array( $product->get_type(), array( 'variable' ) ) ) {
            $variations = wp_list_pluck( $product->get_available_variations(), 'variation_id' );
            foreach ( $variations as $variation ) {
                if ( metadata_exists( 'post', $variation, 'wcb2b_product_group_packages' ) ) {
                    continue;
                }
                add_post_meta( $variation, 'wcb2b_product_group_packages', $values, true );
            }
        }
        delete_post_meta( $result->post_id, 'wcb2b_step', $result->meta_value );
    }
    return true;
}

function wcb2b_set_minquantity_by_group_310() {
    global $wpdb;
    $results = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = 'wcb2b_min'", OBJECT );
    $groups = wp_list_pluck( wcb2b_get_groups()->posts, 'ID' );
    foreach ( $results as $result ) {
        if ( metadata_exists( 'post', $result->post_id, 'wcb2b_product_group_min' ) ) {
            continue;
        }
        $values = array_fill_keys( $groups, $result->meta_value );
        $product = wc_get_product( $result->post_id );

        if ( in_array( $product->get_type(), array( 'simple' ) ) ) {
            add_post_meta( $result->post_id, 'wcb2b_product_group_min', $values, true );
        }
        if ( in_array( $product->get_type(), array( 'variable' ) ) ) {
            $variations = wp_list_pluck( $product->get_available_variations(), 'variation_id' );
            foreach ( $variations as $variation ) {
                if ( metadata_exists( 'post', $variation, 'wcb2b_product_group_min' ) ) {
                    continue;
                }
                add_post_meta( $variation, 'wcb2b_product_group_min', $values, true );
            }
        }
        delete_post_meta( $result->post_id, 'wcb2b_min', $result->meta_value );
    }
    return true;
}

function wcb2b_set_maxquantity_by_group_310() {
    global $wpdb;
    $results = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = 'wcb2b_max'", OBJECT );
    $groups = wp_list_pluck( wcb2b_get_groups()->posts, 'ID' );
    foreach ( $results as $result ) {
        if ( metadata_exists( 'post', $result->post_id, 'wcb2b_product_group_max' ) ) {
            continue;
        }
        $values = array_fill_keys( $groups, $result->meta_value );
        $product = wc_get_product( $result->post_id );

        if ( in_array( $product->get_type(), array( 'simple' ) ) ) {
            add_post_meta( $result->post_id, 'wcb2b_product_group_max', $values, true );
        }
        if ( in_array( $product->get_type(), array( 'variable' ) ) ) {
            $variations = wp_list_pluck( $product->get_available_variations(), 'variation_id' );
            foreach ( $variations as $variation ) {
                if ( metadata_exists( 'post', $variation, 'wcb2b_product_group_max' ) ) {
                    continue;
                }
                add_post_meta( $variation, 'wcb2b_product_group_max', $values, true );
            }
        }
        delete_post_meta( $result->post_id, 'wcb2b_max', $result->meta_value );
    }
    return true;
}

function wcb2b_set_tax_display_by_group_310() {
    $option = get_option( 'wcb2b_split_taxes' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            if ( $id == get_option( 'wcb2b_guest_group' ) ) {
                update_post_meta( $id, 'wcb2b_group_tax_display', 'incl' );
            } else {
                update_post_meta( $id, 'wcb2b_group_tax_display', 'excl' );
            }
        }
    }
    return update_option( 'wcb2b_tax_display', $option ) && delete_option( 'wcb2b_split_taxes' );
}

function wcb2b_set_moderate_registration_by_group_310() {
    $option = get_option( 'wcb2b_moderate_customer_registration' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_moderate_registration', 1 );
        }
    }
    return true;
}

function wcb2b_set_price_rule_by_group_320() {
    $option = get_option( 'wcb2b_price_rules' );
    $groups = wcb2b_get_groups();
    $ids = wp_list_pluck( $groups->posts, 'ID' );
    foreach ( $ids as $id ) {
        update_post_meta( $id, 'wcb2b_group_price_rule', $option );
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_price_rules', false );
    return delete_option( 'wcb2b_price_rules' );
}

function wcb2b_set_already_bought_by_group_320() {
    $option = get_option( 'wcb2b_show_already_bought' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_already_bought', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_show_already_bought', false );
    return delete_option( 'wcb2b_show_already_bought' );
}

function wcb2b_set_show_sales_by_group_320() {
    $option = get_option( 'wcb2b_show_sales' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_show_sales', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_show_sales', false );
    return delete_option( 'wcb2b_show_sales' );
}

function wcb2b_set_shippings_tab_by_group_320() {
    $option = get_option( 'wcb2b_show_shippings_tab' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_shippings_tab', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_show_shippings_tab', false );
    return delete_option( 'wcb2b_show_shippings_tab' );
}

function wcb2b_set_show_unpaid_by_group_320() {
    $option = get_option( 'wcb2b_show_unpaid_amount' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_show_unpaid', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_show_unpaid_amount', false );
    return delete_option( 'wcb2b_show_unpaid_amount' );
}

function wcb2b_set_show_groupname_by_group_320() {
    $option = get_option( 'wcb2b_show_customer_group' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_show_groupname', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_show_customer_group', false );
    return delete_option( 'wcb2b_show_customer_group' );
}

function wcb2b_set_show_discount_myaccount_by_group_320() {
    $option = get_option( 'wcb2b_show_customer_discount' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_show_customer_discount', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_show_customer_discount', false );
    return delete_option( 'wcb2b_show_customer_discount' );
}

function wcb2b_set_show_discount_product_by_group_320() {
    $option = get_option( 'wcb2b_show_customer_discount_product' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_show_discount_product', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_show_customer_discount_product', false );
    return delete_option( 'wcb2b_show_customer_discount_product' );
}

function wcb2b_drop_deprecated_options_320() {
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_tax_display', false );
    update_option( 'wcb2b_min_purchase_amount', false );
    update_option( 'wcb2b_moderate_customer_registration', false );
    update_option( 'wcb2b_product_cat_visibility', false );
    update_option( 'wcb2b_page_visibility', false );
    return  delete_option( 'wcb2b_tax_display' ) &&
            delete_option( 'wcb2b_min_purchase_amount' ) && 
            delete_option( 'wcb2b_moderate_customer_registration' ) && 
            delete_option( 'wcb2b_product_cat_visibility' ) &&
            delete_option( 'wcb2b_page_visibility' );
}

function wcb2b_set_minpurchase_alert_by_group_321() {
    $option = get_option( 'wcb2b_display_min_purchase_cart_message' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_minpurchase_alert', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_display_min_purchase_cart_message', false );
    return delete_option( 'wcb2b_display_min_purchase_cart_message' );
}

function wcb2b_set_minpurchase_button_by_group_321() {
    $option = get_option( 'wcb2b_prevent_checkout_button' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_minpurchase_button', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_prevent_checkout_button', false );
    return delete_option( 'wcb2b_prevent_checkout_button' );
}

function wcb2b_set_show_barcode_by_group_321() {
    $option = get_option( 'wcb2b_show_barcode' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_show_barcode', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_show_barcode', false );
    return delete_option( 'wcb2b_show_barcode' );
}

function wcb2b_set_show_rrp_by_group_321() {
    $option = get_option( 'wcb2b_show_rrp' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_show_rrp', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_show_rrp', false );
    return delete_option( 'wcb2b_show_rrp' );
}

function wcb2b_set_extend_registration_fields_by_group_321() {
    $option = get_option( 'wcb2b_extend_registration_form' );
    if ( $option === 'yes' ) {
        $groups = wcb2b_get_groups();
        $ids = wp_list_pluck( $groups->posts, 'ID' );
        foreach ( $ids as $id ) {
            update_post_meta( $id, 'wcb2b_group_extend_registration_fields', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_extend_registration_form', false );
    return delete_option( 'wcb2b_extend_registration_form' );
}

function wcb2b_set_hide_prices_by_group_321() {
    $option = get_option( 'wcb2b_hide_prices' );
    if ( $option === 'yes' ) {
        if ( $guest_group_id = get_option( 'wcb2b_guest_group' ) ) {
            update_post_meta( $guest_group_id, 'wcb2b_group_hide_prices', 1 );
        }
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_hide_prices', false );
    return delete_option( 'wcb2b_hide_prices' );
}

function wcb2b_set_add_vatnumber_by_group_321() {
    $value = 'hidden';
    if ( 'yes' === get_option( 'wcb2b_add_vatnumber' ) ) {
        $value = 'yes' === get_option( 'wcb2b_vatnumber_required' ) ? 'required' : 'optional';
    }
    $groups = wcb2b_get_groups();
    $ids = wp_list_pluck( $groups->posts, 'ID' );
    foreach ( $ids as $id ) {
        update_post_meta( $id, 'wcb2b_group_add_vatnumber', $value );
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_add_vatnumber', false );
    update_option( 'wcb2b_vatnumber_required', false );
    return delete_option( 'wcb2b_add_vatnumber' ) && delete_option( 'wcb2b_vatnumber_required' );
}

function wcb2b_set_add_invoice_email_by_group_321() {
    $value = 'hidden';
    if ( 'yes' === get_option( 'wcb2b_add_invoice_email' ) ) {
        $value = 'yes' === get_option( 'wcb2b_invoice_email_required' ) ? 'required' : 'optional';
    }
    $groups = wcb2b_get_groups();
    $ids = wp_list_pluck( $groups->posts, 'ID' );
    foreach ( $ids as $id ) {
        update_post_meta( $id, 'wcb2b_group_add_invoice_email', $value );
    }
    // Trick: give a fake value before delete to prevent false response
    update_option( 'wcb2b_add_invoice_email', false );
    update_option( 'wcb2b_invoice_email_required', false );
    return delete_option( 'wcb2b_add_invoice_email' ) && delete_option( 'wcb2b_invoice_email_required' );
}

function wcb2b_set_suitable_roles_321() {
    return update_option( 'wcb2b_has_role_customer', apply_filters( 'wcb2b_has_role_customer', array( 'customer' ) ), true );
}

function wcb2b_set_vies_validation_by_group_335() {
    $option = get_option( 'wcb2b_vies_validation' );
    if ( $option === 'yes' ) {
        $value = 'strict';
    } else {
        $value = 'disabled';
    }
    $groups = wcb2b_get_groups();
    $ids = wp_list_pluck( $groups->posts, 'ID' );
    foreach ( $ids as $id ) {
        update_post_meta( $id, 'wcb2b_group_vies_validation', $value );
    }
    return update_option( 'wcb2b_vies_validation', $value );
}

function wcb2b_add_wcb2bloginform_meta_404() {
    $pages = get_pages();
    foreach ( $pages as $page ) {
        if ( has_shortcode( $page->post_content, 'wcb2bloginform' ) ) {
            if ( false !== preg_match( '/wcb2b_group="([0-9]+?)"/', $page->post_content, $matches ) ) {
                $group_id = $matches[1] ?? 0;
                if ( get_post_type( $group_id ) == 'wcb2b_group' ) {
                    update_post_meta( $page->ID, 'wcb2bloginform', $group_id );
                }
            }
        }
    }
    return true;
}