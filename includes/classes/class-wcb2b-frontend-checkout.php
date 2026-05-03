<?php

/**
 * WooCommerce B2B Frontend Checkout set-up Class
 *
 * @version 4.0.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Frontend_Checkout Class
 */
class WCB2B_Frontend_Checkout {

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
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'add_total_weight' ) );
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'packaging_fee' ) );
        add_filter( 'woocommerce_coupon_is_valid', array( $this, 'coupon_is_valid' ), 10, 3 );
        add_action( 'woocommerce_cart_actions', array( $this, 'add_save_cart_button' ) );
        add_action( 'wp_loaded', array( $this, 'process_saved_carts' ), 20 );
        add_action( 'wp_loaded', array( $this, 'process_quick_order' ) );
        add_action( 'woocommerce_thankyou', array( $this, 'display_purchaseorder_number' ), 1 );
        add_action( 'woocommerce_checkout_process', array( $this, 'check_unpaid_order_amount_limit' ) );
        add_action( 'woocommerce_after_calculate_totals', array( $this, 'disable_cart_button' ) );
        add_action( 'woocommerce_after_calculate_totals', array( $this, 'disable_checkout_button' ) );
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'invoice_email_validation_checkout' ), 10, 2 );
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'vatnumber_validation_checkout' ), 10, 2 );
        add_action( 'woocommerce_blocks_validate_location_address_fields', array( $this, 'vatnumber_validation_block_checkout' ), 10, 3 );
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_estimated_delivery' ), 10, 3 );
        add_filter( 'woocommerce_get_item_data', array( $this, 'show_cart_item_estimated_delivery' ), 10, 2 ); 
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_order_item_estimated_delivery' ), 10, 4 );
        add_action( 'woocommerce_cart_actions', array( $this, 'empty_cart_button' ) );
        add_action( 'wp_loaded', array( $this, 'process_empty_cart' ), 20 );
        add_action( 'woocommerce_before_cart_collaterals', array( $this, 'display_coupons_in_cart' ) );
    }

    /**
     * Add total weight to order
     *
     * @param int $order_id Current order ID
     */
    public function add_total_weight( $order_id ) {
        $weight = WC()->cart->get_cart_contents_weight();
        $order = wc_get_order( $order_id );
        $order->update_meta_data( '_total_weight', $weight );
        $order->save();
    }

    /**
     * Add group package fee to order
     */
    public function packaging_fee() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            $packaging_fee = get_post_meta( $customer_group_id, 'wcb2b_group_packaging_fee', true );
            if ( ! empty( $packaging_fee ) ) {
                $value = $packaging_fee['value'];
                if ( $value ) {
                    if ( $packaging_fee['type'] == 'percent' ) {
                        $value = floatval( WC()->cart->get_cart_contents_total() ) * $packaging_fee['value'] / 100;
                    }
                    WC()->cart->add_fee( __( 'Packaging fee', 'woocommerce-b2b' ), $value );
                }
            }
        }
    }

    /**
     * Check coupons restriction by group or by total spent
     *
     * @param boolean $is_valid Check if coupon is valid
     * @param object $coupon Coupon object
     * @param mixed $discount Discount
     * @return boolean
     */
    public function coupon_is_valid( $is_valid, $coupon, $discount ) {
        // Check allowed groups
        $groups = $coupon->get_meta( '_wcb2b_coupon_group' );
        if ( ! empty( $groups ) ) {
            $customer_group_id = wcb2b_get_customer_group();
            if ( ! in_array( $customer_group_id, $groups ) ) {
                $is_valid = false;
            }
        }
        // Check total spent
        $total_spent = $coupon->get_meta( '_wcb2b_coupon_total_spent' );
        if ( ! empty( $total_spent ) ) {
            // Get current user total spent
            $user_total_spent = wc_get_customer_total_spent( get_current_user_id() );
            if ( $user_total_spent < $total_spent ) {
                $is_valid = false;
            }
        }
        return $is_valid;
    }

    /**
     * Add button to cart to save content
     */
    public function add_save_cart_button() {
        $customer_group_id = wcb2b_get_customer_group();
        if ( get_post_meta( $customer_group_id, 'wcb2b_group_save_cart', true ) ) {
            printf( '<button type="submit" class="button%s" name="wcb2b_cart_save" value="%s">%s</button>',
                esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ),
                esc_attr__( 'Save cart', 'woocommerce-b2b' ),
                esc_html__( 'Save cart', 'woocommerce-b2b' )
            );
        }
    }

    /**
     * Manage save, delete and restore action
     */
    public function process_saved_carts() {
        if ( ! ( isset( $_REQUEST['wcb2b_cart_save'] ) || isset( $_REQUEST['wcb2b_cart_restore'] ) || isset( $_REQUEST['wcb2b_cart_delete'] ) ) ) { return; }
        wc_nocache_headers();
        $nonce_value = wc_get_var( $_REQUEST['woocommerce-cart-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) );
        if ( wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) {
            if ( isset( $_REQUEST['wcb2b_cart_save'] ) ) {
                $key = uniqid();
                // Create two user meta:
                // wcb2b_cart: contains unique key ID
                // 'wcb2b_cart_{KEY}: contains datetime and cart contents
                add_user_meta( get_current_user_id(), 'wcb2b_cart', $key );
                add_user_meta( get_current_user_id(), 'wcb2b_cart_' . $key, array(
                    'datetime' => date( 'YmdHis' ),
                    'content'  => WC()->cart->get_cart()
                ) );
                // User notice
                wc_add_notice( __( 'Cart saved!', 'woocommerce-b2b' ), 'success' );
                return;
            }
            if ( isset( $_REQUEST['wcb2b_cart_restore'] ) ) {
                if ( $cart_key = wc_get_var( $_REQUEST['wcb2b_cart_restore'], false ) ) {
                    $cart_key = esc_attr( $cart_key );
                    // Check if exists
                    if ( $cart = get_user_meta( get_current_user_id(), 'wcb2b_cart_' . $cart_key, true ) ) {
                        // Empty cart by default
                        if ( apply_filters( 'wcb2b_empty_cart_on_save', true ) ) {
                            WC()->cart->empty_cart();
                        }
                        // Add saved contents to cart
                        foreach ( $cart['content'] as $cart_item_key => $cart_item ) {
                            WC()->cart->add_to_cart( $cart_item['product_id'], $cart_item['quantity'], $cart_item['variation_id'], $cart_item['variation'], $cart_item );
                        }
                        // Maybe redirect on cart
                        if ( apply_filters( 'wcb2b_redirect_on_restore_saved_cart', false ) ) {
                            wp_safe_redirect( wc_get_cart_url() );
                            exit;
                        }
                        // User notice
                        wc_add_notice( sprintf( __( 'Cart restored, %d items in cart', 'woocommerce-b2b' ), count( $cart['content'] ) ), 'success' );
                        return;
                    }
                }
            }
            if ( isset( $_REQUEST['wcb2b_cart_delete'] ) ) {
                if ( $cart_key = wc_get_var( $_REQUEST['wcb2b_cart_delete'], false ) ) {
                    $cart_key = esc_attr( $cart_key );
                    // Delete related pairs user meta
                    delete_user_meta( get_current_user_id(), 'wcb2b_cart_' . $cart_key );
                    delete_user_meta( get_current_user_id(), 'wcb2b_cart', $cart_key );
                    // User notice
                    wc_add_notice( __( 'Cart deleted!', 'woocommerce-b2b' ), 'success' );
                    return;
                }
            }
        }
        // User notice
        wc_add_notice( __( 'An error has occurred', 'woocommerce-b2b' ), 'error' );
    }

    /**
     * Process quick order CSV file
     *
     * @return boolean True if process is OK, else False
     */
    public function process_quick_order() {
        if ( isset( $_FILES['wcb2b-quick-order-csv'] ) ) {
            // Get user group (used for min/max/pack)
            $group_id = wcb2b_get_customer_group();

            // Check security nonce
            if ( ! isset( $_POST['quick-order-nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['quick-order-nonce'] ), 'quick-order' ) ) {
                wc_add_notice( __( 'An error has occurred, please try again', 'woocommerce-b2b' ), 'error' );
                return false;
            }

            // Check there are no errors
            if ( $_FILES['wcb2b-quick-order-csv']['error'] > 0 ) {
                wc_add_notice( __( 'An error has occurred in upload file, please try again', 'woocommerce-b2b' ), 'error' );
                return false;
            }
            $name = $_FILES['wcb2b-quick-order-csv']['name'];
            $tmp = $_FILES['wcb2b-quick-order-csv']['tmp_name'];
            $ext = pathinfo( $name, PATHINFO_EXTENSION );

            // Check the file is a CSV
            if ( $ext !== 'csv' ) {
                wc_add_notice( __( 'File must be a CSV, please try again', 'woocommerce-b2b' ), 'error' );
                return false;
            }

            if ( ( $fh = fopen( $tmp, 'r' ) ) !== false ) {
                // Necessary if a large CSV file
                set_time_limit( 0 );

                $params = apply_filters( 'wcb2b_quickorder_params', array(
                    'separator' => ';'
                ) );

                WC()->cart->empty_cart();

                $success = array();
                $errors = array();

                $line = 0;
                while ( ( $data = fgetcsv( $fh, 10000, $params['separator'] ) ) !== false ) {
                    $product_sku = sanitize_text_field( $data[0] );
                    if ( empty( $product_sku ) ) {
                        $errors[] = sprintf( __( 'SKU cannot be empty at line %s', 'woocommerce-b2b' ), $line );
                        continue;
                    }
                    $product_quantity = intval( $data[1] );
                    if ( empty( $product_quantity ) || ! is_numeric( $product_quantity ) || $product_quantity <= 0 ) {
                        $errors[] = sprintf( __( 'Product quantity for SKU %s must be a positive integer at line %s', 'woocommerce-b2b' ), $product_sku, $line );
                        continue;
                    }
                    $product_id = wc_get_product_id_by_sku( $product_sku );
                    if ( 0 == $product_id ) {
                        $errors[] = sprintf( __( 'No product with SKU %s was found at line %s', 'woocommerce-b2b' ), $product_sku, $line );
                        continue;
                    }

                    // Min/Max/Pack by $product_id (it can be simple or variation)
                    $append = '';
                    $min = get_post_meta( $product_id, 'wcb2b_product_group_min', true )[$group_id] ?? 0;
                    $max = get_post_meta( $product_id, 'wcb2b_product_group_max', true )[$group_id] ?? 0;
                    $packages = get_post_meta( $product_id, 'wcb2b_product_group_packages', true )[$group_id] ?? 0;
                    if ( $packages && $product_quantity % $packages ) {
                        $append .= sprintf( __( ' - Package quantity %s applied', 'woocommerce-b2b' ), $packages );
                        $product_quantity = ceil( round( $product_quantity / 10 ) / $packages * 10 ) * $packages;
                    }
                    if ( $max && $product_quantity > $max ) {
                        $append .= sprintf( __( ' - Max quantity %s applied', 'woocommerce-b2b' ), $max );
                        $product_quantity = $max;
                    }
                    if ( $min && $product_quantity < $min ) {
                        $append .= sprintf( __( ' - Min quantity %s applied', 'woocommerce-b2b' ), $min );
                        $product_quantity = $min;
                    }
                    // Adjust IDs
                    $variation_id = 0;
                    $product = wc_get_product( $product_id );
                    if ( $product->is_type( 'variation' ) ) {
                        $variation_id = $product_id;
                        $product_id = $product->get_parent_id();
                    }
                    // Add to cart
                    WC()->cart->add_to_cart( $product_id, $product_quantity, $variation_id );
                    
                    $success[] = sprintf( __( '%sx %s added to cart', 'woocommerce-b2b' ), $product_quantity, $product_sku ) . $append;
                    $line++;
                }
                fclose( $fh );

                if ( count( $errors ) ) {
                    wc_add_notice( implode( '<br />', $errors ), 'error' );
                }
                if ( count( $success ) ) {
                    wc_add_notice( implode( '<br />', $success ), 'success' );
                }
            }

            wc_maybe_define_constant( 'WCB2B_QUICK_ORDER_PROCESSED', true );

            return true;
        }
    }

    /**
     *  Displays the purchase order number on the order-received page
     *  
     *  @param integer $order_id Current order ID
     */
    public function display_purchaseorder_number( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $purchaseorder_number = $order->get_meta( '_wcb2b_purchaseorder_number', true ) ) {
            printf( '<p class="wcb2b-purchaseorder-number"><strong>%s:</strong> %s</p>',
                esc_html__( 'Purchase order number', 'woocommerce-b2b' ),
                $purchaseorder_number
            );
        }
    }

    /**
     * Check if customer has reached and exceeded unpaid order amount limit
     */
    public function check_unpaid_order_amount_limit() {
        if ( $customer_id = get_current_user_id() ) {
            $unpaid_amount_limit = get_user_meta( $customer_id, 'wcb2b_unpaid_limit', true );
            $unpaid_amount = wcb2b_get_total_unpaid( $customer_id );
            if ( ! empty( $unpaid_amount_limit ) && $unpaid_amount >= $unpaid_amount_limit ) {
                wc_add_notice( apply_filters( 'wcb2b_unpaid_notice', sprintf( __( 'Sorry, you have reached unpaid total orders amount limit (%s)', 'woocommerce-b2b' ), wc_price( $unpaid_amount_limit ) ), $unpaid_amount_limit ), 'error');
            }
        }
    }

    /**
     * If min amount is not reached, disable go to checkout button (cart)
     */
    public function disable_cart_button() {
        if ( ! is_cart() ) { return; }

        // Check if customer has group, else consider guest
        $customer_group_id = wcb2b_get_customer_group();
        if ( get_post_meta( $customer_group_id, 'wcb2b_group_minpurchase_alert', true ) ) {
            $scope = apply_filters( 'wcb2b_min_purchase_amount_scope', 'total' );
            switch ( $scope ) {
                case 'total' :
                    $against = WC()->cart->get_total( 'raw' );
                    break;
                case 'goods' :
                    $against = WC()->cart->get_cart_contents_total();
                    break;
            }
            // Check if option is active and minimum amount is reached or not
            $min_price_raw = get_post_meta( $customer_group_id, 'wcb2b_group_min_purchase_amount', true );
            if ( floatval( $min_price_raw ) > floatval( $against ) ) {
                // Add a message to inform that minimum amount is not reached yet (in cart)
                add_action( 'woocommerce_proceed_to_checkout', function() use ( $min_price_raw ) {
                    $min_price = wc_price( $min_price_raw );
                    echo '<p class="wcb2b_display_min_purchase_cart_message">' . apply_filters( 'wcb2b_display_min_purchase_cart_message', sprintf( esc_html__( 'To proceed to checkout and complete your purchase, make sure you have reached the minimum amount of %s.', 'woocommerce-b2b' ), $min_price ), $min_price_raw, $min_price ) . '<p>';
                }, 20 );

                // Remove "Proceed to checkout" button (also in mini-cart)
                
                if ( get_post_meta( $customer_group_id, 'wcb2b_group_minpurchase_button', true ) ) {
                    remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
                    remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );
                }
            }
        }
    }

    /**
     * If min amount is not reached, disable go to checkout button (checkout)
     */
    public function disable_checkout_button() {
        if ( ! is_checkout() ) { return; }

        // Check if customer has group, else consider guest
        $customer_group_id = wcb2b_get_customer_group();
        $scope = apply_filters( 'wcb2b_min_purchase_amount_scope', 'total' );
        switch ( $scope ) {
            case 'total' :
                $against = WC()->cart->get_total( 'raw' );
                break;
            case 'goods' :
                $against = WC()->cart->get_cart_contents_total();
                break;
        }

        $min_price_raw = get_post_meta( $customer_group_id, 'wcb2b_group_min_purchase_amount', true );
        if ( floatval( $min_price_raw ) > floatval( $against ) ) {
            // Remove terms and conditions
            remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 );
            // Block cart payments
            add_filter( 'woocommerce_cart_needs_payment', '__return_false' );
            // Remove payment button
            add_filter( 'woocommerce_order_button_html', '__return_false' );
            // Add a message to inform that minimum amount is not reached yet (in checkout)
            add_action( 'woocommerce_review_order_after_submit', function() use ( $min_price_raw, $against ) {
                $min_price = wc_price( $min_price_raw );
                echo '<p class="wcb2b_display_min_purchase_checkout_message">' . apply_filters( 'wcb2b_display_min_purchase_checkout_message', sprintf( esc_html__( 'To proceed to checkout and complete your purchase, you must reach the minimum amount of %s, but your total cart amount is currently %s. Return to %sshop%s', 'woocommerce-b2b' ), $min_price, wc_price( $against ), '<a href="' . get_permalink( wc_get_page_id( 'shop' ) ) . '">', '</a>' ), $min_price_raw, $min_price ) . '<p>';
            } );
        }
    }

    /**
     * Check if invoice email valid in checkout
     * 
     * @param array $fields Checkout billing address fields
     * @param array $errors List of errors
     * @return array
     */
    public function invoice_email_validation_checkout( $fields, $errors ) {
        if ( defined( 'REST_REQUEST' ) ) { return $errors; }

        if ( isset( $fields['billing_invoice_email'] ) ) {
            if ( ! empty( $fields['billing_invoice_email'] ) && ! is_email( $fields['billing_invoice_email'] ) ) {
                $errors->add( 'validation', esc_html__( 'Email address for invoices is not a valid email address', 'woocommerce-b2b' ) );
            }
        }
        return $errors;
    }

    /**
     * Check if VAT number is VIES valid in checkout
     * 
     * @param array $fields Checkout billing address fields
     * @param array $errors List of errors
     * @return array
     */
    public function vatnumber_validation_checkout( $fields, $errors ) {
        if ( defined( 'REST_REQUEST' ) ) { return $errors; }

        if ( isset( $fields['billing_vat'] ) ) {
            $errors = wcb2b_valid_vies( $fields['billing_country'], $fields['billing_vat'], $errors, is_checkout() );
        }
        return $errors;
    }

    /**
     * Check if VAT number is VIES valid in block checkout
     *   
     * @param WP_Error $errors List of errors
     * @param array $fields Checkout billing address fields
     * @param string $type Address type
     * @return WP_Error
     */
    public function vatnumber_validation_block_checkout( \WP_Error $errors, $fields, $type ) {
        if ( 'billing' === $type ) {
            $errors = wcb2b_valid_vies( ( $fields['country'] ?? false ), ( $fields['woocommerce-b2b/vat-number'] ?? false ), $errors, true );
        }
        return $errors;
    }

    /**
     * Add estimated delivery time when add to cart
     *
     * @param array $cart_item_data Current item data
     * @param integer $product_id Current product ID
     * @param integer $variation_id Current variation ID
     * @return array
     */
    public function add_cart_item_estimated_delivery( $cart_item_data, $product_id, $variation_id ) {
        if ( $estimated_delivery = wcb2b_estimated_delivery( $product_id ) ) {
            $cart_item_data['wcb2b-estimated-delivery'] = $estimated_delivery;
        }
        return $cart_item_data;
    }

    /**
     * Show estimated delivery time to cart page
     *
     * @param array $item_data Current item data
     * @param array $cart_item Current item
     * @return array
     */
    public function show_cart_item_estimated_delivery( $item_data, $cart_item ) {
        if ( ! empty( $cart_item['wcb2b-estimated-delivery'] ) ) {
            $item_data[] = array(
                'key'     => esc_html__( 'Estimated delivery', 'woocommerce-b2b' ),
                'value'   => $cart_item['wcb2b-estimated-delivery'],
                'display' => '',
            );
        }
        return $item_data;
    }

    /**
     * Save cart item custom meta as order item meta data and display it everywhere on orders and email notifications
     *
     * @param array $item Current item
     * @param string $cart_item_key Current item key
     * @param array $values Values
     * @param object $order Current order
     */
    public function save_order_item_estimated_delivery( $item, $cart_item_key, $values, $order ) {
        if ( ! empty( $values['wcb2b-estimated-delivery'] ) ) {
            $item->add_meta_data( esc_html__( 'Estimated delivery', 'woocommerce-b2b' ), $values['wcb2b-estimated-delivery'] );
        }
    }

    /**
     * Add empty cart button to cart page
     */
    public function empty_cart_button() {
        $button = sprintf( '<button type="submit" class="button wp-element-button" name="wcb2b_cart_empty" value="1">%s</button>',
            esc_html__( 'Empty cart', 'woocommerce-b2b' )
        );
        echo apply_filters( 'wcb2b_empty_cart_button_html', $button );
    }

    /**
     * Empty the cart if empty button is clicked
     */
    public function process_empty_cart() {
        if ( ! ( isset( $_REQUEST['wcb2b_cart_empty'] ) ) ) { return; }
        wc_nocache_headers();
        $nonce_value = wc_get_var( $_REQUEST['woocommerce-cart-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) );
        if ( wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) {
            if ( isset( $_REQUEST['wcb2b_cart_empty'] ) ) {
                WC()->cart->empty_cart();
            }
        }
    }

    /**
     * Show coupons list in cart
     */
    public function display_coupons_in_cart() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( $show_coupon = get_post_meta( $customer_group_id, 'wcb2b_group_show_coupon', true ) ) {
                // WP_Query arguments
                $args = array(
                    'post_type'      => array( 'shop_coupon' ),
                    'nopaging'       => true,
                    'posts_per_page' => '-1',
                    'fields'         => 'ids',
                    'meta_key'     => 'wcb2b_show_coupon',
                    'meta_value'   => 'yes',
                );
                // The Query
                $coupons = get_posts( $args );
                $coupons = array_filter( $coupons, function( $coupon_id ) {
                    $coupon = new WC_Coupon( $coupon_id );
                    return $coupon->is_valid();
                } );
                if ( count( $coupons ) ) {
                    wc_get_template( 'cart/coupons.php', array(
                        'coupons' => $coupons
                    ), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
                }
            }
        }
    }

}

return new WCB2B_Frontend_Checkout();