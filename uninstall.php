<?php

/**
 * WooCommerce B2B Uninstall
 *
 * Uninstalling WooCommerce deletes user roles and options
 *
 * @version 4.0.4
 */

// Exit if accessed directly
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

use Automattic\WooCommerce\Utilities\OrderUtil;

global $wpdb;

// Delete options and settings
delete_option( 'wcb2b_version' );
delete_option( 'wcb2b_notice_shown' );
delete_option( 'wcb2b_enable' );
delete_option( 'wcb2b_add_invoice_email' );
delete_option( 'wcb2b_invoice_email_required' );
delete_option( 'wcb2b_add_vatnumber' );
delete_option( 'wcb2b_vatnumber_required' );
delete_option( 'wcb2b_vies_validation' );
delete_option( 'wcb2b_extend_registration_form' );
delete_option( 'wcb2b_registration_notice' );
delete_option( 'wcb2b_redirect_not_allowed' );
delete_option( 'wcb2b_default_group' );
delete_option( 'wcb2b_guest_group' );
delete_option( 'wcb2b_restricted_catalog' );
delete_option( 'wcb2b_enable_quotations' );
delete_option( 'wcb2b_account_quotations_endpoint' );
delete_option( 'wcb2b_enable_saved_carts' );
delete_option( 'wcb2b_account_saved_carts_endpoint' );
delete_option( 'wcb2b_quick_order_page' );
delete_option( 'wcb2b_tax_exemption_countries' );
delete_option( 'wcb2b_has_role_customer' );
delete_option( 'wcb2b_delivery_weekdays' );
delete_option( 'wcb2b_delivery_holidays' );
delete_option( 'wcb2b_quotation_validity_days' );
delete_option( 'wcb2b_debug' );

// Delete orders data
if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
    // HPOS usage is enabled
    $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key IN ( '_wcb2b_group', '_billing_vat', '_billing_invoice_email', '_wcb2b_external_invoice_number', '_wcb2b_quotation_validity' );" );
} else {
    // Traditional CPT-based orders are in use
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( '_wcb2b_group', '_billing_vat', '_billing_invoice_email', '_wcb2b_external_invoice_number', '_wcb2b_quotation_validity' );" );
}

// Delete users data.
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'wcb2b_group' );" );
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( 'wcb2b_group_visibility', 'wcb2bloginform' );" ); // Posts
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( 'wcb2b_group_price_rule', 'wcb2b_group_discount', 'wcb2b_group_tax_display', 'wcb2b_group_min_purchase_amount', 'wcb2b_group_tax_exemption', 'wcb2b_group_gateways', 'wcb2b_group_shippings', 'wcb2b_group_already_bought', 'wcb2b_group_show_sales', 'wcb2b_group_shippings_tab', 'wcb2b_group_purchase_history_tab', 'wcb2b_group_show_unpaid', 'wcb2b_group_show_groupname', 'wcb2b_group_show_discount_myaccount', 'wcb2b_group_show_discount_product', 'wcb2b_group_packaging_fee', 'wcb2b_group_terms_conditions', 'wcb2b_group_minpurchase_alert', 'wcb2b_group_minpurchase_button', 'wcb2b_group_show_barcode', 'wcb2b_group_show_shipping_class', 'wcb2b_group_show_rrp', 'wcb2b_group_hide_prices', 'wcb2b_group_quotations_with_hidden_prices', 'wcb2b_group_save_cart', 'wcb2b_group_add_invoice_email', 'wcb2b_group_add_vatnumber', 'wcb2b_group_add_business_certificate', 'wcb2b_group_show_deliverytime', 'wcb2b_group_vies_validation', 'wcb2b_group_catalog_mode' );" ); // Groups
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( '_wcb2b_coupon_total_spent', '_wcb2b_coupon_group' );" ); // Coupon
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( 'wcb2b_product_group_hide_prices', 'wcb2b_product_group_hide_stocks', 'wcb2b_product_group_prices', 'wcb2b_product_group_tier_prices', 'wcb2b_product_group_packages', 'wcb2b_product_group_min', 'wcb2b_product_group_max', 'wcb2b_barcode', 'wcb2b_product_group_visibility' );" ); // Products
$wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE meta_key IN ( 'wcb2b_group_visibility', 'wcb2b_delivery_days_min', 'wcb2b_delivery_days_max' );" );
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ( 'wcb2b_group', 'wcb2b_status', 'wcb2b_unpaid_limit', 'billing_vat', 'wcb2b_vies_validated_user', 'wcb2b_vies_response' );" );
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wcb2b_cart%';" );

// Bonus: require WooCommerce B2B Warehouses
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( 'wcb2b_group_warehouse' );" ); // Groups

// Clear any cached data that has been removed
flush_rewrite_rules();
wp_cache_flush();