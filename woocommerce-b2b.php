<?php

/*
    Plugin Name: WooCommerce B2B
    Plugin URI: https://commercebird.com/
    Description: Transform your WooCommerce into Business-to-Business shop for wholesale.
    Author: CommerceBird
    Author URI: https://commercebird.com
    Version: 4.1.0
    Text Domain: woocommerce-b2b
    Domain Path: /i18n/
    License: GPLv3
    License URI: http://www.gnu.org/licenses/gpl-3.0.html

    Requires at least: 5.0
    Tested up to: 6.8

    WC requires at least: 8.0
    WC tested up to: 10.3
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// A host plugin may already have autoloaded the loader
if ( ! class_exists( 'WCB2B_Package', false ) ) {
    require_once __DIR__ . '/includes/class-wcb2b-package.php';
}

WCB2B_Package::boot_standalone( __FILE__ );
