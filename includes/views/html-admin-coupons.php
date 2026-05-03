<?php

/**
 * Admin View: Coupons
 *
 * @version 3.1.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wcb2b-card">
    <input type="checkbox" id="wcb2b-collapse_<?php echo $coupon_id; ?>" checked>
    <div class="wcb2b-card-title">
        <?php esc_html_e( 'WooCommerce B2B', 'woocommerce-b2b' ); ?>
        
        <label for="wcb2b-collapse_<?php echo $coupon_id; ?>"></label>
    </div>
    <div class="wcb2b-card-body">
        <div class="wcb2b-label"><?php esc_html_e( 'Groups restriction', 'woocommerce-b2b' ); ?></div>

        <?php
            woocommerce_wp_select( array(
                'id'                => '_wcb2b_coupon_group',
                'name'              => '_wcb2b_coupon_group[]', 
                'label'             => __( 'Allowed group', 'woocommerce-b2b' ),  
                'description'       => __( 'Allow coupon usage to customers assigned to selected groups. No groups selected means no restrictions.', 'woocommerce-b2b' ),
                'desc_tip'          => true,  
                'options'           => wp_list_pluck( wcb2b_get_groups()->posts, 'post_title', 'ID' ),
                'custom_attributes' => array( 'multiple' => 'multiple' )
            ) );
        ?>
    </div>
    <div class="wcb2b-card-body">
        <div class="wcb2b-label"><?php esc_html_e( 'Total spent restriction', 'woocommerce-b2b' ); ?></div>

        <?php
            woocommerce_wp_text_input( array(
                'id'                => '_wcb2b_coupon_total_spent',
                'name'              => '_wcb2b_coupon_total_spent', 
                'type'              => 'number',
                'label'             => __( 'Total spent', 'woocommerce-b2b' ),  
                'description'       => __( 'Allow coupon usage to customers who have spent at least the amount indicated.', 'woocommerce-b2b' ),
                'desc_tip'          => true
            ) );
        ?>
    </div>
</div>