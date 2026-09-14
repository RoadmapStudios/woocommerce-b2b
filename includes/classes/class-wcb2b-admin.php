<?php

/**
 * WooCommerce B2B Admin set-up Class
 *
 * @version 4.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Admin Class
 */
class WCB2B_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Init current class hooks
     */
    public function init_hooks() {
        add_action( 'admin_enqueue_scripts', array( $this, 'print_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'print_scripts' ) );
    }

    /**
     * Include any classes we need within admin
     */
    public function includes() {
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-admin-orders.php';
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-admin-posts.php';
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-admin-products.php';
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-admin-tools.php';
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-admin-users.php';
    }

    /**
     * Print styles
     */
    public function print_styles() {
        wp_register_style( 'wcb2b_admin', plugins_url( 'assets/css/admin.min.css', WCB2B_PLUGIN_FILE ), false, '4.0.1' );
        wp_enqueue_style( 'wcb2b_admin' );
    }

    /**
     * Print scripts
     */
    public function print_scripts() {
        $groups = wcb2b_get_groups();

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_register_script( 'wcb2b_admin', plugins_url( 'assets/js/admin.min.js', WCB2B_PLUGIN_FILE ), array( 'jquery-ui-datepicker' ), '4.1.0', true );
        wp_enqueue_script( 'wcb2b_admin' );
        wp_localize_script( 'wcb2b_admin', 'parameters', array(
            'wcb2b_groups' => json_encode( wp_list_pluck( $groups->posts, 'post_title', 'ID' ) ),
            'wcb2b_statuses' => json_encode( WCB2B_Configuration::get_customer_statuses() )
        ) );
    }

}

return new WCB2B_Admin();