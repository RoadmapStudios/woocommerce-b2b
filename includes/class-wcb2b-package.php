<?php

/**
 * WooCommerce B2B package loader
 *
 * Boots WooCommerce B2B either as a standalone plugin or as a Composer
 * package bundled inside another (host) plugin
 *
 * @version 4.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Package Class
 */
final class WCB2B_Package {

    const MODE_PLUGIN       = 'plugin';
    const MODE_PACKAGE      = 'package';
    const STANDALONE_PLUGIN = 'woocommerce-b2b/woocommerce-b2b.php';

    /**
     * Plugin file that booted WooCommerce B2B
     *
     * @var string|null
     */
    private static $booted_by = null;

    /**
     * Plugin files whose boot was refused
     *
     * @var string[]
     */
    private static $refused = array();

    /**
     * Boot WooCommerce B2B from a host plugin
     *
     * Must be called while the host plugin main file loads, not inside a hook
     *
     * @param string $plugin_file Host plugin main file (__FILE__)
     * @param array $args Optional: 'url' => package base URL when vendor/ lives outside wp-content/plugins
     * @return bool True if this call booted WooCommerce B2B
     */
    public static function boot( $plugin_file, array $args = array() ) {
        // The standalone plugin always wins when active
        if ( self::standalone_is_active() ) {
            return self::refuse( $plugin_file );
        }
        return self::start( $plugin_file, self::MODE_PACKAGE, $args );
    }

    /**
     * Boot WooCommerce B2B as the standalone plugin
     *
     * @param string $plugin_file Standalone plugin main file
     * @return bool
     */
    public static function boot_standalone( $plugin_file ) {
        return self::start( $plugin_file, self::MODE_PLUGIN, array() );
    }

    /**
     * Delete all WooCommerce B2B data, for host plugins' uninstall.php
     */
    public static function uninstall() {
    }

    /**
     * Package root directory
     *
     * @return string
     */
    public static function dir() {
        return dirname( __DIR__ ) . '/';
    }

    /**
     * Admin notice shown when a copy of WooCommerce B2B was skipped
     */
    public static function already_loaded_notice() {
        printf( '<div class="error"><p>%s</p></div>',
            sprintf( esc_html__( 'WooCommerce B2B is already loaded by %1$s, so the copy in %2$s was skipped. Keep only one of them active.', 'woocommerce-b2b' ),
                '<code>' . esc_html( self::loaded_by() ) . '</code>',
                '<code>' . esc_html( implode( ', ', array_map( 'plugin_basename', self::$refused ) ) ) . '</code>'
            )
        );
    }

    /**
     * Define constants and load the core
     *
     * @param string $plugin_file Plugin main file
     * @param string $mode Boot mode
     * @param array $args Boot arguments
     * @return bool
     */
    private static function start( $plugin_file, $mode, array $args ) {
        if ( null !== self::$booted_by || class_exists( 'WooCommerceB2B', false ) ) {
            return self::refuse( $plugin_file );
        }
        if ( did_action( 'plugins_loaded' ) || doing_action( 'plugins_loaded' ) ) {
            _doing_it_wrong( __METHOD__, 'WooCommerce B2B must be booted while the plugin file loads, before plugins_loaded.', '4.2.0' );
            return false;
        }
        self::$booted_by = $plugin_file;

        $url = isset( $args['url'] ) ? $args['url'] : plugins_url( '/', self::dir() . 'woocommerce-b2b.php' );

        defined( 'WCB2B_MODE' ) || define( 'WCB2B_MODE', $mode );
        defined( 'WCB2B_PLUGIN_FILE' ) || define( 'WCB2B_PLUGIN_FILE', $plugin_file );
        defined( 'WCB2B_PACKAGE_DIR' ) || define( 'WCB2B_PACKAGE_DIR', self::dir() );
        defined( 'WCB2B_PLUGIN_URI' ) || define( 'WCB2B_PLUGIN_URI', trailingslashit( apply_filters( 'wcb2b_package_url', $url ) ) );

        include_once self::dir() . 'includes/classes/class-wcb2b.php';

        // Global for backwards compatibility
        $GLOBALS['wcb2b'] = WCB2B();

        return true;
    }

    /**
     * Record a refused boot and warn the admin once
     *
     * @param string $plugin_file Plugin main file
     * @return bool Always false
     */
    private static function refuse( $plugin_file ) {
        self::$refused[] = $plugin_file;
        if ( 1 === count( self::$refused ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'already_loaded_notice' ) );
        }
        return false;
    }

    /**
     * Whether the standalone WooCommerce B2B plugin is active
     *
     * @return bool
     */
    private static function standalone_is_active() {
        return array_key_exists( self::STANDALONE_PLUGIN, (array) get_site_option( 'active_sitewide_plugins', array() ) )
            || in_array( self::STANDALONE_PLUGIN, (array) get_option( 'active_plugins', array() ), true );
    }

    /**
     * Plugin basename of the copy that runs WooCommerce B2B
     *
     * @return string
     */
    private static function loaded_by() {
        return null !== self::$booted_by ? plugin_basename( self::$booted_by ) : self::STANDALONE_PLUGIN;
    }

}
