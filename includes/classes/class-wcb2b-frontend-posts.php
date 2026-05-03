<?php

/**
 * WooCommerce B2B Posts
 *
 * @version 3.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Frontend_Posts
 */
class WCB2B_Frontend_Posts {

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
        add_action( 'template_redirect', array( $this, 'redirect' ), 10 );
        add_filter( 'wp_get_nav_menu_items', array( $this, 'remove_from_menu' ), 10, 3 );
        add_filter( 'get_pages', array( $this, 'remove_from_list' ), 10, 2 );
    }

    /**
     * Redirect to choosed page if page is restricted
     */
    public function redirect() {
        if ( is_singular( array( 'page' ) ) && ! in_array( get_the_ID(), wcb2b_get_always_visible_pages() ) ) {
            if ( $customer_group_id = wcb2b_get_customer_group() ) {
                // If not exists meta, by default is visible
                if ( $visibility = get_post_meta( get_the_ID(), 'wcb2b_group_visibility', true ) ) {
                    if ( ! in_array( $customer_group_id, (array)$visibility ) ) {
                        if ( $redirect = apply_filters( 'wcb2b_redirect_hidden_page', false ) ) {
                            wp_safe_redirect( $redirect, 302 );
                            exit;
                        }
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                    }
                }
            }
        }
    }

    /**
     * Remove pages from menu
     * 
     * @param array $items Menu items
     * @param string $menu Menu name
     * @param array $args Menu arguments
     * @return array
     */
    public function remove_from_menu( $items, $menu, $args ) {
        if ( $customer_group_id = wcb2b_get_customer_group() ) {
            foreach ( $items as $key => $item ) {
                if ( ! in_array( $item->object_id, wcb2b_get_always_visible_pages() ) ) {
                    // If not exists meta, by default is visible
                    if ( $visibility = get_post_meta( $item->object_id, 'wcb2b_group_visibility', true ) ) {
                        if ( in_array( get_post_type( $item->object_id ), array( 'page' ) ) && ! in_array( $customer_group_id, (array)$visibility ) ) {
                            unset( $items[$key] );
                        }
                    }
                }
            }
        }
        return $items;
    }

    /**
     * Remove pages from lists
     * 
     * @param array $pages Pages list
     * @param string $r
     * @return array
     */
    public function remove_from_list( $pages, $r ) {
        if ( did_action( 'init' ) ) {
            if ( $customer_group_id = wcb2b_get_customer_group() ) {
                foreach ( $pages as $key => $page ) {
                    if ( ! in_array( $page->ID, wcb2b_get_always_visible_pages() ) ) {
                        // If not exists meta, by default is visible
                        if ( $visibility = get_post_meta( $page->ID, 'wcb2b_group_visibility', true ) ) {
                            if ( in_array( get_post_type( $page->ID ), array( 'page' ) ) && ! in_array( $customer_group_id, (array)$visibility ) ) {
                                unset( $pages[$key] );
                            }
                        }
                    }
                }
            }
            
        }
        return $pages;
    }

}

return new WCB2B_Frontend_Posts();