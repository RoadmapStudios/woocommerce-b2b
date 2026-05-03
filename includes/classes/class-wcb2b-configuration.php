<?php

/**
 * WooCommerce B2B Configuration Class
 *
 * @version 3.3.9
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WCB2B_Configuration', false ) ) :

    /**
     * WCB2B_Configuration Class
     */
    class WCB2B_Configuration {

        /**
         * Configuration
         *
         * @var array
         */
        private static $configuration = array();

        /**
         * Unallowed terms
         *
         * @var array
         */
        private static $unallowed_terms = array();

        /**
         * Unallowed products
         *
         * @var array
         */
        private static $unallowed_products = array();

        /**
         * The single instance of the class
         *
         * @var WCB2B_Configuration
         */
        protected static $_instance = null;

        /**
         * Main WCB2B_Configuration Instance
         *
         * Ensures only one instance of WCB2B_Configuration is loaded or can be loaded
         * 
         * @static
         * @return WCB2B_Configuration - Main instance
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Load WooCommerce B2B configuration
         *
         * @static
         * @return array
         */
        public static function get_customer_statuses() {
            $customer_statuses = apply_filters( 'wcb2b_customer_statuses', array(
                0 => esc_html__( 'Inactive', 'woocommerce-b2b' ),
                1 => esc_html__( 'Active', 'woocommerce-b2b' )
            ) );
            return $customer_statuses;
        }

        /**
         * Set unallowed terms and products
         *
         * @static
         */
        public static function set_unallowed_terms_and_products() {
            self::$unallowed_terms = wcb2b_set_unallowed_terms();
            self::$unallowed_products = wcb2b_set_unallowed_products();
        }

        /**
         * Get unallowed terms
         *
         * @static
         * @return array
         */
        public static function get_unallowed_terms() {
            return self::$unallowed_terms;
        }

        /**
         * Get unallowed products
         *
         * @static
         * @return array
         */
        public static function get_unallowed_products() {
            return self::$unallowed_products;
        }

    }

endif;