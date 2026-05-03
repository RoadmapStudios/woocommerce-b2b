<?php

/**
 * WooCommerce B2B Install Class
 *
 * @version 4.0.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Install Class
 */
class WCB2B_Install {

    /**
     * DB updates and callbacks that need to be run per version
     *
     * @var array
     */
    private static $db_updates = array(
        '3.0.0' => array(
            'wcb2b_vatnumber_required_300',
            'wcb2b_fix_prices_300'
        ),
        '3.0.1' => array(
            'wcb2b_set_page_visibility_301',
            'wcb2b_set_product_cat_visibility_301'
        ),
        '3.0.7' => array(
            'wcb2b_remove_deprecated_options_307'
        ),
        '3.1.0' => array(
            'wcb2b_set_packages_by_group_310',
            'wcb2b_set_minquantity_by_group_310',
            'wcb2b_set_maxquantity_by_group_310',
            'wcb2b_set_tax_display_by_group_310',
            'wcb2b_set_moderate_registration_by_group_310'
        ),
        '3.2.0' => array(
            'wcb2b_set_price_rule_by_group_320',
            'wcb2b_set_already_bought_by_group_320',
            'wcb2b_set_show_sales_by_group_320',
            'wcb2b_set_shippings_tab_by_group_320',
            'wcb2b_set_show_unpaid_by_group_320',
            'wcb2b_set_show_groupname_by_group_320',
            'wcb2b_set_show_discount_myaccount_by_group_320',
            'wcb2b_set_show_discount_product_by_group_320',
            'wcb2b_drop_deprecated_options_320'
        ),
        '3.2.1' => array(
            'wcb2b_set_minpurchase_alert_by_group_321',
            'wcb2b_set_minpurchase_button_by_group_321',
            'wcb2b_set_show_barcode_by_group_321',
            'wcb2b_set_show_rrp_by_group_321',
            'wcb2b_set_extend_registration_fields_by_group_321',
            'wcb2b_set_hide_prices_by_group_321',
            'wcb2b_set_add_vatnumber_by_group_321',
            'wcb2b_set_add_invoice_email_by_group_321',
            'wcb2b_set_suitable_roles_321'
        ),
        '3.3.5' => array(
            'wcb2b_set_vies_validation_by_group_335'
        ),
        '4.0.4' => array(
            'wcb2b_add_wcb2bloginform_meta_404'
        ),
    );

    /**
     * Init install class
     */
    public static function init() {
        // Process queue
        add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
        add_action( 'wcb2b_run_update_callback', array( __CLASS__, 'run_update_callback' ) );
    }

    /**
     * Check version and run the updater is required
     */
    public static function check_version() {
        $current_version = get_option( 'wcb2b_version', null );
        if ( is_null( $current_version ) ) {
            self::install();
        } else {
            if ( self::needs_db_update() ) {
                if ( isset( $_GET['do_update_wcb2b'] ) && ! empty( $_GET['do_update_wcb2b'] ) ) {
                    if ( check_admin_referer( 'wcb2b_db_update', 'wcb2b_db_update_nonce' ) ) {
                        set_transient( 'wcb2b_update_scheduled', 'yes', MINUTE_IN_SECONDS * 10 );
                        self::update();
                        wp_redirect( admin_url( 'tools.php?page=action-scheduler' ) );
                        exit;
                    }
                } else {
                    if ( false === get_transient( 'wcb2b_update_scheduled' ) ) {
                        add_action( 'admin_notices', array( get_called_class(), 'update_notice' ) );
                    } else {
                        $next_scheduled_date = WC()->queue()->get_next( 'wcb2b_run_update_callback', null, 'wcb2b-db-updates' );
                        if ( $next_scheduled_date ) {
                            add_action( 'admin_notices', array( get_called_class(), 'updating_notice' ) );
                        } else {
                            self::update_version();
                            delete_transient( 'wcb2b_update_scheduled' );
                            add_action( 'admin_notices', array( get_called_class(), 'updated_notice' ) );
                        }
                    }
                }
            } else {
                if ( version_compare( get_option( 'wcb2b_version', 0 ), WCB2B()->version, '<' ) ) {
                    self::update_version();
                }
            }
        }
    }

    /**
     * Install WCB2B
     */
    public static function install() {
        if ( 'yes' === get_transient( 'wcb2b_installing' ) ) {
            return;
        }

        set_transient( 'wcb2b_installing', 'yes', MINUTE_IN_SECONDS * 10 );

        self::set_suitable_roles();
        self::create_guest_group();
        self::create_quick_order_page();
        self::update_version();
        
        flush_rewrite_rules();

        delete_transient( 'wcb2b_installing' );
    }

    /**
     * Update WCB2B
     */
    public static function update() {
        if ( 'yes' === get_transient( 'wcb2b_updating' ) ) {
            return;
        }

        set_transient( 'wcb2b_updating', 'yes', MINUTE_IN_SECONDS * 10 );

        self::schedule_update_callbacks();

        delete_transient( 'wcb2b_updating' );
    }

    /**
     * Is a DB update needed?
     */
    private static function needs_db_update() {
        $current_version = get_option( 'wcb2b_version', null );
        $updates         = self::$db_updates;
        $update_versions = array_keys( $updates );
        usort( $update_versions, 'version_compare' );
        return ! is_null( $current_version ) && version_compare( $current_version, end( $update_versions ), '<' );
    }

    /**
     * See if we need to show or run database updates during install
     */
    private static function schedule_update_callbacks() {
        $current_version = get_option( 'wcb2b_version' );
        $loop            = 0;
        foreach ( self::$db_updates as $version => $update_callbacks ) {
            if ( version_compare( $current_version, $version, '<' ) ) {
                foreach ( $update_callbacks as $update_callback ) {
                    // Add to scheduled queue
                    WC()->queue()->schedule_single(
                        time() + $loop,
                        'wcb2b_run_update_callback',
                        array(
                            'update_callback' => $update_callback,
                        ),
                        'wcb2b-db-updates'
                    );
                    $loop++;
                }
            }
        }
    }

    /**
     * Triggered when a callback will run
     *
     * @param string $callback Callback name
     */
    public static function run_update_callback( $update_callback ) {
        include_once WCB2B_ABSPATH . 'includes/wcb2b-update-functions.php';

        if ( is_callable( $update_callback ) ) {
            // Logger
            wc_get_logger()->debug( sprintf( '::%s:: callback started', $update_callback ), array(
                'source' => 'wcb2b'
            ) );

            $result = (bool) call_user_func( $update_callback );
            self::run_update_callback_end( $update_callback, $result );

            // Logger
            wc_get_logger()->debug( sprintf( '::%s:: callback done [%s]', $update_callback, $result ? 'OK' : 'KO' ), array(
                'source' => 'wcb2b'
            ) );
        }
    }

    /**
     * Triggered when a callback has ran
     *
     * @param string $callback Callback name
     * @param bool   $result Return value from callback. False need to run again
     */
    protected static function run_update_callback_end( $callback, $result ) {
        if ( true === $result ) {
            return;
        }
        
        // If callback return false, needs to run again
        WC()->queue()->add(
            'wcb2b_run_update_callback',
            array(
                'update_callback' => $callback,
            ),
            'wcb2b-db-updates'
        );
    }

    /**
     * Set roles suitable as customer
     *
     * @return boolean
     */
    private static function set_suitable_roles() {
        if ( false === get_option( 'wcb2b_has_role_customer', false ) ) {
            update_option( 'wcb2b_has_role_customer', apply_filters( 'wcb2b_has_role_customer', array( 'customer' ) ) );
        }
        return true;
    }

    /**
     * Create roles and capabilities
     *
     * @return boolean
     */
    private static function create_guest_group() {
        if ( get_option( 'wcb2b_guest_group', false ) ) { return true; }

        // Insert default guest group
        $guest_group = wp_insert_post( array(
            'post_title'  => esc_html__( 'Guest', 'woocommerce-b2b' ),
            'post_type'   => 'wcb2b_group',
            'post_status' => 'publish'
        ) );

        // Get all customers without group
        $query = new WP_User_Query( array(
            'role__in'      => get_option( 'wcb2b_has_role_customer', array( 'customer' ) ),
            'meta_query'    => array(
                array(
                    'key'     => 'wcb2b_group',
                    'compare' => 'NOT EXISTS',
                )
            ),
            'fields' => 'ID'
        ) );
        $customers = $query->get_results();

        // Set default group to all customers without group
        if ( ! empty( $customers ) && ! empty( $guest_group ) ) {
            foreach ( $customers as $customer ) {
                update_user_meta( $customer, 'wcb2b_group', $guest_group ); 
            }
        }

        return update_option( 'wcb2b_guest_group', $guest_group ) 
            && update_option( 'wcb2b_default_group', $guest_group );
    }

    /**
     * Create quick order page
     *
     * @return boolean
     */
    private static function create_quick_order_page() {
        if ( get_option( 'wcb2b_quick_order_page', false ) ) { return true; }

        // Insert quick order page
        $quick_order_page = wp_insert_post( array(
            'post_title'   => esc_html__( 'Quick order', 'woocommerce-b2b' ),
            'post_type'    => 'page',
            'post_status'  => 'draft',
            'post_content' => '[wcb2bquickorder]'
        ) );

        return update_option( 'wcb2b_quick_order_page', $quick_order_page );
    }

    /**
     * Update WCB2B version to current
     */
    private static function update_version() {
        $prev_version = get_option( 'wcb2b_version', 0 );
        $next_version = WCB2B()->version;
        if ( $prev_version !== $next_version ) {
            // Logger
            wc_get_logger()->debug( sprintf( '%s -> %s', $prev_version, $next_version), array(
                'source' => 'wcb2b'
            ) );

            update_option( 'wcb2b_version', $next_version );
        }
    }

    /**
     * Display update needs notice
     */
    public static function update_notice() {
        include_once WCB2B_ABSPATH . 'includes/views/notices/html-admin-notice-update.php';
    }

    /**
     * Display update processing notice
     */
    public static function updating_notice() {
        include_once WCB2B_ABSPATH . 'includes/views/notices/html-admin-notice-updating.php';
    }

    /**
     * Display update complete needs notice
     */
    public static function updated_notice() {
        include_once WCB2B_ABSPATH . 'includes/views/notices/html-admin-notice-updated.php';
    }

}