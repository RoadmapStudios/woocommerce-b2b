<?php

/**
 * WooCommerce B2B Admin Users set-up Class
 *
 * @version 4.0.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Admin_Users Class
 */
class WCB2B_Admin_Users {

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
        add_action( 'show_user_profile', array( $this, 'user_profile' ) );
        add_action( 'edit_user_profile', array( $this, 'user_profile' ) );
        add_action( 'personal_options_update', array( $this, 'user_profile_update' ) );
        add_action( 'edit_user_profile_update', array( $this, 'user_profile_update' ) );
        add_filter( 'manage_users_columns', array( $this, 'render_columns' ) );
        add_filter( 'manage_users_custom_column', array( $this, 'render_columns_value' ), 11, 3 );
        add_filter( 'manage_users_sortable_columns', array( $this, 'make_columns_sortable' ) );
        add_filter( 'bulk_actions-users', array( $this, 'bulk_actions' ) );
        add_filter( 'handle_bulk_actions-users', array( $this, 'bulk_actions_handle' ), 10, 3 );
        add_filter( 'pre_get_users', array( $this, 'query_filters' ) );
        add_action( 'manage_users_extra_tablenav', array( $this, 'render_filters' ) );
        add_action( 'woocommerce_coupon_options', array( $this, 'coupon_options' ) );
        add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'coupon_restriction' ), 10, 2 );
        add_action( 'woocommerce_coupon_options_save', array( $this, 'coupon_options_save' ), 10, 2 );
        add_filter( 'woocommerce_customer_meta_fields', array( $this, 'invoice_email_add_to_customer' ) );
        add_filter( 'woocommerce_customer_meta_fields', array( $this, 'vatnumber_add_to_customer' ) );
    }

    /**
     * Add group field to user edit page
     * 
     * @param WP_User $user The current WP_User object
     */
    public function user_profile( $user ) {
        include_once WCB2B_ABSPATH . 'includes/views/html-admin-user.php';
    }

    /**
     * Update group field in user edit page
     * 
     * @param int $user_id The user ID
     */
    public function user_profile_update( $user_id ) {
        if ( wcb2b_has_role( $user_id, 'customer' ) ) {
            update_user_meta( $user_id, 'wcb2b_unpaid_limit', intval( $_POST['wcb2b_unpaid_limit'] ) );
            update_user_meta( $user_id, 'wcb2b_group', intval( $_POST['wcb2b_group'] ) );
            update_user_meta( $user_id, 'wcb2b_vies_validated_user', intval( ( $_POST['wcb2b_vies_validated_user'] ?? 0 ) ) );

            $current_status = (int)get_the_author_meta( 'wcb2b_status', $user_id );
            update_user_meta( $user_id, 'wcb2b_status', intval( $_POST['wcb2b_status'] ) );

            // If status is enabled, send approval confirmation to customer
            if ( intval( $_POST['wcb2b_status'] ) && apply_filters( 'wcb2b_send_activation_notification', true ) ) {
                if ( $current_status == 0 ) {
                    $emails = WC_Emails::instance()->emails;
                    if ( ! array_key_exists( 'WCB2B_Email_Customer_Status_Notification', $emails ) ) {
                        $emails['WCB2B_Email_Customer_Status_Notification'] = include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-email-customer-status-notification.php';
                    }
                    do_action( 'wcb2b_customer_status_notification', $user_id );
                }
            }
            // If status is disabled, logout from everywhere
            if ( 0 == intval( $_POST['wcb2b_status'] ) ) {
                $sessions = WP_Session_Tokens::get_instance( $user_id );
                $sessions->destroy_all();
            }

            // To fix price caches
            wc_delete_product_transients();
        }
    }

    /**
     * Add new customers columns in users list
     * 
     * @param  array $columns Array of column name => label
     * @return array
     */
    public function render_columns( $columns ) {
        if ( ! isset( $_GET['role'] ) || ( isset( $_GET['role'] ) && in_array( $_GET['role'], get_option( 'wcb2b_has_role_customer', array( 'customer' ) ) ) ) ) {
            $columns['wcb2b_group'] = esc_html__( 'Group', 'woocommerce-b2b' );
            $columns['wcb2b_status'] = esc_html__( 'Status', 'woocommerce-b2b' );
            $columns['wcb2b_total_spent'] = esc_html__( 'Total spent', 'woocommerce-b2b' );
        }
        return $columns;
    }

    /**
     * Retrieve customers column value in users list
     *
     * @param string $value Custom column output
     * @param string $column_name Column name
     * @param int $user_id ID of the currently-listed user
     * @return string
     */
    public function render_columns_value( $value, $column_name, $user_id ) {
        if ( wcb2b_has_role( $user_id, 'customer' ) ) {
            if ( $column_name === 'wcb2b_group' ) {
                if ( $group_id = get_the_author_meta( 'wcb2b_group', $user_id ) ) {
                    $group = get_post( $group_id );
                    if ( ! is_null( $group ) && false !== get_post_status( $group ) ) {
                        if ( $group->post_type == 'wcb2b_group' ) {
                            return get_the_title( $group->ID );
                        }
                    }
                }
            }

            if ( $column_name === 'wcb2b_status' ) {
                $group_id = get_option( 'wcb2b_guest_group' );
                if ( $user_group_id = get_the_author_meta( 'wcb2b_group', $user_id ) ) {
                    $group_id = $user_group_id;
                }
                if ( get_post_meta( $group_id, 'wcb2b_group_moderate_registration', true ) ) {
                    return get_the_author_meta( 'wcb2b_status', $user_id ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no-alt"></span>';
                }
                return '<span class="dashicons dashicons-minus"></span>';
            }

            if ( $column_name === 'wcb2b_total_spent' ) {
                return wc_price( wc_get_customer_total_spent( $user_id ) );
            }
        }
        return $value;
    }

    /**
     * Make group column sortable in users list
     * 
     * @param  array $columns Array of column name => label
     * @return array
     */
    public function make_columns_sortable( $columns ) {
        $columns['wcb2b_group'] = 'wcb2b_group';
        $columns['wcb2b_status'] = 'wcb2b_status';
        $columns['wcb2b_total_spent'] = 'wcb2b_total_spent';

        return $columns;
    }

    /**
     * Bulk assignment to group
     * 
     * @param  array $actions Array of the available bulk actions
     * @return array
     */
    public function bulk_actions( $actions ) {
        $actions['wcb2b-assign_group-action'] = esc_html__( 'Assign group', 'woocommerce-b2b' );
        $actions['wcb2b-change_status-action'] = esc_html__( 'Change status', 'woocommerce-b2b' );

        return $actions;
    }

    /**
     * Process new group assignment and return updated users
     * 
     * @param string $redirect_url The redirect URL
     * @param string $action The action being taken
     * @param array $items The items to take the action on
     * @return string
     */
    public function bulk_actions_handle( $redirect_url, $action, $items ) {
        if ( $action == 'wcb2b-assign_group-action' && isset( $_REQUEST['wcb2b_group'] ) ) {
            $group_id = (int)$_REQUEST['wcb2b_group'];

            foreach ( $items as $user_id ) {
                if ( wcb2b_has_role( $user_id, 'customer' ) ) {
                    update_user_meta( $user_id, 'wcb2b_group', intval( $group_id ) );
                }
            }

            // To fix price caches
            wc_delete_product_transients();
        }

        if ( $action == 'wcb2b-change_status-action' && isset( $_REQUEST['wcb2b_status'] ) ) {
            $status = $_REQUEST['wcb2b_status'];

            // Validate request
            if ( ! is_numeric( $status ) ) { return $redirect_url; }

            foreach ( $items as $user_id ) {
                // Skip if user hasn't a customer role
                if ( wcb2b_has_role( $user_id, 'customer' ) ) {
                    $current_status = get_the_author_meta( 'wcb2b_status', $user_id );

                    // Update status
                    update_user_meta( $user_id, 'wcb2b_status', intval( $status ) );

                    // If status is enabled, send approval confirmation to customer
                    if ( intval( $status ) && apply_filters( 'wcb2b_send_activation_notification', true ) ) {
                        if ( $current_status == 0 ) {
                            $emails = WC_Emails::instance()->emails;
                            if ( ! array_key_exists( 'WCB2B_Email_Customer_Status_Notification', $emails ) ) {
                                $emails['WCB2B_Email_Customer_Status_Notification'] = include_once WCB2B_ABSPATH . 'includes/classes/emails/class-wcb2b-email-customer-status-notification.php';
                            }
                            do_action( 'wcb2b_customer_status_notification', $user_id );
                        }
                    }
                    // If status is disabled, logout from everywhere
                    if ( 0 == intval( $status ) ) {
                        $sessions = WP_Session_Tokens::get_instance( $user_id );
                        $sessions->destroy_all();
                    }
                }
            }
        }

        return $redirect_url;
    }

    /**
     * Add parameters to filter users
     *
     * @param WP_Query $query The WP_Query instance (passed by reference)
     */
    public function query_filters( $query ) {
        global $pagenow;

        if ( ( empty( $query->get( 'role' ) ) || in_array( $query->get( 'role' ), get_option( 'wcb2b_has_role_customer', array( 'customer' ) ) ) ) && 'users.php' == $pagenow ) {
            $meta_query = array();
            if ( isset( $_GET['wcb2b_group_search'] ) && $_GET['wcb2b_group_search'] !== '' ) {
                $meta_query[] = array(
                    'key'   => 'wcb2b_group',
                    'value' => (int)$_GET['wcb2b_group_search']
                );
            }

            if ( isset( $_GET['wcb2b_status_search'] ) && $_GET['wcb2b_status_search'] !== '' ) {
                if ( $_GET['wcb2b_status_search'] == 0 ) {
                    $meta_query[] = array( array(
                        'relation' => 'OR',
                        array(
                            'key'   => 'wcb2b_status',
                            'value' => '',
                            'compare' => 'NOT EXISTS'
                        ),
                        array(
                            'key'   => 'wcb2b_status',
                            'value' => $_GET['wcb2b_status_search']
                        ) ) );
                } else {
                    $meta_query[] = array(
                        'key'   => 'wcb2b_status',
                        'value' => $_GET['wcb2b_status_search']
                    );
                }
            }

            if ( 'wcb2b_total_spent' == $query->get( 'orderby' ) ) {
                $meta_query[] = array(
                    'money_spent' => array(
                        'key' => '_money_spent',
                        'type' => 'NUMERIC'
                    )
                );
                $query->set( 'orderby', 'money_spent' );
            }

            if ( ! empty( $meta_query ) ) {
                $query->set( 'meta_query', $meta_query );
            }
        }
    }

    /**
     * Add filters to users
     *
     * @param string $which The location of the extra table nav markup: 'top' or 'bottom
     */
    public function render_filters( $which ) {
        if ( $which != 'bottom' ) {
            $groups = wcb2b_get_groups();

            $filters = '<select name="wcb2b_group_search" id="wcb2b_group_search">';
            $filters .= '<option value="">' . esc_html__( 'Filter by group', 'woocommerce-b2b' ) . '</option>';

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

            $filters .= '<select name="wcb2b_status_search" id="wcb2b_status_search">';
            $filters .= '<option value="">' . esc_html__( 'Filter by status', 'woocommerce-b2b' ) . '</option>';

            $statuses = $status = WCB2B_Configuration::get_customer_statuses();

            foreach ( $statuses as $status => $status_label ) {
                $filters .= sprintf(
                    '<option value="%1$s" %2$s>%3$s</option>',
                    $status,
                    ( isset( $_GET['wcb2b_status_search'] ) && $_GET['wcb2b_status_search'] != '' && intval( $_GET['wcb2b_status_search'] ) === $status ? ' selected="selected"' : '' ),
                    $status_label
                );
            }
            $filters .= '</select>';

            echo '<div class="alignleft actions">' . $filters . get_submit_button( esc_html__( 'Filter', 'woocommerce-b2b' ), '', 'filter', false ) . '</div>';
        }
    }

    /**
     * Add option to display coupon in cart
     */
    public function coupon_options() { 
        woocommerce_wp_checkbox( array(
            'id' => 'wcb2b_show_coupon',
            'label' => __( 'Show in cart', 'woocommerce' ),
            'description' => sprintf( __( 'When checked, this coupon will be visible on cart page for customers in groups in which this feature is enabled.', 'woocommerce' ) )
        ) );
    }

    /**
     * Add coupons restriction by group
     *
     * @param integer $coupon_id Coupon ID
     * @param object $coupon Coupon object
     */
    public function coupon_restriction( $coupon_id, $coupon ) {
        include_once WCB2B_ABSPATH . 'includes/views/html-admin-coupons.php';
    }

    /**
     * Save coupons restriction by group
     *
     * @param integer $post_id Coupon ID
     * @param object $coupon Coupon object
     */
    public function coupon_options_save( $post_id, $coupon ) {
        $value = isset( $_POST['_wcb2b_coupon_group'] ) ? $_POST['_wcb2b_coupon_group'] : false;
        update_post_meta( $post_id, '_wcb2b_coupon_group', $value );

        $value = isset( $_POST['_wcb2b_coupon_total_spent'] ) ? $_POST['_wcb2b_coupon_total_spent'] : false;
        update_post_meta( $post_id, '_wcb2b_coupon_total_spent', $value );

        $value = isset( $_POST['wcb2b_show_coupon'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, 'wcb2b_show_coupon', $value );
    }

    /**
     * Add invoice email field to billing address (in admin: Customer profile)
     * 
     * @param array $fields Checkout billing address fields
     * @return array
     */
    public function invoice_email_add_to_customer( $fields ) {
        // Add field exactly after company field
        $fields['billing']['fields'] = array_slice( $fields['billing']['fields'], 0, 3, true )
        + array( 'billing_invoice_email' => array(
            'label'       => esc_html__( 'Email address for invoices', 'woocommerce-b2b' ),
            'description' => '',
        ) )
        + array_slice( $fields['billing']['fields'], 3, count( $fields['billing']['fields'] ) - 1, true );
        return $fields;
    }

    /**
     * Add VAT number field to billing address (in admin: Customer profile)
     * 
     * @param array $fields Checkout billing address fields
     * @return array
     */
    public function vatnumber_add_to_customer( $fields ) {
        // Add field exactly after company field
        $fields['billing']['fields'] = array_slice( $fields['billing']['fields'], 0, 3, true )
        + array( 'billing_vat' => array(
            'label'       => esc_html__( 'VAT number', 'woocommerce-b2b' ),
            'description' => '',
        ) )
        + array_slice( $fields['billing']['fields'], 3, count( $fields['billing']['fields'] ) - 1, true );
        return $fields;
    }

}

return new WCB2B_Admin_Users();