<?php

/**
 * WooCommerce B2B Shortcodes Class
 *
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Shortcodes Class
 */
class WCB2B_Shortcodes {

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
        add_shortcode( 'wcb2bgrouptermsconditions', array( $this, 'terms_conditions_shortcode' ) );
        add_shortcode( 'wcb2bquickorder', array( $this, 'quick_order_shortcode' ) );
        add_shortcode( 'wcb2blivesearch', array( $this, 'live_search_shortcode' ) );
        add_shortcode( 'wcb2bhiddenprices', array( $this, 'hidden_prices_shortcode' ) );
        add_shortcode( 'wcb2brestrictedcontent', array( $this, 'restricted_content_shortcode' ) );
        add_shortcode( 'wcb2bloginform', array( $this, 'login_forms' ) );
        add_shortcode( 'wcb2blatestpurchases', array( $this, 'latest_purchases' ) );
        add_shortcode( 'wcb2bpurchasesrelated', array( $this, 'related_purchases' ) );
        // Deprecated since 3.1.5, retrocompatibility only
        add_shortcode( 'wcb2b_login_form', array( $this, 'login_forms_deprecated' ) );
    }

    /**
     * Create shorcode for Terms and Conditions
     *
     * @return string
     */
    public function terms_conditions_shortcode() {
        if ( is_user_logged_in() ) {
            $group_id = get_the_author_meta( 'wcb2b_group', get_current_user_id() );
            return wpautop( get_post_meta( $group_id, 'wcb2b_group_terms_conditions', true ) );
        }
        return false;
    }

    /**
     * Create shortcode for Quick order form
     *
     * @return string
     */
    public function quick_order_shortcode() {
        if ( is_admin() ) { return; }

        ob_start();
        wc_get_template( 'order/quick-order-form.php', array(
            'wcb2b_quick_order_processed' => WC()->cart && ! WC()->cart->is_empty() && defined( 'WCB2B_QUICK_ORDER_PROCESSED' ) && WCB2B_QUICK_ORDER_PROCESSED
        ), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
        $content = ob_get_contents();
        ob_clean();
        return $content;
    }

    /**
     * Create shortcode for Live Search form
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function live_search_shortcode( $atts ) {
        if ( is_admin() ) { return; }

        wp_enqueue_style( 'wcb2b_livesearch' );
        wp_enqueue_script( 'wcb2b_livesearch' );

        $atts = shortcode_atts( array(
            'fixed' => false
        ), $atts, 'wcb2blivesearch' );

        ob_start();
        wc_get_template( 'global/live-search-form.php', $atts, WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
        $content = ob_get_contents();
        ob_clean();
        return $content;
    }

    /**
     * Create shortcode for hidden prices message
     *
     * @return string
     */
    public function hidden_prices_shortcode() {
        if ( is_user_logged_in() || is_admin() ) { return; }

        ob_start();
        wcb2b_login_message();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * Create shortcode to make contents visible only to certain groups
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public function restricted_content_shortcode( $atts, $content ) {
        if ( is_admin() ) { return; }

        $atts = shortcode_atts( array(
            'allowed' => false
        ), $atts, 'wcb2brestrictedcontent' );

        $allowed_groups = explode( ',', $atts['allowed'] );
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( in_array( $customer_group_id, $allowed_groups ) ) {
                return $content;
            }
        }
        return '';
    }

    /**
     * Show custom registration forms dedicated to B2B
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function login_forms( $atts ) {
        $atts = shortcode_atts( array(
            'wcb2b_group' => false
        ), $atts, 'wcb2bloginform' );
    
        if ( false !== $atts['wcb2b_group'] ) {
            $group_id = $atts['wcb2b_group'];
        
            add_action( 'woocommerce_register_form_start', function() use ( $group_id ) {
                woocommerce_form_field(
                    'wcb2b_group',
                    array(
                        'type'              => 'hidden',
                        'required'          => true,
                        'custom_attributes' => array(
                            'readonly' => true
                        )
                    ),
                    $group_id
                );
            } );
            ob_start();
            wc_get_template( apply_filters( 'wcb2b_login_form_template', 'myaccount/form-login.php', $group_id ), array(
                'wcb2b_group' => $group_id
            ) );
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
    }

    /**
     * Show custom registration forms dedicated to B2B
     *
     * @param array $atts Shortcode attributes
     * @return string
     * @deprecated
     */
    public function login_forms_deprecated( $atts ) {
        $atts = shortcode_atts( array(
            'wcb2b_group' => false
        ), $atts, 'wcb2b_login_form' );
    
        if ( false !== $atts['wcb2b_group'] ) {
            $group_id = $atts['wcb2b_group'];
        
            add_action( 'woocommerce_register_form_start', function() use ( $group_id ) {
                woocommerce_form_field(
                    'wcb2b_group',
                    array(
                        'type'              => 'hidden',
                        'required'          => true,
                        'custom_attributes' => array(
                            'readonly' => true
                        )
                    ),
                    $group_id
                );
            } );
            ob_start();
            wc_get_template( apply_filters( 'wcb2b_login_form_template', 'myaccount/form-login.php', $group_id ), array(
                'wcb2b_group' => $group_id
            ) );
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
    }

    /**
     * Show customer latest purchases
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function latest_purchases( $atts ) {
        if ( is_user_logged_in() ) {
            $atts = shortcode_atts( array(
                'limit'     => 10,
                'days'      => 30
            ), $atts, 'wcb2blatestpurchases' );

            $orders = new WC_Order_Query( array(
                'limit'       => $atts['limit'],
                'customer_id' => get_current_user_id(),
                'type'        => wc_get_order_types(),
                'date_paid'   => '> ' . date( 'Y-m-d', strtotime( '-' . $atts['days'] . ' days' ) ),
                'status'      => array_keys( wc_get_is_paid_statuses() )
            ) );
            $products = array();
            foreach ( $orders->get_orders() as $order ) {
                foreach ( $order->get_items() as $item ) {
                    $products[] = $item->get_product_id();
                }
            }
            return do_shortcode( '[products ids="' . implode( ',', array_unique( $products ) ) . '"]' );
        }
        return false;
    }

    /**
     * Show products related to customer purchases
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function related_purchases( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 3, // How many products to show
            'last_orders' => 10 // How many orders consider to get related
        ), $atts, 'wcb2bpurchasesrelated' );

        if ( is_user_logged_in() ) {
            $orders = wc_get_orders( [
                'customer_id'   => get_current_user_id(),
                'return'        => 'ids',
                'status'        => array( 'wc-completed' ),
                'limit'         => $atts['last_orders'],
            ] );
            if ( $orders ) {
                $related = array();
                foreach ( $orders as $order_id ) {
                    $order = wc_get_order( $order_id );
                    $items = $order->get_items();
                    foreach ( $items as $item ) {
                        $related = array_unique( array_merge( wc_get_related_products( $item->get_product_id() ), $related ) );
                    }
                }
                shuffle( $related );
                $related = array_slice( $related, 0, $atts['limit'] );

                return do_shortcode( '[products ids=' . implode( ',', $related ) . ']' );
            }
        }
        return false;
    }

}

return new WCB2B_Shortcodes();