<?php

/**
 * WooCommerce B2B Tools
 *
 * @version 3.2.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Admin_Tools
 */
class WCB2B_Admin_Tools {

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
        add_filter( 'export_filters', array( $this, 'export_customers_option' ) );
        add_filter( 'export_wp', array( $this, 'export_customers' ) );
    }

    /**
     * Add customer exporter to WordPress tools
     */
    public function export_customers_option() {
        ?>
        <fieldset>
            <p>
                <label>
                    <input type="radio" name="content" value="customers"><?php esc_html_e( 'Customers', 'woocommerce-b2b' ); ?>
                </label>
            </p>
            <p class="description"><?php esc_html_e( 'In CSV format for statistics purposes', 'woocommerce-b2b' ); ?></p>
        </fieldset>
        <?php
    }

    /**
     * Export customers
     * 
     * @param array $args Export tools arguments
     */
    public function export_customers( $args ) {
        if ( 'customers' == $args['content'] ) {
            $status = WCB2B_Configuration::get_customer_statuses();
            // Requested fields
            $fields = array(
                'ID',
                'user_login',
                'user_email',
                'user_registered',
                'first_name',
                'last_name',
                'billing_first_name',
                'billing_last_name',
                'billing_company',
                'billing_vat',
                'billing_invoice_email',
                'billing_address_1',
                'billing_address_2',
                'billing_city',
                'billing_postcode',
                'billing_country',
                'billing_state',
                'billing_phone',
                'billing_email',
                'shipping_first_name',
                'shipping_last_name',
                'shipping_company',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_city',
                'shipping_postcode',
                'shipping_country',
                'shipping_state',
                'wcb2b_group',
                'wcb2b_status'
            );

            // Get users by role (customer)
            $customers = get_users( array(
                'role__in'  => get_option( 'wcb2b_has_role_customer', array( 'customer' ) ),
                'fields'    => array(
                    'ID',
                    'user_login',
                    'user_email',
                    'user_registered'
                )
            ) );

            // If any customer is retrieved, skip
            if ( ! $customers ) {
                $referer = add_query_arg( 'error', 'empty', wp_get_referer() );
                wp_redirect( $referer );
                exit;
            }

            // Prepare headers to download CSV file
            header( 'Content-Description: File Transfer' );
            header( 'Content-Disposition: attachment; filename=' . sanitize_key( get_bloginfo( 'name' ) ) . '-customers-' . date( 'YmdHis' ) . '.csv' );
            header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );

            echo strtoupper( str_replace( '_', ' ', implode( ';', $fields ) ) ) . "\n";
            foreach ( $customers as $customer ) {
                $data = array_flip( $fields );
                $data = array_fill_keys( $fields, null );
                $data = array_merge( $data, (array)$customer, wp_list_pluck( get_user_meta( $customer->ID ), 0 ) );
                $data = array_intersect_key( $data, array_flip( $fields ) );
                
                $data['wcb2b_group'] = $data['wcb2b_group'] ? get_post( $data['wcb2b_group'] )->post_title : '';
                $data['wcb2b_status'] = $status[(int)$data['wcb2b_status']];
                $data['user_registered'] = date_i18n( get_option( 'date_format' ), strtotime( $data['user_registered'] ) );

                echo implode( ';', $data ) . "\n";
            }
            exit;
        }
    }

}

return new WCB2B_Admin_Tools();