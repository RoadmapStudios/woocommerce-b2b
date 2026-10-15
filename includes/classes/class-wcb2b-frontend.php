<?php

/**
 * WooCommerce B2B Frontend set-up Class
 *
 * @version 4.0.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Frontend Class
 */
class WCB2B_Frontend {

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
        add_action( 'wp_enqueue_scripts', array( $this, 'print_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'print_scripts' ) );
    }

    /**
     * Include any classes we need within frontend
     */
    public function includes() {
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-frontend-checkout.php';
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-frontend-myaccount.php';
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-frontend-posts.php';
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-frontend-products.php';
    }

    /**
     * Print styles
     */
    public function print_styles() {
        if ( is_account_page() ) {
            wp_register_style( 'wcb2b_myaccount', WCB2B_PLUGIN_URI . 'assets/css/myaccount.min.css', false, '4.0.5' );
            wp_enqueue_style( 'wcb2b_myaccount' );
        }
        if ( is_product() ) {
            wp_register_style( 'wcb2b_product', WCB2B_PLUGIN_URI . 'assets/css/product.min.css', false, '3.2.3' );
            wp_enqueue_style( 'wcb2b_product' );
        }
        if ( is_cart() ) {
            wp_register_style( 'wcb2b_cart', WCB2B_PLUGIN_URI . 'assets/css/cart.min.css', false, '4.0.3' );
            wp_enqueue_style( 'wcb2b_cart' );
        }
        wp_register_style( 'wcb2b_livesearch', WCB2B_PLUGIN_URI . 'assets/css/livesearch.min.css', false, '3.0.3' );
        wp_enqueue_style( 'wcb2b_livesearch' );

        wp_register_style( 'wcb2b_frontend', WCB2B_PLUGIN_URI . 'assets/css/frontend.min.css', false, '4.0.3' );
        wp_enqueue_style( 'wcb2b_frontend' );
    }

    /**
     * Print scripts
     */
    public function print_scripts() {
        if ( is_account_page() ) {
            wp_register_script( 'wcb2b_myaccount', WCB2B_PLUGIN_URI . 'assets/js/myaccount.min.js', array( 'selectWoo' ), '3.0.2', true );
            wp_enqueue_script( 'wcb2b_myaccount' );
        }
        if ( is_product() ) {
            wp_register_script( 'wcb2b_product', WCB2B_PLUGIN_URI . 'assets/js/product.min.js', array( 'jquery' ), '3.2.3', true );
            wp_enqueue_script( 'wcb2b_product' );
            wp_register_script( 'wcb2b_add_to_cart_variation', WCB2B_PLUGIN_URI . 'assets/js/add-to-cart-variation.min.js', array( 'jquery' ), '3.1.0', true );
            wp_enqueue_script( 'wcb2b_add_to_cart_variation' );
        }
        if ( is_cart() ) {
            wp_register_script( 'wcb2b_cart', WCB2B_PLUGIN_URI . 'assets/js/cart.min.js', array( 'jquery' ), '4.0.3', true );
            wp_enqueue_script( 'wcb2b_cart' );
        }
        wp_register_script( 'wcb2b_livesearch', WCB2B_PLUGIN_URI . 'assets/js/livesearch.min.js', array( 'jquery' ), '3.0.3', true );
        wp_localize_script(
            'wcb2b_livesearch',
            'wcb2b_livesearch_params',
            apply_filters( 'wcb2b_livesearch_params', array(
                'current_url'        => get_the_permalink(),
                'ajax_url'           => WC()->ajax_url(),
                'wc_ajax_url'        => WC_AJAX::get_endpoint( '%%endpoint%%' ),
                'i18n_added_to_cart' => esc_attr__( 'Added to cart', 'woocommerce-b2b' ),
                'min_lenght'         => 3
            ) )
        );
    }

}

return new WCB2B_Frontend();