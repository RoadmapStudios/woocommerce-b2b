<?php

/**
 * WooCommerce B2B Settings
 *
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WCB2B_Settings', false ) ) {
    return new WCB2B_Settings();
}

/**
 * WCB2B_Settings
 */
class WCB2B_Settings extends WC_Settings_Page {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id    = 'woocommerce-b2b';
        $this->label = __( 'B2B', 'woocommerce-b2b' );

        add_action( 'woocommerce_admin_field_wcb2b_table', array( $this, 'automatic_group_change_option_template' ) );
        add_filter( 'woocommerce_admin_settings_sanitize_option_wcb2b_automatic_group_change', array( $this, 'automatic_group_change_option_sanitize' ), 10, 3 );

        parent::__construct();
    }

    /**
     * Get settings array
     *
     * @return array
     */
    public function get_settings() {
        // If SOAP extension is not available, disable VIES validation
        if ( ! $soap = extension_loaded( 'soap' ) ) {
            update_option( 'wcb2b_vies_validation', 'no' );
        }

        $countries = new WC_Countries();
        $groups = wcb2b_get_groups();
        $roles = wp_roles()->get_names();

        $settings = apply_filters( 'wcb2b_general_settings', array(
            /* GENERAL SETTINGS */
            array(
                'name'     => __( 'General settings', 'woocommerce-b2b' ),
                'type'     => 'title',
                'desc'     => __( 'This section controls general options to manage your shop.', 'woocommerce-b2b' )
            ),
            array(
                'name'              => __( 'Restricted catalog', 'woocommerce-b2b' ),
                'type'              => 'checkbox',
                'desc'              => __( 'Restict catalog visibility to logged in customers', 'woocommerce-b2b' ),
                'desc_tip'          => __( 'Redirect not logged in users to login page to have a restricted access', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_restricted_catalog'
            ),
            array(
                'name'              => __( 'Quick order page', 'woocommerce-b2b' ),
                'type'              => 'select',
                'default'           => 'null',
                'class'             => 'wc-enhanced-select',
                'desc'              => sprintf( __( 'Page to use for quick orders. Remember to add the shortcode %1$s.', 'woocommerce-b2b' ), '<strong>[wcb2bquickorder]</strong>'),
                'id'                => 'wcb2b_quick_order_page',
                'options'           => array_reduce( get_pages( array( 'post_status' => array( 'publish', 'draft' ) ) ), function( $result, $item ) {
                    $item = (array)$item;
                    $result[$item['ID']] = $item['post_title'];
                    return $result;
                } )
            ),
            array(
                 'type' => 'sectionend'
            ),

            /* PRODUCTS SETTINGS */
            array(
                'name'              => __( 'Products settings', 'woocommerce-b2b' ),
                'type'              => 'title',
                'desc'              => __( 'This section controls product and product categories options to manage prices and visibility.', 'woocommerce-b2b' )
            ),

            // Delivery weekdays
            array(
                'name'              => __( 'Delivery week days', 'woocommerce-b2b' ),
                'type'              => 'multiselect',
                'default'           => 'null',
                'class'             => 'wc-enhanced-select',
                'desc'              => __( 'Indicate week days to be considered working for delivery time calculation.', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_delivery_weekdays',
                'options'           => array(
                    '1' => esc_html__( 'Monday', 'woocommerce-b2b' ),
                    '2' => esc_html__( 'Tuesday', 'woocommerce-b2b' ),
                    '3' => esc_html__( 'Wednesday', 'woocommerce-b2b' ),
                    '4' => esc_html__( 'Thursday', 'woocommerce-b2b' ),
                    '5' => esc_html__( 'Friday', 'woocommerce-b2b' ),
                    '6' => esc_html__( 'Saturday', 'woocommerce-b2b' ),
                    '7' => esc_html__( 'Sunday', 'woocommerce-b2b' ),
                )
            ),
            // Holidays
            array(
                'name'              => __( 'Holidays', 'woocommerce-b2b' ),
                'type'              => 'text',
                'desc'              => __( 'Comma separated dates to consider as holiday (format dd/mm). You can choose from calendar below to add a date', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_delivery_holidays'
            ),

            // Tax exemption by country
            array(
                'name'              => __( 'Tax exemption by country', 'woocommerce-b2b' ),
                'type'              => 'multiselect',
                'default'           => 'null',
                'class'             => 'wc-enhanced-select',
                'desc'              => __( 'Apply the tax exemption in the shopping cart for customers belonging to a group (not GUEST) with billing country among those chosen.', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_tax_exemption_countries',
                'options'           => $countries->get_countries()
            ),
            
            // Redirect on hidden product categories
            array(
                'name'              => __( 'Redirect on page', 'woocommerce-b2b' ),
                'type'              => 'select',
                'default'           => 'null',
                'class'             => 'wc-enhanced-select',
                'id'                => 'wcb2b_redirect_not_allowed',
                'desc'              => __( 'When users go directly to a not visible product category or a not visible product', 'woocommerce-b2b' ),
                'options'           => array_reduce( get_pages(), function( $result, $item ) {
                    $item = (array)$item;
                    $result[$item['ID']] = $item['post_title'];
                    return $result;
                }, array(
                    'null'  => __( '-- Do nothing --', 'woocommerce-b2b' ),
                    '0'     => __( '404 (not found) page', 'woocommerce-b2b' )
                ) )
            ),
            
            array(
                 'type'             => 'sectionend'
            ),

            /* CART & CHECKOUT SETTINGS */
            array(
                'name'              => __( 'Cart & Checkout settings', 'woocommerce-b2b' ),
                'type'              => 'title',
                'desc'              => __( 'This section controls cart and checkout pages by adding new fields and by setting minimum cart amount to allow to purchase.', 'woocommerce-b2b' )
            ),

            // Add invoice email field in checkout
            array(
                'name'              => __( 'Email address for invoices', 'woocommerce-b2b' ),
                'type'              => 'select',
                'default'           => 'no',
                'desc'              => __( 'Email address for invoices field in checkout', 'woocommerce-b2b' ),
                'desc_tip'          => __( 'Add an additional field to billing address, to allow customers to communicate email address where they prefer to receive invoices', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_add_invoice_email',
                'options'           => array(
                    'hidden'    => __( 'Hidden', 'woocommerce-b2b' ),
                    'optional'  => __( 'Optional', 'woocommerce-b2b' ),
                    'required'  => __( 'Required', 'woocommerce-b2b' )
                )
            ),

            // Add VAT number field in checkout
            array(
                'name'              => __( 'VAT number', 'woocommerce-b2b' ),
                'type'              => 'select',
                'default'           => 'no',
                'desc'              => __( 'VAT number field in checkout', 'woocommerce-b2b' ),
                'desc_tip'          => __( 'Add an additional field to billing address, to allow customers to communicate their VAT number', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_add_vatnumber',
                'options'           => array(
                    'hidden'    => __( 'Hidden', 'woocommerce-b2b' ),
                    'optional'  => __( 'Optional', 'woocommerce-b2b' ),
                    'required'  => __( 'Required', 'woocommerce-b2b' )
                )
            ),

            // Enable VIES check for EU VAT number
            array(
                'name'              => __( 'VIES validation', 'woocommerce-b2b' ),
                'type'              => 'select',
                'default'           => 'disabled',
                'desc'              => __( 'Enable VAT number VIES validation in checkout for EU customers', 'woocommerce-b2b' ),
                'desc_tip'          => __( 'VIES validation response can be viewed in backend user edit page', 'woocommerce-b2b' ) . ( $soap ? false : '<br /><span class="error">' . __( 'Attention! To enable VIES check you need PHP SOAP extension is available on your server. Please contact your hosting provider.', 'woocommerce-b2b' ) .'</span>' ),
                'id'                => 'wcb2b_vies_validation',
                'disabled'          => ( ! $soap ),
                'options'           => array(
                    'disabled' => esc_html__( 'Disabled', 'woocommerce-b2b' ),
                    'strict'   => esc_html__( 'Enabled (Strict - block purchase if error occurs)', 'woocommerce-b2b' ),
                    'lax'      => esc_html__( 'Enabled (Lax - don\'t block purchase if error occurs)', 'woocommerce-b2b' )
                )
            ),

            array(
                 'type'             => 'sectionend'
            ),

            /* CUSTOMERS SETTINGS */
            array(
                'name'              => __( 'Customers settings', 'woocommerce-b2b' ),
                'type'              => 'title',
                'desc'              => __( 'This section controls customers options to manage registration forms and account activation.', 'woocommerce-b2b' )
            ),

            // Registration notice
            array(
                'name'              => __( 'Registration notice', 'woocommerce-b2b' ),
                'type'              => 'checkbox',
                'default'           => 'no',
                'desc'              => __( 'Send email to admin on every new customer account registration', 'woocommerce-b2b' ),
                'desc_tip'          => __( 'When a customer create an account, an email is send to admin to inform him', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_registration_notice'
            ),

            // Default group assignment
            array(
                'name'              => __( 'Default group', 'woocommerce-b2b' ),
                'type'              => 'select',
                'default'           => '0',
                'class'             => 'wc-enhanced-select',
                'id'                => 'wcb2b_default_group',
                'desc'              => __( 'Assign automatically customer to a group on registration', 'woocommerce-b2b' ),
                'options'           => array_reduce( $groups->posts, function( $result, $item ) {
                    $item = (array)$item;
                    $result[$item['ID']] = $item['post_title'];
                    return $result;
                }, array(
                    '0'      => __( '-- None --', 'woocommerce-b2b' ),
                    'choose' => __( '-- Allow to choose --', 'woocommerce-b2b' )
                ) )
            ),

            // Roles
            array(
                'name'              => __( 'Roles to consider as customer', 'woocommerce-b2b' ),
                'type'              => 'multiselect',
                'default'           => 'null',
                'class'             => 'wc-enhanced-select',
                'desc'              => __( 'Extends functions reserved for users with "customer" role to other roles.', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_has_role_customer',
                'options'           => array_reduce( array_keys( $roles ), function( $result, $role ) use ( $roles ) {
                    $result[$role] = $roles[$role];
                    return $result;
                } )
            ),

            // Change group
            array(
                'name'              => __( 'Automatic group change', 'woocommerce-b2b' ),
                'type'              => 'wcb2b_table',
                'default'           => array(),
                'class'             => 'wc-enhanced-select',
                'desc'              => __( 'Enable the automatic movement of users between groups based on their total purchase amounts.', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_automatic_group_change'
            ),
            
            array(
                 'type'             => 'sectionend'
            ),

            array(
                'name'    => __( 'Endpoints settings', 'woocommerce-b2b' ),
                'type'     => 'title',
                'desc'     => __( 'Endpoints are appended to your page URLs to handle specific actions on the my-account pages. They should be unique.', 'woocommerce-b2b' )
            ),

            // Enable quotations
            array(
                'name'              => __( 'Enable quotations', 'woocommerce-b2b' ),
                'type'              => 'checkbox',
                'default'           => 'no',
                'desc'              => __( 'Enable quotations endpoint in my-account page', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_enable_quotations'
            ),

            array(
                'name'              => __( 'Quotation endpoint', 'woocommerce-b2b' ),
                'type'              => 'text',
                'default'           => 'quotations',
                'desc_tip'          => __( 'Endpoint for the "My account → Quotations" page.', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_account_quotations_endpoint'
            ),
            array(
                'name'              => __( 'Quotation validity', 'woocommerce-b2b' ),
                'type'              => 'number',
                'default'           => 0,
                'desc_tip'          => __( 'Quotation validity days (set 0 to give no limit).', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_quotation_validity_days'
            ),

            // Enable saved carts
            array(
                'name'              => __( 'Enable saved carts', 'woocommerce-b2b' ),
                'type'              => 'checkbox',
                'default'           => 'no',
                'desc'              => __( 'Enable saved carts endpoint in my-account page', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_enable_saved_carts'
            ),

            array(
                'name'              => __( 'Saved carts endpoint', 'woocommerce-b2b' ),
                'type'              => 'text',
                'default'           => 'saved-carts',
                'desc_tip'          => __( 'Endpoint for the "My account → Saved carts" page.', 'woocommerce-b2b' ),
                'id'                => 'wcb2b_account_saved_carts_endpoint'
            ),

            array(
                'type'    => 'sectionend'
            )
        ) );
        return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
    }

    /**
     * Output the settings
     */
    public function output() {
        global $current_section, $hide_save_button;
        $hide_save_button = true;

        $chunks = array(
            'html',
            'admin',
            'settings',
            empty( $current_section ) ? 'general' : $current_section
        );
        $page = implode('-', array_filter($chunks));

        include_once WCB2B_ABSPATH . 'includes/views/settings/html-admin-settings.php';
    }

    /**
     * Save settings
     */
    public function save() {
        $settings = $this->get_settings();
        WC_Admin_Settings::save_fields( $settings );
        flush_rewrite_rules();
    }

    /**
     * Display option table
     */
    public function automatic_group_change_option_template( $option ) {
        $rules = $option['value'];
        $groups = wcb2b_get_groups()->posts;

        include_once WCB2B_ABSPATH . 'includes/views/settings/html-admin-settings-automatic_group_change.php';
    }

    /**
     * Sanitize option value
     */
    public function automatic_group_change_option_sanitize( $value, $option, $raw_value ) {
        if ( is_array( $value ) ) {
            usort( $value, function ( $a, $b ) {
                return $b['limit'] - $a['limit'];
            } );
        } else {
            $value = array();
        }
        return $value;
    }

}

return new WCB2B_Settings();