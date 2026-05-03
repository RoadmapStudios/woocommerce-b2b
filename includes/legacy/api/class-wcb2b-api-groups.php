<?php

/**
 * WooCommerce B2B REST API group controller
 * 
 * @version 4.0.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_API_Groups Class
 * */
class WCB2B_API_Groups extends WP_REST_Controller
{

    // Properties
    protected $namespace = 'wc/v3';
    protected $base = 'groups';

    /**
     * Register custom routes
     */
    public function register_routes() {
        # GET / POST
        register_rest_route( $this->namespace, '/' . $this->base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => array()
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'                => $this->get_endpoint_args_for_item_schema( true ),
            )
        ) );

        # GET / PUT / DELETE
        register_rest_route( $this->namespace, '/' . $this->base . '/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
                'args'                => array(
                    'context' => array(
                        'default' => 'view',
                    ),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'update_item_permissions_check' ),
                'args'                => $this->get_endpoint_args_for_item_schema( false ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_item' ),
                'permission_callback' => array( $this, 'delete_item_permissions_check' ),
                'args'                => array(
                    'force' => array(
                        'default' => false,
                    ),
                ),
            )
        ) );

        # SCHEMA
        register_rest_route( $this->namespace, '/' . $this->base . '/schema', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array( $this, 'get_public_item_schema' ),
            'permission_callback' => function() { return true; }
        ) );
    }

    /**
     * Get all group items
     */
    public function get_items( $request ) {
        $data = array();
        $args = array(
            'post_type' => 'wcb2b_group'
        );
        foreach( array(
                    'author',
                    'author_name',
                    'author__in',
                    'author__not_in',
                    'date_query',
                    'exact',
                    'meta_query',
                    'menu_order',
                    'name',
                    'nopaging',
                    'no_found_rows',
                    'offset',
                    'order',
                    'orderby',
                    'page',
                    'paged',
                    'pagename',
                    'perm',
                    'post__in',
                    'post__not_in',
                    'post_status',
                    'posts_per_page',
                    'post_name__in',
                    's',
                    'title'              
            ) as $allowed ) {
            if ( isset( $request[$allowed] ) ) {
                $args[$allowed] = $request[$allowed];
            }
        }
        // Get all groups
        $groups = get_posts( $args );
        if ( $groups ) {
            foreach ( $groups as $group ) {
                $item = $this->prepare_item_for_response( $group, $request );
                $data[] = $this->prepare_response_for_collection( $group );
            }
        }
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Get single group item
     */
    public function get_item( $request ) {
        // Check ID
        if ( ! isset( $request['id'] ) ) {
            return new WP_Error( 'wcb2b_api_missing_group_id', sprintf( __( 'Missing parameter %s', 'woocommerce-b2b' ), 'id' ), array( 'status' => 400 ) );
        }
        // Get group
        $group = get_post( $request['id'] );
        if ( null === $group ) {
            return new WP_Error( 'wcb2b_api_invalid_group', __( 'Invalid group', 'woocommerce-b2b' ), array( 'status' => 400 ) );
        }
        $data = $this->prepare_item_for_response( $group, $request );
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Add single group item
     */
    public function create_item( $request ) {
        // Check title
        if ( ! isset( $request['name'] ) ) {
            return new WP_Error( 'wcb2b_api_missing_group_name', sprintf( __( 'Missing parameter %s', 'woocommerce-b2b' ), 'name' ), array( 'status' => 400 ) );
        }
        // Check for duplicate
        if ( get_page_by_title( $request['name'], ARRAY_N, 'wcb2b_group' ) != null ) {
            return new WP_Error( 'wcb2b_api_duplicate_group_name', sprintf( __( 'A group named %s already exists', 'woocommerce-b2b' ), $request['name'] ), array( 'status' => 400 ) );
        }
        $group = $this->prepare_item_for_database( $request );
        // Insert group
        $group_id = wp_insert_post($group);
        if ( ! $group_id ) {
            return new WP_Error( 'wcb2b_api_cannot_create_group', __( 'This group cannot be created', 'woocommerce-b2b' ), array( 'status' => 400 ) );
        }
        return $this->get_item( array( 'id' => $group_id ) );
    }

    /**
     * Update single group item
     */
    public function update_item( $request ) {
        // Check ID
        if ( ! isset( $request['id'] ) ) {
            return new WP_Error( 'wcb2b_api_missing_group_id', sprintf( __( 'Missing parameter %s', 'woocommerce-b2b' ), 'id' ), array( 'status' => 400 ) );
        }
        // Check if group exists
        if ( get_post_status( $request['id'] ) == false ) {
            return new WP_Error( 'wcb2b_api_invalid_group', __( 'Invalid group', 'woocommerce-b2b' ), array( 'status' => 400 ) );
        }

        $group = $this->prepare_item_for_database( $request );
        // Update group and meta
        if ( isset( $request['name'] ) ) {
            $group_id = wp_update_post( array(
                'ID' => $request['id'],
                'post_title' => $request['name']
            ) );
        }
        foreach ( $group['meta_input'] as $meta_key => $meta_value ) {
            update_post_meta( $request['id'], $meta_key, $meta_value );
        }
        return $this->get_item( array( 'id' => $request['id'] ) );
    }

    /**
     *  Delete single group item
     */
    public function delete_item( $request ) {
        // Check ID
        if ( ! isset( $request['id'] ) ) {
            return new WP_Error( 'wcb2b_api_missing_group_id', sprintf( __( 'Missing parameter %s', 'woocommerce-b2b' ), 'id' ), array( 'status' => 400 ) );
        }
        // Check if group exists
        if ( get_post_status( $request['id'] ) == false ) {
            return new WP_Error( 'woocommerce_api_invalid_group', __( 'Invalid group', 'woocommerce-b2b' ), array( 'status' => 400 ) );
        }
        // Delete group
        $item = $this->get_item( array( 'id' => $request['id'] ) );
        $group_id = wp_delete_post( $request['id'], true );

        if ( ! $group_id ) {
            return new WP_Error( 'wcb2b_api_cannot_delete_group', __( 'This group cannot be deleted', 'woocommerce-b2b' ), array( 'status' => 400 ) );
        }
        return $item;
    }

    /**
     * Prepare data for respose
     */
    public function prepare_item_for_response( $group, $request ) {
        $group->wcb2b_group_hide_prices                = get_post_meta( $group->ID, 'wcb2b_group_hide_prices', true );
        $group->wcb2b_group_price_rule                 = get_post_meta( $group->ID, 'wcb2b_group_price_rule', true );
        $group->wcb2b_group_discount                   = get_post_meta( $group->ID, 'wcb2b_group_discount', true );
        $group->wcb2b_group_packaging_fee              = get_post_meta( $group->ID, 'wcb2b_group_packaging_fee', true );
        $group->wcb2b_group_minpurchase_alert          = get_post_meta( $group->ID, 'wcb2b_group_minpurchase_alert', true );
        $group->wcb2b_group_minpurchase_button         = get_post_meta( $group->ID, 'wcb2b_group_minpurchase_button', true );
        $group->wcb2b_group_min_purchase_amount        = get_post_meta( $group->ID, 'wcb2b_group_min_purchase_amount', true );
        $group->wcb2b_group_save_cart                   = get_post_meta( $group->ID, 'wcb2b_group_save_cart', true ); 
        $group->wcb2b_group_tax_exemption              = get_post_meta( $group->ID, 'wcb2b_group_tax_exemption', true );
        $group->wcb2b_group_tax_display                = get_post_meta( $group->ID, 'wcb2b_group_tax_display', true );
        $group->wcb2b_group_price_suffix               = get_post_meta( $group->ID, 'wcb2b_group_price_suffix', true );
        $group->wcb2b_group_add_invoice_email          = get_post_meta( $group->ID, 'wcb2b_group_add_invoice_email', true );
        $group->wcb2b_group_add_vatnumber              = get_post_meta( $group->ID, 'wcb2b_group_add_vatnumber', true );
        $group->wcb2b_group_add_business_certificate   = get_post_meta( $group->ID, 'wcb2b_group_add_business_certificate', true );
        $group->wcb2b_group_extend_registration_fields = get_post_meta( $group->ID, 'wcb2b_group_extend_registration_fields', true );
        $group->wcb2b_group_moderate_registration      = get_post_meta( $group->ID, 'wcb2b_group_moderate_registration', true );
        $group->wcb2b_group_gateways                   = get_post_meta( $group->ID, 'wcb2b_group_gateways', true );
        $group->wcb2b_group_shippings                  = get_post_meta( $group->ID, 'wcb2b_group_shippings', true );
        $group->wcb2b_group_terms_conditions           = get_post_meta( $group->ID, 'wcb2b_group_terms_conditions', true );
        $group->wcb2b_group_show_deliverytime          = get_post_meta( $group->ID, 'wcb2b_group_show_deliverytime', true );
        $group->wcb2b_group_show_shipping_class        = get_post_meta( $group->ID, 'wcb2b_group_show_shipping_class', true );
        $group->wcb2b_group_show_barcode               = get_post_meta( $group->ID, 'wcb2b_group_show_barcode', true );
        $group->wcb2b_group_show_rrp                   = get_post_meta( $group->ID, 'wcb2b_group_show_rrp', true );
        $group->wcb2b_group_already_bought             = get_post_meta( $group->ID, 'wcb2b_group_already_bought', true );
        $group->wcb2b_group_show_sales                 = get_post_meta( $group->ID, 'wcb2b_group_show_sales', true );
        $group->wcb2b_group_shippings_tab              = get_post_meta( $group->ID, 'wcb2b_group_shippings_tab', true );
        $group->wcb2b_group_purchase_history_tab       = get_post_meta( $group->ID, 'wcb2b_group_purchase_history_tab', true );
        $group->wcb2b_group_show_unpaid                = get_post_meta( $group->ID, 'wcb2b_group_show_unpaid', true );
        $group->wcb2b_group_show_groupname             = get_post_meta( $group->ID, 'wcb2b_group_show_groupname', true );
        $group->wcb2b_group_show_discount_myaccount    = get_post_meta( $group->ID, 'wcb2b_group_show_discount_myaccount', true );
        $group->wcb2b_group_show_discount_product      = get_post_meta( $group->ID, 'wcb2b_group_show_discount_product', true );
        $group->wcb2b_group_catalog_mode              = get_post_meta( $group->ID, 'wcb2b_group_catalog_mode', true );
        $group->wcb2b_group_quotations_with_hidden_prices = get_post_meta( $group->ID, 'wcb2b_group_quotations_with_hidden_prices', true );
        $group->wcb2b_group_show_coupon = get_post_meta( $group->ID, 'wcb2b_group_show_coupon', true );
        $group->wcb2b_group_email_recipients = get_post_meta( $group->ID, 'wcb2b_group_email_recipients', true );
        $group->wcb2b_group_blocked_billing_address = get_post_meta( $group->ID, 'wcb2b_group_blocked_billing_address', true );
        $group->wcb2b_group_vies_validation = get_post_meta( $group->ID, 'wcb2b_group_vies_validation', true );
        return $group;
    }

    /**
     * Prepare data for database
     */
    protected function prepare_item_for_database( $request ) {
        $meta_input = array();
        if ( isset( $request['wcb2b_group_hide_prices'] ) ) {
            $meta_input['wcb2b_group_hide_prices'] = $request['wcb2b_group_hide_prices'];
        }
        if ( isset( $request['wcb2b_group_price_rule'] ) ) {
            $meta_input['wcb2b_group_price_rule'] = $request['wcb2b_group_price_rule'];
        }
        if ( isset( $request['wcb2b_group_discount'] ) ) {
            $meta_input['wcb2b_group_discount'] = $request['wcb2b_group_discount'];
        }
        if ( isset( $request['wcb2b_group_packaging_fee'] ) ) {
            $meta_input['wcb2b_group_packaging_fee'] = $request['wcb2b_group_packaging_fee'];
        }
        if ( isset( $request['wcb2b_group_minpurchase_alert'] ) ) {
            $meta_input['wcb2b_group_minpurchase_alert'] = $request['wcb2b_group_minpurchase_alert'];
        }
        if ( isset( $request['wcb2b_group_minpurchase_button'] ) ) {
            $meta_input['wcb2b_group_minpurchase_button'] = $request['wcb2b_group_minpurchase_button'];
        }
        if ( isset( $request['wcb2b_group_min_purchase_amount'] ) ) {
            $meta_input['wcb2b_group_min_purchase_amount'] = $request['wcb2b_group_min_purchase_amount'];
        }
        if ( isset( $request['wcb2b_group_save_cart'] ) ) {
            $meta_input['wcb2b_group_save_cart'] = $request['wcb2b_group_save_cart'];
        }
        if ( isset( $request['wcb2b_group_tax_exemption'] ) ) {
            $meta_input['wcb2b_group_tax_exemption'] = $request['wcb2b_group_tax_exemption'];
        }
        if ( isset( $request['wcb2b_group_tax_display'] ) ) {
            $meta_input['wcb2b_group_tax_display'] = $request['wcb2b_group_tax_display'];
        }
        if ( isset( $request['wcb2b_group_price_suffix'] ) ) {
            $meta_input['wcb2b_group_price_suffix'] = $request['wcb2b_group_price_suffix'];
        }
        if ( isset( $request['wcb2b_group_add_invoice_email'] ) ) {
            $meta_input['wcb2b_group_add_invoice_email'] = $request['wcb2b_group_add_invoice_email'];
        }
        if ( isset( $request['wcb2b_group_add_vatnumber'] ) ) {
            $meta_input['wcb2b_group_add_vatnumber'] = $request['wcb2b_group_add_vatnumber'];
        }
        if ( isset( $request['wcb2b_group_add_business_certificate'] ) ) {
            $meta_input['wcb2b_group_add_business_certificate'] = $request['wcb2b_group_add_business_certificate'];
        }
        if ( isset( $request['wcb2b_group_extend_registration_fields'] ) ) {
            $meta_input['wcb2b_group_extend_registration_fields'] = $request['wcb2b_group_extend_registration_fields'];
        }
        if ( isset( $request['wcb2b_group_moderate_registration'] ) ) {
            $meta_input['wcb2b_group_moderate_registration'] = $request['wcb2b_group_moderate_registration'];
        }
        if ( isset( $request['wcb2b_group_gateways'] ) ) {
            $meta_input['wcb2b_group_gateways'] = $request['wcb2b_group_gateways'];
        }
        if ( isset( $request['wcb2b_group_shippings'] ) ) {
            $meta_input['wcb2b_group_shippings'] = $request['wcb2b_group_shippings'];
        }
        if ( isset( $request['wcb2b_group_terms_conditions'] ) ) {
            $meta_input['wcb2b_group_terms_conditions'] = $request['wcb2b_group_terms_conditions'];
        }
        if ( isset( $request['wcb2b_group_show_deliverytime'] ) ) {
            $meta_input['wcb2b_group_show_deliverytime'] = $request['wcb2b_group_show_deliverytime'];
        }
        if ( isset( $request['wcb2b_group_show_shipping_class'] ) ) {
            $meta_input['wcb2b_group_show_shipping_class'] = $request['wcb2b_group_show_shipping_class'];
        }
        if ( isset( $request['wcb2b_group_show_barcode'] ) ) {
            $meta_input['wcb2b_group_show_barcode'] = $request['wcb2b_group_show_barcode'];
        }
        if ( isset( $request['wcb2b_group_show_rrp'] ) ) {
            $meta_input['wcb2b_group_show_rrp'] = $request['wcb2b_group_show_rrp'];
        }
        if ( isset( $request['wcb2b_group_already_bought'] ) ) {
            $meta_input['wcb2b_group_already_bought'] = $request['wcb2b_group_already_bought'];
        }
        if ( isset( $request['wcb2b_group_show_sales'] ) ) {
            $meta_input['wcb2b_group_show_sales'] = $request['wcb2b_group_show_sales'];
        }
        if ( isset( $request['wcb2b_group_shippings_tab'] ) ) {
            $meta_input['wcb2b_group_shippings_tab'] = $request['wcb2b_group_shippings_tab'];
        }
        if ( isset( $request['wcb2b_group_purchase_history_tab'] ) ) {
            $meta_input['wcb2b_group_purchase_history_tab'] = $request['wcb2b_group_purchase_history_tab'];
        }
        if ( isset( $request['wcb2b_group_show_unpaid'] ) ) {
            $meta_input['wcb2b_group_show_unpaid'] = $request['wcb2b_group_show_unpaid'];
        }
        if ( isset( $request['wcb2b_group_show_groupname'] ) ) {
            $meta_input['wcb2b_group_show_groupname'] = $request['wcb2b_group_show_groupname'];
        }
        if ( isset( $request['wcb2b_group_show_discount_myaccount'] ) ) {
            $meta_input['wcb2b_group_show_discount_myaccount'] = $request['wcb2b_group_show_discount_myaccount'];
        }
        if ( isset( $request['wcb2b_group_show_discount_product'] ) ) {
            $meta_input['wcb2b_group_show_discount_product'] = $request['wcb2b_group_show_discount_product'];
        }
        if ( isset( $request['wcb2b_group_catalog_mode'] ) ) {
            $meta_input['wcb2b_group_catalog_mode'] = $request['wcb2b_group_catalog_mode'];
        }
        if ( isset( $request['wcb2b_group_quotations_with_hidden_prices'] ) ) {
            $meta_input['wcb2b_group_quotations_with_hidden_prices'] = $request['wcb2b_group_quotations_with_hidden_prices'];
        }
        if ( isset( $request['wcb2b_group_show_coupon'] ) ) {
            $meta_input['wcb2b_group_show_coupon'] = $request['wcb2b_group_show_coupon'];
        }
        if ( isset( $request['wcb2b_group_email_recipients'] ) ) {
            $meta_input['wcb2b_group_email_recipients'] = $request['wcb2b_group_email_recipients'];
        }
        if ( isset( $request['wcb2b_group_blocked_billing_address'] ) ) {
            $meta_input['wcb2b_group_blocked_billing_address'] = $request['wcb2b_group_blocked_billing_address'];
        }
        if ( isset( $request['wcb2b_group_vies_validation'] ) ) {
            $meta_input['wcb2b_group_vies_validation'] = $request['wcb2b_group_vies_validation'];
        }
        return array(
            'post_title' => $request['name'], 
            'post_type' => 'wcb2b_group',
            'post_status' => 'publish',
            'meta_input' => $meta_input
        );
    }

    /**
     * Check permissions
     */
    public function get_items_permissions_check( $request ) {
        if ( ! current_user_can( 'list_users' ) ) {
            return new WP_Error( 'wcb2b_api_cannot_read', __( 'Sorry, you cannot list groups', 'woocommerce-b2b' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }

    public function get_item_permissions_check( $request ) {
        if ( ! current_user_can( 'list_users' ) ) {
            return new WP_Error( 'wcb2b_api_cannot_read', __( 'Sorry, you cannot list groups', 'woocommerce-b2b' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }

    public function create_item_permissions_check( $request ) {
        if ( ! current_user_can( 'publish_products' ) ) {
            return new WP_Error( 'wcb2b_api_cannot_write', __( 'Sorry, you cannot add groups', 'woocommerce-b2b' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }

    public function update_item_permissions_check( $request ) {
        if ( ! current_user_can( 'edit_products' ) ) {
            return new WP_Error( 'wcb2b_api_cannot_edit', __( 'Sorry, you cannot edit groups', 'woocommerce-b2b' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }

    public function delete_item_permissions_check( $request ) {
        if ( ! current_user_can( 'delete_products' ) ) {
            return new WP_Error( 'wcb2b_api_cannot_delete', __( 'Sorry, you cannot delete groups', 'woocommerce-b2b' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }

}