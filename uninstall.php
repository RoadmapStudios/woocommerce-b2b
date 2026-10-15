<?php

/**
 * WooCommerce B2B Uninstall
 *
 * Uninstalling WooCommerce B2B deletes user roles and options
 *
 * @version 4.2.0
 */

// Exit if accessed directly
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// A host plugin still runs WooCommerce B2B as a package: keep its data
if ( defined( 'WCB2B_MODE' ) && 'package' === WCB2B_MODE ) {
    return;
}

include_once __DIR__ . '/includes/wcb2b-uninstall.php';

wcb2b_uninstall();
