<?php

/**
 * WooCommerce B2B Debug Class
 *
 * @version 3.3.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Debug Class
 */
class WCB2B_Debug {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Init current class hooks
     */
    public function init_hooks() {
        add_filter( 'admin_init', array( $this, 'manage_debug_mode' ) );
    }

    public function manage_debug_mode() {
        // Check POST data and security nonce
        if ( ! isset( $_POST['_wcb2bnonce'], $_POST['wcb2b_tools_action'] ) ) { return; }
        if ( ! wp_verify_nonce( $_POST['_wcb2bnonce'], 'wcb2b_tools' ) ) { return; }

        try {
            $action = sanitize_text_field( $_POST['wcb2b_tools_action'] );
            switch ( $action ) {
                // Enable debug mode
                case 'wcb2b_form_debug_mode' :
                    if ( get_option( 'wcb2b_debug', false ) ) {
                        // Check if MU plugin exists, then try to delete it
                        if ( is_file( WPMU_PLUGIN_DIR . '/wcb2b_debug.php' ) ) {
                            if ( ! unlink( WPMU_PLUGIN_DIR . '/wcb2b_debug.php' ) ) {
                                throw new Exception( __( 'Cannot delete MU-Plugin file', 'woocommerce-b2b' ) );
                            }
                        }
                        // Check if MU directory exists and is empty, then try to delete it
                        if ( is_dir( WPMU_PLUGIN_DIR ) ) {
                            if ( count( glob( WPMU_PLUGIN_DIR . '/*' ) ) === 0 && ! rmdir( WPMU_PLUGIN_DIR ) ) {
                                throw new Exception( __( 'Cannot delete MU-Plugin directory', 'woocommerce-b2b' ) );
                            }
                        }
                        // Delete debug mode allowed IP
                        if ( ! delete_option( 'wcb2b_debug' ) ) {
                            throw new Exception( __( 'Cannot delete IP address option', 'woocommerce-b2b' ) );
                        }
                    } else {
                        // Check if MU directory exists, else try to create it
                        if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
                            if ( ! mkdir( WPMU_PLUGIN_DIR, 0775, true ) ) {
                                throw new Exception( __( 'Cannot create MU-Plugin directory', 'woocommerce-b2b' ) );
                            }
                        }
                        // Check if MU plugin exists, else try to create it
                        if ( ! is_file( WPMU_PLUGIN_DIR . '/wcb2b_debug.php' ) ) {
                            if ( ! copy( WCB2B_ABSPATH . '/src/mu-debugger.php', WPMU_PLUGIN_DIR . '/wcb2b_debug.php' ) ) {
                                throw new Exception( __( 'Cannot create MU-Plugin file', 'woocommerce-b2b' ) );
                            }
                        }
                        // Store current IP address
                        if ( ! update_option( 'wcb2b_debug', $_SERVER['REMOTE_ADDR'] ) ) {
                            throw new Exception( __( 'Cannot store IP address option', 'woocommerce-b2b' ) );
                        }
                    }
                    break;
                default :
                    throw new Exception( __( 'Wrong action', 'woocommerce-b2b' ) );
                    break;
            }
            // Right now, all is ok!
            wp_safe_redirect(
                site_url( '/wp-admin/admin.php?page=wc-settings&tab=woocommerce-b2b&section=tools' )
            );
            exit();
        } catch ( Exception $e ) {
            add_action( 'admin_notices', function() use ( $e ) {
                printf( '<div class="error"><p>%s</p></div>', 
                    sprintf( esc_html__( 'An error occurred while activating or deactivating debug mode: %s', 'woocommerce-b2b' ),
                        $e->getMessage()
                    )
                );
            } );
        }
    }

}

return new WCB2B_Debug();