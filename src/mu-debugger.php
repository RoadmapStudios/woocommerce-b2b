<?php

/*
    Plugin Name:    WooCommerce B2B - Debugger
    Plugin URI:     https://woocommerce-b2b.com/
    Description:    Disable all other plugins than WooCommerce B2B for debug purposes.
    Author:         Code4Life
    Author URI:     https://code4life.it/
    Version:        3.3.3
    Text Domain:    woocommerce-b2b
    License:        GPLv3
    License URI:    http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add notice to admin
 */
add_action( 'admin_notices', function() {
    $plugin_name = 'WooCommerce B2B';
    printf( '<div class="notice notice-warning"><p><span class="dashicons dashicons-bell"></span> %s</p></div>',
        sprintf( esc_html__( '%s debug mode is active. All plugins except WooCommerce and %s will be temporarily disabled only for your current IP ADDRESS <%s>.', 'woocommerce-b2b' ), 
            sprintf( '<strong>%s</strong>', $plugin_name),
            $plugin_name,
            get_option( 'wcb2b_debug', false )
        )
    );
} );

/**
 * Consider only WooCommerce B2B active
 *
 * @param mixed $value Option value
 * @param string $option Option name
 * @return mixed
 */
add_filter( 'option_active_plugins', function( $value, $option ) {
    if ( $_SERVER['REMOTE_ADDR'] == get_option( 'wcb2b_debug', false ) ) {
        return apply_filters( 'wcb2b_debug_muplugins', array(
            'woocommerce/woocommerce.php',
            'woocommerce-b2b/woocommerce-b2b.php'
        ) );
    }
    return $value;
}, 1, 2 );