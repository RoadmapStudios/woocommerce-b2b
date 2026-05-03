<?php

/**
 * WooCommerce B2B Admin Post view
 *
 * @version 3.0.2
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wcb2b-card">
    <div class="wcb2b-card-title"><?php esc_html_e( 'WooCommerce B2B', 'woocommerce-b2b' ); ?></div>
    <div class="wcb2b-card-body">
        <div class="wcb2b-label"><?php esc_html_e( 'Group access', 'woocommerce-b2b' ); ?></div>
        <br />

        <?php if ( in_array( $post->ID, wcb2b_get_always_visible_pages() ) ) : ?>
            
            <em class="wcb2b-help"><?php esc_html_e( 'This is an always visible page. To unblock, please check documentation hooks.', 'woocommerce-b2b' ); ?></em>

        <?php else : ?>
        
            <?php
                if ( $groups->post_count ) {
                    foreach ( $groups->posts as $group ) {
                        $checked = checked( true, true, false );
                        if ( $visibility !== false ) {
                            $checked = checked( in_array( $group->ID, (array)$visibility ), true, false );
                        }
                        printf( '<p><input type="checkbox" name="wcb2b_group_visibility[]" id="wcb2b_group_visibility_%s" value="%s" %s>%s</p>',
                            $group->ID,
                            $group->ID,
                            $checked,
                            get_the_title( $group->ID )
                        );
                    }
                }
            ?>

            <em class="wcb2b-help"><?php esc_html_e( 'Select the groups you want to be able to view this page', 'woocommerce-b2b' ); ?></em>

        <?php endif; ?>

    </div>
</div>