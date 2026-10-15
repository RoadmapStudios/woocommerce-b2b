<?php

/**
 * WooCommerce B2B setup
 * 
 * @version 4.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main WooCommerce B2B Class
 *
 * @class WooCommerceB2B
 */
final class WooCommerceB2B {

    /**
     * Plugin name
     *
     * @var string
     */
    public $plugin_name = 'WooCommerce B2B';

    /**
     * WooCommerce B2B version
     *
     * @var string
     */
    public $version = '4.1.0';

    /**
     * WooCommerce B2B Envato ID
     *
     * @var string
     */
    public $envato_id = '21565847';

    /**
     * Useful external links
     *
     * @var array
     */
    protected $links = array(
        'support'   => 'https://support.woocommerce-b2b.com/',
        'docs'      => 'https://woocommerce-b2b.com/documentation/',
        'blog'      => 'https://woocommerce-b2b.com/blog/',
        'changelog' => 'https://woocommerce-b2b.com/changelog/',
        'faq'       => 'https://woocommerce-b2b.com/frequently-asked-questions/'
    );

    /**
     * The single instance of the class
     *
     * @var WooCommerceB2B
     */
    protected static $_instance = null;

    /**
     * Main WooCommerce B2B Instance
     *
     * Ensures only one instance of WooCommerce B2B is loaded or can be loaded
     * 
     * @static
     * @return WooCommerceB2B - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * WooCommerce B2B Constructor
     */
    public function __construct() {
        if ( ! array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) && ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            add_action( 'admin_notices', array( $this, 'dependencies_notice' ) );
            add_action( 'admin_init', array( $this, 'dependencies_disable' ) );
            return false;
        }
        $this->define_constants();
        $this->init_hooks();
        $this->includes();
    }

    /**
     * Define WooCommerce B2B Constants
     */
    private function define_constants() {
        $this->define( 'WCB2B_ABSPATH', dirname( WCB2B_PLUGIN_FILE ) . '/' );
        $this->define( 'WCB2B_VERSION', $this->version );
        $this->define( 'WCB2B_ENVATO_ID', $this->envato_id );
        $this->define( 'WCB2B_OVERRIDES', 'woocommerce-b2b' );
    }

    /**
     * Include required core files used in admin and on the frontend
     */
    public function includes() {
        /* Functions */
        include_once WCB2B_ABSPATH . 'includes/wcb2b-functions.php';
        /* Debug */
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-debug.php';
        /* Upgrades */
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-upgrade.php';
        /* Common */
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-configuration.php';
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-install.php';
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-shortcodes.php';
        include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-post_types.php';

        if ( $this->is_request( 'admin' ) ) {
            include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-admin.php';
        }
        if ( $this->is_request( 'frontend' ) ) {
            include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-frontend.php';
        }
        if ( $this->is_rest_api_request() ) {
            include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-rest_api.php';
        }

        add_action( 'plugins_loaded', function() {
            include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-hooks.php';
            include_once WCB2B_ABSPATH . 'includes/classes/class-wcb2b-compatibility.php';
        } );
    }

    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        register_activation_hook( WCB2B_PLUGIN_FILE, array( $this, 'activation' ) );
        register_deactivation_hook( WCB2B_PLUGIN_FILE, array( $this, 'deactivation' ) );

        add_filter( 'woocommerce_get_settings_pages', array( $this, 'settings' ) );
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
        add_filter( 'plugin_action_links_' . plugin_basename( WCB2B_PLUGIN_FILE ), array( $this, 'settings_link' ) );
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -1 );
        add_action( 'after_setup_theme', array( $this, 'pluggable' ) );
        add_action( 'init', array( $this, 'init' ), 0 );
        // Generators
        add_filter( 'get_the_generator_html', array( $this, 'add_generator_tag' ) );
        add_filter( 'get_the_generator_xhtml', array( $this, 'add_generator_tag' ) );
        // HPOS, blocks
        add_action( 'before_woocommerce_init', array( $this, 'wc_compatibility' ) );
        add_action( 'woocommerce_blocks_loaded', array( $this, 'wc_compatibility_blocks' ) );
    }

    /**
     * Add pluggable support to functions
     */
    public function pluggable() {
        include_once WCB2B_ABSPATH . 'includes/wcb2b-deprecated-functions.php';
        include_once WCB2B_ABSPATH . 'includes/wcb2b-pluggable-functions.php';
    }

    /**
     * Fired on plugin activation
     */
    public function activation() {
        if ( ! current_user_can( 'activate_plugins' ) ) { return; }

        $plugin = isset( $_REQUEST[ 'plugin' ] ) ? $_REQUEST[ 'plugin' ] : null;
        check_admin_referer( 'activate-plugin_' . $plugin );
    }

    /**
     * Fired on plugin deactivation
     */
    public function deactivation() {
        if ( ! current_user_can( 'activate_plugins' ) ) { return; }

        $plugin = isset( $_REQUEST[ 'plugin' ] ) ? $_REQUEST[ 'plugin' ] : null;
        check_admin_referer( 'deactivate-plugin_' . $plugin );
    }

    /**
     * Include the settings page classes
     *
     * @param array $settings Array of WooCommerce settings pages
     * @return array
     */
    public function settings( $settings ) {
        $settings[] = include WCB2B_ABSPATH . 'includes/classes/settings/class-wcb2b-settings.php';
        return $settings;
    }

    /**
     * Add link to configuration page into plugin
     *
     * @param array $links Array of plugin action links
     * @return array
     */
    public function settings_link( $links ) {
        return array_merge( array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=woocommerce-b2b' ) . '">' . esc_html__( 'Settings', 'woocommerce-b2b' ) . '</a>'
        ), $links );
    } 

    /**
     * Add useful links into plugin
     *
     * @param array $links Array of plugin action links
     * @return array
     */
    public function plugin_row_meta( $links, $file ) {
        if ( plugin_basename( WCB2B_PLUGIN_FILE ) === $file ) {
            $row_meta = array(
                'changelog'=> sprintf( '<a href="%s">%s</a>', $this->links['changelog'], esc_html__( 'Changelog', 'woocommerce-b2b' ) ),
                'docs'     => sprintf( '<a href="%s">%s</a>', $this->links['docs'], esc_html__( 'Docs', 'woocommerce-b2b' ) ),
                'faq'      => sprintf( '<a href="%s">%s</a>', $this->links['faq'], esc_html__( 'Faq', 'woocommerce-b2b' ) ),
                'blog'     => sprintf( '<a href="%s">%s</a>', $this->links['blog'], esc_html__( 'Guide & Snippets', 'woocommerce-b2b' ) ),
                'support'  => sprintf( '<a href="%s">%s</a>', $this->links['support'], esc_html__( 'Support', 'woocommerce-b2b' ) )
            );
            return array_merge( $links, $row_meta );
        }
        return (array)$links;
    }

    /**
     * When WP has loaded all plugins, trigger the `wcb2b_loaded` hook
     */
    public function on_plugins_loaded() {
        do_action( 'wcb2b_loaded' );
    }

    /**
     * Init WooCommerce B2B when WordPress Initialises
     */
    public function init() {
        // Set up localisation
        $this->load_plugin_textdomain();

        // Before init action
        do_action( 'wcb2b_before_init' );

        WCB2B_Install::init();

        // Init action
        do_action( 'wcb2b_init' );
    }

    /**
     * Generator tag
     *
     * @param string $tag Meta generator tag
     * @return string
     */
    public function add_generator_tag( $tag ) {
        if ( false === apply_filters( 'wcb2b_show_generator_tag', true ) ) {
            return $tag;
        }
        return "\n" . sprintf( '<meta name="generator" content="%s %s">',
            esc_attr( $this->plugin_name ),
            esc_attr( $this->version )
        );
    }

    /**
     * Declare compatibility with HPOS and blocks
     */
    public function wc_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WCB2B_PLUGIN_FILE, true );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WCB2B_PLUGIN_FILE, true );
        }
    }

    /**
     * Include custom blocks
     */
    public function wc_compatibility_blocks() {
        if ( ! class_exists( \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class ) ) {
            return;
        }
        require_once WCB2B_ABSPATH . '/src/Blocks/Payments/Integrations/WCB2B_Invoice.php';
        require_once WCB2B_ABSPATH . '/src/Blocks/Payments/Integrations/WCB2B_PurchaseOrder.php';
        require_once WCB2B_ABSPATH . '/src/Blocks/Payments/Integrations/WCB2B_Quotation.php';

        add_action( 'woocommerce_blocks_payment_method_type_registration', function( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register( new WCB2B_Invoice_Gateway_Blocks );
            $payment_method_registry->register( new WCB2B_PurchaseOrder_Gateway_Blocks );
            $payment_method_registry->register( new WCB2B_Quotation_Gateway_Blocks );
        } );
    }

    /**
     * Load Localisation files
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'woocommerce-b2b', false, plugin_basename( dirname( WCB2B_PLUGIN_FILE ) ) . '/i18n/' );
    }

    /**
     * Returns true if the request is a non-legacy REST API request
     * 
     * @return boolean
     */
    public function is_rest_api_request() {
        if ( empty( $_SERVER['REQUEST_URI'] ) ) {
            return false;
        }

        $rest_prefix         = trailingslashit( rest_get_url_prefix() );
        $is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) );

        return apply_filters( 'woocommerce_is_rest_api_request', $is_rest_api_request );
    }

    /**
     * What type of request is this?
     *
     * @param string $type Type of request: admin, ajax, cron or frontend
     * @return bool
     */
    private function is_request( $type ) {
        switch ( $type ) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined( 'DOING_AJAX' );
            case 'cron':
                return defined( 'DOING_CRON' );
            case 'frontend':
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! $this->is_rest_api_request();
        }
    }

    /**
     * Define constant if not already set
     *
     * @param string $name Constant name
     * @param string|bool $value Constant value
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * Return useful external links
     * 
     * @return array
     */
    public function get_links() {
        return $this->links;
    }

    /**
     * Add admin notice if WooCommerce is disabled
     */
    public function dependencies_notice() {
        printf( '<div class="error"><p>%s</p></div>',
            sprintf( esc_html__( '%s requires %s plugin to be installed and active, please check. %s has been disabled.', 'woocommerce-b2b' ),
                '<strong>' . $this->plugin_name . '</strong>',
                '<strong>WooCommerce</strong>',
                '<strong>' . $this->plugin_name . '</strong>'
            )
        );
    }

    /**
     * Disable plugin if WooCommerce is disabled
     */
    public function dependencies_disable() {
        deactivate_plugins( WCB2B_PLUGIN_FILE );
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
    
}

if ( ! function_exists( 'WCB2B' ) ) {

    /**
     * Returns the main instance of WCB2B
     *
     * @return WooCommerceB2B
     */
    function WCB2B() {
        return WooCommerceB2B::instance();
    }

}
