<?php

/**
 * WooCommerce B2B Frontend MyAccount set-up Class
 *
 * @version 4.0.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Frontend_Myaccount Class
 */
class WCB2B_Frontend_Myaccount {

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
        if ( get_option( 'wcb2b_enable_quotations' ) === 'yes' ) {
            add_action( 'woocommerce_account_quotations_endpoint', array( $this, 'add_quotations_endpoint_content' ) );
            add_filter( 'woocommerce_endpoint_quotations_title', array( $this, 'add_quotations_endpoint_title' ), 10, 2 );
            add_filter( 'woocommerce_get_endpoint_url', array( $this, 'quotations_pagination' ), 10, 4 );
            add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_vars' ), 10 );
            add_filter( 'woocommerce_account_menu_items', array( $this, 'add_quotations_menu' ) );
            add_filter( 'woocommerce_account_menu_item_classes', array( $this, 'active_quotations_menu' ), 10, 2 );
            add_filter( 'woocommerce_my_account_my_orders_query', array( $this, 'remove_quotations_from_orders' ), 10, 1 );
            add_action( 'woocommerce_view_order', array( $this, 'add_payment_button' ), 99 );
            add_action( 'woocommerce_view_order', array( $this, 'add_quotation_validity' ), 99 );
            add_filter( 'woocommerce_get_order_item_totals', array( $this, 'remove_quotations_payment_method' ), 10, 3 );
            add_filter( 'woocommerce_get_return_url', array( $this, 'redirect_to_quotations' ), 10, 2 );
        }
        if ( get_option( 'wcb2b_registration_notice' ) === 'yes' ) {
            add_action( 'woocommerce_created_customer', array( $this, 'notify_admin' ), 99 );
            add_filter( 'wp_new_user_notification_email_admin', array( $this, 'user_data' ), 10, 3 );
        }
        if ( get_option( 'wcb2b_restricted_catalog' ) === 'yes' ) {
            add_action( 'template_redirect', array( $this, 'restrict_catalog' ), 10 );
            add_filter( 'wp_get_nav_menu_items', array( $this, 'remove_from_menu' ), 10, 3 );
            add_filter( 'get_pages', array( $this, 'remove_from_list' ), 10, 2 );
            add_filter( 'widget_display_callback', array( $this, 'remove_from_widgets' ), 10, 3 );
            add_action( 'init', array( $this, 'remove_from_search' ) );
        }
        if ( get_option( 'wcb2b_default_group' ) === 'choose' ) {
            add_action( 'woocommerce_register_form_start', array( $this, 'group_add_field' ) );
        }

        add_action( 'woocommerce_register_form', array( $this, 'add_business_certificate_field' ) );
        add_action( 'woocommerce_edit_account_form', array( $this, 'add_business_certificate_field' ) );
        add_action( 'woocommerce_save_account_details_errors', array( $this, 'validate_business_certificate_field' ) );
        add_action( 'woocommerce_registration_errors', array( $this, 'validate_business_certificate_field' ) );
        add_filter( 'woocommerce_form_field_text', array( $this, 'business_certificate_field_type' ), 10, 4 );
        add_action( 'woocommerce_edit_account_form_tag', array( $this, 'add_registration_form_enctype' ) );
        add_action( 'woocommerce_register_form_tag', array( $this, 'add_registration_form_enctype' ) );
        add_action( 'woocommerce_register_form_tag', array( $this, 'add_registration_form_reload' ) );
        add_action( 'woocommerce_register_form_start', array( $this, 'add_extended_fields' ) );
        add_action( 'woocommerce_register_post', array( $this, 'validate_extended_fields' ), 10, 3 );
        add_action( 'woocommerce_created_customer', array( $this, 'save_extended_fields' ) );
        add_action( 'woocommerce_register_post', array( $this, 'invoice_email_validation_registration' ), 11, 3 );
        add_action( 'woocommerce_register_post', array( $this, 'vatnumber_validation_registration' ), 11, 3 );
        add_filter( 'woocommerce_new_customer_data', array( $this, 'update_user_data' ) );
        add_filter( 'authenticate', array( $this, 'prevent_login' ), 98, 2 );
        add_filter( 'woocommerce_registration_auth_new_customer', array( $this, 'prevent_autologin' ), 98, 2 );
        add_action( 'lostpassword_post', array( $this, 'disallow_password_reset' ), 10, 2 );
        add_action( 'validate_password_reset', array( $this, 'disallow_password_reset' ), 10, 2 );
        add_action( 'template_redirect', array( $this, 'redirect_login_forms' ), 5 );
        add_action( 'woocommerce_account_dashboard', array( $this, 'display_unpaid_orders_total' ) );
        add_action( 'woocommerce_account_dashboard', array( $this, 'show_customer_group' ) );
        add_action( 'woocommerce_account_dashboard', array( $this, 'display_discount' ) );
        add_filter( 'woocommerce_account_menu_items', array( $this, 'add_saved_carts_menu' ), 99, 1 );
        add_filter( 'woocommerce_endpoint_saved-carts_title', array( $this, 'add_saved_carts_endpoint_title' ), 10, 2 );
        add_action( 'woocommerce_account_saved-carts_endpoint', array( $this, 'add_saved_carts_endpoint_content' ) );
        add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'order_again_button' ), 99, 2 );
        add_filter( 'woocommerce_address_to_edit', array( $this, 'disable_billing_address_edit' ), 11 );
        add_action( 'woocommerce_before_edit_address_form_billing', array( $this, 'disable_billing_address_edit_notice' ) );
    }

    /**
     * Add quotations my-account page endpoint content
     * 
     * @param int $current_page Current page for pagination
     */
    public function add_quotations_endpoint_content( $current_page ) {
        $current_page = empty( $current_page ) ? 1 : absint( $current_page );
        $customer_orders = wc_get_orders( array(
            'customer' => get_current_user_id(),
            'page'     => $current_page,
            'paginate' => true,
            'post_status' => array( 'wc-on-quote', 'wc-quoted' )
        ) );

        wc_get_template(
            'myaccount/orders.php',
            array(
                'current_page'    => absint( $current_page ),
                'customer_orders' => $customer_orders,
                'has_orders'      => 0 < $customer_orders->total,
                'wp_button_class' => wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '',
            )
        );
    }

    /**
     * Add quotations my-account page endpoint content title
     * 
     * @param string $title Page title
     * @param string $endpoint Current endpoint
     * @return string
     */
    public function add_quotations_endpoint_title( $title, $endpoint ) {
        return esc_html__( 'Quotations', 'woocommerce-b2b' );
    }

    /**
     * Adjust quotations pagination URLs
     *   
     * @param string $url Endpoint URL
     * @param string $endpoint Current endpoint name
     * @param integer $value Page number
     * @param string $permalink Current permalink
     * @return string
     */
    public function quotations_pagination( $url, $endpoint, $value, $permalink ) {
        if ( did_action( 'woocommerce_before_account_orders_pagination' ) ) {
            if ( 'orders' == $endpoint && is_wc_endpoint_url( 'quotations' ) ) {
                return wc_get_endpoint_url( 'quotations', $value );
            }
        }
        return $url;
    }

    /**
     * Add query vars to manage endpoint
     * 
     * @param array $query_vars Query vars list
     * @return array
     */
    public function add_query_vars( $query_vars ) {
        $query_vars['quotations'] = get_option( 'wcb2b_account_quotations_endpoint', 'quotations' );
        $query_vars['saved-carts'] = get_option( 'wcb2b_account_saved_carts_endpoint', 'saved-carts' );
        return $query_vars;
    }

    /**
     * Add quotations endpoint to my-account menu
     * 
     * @param array $menu_links Menu links
     * @return array
     */
    public function add_quotations_menu( $menu_links ) {
        $menu_links = array_slice( $menu_links, 0, 1, true) +
            array( 'quotations' => __( 'Quotations', 'woocommerce-b2b' ) ) +
            array_slice( $menu_links, 1, count( $menu_links ) - 1, true );
        return $menu_links;
    }

    /**
     * Add quotations menu active link management
     * 
     * @param array $classes Current menu link classes
     * @param string $endpoint Current endpoint
     * @return array
     */
    public function active_quotations_menu( $classes, $endpoint ) {
        global $wp;

        if ( isset( $wp->query_vars['view-order'] ) ) {
            $order = wc_get_order( $wp->query_vars['view-order'] );

            if ( 'quotations' === $endpoint && $order->has_status( array( 'quoted', 'on-quote' ) ) ) {
                $classes[] = 'is-active';
            }
            if ( 'orders' === $endpoint && $order->has_status( array( 'quoted', 'on-quote' ) ) ) {
                if ( ( $key = array_search( 'is-active', $classes ) ) !== false ) {
                    unset( $classes[$key] );
                }
            }
        }
        return $classes;
    }

    /**
     * Remove quotations from orders list in my account page
     * 
     * @param array $args Arguments
     * @return array
     */
    public function remove_quotations_from_orders( $args ) {
        $args['post_status'] = array_diff( array_keys( wc_get_order_statuses() ), array( 'wc-on-quote', 'wc-quoted', 'wc-checkout-draft' ) );
        return $args;
    }

    /**
     * Add payment button to quotations
     * 
     * @param integer $order_id Current order ID
     */
    public function add_payment_button( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $order->has_status( 'quoted' ) ) {
            if ( $valid_until = $order->get_meta( '_wcb2b_quotation_validity', true ) ) {
                $now = new DateTime();
                $validity = new DateTime( $valid_until );
                if ( $validity < $now ) {
                    return false;
                }
            }
            printf( '<a href="%s" class="button">%s</a>',
                esc_url( $order->get_checkout_payment_url() ),
                esc_html__( 'Pay now', 'woocommerce-b2b' )
            );
        }
    }

    /**
     * Add quotation validity in myaccount
     *
     * @param integer $order_id Order ID 
     */
    public function add_quotation_validity( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $order->has_status( 'quoted' ) ) {
            if ( $valid_until = $order->get_meta( '_wcb2b_quotation_validity', true ) ) {
                printf( '<p><strong>%s:</strong> %s</p>',
                    esc_html__( 'Quotation valid until', 'woocommerce-b2b' ),
                    date_i18n( get_option( 'date_format' ), strtotime( $valid_until ) )
                );
            }
        }
    }

    /**
     * Prevent quotation display as payment method in order review
     * 
     * @param array $rows Order rows
     * @param object $order Current order instance
     * @param boolean $tax_display Tax display
     * @return array
     */
    public function remove_quotations_payment_method( $rows, $order, $tax_display ) {
        if ( is_object($order) && $order->has_status( array( 'quoted', 'on-quote' ) ) ) {
            unset( $rows['payment_method'] );
        }
        return $rows;
    }

    /**
     * Redirect customers on proper quotation endpoint after placed quotation
     * 
     * @param string $return_url Redirect to URL
     * @param object $order Current order object
     * @return string
     */
    public function redirect_to_quotations( $return_url, $order ) {
        if ( is_object($order) && $order->has_status( 'on-quote' ) ) {
            return wc_get_account_endpoint_url( 'quotations' );
        }
        return $return_url;
    }

    /**
     * Add verification file field
     */
    public function add_business_certificate_field() {
        // Maybe I show the field?
        $show = false;
        $required = false;
        // Business certificate field also is shown in myaccount area, so if customer is logged in I need to retrieve his group,
        // because shorcode no longer exists
        if ( is_user_logged_in() ) {
            $customer_group_id = wcb2b_get_customer_group();
        } else {
            if ( isset( $_POST['wcb2b_group'] ) ) {
                $customer_group_id = $_POST['wcb2b_group'];
            } else {
                global $post;
                // Is a group form?
                $customer_group_id = get_option( 'wcb2b_guest_group', false );
                if ( is_object( $post ) ) {
                    $loginform_group_id = get_post_meta( $post->ID, 'wcb2bloginform', true );
                    if ( ! empty( $loginform_group_id ) ) {
                        $customer_group_id = $loginform_group_id;
                    }
                }
            }
        }
        if ( $option = get_post_meta( $customer_group_id, 'wcb2b_group_add_business_certificate', true ) ) {
            if ( 'hidden' !== $option ) {
                $show = true;
                $required = 'required' === $option;
            }
        }
        if ( $show ) {
            wc_get_template( 'myaccount/business-certificate-field.php', apply_filters( 'wcb2b_business_certificate_args', array(
                'can_edit' => true,
                'value'    => get_user_meta( get_current_user_id(), 'wcb2b_business_certificate', true ),
                'required' => $required,
                'maxsize'  => 10485760,
                'allowed'  => array(
                    'image/*',
                    '.pdf'
                )
            ), $customer_group_id ), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
        }
    }

    /**
     * Manage file upload
     * 
     * @param array $errors Any possibile error
     * @return array
     */
    public function validate_business_certificate_field( $errors ) {
        // Maybe I show the field?
        $show = false;
        $required = false;
        // Business certificate field also is shown in myaccount area, so if customer is logged in I need to retrieve his group,
        // because shorcode no longer exists
        if ( is_user_logged_in() ) {
            $customer_group_id = wcb2b_get_customer_group();
        } else {
            if ( isset( $_POST['wcb2b_group'] ) ) {
                $customer_group_id = $_POST['wcb2b_group'];
            } else {
                global $post;
                // Is a group form?
                $customer_group_id = get_option( 'wcb2b_guest_group', false );
                if ( is_object( $post ) ) {
                    $loginform_group_id = get_post_meta( $post->ID, 'wcb2bloginform', true );
                    if ( ! empty( $loginform_group_id ) ) {
                        $customer_group_id = $loginform_group_id;
                    }
                }
            }
        }
        if ( ! is_checkout() ) {
            if ( $option = get_post_meta( $customer_group_id, 'wcb2b_group_add_business_certificate', true ) ) {
                if ( 'hidden' !== $option ) {
                    $show = true;
                    $required = 'required' === $option;
                }
            }
            if ( $show ) {
                // Check if customer asked for delete verification file
                if ( isset( $_POST['wcb2b_business_certificate_delete'] ) ) {
                    add_action( 'woocommerce_save_account_details', function( $customer_id ) {
                        delete_user_meta( $customer_id, 'wcb2b_business_certificate' );
                    } );
                }
                $upload_result = $_FILES['wcb2b_business_certificate']['error'] ?? 4; // 4 (Not found by default)
                // File not present, check for required
                if ( 4 === $upload_result ) {
                    if ( $required ) {
                        $errors->add( 'wcb2b_business_certificate_error', sprintf( __( 'Business certificate is a required field.', 'woocommerce-b2b' ) ) );
                        return $errors;
                    }
                } else {
                    // File is present, look for errors
                    if ( $upload_result > 0 ) {
                        switch ( $upload_result ) {
                            case 1 :
                            case 2 :
                                $err = esc_html__( 'Uploaded file exceeds max size allowed', 'woocommerce-b2b' );
                                break;
                            case 3 :
                            case 4 :
                            case 5 :
                            case 6 :
                            case 7 :
                            case 8 :
                                $err = esc_html__( 'A problem has occurred', 'woocommerce-b2b' );
                                break;
                            default :
                                $err = esc_html__( 'An unknown problem has occurred', 'woocommerce-b2b' );
                                break;
                        }
                        $errors->add( 'wcb2b_business_certificate_error', sprintf( '%s [%s]', $err, $upload_result ) );
                        return $errors;
                    }
                    // No errors, now upload!
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    $attachment_id = media_handle_upload( 'wcb2b_business_certificate', 0 );
                    if ( is_wp_error( $attachment_id ) ) {
                        $errors->add( 'wcb2b_business_certificate_error', sprintf( __( 'An upload error occurred: %s', 'woocommerce-b2b' ), $attachment_id->get_error_message() ) );
                    } else {
                        // Attach file verification to customer
                        $callback = function( $customer_id ) use ( $attachment_id ) {
                            update_user_meta( $customer_id, 'wcb2b_business_certificate', $attachment_id );
                        };
                        add_action( 'woocommerce_save_account_details', $callback );
                        add_action( 'woocommerce_created_customer', $callback );
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * In case of verfication file field, change type attribute for HTML input tag
     * 
     * @param string $field Field HTML markup
     * @param string $key Field ID
     * @param array $args Field arguments
     * @param mixed $value Field current value
     * @return string
     */
    public function business_certificate_field_type( $field, $key, $args, $value ) {
        if ( $key === 'wcb2b_business_certificate' ) {
            return str_replace( 'type="text"', 'type="file"', $field );
        }
        return $field;
    }

    /**
     * Add attribute to manage files upload in myaccount forms
     */
    public function add_registration_form_enctype() {
        printf( 'enctype="multipart/form-data"' );
    }

    /**
     * Add attribute to manage JS fragment reload myaccount forms
     */
    public function add_registration_form_reload() {
        $reload = apply_filters( 'wcb2b_registration_form_reload', true );
        printf( ' data-wcb2b-reload="' . $reload . '" ' );
    }

    /**
     * Add extended fields to customer registration
     */
    public function add_extended_fields() {
        if ( apply_filters( 'wcb2b_extend_registration_form_b2b_only', false ) && is_account_page() ) { return; }

        // Maybe I show the field?
        $show = false;
        if ( isset( $_POST['wcb2b_group'] ) ) {
            $customer_group_id = $_POST['wcb2b_group'];
        } else {
            global $post;
            // Is a group form?
            $customer_group_id = get_option( 'wcb2b_guest_group', false );
            if ( is_object( $post ) ) {
                $loginform_group_id = get_post_meta( $post->ID, 'wcb2bloginform', true );
                if ( ! empty( $loginform_group_id ) ) {
                    $customer_group_id = $loginform_group_id;
                }
            }
        }
        if ( get_post_meta( $customer_group_id, 'wcb2b_group_extend_registration_fields', true ) ) {
            $show = true;
        }
        if ( $show ) {
            wp_enqueue_script( 'wc-country-select' );
            wp_enqueue_script( 'wc-address-i18n' );
            wp_enqueue_script( 'wc-checkout' );

            $checkout = WC_Checkout::instance();
            $fields = apply_filters( 'wcb2b_register_form_fields', $checkout->get_checkout_fields( 'billing' ), $customer_group_id );

            foreach ( $fields as $key => $field ) {
                // Prevent email field replication
                if ( $key == 'billing_email' ) { continue; }

                if ( isset( $field['country_field'], $fields[ $field['country_field'] ] ) ) {
                    $field['country'] = $checkout->get_value( $field['country_field'] );
                }
                woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
            }
        }
    }

    /**
     * Validate extended fields
     * 
     * @param string $username Post registering username
     * @param string $email Post registering email
     * @param array $errors List of errors
     * @return array
     */
    public function validate_extended_fields( $username, $email, $errors ) {
        if ( defined( 'REST_REQUEST' ) ) { return $errors; }

        $checkout = WC_Checkout::instance();
        $fields = apply_filters( 'wcb2b_register_form_fields', $checkout->get_checkout_fields( 'billing' ), 0 );
        $data = $_POST;

        foreach ( $fields as $key => $field ) {
            if ( $key == 'billing_email' ) { continue; }

            if ( ! isset( $data[ $key ] ) ) {
                continue;
            }

            $required    = ! empty( $field['required'] ) && ! is_user_logged_in(); // WCB2BSA tweak
            $format      = array_filter( isset( $field['validate'] ) ? (array) $field['validate'] : array() );
            $field_label = isset( $field['label'] ) ? $field['label'] : '';

            if ( in_array( 'postcode', $format ) ) {
                $country      = isset( $data[ 'billing_country' ] ) ? $data[ 'billing_country' ] : WC()->customer->{"get_billing_country"}();
                $data[ $key ] = wc_format_postcode( $data[ $key ], $country );

                if ( '' !== $data[ $key ] && ! WC_Validation::is_postcode( $data[ $key ], $country ) ) {
                    switch ( $country ) {
                        case 'IE':
                            /* translators: %1$s: field name, %2$s finder.eircode.ie URL */
                            $postcode_validation_notice = sprintf( __( '%1$s is not valid. You can look up the correct Eircode <a target="_blank" href="%2$s">here</a>.', 'woocommerce-b2b' ), '<strong>' . esc_html( $field_label ) . '</strong>', 'https://finder.eircode.ie' );
                            break;
                        default:
                            /* translators: %s: field name */
                            $postcode_validation_notice = sprintf( __( '%s is not a valid postcode / ZIP.', 'woocommerce-b2b' ), '<strong>' . esc_html( $field_label ) . '</strong>' );
                    }
                    $errors->add( 'validation', apply_filters( 'woocommerce_checkout_postcode_validation_notice', $postcode_validation_notice, $country, $data[ $key ] ), array( 'id' => $key ) );
                }
            }

            if ( in_array( 'phone', $format ) ) {
                $data[ $key ] = wc_format_phone_number( $data[ $key ] );

                if ( '' !== $data[ $key ] && ! WC_Validation::is_phone( $data[ $key ] ) ) {
                    /* translators: %s: phone number */
                    $errors->add( 'validation', sprintf( __( '%s is not a valid phone number.', 'woocommerce-b2b' ), '<strong>' . esc_html( $field_label ) . '</strong>' ), array( 'id' => $key ) );
                }
            }

            if ( in_array( 'email', $format ) && '' !== $data[ $key ] ) {
                $data[ $key ] = sanitize_email( $data[ $key ] );
                if ( ! is_email( $data[ $key ] ) ) {
                    /* translators: %s: email address */
                    $errors->add( 'validation', sprintf( __( '%s is not a valid email address.', 'woocommerce-b2b' ), '<strong>' . esc_html( $field_label ) . '</strong>' ), array( 'id' => $key ) );
                    continue;
                }
            }

            if ( '' !== $data[ $key ] && in_array( 'state', $format ) ) {
                $country      = isset( $data[ 'billing_country' ] ) ? $data[ 'billing_country' ] : WC()->customer->{"get_billing_country"}();
                $valid_states = WC()->countries->get_states( $country );

                if ( ! empty( $valid_states ) && is_array( $valid_states ) && count( $valid_states ) > 0 ) {
                    $valid_state_values = array_map( 'wc_strtoupper', array_flip( array_map( 'wc_strtoupper', $valid_states ) ) );
                    $data[ $key ]       = wc_strtoupper( $data[ $key ] );

                    if ( isset( $valid_state_values[ $data[ $key ] ] ) ) {
                        // With this part we consider state value to be valid as well, convert it to the state key for the valid_states check below.
                        $data[ $key ] = $valid_state_values[ $data[ $key ] ];
                    }

                    if ( ! in_array( $data[ $key ], $valid_state_values ) ) {
                        /* translators: 1: state field 2: valid states */
                        $errors->add( 'validation', sprintf( __( '%1$s is not valid. Please enter one of the following: %2$s', 'woocommerce-b2b' ), '<strong>' . esc_html( $field_label ) . '</strong>', implode( ', ', $valid_states ) ), array( 'id' => $key ) );
                    }
                }
            }

            if ( $required && '' === $data[ $key ] ) {
                /* translators: %s: field name */
                $errors->add( 'required-field', apply_filters( 'woocommerce_checkout_required_field_notice', sprintf( __( '%s is a required field.', 'woocommerce-b2b' ), '<strong>' . esc_html( $field_label ) . '</strong>' ), $field_label ), array( 'id' => $key ) );
            }
        }

        // If allow to choose is enabled
        if ( isset( $data['wcb2b_group'] ) ) {
            if ( empty( $data['wcb2b_group'] ) ) {
                $errors->add( 'validation', __( 'Please choose a group', 'woocommerce-b2b' ) );
            }
        }

        return $errors;
    }

    /**
     * Save extended fields to customer registration
     * 
     * @param int $customer_id Current customer ID
     */
    public function save_extended_fields( $customer_id ) {
        if ( defined( 'REST_REQUEST' ) ) { return true; }

        $checkout = WC_Checkout::instance();
        $fields = apply_filters( 'wcb2b_register_form_fields', $checkout->get_checkout_fields( 'billing' ), 0 );

        foreach ( $fields as $key => $field ) {
            // Prevent email field replication
            if ( $key == 'billing_email' ) { continue; }

            if ( ! isset( $_POST[$key] ) ) {
                continue;
            }

            update_user_meta( $customer_id, $key, sanitize_text_field( $_POST[$key] ) );
            if ( $key == 'billing_first_name' ) {
                update_user_meta( $customer_id, 'first_name', sanitize_text_field( $_POST[$key] ) );
            }
            if ( $key == 'billing_last_name' ) {
                update_user_meta( $customer_id, 'last_name', sanitize_text_field( $_POST[$key] ) );
            }
        }
    }

    /**
     * Send new registration notice to admin by email
     * 
     * @param int $customer_id Current customer ID
     */
    public function notify_admin( $customer_id ) {
        wp_new_user_notification( $customer_id, null, 'admin' );
    }

    /**
     * Add new customer data in admin registration notification
     *
     * @param array $wp_new_user_notification_email_admin Email data
     * @param object $user New user object
     * @param string $blogname WordPress blog name
     * @return array
     */
    public function user_data( $wp_new_user_notification_email_admin, $user, $blogname ) {
        if ( apply_filters( 'wcb2b_extend_new_user_notification_email_admin', false ) ) {
            return $wp_new_user_notification_email_admin;
        }
        $customer = new WC_Customer( $user->ID );
        $address = $customer->get_billing();

        $wp_new_user_notification_email_admin['message'] .= "\r\n\r\n" . WC()->countries->get_formatted_address( $address, "\r\n" ) . "\r\n\r\n";
        if ( $group_id = get_user_meta( $user->ID, 'wcb2b_group', true ) ) {
            $wp_new_user_notification_email_admin['message'] .= sprintf( __( 'Group: %s (ID: %d)', 'woocommerce-b2b' ),
                get_the_title( $group_id ),
                $group_id
            ) . "\r\n\r\n";
        }
        return $wp_new_user_notification_email_admin;
    }

    /**
     * Check if invoice email is valid in extended fields
     *
     * @param string $username Post registering username
     * @param string $email Post registering email
     * @param array $errors List of errors
     * @return array
     */
    public function invoice_email_validation_registration( $username, $email, $errors ) {
        if ( isset( $_POST['billing_invoice_email'] ) ) {
            if ( ! empty( $_POST['billing_invoice_email'] ) && ! is_email( $_POST['billing_invoice_email'] ) ) {
                $errors->add( 'validation', esc_html__( 'Email address for invoices is not a valid email address', 'woocommerce-b2b' ) );
            }
        }
        return $errors;
    }

    /**
     * Check if VAT number is VIES valid in extended fields
     *
     * @param string $username Post registering username
     * @param string $email Post registering email
     * @param array $errors List of errors
     * @return array
     */
    public function vatnumber_validation_registration( $username, $email, $errors ) {
        if ( isset( $_POST['billing_country'], $_POST['billing_vat'] ) ) {
            $errors = wcb2b_valid_vies( $_POST['billing_country'], $_POST['billing_vat'], $errors, is_checkout() );
        }
        return $errors;
    }

    /**
     * Redirect to login/registration page not logged in customers
     */
    public function restrict_catalog() {
        if ( is_user_logged_in() ) { return; }
        if ( is_woocommerce() || is_cart() || is_checkout() || in_array( get_the_ID(), wcb2b_get_restricted_pages() ) ) {
            $redirect = admin_url();
            if ( $page_id = get_option( 'woocommerce_myaccount_page_id' ) ) {
                $redirect = get_permalink( $page_id );
            }

            wp_safe_redirect( apply_filters( 'wcb2b_restricted_catalog_redirect', $redirect ), 302 );
            exit;
        }
    }

    /**
     * Remove WooCommerce items from menu
     * 
     * @param array $items Menu items
     * @param string $menu Menu name
     * @param array $args Menu arguments
     * @return array
     */
    public function remove_from_menu( $items, $menu, $args ) {
        if ( ! is_user_logged_in() ) {
            $woocommerce_pages = wcb2b_get_restricted_pages();
            foreach ( $items as $key => $item ) {
                if ( in_array( $item->object, array( 'product', 'product_cat', 'product_brand', 'product_tag' ) ) || in_array( $item->object_id, $woocommerce_pages ) ) {
                    unset( $items[$key] );
                }
            }
        }
        return $items;
    }

    /**
     * Remove all WooCommerce pages from lists
     * 
     * @param array $pages Pages list
     * @param string $r
     * @return array
     */
    public function remove_from_list( $pages, $r ) {
        if ( did_action( 'init' ) ) {
            if ( ! is_user_logged_in() ) {
                $woocommerce_pages = wcb2b_get_restricted_pages();
                foreach ( $pages as $key => $page ) {
                    if ( in_array( $page->ID, $woocommerce_pages ) ) {
                        unset( $pages[$key] );
                    }
                }
            }
        }
        return $pages;
    }

    /**
     * Remove all WooCommerce widgets
     *
     * @param array $settings Settings list
     * @param string $widget Widget name
     * @param array $args Arguments
     * @return array
     */
    public function remove_from_widgets( $settings, $widget, $args ) {
        if ( ! is_user_logged_in() ) {
            if ( property_exists( $widget, 'widget_id' ) && strpos( $widget->widget_id, 'woocommerce' ) !== false ) {
                return false;
            }
        }
        return $settings;
    }

    /**
     * Remove product post type from search
     */
    public function remove_from_search() {
        if ( ! is_user_logged_in() ) {
            global $wp_post_types;
            if ( post_type_exists( 'product' ) ) {
                $wp_post_types['product']->exclude_from_search = true;
            }
        }
    }

    /**
     * Add group field to customer (in registration) when "Allow to chose" enabled
     */
    public function group_add_field() {
        if ( ! is_account_page() ) { return true; }

        $groups = wcb2b_get_groups();
        if ( $groups->found_posts ) {
            woocommerce_form_field( 'wcb2b_group', array(
                'type'          => 'select',
                'class'         => array( 'form-row-wide' ),
                'label'         => esc_html__( 'Choose your group', 'woocommerce-b2b' ),
                'required'      => true,
                'options'       => array( '' => __( '-- Select --', 'woocommerce-b2b' ) ) + array_reduce( $groups->posts, function( $result, $item ) {
                    $item = (array)$item;
                    $result[$item['ID']] = $item['post_title'];
                    return $result;
                } )
            ), $_POST['wcb2b_group'] ?? '' );
        }
    }

    /**
     * Add group and status to user on new registration
     *
     * @param array $data User data
     * @return array
     */
    public function update_user_data( $data ) {
        // Group
        $group = get_option( 'wcb2b_default_group', false );
        if ( ! empty( $_POST['wcb2b_group'] ) ) {
            $group = (int)$_POST['wcb2b_group'];
        }
        // Status
        $moderate = get_post_meta( $group, 'wcb2b_group_moderate_registration', true );
        // Bypass for customers without billing VAT number
        if ( 'yes' == get_option( 'wcb2b_add_vatnumber' ) && apply_filters( 'wcb2b_moderate_customer_registration_only_with_vat', false, $group ) ) {
            $billing_vat = $_POST['billing_vat'] ?? false;
            if ( '' === $billing_vat ) {
                // Enable customer status, we suppose is a B2C, so don't need moderation
                $moderate = 0;
            }
        }
        $data['meta_input']['wcb2b_group'] = $group;
        $data['meta_input']['wcb2b_status'] = (int)!$moderate;

        global $vies_validated_user, $vies_response;
        if (null !== $vies_validated_user) {
            $data['meta_input']['wcb2b_vies_validated_user'] = (int)$vies_validated_user;
            $data['meta_input']['wcb2b_vies_response'] = $vies_response;
        }

        return $data;
    }

    /**
     * Prevent login in wp-login page
     * 
     * @param object $user Current user instance
     * @param string $username Current user username
     * @return object
     */
    public function prevent_login( $user, $username ) {
        if ( ! is_null( $user ) && ! is_wp_error( $user ) ) {
            // If user isn't a customer, skip. Only customers could be inactive
            if ( wcb2b_has_role( $user->ID, 'customer' ) ) {
                $customer_group_id = wcb2b_get_customer_group( $user->ID );
                if ( $moderate = get_post_meta( $customer_group_id, 'wcb2b_group_moderate_registration', true ) ) {
                    $status = get_the_author_meta( 'wcb2b_status', $user->ID );
                    if ( 0 == (int)$status ) {
                        $message = __( 'Your account is now under review. You will receive an email as soon as it is activated.', 'woocommerce-b2b' );
                        $user = new WP_Error( 'authentication_failed', $message );
                    }
                }
            }
        }
        return $user;
    }

    /**
     * Prevent autologin on new registration
     * 
     * @param boolean $auth Prevent autologin?
     * @param integer $customer_id Current customer ID
     * @return boolean
     */
    public function prevent_autologin( $auth, $customer_id ) {
        if ( ! is_null( $customer_id ) && ! is_wp_error( $customer_id ) ) {
            // If user isn't a customer, skip. Only customers could be inactive
            if ( wcb2b_has_role( $customer_id, 'customer' ) ) {
                $status = get_the_author_meta( 'wcb2b_status', $customer_id );
                if ( 0 == (int)$status ) {
                    // WCB2BSA tweak
                    if ( ! is_user_logged_in() ) {
                        $message = __( 'Your account is now under review. You will receive an email as soon as it is activated.', 'woocommerce-b2b' );
                        wc_add_notice( apply_filters( 'wcb2b_waiting_approvation', $message ), 'notice' );
                    }
                    return false;
                }
            }
        }
        return $auth;
    }

    /**
     * Prevent login from reset password email link
     *
     * @param WP_Error $errors WP_Error instance
     * @param WP_User $user Current user instance
     */
    public function disallow_password_reset( $errors, $user ) {
        if ( ! is_null( $user->ID ) && wcb2b_has_role( $user->ID, 'customer' ) ) {
            $customer_group_id = wcb2b_get_customer_group( $user->ID );
            if ( $moderate = get_post_meta( $customer_group_id, 'wcb2b_group_moderate_registration', true ) ) {
                $status = get_the_author_meta( 'wcb2b_status', $user->ID );
                if ( 0 == (int)$status ) {
                    $errors->add( 'wcb2b_disallow_password_reset',  __( 'Your account is still under review. Please try again after receiving the approval email.', 'woocommerce-b2b' ) );
                }
            }
        }
    }

    /**
     * Redirect on default my-account page if customer is logged in and shortcode is custom form
     */
    public function redirect_login_forms() {
        global $post;
        if ( is_user_logged_in() && ! current_user_can( 'administrator' ) && is_object( $post ) ) {
            $loginform_group_id = get_post_meta( $post->ID, 'wcb2bloginform', true );
            if ( ! empty( $loginform_group_id ) ) {
                wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
                exit;
            }
        }
    }

    /**
     * Display unpaid orders total in user area
     */
    public function display_unpaid_orders_total() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            $show_unpaid = get_post_meta( $customer_group_id, 'wcb2b_group_show_unpaid', true );
            if ( $show_unpaid ) {
                $user_id = get_current_user_id();
                $unpaid_amount_limit = get_user_meta( $user_id, 'wcb2b_unpaid_limit', true );
                $unpaid_amount = wcb2b_get_total_unpaid( $user_id );
                if ( ! empty( $unpaid_amount_limit ) ) {
                    printf( '<div class="wcb2b-unpaid-total">%s</div><br />',
                        apply_filters( 'wcb2b_unpaid_message' , sprintf( esc_html__( 'Your unpaid amount: %s (Max allowed: %s)', 'woocommerce-b2b' ), 
                            wc_price( $unpaid_amount ),
                            wc_price( $unpaid_amount_limit )
                        ), get_current_user_id(), $unpaid_amount, $unpaid_amount_limit )
                    );
                }
            }
        }
    }

    /**
     * Show customer group in my-account page
     */
    public function show_customer_group() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            $show_name = get_post_meta( $customer_group_id, 'wcb2b_group_show_groupname', true );
            if ( $show_name ) {
                $wcb2b_group_name = get_the_title( $customer_group_id );
                echo '<div class="wcb2b-customer-group">' . apply_filters( 'wcb2b_show_group_message', sprintf( esc_html__( 'Your current group: %s', 'woocommerce-b2b' ), $wcb2b_group_name ), $wcb2b_group_name ) . '</div>';
            }
        }
    }

    /**
     * Display customer discount percentage in my-account page
     */
    public function display_discount() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            $show_discount_myaccount = get_post_meta( $customer_group_id, 'wcb2b_group_show_discount_myaccount', true );
            if ( $show_discount_myaccount ) {
                $price_rule = get_post_meta( $customer_group_id, 'wcb2b_group_price_rule', true );
                if ( in_array( $price_rule, array( 'global', 'both' ) ) ) {
                    // If has discount assigned, display
                    if ( $discount = get_post_meta( $customer_group_id, 'wcb2b_group_discount', true ) ) {
                        $discount = number_format( $discount, 
                            wc_get_price_decimals(),
                            wc_get_price_decimal_separator(),
                            wc_get_price_thousand_separator()
                        );
                        echo '<div class="wcb2b-discount-amount">' . apply_filters( 'wcb2b_discount_message' , sprintf( esc_html__( 'Discount amount assigned to you: %s%%', 'woocommerce-b2b' ), $discount ), $discount ) . '</div><br />';
                    }
                }
            }
        }
    }

    /**
     * Add saved carts endpoint to my-account menu
     * 
     * @param array $menu_links Menu links
     * @return array
     */
    public function add_saved_carts_menu( $menu_links ) {
        $customer_group_id = wcb2b_get_customer_group();
        if ( get_post_meta( $customer_group_id, 'wcb2b_group_save_cart', true ) ) {
            $menu_links = array_slice( $menu_links, 0, 1, true ) + array(
                'saved-carts' => __( 'Saved carts', 'woocommerce-b2b' )
            ) + array_slice( $menu_links, 1, count( $menu_links ), true );
        }
        return $menu_links;
    }

    /**
     * Add saved carts my-account page endpoint content title
     * 
     * @param string $title Page title
     * @param string $endpoint Current endpoint
     * @return string
     */
    public function add_saved_carts_endpoint_title( $title, $endpoint ) {
        return esc_html__( 'Saved carts', 'woocommerce-b2b' );
    }

    /**
     * Add saved carts my-account page endpoint content
     * 
     * @param int $current_page Current page for pagination
     */
    public function add_saved_carts_endpoint_content( $current_page ) {
        wc_get_template( 'myaccount/saved-carts.php', array(
            'carts' => get_user_meta( get_current_user_id(), 'wcb2b_cart' )
        ), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
    }

    /**
     * Add order again button in myaccount order list for completed orders
     *
     * @param array $actions Actions list
     * @param object $order Current order instance
     * @return array
     */
    public function order_again_button( $actions, $order ) {
        if ( $order->has_status( apply_filters( 'wcb2b_order_again_statuses', array( 'completed' ) ) ) ) {
            $actions['order-again'] = array(
                'url' => wp_nonce_url( add_query_arg( 'order_again', $order->get_id(), wc_get_cart_url() ), 'woocommerce-order_again' ),
                'name' => __( 'Order again', 'woocommerce-b2b' ),
            );
        }
        return $actions;
    }

    /**
     * Prevent address edit in checkout
     * 
     * @param  array $fields Billing fields
     * @return array
     */
    public function disable_billing_address_edit( $fields ) {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            $blocked_billing_address = get_post_meta( $customer_group_id, 'wcb2b_group_blocked_billing_address', true );
            if ( $blocked_billing_address ) {
                foreach ( $fields as $key => $field ) {
                    if ( true == $field['required'] && empty( get_user_meta( get_current_user_id(), $key, true ) ) ) {
                        continue;
                    }
                    if ( ! isset( $fields[$key]['class'] ) || ! is_array( $fields[$key]['class'] ) ) {
                        $fields[$key]['class'] = array();
                    }
                    array_push( $fields[$key]['class'], 'wcb2b-readonly' );
                    $fields[$key]['custom_attributes'] = array(
                        'readonly' => 'readonly'
                    );
                }
            }
        }
        return $fields;
    }

    /**
     * Display a message in address edit page to notice readonly fields
     */
    public function disable_billing_address_edit_notice() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            $blocked_billing_address = get_post_meta( $customer_group_id, 'wcb2b_group_blocked_billing_address', true );
            if ( $blocked_billing_address ) {
                printf( '<div class="woocommerce-info">%s</div>', __( 'You are not allowed to change billing address, you can only enter missing data.', 'woocommerce-b2b' ) );
            }
        }
    }

}

return new WCB2B_Frontend_Myaccount();