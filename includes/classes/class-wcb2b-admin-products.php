<?php

/**
 * WooCommerce B2B Products
 *
 * @version 4.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Admin_Products
 */
class WCB2B_Admin_Products {

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
        add_action( 'woocommerce_product_options_pricing', array( $this, 'add_product_data_hook' ) );
        add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_variation_data_hook' ), 10, 3 );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ) );
        add_action( 'woocommerce_save_product_variation', array( $this, 'save_product_data' ) );
        add_filter( 'woocommerce_product_export_column_names', array( $this, 'add_export_column' ) );
        add_filter( 'woocommerce_product_export_product_default_columns', array( $this, 'add_export_column' ) );
        add_filter( 'woocommerce_product_export_product_query_args', array( $this, 'query_args' ) );
        add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'import_mapping_options' ) );
        add_filter( 'woocommerce_product_import_pre_insert_product_object', array( $this, 'import_pre_insert_product_object' ), 10, 2 );
        add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array( $this, 'add_column_to_mapping_screen' ) );
        // Categories and brand visibility
        add_action( 'product_cat_add_form_fields', array( $this, 'add_taxonomy_visibility_metabox' ) );
        add_action( 'product_cat_edit_form_fields', array( $this, 'edit_taxonomy_visibility_metabox' ), 10 );
        add_action( 'product_brand_add_form_fields', array( $this, 'add_taxonomy_visibility_metabox' ) );
        add_action( 'product_brand_edit_form_fields', array( $this, 'edit_taxonomy_visibility_metabox' ), 10 );
        add_action( 'created_term', array( $this, 'save_taxonomy_visibility_fields' ), 10, 3 );
        add_action( 'edit_term', array( $this, 'save_taxonomy_visibility_fields' ), 10, 3 );
    }

    /**
     * Add new field to manage group product data in WooCommerce products
     */
    public function add_product_data_hook() {
        global $post;
        $this->build_product_data_fields( $post->ID, false );
    }

    /**
     * Add new field to manage group product data in WooCommerce variations
     *
     * @param integer $loop Position in the loop
     * @param array $variation_data Variation data
     * @param object $variation Post data
     */
    public function add_variation_data_hook( $loop, $variation_data, $variation ) {
        $this->build_product_data_fields( $variation->ID, true );
    }

    /**
     * Build group data fields
     *
     * @param int $post_id Current product ID
     * @param bool $is_variation Return if current product is a variation
     */
    public function build_product_data_fields( $post_id, $is_variation ) {
        // Retrieve data and groups
        $product_group_visibility = get_post_meta( $post_id, 'wcb2b_product_group_visibility', true );
        $barcode = get_post_meta( $post_id, 'wcb2b_barcode', true );
        $product_group_hide_prices = get_post_meta( $post_id, 'wcb2b_product_group_hide_prices', true );
        $product_group_hide_stocks = get_post_meta( $post_id, 'wcb2b_product_group_hide_stocks', true );
        $product_group_prices = get_post_meta( $post_id, 'wcb2b_product_group_prices', true );
        $product_group_tier_prices = get_post_meta( $post_id, 'wcb2b_product_group_tier_prices', true );
        $product_group_packages = get_post_meta( $post_id, 'wcb2b_product_group_packages', true );
        $product_group_min = get_post_meta( $post_id, 'wcb2b_product_group_min', true );
        $product_group_max = get_post_meta( $post_id, 'wcb2b_product_group_max', true );
        $groups = wcb2b_get_groups();

        include WCB2B_ABSPATH . 'includes/views/html-admin-product-data.php';
    }

    /**
     * Save value into database
     *
     * @param int $product_id Product ID
     */
    public function save_product_data( $product_id ) {
        $product_group_visibility = isset( $_POST['wcb2b_product_group_visibility'][$product_id] ) ? $_POST['wcb2b_product_group_visibility'][$product_id] : array();
        update_post_meta( $product_id, 'wcb2b_product_group_visibility', array_keys( $product_group_visibility ) );

        $barcode = isset( $_POST['wcb2b_barcode'][$product_id] ) ? $_POST['wcb2b_barcode'][$product_id] : array();
        update_post_meta( $product_id, 'wcb2b_barcode', $barcode );

        $product_group_hide_prices = isset( $_POST['wcb2b_product_group_hide_prices'][$product_id] ) ? $_POST['wcb2b_product_group_hide_prices'][$product_id] : array();
        update_post_meta( $product_id, 'wcb2b_product_group_hide_prices', array_keys( $product_group_hide_prices ) );

        $product_group_hide_stocks = isset( $_POST['wcb2b_product_group_hide_stocks'][$product_id] ) ? $_POST['wcb2b_product_group_hide_stocks'][$product_id] : array();
        update_post_meta( $product_id, 'wcb2b_product_group_hide_stocks', array_keys( $product_group_hide_stocks ) );

        $product_group_prices = isset( $_POST['wcb2b_product_group_prices'][$product_id] ) ? $_POST['wcb2b_product_group_prices'][$product_id] : '';
        if ( $product_group_prices ) {
            foreach ( $product_group_prices as $group_id => $product_group_price ) {
                if ( '' != $product_group_price['regular_price'] ) {
                    $product_group_price['regular_price'] = wc_format_decimal( $product_group_price['regular_price'], '' );
                }
                $product_group_prices[$group_id]['regular_price'] = $product_group_price['regular_price'];
                if ( '' !=  $product_group_price['sale_price'] ) {
                    $product_group_price['sale_price'] = wc_format_decimal( $product_group_price['sale_price'], '' );
                }
                $product_group_prices[$group_id]['sale_price'] = $product_group_price['sale_price'];
            }
        }
        update_post_meta( $product_id, 'wcb2b_product_group_prices', $product_group_prices );

        $product_group_tier_prices = isset( $_POST['wcb2b_product_group_tier_prices'][$product_id] ) ? $_POST['wcb2b_product_group_tier_prices'][$product_id] : '';
        if ( $product_group_tier_prices ) {
            foreach ( $product_group_tier_prices as $group_id => $product_group_tier_price ) {
                if ( '' != $product_group_tier_price['price'] ) {
                    $product_group_tier_price['price'] = wc_format_decimal( $product_group_tier_price['price'] );
                }
                $product_group_tier_prices[$group_id] = array_combine(
                    $product_group_tier_price['quantity'],
                    $product_group_tier_price['price']
                );
                ksort( $product_group_tier_prices[$group_id], SORT_NUMERIC );
            }
        }
        update_post_meta( $product_id, 'wcb2b_product_group_tier_prices', $product_group_tier_prices );

        $product_group_packages = isset( $_POST['wcb2b_product_group_packages'][$product_id] ) ? $_POST['wcb2b_product_group_packages'][$product_id] : array();
        update_post_meta( $product_id, 'wcb2b_product_group_packages', $product_group_packages );
        
        $product_group_min = isset( $_POST['wcb2b_product_group_min'][$product_id] ) ? $_POST['wcb2b_product_group_min'][$product_id] : array();
        update_post_meta( $product_id, 'wcb2b_product_group_min', $product_group_min );
        
        $product_group_max = isset( $_POST['wcb2b_product_group_max'][$product_id] ) ? $_POST['wcb2b_product_group_max'][$product_id] : array();
        update_post_meta( $product_id, 'wcb2b_product_group_max', $product_group_max );
    }

    /**
     * Add prices fields to export tools
     * 
     * @param array $columns Columns definition
     * @return array
     */
    public function add_export_column( $columns ) {
        $groups = wcb2b_get_groups();
        if ( $groups->have_posts() ) {
            while ( $groups->have_posts() ) {
                $groups->the_post();

                $columns['wcb2b_product_group_visibility_' . get_the_ID()] = sprintf( __( 'Product visibility (%s)', 'woocommerce-b2b' ), get_the_title() );
                $columns['wcb2b_product_group_prices_' . get_the_ID() . '_regular_price'] = sprintf( __( 'Regular price (%s)', 'woocommerce-b2b' ), get_the_title() );
                $columns['wcb2b_product_group_prices_' . get_the_ID() . '_sale_price'] = sprintf( __( 'Sale price (%s)', 'woocommerce-b2b' ), get_the_title() );
                $columns['wcb2b_product_group_tier_prices_' . get_the_ID()] = sprintf( __( 'Tier prices (%s)', 'woocommerce-b2b' ), get_the_title() );
                $columns['wcb2b_product_group_hide_prices_' . get_the_ID()] = sprintf( __( 'Hide prices (%s)', 'woocommerce-b2b' ), get_the_title() );
                $columns['wcb2b_product_group_hide_stocks_' . get_the_ID()] = sprintf( __( 'Hide stocks (%s)', 'woocommerce-b2b' ), get_the_title() );

                $columns['wcb2b_product_group_packages_' . get_the_ID()] = sprintf( __( 'Packages (%s)', 'woocommerce-b2b' ), get_the_title() );
                $columns['wcb2b_product_group_min_' . get_the_ID()] = sprintf( __( 'Minimum quantity (%s)', 'woocommerce-b2b' ), get_the_title() );
                $columns['wcb2b_product_group_max_' . get_the_ID()] = sprintf( __( 'Maximum quantity (%s)', 'woocommerce-b2b' ), get_the_title() );
            }
        }

        $columns['wcb2b_barcode'] = __( 'Barcode', 'woocommerce-b2b' );
        return $columns;
    }

    /**
     * Manage prices fields in export tools
     * 
     * @param  array $args Arguments for WP_Query
     * @return array
     */
    public function query_args( $args ) {
        $groups = wcb2b_get_groups();
        if ( $groups->have_posts() ) {
            while ( $groups->have_posts() ) {
                $groups->the_post();

                // Provide the data to be exported for one item in the column
                add_filter( 'woocommerce_product_export_product_column_wcb2b_product_group_visibility_' . get_the_ID(), function( $value, $product, $column_id ) {
                    $group_id = (int) substr( filter_var( $column_id, FILTER_SANITIZE_NUMBER_INT ), 1 );    
                    $product_group_visibility = $product->get_meta( 'wcb2b_product_group_visibility' );

                    if ( is_array( $product_group_visibility ) && in_array( $group_id, $product_group_visibility ) ) {
                        return 1;
                    }

                    return 0;
                }, 10, 3 );

                // Provide the data to be exported for one item in the column
                add_filter( 'woocommerce_product_export_product_column_wcb2b_product_group_hide_prices_' . get_the_ID(), function( $value, $product, $column_id ) {
                    $group_id = (int) substr( filter_var( $column_id, FILTER_SANITIZE_NUMBER_INT ), 1 );    
                    $product_group_hide_prices = $product->get_meta( 'wcb2b_product_group_hide_prices' );

                    if ( is_array( $product_group_hide_prices ) && in_array( $group_id, $product_group_hide_prices ) ) {
                        return 1;
                    }

                    return 0;
                }, 10, 3 );

                // Provide the data to be exported for one item in the column
                add_filter( 'woocommerce_product_export_product_column_wcb2b_product_group_hide_stocks_' . get_the_ID(), function( $value, $product, $column_id ) {
                    $group_id = (int) substr( filter_var( $column_id, FILTER_SANITIZE_NUMBER_INT ), 1 );    
                    $product_group_hide_stocks = $product->get_meta( 'wcb2b_product_group_hide_stocks' );

                    if ( is_array( $product_group_hide_stocks ) && in_array( $group_id, $product_group_hide_stocks ) ) {
                        return 1;
                    }

                    return 0;
                }, 10, 3 );

                add_filter( 'woocommerce_product_export_product_column_wcb2b_product_group_prices_' . get_the_ID() . '_regular_price', function( $value, $product, $column_id ) {
                    $group_id = (int) substr( filter_var( $column_id, FILTER_SANITIZE_NUMBER_INT ), 1 );    
                    $product_group_prices = $product->get_meta( 'wcb2b_product_group_prices' );

                    if ( is_array( $product_group_prices ) && array_key_exists( $group_id, $product_group_prices ) ) {
                        if ( is_array( $product_group_prices[$group_id] ) && array_key_exists( 'regular_price', $product_group_prices[$group_id] ) ) {
                            return wcb2b_price_format( $product_group_prices[$group_id]['regular_price'] );
                        }
                    }

                    return $value;
                }, 10, 3 );

                add_filter( 'woocommerce_product_export_product_column_wcb2b_product_group_prices_' . get_the_ID() . '_sale_price', function( $value, $product, $column_id ) {
                    $group_id = (int) substr( filter_var( $column_id, FILTER_SANITIZE_NUMBER_INT ), 1 );    
                    $product_group_prices = $product->get_meta( 'wcb2b_product_group_prices' );

                    if ( is_array( $product_group_prices ) && array_key_exists( $group_id, $product_group_prices ) ) {
                        if ( is_array( $product_group_prices[$group_id] ) && array_key_exists( 'sale_price', $product_group_prices[$group_id] ) ) {
                            return wcb2b_price_format( $product_group_prices[$group_id]['sale_price'] );
                        }
                    }

                    return $value;
                }, 10, 3 );

                add_filter( 'woocommerce_product_export_product_column_wcb2b_product_group_tier_prices_' . get_the_ID(), function( $value, $product, $column_id ) {
                    $group_id = (int) substr( filter_var( $column_id, FILTER_SANITIZE_NUMBER_INT ), 1 );    
                    $product_group_tier_prices = $product->get_meta( 'wcb2b_product_group_tier_prices' );

                    if ( is_array( $product_group_tier_prices ) && array_key_exists( $group_id, $product_group_tier_prices ) ) {
                        if ( is_array( $product_group_tier_prices[$group_id] ) ) {
                            return implode( '|', array_map(
                                function ( $v, $k ) {
                                    return sprintf( "%s:%s", $k, wcb2b_price_format( $v ) );
                                },
                                $product_group_tier_prices[$group_id],
                                array_keys($product_group_tier_prices[$group_id])
                            ) );
                        }
                    }

                    return $value;
                }, 10, 3 );

                // Packages and minimum quantity
                add_filter( 'woocommerce_product_export_product_column_wcb2b_product_group_packages_' . get_the_ID(), function( $value, $product, $column_id ) {
                    $group_id = (int) substr( filter_var( $column_id, FILTER_SANITIZE_NUMBER_INT ), 1 );
                    $product_group_packages = $product->get_meta( 'wcb2b_product_group_packages' );
                    if ( is_array( $product_group_packages ) && array_key_exists( $group_id, $product_group_packages ) ) {
                        return intval( $product_group_packages[$group_id] );
                    }
                    return $value;
                }, 10, 3 );
                add_filter( 'woocommerce_product_export_product_column_wcb2b_product_group_min_' . get_the_ID(), function( $value, $product, $column_id ) {
                    $group_id = (int) substr( filter_var( $column_id, FILTER_SANITIZE_NUMBER_INT ), 1 );
                    $product_group_min = $product->get_meta( 'wcb2b_product_group_min' );
                    if ( is_array( $product_group_min ) && array_key_exists( $group_id, $product_group_min ) ) {
                        return intval( $product_group_min[$group_id] );
                    }
                    return $value;
                }, 10, 3 );
                add_filter( 'woocommerce_product_export_product_column_wcb2b_product_group_max_' . get_the_ID(), function( $value, $product, $column_id ) {
                    $group_id = (int) substr( filter_var( $column_id, FILTER_SANITIZE_NUMBER_INT ), 1 );
                    $product_group_max = $product->get_meta( 'wcb2b_product_group_max' );
                    if ( is_array( $product_group_max ) && array_key_exists( $group_id, $product_group_max ) ) {
                        return intval( $product_group_max[$group_id] );
                    }
                    return $value;
                }, 10, 3 );
            }
        }

        // Force default product prices
        add_filter( 'woocommerce_product_export_product_column_regular_price', function( $value, $product, $column_id ) {
            return get_post_meta( $product->get_id(), '_regular_price', true );
        }, 10, 3 );
        add_filter( 'woocommerce_product_export_product_column_sale_price', function( $value, $product, $column_id ) {
            return get_post_meta( $product->get_id(), '_sale_price', true );
        }, 10, 3 );

        // Barcode
        add_filter( 'woocommerce_product_export_product_column_wcb2b_barcode', function( $value, $product, $column_id ) {
            return $product->get_meta( 'wcb2b_barcode' );
        }, 10, 3 );

        return $args;
    }

    /**
     * Register the columns in the importer
     * 
     * @param  array $options Mapping options
     * @return array
     */
    public function import_mapping_options( $options ) {
        $groups = wcb2b_get_groups();

        if ( $groups->have_posts() ) {
            while ( $groups->have_posts() ) {
                $groups->the_post();

                // Add group prices option to columns
                $options['wcb2b_product_group_visibility_' . get_the_ID()] = array(
                    'name' => sprintf( __( 'WCB2B - Product visibility for %s group', 'woocommerce-b2b' ), get_the_title() ),
                    'options' => array(
                        'wcb2b_product_group_visibility:' . get_the_ID() => sprintf( __( 'Product visibility (%s)', 'woocommerce-b2b' ), get_the_title() )
                    )
                );

                // Add group prices option to columns
                $options['wcb2b_product_group_prices_' . get_the_ID()] = array(
                    'name' => sprintf( __( 'WCB2B - Product prices for %s group', 'woocommerce-b2b' ), get_the_title() ),
                    'options' => array(
                        'wcb2b_product_group_hide_prices:' . get_the_ID() => sprintf( __( 'Hide prices (%s)', 'woocommerce-b2b' ), get_the_title() ),
                        'wcb2b_product_group_hide_stocks:' . get_the_ID() => sprintf( __( 'Hide stocks (%s)', 'woocommerce-b2b' ), get_the_title() ),
                        'wcb2b_product_group_prices:' . get_the_ID() . ':regular_price' => sprintf( __( 'Regular price (%s)', 'woocommerce-b2b' ), get_the_title() ),
                        'wcb2b_product_group_prices:' . get_the_ID() . ':sale_price' => sprintf( __( 'Sale price (%s)', 'woocommerce-b2b' ), get_the_title() ),
                        'wcb2b_product_group_tier_prices:' . get_the_ID() => sprintf( __( 'Tier prices (%s)', 'woocommerce-b2b' ), get_the_title() )
                    )
                );

                // Packages and minimum quantity
                $options['wcb2b_quantity_' . get_the_ID()] = array(
                    'name' => sprintf( __( 'WCB2B - Package and min-max quantity for %s group', 'woocommerce-b2b' ), get_the_title() ),
                    'options' => array(
                        'wcb2b_product_group_packages:' . get_the_ID() => sprintf( __( 'Packages (%s)', 'woocommerce-b2b' ), get_the_title() ),
                        'wcb2b_product_group_min:' . get_the_ID() => sprintf( __( 'Minimum quantity (%s)', 'woocommerce-b2b' ), get_the_title() ),
                        'wcb2b_product_group_max:' . get_the_ID() => sprintf( __( 'Maximum quantity (%s)', 'woocommerce-b2b' ), get_the_title() )
                    )
                );
            }
        }

        // Barcode
        $options['wcb2b_barcode'] = array(
            'name' => __( 'WCB2B - Barcode', 'woocommerce-b2b' ),
            'options' => array(
                'wcb2b_barcode' => __( 'Barcode', 'woocommerce-b2b' )
            )
        );
        return $options;
    }

    /**
     * Process the data read from the CSV file
     *
     * @param object $object Current object instance
     * @param array $data Data from CSV file
     * @return object
     */
    public function import_pre_insert_product_object( $object, $data ) {
        $product_group_visibility = $object->get_meta( 'wcb2b_product_group_visibility', true );
        if ( ! $product_group_visibility ) {
            $product_group_visibility = array();
        }
        $product_group_hide_prices = $object->get_meta( 'wcb2b_product_group_hide_prices', true );
        if ( ! $product_group_hide_prices ) {
            $product_group_hide_prices = array();
        }
        $product_group_hide_stocks = $object->get_meta( 'wcb2b_product_group_hide_stocks', true );
        if ( ! $product_group_hide_stocks ) {
            $product_group_hide_stocks = array();
        }
        $product_group_prices = $object->get_meta( 'wcb2b_product_group_prices', true );
        if ( ! $product_group_prices ) {
            $product_group_prices = array();
        }
        $product_group_tier_prices = $object->get_meta( 'wcb2b_product_group_tier_prices', true );
        if ( ! $product_group_tier_prices ) {
            $product_group_tier_prices = array();
        }
        $product_group_packages = $object->get_meta( 'wcb2b_product_group_packages', true );
        if ( ! $product_group_packages ) {
            $product_group_packages = array();
        }
        $product_group_min = $object->get_meta( 'wcb2b_product_group_min', true );
        if ( ! $product_group_min ) {
            $product_group_min = array();
        }
        $product_group_max = $object->get_meta( 'wcb2b_product_group_max', true );
        if ( ! $product_group_max ) {
            $product_group_max = array();
        }
        $groups = wcb2b_get_groups();

        if ( $groups->have_posts() ) {
            while ( $groups->have_posts() ) {
                $groups->the_post();

                // Set product visibility option
                if ( ! empty( $data['wcb2b_product_group_visibility:' . get_the_ID()] ) ) {
                    $product_group_visibility[] = get_the_ID();
                } else {
                    $key = array_search( get_the_ID(), $product_group_visibility );
                    if ( false !== $key ) {
                        unset( $product_group_visibility[$key] );
                    }
                }

                // Set hide prices option
                if ( ! empty( $data['wcb2b_product_group_hide_prices:' . get_the_ID()] ) ) {
                    $product_group_hide_prices[] = get_the_ID();
                } else {
                    $key = array_search( get_the_ID(), $product_group_hide_prices );
                    if ( false !== $key ) {
                        unset( $product_group_hide_prices[$key] );
                    }
                }

                // Set hide stocks option
                if ( ! empty( $data['wcb2b_product_group_hide_stocks:' . get_the_ID()] ) ) {
                    $product_group_hide_stocks[] = get_the_ID();
                } else {
                    $key = array_search( get_the_ID(), $product_group_hide_stocks );
                    if ( false !== $key ) {
                        unset( $product_group_hide_stocks[$key] );
                    }
                }

                // Set the new regular price (if exists)
                if ( isset( $data['wcb2b_product_group_prices:' . get_the_ID() . ':regular_price'] ) ) {
                    $value = $data['wcb2b_product_group_prices:' . get_the_ID() . ':regular_price'];
                    if ('' != $value) {
                        $value = wc_format_decimal( $value, '' );
                    }
                    $product_group_prices[get_the_ID()]['regular_price'] = $value;
                }

                // Set the new sale price (if exists)
                if ( isset( $data['wcb2b_product_group_prices:' . get_the_ID() . ':sale_price'] ) ) {
                    $value = $data['wcb2b_product_group_prices:' . get_the_ID() . ':sale_price'];
                    if ('' != $value) {
                        $value = wc_format_decimal( $value, '' );
                    }
                    $product_group_prices[get_the_ID()]['sale_price'] = $value;
                }

                // Set the new tier price (if exists)
                if ( isset( $data['wcb2b_product_group_tier_prices:' . get_the_ID()] ) ) {
                    $value = $data['wcb2b_product_group_tier_prices:' . get_the_ID()];
                    if ('' != $value) {
                        $keys = array();
                        $tier_prices = explode( '|', $value );
                        foreach ( $tier_prices as $tier_price ) {
                            $values = explode( ':', $tier_price );
                            array_push($keys, $values[0]); // Store quantity allowed
                            $product_group_tier_prices[get_the_ID()][$values[0]] = wc_format_decimal( $values[1], '' );
                        }
                        $product_group_tier_prices[get_the_ID()] = array_intersect_key(
                            $product_group_tier_prices[get_the_ID()],
                            array_flip( $keys )
                        );
                        ksort( $product_group_tier_prices[get_the_ID()] );
                    } else {
                        if ( isset( $product_group_tier_prices[get_the_ID()] ) ) {
                            unset( $product_group_tier_prices[get_the_ID()] );
                        }
                    }
                }

                // Set the new quantities (if exists)
                if ( isset( $data['wcb2b_product_group_packages:' . get_the_ID()] ) ) {
                    $value = $data['wcb2b_product_group_packages:' . get_the_ID()];
                    if ('' != $value) {
                        $value = intval( $value );   
                    }
                    $product_group_packages[get_the_ID()] = $value;
                }
                if ( isset( $data['wcb2b_product_group_min:' . get_the_ID()] ) ) {
                    $value = $data['wcb2b_product_group_min:' . get_the_ID()];
                    if ('' != $value) {
                        $value = intval( $value );   
                    }
                    $product_group_min[get_the_ID()] = $value;
                }
                if ( isset( $data['wcb2b_product_group_max:' . get_the_ID()] ) ) {
                    $value = $data['wcb2b_product_group_max:' . get_the_ID()];
                    if ('' != $value) {
                        $value = intval( $value );   
                    }
                    $product_group_max[get_the_ID()] = $value;
                }
            }

            // Save the group product visibility
            $object->update_meta_data( 'wcb2b_product_group_visibility', array_unique( $product_group_visibility ) );
            // Save the group hide prices
            $object->update_meta_data( 'wcb2b_product_group_hide_prices', array_unique( $product_group_hide_prices ) );
            // Save the group hide stocks
            $object->update_meta_data( 'wcb2b_product_group_hide_stocks', array_unique( $product_group_hide_stocks ) );
            // Save the group prices
            $object->update_meta_data( 'wcb2b_product_group_prices', $product_group_prices );
            // Save the group tier prices
            $object->update_meta_data( 'wcb2b_product_group_tier_prices', $product_group_tier_prices );
            // Save the group packages
            $object->update_meta_data( 'wcb2b_product_group_packages', $product_group_packages );
            // Save the group minimum quantity
            $object->update_meta_data( 'wcb2b_product_group_min', $product_group_min );
            // Save the group maximum quantity
            $object->update_meta_data( 'wcb2b_product_group_max', $product_group_max );
        }

        // Save barcode
        if ( isset( $data['wcb2b_barcode'] ) ) {
            $barcode = $data['wcb2b_barcode'];
            $object->update_meta_data( 'wcb2b_barcode', $barcode );
        }
        return $object;
    }

    /**
     * Map columns
     *
     * @param array $columns File columns
     * @return array
     */
    public function add_column_to_mapping_screen( $columns ) {
        $groups = wcb2b_get_groups();
        if ( $groups->have_posts() ) {
            while ( $groups->have_posts() ) {
                $groups->the_post();
                $product_visibility           = sprintf( __( 'Product visibility (%s)', 'woocommerce-b2b' ), get_the_title() );
                $hide_prices                  = sprintf( __( 'Hide prices (%s)', 'woocommerce-b2b' ), get_the_title() );
                $hide_stocks                  = sprintf( __( 'Hide stocks (%s)', 'woocommerce-b2b' ), get_the_title() );
                $regular_price                = sprintf( __( 'Regular price (%s)', 'woocommerce-b2b' ), get_the_title() );
                $sale_price                   = sprintf( __( 'Sale price (%s)', 'woocommerce-b2b' ), get_the_title() );
                $tier_prices                  = sprintf( __( 'Tier prices (%s)', 'woocommerce-b2b' ), get_the_title() );
                $packages                     = sprintf( __( 'Packages (%s)', 'woocommerce-b2b' ), get_the_title() );
                $min                          = sprintf( __( 'Minimum quantity (%s)', 'woocommerce-b2b' ), get_the_title() );
                $max                          = sprintf( __( 'Maximum quantity (%s)', 'woocommerce-b2b' ), get_the_title() );
                $columns[$product_visibility] = 'wcb2b_product_group_visibility:' . get_the_ID();
                $columns[$hide_prices]        = 'wcb2b_product_group_hide_prices:' . get_the_ID();
                $columns[$hide_stocks]        = 'wcb2b_product_group_hide_stocks:' . get_the_ID();
                $columns[$regular_price]      = 'wcb2b_product_group_prices:' . get_the_ID() . ':regular_price';
                $columns[$sale_price]         = 'wcb2b_product_group_prices:' . get_the_ID() . ':sale_price';
                $columns[$tier_prices]        = 'wcb2b_product_group_tier_prices:' . get_the_ID() . '';
                $columns[$packages]           = 'wcb2b_product_group_packages:' . get_the_ID() . '';
                $columns[$min]                = 'wcb2b_product_group_min:' . get_the_ID() . '';
                $columns[$max]                = 'wcb2b_product_group_max:' . get_the_ID() . '';
            } 
        }

        $barcode = __( 'Barcode', 'woocommerce-b2b' );
        $columns[$barcode] = 'wcb2b_barcode';
        return $columns;
    }

    /**
     * Add metaboxes for product categories to add groups visibility (new category)
     */
    public function add_taxonomy_visibility_metabox() {
        $context = 'new';
        $groups = wcb2b_get_groups();
        $visibility = false;
        include_once WCB2B_ABSPATH . 'includes/views/html-admin-product_taxonomies.php';
    }

    /**
     * Add metaboxes for product categories to add groups visibility (edit category)
     *
     * @param object $term Current taxonomy term
     */
    public function edit_taxonomy_visibility_metabox( $term ) {
        $context = 'edit';
        $groups = wcb2b_get_groups();
        $visibility = false;
        if ( metadata_exists( 'term', $term->term_id, 'wcb2b_group_visibility' ) ) {
            $visibility = get_term_meta( $term->term_id, 'wcb2b_group_visibility', true );
        }
        include_once WCB2B_ABSPATH . 'includes/views/html-admin-product_taxonomies.php';
    }

    /**
     * Save groups visibility when save product categories
     * 
     * @param int $term_id Current term ID
     * @param int $tt_id Current taxonomy ID
     * @param string $taxonomy Current taxonomy name
     */
    public function save_taxonomy_visibility_fields( $term_id, $tt_id, $taxonomy ) {
        if ( 'product_cat' === $taxonomy || 'product_brand' === $taxonomy ) {
            $visibility = false;
            if ( isset( $_POST['wcb2b_group_visibility'] ) ) {
                // Sanitize values
                $visibility = array_map( 'intval', array_filter( $_POST['wcb2b_group_visibility'] ) );
            }
            update_term_meta( $term_id, 'wcb2b_group_visibility', $visibility );
        }
    }

}

return new WCB2B_Admin_Products();