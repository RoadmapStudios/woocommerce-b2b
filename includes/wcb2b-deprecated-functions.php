<?php

/**
 * WooCommerce B2B Deprecated functions
 *
 * @version 3.0.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return visible product categories ids, filtered by user (if logged in) or empty
 * @deprecated 3.0.2 No longer used
 */
function wcb2b_get_allowed_terms() {
    wc_deprecated_function( 'wcb2b_get_allowed_terms', '3.0.2' );
}

/**
 * Return visible products ids (belonging visible product categories), filtered by user (if logged in) or empty
 * @deprecated 3.0.2 No longer used
 */
function wcb2b_get_allowed_products() {
    wc_deprecated_function( 'wcb2b_get_allowed_products', '3.0.2' );
}

/**
 * Set visible terms for current customer group in WooCommerce global class
 * @deprecated 3.0.2 No longer used
 */
function wcb2b_set_allowed_terms() {
    wc_deprecated_function( 'wcb2b_set_allowed_terms', '3.0.2' );
}

/**
 * Set visible products for current customer group in WooCommerce global class
 * @deprecated 3.0.2 No longer used
 */
function wcb2b_set_allowed_products() {
    wc_deprecated_function( 'wcb2b_set_allowed_products', '3.0.2' );
}