<?php

/**
 * WooCommerce B2B Products
 *
 * @version 4.0.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Frontend_Products
 */
class WCB2B_Frontend_Products {

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
        add_action( 'wp', array( $this, 'set_query_vars' ), 10 );
        add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'check_cart_max_quantity' ), 10, 3 );
        add_filter( 'woocommerce_quantity_input_args', array( $this, 'render_quantity_input' ), 10, 2 );
        add_filter( 'woocommerce_available_variation', array( $this, 'render_quantity_input_variation' ), 10, 3 );
        add_filter( 'woocommerce_loop_add_to_cart_args', array( $this, 'render_quantity_input_loop' ), 10, 2 );
        add_filter( 'woocommerce_after_add_to_cart_form', array( $this, 'display_quantity_notices' ) );
        add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'prices_hash' ) );
        add_action( apply_filters( 'wcb2b_simple_tier_prices_hook', 'woocommerce_before_add_to_cart_form' ), array( $this, 'display_tier_prices' ), 15 );
        add_filter( 'woocommerce_available_variation', array( $this, 'display_tier_prices_variation' ) );
        add_filter( 'pre_get_posts', array( $this, 'search_by_sku' ), 15 );
        add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'live_search_prepare' ), 10, 2 );
        add_action( 'wp_ajax_wcb2b_livesearch_results', array( $this, 'live_search_ajax_results' ) );
        add_action( 'wp_ajax_nopriv_wcb2b_livesearch_results', array( $this, 'live_search_ajax_results' ) );
        add_action( 'wp_ajax_wcb2b_livesearch_addtocart', array( $this, 'live_search_ajax_addtocart' ) );
        add_action( 'wp_ajax_nopriv_wcb2b_livesearch_addtocart', array( $this, 'live_search_ajax_addtocart' ) );
        add_filter( 'woocommerce_variation_is_visible', array( $this, 'always_show_product_variation' ), 10, 4);
        add_filter( 'woocommerce_get_stock_html', array( $this, 'hide_stock_by_group' ), 10, 2 );
        add_filter( 'pre_option_woocommerce_price_display_suffix', array( $this, 'price_suffix' ), 99, 3 );
        add_action( 'init', array( $this, 'hide_products_init' ) );
        add_action( 'woocommerce_product_query', array( $this, 'hide_products_in_query' ), 11 );
        add_filter( 'wp_get_nav_menu_items', array( $this, 'hide_in_menu' ), 10, 3 );
        add_filter( 'woocommerce_products_widget_query_args', array( $this, 'hide_in_widgets' ) );
        add_filter( 'woocommerce_recently_viewed_products_widget_query_args', array( $this, 'hide_in_widgets' ) );
        add_filter( 'get_terms_args', array( $this, 'hide_product_taxonomies' ), 10, 2 );
        add_action( 'template_redirect', array( $this, 'hidden_product_redirect' ) );
        add_filter( 'woocommerce_product_is_visible', array( $this, 'hide_products' ), 10, 2 );
        add_filter( 'woocommerce_variation_is_visible', array( $this, 'hide_variation' ), 15, 4 );
        add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'already_bought' ) );
        add_action( 'woocommerce_product_meta_end', array( $this, 'show_product_sales' ) );
        add_filter( 'woocommerce_product_tabs', array( $this, 'shippings_tab' ) );
        add_filter( 'woocommerce_product_tabs', array( $this, 'purchase_history_tab' ) );
        add_action( 'woocommerce_single_product_summary', array( $this, 'display_discount_message' ), 15 );
        add_filter( 'woocommerce_get_price_html', array( $this, 'display_simple_rrp' ), 99, 2 );
        add_filter( 'woocommerce_available_variation', array( $this, 'display_variation_rrp' ), 99, 3 );
        add_action( 'woocommerce_product_meta_start', array( $this, 'display_simple_barcode' ), 99 );
        add_filter( 'woocommerce_available_variation', array( $this, 'display_variation_barcode' ), 10, 3 );
        add_filter( 'woocommerce_is_purchasable', array( $this, 'catalog_mode' ) );
        add_action( 'woocommerce_init', array( $this, 'hide_prices_hooks' ) );
        add_action( apply_filters( 'wcb2b_estimated_delivery_hook', 'woocommerce_after_add_to_cart_button' ), array( $this, 'show_product_estimated_delivery' ) );
        add_action( 'woocommerce_product_meta_end', array( $this, 'show_product_shipping_class' ) );
        // Blocks
        add_filter( 'woocommerce_blocks_product_grid_item_html', array( $this, 'hide_products_in_block_grid' ), 10, 3 );
        add_filter( 'render_block_context', array( $this, 'hide_products_in_block_single_product' ), 10, 3 );
        // Group simulator
        add_action( 'after_setup_theme', array( $this, 'group_simulator_init' ) );
    }

    /**
     * Retrieve product type to fix min and pack settings 
     */
    public function set_query_vars() {
        if ( is_product() ) {
            set_query_var( 'wcb2b_product_type', WC_Product_Factory::get_product_type( get_the_ID() ) );
        }
    }

    /**
     * Check if product in cart has reached max quantity
     * 
     * @param boolean $valid Product can be added to cart
     * @param integer $product_id Current product ID
     * @param integer $quantity Quantity to add to cart
     * @return boolean
     */
    public function check_cart_max_quantity( $valid, $product_id, $quantity ) {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( ( $cart_quantity = WC()->cart->get_cart_item_quantities()[$product_id] ?? 0 ) > 0 ) {
                $product_group_max = get_post_meta( $product_id, 'wcb2b_product_group_max', true )[$customer_group_id] ?? 0;
                if ( $product_group_max && ( $quantity + $cart_quantity ) > $product_group_max ) {
                    wc_add_notice( sprintf( __( 'You can\'t have more than %s of this product in cart', 'woocommerce-b2b' ), $product_group_max ), 'error' );
                    return false;
                }
            }
        }
        return $valid;
    }

    /**
     * Add min and pack amounts to WooCommerce frontend
     * 
     * @param array $args Arguments list
     * @param object $product Current product instance
     * @return array
     */
    public function render_quantity_input( $args, $product ) {
        // Choose customer group, if not logged in default GUEST
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            // Product type
            $product_type = get_query_var( 'wcb2b_product_type' );

            // Packages
            $product_group_packages = get_post_meta( $product->get_ID(), 'wcb2b_product_group_packages', true );
            if ( ! empty( $product_group_packages ) ) {
                if ( is_array( $product_group_packages ) && isset( $product_group_packages[$customer_group_id] ) && ! empty( $product_group_packages[$customer_group_id] ) ) {
                    $args['step'] = intval( $product_group_packages[$customer_group_id] );
                }
            }
            // Minimum quantity
            $product_group_min = get_post_meta( $product->get_ID(), 'wcb2b_product_group_min', true );
            if ( ! empty( $product_group_min ) ) {
                if ( is_array( $product_group_min ) && isset( $product_group_min[$customer_group_id] ) && ! empty( $product_group_min[$customer_group_id] ) ) {
                    $args['min_value'] = $product_type == 'grouped' ? 0 : intval( $product_group_min[$customer_group_id] );
                    if ( ! is_cart() ) {
                        // If not is cart page, force input value to min by default
                        $args['input_value'] = $args['min_value'];
                    }
                }
            }
            // Maximum quantity
            $product_group_max = get_post_meta( $product->get_ID(), 'wcb2b_product_group_max', true );
            if ( ! empty( $product_group_max ) ) {
                if ( is_array( $product_group_max ) && isset( $product_group_max[$customer_group_id] ) && ! empty( $product_group_max[$customer_group_id] ) ) {
                    if ( -1 == $args['max_value'] || intval( $product_group_max[$customer_group_id] ) < $args['max_value'] ) {
                        $args['max_value'] = intval( $product_group_max[$customer_group_id] );
                    }
                }
            }
        }
        return $args;
    }

    /**
     * Add min and pack amounts to WooCommerce frontend [Variables]
     * 
     * @param array $args Arguments list
     * @param object $product Current product instance
     * @param object $variation Current variation instance
     * @return array
     */
    public function render_quantity_input_variation( $args, $product, $variation ) {
        // Choose customer group, if not logged in default GUEST
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            // Packages
            $product_group_packages = get_post_meta( $variation->get_ID(), 'wcb2b_product_group_packages', true );
            if ( ! empty( $product_group_packages ) ) {
                if ( is_array( $product_group_packages ) && isset( $product_group_packages[$customer_group_id] ) && ! empty( $product_group_packages[$customer_group_id] ) ) {
                    $args['step'] = intval( $product_group_packages[$customer_group_id] );
                }
            }
            // Minimum quantity
            $product_group_min = get_post_meta( $variation->get_ID(), 'wcb2b_product_group_min', true );
            if ( ! empty( $product_group_min ) ) {
                if ( is_array( $product_group_min ) && isset( $product_group_min[$customer_group_id] ) && ! empty( $product_group_min[$customer_group_id] ) ) {
                    $args['min_qty'] = $args['min_value'] = intval( $product_group_min[$customer_group_id] );
                }
            }
            // Maximum quantity
            $product_group_max = get_post_meta( $variation->get_ID(), 'wcb2b_product_group_max', true );
            if ( ! empty( $product_group_max ) ) {
                if ( is_array( $product_group_max ) && isset( $product_group_max[$customer_group_id] ) && ! empty( $product_group_max[$customer_group_id] ) ) {
                    $args['max_qty'] = min( $args['max_qty'], intval( $product_group_max[$customer_group_id] ) );
                    if ( -1 == $args['max_qty'] || '' == $args['max_qty'] ) {
                        $args['max_qty'] = intval( $product_group_max[$customer_group_id] );
                    }
                }
            }
            $args['quantity_notices'] = wcb2b_get_quantity_notices(
                $args['step'] ?? 1,
                $args['min_qty'] ?? 0,
                $args['max_qty'] ?? 0
            );
        }
        return $args;
    }

    /**
     * Add min and pack amounts to WooCommerce frontend [Variables]
     * 
     * @param array $args Arguments list
     * @param object $product Current product instance
     * @return array
     */
    public function render_quantity_input_loop( $args, $product ) {
        // Choose customer group, if not logged in default GUEST
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            // Minimum quantity
            $product_group_min = get_post_meta( $product->get_ID(), 'wcb2b_product_group_min', true );
            if ( ! empty( $product_group_min ) ) {
                if ( is_array( $product_group_min ) && isset( $product_group_min[$customer_group_id] ) && ! empty( $product_group_min[$customer_group_id] ) ) {
                    $args['quantity'] = intval( $product_group_min[$customer_group_id] );
                }
            }
        }
        return $args;
    }

    /**
     * Add min and pack amount message to product
     */
    public function display_quantity_notices() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            global $product;

            // If is set increment purchase value, display message
            $product_group_packages = get_post_meta( $product->get_id(), 'wcb2b_product_group_packages', true );
            $packages = 1;
            if ( ! empty( $product_group_packages ) ) {
                if ( is_array( $product_group_packages ) && isset( $product_group_packages[$customer_group_id] ) && ! empty( $product_group_packages[$customer_group_id] ) ) {
                    $packages = intval( $product_group_packages[$customer_group_id] );
                }
            }

            // If is set max purchase value, display message
            $product_group_max = get_post_meta( $product->get_ID(), 'wcb2b_product_group_max', true );
            $max = 0;
            if ( ! empty( $product_group_max ) ) {
                if ( is_array( $product_group_max ) && isset( $product_group_max[$customer_group_id] ) && ! empty( $product_group_max[$customer_group_id] ) ) {
                    $max = intval( $product_group_max[$customer_group_id] );
                }
            }

            // If is set min purchase value, display message
            $product_group_min = get_post_meta( $product->get_id(), 'wcb2b_product_group_min', true );
            $min = 0;
            if ( ! empty( $product_group_min ) ) {
                if ( is_array( $product_group_min ) && isset( $product_group_min[$customer_group_id] ) && ! empty( $product_group_min[$customer_group_id] ) ) {
                    $min = intval( $product_group_min[$customer_group_id] );
                }
            }
            printf( '<div id="wcb2b_quantity_notices">%s</div>',
                wcb2b_get_quantity_notices( $packages, $min, $max )
            );
        }
    }

    /**
     * Create hash for prices cache
     * 
     * @param string $hash Default hash
     * @return string
     */
    public function prices_hash( $hash ) {
        if ( is_user_logged_in() ) {
            $hash[] = get_current_user_id();
        }
        return $hash;
    }

    /**
     * Display tier prices table
     */
    public function display_tier_prices() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( ! get_post_meta( $customer_group_id, 'wcb2b_group_hide_prices', true ) ) {
                global $product;

                // Get price visibility
                $product_group_hide_prices = get_post_meta( $product->get_id(), 'wcb2b_product_group_hide_prices', true );
                if ( is_array( $product_group_hide_prices ) && in_array( $customer_group_id, $product_group_hide_prices ) ) {
                    return false;
                }

                $product_group_tier_prices = get_post_meta( $product->get_id(), 'wcb2b_product_group_tier_prices', true );
                if ( isset( $product_group_tier_prices[$customer_group_id] ) ) {
                    $price = $product->get_price();
                    $tier_prices = $product_group_tier_prices[$customer_group_id];

                    wc_get_template( 'single-product/tier-price.php', array(
                        'tier_prices' => $tier_prices,
                        'price'       => $price
                    ), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
                }
            }
        }
    }

    /**
     * Allow search product by SKU
     *
     * @param object $query WP_Query instance
     */
    public function search_by_sku( $query ) {
        if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
            if ( $product_id = wc_get_product_id_by_sku( $query->query['s'] ) ) {
                $product = wc_get_product( $product_id );
                if ( $product->is_type( 'variation' ) ) {
                    $product_id = $product->get_parent_id();
                }
                if ( $product->is_visible() ) {
                    $query->set( 'post__in', array( $product_id ) );
                    unset( $query->query['s'] );
                    unset( $query->query_vars['s'] );
                }
            }
        }
    }

    /**
     * Allow live search products
     *
     * @param object $query WP_Query instance
     * @param array $query_vars WP_Query vars
     * @return object
     */
    public function live_search_prepare( $query, $query_vars ) {
        if ( isset( $query_vars['wcb2b_livesearch'] ) && ! empty( $query_vars['wcb2b_livesearch'] ) ) {
            $query['s'] = esc_attr( $query_vars['wcb2b_livesearch'] );
        }
        return $query;
    }

    /**
     * Ajax call to get Live Search results
     */
    public function live_search_ajax_results() {
        $args = apply_filters( 'wcb2b_livesearch_results_args', array(
            'status'       => array( 'publish' ),
            'type'         => array( 'grouped', 'simple', 'variation' ),
            'limit'        => -1,
            'orderby'      => 'name',
            'order'        => 'ASC',
            'stock_status' => 'instock',
            'return'       => 'ids',
            'exclude'      => wcb2b_get_unallowed_products()
        ) );
        $products_by_sku = wc_get_products( array_merge( $args, array(
            'sku' => esc_attr( $_POST['keyword'] )
        ) ) );

        $products_by_title = wc_get_products( array_merge( $args, array(
            'wcb2b_livesearch' => esc_attr( $_POST['keyword'] )
        ) ) );
        $products = array_unique( array_merge( $products_by_sku, $products_by_title ), SORT_REGULAR );
        if ( empty( $products ) ) { _e( 'No results matching your search', 'woocommerce-b2b' ); }

        foreach ( $products as $product_id ) {
            $product = wc_get_product( $product_id );
            $product_id = $product->get_ID();
            $variation_id = 0;
            if ( $product->get_type() == 'variation' ) {
                $variation_id = $product->get_parent_id();
            }
            $product_group_min = get_post_meta( $product_id, 'wcb2b_product_group_min', true );
            $user_group = wcb2b_get_customer_group();
            $min = $product_group_min[$user_group] ?? 1;
            wc_get_template( 'global/live-search-form-product.php', array(
                'product'   => $product,
                'data'      => sprintf( 'data-product_id="%s" data-variation_id="%s" data-quantity="%s"',
                    $product_id,
                    $variation_id,
                    $min
                ),
                'page'      => esc_attr( $_POST['page'] )
            ), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
        }
        die();
    }

    /**
     * Ajax call to add product to cart
     */
    public function live_search_ajax_addtocart() {
        WC_Form_Handler::add_to_cart_action();
        WC_AJAX::get_refreshed_fragments();
        die();
    }

    /**
     * Display tier prices table for variations
     * 
     * @param array $variation Choosed variation data
     * @return array
     */
    public function display_tier_prices_variation( $variation ) { 
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( ! get_post_meta( $customer_group_id, 'wcb2b_group_hide_prices', true ) ) {
                // Get price visibility
                $product_group_hide_prices = get_post_meta( $variation['variation_id'], 'wcb2b_product_group_hide_prices', true );
                if ( is_array( $product_group_hide_prices ) && in_array( $customer_group_id, $product_group_hide_prices ) ) {
                    return $variation;
                }

                $product_group_tier_prices = get_post_meta( $variation['variation_id'], 'wcb2b_product_group_tier_prices', true );
                if ( isset( $product_group_tier_prices[$customer_group_id] ) ) {
                    $price = $variation['display_price'];
                    $tier_prices = $product_group_tier_prices[$customer_group_id];
                    ob_start();
                    wc_get_template( 'single-product/tier-price.php', array(
                        'tier_prices' => $tier_prices,
                        'price'       => $price
                    ), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
                    $output = ob_get_contents();
                    ob_end_clean();
                    $variation['price_html'] .= $output;
                }
            }
        }
        return $variation;
    }

    /**
     * Always show product variation
     *
     * @param boolean $visible Are variations visibile?
     * @param integer $id Product ID
     * @param integer $parent_id Product parent ID
     * @param object $product Product current object instance
     * @return boolean
     */
    public function always_show_product_variation( $show, $variation_id, $product_id, $variation ) {
        return apply_filters( 'wcb2b_always_show_product_variation', true, $variation_id, $product_id, $variation );
    }

    /**
     * Hide stock by group
     *
     * @param string $html HTML text
     * @param object $product Current product instance
     * @return string
     */
    public function hide_stock_by_group( $html, $product ) {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            // Get stock visibility
            $product_group_hide_stocks = get_post_meta( $product->get_id(), 'wcb2b_product_group_hide_stocks', true );
            if ( is_array( $product_group_hide_stocks ) && in_array( $customer_group_id, $product_group_hide_stocks ) ) {
                return '';
            }
        }
        return $html;
    }

    /**
     * Change price suffix by group
     *
     * @param mixed $pre_option The value to return instead of the option value
     * @param string $option Option name
     * @param mixed $default The fallback value to return if the option does not exist
     * @return mixed
     */
    public function price_suffix( $pre_option, $option, $default ) {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            // Get price suffix
            if ( $price_suffix = get_post_meta( $customer_group_id, 'wcb2b_group_price_suffix', true ) ) {
                return $price_suffix;
            }
        }
        return $pre_option;
    }

    /**
     * Remove WooCommerce default hooks to remove hidden products from up-sells, cross-sells, related
     */
    public function hide_products_init() {
        if ( current_user_can( 'manage_woocommerce' ) ) { return; }

        WCB2B_Configuration::set_unallowed_terms_and_products();
    }

    /**
     * Hide unallowed products from main query
     *   
     * @param object $q Current products query
     */
    public function hide_products_in_query( $q ) {
        $q->set( 'post__not_in', wcb2b_get_unallowed_products() );
    }

    /**
     * Exclude not allowed product categories and products
     *
     * @param array $items Menu items
     * @param string $menu Menu name
     * @param array $args Menu arguments
     * @return array
     */
    public function hide_in_menu( $items, $menu, $args ) {
        $unallowed_products = wcb2b_get_unallowed_products();
        $unallowed_terms = wcb2b_get_unallowed_terms();

        if ( is_array( $items ) && ! empty( $items ) ) {
            foreach ( $items as $key => $item ) {
                if (  
                        ( $item->object == 'product' && in_array( $item->object_id, $unallowed_products ) ) || 
                        ( ( $item->object == 'product_cat' || $item->object == 'product_brand' ) && in_array( $item->object_id, $unallowed_terms ) )
                    ) {
                    unset( $items[$key] );
                }
            }
        }
        return $items;
    }

    /**
     * Remove products from WooCommerce products widgets and shortcodes if has restricted category
     * 
     * @param  array $args Arguments
     * @return array
     */
    public function hide_in_widgets( $args ) {
        $args['tax_query']['relation'] = 'OR';
        $args['tax_query'][] = array(
            'taxonomy'          => 'product_cat',
            'field'             => 'term_id',
            'terms'             => wcb2b_get_unallowed_terms(),
            'operator'          => 'NOT IN',
            'include_children'  => false
        );
        $args['tax_query'][] = array(
            'taxonomy'          => 'product_brand',
            'field'             => 'term_id',
            'terms'             => wcb2b_get_unallowed_terms(),
            'operator'          => 'NOT IN',
            'include_children'  => false
        );
        return $args;
    }

    /**
     * Exclude globally not allowed product categories from displaying
     *   
     * @param array $args Arguments
     * @param array $taxonomies Taxonomies
     * @return array
     */
    public function hide_product_taxonomies( $args, $taxonomies ) {
        if ( ! isset( $args['taxonomy'] ) ) { return $args; }
        if ( ( false === strpos( $args['taxonomy'][0], 'pa_' ) ) && ( ! in_array( 'product_cat', $args['taxonomy'] ) || count( $args['taxonomy'] ) > 1 ) && ( ! in_array( 'product_brand', $args['taxonomy'] ) || count( $args['taxonomy'] ) > 1 ) ) { return $args; }
        if ( ( $args['skip'] ?? false ) ) { return $args; }

        $unallowed_terms = wcb2b_get_unallowed_terms();
        if ( ! empty( $args['exclude'] ) ) {
            $args['exclude'] = array_merge( wp_parse_id_list( $args['exclude'] ), $unallowed_terms );
        } else {
            $args['exclude'] = $unallowed_terms;
        }
        return $args;
    }

    /**
     * Redirect to choosed page if product has restricted category
     */
    public function hidden_product_redirect() {
        global $wp_query;
        $unallowed_terms = wcb2b_get_unallowed_terms();

        // If is a product page
        if ( is_product() ) {
            $skip = empty( $unallowed_terms ) || ( ! has_term( $unallowed_terms, 'product_cat', $wp_query->post->ID ) && ! has_term( $unallowed_terms, 'product_brand', $wp_query->post->ID ) );
            if ( $customer_group_id = wcb2b_get_customer_group() ) {
                // Get product visibility
                if ('variable' == WC_Product_Factory::get_product_type( $wp_query->post->ID ) ) {
                    $visibility = true;
                    $product = wc_get_product( $wp_query->post->ID );
                    $variations = $product->get_visible_children();
                    foreach ( $variations as $variation_id ) {
                        $product_group_visibility = get_post_meta( $variation_id, 'wcb2b_product_group_visibility', true );
                        $visibility &= is_array( $product_group_visibility ) && in_array( $customer_group_id, $product_group_visibility );
                    }
                    $skip = !$visibility;
                }
                $product_group_visibility = get_post_meta( $wp_query->post->ID, 'wcb2b_product_group_visibility', true );
                if ( is_array( $product_group_visibility ) && in_array( $customer_group_id, $product_group_visibility ) ) {
                    $skip = false;
                }
            }
        } elseif ( isset( $wp_query->query['product_cat'] ) ) {
            $terms = get_terms( array(
                'taxonomy'  => 'product_cat',
                'slug'      => $wp_query->query['product_cat'],
                'number'    => 1,
                'hide_empty' => false,
                'skip' => 1
            ) );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                $skip = true;
            } else {
                $term = array_shift( $terms );
                $skip = ! in_array( $term->term_id, $unallowed_terms );
            }
        } elseif ( isset( $wp_query->query['product_brand'] ) ) {
            $terms = get_terms( array(
                'taxonomy'  => 'product_brand',
                'slug'      => $wp_query->query['product_brand'],
                'number'    => 1,
                'hide_empty' => false,
                'skip' => 1
            ) );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                $skip = true;
            } else {
                $term = array_shift( $terms );
                $skip = ! in_array( $term->term_id, $unallowed_terms );
            }
        } else {
            $skip = true;
        }

        if ( $skip ) { return; }

        $redirect = get_option( 'wcb2b_redirect_not_allowed', 'null' );
        switch ( $redirect ) {
            case 'null' :
                break;
            case '0' :
                $wp_query->set_404();
                status_header( 404 );
                break;
            default :
                wp_safe_redirect( get_permalink( $redirect ), 302 );
                exit;
                break;
        }
    }

    /**
     * Hide unallowed products from search, categories, related, cross-sells, up-sells
     *   
     * @param boolean $visible Product visibility
     * @param integer $product_id Current product ID
     * @return boolean
     */
    public function hide_products( $visible, $product_id ) {
        if ( apply_filters( 'wcb2b_manage_visibility_by_all_variations', true, $product_id ) ) {
            if ( $customer_group_id = wcb2b_get_customer_group() ) {
                // Get product visibility
                if ('variable' == WC_Product_Factory::get_product_type( $product_id) ) {
                    $visibility = false;
                    $product = wc_get_product( $product_id );
                    $variations = wp_list_pluck( $product->get_available_variations(), 'variation_id' );
                    foreach ( $variations as $variation_id ) {
                        $product_group_visibility = get_post_meta( $variation_id, 'wcb2b_product_group_visibility', true );
                        $visibility |= ! ( is_array( $product_group_visibility ) && in_array( $customer_group_id, $product_group_visibility ) );
                    }
                    $visible &= $visibility;
                }
                $product_group_visibility = get_post_meta( $product_id, 'wcb2b_product_group_visibility', true );
                if ( is_array( $product_group_visibility ) && in_array( $customer_group_id, $product_group_visibility ) ) {
                    return false;
                }
            }
        }
        if ( in_array( $product_id, wcb2b_get_unallowed_products() ) ) {
            return false;
        }
        return $visible;
    }

    /**
     * Hide unallowed variations from search, categories, related, cross-sells, up-sells
     *   
     * @param boolean $visible Variation visibility
     * @param integer $variation_id Current variation ID
     * @param object $product Current product instance
     * @param array $variation Current variation
     * @return boolean
     */
    public function hide_variation( $visible, $variation_id, $product, $variation ) {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            $product_group_visibility = get_post_meta( $variation_id, 'wcb2b_product_group_visibility', true );
            if ( is_array( $product_group_visibility ) && in_array( $customer_group_id, $product_group_visibility ) ) {
                return false;
            }
        }
        return $visible;
    }

    /**
     * Show "Already bought" message in product page
     */
    public function already_bought() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( get_post_meta( $customer_group_id, 'wcb2b_group_already_bought', true ) ) {
                wcb2b_already_bought();
            }
        }
    }

    /**
     * Show totale sales in product page
     */
    public function show_product_sales() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( $show_sales = get_post_meta( $customer_group_id, 'wcb2b_group_show_sales', true ) ) {
                wcb2b_product_sales();
            }
        }
    }

    /**
     * Display discount percentage in product page
     */
    public function display_discount_message() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( get_post_meta( $customer_group_id, 'wcb2b_group_show_discount_product', true ) ) {
                $price_rules = get_post_meta( $customer_group_id, 'wcb2b_group_price_rule', true );
                if ( in_array( $price_rules, array( 'global', 'both' ) ) ) {
                    // If has discount assigned, display
                    if ( $discount = get_post_meta( $customer_group_id, 'wcb2b_group_discount', true ) ) {
                        $discount = number_format(
                            floatval( $discount ), 
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
     * Display RRP for simple products
     *
     * @param string $price_html Price HTML formatted
     * @param object $object Current variation
     */
    public function display_simple_rrp( $price_html, $object ) {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( $show_rrp = get_post_meta( $customer_group_id, 'wcb2b_group_show_rrp', true ) ) {
                return wcb2b_display_rrp( $price_html, $object );
            }
        }
        return $price_html;
    }

    /**
     * Display RRP for variable products
     *
     * @param array $args Arguments
     * @param object $product Current product
     * @param object $variation Current variation
     */
    public function display_variation_rrp( $args, $product, $variation ) {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( $show_rrp = get_post_meta( $customer_group_id, 'wcb2b_group_show_rrp', true ) ) {
                return wcb2b_display_variation_rrp( $args, $product, $variation );
            }
        }
        return $args;
    }

    /**
     * Display barcode
     */
    public function display_simple_barcode() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( $show_barcode = get_post_meta( $customer_group_id, 'wcb2b_group_show_barcode', true ) ) {
                wcb2b_display_barcode();
            }
        }
    }

    /**
     * Add min and pack amounts to WooCommerce frontend [Variables]
     * 
     * @param array $args Arguments list
     * @param object $product Current product instance
     * @param object $variation Current variation instance
     * @return array
     */
    public function display_variation_barcode( $args, $product, $variation ) {
        if ( $barcode = get_post_meta( $variation->get_id(), 'wcb2b_barcode', true ) ) {
            $args['barcode'] = $barcode;
        }
        return $args;
    }

    /**
     * Enable catalog mode
     * 
     * @param boolean $purchasable Is product purchasable?
     * @return boolean
     */
    public function catalog_mode( $purchasable ) {
        // Choose customer group, if not logged in default GUEST
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            // Option
            $catalog_mode = get_post_meta( $customer_group_id, 'wcb2b_group_catalog_mode', true );
            if ( "1" == $catalog_mode ) {
                return false;
            }
        }
        return $purchasable;
    }

    /**
     * Hide prices to not logged in users hooks
     */
    public function hide_prices_hooks() {
        // Check if group exists or is deleted precedently
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( get_post_meta( $customer_group_id, 'wcb2b_group_hide_prices', true ) ) {
                // Verify if quotations are allowed
                if ( get_post_meta( $customer_group_id, 'wcb2b_group_quotations_with_hidden_prices', true ) ) {
                    // Replace prices with message
                    add_filter( 'woocommerce_get_price_html', function( $price_html ) {
                        return wcb2b_quotation_prices_placeholder();
                    }, 10, 2 );
                    add_filter( 'woocommerce_cart_item_price', function( $price, $cart_item, $cart_item_key ) {
                        return wcb2b_quotation_prices_placeholder();
                    }, 10, 3 );
                    add_filter( 'woocommerce_cart_item_subtotal', function( $subtotal, $cart_item, $cart_item_key ) {
                        return wcb2b_quotation_prices_placeholder();
                    }, 10, 3 );
                    add_filter( 'woocommerce_cart_subtotal', function( $cart_subtotal, $compound, $cart ) {
                        return wcb2b_quotation_prices_placeholder();
                    }, 10, 3 );
                    add_filter( 'woocommerce_cart_totals_fee_html', function( $cart_totals_fee_html, $fee ) {
                        return wcb2b_quotation_prices_placeholder();
                    }, 10, 3 );
                    add_filter( 'woocommerce_cart_totals_order_total_html', function( $value ) {
                        return wcb2b_quotation_prices_placeholder();
                    } );
                    // Remove shipping claculation in cart
                    add_filter( 'woocommerce_cart_ready_to_calc_shipping', '__return_false' );
                    add_filter( 'pre_option_woocommerce_enable_shipping_calc', function( $pre_option, $option_name, $default ) {
                        return 'no';
                    }, 98, 3 );
                    // Replace add to cart button message
                    add_filter( 'woocommerce_product_single_add_to_cart_text', 'wcb2b_custom_add_cart_button', 9999 ); 
                    add_filter( 'woocommerce_product_add_to_cart_text', 'wcb2b_custom_add_cart_button', 9999 );
                } else {
                    // Remove prices also for grouped and variable products
                    add_filter('woocommerce_get_price_html', '__return_false' );
                    
                    // Remove add to cart button to variable products
                    remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
                    
                    // Add a link to login page with message
                    add_action( 'woocommerce_after_shop_loop_item', 'wcb2b_login_message' );
                    add_action( 'woocommerce_single_product_summary', 'wcb2b_login_message' );

                    // Any product can be purchased 
                    add_filter( 'woocommerce_is_purchasable', '__return_false' );

                    // Remove all product from cart
                    add_action( 'woocommerce_loaded', function() {
                        wc_empty_cart();
                    } );
                }

                // Remove sale flash and price from loop
                remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
                remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

                // Remove sale flash and price from single product
                remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
                remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );

                // Fix to remove sale flash message from loop
                add_filter( 'woocommerce_sale_flash', '__return_false' );

                // Remove offers object if prices are hidden
                add_filter( 'woocommerce_structured_data_product', function( $markup ) {
                    unset( $markup['offers'] );
                    return $markup;
                } );

                // Remove price filter for guests
                add_action( 'widgets_init', function() {
                    unregister_widget( 'WC_Widget_Price_Filter' );
                }, 99 );
            }
        }
    }

    /**
     * Add shipping tab in product page
     *
     * @param array $tabs All product tabs
     * @return array
     */
    public function shippings_tab( $tabs ) {
        // Check if group exists or is deleted precedently
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( get_post_meta( $customer_group_id, 'wcb2b_group_shippings_tab', true ) ) {
                $tabs['wcb2b_shippings_tab'] = array(
                    'title'     => __( 'Shipping Informations', 'woocommerce-b2b' ),
                    'priority'  => 50,
                    'callback'  => 'wcb2b_shipping_table_content'
                );
            }
        }
        return $tabs;
    }

    /**
     * Add purchase history tab in product page
     *
     * @param array $tabs All product tabs
     * @return array
     */
    function purchase_history_tab( $tabs ) {
        // Only registered and logged in customers can have purchase history
        if ( is_user_logged_in() ) {
            // Check if group exists or is deleted precedently
            if ( $customer_group_id = wcb2b_get_customer_group() ) {
                if ( get_post_meta( $customer_group_id, 'wcb2b_group_purchase_history_tab', true ) ) {
                    $tabs['wcb2b_purchase_history_tab'] = array(
                        'title'     => __( 'Your purchase history', 'woocommerce-b2b' ),
                        'priority'  => 50,
                        'callback'  => 'wcb2b_purchase_history_table_content'
                    );
                }
            }
        }
        return $tabs;
    }

    /**
     * Display estimated delivery time to product page
     */
    public function show_product_estimated_delivery() {
        global $product;
        if ( $estimated_delivery = wcb2b_estimated_delivery( $product->get_id() ) ) {
            printf( '<div class="wcb2b-estimated-delivery">%s</div>',
                sprintf( esc_html__( 'Estimated delivery: %s', 'woocommerce-b2b' ),
                    $estimated_delivery
                )
            );
        }
    }

    /**
     * Display shipping class in product page
     */
    public function show_product_shipping_class() {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( get_post_meta( $customer_group_id, 'wcb2b_group_show_shipping_class', true ) ) {
                global $product;
                $class_id = $product->get_shipping_class_id();
                if ( $class_id ) {
                    $term = get_term_by( 'id', $class_id, 'product_shipping_class' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        printf( '<div class="wcb2b-shipping-class">%s</div>',
                            sprintf( __( 'Shipping class: %s', 'woocommerce-b2b' ), $term->name )
                        );
                    }
                }
            }
        }
    }

    /*** BLOCKS ***/

    /**
     * Hide unallowed products from blocks (grid)
     *   
     * @param string $html Product markup
     * @param array $data Current product data
     * @param object $product Current product instance
     * @return string
     */
    public function hide_products_in_block_grid( $html, $data, $product ) {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            if ( 'variable' == $product->get_type() ) {
                $visibility = true;
                foreach ( $product->get_visible_children() as $variation_id ) {
                    $product_group_visibility = get_post_meta( $variation_id, 'wcb2b_product_group_visibility', true );
                    $visibility &= ! ( is_array( $product_group_visibility ) && in_array( $customer_group_id, $product_group_visibility ) );
                }
                return $visibility ? $html : false;
            }
            $product_group_visibility = get_post_meta( $product->get_id(), 'wcb2b_product_group_visibility', true );
            if ( is_array( $product_group_visibility ) && in_array( $customer_group_id, $product_group_visibility ) ) {
                return false;
            }
            if ( in_array( $product->get_id(), wcb2b_get_unallowed_products() ) ) {
                return false;
            }
        }
        return $html;
    }

    /**
     * Hide unallowed products from blocks (single-product)
     *   
     * @param array $context Block context.
     * @param array $block Block attributes.
     * @param WP_Block $parent_block Block instance.
     * @return string
     */
    public function hide_products_in_block_single_product( $context, $block, $parent_block ) {
        if ( 'woocommerce/single-product' === $block['blockName'] ) {
            if ( $customer_group_id = wcb2b_get_customer_group() ) {
                $product = wc_get_product( $block['attrs']['productId'] );
                if ( 'variable' == $product->get_type() ) {
                    $visibility = true;
                    foreach ( $product->get_visible_children() as $variation_id ) {
                        $product_group_visibility = get_post_meta( $variation_id, 'wcb2b_product_group_visibility', true );
                        $visibility &= ! ( is_array( $product_group_visibility ) && in_array( $customer_group_id, $product_group_visibility ) );
                    }
                    return $visibility ? $context : '';
                }
                $product_group_visibility = get_post_meta( $product->get_id(), 'wcb2b_product_group_visibility', true );
                if ( is_array( $product_group_visibility ) && in_array( $customer_group_id, $product_group_visibility ) ) {
                    return '';
                }
                if ( in_array( $product->get_id(), wcb2b_get_unallowed_products() ) ) {
                    return '';
                }
            }
        }
        return $context;
    }

    /**
     * Manage group simulator init
     */
    public function group_simulator_init() { 
        if ( apply_filters( 'wcb2b_compatibility__enable_group_simulator', false ) ) {
            $hooks = apply_filters( 'wcb2b_compatibility__enable_group_simulator_hooks', array(
                'woocommerce_before_shop_loop'      => 1,
                'woocommerce_before_single_product' => 1,
                'woocommerce_before_cart'           => 1,
            ) );
            foreach ( $hooks as $hook => $priority ) {
                add_action( $hook, array( $this, 'wcb2b_group_selector' ), $priority );
            }
            add_filter( 'wcb2b_get_customer_group', array( $this, 'current_group_simulator' ), 10, 2 );
        }
    }

    /**
     * Show group simulator dropdown
     */
    public function wcb2b_group_selector() {
        $original_customer_group_id = wcb2b_get_customer_group( false, true );
        $current_customer_group_id = wcb2b_get_customer_group();
        $groups = wcb2b_get_groups();

        wc_get_template( 'global/group-simulator.php', array(
            'original_customer_group_id'    => $original_customer_group_id,
            'current_customer_group_id'     => $current_customer_group_id,
            'groups'                        => $groups,
        ), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
    }

    /**
     * Store temporary customer group in session
     *
     * @param integer $group_id Current group ID
     * @param integer $user_id Current user ID
     * @return integer
     */
    public function current_group_simulator( $group_id, $user_id ) {
        if ( ! isset( WC()->session ) ) {
            return $group_id;
        }
        if ( isset( $_POST['wcb2b_group'] ) ) {
            WC()->session->set_customer_session_cookie( true );
            WC()->session->set( 'wcb2b_group', (int)$_POST['wcb2b_group'] );
        }
        return WC()->session->get( 'wcb2b_group' ) ?? $group_id;
    }

}

return new WCB2B_Frontend_Products();