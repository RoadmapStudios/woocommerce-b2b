<?php

/**
 * WooCommerce B2B Admin Product data tab
 *
 * @version 3.3.2
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wcb2b_options_group options_group show_if_simple show_if_variable hide_if_external">
    <div class="wcb2b-card">
        <input type="checkbox" id="wcb2b-collapse_<?php echo $post_id; ?>" checked>
        <div class="wcb2b-card-title">
            <?php esc_html_e( 'WooCommerce B2B', 'woocommerce-b2b' ); ?>
                
            <label for="wcb2b-collapse_<?php echo $post_id; ?>"></label>
        </div>
        <div class="wcb2b-card-body">
            <section class="wcb2b-section">
                <div class="wcb2b-heading"><?php esc_html_e( 'Inventory', 'woocommerce-b2b' ); ?></div>
                <div class="wcb2b-form">

                    <?php
                        woocommerce_wp_text_input( array(
                            'id'            => '_wcb2b_barcode',
                            'type'          => 'text',
                            'name'          => 'wcb2b_barcode[' . $post_id . ']',
                            'label'         => __( 'Barcode', 'woocommerce-b2b' ),
                            'description'   => __( 'Product barcode number (EAN/IAN/GTIN)', 'woocommerce-b2b' ),
                            'desc_tip'      => false,
                            'value'         => $barcode,
                            'wrapper_class' => 'wcb2b-form-field',
                            'required'      => false
                        ) );
                    ?>

                </div>
            </section>
            
            <?php if ( $groups->post_count ) : ?>
            <section class="wcb2b-section">
                <div class="wcb2b-heading"><?php esc_html_e( 'Groups options', 'woocommerce-b2b' ); ?></div>

                <?php foreach ( $groups->posts as $group ) : $groupname = get_the_title( $group->ID ); ?>
                <div class="wcb2b-card-inner">
                    <input type="checkbox" id="wcb2b-collapse_<?php printf( '%d-%d', $post_id, $group->ID ); ?>">
                    <div class="wcb2b-card-title wcb2b-card-title-noicon">
                        <?php printf( esc_html__( 'Edit %s group options', 'woocommerce-b2b' ), $groupname ); ?>
                            
                        <label for="wcb2b-collapse_<?php printf( '%d-%d', $post_id, $group->ID ); ?>"></label>
                    </div>
                    <div class="wcb2b-card-body">
                        <div class="wcb2b-heading"><?php esc_html_e( 'Group visibility', 'woocommerce-b2b' ); ?></div>
                        <div class="wcb2b-form">
                            
                            <?php
                                $args = array(
                                    'id'            => '_wcb2b_product_group_visibility_' . $group->ID,
                                    'value'         => 'no',
                                    'cbvalue'       => 'yes',
                                    'name'          => 'wcb2b_product_group_visibility[' . $post_id . '][' . $group->ID . ']',
                                    'wrapper_class' => 'wcb2b-form-field',
                                    'label'         => sprintf( __( 'Product visibility for %s group', 'woocommerce-b2b' ), $groupname ),
                                    'description'   => esc_html__( 'Hide product', 'woocommerce-b2b' )
                                );
                                if ( is_array( $product_group_visibility ) && in_array( $group->ID, $product_group_visibility ) ) {
                                    $args['value'] = 'yes';
                                }
                                if ( get_post_meta( $group->ID, 'wcb2b_product_group_visibility', true ) ) {
                                    $args['value'] = 'yes';
                                    $args['custom_attributes'] = array(
                                        'disabled' => true
                                    );
                                }
                                woocommerce_wp_checkbox( $args );
                            ?>

                        </div>
                    </div>
                    <div class="wcb2b-card-body">
                        <div class="wcb2b-heading"><?php esc_html_e( 'Group prices', 'woocommerce-b2b' ); ?></div>
                        <div class="wcb2b-form">
                            
                            <?php
                                $args = array(
                                    'id'            => '_wcb2b_product_group_hide_prices_' . $group->ID,
                                    'value'         => 'no',
                                    'cbvalue'       => 'yes',
                                    'name'          => 'wcb2b_product_group_hide_prices[' . $post_id . '][' . $group->ID . ']',
                                    'wrapper_class' => 'wcb2b-form-field',
                                    'label'         => sprintf( __( 'Hide price for %s group', 'woocommerce-b2b' ), $groupname ),
                                    'description'   => esc_html__( 'Hide product price', 'woocommerce-b2b' )
                                );
                                if ( is_array( $product_group_hide_prices ) && in_array( $group->ID, $product_group_hide_prices ) ) {
                                    $args['value'] = 'yes';
                                }
                                if ( get_post_meta( $group->ID, 'wcb2b_group_hide_prices', true ) ) {
                                    $args['value'] = 'yes';
                                    $args['custom_attributes'] = array(
                                        'disabled' => true
                                    );
                                }
                                woocommerce_wp_checkbox( $args );
                            ?>

                        </div>
                        <div class="wcb2b-form">

                            <?php
                                $args = array(
                                    'id'            => '_wcb2b_product_group_hide_stocks_' . $group->ID,
                                    'value'         => 'no',
                                    'cbvalue'       => 'yes',
                                    'name'          => 'wcb2b_product_group_hide_stocks[' . $post_id . '][' . $group->ID . ']',
                                    'wrapper_class' => 'wcb2b-form-field',
                                    'label'         => sprintf( esc_html__( 'Hide stock for %s group', 'woocommerce-b2b' ), $groupname ),
                                    'description'   => esc_html__( 'Hide product stock', 'woocommerce-b2b' )
                                );
                                if ( is_array( $product_group_hide_stocks ) && in_array( $group->ID, $product_group_hide_stocks ) ) {
                                    $args['value'] = 'yes';
                                }
                                if ( 'yes' != get_post_meta( $post_id, '_manage_stock', true ) ) {
                                    $args['custom_attributes'] = array(
                                        'disabled' => true
                                    );
                                }
                                woocommerce_wp_checkbox( $args );
                            ?>

                        </div>
                        <div class="wcb2b-form">

                            <?php
                                // Retrieve product prices by group
                                $regular_price = '';
                                $sale_price = '';
                                if ( is_array( $product_group_prices ) && array_key_exists( $group->ID, $product_group_prices ) ) {
                                    if ( is_array( $product_group_prices[$group->ID] ) ) {
                                        if ( array_key_exists( 'regular_price', $product_group_prices[$group->ID] ) ) {
                                            if ( $regular_price = $product_group_prices[$group->ID]['regular_price'] ) {
                                                $regular_price = number_format(
                                                    floatval( $regular_price ),
                                                    wc_get_price_decimals(),
                                                    wc_get_price_decimal_separator(),
                                                    ''
                                                );
                                            }
                                        }
                                        if ( array_key_exists( 'sale_price', $product_group_prices[$group->ID] ) ) {
                                            if ( $sale_price = $product_group_prices[$group->ID]['sale_price'] ) {
                                                $sale_price = number_format(
                                                    floatval( $sale_price ),
                                                    wc_get_price_decimals(),
                                                    wc_get_price_decimal_separator(),
                                                    ''
                                                );
                                            }
                                        }
                                    }
                                }

                                // Build regular price field
                                woocommerce_wp_text_input( array(
                                    'id'            => '_wcb2b_product_group_regular_price_' . $group->ID,
                                    'type'          => 'text',
                                    'class'         => 'short wc_input_price',
                                    'name'          => 'wcb2b_product_group_prices[' . $post_id . '][' . $group->ID . '][regular_price]',
                                    'label'         => sprintf( esc_html__( 'Regular price for %s group', 'woocommerce-b2b' ), $groupname ),
                                    'value'         => $regular_price,
                                    'required'      => true,
                                    'wrapper_class' => 'wcb2b-form-field'
                                ) );

                                // Build sale price field
                                woocommerce_wp_text_input( array(
                                    'id'            => '_wcb2b_product_group_sale_price_' . $group->ID,
                                    'type'          => 'text',
                                    'class'         => 'short wc_input_price',
                                    'name'          => 'wcb2b_product_group_prices[' . $post_id . '][' . $group->ID . '][sale_price]',
                                    'label'         => sprintf( esc_html__( 'Sale price for %s group', 'woocommerce-b2b' ), $groupname ),
                                    'value'         => $sale_price,
                                    'required'      => true,
                                    'wrapper_class' => 'wcb2b-form-field',
                                ) );
                            ?>

                            <p class="wcb2b-form-button">
                                <a class="button" href="" data-toggle="wcb2b-product_group_tier_prices-add" data-group="<?php echo $group->ID; ?>" data-post="<?php echo $post_id; ?>" data-label="<?php esc_html_e( 'Remove', 'woocommerce-b2b' ); ?>"><?php esc_html_e( 'Add tier price', 'woocommerce-b2b' ); ?></a>
                            </p>
                        </div>
                        <div class="wcb2b-panel">
                            <div class="wcb2b-label"><?php printf( esc_html__( 'Tier prices for %s group', 'woocommerce-b2b' ), $groupname ); ?></div>
                            <div class="wcb2b-panel-content">
                                <table>
                                    <thead>
                                        <tr>
                                            <th width="40%"><?php esc_html_e( 'Min quantity', 'woocommerce-b2b' ); ?> <sup>*</sup></th>
                                            <th width="40%"><?php esc_html_e( 'Final discounted price', 'woocommerce-b2b' ); ?> <sup>*</sup></th>
                                            <th width="20%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                        <?php
                                            if ( isset( $product_group_tier_prices[$group->ID] ) ) {
                                                foreach ( $product_group_tier_prices[$group->ID] as $quantity => $price ) {
                                                    if ( $price ) {
                                                        $price = number_format(
                                                            floatval( $price ),
                                                            wc_get_price_decimals(),
                                                            wc_get_price_decimal_separator(),
                                                            ''
                                                        );
                                                    }
                                                    printf( '<tr>%s%s%s</tr>',
                                                        sprintf( '<td><input type="number" name="%s" value="%s" class="short" min="0" step="1" required></td>',
                                                            'wcb2b_product_group_tier_prices[' . $post_id . '][' . $group->ID . '][quantity][]',
                                                            $quantity
                                                        ),
                                                        sprintf( '<td><input type="text" name="%s" value="%s" class="wc_input_price" required></td>',
                                                            'wcb2b_product_group_tier_prices[' . $post_id . '][' . $group->ID . '][price][]',
                                                            $price
                                                        ),
                                                        sprintf( '<td><a class="%s" href="">%s</a></td>',
                                                            'button-link-delete',
                                                            esc_html__( 'Remove', 'woocommerce-b2b' )
                                                        )
                                                    );
                                                }
                                            }
                                        ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="wcb2b-card-body">
                        <div class="wcb2b-heading"><?php esc_html_e( 'Group min/max quantity and packages', 'woocommerce-b2b' ); ?></div>
                        <div class="wcb2b-form wcb2b-form-inline">
                        
                            <?php
                                $args = array(
                                    'id'            => '_wcb2b_product_group_packages_' . $group->ID,
                                    'type'          => 'number',
                                    'name'          => 'wcb2b_product_group_packages[' . $post_id . '][' . $group->ID . ']',
                                    'label'         => __( 'Packages', 'woocommerce-b2b' ),
                                    'description'   => __( 'Force customers to purchase product by pack increment. Insert how much products are in every pack. Set to 0 to disable', 'woocommerce-b2b' ),
                                    'desc_tip'      => true,
                                    'wrapper_class' => 'wcb2b-form-field',
                                    'required'      => true,
                                    'value'         => 0,
                                    'custom_attributes'     => array(
                                        'min'   => 0
                                    )
                                );
                                if ( is_array( $product_group_packages ) && array_key_exists( $group->ID, $product_group_packages ) ) {
                                    $step = $product_group_packages[$group->ID];
                                    $args['value'] = intval( $step );
                                }
                                woocommerce_wp_text_input( $args );
                            ?>

                        </div>
                        <div class="wcb2b-form wcb2b-form-inline">
                            
                            <?php
                                $args = array(
                                    'id'            => '_wcb2b_product_group_min_' . $group->ID,
                                    'type'          => 'number',
                                    'name'          => 'wcb2b_product_group_min[' . $post_id . '][' . $group->ID . ']',
                                    'label'         => __( 'Minimum quantity', 'woocommerce-b2b' ),
                                    'description'   => __( 'Force customers to purchase minimum quantity of this product. Set to 0 to disable', 'woocommerce-b2b' ),
                                    'desc_tip'      => true,
                                    'wrapper_class' => 'wcb2b-form-field',
                                    'required'      => true,
                                    'value'         => 0,
                                    'custom_attributes'     => array(
                                        'min'   => 0
                                    )
                                );
                                if ( is_array( $product_group_min ) && array_key_exists( $group->ID, $product_group_min ) ) {
                                    $min = $product_group_min[$group->ID];
                                    $args['value'] = intval( $min );
                                }
                                woocommerce_wp_text_input( $args );
                            ?>

                        </div>
                        <div class="wcb2b-form wcb2b-form-inline">
                            
                            <?php
                                $args = array(
                                    'id'            => '_wcb2b_product_group_max_' . $group->ID,
                                    'type'          => 'number',
                                    'name'          => 'wcb2b_product_group_max[' . $post_id . '][' . $group->ID . ']',
                                    'label'         => __( 'Maximum quantity', 'woocommerce-b2b' ),
                                    'description'   => __( 'Force customers to purchase maximum quantity of this product. Set to 0 to disable', 'woocommerce-b2b' ),
                                    'desc_tip'      => true,
                                    'wrapper_class' => 'wcb2b-form-field',
                                    'required'      => true,
                                    'value'         => 0,
                                    'custom_attributes'     => array(
                                        'min'   => 0
                                    )
                                );
                                if ( is_array( $product_group_max ) && array_key_exists( $group->ID, $product_group_max ) ) {
                                    $max = $product_group_max[$group->ID];
                                    $args['value'] = intval( $max );
                                }
                                woocommerce_wp_text_input( $args );
                            ?>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </section>
            <?php endif; ?>
        
        </div>
    </div>
</div>