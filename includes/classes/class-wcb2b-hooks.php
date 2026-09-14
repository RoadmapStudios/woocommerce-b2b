<?php

/**
 * WooCommerce B2B Common hooks set-up
 *
 * @version 4.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Hooks Class
 */
class WCB2B_Hooks {

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
        add_filter( 'option_wcb2b_has_role_customer', array( $this, 'has_role_customer' ), 99, 2 );
        add_filter( 'is_protected_meta', array( $this, 'hide_protected_meta' ), 10, 3 );
        add_action( 'wp', array( $this, 'tax_exemption' ) );
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'tax_exemption_by_country' ), 11 );
        add_action( 'woocommerce_new_order', array( $this, 'add_customer_group' ), 10, 2 );
        add_action( 'init', array( $this, 'add_quotations_endpoint' ) );
        add_action( 'init', array( $this, 'add_saved_cart_endpoint' ) );
        add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
        add_action( 'woocommerce_register_shop_order_post_statuses', array( $this, 'register_order_statuses' ) );
        add_filter( 'wc_order_statuses', array( $this, 'add_order_statuses' ) );
        add_filter( 'wc_order_is_editable', array( $this, 'make_order_statuses_editable' ), 10, 2 );
        add_filter( 'woocommerce_email_actions', array( $this, 'set_emails_actions' ) );
        add_filter( 'woocommerce_email_classes', array( $this, 'set_quotations_emails' ) );
        add_filter( 'woocommerce_email_recipient_new_order', array( $this, 'additional_new_order_email_recipients' ), 10, 2 );
        add_filter( 'woocommerce_email_recipient_new_order', array( $this, 'prevent_new_order_email' ), 20, 2 );
        add_filter( 'woocommerce_email_order_meta_fields', array( $this, 'display_purchaseorder_number' ), 10, 3 );
        add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'prevent_stock_reduction_on_quotation' ), 10, 2 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'stock_reduction_on_quotation' ), 20, 4 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'set_quotation_validity' ), 10, 4 );
        add_filter( 'woocommerce_valid_order_statuses_for_payment', array( $this, 'quotations_can_be_payed' ), 10, 2 );
        add_filter( 'woocommerce_valid_order_statuses_for_cancel', array( $this, 'quotations_can_be_cancelled' ) );
        add_filter( 'pre_option_woocommerce_tax_display_shop', array( $this, 'tax_split' ), 98, 3 );
        add_filter( 'pre_option_woocommerce_tax_display_cart', array( $this, 'tax_split' ), 98, 3 );
        add_action( 'woocommerce_email_header', array( $this, 'tax_split_email' ), 1, 2 );
        add_filter( 'woocommerce_email_classes', array( $this, 'pending_account_email' ) );
        add_action( 'woocommerce_email_footer', array( $this, 'new_account_email_message' ) );
        add_action( 'woocommerce_email_footer', array( $this, 'quoted_email_validity' ) );
        add_filter( 'woocommerce_billing_fields', array( $this, 'add_additional_fields_to_address' ) );
        add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_additional_fields_to_email' ), 10, 2 );
        add_filter( 'woocommerce_localisation_address_formats', array( $this, 'additional_fields_format' ) );
        add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'add_additional_fields_to_formatted_address' ), 10, 3 );
        add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'add_additional_fields_to_order_details' ), 10, 2 );
        add_action( 'woocommerce_init', array( $this, 'add_additional_fields_to_address_block' ) );
        add_action( 'woocommerce_set_additional_field_value', array( $this, 'additional_fields_block_retrocompatibility' ), 10, 4 );
        add_filter( 'woocommerce_get_default_value_for_woocommerce-b2b/invoice-email', array( $this, 'map_invoicemail_field_to_block' ), 10, 3 );
        add_filter( 'woocommerce_get_default_value_for_woocommerce-b2b/vat-number', array( $this, 'map_vatnumber_field_to_block' ), 10, 3 );
        add_filter( 'woocommerce_shipping_classes_columns', array( $this, 'add_shipping_class_estimated_delivery' ) );
        add_action( 'woocommerce_shipping_classes_column_wcb2b_delivery_days_min', array( $this, 'populate_shipping_class_estimated_delivery' ) );
        add_action( 'woocommerce_shipping_classes_column_wcb2b_delivery_days_max', array( $this, 'populate_shipping_class_estimated_delivery' ) );
        add_filter( 'woocommerce_get_shipping_classes', array( $this, 'show_shipping_class_estimated_delivery' ), 11, 1 );
        add_action( 'woocommerce_shipping_classes_save_class', array( $this, 'save_shipping_class_estimated_delivery' ), 11, 2 );
        add_filter( 'woocommerce_admin_settings_sanitize_option_wcb2b_delivery_holidays', array( $this, 'unique_delivery_holidays' ), 10, 3 );
        add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'calculate_regular_price' ), 10, 2 );
        add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'calculate_regular_price' ), 10, 2 );
        add_filter( 'woocommerce_product_get_regular_price', array( $this, 'calculate_regular_price' ), 10, 2 );
        add_filter( 'woocommerce_variation_prices_sale_price', array( $this, 'calculate_sale_price' ), 10, 2 );
        add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'calculate_sale_price' ), 10, 2 );
        add_filter( 'woocommerce_product_get_sale_price', array( $this, 'calculate_sale_price' ), 10, 2 );
        add_filter( 'woocommerce_variation_prices_price', array( $this, 'calculate_price' ), 10, 2 );
        add_filter( 'woocommerce_product_variation_get_price', array( $this, 'calculate_price' ), 10, 2 );
        add_filter( 'woocommerce_product_get_price', array( $this, 'calculate_price' ), 10, 2 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'automatic_group_change' ), 10, 3 );
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_payment_methods' ) );
        add_filter( 'woocommerce_package_rates', array( $this, 'disable_shipping_methods' ) );
        add_action( 'woocommerce_init', array( $this, 'hide_prices_hooks' ) );
    }

    /**
     * Include any classes we need
     */
    public function includes() {
        add_action( 'plugins_loaded', function() {
            include_once WCB2B_ABSPATH . 'includes/classes/gateways/class-wcb2b-payment-gateway-invoice.php';
            include_once WCB2B_ABSPATH . 'includes/classes/gateways/class-wcb2b-payment-gateway-purchaseorder.php';
            include_once WCB2B_ABSPATH . 'includes/classes/gateways/class-wcb2b-payment-gateway-quotation.php';
        }, 11 );
    }

    /**
     * Apply hook to consider other roles as "customer"
     * 
     * @param mixed $pre_option The value to return instead of the option value
     * @param string $option_name Option name
     * @param mixed $default The fallback value to return if the option does not exist
     * @return mixed
     */
    public function has_role_customer( $value, $option ) {
        if ( ! is_array( $value ) ) {
            $value = array( $value );
        }
        return apply_filters( 'wcb2b_has_role_customer', $value );
    }

    /**
     * Hide protected meta$protected, $meta_key, $meta_type
     *
     * @param boolean $protected Is meta protected?
     * @param string $meta_key Meta key name
     * @param string $meta_type Meta type
     * @return boolean
     */
    public function hide_protected_meta( $protected, $meta_key, $meta_type ) {
        $meta = array(
            'wcb2b_product_group_packages',
            'wcb2b_product_group_min',
            'wcb2b_product_group_max',
            'wcb2b_barcode',
            'wcb2b_group_discount',
            'wcb2b_group_tax_display',
            'wcb2b_product_group_prices',
            'wcb2b_product_group_tier_prices',
            'wcb2b_group_price_rule',
            'wcb2b_group_min_purchase_amount',
            'wcb2b_group_tax_exemption',
            'wcb2b_group_packaging_fee',
            'wcb2b_group_gateways',
            'wcb2b_group_shippings',
            'wcb2b_group_terms_conditions',
            'wcb2b_group_visibility',
            'wcb2b_coupon'

        );
        if ( in_array( $meta_key, $meta ) ) {
            $protected = true;
        }
        return $protected;
    }

    /**
     * Add customer group to order
     *
     * @param int $order_id Current order ID
     * @param object $order Current order instance
     */
    public function add_customer_group( $order_id, $order ) {
        if ( $customer_group_id = wcb2b_get_customer_group( $order->get_customer_id() ) ) {
            $order->update_meta_data( '_wcb2b_group', $customer_group_id );
            $order->save();
        }
    }

    /**
     * Add quotations my-account page endpoint
     */
    public function add_quotations_endpoint() {
        add_rewrite_endpoint( get_option( 'wcb2b_account_quotations_endpoint', 'quotations' ), EP_ROOT | EP_PAGES );
    }

    /**
     * Add "Saved carts" endpoint
     */
    public function add_saved_cart_endpoint() {
        add_rewrite_endpoint( get_option( 'wcb2b_account_saved_carts_endpoint', 'saved-carts' ), EP_ROOT | EP_PAGES );
    }

    /**
     * Add gateways (quotation, invoice)
     * 
     * @param array $gateways All payment gateways
     * @return array
     */
    public function add_gateways( $gateways ) {
        $gateways[] = 'WCB2B_Gateway_Invoice';
        $gateways[] = 'WCB2B_Gateway_PurchaseOrder';
        if ( ! is_wc_endpoint_url( 'order-pay' ) ) {
            $gateways[] = 'WCB2B_Gateway_Quotation';
        }
        return $gateways;
    }

    /**
     * Register new order statuses
     * 
     * @param array $order_statuses All statuses settings list
     * @return array
     */
    public function register_order_statuses( $order_statuses ) {
        $order_statuses['wc-invoice-payment'] = array(
            'label'                     => __( 'Awaiting invoice payment', 'woocommerce-b2b' ),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'Awaiting invoice payment <span class="count">(%s)</span>', 'Awaiting invoice payment <span class="count">(%s)</span>', 'woocommerce-b2b' )
        );
        $order_statuses['wc-on-quote'] = array(
            'label'                     => __( 'On quote', 'woocommerce-b2b' ),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'On quote <span class="count">(%s)</span>', 'On quote <span class="count">(%s)</span>', 'woocommerce-b2b' )
        );
        $order_statuses['wc-quoted'] = array(
            'label'                     => __( 'Quoted', 'woocommerce-b2b' ),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'Quoted <span class="count">(%s)</span>', 'Quoted <span class="count">(%s)</span>', 'woocommerce-b2b' )
        );
        return $order_statuses;
    }

    /**
     * Add new statuses to list
     * 
     * @param array $order_statuses All statuses list
     * @return array
     */
    public function add_order_statuses( $order_statuses ) {
        $order_statuses['wc-invoice-payment'] = __( 'Awaiting invoice payment', 'woocommerce-b2b' );
        $order_statuses['wc-on-quote'] = __( 'On quote', 'woocommerce-b2b' );
        $order_statuses['wc-quoted'] = __( 'Quoted', 'woocommerce-b2b' );
        return $order_statuses;
    }

    /**
     * Make order statuses editable
     * 
     * @param boolean $editable Order can be edit?
     * @param object $order Current order instance
     * @return boolean
     */
    public function make_order_statuses_editable( $editable, $order ) {
        if ( in_array( $order->get_status(), array( 'on-quote' ) ) ) {
            return true;
        }
        return $editable;
    }

    /**
     * Add transactional email for quotations
     * 
     * @param array $emails Emails settings
     * @return array
     */
    public function set_quotations_emails( $emails ) {
        $emails['WCB2B_Email_New_Quote']   = include WCB2B_ABSPATH . 'includes/classes/emails/class-wcb2b-email-new-quote.php';
        $emails['WCB2B_Email_Customer_OnQuote_Order']   = include WCB2B_ABSPATH . 'includes/classes/emails/class-wcb2b-email-customer-onquote-order.php';
        $emails['WCB2B_Email_Customer_Quoted_Order']    = include WCB2B_ABSPATH . 'includes/classes/emails/class-wcb2b-email-customer-quoted-order.php';
        return $emails;
    }

    /**
     * Prevent sending new order email if is a quotation
     * 
     * @param string $recipient Email recipient
     * @param object $order Current order object
     * @return string
     */
    public function additional_new_order_email_recipients( $recipient, $order ) {
        if ( ! is_admin() && is_object( $order ) ) {
            if ( $group_id = $order->get_meta( '_wcb2b_group' ) ) {
                if ( false !== get_post_status( $group_id ) ) {
                    if ( get_post_type( $group_id ) == 'wcb2b_group' ) {
                        if ( $group_recipients = get_post_meta( $group_id, 'wcb2b_group_email_recipients', true ) ) {
                            $recipient .= ', ' . implode(', ', filter_var_array( explode(',', $group_recipients ), FILTER_SANITIZE_EMAIL ) );
                        }
                    }
                }
            }
        }
        return $recipient;
    }

    /**
     * Prevent sending new order email if is a quotation
     * 
     * @param string $recipient Email recipient
     * @param object $order Current order object
     * @return string
     */
    public function prevent_new_order_email( $recipient, $order ) {
        if ( is_object( $order ) && $order->has_status( 'on-quote' ) ) {
            return '';
        }
        return $recipient;
    }

    /**
     *  Adds the purchase order number to the order emails
     *  
     *  @param array $fields The order meta fields
     *  @param bool $sent_to_admin Send email to admin as well as customer?
     *  @param object $order The order object
     *  
     *  @return array
     */
    public function display_purchaseorder_number( $fields, $sent_to_admin, $order ) {
        if ( $purchaseorder_number = $order->get_meta( '_wcb2b_purchaseorder_number', true ) ) {
            $fields['wcb2b_purchaseorder_number'] = array(
                'label' => __( 'Purchase Order number', 'woocommerce-b2b' ), 
                'value' => esc_html( $purchaseorder_number )
            );
        }
        return $fields;
    }

    /**
     * Add transactional email actions for quotations
     * 
     * @param array $emails Emails settings
     * @return array
     */
    public function set_emails_actions( $emails ) {
        $emails[] = 'woocommerce_order_status_pending_to_on-quote';
        $emails[] = 'woocommerce_order_status_on-quote';
        $emails[] = 'woocommerce_order_status_quoted';
        return $emails;
    }

    /**
     * Prevent stock reduction for quotations
     * 
     * @param boolean $reduce_stock If reduce or not stock
     * @param object $order Current order instance
     * @return boolean
     */
    public function prevent_stock_reduction_on_quotation( $reduce_stock, $order ) {
        if ( $order->has_status( 'on-quote' ) ) {
            $reduce_stock = false;
        }
        return $reduce_stock;
    }

    /**
     * Proceed to reduce stock on quotation completed
     * 
     * @param integer $order_id Current order ID
     * @param string $old_status Order status before change
     * @param string $new_status Order status after change
     * @param object $order Current order instance
     */
    public function stock_reduction_on_quotation( $order_id, $old_status, $new_status, $order ) {
        // Only for 'processing' and 'completed' order statuses change
        if ( $new_status == 'quoted' ) {
            $stock_reduced = $order->get_meta( '_order_stock_reduced', true );
            if ( empty( $stock_reduced ) ) {
                wc_maybe_reduce_stock_levels( $order_id );
            }
        }
        if ( $new_status == 'invoice-payment' ) {
            // Getting all WC_emails objects
            $emails = WC()->mailer()->get_emails();
            $emails['WC_Email_Customer_On_Hold_Order']->trigger( $order_id ); // Send to customer
            $emails['WC_Email_New_Order']->trigger( $order_id ); // Send to admin
        }
    }

    /**
     * Calculate quotation validity date
     *   
     * @param integer $order_id Order ID
     * @param string $old_status Order previous status
     * @param string $new_status Order NEW status
     * @param object $order Current order instance
     */
    public function set_quotation_validity( $order_id, $old_status, $new_status, $order ) {
        if ( 'quoted' == $new_status ) {
            $validity = get_option( 'wcb2b_quotation_validity_days', 0 );
            if ( $validity ) {
            $valid_until = ( new DateTime('now') )->modify( '+' . $validity . ' days' )->format( 'Y-m-d 23:59:59' );
                $order->update_meta_data( '_wcb2b_quotation_validity', $valid_until );
                $order->save();
            }
        }
    }

    /**
     * Mark quotations as payable
     * 
     * @param array $statuses Payable statuses
     * @param object $order Current order instance
     * @return array
     */
    public function quotations_can_be_payed( $statuses, $order ) {
        if ( $order->has_status( 'quoted' ) ) {
            if ( $valid_until = $order->get_meta( '_wcb2b_quotation_validity', true ) ) {
                $now = new DateTime();
                $validity = new DateTime( $valid_until );
                if ( $validity < $now ) {
                    return $statuses;
                }
            }
            $statuses[] = 'quoted';
        }
        return $statuses;
    }

    /**
     * Mark quotations as cancellable
     * 
     * @param array $statuses Cancellable statuses
     * @return array
     */
    public function quotations_can_be_cancelled( $statuses ) {
        $statuses[] = 'on-quote';
        $statuses[] = 'quoted';
        return $statuses;
    }

    /**
     * Force to show prices with taxes to guest customers and without taxes to customers inserted into a group
     * 
     * @param mixed $pre_option The value to return instead of the option value
     * @param string $option_name Option name
     * @param mixed $default The fallback value to return if the option does not exist
     * @return mixed
     */
    public function tax_split( $pre_option, $option_name, $default ) {
        if ( function_exists( 'wcb2b_get_customer_group' ) ) {
            // By default, consider guest group configuration
            if ( $customer_group_id = wcb2b_get_customer_group() ) {
                // Get and return group option for tax display (if exists, else default)
                if ( $option = get_post_meta( $customer_group_id, 'wcb2b_group_tax_display', true ) ) {
                    return $option;
                }
            }
        }
        return $pre_option;
    }

    /**
     * Display prices with/withour taxes in email sent from WP-ADMIN (ie: change order status to quoted)
     *
     * @param string $email_heading Current email heading
     * @param object $email Current email instance
     */
    public function tax_split_email( $email_heading, $email ) {
        if ( is_admin() ) {
            if ( is_a( $email->object, 'WC_Order') ) {
                $group_id = $email->object->get_meta( '_wcb2b_group', true );
                if ( empty( $group_id ) ) {
                    $group_id = get_option( 'wcb2b_guest_group' );
                }
                $option = get_post_meta( $group_id, 'wcb2b_group_tax_display', true );

                add_filter( 'pre_option_woocommerce_tax_display_shop', function( $pre_option, $option_name, $default ) use ( $option ) {
                    return $option;
                }, 99, 3 );
                add_filter( 'pre_option_woocommerce_tax_display_cart', function( $pre_option, $option_name, $default ) use ( $option ) {
                    return $option;
                }, 99, 3 );
            }
        }
    }

    /**
     * Include email classes
     * 
     * @param array $emails Email classes list
     * @return array
     */
    public function pending_account_email( $emails ) {
        if ( ! array_key_exists( 'WCB2B_Email_Customer_Status_Notification', $emails ) ) {
            $emails['WCB2B_Email_Customer_Status_Notification'] = include_once WCB2B_ABSPATH . 'includes/classes/emails/class-wcb2b-email-customer-status-notification.php';
        }

        return $emails;
    }

    /**
     * Add message into new account email to inform customers to wait activation
     * 
     * @param object $email Current email instance
     */
    public function new_account_email_message( $email ) {
        // Customize new account email
        if ( is_object( $email ) && $email->id == 'customer_new_account' ) {
            $status = get_the_author_meta( 'wcb2b_status', $email->object->ID );
            if ( 0 == (int)$status ) {
                echo '<p>' . apply_filters( 'wcb2b_new_account_email', esc_html__( 'We are checking your account, please wait for the activation confirmation email before trying to login', 'woocommerce-b2b' ) ) . '</p>';
            }
        }
    }

    /**
     * Add quotation validity in email
     * 
     * @param object $email Current email instance
     */
    public function quoted_email_validity( $email ) {
        // Customize new account email
        if ( is_object( $email ) && $email->id == 'wcb2b_customer_quoted_order' ) {
            $order = wc_get_order( $email->object->get_id() );
            if ( $order && $order->has_status( 'quoted' ) ) {
                if ( $valid_until = $order->get_meta( '_wcb2b_quotation_validity', true ) ) {
                    printf( '<p><strong>%s:</strong> %s</p>',
                        esc_html__( 'Quotation valid until', 'woocommerce-b2b' ),
                        date_i18n( get_option( 'date_format' ), strtotime( $valid_until ) )
                    );
                }
            }
        }
    }

    /**
     * Apply tax exemption for groups
     *
     * @param string $tax_class Tax class to apply
     * @param object $product Current product instance
     * @return string
     */
    public function tax_exemption() {
        if ( isset( WC()->customer ) ) {
            WC()->customer->set_is_vat_exempt( false );
        }
    }

    /**
     * Apply tax free by country
     * 
     * @param  array $data Posted data
     */
    public function tax_exemption_by_country() {
        // Check customer group
        if ( isset( WC()->customer ) ) {
            WC()->customer->set_is_vat_exempt( false );
            if ( $customer_group_id = wcb2b_get_customer_group() ) {
                if ( get_post_meta( $customer_group_id, 'wcb2b_group_tax_exemption', true ) ) {
                    WC()->customer->set_is_vat_exempt( true );
                } else {
                    // Skip if customer group is guest
                    if ( $customer_group_id !== get_option( 'wcb2b_guest_group' ) ) {
                        // Get tax free countries
                        $countries = get_option( 'wcb2b_tax_exemption_countries' );
                        // Check if country is tax free
                        if ( is_array( $countries ) && in_array( WC()->customer->get_billing_country(), $countries ) ) {
                            WC()->customer->set_is_vat_exempt( true );
                        }
                    }
                }
            }
        }
    }

    /**
     * Creating merger additional fields variables for printing formatting
     * 
     * @param array $address Address fields
     * @param array $args Arguments
     * @return array
     */
    public function add_additional_fields_to_email( $address, $args ) {
        // Invoice email
        $address['{invoice_email}'] = '';
        $address['{invoice_email_upper}'] = '';

        if ( ! empty( $args['invoice_email'] ) ) {
            $address['{invoice_email}'] = $args['invoice_email'];
            $address['{invoice_email_upper}'] = strtoupper($args['invoice_email']);
        }
        // VAT number
        $address['{vat}'] = '';
        $address['{vat_upper}'] = '';

        if ( ! empty( $args['vat'] ) ) {
            $address['{vat}'] = $args['vat'];
            $address['{vat_upper}'] = strtoupper($args['vat']);
        }
        return $address;
    }

    /**
     * Add additional fields to billing address (in checkout)
     * 
     * @param array $fields Checkout billing address fields
     * @return array
     */
    public function add_additional_fields_to_address( $fields ) {
        // Maybe I show the field?
        $show_invoicemail = false;
        $required_invoicemail = false;
        $show_vatnumber = false;
        $required_vatnumber = false;
        if ( is_checkout() ) {
            $option = get_option( 'wcb2b_add_invoice_email', 'hidden' );
            if ( 'hidden' !== $option ) {
                $show_invoicemail = true;
                $required_invoicemail = 'required' === $option;
            }
            $option = get_option( 'wcb2b_add_vatnumber', 'hidden' );
            if ( 'hidden' !== $option ) {
                $show_vatnumber = true;
                $required_vatnumber = 'required' === $option;
            }
        } else {
            if ( isset( $_POST['wcb2b_group'] ) ) {
                $customer_group_id = $_POST['wcb2b_group'];
            } else {
                global $post;
                // Is a group form?
                $customer_group_id = wcb2b_get_customer_group();
                if ( is_object( $post ) ) {
                    $loginform_group_id = get_post_meta( $post->ID, 'wcb2bloginform', true );
                    if ( ! empty( $loginform_group_id ) ) {
                        $customer_group_id = $loginform_group_id;
                    }
                }
            }
            if ( $option = get_post_meta( $customer_group_id, 'wcb2b_group_add_invoice_email', true ) ) {
                if ( 'hidden' !== $option ) {
                    $show_invoicemail = true;
                    $required_invoicemail = 'required' === $option;
                }
            }
            if ( $option = get_post_meta( $customer_group_id, 'wcb2b_group_add_vatnumber', true ) ) {
                if ( 'hidden' !== $option ) {
                    $show_vatnumber = true;
                    $required_vatnumber = 'required' === $option;
                }
            }
        }
        if ( $show_invoicemail ) {
            // Add field exactly after company field
            $fields += array( 'billing_invoice_email' => array(
                'type'          => 'text',
                'label'         => esc_html__( 'Email address for invoices', 'woocommerce-b2b' ),
                'placeholder'   => esc_html_x( 'Email address for invoices', 'placeholder', 'woocommerce-b2b' ),
                'required'      => $required_invoicemail,
                'class'         => array( 'form-row-wide' ),
                'clear'         => true,
                'priority'      => 35
            ) );
        }
        if ( $show_vatnumber ) {
            // Company is mandatory (only for WC < 3.4.0)
            if ( version_compare( '3.4.0', get_option( 'woocommerce_version' ) ) === 1 ) {
                $fields['billing_company']['required'] = apply_filters( 'wcb2b_billing_company_required', true );
            }
            // Add field exactly after company field
            $fields += array( 'billing_vat' => array(
                'type'          => 'text',
                'label'         => esc_html__( 'Vat number', 'woocommerce-b2b' ),
                'placeholder'   => esc_html_x( 'Vat number', 'placeholder', 'woocommerce-b2b' ),
                'required'      => $required_vatnumber,
                'class'         => array( 'form-row-wide' ),
                'clear'         => true,
                'priority'      => 35
            ) );
        }
        return $fields;
    }

    /**
     * Redefine the formatting to print the address, including additional fields
     * 
     * @param string $formats Current i18n format
     * @return string
     */
    public function additional_fields_format( $formats ) {
        if ( WC_Blocks_Utils::has_block_in_page( wc_get_page_id( 'checkout' ), 'woocommerce/checkout' ) ) {
            return $formats;
        }
        $formats = str_replace( "{company}", "{company}\n{invoice_email}", $formats );
        $formats = str_replace( "{company}", "{company}\n{vat_upper}", $formats );
        return $formats;
    }

    /**
     * Add additional fields to billing address (in My account)
     * 
     * @param array $fields Checkout billing address fields
     * @param int $customer_id Current customer ID
     * @param string $type Address type (billing/shipping)
     * @return array
     */
    public function add_additional_fields_to_formatted_address( $fields, $customer_id, $type ) {
        if ( $type == 'billing' ) {
            $fields['invoice_email'] = get_user_meta( $customer_id, 'billing_invoice_email', true );
            $fields['vat'] = get_user_meta( $customer_id, 'billing_vat', true );
        }
        return $fields;
    }

    /**
     * Add additional fields to billing address (in Order received)
     * 
     * @param array $fields Checkout billing address fields
     * @param object $order Current order object instance
     * @return array
     */
    public function add_additional_fields_to_order_details( $fields, $order ) {
        $fields['invoice_email'] = $order->get_meta( '_billing_invoice_email', true );
        $fields['vat'] = $order->get_meta( '_billing_vat', true );
        return $fields;
    }

    /**
     * Add additional fields to billing address (blocks)
     */
    public function add_additional_fields_to_address_block() {
        // VAT number
        $option = get_option( 'wcb2b_add_vatnumber', 'hidden' );
        if ( 'hidden' !== $option ) {
            woocommerce_register_additional_checkout_field( array(
                'id'            => 'woocommerce-b2b/vat-number',
                'label'         => esc_html__( 'Vat number', 'woocommerce-b2b' ),
                'optionalLabel' => esc_html__( 'Vat number (optional)', 'woocommerce-b2b' ),
                'location'      => 'address',
                'required'      => 'required' === $option,
                'attributes'    => array(
                    'autocomplete'     => 'vat-number',
                    'title'            => esc_html__( 'Vat number', 'woocommerce-b2b' ),
                ),
            ) );
        }

        // Invoice email
        $option = get_option( 'wcb2b_add_invoice_email', 'hidden' );
        if ( 'hidden' !== $option ) {
            woocommerce_register_additional_checkout_field( array(
                'id'            => 'woocommerce-b2b/invoice-email',
                'label'         => esc_html__( 'Email address for invoices', 'woocommerce-b2b' ),
                'optionalLabel' => esc_html__( 'Email address for invoices (optional)', 'woocommerce-b2b' ),
                'location'      => 'address',
                'required'      => 'required' === $option,
                'attributes'    => array(
                    'autocomplete'     => 'invoice-email',
                    'title'            => esc_html__( 'Email address for invoices', 'woocommerce-b2b' ),
                ),
            ) );
        }
    }

    /**
     * Link addtional fields definition between classic version and block version
     * 
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     * @param string $type Address type
     * @param object $woocommerce WooCommerce instance
     */
    public function additional_fields_block_retrocompatibility( $meta_key, $meta_value, $type, $woocommerce ) {
        // VAT number
        if ( 'woocommerce-b2b/vat-number' === $meta_key ) {
            if ( 'billing' === $type ) {
                $woocommerce->update_meta_data( '_billing_vat', $meta_value, true );
            }
        }
        // Invoice email
        if ( 'woocommerce-b2b/invoice-email' === $meta_key ) {
            if ( 'billing' === $type ) {
                $woocommerce->update_meta_data( '_billing_invoice_email', $meta_value, true );
            }
        }
    }

    /**
     * Map invoice email additional field
     * 
     * @param mixed $value Field value
     * @param string $type Address type
     * @param object $woocommerce WooCommerce instance
     * @return mixed
     */
    public function map_invoicemail_field_to_block( $value, $type, $woocommerce ) {
        return $woocommerce->get_meta( '_billing_invoice_email' );
    }

    /**
     * Map vat number additional field
     * 
     * @param mixed $value Field value
     * @param string $type Address type
     * @param object $woocommerce WooCommerce instance
     * @return mixed
     */
    public function map_vatnumber_field_to_block( $value, $type, $woocommerce ) {
        return $woocommerce->get_meta( '_billing_vat' );
    }

    /**
     * Add new columns to list table's header
     *
     * @param array $shipping_class_columns Array of intial shipping class columns
     * @return array
     */
    public function add_shipping_class_estimated_delivery( $columns ) {
        $columns = array_slice( $columns, 0, 2 ) + array(
            'wcb2b_delivery_days_min' => esc_html__( 'Delivery days (min)', 'woocommerce-b2b' ),
            'wcb2b_delivery_days_max' => esc_html__( 'Delivery days (max)', 'woocommerce-b2b' ),
        ) + array_slice( $columns, 2, 3 );
        return $columns;
    }

    /**
     * Display new attributes
     */
    public function populate_shipping_class_estimated_delivery() {
        $current_action = current_filter();
        // Cropping out the current action name to get only the field name.
        $field = str_replace( 'woocommerce_shipping_classes_column_', '', $current_action );
        switch ( $field ) {
            case 'wcb2b_delivery_days_min' :
                printf( '<div class="view">{{ data.wcb2b_delivery_days_min }}</div>' );
                printf( '<div class="edit"><input type="text" name="wcb2b_delivery_days_min" data-attribute="wcb2b_delivery_days_min" value="{{ data.wcb2b_delivery_days_min }}" placeholder="%s" /></div>',
                    esc_attr__( 'Delivery days (min)', 'woocommerce-b2b' )
                );
                break;
            case 'wcb2b_delivery_days_max':
                printf( '<div class="view">{{ data.wcb2b_delivery_days_max }}</div>' );
                printf( '<div class="edit"><input type="text" name="wcb2b_delivery_days_max" data-attribute="wcb2b_delivery_days_max" value="{{ data.wcb2b_delivery_days_max }}" placeholder="%s" /></div>',
                    esc_attr__( 'Delivery days (max)', 'woocommerce-b2b' )
                );
                break;
            default:
                break;
        }
    }

    /**
     * Modify shipping class default data before localization
     * This will add the values of new fields from the database to the view
     *
     * @param array $shipping_class Array of shipping classes
     * @return array
     */
    public function show_shipping_class_estimated_delivery( $shipping_class ) {
        $classes          = array();
        $class_new_fields = array( 'wcb2b_delivery_days_min', 'wcb2b_delivery_days_max' );
        foreach ( $shipping_class as $key => $class ) {
            // Convert shipping class object to array.
            $data = (array)$class;
            // Add new field value to array.
            foreach ( $class_new_fields as $meta_field ) {
                $data[$meta_field] = get_term_meta( $class->term_id, $meta_field, true );
            }
            // Convert back to object format
            // This makes a object of stdClass instead, which will also work
            $classes[$key] = new WP_Term( (object)$data );
        }
        return $classes;
    }

    /**
     * Save updated fields values to shipping class meta data
     *
     * @param integer $term_id Shipping class ID
     * @param array $data Data obtained from the frontend
     */
    public function save_shipping_class_estimated_delivery( $term_id, $data ) {
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, array( 'wcb2b_delivery_days_min', 'wcb2b_delivery_days_max' ), true ) ) {
                update_term_meta( $term_id, $key, $value );
            }
        }
    }

    /**
     * Make unique values for delivery holidays
     *
     * @param mixed $value Current value
     * @param string $option Option name
     * @param mixed $raw_value Current raw value
     * @return mixed
     */
    public function unique_delivery_holidays( $value, $option, $raw_value ) {
        return implode( ',', array_unique( array_map( 'trim', explode( ',', $value ) ) ) );
    }

    /**
     * Calculate group regular price
     * 
     * @param string $price Current product price
     * @param object $object Current product/variation instance
     * @return string
     */
    public function calculate_regular_price( $price, $object ) {
        return wcb2b_get_group_price( $price, $object->get_id(), 'regular_price' );
    }

    /**
     * Calculate group sale price
     * 
     * @param string $price Current product price
     * @param object $object Current product/variation instance
     * @return string
     */
    public function calculate_sale_price( $price, $object ) {
        return wcb2b_get_group_price( $price, $object->get_id(), 'sale_price' );
    }

    /**
     * Calculate group final price
     * 
     * @param string $price Current product price
     * @param object $object Current product/variation instance
     * @return string
     */
    public function calculate_price( $price, $object ) {
        return wcb2b_get_group_price( $price, $object->get_id(), 'price' );
    }

    /**
     * Automatically change group
     *   
     * @param integer $order_id Order ID
     * @param string $old_status Order previous status
     * @param string $new_status Order NEW status
     * @return boolean
     */
    public function automatic_group_change( $order_id, $old_status, $new_status ) {
        $rules = get_option( 'wcb2b_automatic_group_change', array() );
        if ( empty( $rules ) ) { return false; }

        $order = wc_get_order( $order_id );
        $customer_id = $order->get_user_id();
        $customer_group_id = wcb2b_get_customer_group();

        // Allow to exclude guest users from change group
        if ( $customer_group_id === get_option( 'wcb2b_guest_group' ) && apply_filters( 'wcb2b_exclude_guest_from_automatic_group_change', false ) ) {
            return false;
        }

        $total_spent = wc_get_customer_total_spent( $customer_id );
        foreach ( $rules as $rule ) {
            if ( $total_spent > $rule['limit'] ) {
                if ( WP_DEBUG_LOG ) {
                    wc_get_logger()->info(
                        sprintf( 'Customer ID %s has reached %s total spent (limit %s) with order ID %s (%s -> %s). Moved to group %s',
                            $customer_id,
                            $total_spent,
                            $rule['limit'],
                            $order_id,
                            $old_status,
                            $new_status,
                            $rule['group']
                        ), array(
                        'source' => 'wcb2b-automatic-group_change-log'
                    ) );
                }
                return update_user_meta( $customer_id, 'wcb2b_group', $rule['group'] );
            }
        }
        return false;
    }

    /**
     * Remove not allowed payment gateways
     * 
     * @param array $available_gateways List of available payment methods
     * @return array
     */
    public function disable_payment_methods( $available_gateways ) {
        $customer_group_id = wcb2b_get_customer_group();
        if ( $customer_group_id ) {
            // Retrieve group allowed gateways value
            $group_gateways = get_post_meta( $customer_group_id, 'wcb2b_group_gateways', true );
            if ( is_array( $group_gateways ) ) {
                foreach ( $available_gateways as $available_gateway => $data ) {
                    // Disable payment method
                    if ( in_array( $available_gateway, $group_gateways ) ) {
                        unset( $available_gateways[$available_gateway] );
                    }
                }
            }
        }
        // Remove quotation payment method in order pay page
        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            if ( isset( $available_gateways['wcb2b_quotation_gateway'] ) ) {
                unset( $available_gateways['wcb2b_quotation_gateway'] );
            }
        } else {
            $show_only_quotations = get_post_meta( $customer_group_id, 'wcb2b_group_quotations_with_hidden_prices', true );
            if ( $show_only_quotations ) {
                if ( isset( $available_gateways['wcb2b_quotation_gateway'] ) ) {
                    $available_gateways = array(
                        'wcb2b_quotation_gateway' => $available_gateways['wcb2b_quotation_gateway']
                    );
                }
            }
        }
        return $available_gateways;
    }

    /**
     * Remove not allowed shipping methods
     * 
     * @param array $rates List of available rates
     * @return array
     */
    public function disable_shipping_methods( $shipping_rates ) {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            // Retrieve group allowed gateways value
            $group_shippings = get_post_meta( $customer_group_id, 'wcb2b_group_shippings', true );
            if ( is_array( $group_shippings ) ) {
                foreach ( $shipping_rates as $shipping_rate_key => $shipping_rate ) {
                    if ( in_array( $shipping_rate_key, $group_shippings ) ) {
                        unset( $shipping_rates[$shipping_rate_key] );
                    }
                    if ( in_array( $shipping_rate->get_method_id(), $group_shippings ) ) {
                        unset( $shipping_rates[$shipping_rate_key] );
                    }
                }
            }
        }
        return $shipping_rates;
    }

    /**
     * Hide prices for quotations
     */
    public function hide_prices_hooks() {
        // Check if group exists or is deleted precedently
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( get_post_meta( $customer_group_id, 'wcb2b_group_hide_prices', true ) ) {
                // Verify if quotations are allowed
                if ( get_post_meta( $customer_group_id, 'wcb2b_group_quotations_with_hidden_prices', true ) ) {
                    // Replace prices with message
                    add_filter( 'woocommerce_get_formatted_order_total', function( $formatted_total, $order, $tax_display, $display_refunded ) {
                        if ( $order->has_status( 'on-quote' ) ) {
                            return wcb2b_quotation_prices_placeholder();
                        }
                        return $formatted_total;
                    }, 10, 4 );
                    add_filter( 'woocommerce_order_formatted_line_subtotal', function( $subtotal, $item, $order ) {
                        if ( $order->has_status( 'on-quote' ) ) {
                            return wcb2b_quotation_prices_placeholder();
                        }
                        return $subtotal;
                    }, 10, 3 );
                    add_filter( 'woocommerce_get_order_item_totals', function( $total_rows, $order, $tax_display ) {
                        if ( $order->has_status( 'on-quote' ) ) {
                            return array();
                        }
                        return $total_rows;
                    }, 10, 3 );
                }
            }
        }
    }

}

return new WCB2B_Hooks();