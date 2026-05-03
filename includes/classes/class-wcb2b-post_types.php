<?php

/**
 * WooCommerce B2B Post types set-up Class
 *
 * @version 4.0.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Post_Types Class
 */
class WCB2B_Post_Types {

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
        add_action( 'init', array( $this, 'register_group_post_type' ) );
        add_filter( 'post_row_actions', array( $this, 'row_actions' ), 100, 2 );
        add_action( 'add_meta_boxes_wcb2b_group', array( $this, 'add_metabox' ) );
        add_action( 'save_post_wcb2b_group', array( $this, 'save_meta_box_fields' ) );
        add_filter( 'manage_wcb2b_group_posts_columns', array( $this, 'render_columns' ) );
        add_filter( 'manage_wcb2b_group_posts_custom_column', array( $this, 'render_columns_value' ), 10, 2 );
        add_filter( 'manage_edit-wcb2b_group_sortable_columns', array( $this, 'make_columns_sortable' ) );
        add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ), 10, 2 );
        add_action( 'bulk_edit_custom_box',  array( $this, 'quick_edit_fields' ), 10, 2 );
        add_filter( 'post_row_actions', array( $this, 'remove_guest_group_delete' ), 10, 2 );
        add_filter( 'user_has_cap', array( $this, 'prevent_guest_group_delete' ), 10, 3 );
        add_action( 'transition_post_status', array( $this, 'updated_group_status' ), 10, 3 );
        add_action( 'trash_wcb2b_group', array( $this, 'deleted_group' ) );
    }

    /**
     * Register custom group post type
     */
    public function register_group_post_type() {
        $labels = array(
            'name'                  => __( 'Groups', 'woocommerce-b2b' ),
            'singular_name'         => __( 'Group', 'woocommerce-b2b' ),
            'all_items'             => __( 'All Groups', 'woocommerce-b2b' ),
            'menu_name'             => _x( 'Groups', 'Admin menu name', 'woocommerce-b2b' ),
            'add_new'               => __( 'Add New', 'woocommerce-b2b' ),
            'add_new_item'          => __( 'Add new group', 'woocommerce-b2b' ),
            'edit'                  => __( 'Edit', 'woocommerce-b2b' ),
            'edit_item'             => __( 'Edit group', 'woocommerce-b2b' ),
            'new_item'              => __( 'New group', 'woocommerce-b2b' ),
            'view_item'             => __( 'View group', 'woocommerce-b2b' ),
            'view_items'            => __( 'View groups', 'woocommerce-b2b' ),
            'search_items'          => __( 'Search groups', 'woocommerce-b2b' ),
            'not_found'             => __( 'No groups found', 'woocommerce-b2b' ),
            'not_found_in_trash'    => __( 'No groups found in trash', 'woocommerce-b2b' ),
            'parent'                => __( 'Parent group', 'woocommerce-b2b' ),
            'featured_image'        => __( 'Group image', 'woocommerce-b2b' ),
            'set_featured_image'    => __( 'Set group image', 'woocommerce-b2b' ),
            'remove_featured_image' => __( 'Remove group image', 'woocommerce-b2b' ),
            'use_featured_image'    => __( 'Use as group image', 'woocommerce-b2b' ),
            'insert_into_item'      => __( 'Insert into group', 'woocommerce-b2b' ),
            'uploaded_to_this_item' => __( 'Uploaded to this group', 'woocommerce-b2b' ),
            'filter_items_list'     => __( 'Filter groups', 'woocommerce-b2b' ),
            'items_list_navigation' => __( 'Groups navigation', 'woocommerce-b2b' ),
            'items_list'            => __( 'Groups list', 'woocommerce-b2b' )
        );
        $args = array(
            'label'                 => __( 'Group', 'woocommerce-b2b' ),
            'description'           => __( 'This is where you can add new groups to your customers.', 'woocommerce-b2b' ),
            'labels'                => $labels,
            'supports'              => array( 'title' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'users.php',
            'menu_position'         => 99,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'product',
            'map_meta_cap'          => true
        );
        register_post_type( 'wcb2b_group', $args );
    }

    /**
     * Add Group ID to list table rows
     */
    public function row_actions( $actions, $post ) {
        if ( 'wcb2b_group' === $post->post_type ) {
            return array_merge( array( 'id' => sprintf( __( 'ID: %d', 'woocommerce-b2b' ), $post->ID ) ), $actions );
        }
        return $actions;
    }

    /**
     * Add group post type metabox with custom fields
     * 
     * @param WP_Post $post The current WP_Post object
     */
    public function add_metabox( $post ) {
        /************************
         * Prices & Tax metabox *
         ************************/
        add_meta_box( 'wcb2b_group-prices+tax-meta_box', __( 'Price & Tax settings', 'woocommerce-b2b' ), function( $post ) {
            // Make sure the form request comes from WordPress
            wp_nonce_field( basename( __FILE__ ), 'wcb2b_group-prices+tax-nonce' );
            // Init options
            $metaboxes = array();

            ///////////////////
            // Manage prices //
            ///////////////////
            
            $section = 'manage-prices';
            $metaboxes[$section]['title'] = esc_html__( 'Manage prices', 'woocommerce-b2b' );

            // Hide prices //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_hide_prices', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Hide prices', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Hide prices for all products', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'If enabled, price is replaced by a message linked to login page for customers in this group.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_hide_prices" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );
            $gateway_enabled = ! empty( array_filter( WC()->payment_gateways->payment_gateways(), function( $payment ) {
                return $payment->enabled && 'wcb2b_quotation_gateway' == $payment->id;
            } ) );
            $disabled = !( $current && $gateway_enabled ); // Used by next options!

            // Allow quotations with hidden prices
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_quotations_with_hidden_prices', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Allow quotations', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Allow quotations even if prices are hidden.', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Please be sure quotations payment gateway is enabled.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_quotations_with_hidden_prices" value="1" data-enabled="%s" %s %s />',
                    $gateway_enabled,
                    checked( $current, true, false ),
                    $disabled ? 'disabled' : false
                ),
                'full'      => false
            );

            // Price rules //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_price_rule', true );
            $current = esc_attr( $current );
            // Build options
            $options = array(
                'single' => esc_html__( 'Show prices assigned to single product', 'woocommerce-b2b' ),
                'global' => esc_html__( 'Show default prices discounted by customer group percentage', 'woocommerce-b2b' ),
                'both'   => esc_html__( 'Show prices assigned to single product discounted by customer group percentage', 'woocommerce-b2b' )
            );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Price rules', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Choose how to manage the different price lists. For option (1) you can set the prices in each product, for options (2) and (3) you can set the percentage discount below.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<p><select name="wcb2b_group_price_rule">%s</select></p>',
                    implode( '', array_map( function( $key, $value ) use ( $current ) {
                        return sprintf( '<option value="%s" %s>%s</option>',
                            $key,
                            selected( $key, $current, false ),
                            $value
                        );
                    }, array_keys( $options ), array_values( $options ) ) )
                ),
                'full'      => false
            );
            $disabled = $current === 'single'; // Used by next options!

            // Discount percentage //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_discount', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Global discount (%)', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'This should be a number with up to 2 decimal places, with (.) as decimal separator.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<p><input type="text" name="wcb2b_group_discount" value="%s" pattern="[0-9]+([\.][0-9]+)?" %s /></p>',
                    $current,
                    $disabled ? 'disabled' : false
                ),
                'full'      => false
            );

            // Price suffix //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_price_suffix', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Price suffix', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Define text to show after your product prices. This could be, for example, "inc. Vat" to explain your pricing. You can also have prices substituted here using one of the following: {price_including_tax}, {price_excluding_tax}.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<p><input type="text" name="wcb2b_group_price_suffix" value="%s" /></p>',
                    $current
                ),
                'full'      => false
            );

            //////////////////
            // Manage taxes //
            //////////////////
            
            $section = 'manage-taxes';
            $metaboxes[$section]['title'] = esc_html__( 'Manage taxes', 'woocommerce-b2b' );

            // Display prices //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_tax_display', true );
            $current = esc_attr( $current );
            // Build options
            $options = array(
                0      => esc_html__( '-- As globally defined --', 'woocommerce-b2b' ),
                'incl' => esc_html__( 'Including tax', 'woocommerce-b2b' ),
                'excl' => esc_html__( 'Excluding tax', 'woocommerce-b2b' ),
            );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Display prices', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => false,
                'field'     => sprintf( '<p><select name="wcb2b_group_tax_display">%s</select></p>',
                    implode( '', array_map( function( $key, $value ) use ( $current ) {
                        return sprintf( '<option value="%s" %s>%s</option>',
                            $key,
                            selected( $key, $current, false ),
                            $value
                        );
                    }, array_keys( $options ), array_values( $options ) ) )
                ),
                'full'      => false
            );

            // Tax exemption //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_tax_exemption', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Tax exemption', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Enable tax exemption', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'If enabled, customers in this group can purchase with no taxes.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_tax_exemption" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );
            ///////////////
            // Rendering //
            ///////////////

            include WCB2B_ABSPATH . 'includes/views/html-admin-group-metabox.php';
        }, 'wcb2b_group', 'normal', 'high' );

        /***************************
         * Cart & Checkout metabox *
         ***************************/
        add_meta_box( 'wcb2b_group-cart+checkout-meta_box', __( 'Cart & Checkout settings', 'woocommerce-b2b' ), function( $post ) {
            // Make sure the form request comes from WordPress
            wp_nonce_field( basename( __FILE__ ), 'wcb2b_group-cart+checkout-nonce' );
            // Init options
            $metaboxes = array();

            ///////////////////
            // Manage totals //
            ///////////////////
            
            $section = 'manage-totals';
            $metaboxes[$section]['title'] = esc_html__( 'Manage totals', 'woocommerce-b2b' );

            // Packaging fee //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_packaging_fee', true );
            if ( '' == $current ) {
                $current = array(
                    'type' => 'amount',
                    'value' => 0
                );
            }
            // Build options
            $options = array(
                'amount'  => esc_html__( 'Amount', 'woocommerce-b2b' ),
                'percent' => esc_html__( 'Percentage', 'woocommerce-b2b' ),
            );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Package fee', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Add a packaging fee to checkout. Fee amount should be a number with up to 2 decimal places, with (.) as decimal separator.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<p>%s%s</p>',
                    sprintf( '<select name="wcb2b_group_packaging_fee[type]">%s</select>',
                        implode( '', array_map( function( $key, $value ) use ( $current ) {
                            return sprintf( '<option value="%s" %s>%s</option>',
                                $key,
                                selected( $key, $current['type'], false ),
                                $value
                            );
                        }, array_keys( $options ), array_values( $options ) ) )
                    ),
                    sprintf( '<input type="text" name="wcb2b_group_packaging_fee[value]" value="%s" pattern="[0-9]+([\.][0-9]+)?" placeholder="%s" />', $current['value'], esc_html__( 'Fee amount', 'woocommerce-b2b' ) )
                ),
                'full'      => false
            );

            //////////////////////
            // Manage purchases //
            //////////////////////

            $section = 'manage-purchases';
            $metaboxes[$section]['title'] = esc_html__( 'Manage purchases', 'woocommerce-b2b' );

            // Catalog mode //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_catalog_mode', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Catalog mode', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Use shop pages as catalog', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'If enabled, remove add to cart and purchase capabilities, with visible prices.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_catalog_mode" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Min purchase amount //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_min_purchase_amount', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Min purchase amount', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Customers can purchase only if cart amount is greater than this value.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<p><input type="number" name="wcb2b_group_min_purchase_amount" value="%s" step="1" min="0" /></p>',
                    $current
                ),
                'full'      => false
            );
            $disabled = (int)$current === 0; // Used by next options!

            // Min purchase message in cart //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_minpurchase_alert', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Min purchase alert', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Enable min purchase alert in cart page', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Display a message in the shopping cart page to alert that minimum amount isn\'t reached yet.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_minpurchase_alert" value="1" %s %s />',
                    checked( $current, true, false ),
                    $disabled ? 'disabled' : false
                ),
                'full'      => false
            );

            // Remove checkout button in cart //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_minpurchase_button', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Min purchase checkout button', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Disable checkout button in cart page', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Remove "Proceed to checkout" button in shopping cart if the minimum amount isn\'t reached yet.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_minpurchase_button" value="1" %s %s />',
                    checked( $current, true, false ),
                    $disabled ? 'disabled' : false
                ),
                'full'      => false
            );

            // Save cart button //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_save_cart', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Save cart button', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Enable save cart button in cart page', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Allow customers to save their cart for later.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_save_cart" value="1" %s %s />',
                    checked( $current, true, false ),
                    $post->ID === get_option( 'wcb2b_guest_group' ) ? 'disabled' : false
                ),
                'full'      => false
            );

            // Show coupons in cart //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_show_coupon', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Show coupons', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Enable public coupons list in cart page', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'List available public coupons in cart page for this group.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_show_coupon" value="1" %s />',
                    checked( $current, true, false ),
                ),
                'full'      => false
            );

            // New order additional recipients //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_email_recipients', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'New order email recipients', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Insert additional email recipients (separated by comma) you want to receive new order email copy.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<p><input type="text" name="wcb2b_group_email_recipients" value="%s" /></p>',
                    $current
                ),
                'full'      => false
            );

            // Payment methods //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_gateways', true ) ?: array();
            array_walk_recursive( $current, function( &$item, $key ) {
                $item = esc_attr( $item );
            } );
            // Build options
            $options = array_filter( WC()->payment_gateways->payment_gateways(), function( $payment ) {
                return $payment->enabled;
            } );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Disabled payment methods', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => false,
                'field'     => sprintf( '%s',
                    implode( '', array_map( function( $key, $payment ) use ( $current ) {
                        return sprintf( '<p><input type="checkbox" name="wcb2b_group_gateways[]" value="%s" %s /> %s</p>',
                            $payment->id,
                            checked( in_array( $payment->id, $current ), true, false ),
                            $payment->title
                        );
                    }, array_keys( $options ), array_values( $options ) ) )
                ),
                'full'      => false
            );

            // Shipping methods //

            // Rates
            $shipping_rates = array();
            $zones = WC_Shipping_Zones::get_zones();
            array_walk( $zones, function( $zone ) use ( &$shipping_rates ) {
                foreach ( $zone['shipping_methods'] as $zone_shipping_methods ) {
                    $shipping_rates[$zone_shipping_methods->id][] = array(
                        'id'    => sprintf( '%s:%s', $zone_shipping_methods->id, $zone_shipping_methods->instance_id ),
                        'title' => $zone_shipping_methods->title,
                        'zone'  => $zone['zone_name']
                    );
                }
            } );
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_shippings', true ) ?: array();
            array_walk_recursive($current, function(&$item, $key) {
                $item = esc_attr($item);
            });
            // Build options
            $options = array_filter( WC()->shipping->get_shipping_methods(), function( $shipping ) {
                return apply_filters( 'wcb2b_force_shipping_methods_display', false ) || $shipping->is_enabled();
            } );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Disabled shipping methods', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => false,
                'field'     => sprintf( '%s',
                    implode( '', array_map( function( $key, $shipping ) use ( $current, $shipping_rates ) {
                        $field = sprintf( '<p class="wcb2b_shipping_method"><input type="checkbox" name="wcb2b_group_shippings[]" value="%s" %s /> %s</p>',
                            $shipping->id,
                            checked( in_array( $shipping->id, $current ), true, false ),
                            $shipping->method_title
                        );
                        $field = apply_filters_deprecated( 'wcb2b_admin_shipping_methods', array( $field, $key, $shipping, $current), '3.0.6' );
                        // Sub-methods
                        if ( isset( $shipping_rates[$shipping->id] ) ) {
                            foreach ( $shipping_rates[$shipping->id] as $shipping_rate_key => $shipping_rate ) {
                                $field .= sprintf( '<p class="wcb2b_shipping_rate" data-parent="%s"><input type="checkbox" name="wcb2b_group_shippings[]" class="%s" value="%s" %s /> %s <em>%s</em></p>',
                                    $shipping->id,
                                    in_array( $shipping->id, $current ) ? 'disabled' : false,
                                    $shipping_rate['id'],
                                    checked( in_array( $shipping_rate['id'], $current ), true, false ),
                                    $shipping_rate['title'],
                                    sprintf( esc_html__( 'Defined in "%s" zone', 'woocommerce-b2b' ), $shipping_rate['zone'] )
                                );
                            }
                        }
                        return $field;
                    }, array_keys( $options ), array_values( $options ) ) )
                ),
                'full'      => false
            );

            ////////////////////////////////////
            // Manage additional informations //
            ////////////////////////////////////
            
            $section = 'manage-informations';
            $metaboxes[$section]['title'] = esc_html__( 'Manage additional informations', 'woocommerce-b2b' );

            // Terms & Conditions //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_terms_conditions', true );
            $current = wp_kses_post( $current, wp_kses_allowed_html( 'post' ) );
            // Editor
            ob_start();
            wp_editor(
                $current,
                'wcb2b_group_terms_conditions',
                array(
                    'textarea_name' => 'wcb2b_group_terms_conditions',
                    'media_buttons' => false,
                    'tinymce'       => apply_filters( 'wcb2b_group_terms_conditions_editor', array(
                        'toolbar1'      => 'bold,italic,underline,separator,alignleft,aligncenter,alignright,separator,link,unlink,undo,redo',
                        'toolbar2'      => '',
                        'toolbar3'      => '',
                    ) )
                )
            );
            $field = ob_get_clean();
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Terms&Conditions', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => sprintf( esc_html__( 'You can add this content by using the shortcode %s.', 'woocommerce-b2b' ),
                    '<strong>[wcb2bgrouptermsconditions]</strong>'
                ),
                'field'     => sprintf( '%s', $field ),
                'full'      => true
            );

            ///////////////
            // Rendering //
            ///////////////

            include WCB2B_ABSPATH . 'includes/views/html-admin-group-metabox.php';
        }, 'wcb2b_group', 'normal', 'high' );

        /***************************
         * Shop & Products metabox *
         ***************************/
        add_meta_box( 'wcb2b_group-shop+products-meta_box', __( 'Shop & Products settings', 'woocommerce-b2b' ), function( $post ) {
            // Make sure the form request comes from WordPress
            wp_nonce_field( basename( __FILE__ ), 'wcb2b_group-shop+products-nonce' );
            // Init options
            $metaboxes = array();

            ////////////////////////////////////
            // Manage additional informations //
            ////////////////////////////////////
            
            $section = 'manage-informations';
            $metaboxes[$section]['title'] = esc_html__( 'Manage additional informations', 'woocommerce-b2b' );

            // Delivery time //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_show_deliverytime', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Show delivery time', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Enable delivery time displaying', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'If enabled, customers in this group can see the min/max days to delivery as set in each product.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_show_deliverytime" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Show shipping class //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_show_shipping_class', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Shipping class', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Display shipping class in the product page', 'woocommerce-b2b' ),
                'helper'    => false,
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_show_shipping_class" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Show barcode //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_show_barcode', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Barcode', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Display barcode in the product page', 'woocommerce-b2b' ),
                'helper'    => false,
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_show_barcode" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Show RRP //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_show_rrp', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'RRP', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Show recommended retail price (RRP)', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Show default product regular price as recommended retail price (RRP).', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_show_rrp" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Already bought message //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_already_bought', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Already bought', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Enable "Already bought" message in product page', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Show a warning if the current customer has already purchased the product before.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_already_bought" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Show total sales //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_show_sales', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Total sales', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Show product total sales in product page', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Displays a notice indicating the number of times the product has been sold.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_show_sales" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Show shippings tab //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_shippings_tab', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Shippings tab', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Show shippings tab in product page', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Shows an additional tab on the product page which describes the active shipping methods based on the current customer\'s group.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_shippings_tab" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Show purchase history tab //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_purchase_history_tab', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Purchase history tab', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Show purchase history tab in product page', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Shows an additional tab on the product page listing customer previous purchases for this product.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_purchase_history_tab" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            ///////////////
            // Rendering //
            ///////////////

            include WCB2B_ABSPATH . 'includes/views/html-admin-group-metabox.php';
        }, 'wcb2b_group', 'normal', 'high' );

        /******************************************
         * Login/Registration & MyAccount metabox *
         ******************************************/
        add_meta_box( 'wcb2b_group-login+account-meta_box', __( 'Login/Registration & MyAccount settings', 'woocommerce-b2b' ), function( $post ) {
            // Make sure the form request comes from WordPress
            wp_nonce_field( basename( __FILE__ ), 'wcb2b_group-login+account-nonce' );
            // Init options
            $metaboxes = array();

            ///////////////////
            // Manage fields //
            ///////////////////
            
            $section = 'manage-fields';
            $metaboxes[$section]['title'] = esc_html__( 'Manage fields', 'woocommerce-b2b' );

            // Invoice email //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_add_invoice_email', true );
            $current = esc_attr( $current );
            // Build options
            $options = array(
                'hidden'    => esc_html__( 'Hidden', 'woocommerce-b2b' ),
                'optional'  => esc_html__( 'Optional', 'woocommerce-b2b' ),
                'required'  => esc_html__( 'Required', 'woocommerce-b2b' )
            );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Email address for invoices', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Add an additional field to registration form dedicated to this group, to allow customers to communicate email address where they prefer to receive invoices.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<p><select name="wcb2b_group_add_invoice_email">%s</select></p>',
                    implode( '', array_map( function( $key, $value ) use ( $current ) {
                        return sprintf( '<option value="%s" %s>%s</option>',
                            $key,
                            selected( $key, $current, false ),
                            $value
                        );
                    }, array_keys( $options ), array_values( $options ) ) )
                ),
                'full'      => false
            );

            // VAT number //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_add_vatnumber', true );
            $current = esc_attr( $current );
            // Build options
            $options = array(
                'hidden'    => esc_html__( 'Hidden', 'woocommerce-b2b' ),
                'optional'  => esc_html__( 'Optional', 'woocommerce-b2b' ),
                'required'  => esc_html__( 'Required', 'woocommerce-b2b' )
            );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'VAT number', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Add an additional field to registration form dedicated to this group, to allow customers to communicate their VAT number', 'woocommerce-b2b' ),
                'field'     => sprintf( '<p><select name="wcb2b_group_add_vatnumber">%s</select></p>',
                    implode( '', array_map( function( $key, $value ) use ( $current ) {
                        return sprintf( '<option value="%s" %s>%s</option>',
                            $key,
                            selected( $key, $current, false ),
                            $value
                        );
                    }, array_keys( $options ), array_values( $options ) ) )
                ),
                'full'      => false
            );
            $disabled = $current === 'hidden'; // Used by next options!

            // VIES validation //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_vies_validation', true );
            $current = esc_attr( $current );
            // Build options
            $options = array(
                'disabled' => esc_html__( 'Disabled', 'woocommerce-b2b' ),
                'strict'   => esc_html__( 'Enabled (Strict - block registration if error occurs)', 'woocommerce-b2b' ),
                'lax'      => esc_html__( 'Enabled (Lax - don\'t block registration if error occurs)', 'woocommerce-b2b' )
            );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'VIES validation', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Enable VIES validation on this group in registration', 'woocommerce-b2b' ),
                'field'     => sprintf( '<p><select name="wcb2b_group_vies_validation" %s>%s</select></p>',
                    $disabled ? 'disabled' : false,
                    implode( '', array_map( function( $key, $value ) use ( $current ) {
                        return sprintf( '<option value="%s" %s>%s</option>',
                            $key,
                            selected( $key, $current, false ),
                            $value
                        );
                    }, array_keys( $options ), array_values( $options ) ) )
                ),
                'full'      => false
            );

            // Business certificate //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_add_business_certificate', true );
            $current = esc_attr( $current );
            // Build options
            $options = array(
                'hidden'    => esc_html__( 'Hidden', 'woocommerce-b2b' ),
                'optional'  => esc_html__( 'Optional', 'woocommerce-b2b' ),
                'required'  => esc_html__( 'Required', 'woocommerce-b2b' )
            );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Business certificate', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Add an additional field to registration form dedicated to this group and in "My account -> Account details" page, to allow customers to upload business certificate.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<p><select name="wcb2b_group_add_business_certificate">%s</select></p>',
                    implode( '', array_map( function( $key, $value ) use ( $current ) {
                        return sprintf( '<option value="%s" %s>%s</option>',
                            $key,
                            selected( $key, $current, false ),
                            $value
                        );
                    }, array_keys( $options ), array_values( $options ) ) )
                ),
                'full'      => false
            );

            /////////////////////
            // Manage accounts //
            /////////////////////
            
            $section = 'manage-accounts';
            $metaboxes[$section]['title'] = esc_html__( 'Manage accounts', 'woocommerce-b2b' );

            // Extend registration fields //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_extend_registration_fields', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Extend registration fields', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Add billing fields to registration form dedicated to this group', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'To show registration form, you need to enable the option "Allow customers to create an account on the \'My account\' page" in "Accounts & Privacy" tab under WooCommerce settings', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_extend_registration_fields" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Moderate registration //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_moderate_registration', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Moderate registration', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Enable login only after admin approval', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Customers belonging to this group will only be able to login if their account has been approved by an administrator.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_moderate_registration" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Blocked billing address //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_blocked_billing_address', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Blocked billing address', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Disable billing address change', 'woocommerce-b2b' ),
                'helper'    => esc_html__( 'Disable the ability to change your billing address for customers in this group.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_blocked_billing_address" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            ////////////////////////////////////
            // Manage additional informations //
            ////////////////////////////////////
            
            $section = 'manage-informations';
            $metaboxes[$section]['title'] = esc_html__( 'Manage additional informations', 'woocommerce-b2b' );

            // Unpaid amount //
            
            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_show_unpaid', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Unpaid amount', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Show unpaid total orders amount in customer own account area', 'woocommerce-b2b' ),
                'helper'    => false,
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_show_unpaid" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Show customer group //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_show_groupname', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Customer group', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Show assigned group in customer own account area', 'woocommerce-b2b' ),
                'helper'    => false,
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_show_groupname" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Show customer discount % //

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_show_discount_myaccount', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Discount % in myaccount', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Customers can view discount amount assigned to them in their own account area', 'woocommerce-b2b' ),
                'helper'    => false,
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_show_discount_myaccount" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            // Retrieve option current value
            $current = get_post_meta( $post->ID, 'wcb2b_group_show_discount_product', true );
            $current = esc_attr( $current );
            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Discount % in products', 'woocommerce-b2b' ),
                'desc'      => esc_html__( 'Customers can view discount amount assigned to them in single product page', 'woocommerce-b2b' ),
                'helper'    => false,
                'field'     => sprintf( '<input type="checkbox" name="wcb2b_group_show_discount_product" value="1" %s />',
                    checked( $current, true, false )
                ),
                'full'      => false
            );

            ///////////////
            // Rendering //
            ///////////////

            include WCB2B_ABSPATH . 'includes/views/html-admin-group-metabox.php';
        }, 'wcb2b_group', 'normal', 'high' );

        /**********************
         * Visibility metabox *
         **********************/
        add_meta_box( 'wcb2b_group-visibility-meta_box', __( 'Visibility summary', 'woocommerce-b2b' ), function( $post ) {
            // Init options
            $metaboxes = array();

            ////////////////////////////////////
            // Manage additional informations //
            ////////////////////////////////////
            
            $section = 'manage-informations';
            $metaboxes[$section]['title'] = esc_html__( 'Manage additional informations', 'woocommerce-b2b' );

            // Product categories //

            // Display fields
            $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                $terms = array();
            }
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Product categories visibility', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => false,
                'field'     => sprintf( '%s',
                    implode( '', array_map( function( $term ) use ( $post ) {
                        $current = get_term_meta( $term->term_id, 'wcb2b_group_visibility', true ) ?: array();
                        array_walk_recursive( $current, function( &$item, $key ) {
                            $item = esc_attr( $item );
                        } );
                        return sprintf( '<p class="visibility-%s"><a href="%s"><span class="dashicons dashicons-external"></span></a> %s</p>',
                            in_array( $post->ID, $current ) ? 'yes' : 'no',
                            get_edit_term_link( $term->term_id, 'product_cat' ),
                            $term->name
                        );
                    }, $terms ) )
                ),
                'full'      => false
            );

            // Product brands //

            // Display fields
            $terms = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false ) );
            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                $terms = array();
            }
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Product brands visibility', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => false,
                'field'     => sprintf( '%s',
                    implode( '', array_map( function( $term ) use ( $post ) {
                        $current = get_term_meta( $term->term_id, 'wcb2b_group_visibility', true ) ?: array();
                        array_walk_recursive( $current, function( &$item, $key ) {
                            $item = esc_attr( $item );
                        } );
                        return sprintf( '<p class="visibility-%s"><a href="%s"><span class="dashicons dashicons-external"></span></a> %s</p>',
                            in_array( $post->ID, $current ) ? 'yes' : 'no',
                            get_edit_term_link( $term->term_id, 'product_brand' ),
                            $term->name
                        );
                    }, $terms ) )
                ),
                'full'      => false
            );

            // Pages //

            // Display fields
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Pages visibility', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => false,
                'field'     => sprintf( '%s',
                    implode( '', array_map( function( $page ) use ( $post ) {
                        $current = get_post_meta( $page->ID, 'wcb2b_group_visibility', true ) ?: array();
                        array_walk_recursive( $current, function( &$item, $key ) {
                            $item = esc_attr( $item );
                        } );
                        return sprintf( '<p class="visibility-%s"><a href="%s"><span class="dashicons dashicons-external"></span></a> %s</p>',
                            in_array( $post->ID, $current ) ? 'yes' : 'no',
                            get_edit_post_link( $page->ID ),
                            get_the_title( $page->ID )
                        );
                    }, get_pages( array( 'exclude' => wcb2b_get_always_visible_pages() ) ) ) )
                ),
                'full'      => false
            );

            ///////////////
            // Rendering //
            ///////////////

            include WCB2B_ABSPATH . 'includes/views/html-admin-group-metabox.php';
        }, 'wcb2b_group', 'normal', 'high' );

        /**********************
         * Shortcodes metabox *
         **********************/
        add_meta_box( 'wcb2b_group-shortcodes-meta_box', __( 'Shortcodes summary', 'woocommerce-b2b' ), function( $post ) {
            // Init options
            $metaboxes = array();

            ////////////////////////////////////
            // Manage additional informations //
            ////////////////////////////////////
            
            $section = 'manage-informations';
            $metaboxes[$section]['title'] = esc_html__( 'Manage additional informations', 'woocommerce-b2b' );

            // Shortcodes //

            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Restricted contents', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Allow content visibility only to this group.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<code>[wcb2brestrictedcontent allowed="%s"][/wcb2brestrictedcontent]</code>', $post->ID ),
                'full'      => false
            );
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'B2B login/registration forms', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Allow to add a login/registration form dedicated to this group.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<code>[wcb2bloginform wcb2b_group="%s"]</code>', $post->ID ),
                'full'      => false
            );
            $metaboxes[$section]['options'][] = array(
                'label'     => esc_html__( 'Terms&Conditions', 'woocommerce-b2b' ),
                'desc'      => false,
                'helper'    => esc_html__( 'Allow to add dedicated Terms&Conditions contents according to customer group.', 'woocommerce-b2b' ),
                'field'     => sprintf( '<code>[wcb2bgrouptermsconditions]</code>', $post->ID ),
                'full'      => false
            );

            ///////////////
            // Rendering //
            ///////////////

            include WCB2B_ABSPATH . 'includes/views/html-admin-group-metabox.php';
        }, 'wcb2b_group', 'normal', 'high' );
    }

    /**
     * Store custom field meta box data
     * 
     * @param int $post_id The current post ID
     */
    public function save_meta_box_fields( $post_id ) {
        // Only ShopManager can edit customer group
        if ( current_user_can( 'manage_woocommerce' ) ) {

            // Check bulk edit nonce
            if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-posts' ) ) {
                // Store hide prices
                $hide_prices = $_REQUEST['wcb2b_group_hide_prices-bulk'] ?? -1;
                if ( '-1' !== $hide_prices ) {
                    update_post_meta( $post_id, 'wcb2b_group_hide_prices', intval( $hide_prices ) );
                }
                // Store quotations with hidden prices flag
                $quotations_with_hidden_prices = $_REQUEST['wcb2b_group_quotations_with_hidden_prices-bulk'] ?? -1;
                if ( '-1' !== $quotations_with_hidden_prices ) {
                    update_post_meta( $post_id, 'wcb2b_group_quotations_with_hidden_prices', intval( $quotations_with_hidden_prices ) );
                }
                // Store group price rules exemption flag
                $price_rule = $_REQUEST['wcb2b_group_price_rule-bulk'] ?? -1;
                if ( '-1' !== $price_rule ) {
                    update_post_meta( $post_id, 'wcb2b_group_price_rule', $price_rule );
                }
                // Store group tax exemption flag
                $tax_exemption = $_REQUEST['wcb2b_group_tax_exemption-bulk'] ?? -1;
                if ( '-1' !== $tax_exemption ) {
                    update_post_meta( $post_id, 'wcb2b_group_tax_exemption', intval( $tax_exemption ) );
                }
                // Store tax display
                $tax_display = $_REQUEST['wcb2b_group_tax_display-bulk'] ?? -1;
                if ( '-1' !== $tax_display ) {
                    update_post_meta( $post_id, 'wcb2b_group_tax_display', wp_kses_post( $tax_display ) );
                }
                // Store add invoice email
                $add_invoice_email = $_REQUEST['wcb2b_group_add_invoice_email-bulk'] ?? -1;
                if ( '-1' !== $add_invoice_email ) {
                    update_post_meta( $post_id, 'wcb2b_group_add_invoice_email', $add_invoice_email );
                }
                // Store add VAT number
                $add_vatnumber = $_REQUEST['wcb2b_group_add_vatnumber-bulk'] ?? -1;
                if ( '-1' !== $add_vatnumber ) {
                    update_post_meta( $post_id, 'wcb2b_group_add_vatnumber', $add_vatnumber );
                }
                // Store VIES validation
                $vies_validation = $_REQUEST['wcb2b_group_vies_validation-bulk'] ?? -1;
                if ( '-1' !== $vies_validation ) {
                    update_post_meta( $post_id, 'wcb2b_group_vies_validation', $vies_validation );
                }
                // Store add business certificate
                $add_business_certificate = $_REQUEST['wcb2b_group_add_business_certificate-bulk'] ?? -1;
                if ( '-1' !== $add_business_certificate ) {
                    update_post_meta( $post_id, 'wcb2b_group_add_business_certificate', $add_business_certificate );
                }
                // Store extend registration form
                $extend_registration_fields = $_REQUEST['wcb2b_group_extend_registration_fields-bulk'] ?? -1;
                if ( '-1' !== $extend_registration_fields ) {
                    update_post_meta( $post_id, 'wcb2b_group_extend_registration_fields', intval( $extend_registration_fields ) );
                }
                // Store moderate registration
                $moderate_registration = $_REQUEST['wcb2b_group_moderate_registration-bulk'] ?? -1;
                if ( '-1' !== $moderate_registration ) {
                    update_post_meta( $post_id, 'wcb2b_group_moderate_registration', intval( $moderate_registration ) );
                }
                // Store blocked billing address
                $blocked_billing_address = $_REQUEST['wcb2b_group_blocked_billing_address-bulk'] ?? -1;
                if ( '-1' !== $blocked_billing_address ) {
                    update_post_meta( $post_id, 'wcb2b_group_blocked_billing_address', intval( $blocked_billing_address ) );
                }
                // Store group delivery time flag
                $delivery_time = $_REQUEST['wcb2b_group_show_deliverytime-bulk'] ?? -1;
                if ( '-1' !== $delivery_time ) {
                    update_post_meta( $post_id, 'wcb2b_group_show_deliverytime', $delivery_time );
                }
                // Store show barcode
                $show_shipping_class = $_REQUEST['wcb2b_group_show_shipping_class-bulk'] ?? -1;
                if ( '-1' !== $show_shipping_class ) {
                    update_post_meta( $post_id, 'wcb2b_group_show_shipping_class', intval( $show_shipping_class ) );
                }
                // Store show barcode
                $show_barcode = $_REQUEST['wcb2b_group_show_barcode-bulk'] ?? -1;
                if ( '-1' !== $show_barcode ) {
                    update_post_meta( $post_id, 'wcb2b_group_show_barcode', intval( $show_barcode ) );
                }
                // Store show rrp
                $show_rrp = $_REQUEST['wcb2b_group_show_rrp-bulk'] ?? -1;
                if ( '-1' !== $show_rrp ) {
                    update_post_meta( $post_id, 'wcb2b_group_show_rrp', intval( $show_rrp ) );
                }
                // Store already bought
                $already_bought = $_REQUEST['wcb2b_group_already_bought-bulk'] ?? -1;
                if ( '-1' !== $already_bought ) {
                    update_post_meta( $post_id, 'wcb2b_group_already_bought', intval( $already_bought ) );
                }
                // Store show sales
                $show_sales = $_REQUEST['wcb2b_group_show_sales-bulk'] ?? -1;
                if ( '-1' !== $show_sales ) {
                    update_post_meta( $post_id, 'wcb2b_group_show_sales', intval( $show_sales ) );
                }
                // Store show shippings tab
                $shipping_tabs = $_REQUEST['wcb2b_group_shippings_tab-bulk'] ?? -1;
                if ( '-1' !== $shipping_tabs ) {
                    update_post_meta( $post_id, 'wcb2b_group_shippings_tab', intval( $shipping_tabs ) );
                }
                // Store show purchase history tab
                $purchase_history = $_REQUEST['wcb2b_group_purchase_history_tab-bulk'] ?? -1;
                if ( '-1' !== $purchase_history ) {
                    update_post_meta( $post_id, 'wcb2b_group_purchase_history_tab', intval( $purchase_history ) );
                }
                // Store show shippings tab
                $show_unpaid = $_REQUEST['wcb2b_group_show_unpaid-bulk'] ?? -1;
                if ( '-1' !== $show_unpaid ) {
                    update_post_meta( $post_id, 'wcb2b_group_show_unpaid', intval( $show_unpaid ) );
                }
                // Store show name tab
                $show_groupname = $_REQUEST['wcb2b_group_show_groupname-bulk'] ?? -1;
                if ( '-1' !== $show_groupname ) {
                    update_post_meta( $post_id, 'wcb2b_group_show_groupname', intval( $show_groupname ) );
                }
                // Store show discount % in myaccount tab
                $show_discount_myaccount = $_REQUEST['wcb2b_group_show_discount_myaccount-bulk'] ?? -1;
                if ( '-1' !== $show_discount_myaccount ) {
                    update_post_meta( $post_id, 'wcb2b_group_show_discount_myaccount', intval( $show_discount_myaccount ) );
                }
                // Store show discount % in products tab
                $show_discount_product = $_REQUEST['wcb2b_group_show_discount_product-bulk'] ?? -1;
                if ( '-1' !== $show_discount_product ) {
                    update_post_meta( $post_id, 'wcb2b_group_show_discount_product', intval( $show_discount_product ) );
                }
                // Store catalog mode flag
                $catalog_mode = $_REQUEST['wcb2b_group_catalog_mode-bulk'] ?? -1;
                if ( '-1' !== $catalog_mode ) {
                    update_post_meta( $post_id, 'wcb2b_group_catalog_mode', intval( $catalog_mode ) );
                }
                // Store min purchase alert
                $minpurchase_alert = $_REQUEST['wcb2b_group_minpurchase_alert-bulk'] ?? -1;
                if ( '-1' !== $minpurchase_alert ) {
                    update_post_meta( $post_id, 'wcb2b_group_minpurchase_alert', intval( $minpurchase_alert ) );
                }
                // Store min purchase button
                $minpurchase_button = $_REQUEST['wcb2b_group_minpurchase_button-bulk'] ?? -1;
                if ( '-1' !== $minpurchase_button ) {
                    update_post_meta( $post_id, 'wcb2b_group_minpurchase_button', intval( $minpurchase_button ) );
                }
                // Store save cart
                $save_cart = $_REQUEST['wcb2b_group_save_cart-bulk'] ?? -1;
                if ( '-1' !== $save_cart ) {
                    update_post_meta( $post_id, 'wcb2b_group_save_cart', intval( $save_cart ) );
                }
                // Store show coupons
                $group_show_coupons = $_REQUEST['wcb2b_group_show_coupon-bulk'] ?? -1;
                if ( '-1' !== $group_show_coupons ) {
                    update_post_meta( $post_id, 'wcb2b_group_show_coupon', intval( $group_show_coupons ) );
                }
            }

            // Verify meta box nonce
            if ( ! isset( $_POST['wcb2b_group-prices+tax-nonce'] ) || ! wp_verify_nonce( $_POST['wcb2b_group-prices+tax-nonce'], basename( __FILE__ ) ) ) { return; }
            if ( ! isset( $_POST['wcb2b_group-cart+checkout-nonce'] ) || ! wp_verify_nonce( $_POST['wcb2b_group-cart+checkout-nonce'], basename( __FILE__ ) ) ) { return; }
            if ( ! isset( $_POST['wcb2b_group-shop+products-nonce'] ) || ! wp_verify_nonce( $_POST['wcb2b_group-shop+products-nonce'], basename( __FILE__ ) ) ) { return; }
            if ( ! isset( $_POST['wcb2b_group-login+account-nonce'] ) || ! wp_verify_nonce( $_POST['wcb2b_group-login+account-nonce'], basename( __FILE__ ) ) ) { return; }
            
            // Return if autosave
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }

            // Store group hide prices flag
            $hide_prices = isset( $_POST['wcb2b_group_hide_prices'] ) ? $_POST['wcb2b_group_hide_prices'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_hide_prices', intval( $hide_prices ) );

            // Store group quotations with hidden prices flag
            $quotations_with_hidden_prices = isset( $_POST['wcb2b_group_quotations_with_hidden_prices'] ) ? $_POST['wcb2b_group_quotations_with_hidden_prices'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_quotations_with_hidden_prices', intval( $quotations_with_hidden_prices ) );

            // Store group price rule
            $price_rule = isset( $_POST['wcb2b_group_price_rule'] ) ? $_POST['wcb2b_group_price_rule'] : '';
            update_post_meta( $post_id, 'wcb2b_group_price_rule', $price_rule );

            // Store group discount
            $group_discount = isset( $_POST['wcb2b_group_discount'] ) ? $_POST['wcb2b_group_discount'] : '';
            update_post_meta( $post_id, 'wcb2b_group_discount', wc_format_decimal( $group_discount ) );

            // Store group packaging fee
            $group_packaging_fee = isset( $_POST['wcb2b_group_packaging_fee'] ) ? $_POST['wcb2b_group_packaging_fee'] : '';
            update_post_meta( $post_id, 'wcb2b_group_packaging_fee', $group_packaging_fee );

            // Store group catalog mode flag
            $group_catalog_mode = isset( $_POST['wcb2b_group_catalog_mode'] ) ? $_POST['wcb2b_group_catalog_mode'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_catalog_mode', intval( $group_catalog_mode ) );

            // Store group min purchase amount
            $group_min_purchase_amount = isset( $_POST['wcb2b_group_min_purchase_amount'] ) ? $_POST['wcb2b_group_min_purchase_amount'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_min_purchase_amount', intval( $group_min_purchase_amount ) );

            // Store group min purchase alert flag
            $minpurchase_alert = isset( $_POST['wcb2b_group_minpurchase_alert'] ) ? $_POST['wcb2b_group_minpurchase_alert'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_minpurchase_alert', intval( $minpurchase_alert ) );

            // Store group min purchase button flag
            $minpurchase_button = isset( $_POST['wcb2b_group_minpurchase_button'] ) ? $_POST['wcb2b_group_minpurchase_button'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_minpurchase_button', intval( $minpurchase_button ) );

            // Store group save cart flag
            $save_cart = isset( $_POST['wcb2b_group_save_cart'] ) ? $_POST['wcb2b_group_save_cart'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_save_cart', intval( $save_cart ) );

            // Store group show coupons
            $group_show_coupons = isset( $_POST['wcb2b_group_show_coupon'] ) ? $_POST['wcb2b_group_show_coupon'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_show_coupon', intval( $group_show_coupons ) );

            // Store group new order additional recipients
            $email_recipients = isset( $_POST['wcb2b_group_email_recipients'] ) ? $_POST['wcb2b_group_email_recipients'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_email_recipients', implode( ', ', filter_var_array( explode( ',', $email_recipients ), FILTER_SANITIZE_EMAIL ) ) );

            // Store group tax exemption flag
            $tax_exemption = isset( $_POST['wcb2b_group_tax_exemption'] ) ? $_POST['wcb2b_group_tax_exemption'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_tax_exemption', intval( $tax_exemption ) );

            // Store tax display
            $tax_display = isset( $_POST['wcb2b_group_tax_display'] ) ? wp_kses_post( $_POST['wcb2b_group_tax_display'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_tax_display', $tax_display );

            // Store price suffix
            $price_suffix = isset( $_POST['wcb2b_group_price_suffix'] ) ? wp_kses_post( $_POST['wcb2b_group_price_suffix'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_price_suffix', $price_suffix );

            // Store add invoice email
            $add_invoice_email = isset( $_POST['wcb2b_group_add_invoice_email'] ) ? $_POST['wcb2b_group_add_invoice_email'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_add_invoice_email', $add_invoice_email );

            // Store VAT number
            $add_vatnumber = isset( $_POST['wcb2b_group_add_vatnumber'] ) ? $_POST['wcb2b_group_add_vatnumber'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_add_vatnumber', $add_vatnumber );

            // Store VIES validation
            $vies_validation = isset( $_POST['wcb2b_group_vies_validation'] ) ? $_POST['wcb2b_group_vies_validation'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_vies_validation', $vies_validation );
            
            // Store add business certificate
            $add_business_certificate = isset( $_POST['wcb2b_group_add_business_certificate'] ) ? $_POST['wcb2b_group_add_business_certificate'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_add_business_certificate', $add_business_certificate );

            // Store extend registration form
            $extend_registration_fields = isset( $_POST['wcb2b_group_extend_registration_fields'] ) ? $_POST['wcb2b_group_extend_registration_fields'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_extend_registration_fields', intval( $extend_registration_fields ) );

            // Store moderate registration
            $moderate_registration = isset( $_POST['wcb2b_group_moderate_registration'] ) ? $_POST['wcb2b_group_moderate_registration'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_moderate_registration', intval( $moderate_registration ) );

            // Store blocked billing address
            $blocked_billing_address = isset( $_POST['wcb2b_group_blocked_billing_address'] ) ? $_POST['wcb2b_group_blocked_billing_address'] : 0;
            update_post_meta( $post_id, 'wcb2b_group_blocked_billing_address', intval( $blocked_billing_address ) );

            // Store group gateways
            $group_gateways = isset( $_POST['wcb2b_group_gateways'] ) ? $_POST['wcb2b_group_gateways'] : '';
            update_post_meta( $post_id, 'wcb2b_group_gateways', $group_gateways );

            // Store group shippings
            $group_shippings = isset( $_POST['wcb2b_group_shippings'] ) ? $_POST['wcb2b_group_shippings'] : '';
            update_post_meta( $post_id, 'wcb2b_group_shippings', $group_shippings );

            // Store group terms
            $group_terms_conditions = isset( $_POST['wcb2b_group_terms_conditions'] ) ? wp_kses_post( $_POST['wcb2b_group_terms_conditions'], wp_kses_allowed_html( 'post' ) ) : '';
            update_post_meta( $post_id, 'wcb2b_group_terms_conditions', $group_terms_conditions );

            // Store group delivery time
            $delivery_time = isset( $_POST['wcb2b_group_show_deliverytime'] ) ? $_POST['wcb2b_group_show_deliverytime'] : '';
            update_post_meta( $post_id, 'wcb2b_group_show_deliverytime', $delivery_time );

            // Store group show shipping class
            $show_shipping_class = isset( $_POST['wcb2b_group_show_shipping_class'] ) ? wp_kses_post( $_POST['wcb2b_group_show_shipping_class'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_show_shipping_class', $show_shipping_class );

            // Store group show barcode
            $show_barcode = isset( $_POST['wcb2b_group_show_barcode'] ) ? wp_kses_post( $_POST['wcb2b_group_show_barcode'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_show_barcode', $show_barcode );

            // Store group show RRP
            $show_rrp = isset( $_POST['wcb2b_group_show_rrp'] ) ? wp_kses_post( $_POST['wcb2b_group_show_rrp'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_show_rrp', $show_rrp );

            // Store group already bought
            $already_bought = isset( $_POST['wcb2b_group_already_bought'] ) ? wp_kses_post( $_POST['wcb2b_group_already_bought'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_already_bought', $already_bought );

            // Store group show sales
            $show_sales = isset( $_POST['wcb2b_group_show_sales'] ) ? wp_kses_post( $_POST['wcb2b_group_show_sales'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_show_sales', $show_sales );

            // Store group shippings tab
            $shippings_tab = isset( $_POST['wcb2b_group_shippings_tab'] ) ? wp_kses_post( $_POST['wcb2b_group_shippings_tab'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_shippings_tab', $shippings_tab );

            // Store group purchase history tab
            $purchase_history = isset( $_POST['wcb2b_group_purchase_history_tab'] ) ? wp_kses_post( $_POST['wcb2b_group_purchase_history_tab'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_purchase_history_tab', $purchase_history );

            // Store group show unpaid
            $show_unpaid = isset( $_POST['wcb2b_group_show_unpaid'] ) ? wp_kses_post( $_POST['wcb2b_group_show_unpaid'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_show_unpaid', $show_unpaid );

            // Store group show name
            $show_groupname = isset( $_POST['wcb2b_group_show_groupname'] ) ? wp_kses_post( $_POST['wcb2b_group_show_groupname'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_show_groupname', $show_groupname );

            // Store group show discount myaccount
            $show_discount_myaccount = isset( $_POST['wcb2b_group_show_discount_myaccount'] ) ? wp_kses_post( $_POST['wcb2b_group_show_discount_myaccount'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_show_discount_myaccount', $show_discount_myaccount );

            // Store group show discount product
            $show_discount_product = isset( $_POST['wcb2b_group_show_discount_product'] ) ? wp_kses_post( $_POST['wcb2b_group_show_discount_product'] ) : '';
            update_post_meta( $post_id, 'wcb2b_group_show_discount_product', $show_discount_product );

            // To fix price caches
            wc_delete_product_transients();
        }
    }

    /**
     * Add new columns in groups list
     * 
     * @param  array $columns Array of column name => label
     * @return array
     */
    public function render_columns( $columns ) {
        return  array_slice( $columns, 0, -1, true ) + 
                array( 'wcb2b_group_discount'                   => __( 'Discount (%)', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_packaging_fee'              => __( 'Packaging fee', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_catalog_mode'               => __( 'Catalog mode', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_min_purchase_amount'        => __( 'Min purchase amount', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_minpurchase_alert'          => __( 'Min purchase alert', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_minpurchase_button'         => __( 'Min purchase button', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_save_cart'                  => __( 'Save cart', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_show_coupon'                => __( 'Show coupons', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_hide_prices'                => __( 'Hide prices', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_quotations_with_hidden_prices' => __( 'Allow quotations with hidden prices', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_price_rule'                 => __( 'Price rule', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_tax_display'                => __( 'Display prices', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_tax_exemption'              => __( 'Tax exemption', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_add_invoice_email'          => __( 'Add invoice email', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_add_vatnumber'              => __( 'Add VAT number', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_vies_validation'            => __( 'VIES validation', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_add_business_certificate'   => __( 'Add business certificate', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_extend_registration_fields' => __( 'Extend registration fields', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_moderate_registration'      => __( 'Moderate registration', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_blocked_billing_address'    => __( 'Blocked billing address', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_show_deliverytime'          => __( 'Show delivery time', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_show_shipping_class'        => __( 'Show shipping class', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_show_barcode'               => __( 'Show barcode', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_show_rrp'                   => __( 'Show RRP', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_already_bought'             => __( 'Show already bought', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_show_sales'                 => __( 'Show products sales', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_shippings_tab'              => __( 'Show products shippings tab', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_purchase_history_tab'       => __( 'Show products purchase history tab', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_show_unpaid'                => __( 'Show unpaid amount', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_show_groupname'             => __( 'Show customer group', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_show_discount_myaccount'    => __( 'Show discount % in myaccount', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_show_discount_product'      => __( 'Show discount % in product', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_gateways'                   => __( 'Allowed payment methods', 'woocommerce-b2b' ) ) +
                array( 'wcb2b_group_shippings'                  => __( 'Allowed shipping methods', 'woocommerce-b2b' ) ) +
                array_slice( $columns, -1, null, true );
    }

    /**
     * Retrieve column value in groups list
     *
     * @param string $column Column name
     * @param int $post_id ID of the currently-listed group
     */
    public function render_columns_value( $column, $post_id ) {
        if ( $column === 'wcb2b_group_price_rule' ) {
            $value = get_post_meta( $post_id, 'wcb2b_group_price_rule', true );
            switch ( $value ) {
                case 'single' :
                    esc_html_e( 'Group dedicated price', 'woocommerce-b2b' );
                    break;
                case 'global' :
                    esc_html_e( 'Group discount on default price', 'woocommerce-b2b' );
                    break;
                case 'both' :
                    esc_html_e( 'Group discount on dedicated price', 'woocommerce-b2b' );
                    break;
            }
        }
        if ( $column === 'wcb2b_group_hide_prices' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_hide_prices', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_quotations_with_hidden_prices' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_quotations_with_hidden_prices', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_discount' ) {
            if ( $discount = get_post_meta( $post_id, 'wcb2b_group_discount', true ) ) {
                printf( '%s%%', $discount );
            }
        }
        if ( $column === 'wcb2b_group_catalog_mode' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_catalog_mode', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_min_purchase_amount' ) {
            echo get_post_meta( $post_id, 'wcb2b_group_min_purchase_amount', true );
        }
        if ( $column === 'wcb2b_group_minpurchase_alert' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_minpurchase_alert', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_minpurchase_button' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_minpurchase_button', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_save_cart' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_save_cart', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_show_coupon' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_show_coupon', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_packaging_fee' ) {
            if ( $fee = get_post_meta( $post_id, 'wcb2b_group_packaging_fee', true ) ) {
                printf( '%s%s',
                    $fee['value'],
                    $fee['type'] == 'percent' ? '%' : false
                );
            }
        }
        if ( $column === 'wcb2b_group_gateways' ) {
            if ( $gateways = WC()->payment_gateways->payment_gateways() ) {
                // Retrieve group allowed gateways value
                $group_gateways = get_post_meta( $post_id, 'wcb2b_group_gateways', true );
                if ( empty( $group_gateways ) ) {
                    $group_gateways = array();
                }
                foreach ( $gateways as $key => $gateway ) {
                    if ( ! in_array( $gateway->id, $group_gateways ) && 'yes' == $gateway->enabled ) {
                        printf( '<div>%s</div>', $gateway->title );
                    }
                }
            }
        }
        if ( $column === 'wcb2b_group_shippings' ) {
            if ( $shippings = WC()->shipping->get_shipping_methods() ) {
                // Retrieve group allowed gateways value
                $group_shippings = get_post_meta( $post_id, 'wcb2b_group_shippings', true );
                if ( empty( $group_shippings ) ) {
                    $group_shippings = array();
                }
                foreach ( $shippings as $key => $shipping ) {
                    if ( ! in_array( $shipping->id, $group_shippings ) ) {
                        printf( '<div>%s</div>', $shipping->method_title );
                    }
                }
            }
        }
        if ( $column === 'wcb2b_group_tax_display' ) {
            $value = get_post_meta( $post_id, 'wcb2b_group_tax_display', true );
            switch ( $value ) {
                default :
                    esc_html_e( '-- As globally defined --', 'woocommerce-b2b' );
                    break;
                case 'incl' :
                    esc_html_e( 'Including tax', 'woocommerce-b2b' );
                    break;
                case 'excl' :
                    esc_html_e( 'Excluding tax', 'woocommerce-b2b' );
                    break;
            }
        }
        if ( $column === 'wcb2b_group_add_invoice_email' ) {
            $value = get_post_meta( $post_id, 'wcb2b_group_add_invoice_email', true );
            switch ( $value ) {
                default :
                    esc_html_e( 'Hidden', 'woocommerce-b2b' );
                    break;
                case 'optional' :
                    esc_html_e( 'Optional', 'woocommerce-b2b' );
                    break;
                case 'required' :
                    esc_html_e( 'Required', 'woocommerce-b2b' );
                    break;
            }
        }
        if ( $column === 'wcb2b_group_add_vatnumber' ) {
            $value = get_post_meta( $post_id, 'wcb2b_group_add_vatnumber', true );
            switch ( $value ) {
                default :
                    esc_html_e( 'Hidden', 'woocommerce-b2b' );
                    break;
                case 'optional' :
                    esc_html_e( 'Optional', 'woocommerce-b2b' );
                    break;
                case 'required' :
                    esc_html_e( 'Required', 'woocommerce-b2b' );
                    break;
            }
        }
        if ( $column === 'wcb2b_group_vies_validation' ) {
            $value = get_post_meta( $post_id, 'wcb2b_group_vies_validation', true );
            switch ( $value ) {
                default :
                    esc_html_e( 'Disabled', 'woocommerce-b2b' );
                    break;
                case 'lax' :
                    esc_html_e( 'Enabled (lax)', 'woocommerce-b2b' );
                    break;
                case 'strict' :
                    esc_html_e( 'Enabled (strict)', 'woocommerce-b2b' );
                    break;
            }
        }
        if ( $column === 'wcb2b_group_add_business_certificate' ) {
            $value = get_post_meta( $post_id, 'wcb2b_group_add_business_certificate', true );
            switch ( $value ) {
                default :
                    esc_html_e( 'Hidden', 'woocommerce-b2b' );
                    break;
                case 'optional' :
                    esc_html_e( 'Optional', 'woocommerce-b2b' );
                    break;
                case 'required' :
                    esc_html_e( 'Required', 'woocommerce-b2b' );
                    break;
            }
        }
        if ( $column === 'wcb2b_group_tax_exemption' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_tax_exemption', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_extend_registration_fields' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_extend_registration_fields', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_moderate_registration' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_moderate_registration', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_blocked_billing_address' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_blocked_billing_address', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_show_deliverytime' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_show_deliverytime', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_show_shipping_class' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_show_shipping_class', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_show_barcode' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_show_barcode', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_show_rrp' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_show_rrp', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_already_bought' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_already_bought', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_show_sales' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_show_sales', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_shippings_tab' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_shippings_tab', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_purchase_history_tab' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_purchase_history_tab', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_show_unpaid' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_show_unpaid', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_show_groupname' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_show_groupname', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_show_discount_myaccount' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_show_discount_myaccount', true ) ? 'yes' : 'no'
            );
        }
        if ( $column === 'wcb2b_group_show_discount_product' ) {
            printf('<span class="dashicons dashicons-%s"></span>',
                get_post_meta( $post_id, 'wcb2b_group_show_discount_product', true ) ? 'yes' : 'no'
            );
        }
    }

    /**
     * Make column sortable in groups list
     * 
     * @param  array $columns Array of column name => label
     * @return array
     */
    public function make_columns_sortable( $columns ) {
        $columns['wcb2b_group_discount']            = 'wcb2b_group_discount';
        $columns['wcb2b_group_min_purchase_amount'] = 'wcb2b_group_group_min_purchase_amount';
        $columns['wcb2b_group_packaging_fee']       = 'wcb2b_group_packaging_fee';
        return $columns;
    }

    /**
     * Default hidden columns in groups 
     *   
     * @param array $hidden Hidden columns
     * @param object $screen Current screen instance
     * @return array
     */
    public function default_hidden_columns( $hidden, $screen ) {
        if ( 'wcb2b_group' == $screen->post_type ) {
            $hidden[] = 'wcb2b_group_hide_prices';
            $hidden[] = 'wcb2b_group_quotations_with_hidden_prices';
            $hidden[] = 'wcb2b_group_tax_display';
            $hidden[] = 'wcb2b_group_tax_exemption';
            $hidden[] = 'wcb2b_group_add_invoice_email';
            $hidden[] = 'wcb2b_group_add_vatnumber';
            $hidden[] = 'wcb2b_group_vies_validation';
            $hidden[] = 'wcb2b_group_add_business_certificate';
            $hidden[] = 'wcb2b_group_extend_registration_fields';
            $hidden[] = 'wcb2b_group_moderate_registration';
            $hidden[] = 'wcb2b_group_blocked_billing_address';
            $hidden[] = 'wcb2b_group_catalog_mode';
            $hidden[] = 'wcb2b_group_minpurchase_alert';
            $hidden[] = 'wcb2b_group_minpurchase_button';
            $hidden[] = 'wcb2b_group_save_cart';
            $hidden[] = 'wcb2b_group_show_coupon';
            $hidden[] = 'wcb2b_group_show_deliverytime';
            $hidden[] = 'wcb2b_group_show_shipping_class';
            $hidden[] = 'wcb2b_group_show_barcode';
            $hidden[] = 'wcb2b_group_show_rrp';
            $hidden[] = 'wcb2b_group_already_bought';
            $hidden[] = 'wcb2b_group_show_sales';
            $hidden[] = 'wcb2b_group_shippings_tab';
            $hidden[] = 'wcb2b_group_purchase_history_tab';
            $hidden[] = 'wcb2b_group_show_unpaid';
            $hidden[] = 'wcb2b_group_show_groupname';
            $hidden[] = 'wcb2b_group_price_rule';
            $hidden[] = 'wcb2b_group_show_discount_myaccount';
            $hidden[] = 'wcb2b_group_show_discount_product';
            $hidden[] = 'date';
        }
        return $hidden;
    }

    /**
     * Add fields for quick bulk edit
     * 
     * @param string $column_name Current column slug
     * @param string $post type Current post type
     */
    public function quick_edit_fields( $column_name, $post_type ) {
        if ( 'wcb2b_group' !== $post_type ) { return false; }
        switch( $column_name ) {
            case 'wcb2b_group_price_rule' :
                $options_html = '';
                foreach ( array(
                    '-1'     => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    'single' => esc_html__( 'Group dedicated price', 'woocommerce-b2b' ),
                    'global' => esc_html__( 'Group discount on default price', 'woocommerce-b2b' ),
                    'both'   => esc_html__( 'Group discount on dedicated price', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Price rule', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_price_rule-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_tax_display' :
                $options_html = '';
                foreach ( array(
                    '-1'   => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    ''     => esc_html__( '-- As globally defined --', 'woocommerce-b2b' ),
                    'incl' => esc_html__( 'Including tax', 'woocommerce-b2b' ),
                    'excl' => esc_html__( 'Excluding tax', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Display prices', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_tax_display-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_hide_prices' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Hide prices', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_hide_prices-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_quotations_with_hidden_prices' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Allow quotations with hidden prices', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_quotations_with_hidden_prices-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_tax_exemption' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Tax exemption', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_tax_exemption-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_add_invoice_email' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    'hidden'   => esc_html__( 'Hidden', 'woocommerce-b2b' ),
                    'optional' => esc_html__( 'Optional', 'woocommerce-b2b' ),
                    'required' => esc_html__( 'Required', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Add invoice email', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_add_invoice_email-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_add_vatnumber' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    'hidden'   => esc_html__( 'Hidden', 'woocommerce-b2b' ),
                    'optional' => esc_html__( 'Optional', 'woocommerce-b2b' ),
                    'required' => esc_html__( 'Required', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Add VAT number', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_add_vatnumber-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_vies_validation' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    'disabled'   => esc_html__( 'Disabled', 'woocommerce-b2b' ),
                    'lax' => esc_html__( 'Enabled (lax)', 'woocommerce-b2b' ),
                    'strict' => esc_html__( 'Enabled (strict)', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'VIES validation', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_vies_validation-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_add_business_certificate' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    'hidden'   => esc_html__( 'Hidden', 'woocommerce-b2b' ),
                    'optional' => esc_html__( 'Optional', 'woocommerce-b2b' ),
                    'required' => esc_html__( 'Required', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Add business centificate', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_add_business_certificate-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_extend_registration_fields' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Extend registration fields', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_extend_registration_fields-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_moderate_registration' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Moderate registration', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_moderate_registration-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_blocked_billing_address' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Blocked billing address', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_blocked_billing_address-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_show_deliverytime' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show delivery time', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_show_deliverytime-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_show_shipping_class' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show shipping class', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_show_shipping_class-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_show_barcode' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show barcode', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_show_barcode-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_show_rrp' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show RRP', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_show_rrp-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_already_bought' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Already bought', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_already_bought-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_show_sales' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show products sales', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_show_sales-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_shippings_tab' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show products shippings tab', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_shippings_tab-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_purchase_history_tab' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show products purchase history tab', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_purchase_history_tab-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_show_unpaid' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show unpaid amount', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_show_unpaid-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_show_groupname' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show customer group', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_show_groupname-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_show_discount_myaccount' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show discount % in myaccount', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_show_discount_myaccount-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_show_discount_product' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show discount % in product', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_show_discount_product-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_catalog_mode' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Catalog mode', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_catalog_mode-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_minpurchase_alert' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Min purchase alert', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_minpurchase_alert-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_minpurchase_button' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Min purchase checkout button', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_minpurchase_button-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_save_cart' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Save cart', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_save_cart-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
            case 'wcb2b_group_show_coupon' :
                $options_html = '';
                foreach ( array(
                    '-1' => esc_html__( '-- No Change --', 'woocommerce-b2b' ),
                    '1'  => esc_html__( 'Yes', 'woocommerce-b2b' ),
                    '0'  => esc_html__( 'No', 'woocommerce-b2b' ),
                ) as $key => $value ) {
                    $options_html .= sprintf( '<option value="%s">%s</option>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                    sprintf( '<span class="title">%s</span>',
                        esc_html__( 'Show coupons', 'woocommerce-b2b' )
                    ),
                    sprintf( '<select name="wcb2b_group_show_coupon-bulk">%s</select>',
                        $options_html
                    )
                );
                break;
        }
    }

    /**
     * Remove guest group action delete
     * 
     * @param array $actions Actions list
     * @param object $post Current listed post instance
     * @return array
     */
    public function remove_guest_group_delete( $actions, $post ) {
        if ( $post->ID == get_option( 'wcb2b_guest_group' ) ) {
            unset( $actions['trash'] );
            unset( $actions['delete'] );
        }
        return $actions;
    }

    /**
     * Prevent guest group action delete
     *
     * @param array $allcaps All capabilities
     * @param array $caps User capabilities
     * @param array $args Arguments
     * @return array
     */
    public function prevent_guest_group_delete( $allcaps, $caps, $args ) {
        $guest_group_id = get_option( 'wcb2b_guest_group' );
        if ( isset( $args[0] ) && isset( $args[2] ) && $args[2] == $guest_group_id && $args[0] == 'delete_post' ) {
            $allcaps[ $caps[0] ] = false;
        }
        return $allcaps;
    }

    /**
     * On save group (only add new) add to all terms visibility
     * 
     * @param string $new_status New group status
     * @param string $old_status Previous group status
     * @param object $post Current group instance
     */
    public function updated_group_status( $new_status, $old_status, $post ) {
        if ( $post->post_type == 'wcb2b_group' ) {
            if ( $old_status == 'new' ) {
                if ( $terms = get_terms( array( 'taxonomy' => array( 'product_cat', 'product_brand' ), 'hide_empty' => false ) ) ) {
                    // Update visible groups
                    foreach ( $terms as $term ) {
                        $visibility = get_term_meta( $term->term_id, 'wcb2b_group_visibility', true );
                        if ( ! $visibility || ! is_array( $visibility ) ) {
                            // Fix if term meta is empty
                            $visibility = array();
                        }
                        array_push( $visibility, $post->ID );
                        $visibility = array_filter( array_unique( $visibility, SORT_REGULAR ) );

                        // Add created group id to term meta
                        update_term_meta( $term->term_id, 'wcb2b_group_visibility', $visibility );
                    }
                }
                if ( $pages = get_pages() ) {
                    foreach ( $pages as $page ) {
                        if ( ! $visibility = get_post_meta( $page->ID, 'wcb2b_group_visibility', true ) ) {
                            // Fix if term meta is empty
                            $visibility = array();
                        }
                        array_push( $visibility, $post->ID );

                        // Add created group id to term meta
                        update_post_meta( $page->ID, 'wcb2b_group_visibility', $visibility );
                    }
                }
            }
        }
    }

    /**
     * On delete group, remove visibility from all terms and assign default group to customers
     * 
     * @param int $post_id Current group ID
     */
    public function deleted_group( $post_id ) {
        if ( $terms = get_terms( array( 'taxonomy' => array( 'product_cat', 'product_brand' ), 'hide_empty' => false ) ) ) {
            foreach ( $terms as $term ) {
                if ( ! $visibility = get_term_meta( $term->term_id, 'wcb2b_group_visibility', wcb2b_get_groups()->posts, true ) ) {
                    // Fix if term meta is empty
                    $visibility = array();
                }
                $key = array_search( $post_id, (array)$visibility );
                if ( $key !== false ) {
                    unset( $visibility[$key] );
                }
                $visibility = array_filter( array_unique( $visibility, SORT_REGULAR ) );

                // Update groups id to term meta
                update_term_meta( $term->term_id, 'wcb2b_group_visibility', $visibility );
            }
        }
        if ( $pages = get_pages() ) {
            foreach ( $pages as $page ) {
                if ( ! $visibility = get_post_meta( $page->ID, 'wcb2b_group_visibility', wcb2b_get_groups()->posts, true ) ) {
                    // Fix if term meta is empty
                    $visibility = array();
                }
                $key = array_search( $post_id, (array)$visibility );
                if ( $key !== false ) {
                    unset( $visibility[$key] );
                }
                $visibility = array_filter( array_unique( $visibility, SORT_REGULAR ) );

                // Update groups id to term meta
                update_post_meta( $page->ID, 'wcb2b_group_visibility', $visibility );
            }
        }

        // Get all customers belonging deleted group
        $query = new WP_User_Query( array(
            'role__in'   => get_option( 'wcb2b_has_role_customer', array( 'customer' ) ),
            'meta_key'   => 'wcb2b_group',
            'meta_value' => $post_id,
            'fields'     => 'ID'
        ) );
        $customers = $query->get_results();

        // Get the default group
        $default_group = get_option( 'wcb2b_default_group', false );
        
        // Remove group from default option, if exists
        if ( $default_group == $post_id ) {
            update_option( 'wcb2b_default_group', get_option( 'wcb2b_guest_group' ) );
        }
        
        // Set default group to all customers belonging deleted group
        if ( ! empty( $customers ) ) {
            foreach ( $customers as $customer ) {
                update_user_meta( $customer, 'wcb2b_group', $default_group ); 
            }
        }

        // To fix price caches
        wc_delete_product_transients();
    }

}

return new WCB2B_Post_Types();