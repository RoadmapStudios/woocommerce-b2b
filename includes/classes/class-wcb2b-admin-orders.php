<?php

/**
 * WooCommerce B2B Admin Orders set-up Class
 *
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * WCB2B_Admin_Orders Class
 */
class WCB2B_Admin_Orders {

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
        add_action( 'woocommerce_ajax_add_order_item_meta', array( $this, 'calculate_group_prices' ), 99, 3 );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_total_weight' ), 10, 1 );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_customer_group' ), 10, 1 );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_purchaseorder_number' ), 10, 1 );
        add_action( 'woocommerce_admin_order_data_after_payment_info', array( $this, 'display_quotation_validity' ), 10, 1 );
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'render_columns' ) );
        add_filter( 'manage_shop_order_posts_custom_column', array( $this, 'render_columns_value' ), 10, 2 );
        add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'make_columns_sortable' ) );
        add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'render_columns' ) );
        add_filter( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_columns_value' ), 10, 2 );
        add_filter( 'pre_get_posts', array( $this, 'query_filters' ) );
        add_action( 'restrict_manage_posts', array( $this, 'render_filters' ), 9 );
        add_filter( 'woocommerce_order_is_vat_exempt', array( $this, 'order_is_vat_exempt' ), 10, 2 );
        add_filter( 'woocommerce_order_actions', array( $this, 'preview_thankyou_page' ), 99, 2 );
        add_action( 'woocommerce_order_action_thankyoupage', array( $this, 'preview_thankyou_page_url' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_metabox' ), 10, 2 );
        add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_metabox_fields' ), 10, 2 );
        add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_status_actions' ) );
        add_filter( 'woocommerce_admin_billing_fields', array( $this, 'invoice_email_add_to_order_billing' ) );
        add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'invoice_email_copy_to_order' ) );
        add_filter( 'woocommerce_admin_billing_fields', array( $this, 'vatnumber_add_to_order_billing' ) );
        add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'vatnumber_copy_to_order' ) );
    }

    /**
     * Allow to apply discounted prices in backend orders
     * 
     * @param int $item_id Current order line item
     * @param object $item Current order line object instance
     * @param object $order Current order object instance
     */
    public function calculate_group_prices( $item_id, $item, $order ) {
        foreach ( $order->get_items() as $order_item_id => $order_item_data ) {
            if ( $order_item_id == $item_id ) {
                if ( $order_item_data->get_type() != 'line_item' ) { continue; }

                $product_id = $order_item_data->get_variation_id();
                if ( 0 == $product_id ) {
                    $product_id = $order_item_data->get_product_id();
                }

                if ( $product = wc_get_product( $product_id ) ) {
                    $customer_id = $order->get_customer_id();
                    $qty = $order_item_data->get_quantity();
                    $price = wcb2b_get_group_price( $product->get_price(), $product->get_id(), 'price', $customer_id, $qty );
                    $subtotal = wc_get_price_excluding_tax( $product, array( 'qty' => 1, 'price' => $price ) );
                    $order_item_data->set_subtotal( $subtotal );
                    $order_item_data->set_total( $qty * $subtotal );

                    $order->apply_changes();
                    $order->save();
                }                
            }
        }
    }

    /**
     * Display total weight in order
     * 
     * @param object $order Current order instance
     */
    public function display_total_weight( $order ) {
        if ( apply_filters( 'wcb2b_display_order_weight', true ) ) {
            if ( $total_weight = $order->get_meta( '_total_weight', true ) ) {
                printf( '<p><strong>%s:</strong> %s</p>',
                    esc_html__( 'Order weight', 'woocommerce-b2b' ),
                    sprintf( '%s %s', $total_weight, get_option( 'woocommerce_weight_unit' ) )
                );
            }
        }
    }

    /**
     * Display customer group in order
     * 
     * @param object $order Current order instance
     */
    public function display_customer_group( $order ) {
        if ( apply_filters( 'wcb2b_display_customer_group', true ) ) {
            if ( $group_id = $order->get_meta( '_wcb2b_group', true ) ) {
                if ( false !== get_post_status( $group_id ) ) {
                    if ( get_post_type( $group_id ) == 'wcb2b_group' ) {
                        printf( '<p class="wcb2b-order-group"><strong>%s:</strong> %s</p>',
                            esc_html__( 'Customer group', 'woocommerce-b2b' ),
                            sprintf( '<br /><a href="%s">%s</a>', get_edit_post_link( $group_id ), get_the_title( $group_id ) )
                        );
                    }
                }
            }
        }
    }

    /**
     * Display purchase order number
     * 
     * @param object $order Current order instance
     */
    public function display_purchaseorder_number( $order ) {
        if ( 'wcb2b_purchaseorder_gateway' == $order->get_payment_method() ) {
            if ( $purchaseorder_number = $order->get_meta( '_wcb2b_purchaseorder_number', true ) ) {
                printf( '<p class="wcb2b-purchaseorder-number"><strong>%s:</strong> %s</p>',
                    esc_html__( 'Purchase order number', 'woocommerce-b2b' ),
                    $purchaseorder_number
                );
            }
        }
    }

    /**
     * Display quotation validity date in order
     * 
     * @param object $order Current order instance
     */
    public function display_quotation_validity( $order ) {
        if ( $valid_until = $order->get_meta( '_wcb2b_quotation_validity', true ) ) {
            printf( '<p><strong>%s:</strong> %s</p>',
                esc_html__( 'Quotation valid until', 'woocommerce-b2b' ),
                date_i18n( get_option( 'date_format' ), strtotime( $valid_until ) )
            );
        }
    }

    /**
     * Add new orders columns in list
     * 
     * @param  array $columns Array of column name => label
     * @return array
     */
    public function render_columns( $columns ) {
        $_columns = [];
        foreach ( $columns as $column_name => $column_info ) {
            if ( 'order_total' === $column_name ) {
                $_columns['wcb2b_group'] = esc_html__( 'Group', 'woocommerce-b2b' );
                $_columns['wcb2b_payment'] = esc_html__( 'Payment Method', 'woocommerce-b2b' );
            }
            $_columns[ $column_name ] = $column_info;
        }
        return $_columns;
    }

    /**
     * Retrieve orders column value in list
     *
     * @param string $column_name Column name
     * @param int $order_id ID of the currently-listed order
     */
    public function render_columns_value( $column_name, $order_id ) {
        if ( $column_name === 'wcb2b_group' ) {
            $order = wc_get_order( $order_id );
            if ( $group_id = $order->get_meta( '_wcb2b_group', true ) ) {
                if ( false !== get_post_status( $group_id ) ) {
                    if ( get_post_type( $group_id ) == 'wcb2b_group' ) {
                        echo get_the_title( $group_id );
                    }
                }
            }
        }
        if ( $column_name === 'wcb2b_payment' ) {
            $order = wc_get_order( $order_id );
            echo $order->get_payment_method_title();
        }
    }

    /**
     * Make new columns sortable in order list
     * 
     * @param  array $columns Array of column name => label
     * @return array
     */
    public function make_columns_sortable( $columns ) {
        $columns['wcb2b_group'] = 'wcb2b_group';
        $columns['wcb2b_payment'] = 'wcb2b_payment';
        return $columns;
    }

    /**
     * Add parameters to filter orders
     *
     * @param WP_Query $query The WP_Query instance (passed by reference)
     */
    public function query_filters( $query ) {
        global $typenow;

        if ( $typenow == 'shop_order' && 'shop_order' == $query->get( 'post_type' ) ) {
            $meta_query = array();
            if ( isset( $_GET['wcb2b_group_search'] ) && $_GET['wcb2b_group_search'] !== '' ) {
                $meta_query[] = array(
                    'key'   => '_wcb2b_group',
                    'value' => (int)$_GET['wcb2b_group_search']
                );
            }
            if ( isset( $_GET['wcb2b_payment_search'] ) && $_GET['wcb2b_payment_search'] !== '' ) {
                $meta_query[] = array(
                    'key'   => '_payment_method',
                    'value' => sanitize_text_field( $_GET['wcb2b_payment_search'] )
                );
            }

            if ( ! empty( $meta_query ) ) {
                $query->set( 'meta_query', $meta_query );
            }
        }
    }

    /**
     * Add filters to orders
     */
    public function render_filters() {
        global $typenow;

        if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ), true ) ) {
            // Groups
            $groups = wcb2b_get_groups();

            $filters = '<select name="wcb2b_group_search" id="wcb2b_group_search">';
            $filters .= '<option value="">' . esc_html__( 'All groups', 'woocommerce-b2b' ) . '</option>';

            if ( $groups->have_posts() ) {
                while ( $groups->have_posts() ) {
                    $groups->the_post();

                    $filters .= sprintf(
                        '<option value="%1$s" %2$s>%3$s</option>',
                        get_the_ID(),
                        ( isset( $_GET['wcb2b_group_search'] ) && $_GET['wcb2b_group_search'] != '' && (int)$_GET['wcb2b_group_search'] === get_the_ID() ? ' selected="selected"' : '' ),
                        get_the_title()
                    );
                }
            }
            $filters .= '</select>';
            wp_reset_postdata();

            // Payment methods
            $payments = WC()->payment_gateways->get_available_payment_gateways();
            $filters .= '<select name="wcb2b_payment_search" id="wcb2b_payment_search">';
            $filters .= '<option value="">' . esc_html__( 'All payment methods', 'woocommerce-b2b' ) . '</option>';
          
            // Visualizzo le opzioni
            foreach ( $payments as $payment ) {
                $filters .= sprintf(
                    '<option value="%1$s" %2$s>%3$s</option>',
                    $payment->id,
                    ( ( isset( $_GET['wcb2b_payment_search'] ) && ( $_GET['wcb2b_payment_search'] == $payment->id ) ) ? ' selected="selected"' : '' ),
                    $payment->title
                );
            }
            $filters .= '</select>';

            echo $filters;
        }
    }

    /**
     * Apply tax free in backend orders
     * 
     * @param boolean $exempt Is order tax exempt?
     * @param object $order Order instance
     * @return boolean
     */
    public function order_is_vat_exempt( $exempt, $order ) {
        if ( ! is_a( $order, 'WC_Order' ) ) {
            return $exempt;
        }
        if ( $customer_group_id = wcb2b_get_customer_group( $order->get_customer_id() ) ) {
            if ( get_post_meta( $customer_group_id, 'wcb2b_group_tax_exemption', true ) ) {
                $exempt = true;
            } else {
                // Skip if customer group is guest
                // Country exempt only applied to other groups!
                if ( $customer_group_id !== get_option( 'wcb2b_guest_group' ) ) {
                    // Get tax free countries
                    $countries = get_option( 'wcb2b_tax_exemption_countries' );
                    // Check if country is tax free
                    if ( is_array( $countries ) && in_array( $order->get_billing_country(), $countries ) ) {
                        $exempt = true;
                    }
                }
            }
        }
        return $exempt;
    }

    /**
     * Allow to preview thankyou page from admin order page
     *
     * @param array $actions Contains all dropdown actions
     * @param object $order Current order instance
     * @return array
     */
    public function preview_thankyou_page( $actions, $order ) {
        if ( $order->has_status( wc_get_is_paid_statuses() ) ) {
            $actions['thankyoupage'] = __( 'Display thank you page', 'woocommerce-b2b' );
        }
        return $actions;
    }

    /**
     * Add order external invoice number
     *
     * @param object $order Current order instance
     */
    public function add_metabox( $object ) {
        $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id( 'shop-order' )
        : 'shop_order';
        // External invoice number
        add_meta_box( 'wcb2b_external_invoice_number-meta_box', __( 'External invoice number', 'woocommerce-b2b' ), function( $object ) {
            // Make sure the form request comes from WordPress
            wp_nonce_field( basename( __FILE__ ), 'wcb2b_external_invoice_number-nonce' );

            // Is order or post object instance?
            $order = ( $object instanceof WP_Post ) ? wc_get_order( $object->ID ) : $object;

            // Retrieve external invoice number value
            $external_invoice_number = $order->get_meta( '_wcb2b_external_invoice_number', true );

            // Display fields
            include_once WCB2B_ABSPATH . 'includes/views/html-admin-orders.php';
        }, $screen, 'side', 'low' );
    }

    /**
     * Save order external invoice number
     *
     * @param integer $order_id Current order ID
     * @param object $order Current order instance
     */
    public function save_metabox_fields( $order_id, $order ) {
        // Only ShopManager can edit customer group
        if ( current_user_can( 'manage_woocommerce' ) ) {

            // Verify meta box nonce
            if ( ! isset( $_POST['wcb2b_external_invoice_number-nonce'] ) || ! wp_verify_nonce( $_POST['wcb2b_external_invoice_number-nonce'], basename( __FILE__ ) ) ) { return; }
            
            // Return if autosave
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }

            // Store group discount
            if ( ! is_a( $order, 'WC_Order' ) ) {
                $order = new WC_Order( $order_id );
            }
            $external_invoice_number = isset( $_POST['wcb2b_external_invoice_number'] ) ? $_POST['wcb2b_external_invoice_number'] : '';
            $order->update_meta_data( '_wcb2b_external_invoice_number', sanitize_text_field( $external_invoice_number ) );
            $order->save();
        }
    }

    /**
     * Prepare redirect for thankyou page preview
     *
     * @param object $order Current order instance
     */
    public function preview_thankyou_page_url( $order ) {
        $url = $order->get_checkout_order_received_url();
        add_filter( 'redirect_post_location', function() use ( $url ) {
            return $url;
        } );
    }

    /**
     * Add new statuses to bulk actions
     * 
     * @param array $bulk_actions Bulk actions list
     * @return array
     */
    public function add_status_actions( $bulk_actions ) {
        // Note: "mark_" must be there instead of "wc"
        $bulk_actions['mark_invoice-payment'] = __( 'Change status to "awaiting invoice payment"', 'woocommerce-b2b' );
        if ( get_option( 'wcb2b_enable_quotations' ) === 'yes' ) {
            $bulk_actions['mark_on-quote'] = __( 'Change status to "on quote"', 'woocommerce-b2b' );
            $bulk_actions['mark_quoted'] = __( 'Change status to "quoted"', 'woocommerce-b2b' );
        }
        return $bulk_actions;
    }

    /**
     * Add invoice email field to billing address (in admin: Order page)
     * 
     * @param array $fields Checkout billing address fields
     * @return array
     */
    public function invoice_email_add_to_order_billing( $fields ) {
        // Add field exactly after company field
        $fields = array_slice( $fields, 0, 3, true )
        + array( 'invoice_email' => array(
            'label' => esc_html__( 'Email address for invoices', 'woocommerce-b2b' ),
            'show'  => true,
        ) )
        + array_slice( $fields, 3, count( $fields ) - 1, true );
        return $fields;
    }

    /**
     * Filter to copy the invoice email field from user meta fields to the order admin form (after clicking dedicated button on admin page)
     * 
     * @param array $customer_data Current order customer data
     * @return array
     */
    public function invoice_email_copy_to_order( $customer_data ) {
        $customer_data['billing']['invoice_email'] = get_user_meta( $_POST['user_id'], 'billing_invoice_email', true );
        return $customer_data;
    }

    /**
     * Add VAT number field to billing address (in admin: Order page)
     * 
     * @param array $fields Checkout billing address fields
     * @return array
     */
    public function vatnumber_add_to_order_billing( $fields ) {
        // Add field exactly after company field
        $fields = array_slice( $fields, 0, 3, true )
        + array( 'vat' => array(
            'label' => esc_html__( 'VAT number', 'woocommerce-b2b' ),
            'show'  => true,
        ) )
        + array_slice( $fields, 3, count( $fields ) - 1, true );
        return $fields;
    }

    /**
     * Filter to copy the VAT number field from user meta fields to the order admin form (after clicking dedicated button on admin page)
     * 
     * @param array $customer_data Current order customer data
     * @return array
     */
    public function vatnumber_copy_to_order( $customer_data ) {
        $customer_data['billing']['vat'] = get_user_meta( $_POST['user_id'], 'billing_vat', true );
        return $customer_data;
    }

}

return new WCB2B_Admin_Orders();