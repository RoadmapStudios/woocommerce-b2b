<?php

/**
 * WooCommerce B2B REST API support
 *
 * @version 4.0.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Rest_Controller Class
 */
class WCB2B_Rest_Controller {

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
        add_action( 'rest_api_init', array( $this, 'add_group_controller' ), 10 );
        add_action( 'rest_api_init', array( $this, 'add_product_taxonomy_meta' ) );
        add_filter( 'woocommerce_rest_prepare_customer', array( $this, 'add_customer_meta' ), 90, 2 );
        add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'add_order_meta' ), 90, 2 );
        add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'add_product_meta' ), 90, 2 );
        add_filter( 'woocommerce_rest_prepare_product_variation_object', array( $this, 'add_product_meta' ), 90, 2 );
        add_filter( 'woocommerce_rest_pre_insert_shop_order_object', array( $this, 'pre_insert_order' ), 10, 3 );
        add_filter( 'woocommerce_rest_pre_insert_product_object', array( $this, 'pre_insert_product' ), 10, 3 );
    }

    /**
     * Add new REST API class
     */
    public function add_group_controller() {
        include_once WCB2B_ABSPATH . 'includes/legacy/api/class-wcb2b-api-groups.php';
        $controller = new WCB2B_API_Groups();
        $controller->register_routes();
    }

    /**
     * Add support for term meta to REST API
     */
    public function add_product_taxonomy_meta() {
        register_rest_field( 'product_cat',
            'meta', array(
                'get_callback'    => function( $object, $field, $request ) {
                    return get_term_meta( $object['id'] );
                },
                'update_callback' => function( $values, $object, $field ) {
                    $result = true;
                    if ( is_array( $values ) ) {
                        foreach ( $values as $key => $value ) {
                            $result &= update_term_meta( $object->term_id, $key, $value );
                        }
                    }
                    return $result;
                },
                'schema'          => null,
            )
        );
        register_rest_field( 'product_brand',
            'meta', array(
                'get_callback'    => function( $object, $field, $request ) {
                    return get_term_meta( $object['id'] );
                },
                'update_callback' => function( $values, $object, $field ) {
                    $result = true;
                    if ( is_array( $values ) ) {
                        foreach ( $values as $key => $value ) {
                            $result &= update_term_meta( $object->term_id, $key, $value );
                        }
                    }
                    return $result;
                },
                'schema'          => null,
            )
        );
    }

    /**
     * Add customer fields to REST API
     *
     * @param object $response REST API response object
     * @param object $customer Current customer object
     * @return object
     */
    public function add_customer_meta( $response, $customer ) {
        // Invoice email
        $response->data['billing']['invoice_email'] = $response->data['wcb2b_invoice_email'] = get_user_meta( $customer->ID, 'billing_invoice_email', true );

        // VAT number
        $response->data['billing']['vat_number'] = $response->data['wcb2b_vat_number'] = get_user_meta( $customer->ID, 'billing_vat', true );

        // Business certificate
        $response->data['business_certificate'] = $response->data['wcb2b_business_certificate'] = get_user_meta( $customer->ID, 'business_certificate', true );

        // Status
        $response->data['wcb2b_status'] = (int)get_user_meta( $customer->ID, 'wcb2b_status', true );

        // Upnaid orders limit
        $response->data['wcb2b_unpaid_limit'] = (int)get_user_meta( $customer->ID, 'wcb2b_unpaid_limit', true );

        // Group
        $response->data['wcb2b_group'] = array();
        if ( $group = get_post( get_user_meta( $customer->ID, 'wcb2b_group', true ) ) ) {
            $response->data['wcb2b_group']['id'] = $group->ID;
            $response->data['wcb2b_group']['name'] = $group->post_title;
            $response->data['wcb2b_group']['discount'] = get_post_meta( $group->ID, 'wcb2b_group_discount', true );
        }
        return $response;
    }

    /**
     * Add order fields to REST API
     *
     * @param object $response REST API response object
     * @param object $order Current order object
     * @return object
     */
    public function add_order_meta( $response, $order ) {
        // External invoice number
        $response->data['wcb2b_external_invoice_number'] = (int)$order->get_meta( '_wcb2b_external_invoice_number', true );
        // Group
        $response->data['wcb2b_group'] = (int)$order->get_meta( '_wcb2b_group', true );
        // Total weight
        $response->data['wcb2b_total_weight'] = (int)$order->get_meta( '_total_weight', true );
        return $response;
    }

    /**
     * Add product fields to REST API
     *
     * @param object $response REST API response object
     * @param object $product Current product object
     * @return object
     */
    public function add_product_meta( $response, $product ) {
        // Hidden prices
        $response->data['wcb2b_group_visibility'] = get_post_meta( $product->get_ID(), 'wcb2b_product_group_visibility' );

        // Group prices
        $response->data['wcb2b_group_prices'] = get_post_meta( $product->get_ID(), 'wcb2b_product_group_prices' );

        // Group tier prices
        $response->data['wcb2b_group_tier_prices'] = get_post_meta( $product->get_ID(), 'wcb2b_product_group_tier_prices' );

        // Barcode
        $response->data['wcb2b_barcode'] = get_post_meta( $product->get_ID(), 'wcb2b_barcode' );

        // Package quantity
        $response->data['wcb2b_package_quantity'] = get_post_meta( $product->get_ID(), 'wcb2b_product_group_packages' );

        // Minimum quantity
        $response->data['wcb2b_min_quantity'] = get_post_meta( $product->get_ID(), 'wcb2b_product_group_min' );

        // Maximum quantity
        $response->data['wcb2b_max_quantity'] = get_post_meta( $product->get_ID(), 'wcb2b_product_group_max' );

        // Hidden prices
        $response->data['wcb2b_group_hide_prices'] = get_post_meta( $product->get_ID(), 'wcb2b_product_group_hide_prices' );
        
        return $response;
    }

    /**
     * Prepare order meta
     *
     * @param object $order Current order object
     * @param object $response REST API response object
     * @param bool $creating If is creating a new object
     * @return object
     */
    public function pre_insert_order( $order, $request, $creating ) {
        if ( isset( $request['line_items'] ) ) {
            $products = array_unique( array_column( $request['line_items'], 'product_id' ) );
            $customer_id = $order->get_customer_id();
            if ( $customer_group_id = wcb2b_get_customer_group( $customer_id ) ) {
                if ( get_post_meta( $customer_group_id, 'wcb2b_group_tax_exemption', true ) ) {
                    $order->update_meta_data( 'is_vat_exempt', 'yes', true, 'edit' );
                }
            }
            foreach ( $order->get_items() as $item_id => $item ) {
                $product_id = $item->get_product_id();
                if ( $variation_id = $item->get_variation_id() ) {
                    $product_id = $variation_id;
                }
                if ( in_array( $product_id, $products ) ) {
                    $qty = $item->get_quantity();
                    $unit_price = $item->get_subtotal() / $qty;

                    $group_price = (float) wcb2b_get_group_price(
                        $unit_price,
                        $product_id,
                        'price',
                        $customer_id,
                        $qty
                    );
                    // Set prices
                    $item->set_subtotal( $group_price * $qty );
                    $item->set_total( $group_price * $qty );
                    // Make new taxes calculations
                    $item->calculate_taxes();
                }
            }
        }
        return $order;
    }

    /**
     * Prepare product meta
     *
     * @param object $product Current product object
     * @param object $response REST API response object
     * @param bool $creating If is creating a new object
     * @return object
     */
    public function pre_insert_product( $product, $request, $creating ) {
        if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
            $meta = array(
                'wcb2b_product_group_prices',
                'wcb2b_product_group_tier_prices',
                'wcb2b_product_group_packages',
                'wcb2b_product_group_min',
                'wcb2b_product_group_max'
            );
            foreach ( $request['meta_data'] as $meta_data ) {
                if ( in_array( $meta_data['key'], $meta ) ) {
                    $current_value = get_post_meta( $product->get_id(), $meta_data['key'], true );
                    if ( ! is_array( $current_value ) ) {
                        $current_value = array();
                    }
                    $product->update_meta_data( $meta_data['key'], array_replace_recursive(
                        $current_value,
                        $meta_data['value']
                    ), isset( $meta_data['id'] ) ? $meta_data['id'] : '' );
                }
            }
        }
        return $product;
    }

}

return new WCB2B_Rest_Controller();