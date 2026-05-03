<?php

/**
 * WooCommerce B2B Common hooks to improve compatibility with third parts assets
 *
 * @version 4.0.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Compatibility Class
 */
class WCB2B_Compatibility {

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
        add_filter( 'wcb2b_always_visible_pages', array( $this, 'wpml_always_visible_pages' ), 10 ); // WPML
        add_action( 'wp', array( $this, 'elementor_preview_mode' ) ); // Elementor
        add_filter( 'woocommerce_layered_nav_count_maybe_cache', '__return_false' );
        add_filter( 'woocommerce_get_filtered_term_product_counts_query', array( $this, 'remove_unallowed_products_from_layered_count' ) );
        add_filter( 'body_class', array( $this, 'add_wc_body_classes_to_loginforms' ) ); // Common themes and builders

        // WCB2BW compatibility
        if ( wcb2b_is_active( 'warehouses' ) ) {
            // Specific group warehouse
            add_action( 'add_meta_boxes_wcb2b_group', array( $this, 'add_metabox_group' ) );
            add_action( 'save_post_wcb2b_group', array( $this, 'save_metabox_fields_group' ) );
            add_filter( 'wcb2b_compatibility__product_warehouse_by_group', array( $this, 'product_warehouse_by_group' ) );
        }
    }

    /**
     * Used to fix WPML problem with always visible pages
     *
     * @param array $args Always visible pages
     * @return array
     */
    public function wpml_always_visible_pages( $pages ) {
        if ( function_exists( 'wpml_object_id' ) ) {
            array_walk( $pages, function( &$item ) {
                $item = apply_filters( 'wpml_object_id', $item, 'post', false );
            } );
        }
        return $pages;
    }

    /**
     * Remove redirect in login forms when in Elementor preview mode
     */
    public function elementor_preview_mode() {
        if ( did_action( 'elementor/loaded' ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
            global $wp_filter;
            if ( isset( $wp_filter['template_redirect']->callbacks[5] ) && !empty( $wp_filter['template_redirect']->callbacks[5] ) ) {
                $wp_filter['template_redirect']->callbacks[5] = array_filter( $wp_filter['template_redirect']->callbacks[5], function( $v, $k ) {
                    return ( stripos( $k, 'redirect_login_forms' ) === false );
                }, ARRAY_FILTER_USE_BOTH );
            }
        }
    }

    /**
     * Remove unallowed products from layered count
     * 
     * @param WP_Query $query Layered nav query
     * @return WP_Query
     */
    public function remove_unallowed_products_from_layered_count( $query ) {
        global $wpdb;

        if ( ! is_user_logged_in() || wcb2b_has_role( get_current_user_id(), 'customer' ) ) {
            if ( 'yes' === get_option( 'woocommerce_attribute_lookup_enabled', false ) ) {
                $query["where"] .= " AND product_or_parent_id NOT IN (" . implode( ',', WCB2B_Configuration::get_unallowed_products() ) . ")";
            } else {
                $query["where"] .= " AND {$wpdb->posts}.ID NOT IN (" . implode( ',', WCB2B_Configuration::get_unallowed_products() ) . ")";
            }
        }
        return $query;
    }

    /**
     * Add default body classes needed by styles in pages with custom login forms
     * 
     * @param array $classes Body classes
     * @return array
     */
    public function add_wc_body_classes_to_loginforms( $classes ) {
        if ( in_array( get_the_ID(), wcb2b_loginform_pages() ) ) {
            $classes[] = 'woocommerce';
            $classes[] = 'woocommerce-account';
            $classes[] = 'woocommerce-page';
        }
        return $classes;
    }

    /**
     * Add warehouse metabox to WCB2B group
     * Bonus: require WooCommerce B2B Warehouses
     * 
     * @param WP_Post $post The current WP_Post object
     */
    public function add_metabox_group( $post ) {
        // Warehouse meta box
        add_meta_box( 'wcb2b_group-warehouse-meta_box', __( 'Warehouse', 'woocommerce-b2b' ), function( $post ) {
            // Make sure the form request comes from WordPress
            wp_nonce_field( basename( __FILE__ ), 'wcb2b_group-warehouse-nonce' );

            // Retrieve group warehouse current value
            $group_warehouse = get_post_meta( $post->ID, 'wcb2b_group_warehouse', true );
            // Build options
            $options = array_reduce( wcb2bw_get_warehouses()->posts, function( $result, $item ) {
                $item = (array)$item;
                $result[$item['ID']] = $item['post_title'];
                return $result;
            }, array(
                '0'      => __( '-- None --', 'woocommerce-b2b' )
            ) );
            // Helper
            $helper = esc_html__( 'Forces the warehouse from which to pick products for customer orders associated with this group', 'woocommerce-b2b' );
            // Display fields
            printf( $helper . '<p><select name="wcb2b_group_warehouse">%s</select></p>',
                implode( '', array_map( function( $key, $value ) use ( $group_warehouse ) {
                    return sprintf( '<option value="%s" %s>%s</option>',
                        $key,
                        selected( $key, $group_warehouse, false ),
                        $value
                    );
                }, array_keys( $options ), array_values( $options ) ) )
            );
        }, 'wcb2b_group', 'side', 'low' );
    }

    /**
     * Store custom field meta box data
     * Bonus: require WooCommerce B2B Warehouses
     * 
     * @param int $post_id The current post ID
     */
    public function save_metabox_fields_group( $post_id ) {
        // Only ShopManager can edit
        if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }

        // Verify meta box nonce
        if ( ! isset( $_POST['wcb2b_group-warehouse-nonce'] ) || ! wp_verify_nonce( $_POST['wcb2b_group-warehouse-nonce'], basename( __FILE__ ) ) ) { return; }
        
        // Return if autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }

        // Store warehouse
        $group_warehouse = isset( $_POST['wcb2b_group_warehouse'] ) ? $_POST['wcb2b_group_warehouse'] : '';
        update_post_meta( $post_id, 'wcb2b_group_warehouse', $group_warehouse );
    }

    /**
     * Force product warehouse according to customer group
     *   
     * @param array $product_warehouses Product possible warehouses
     * @return array
     */
    public function product_warehouse_by_group( $product_warehouses ) {
        $group_id = wcb2b_get_customer_group();
        if ( $group_warehouse = get_post_meta( $group_id, 'wcb2bw_group_warehouse', true ) ) {
            if ( isset( $product_warehouses[$group_warehouse] ) ) {
                $product_warehouses = array(
                    $group_warehouse => $product_warehouses[$group_warehouse]
                );
            } else {
                $product_warehouses = array();
            }
        }
        return $product_warehouses;
    }

}

return new WCB2B_Compatibility();