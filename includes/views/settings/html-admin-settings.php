<?php

/**
 * WooCommerce B2B Admin settings page
 *
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;

?>

<div id="wcb2b-settings">
    <div class="wcb2b-card">
        <div class="wcb2b-card-body">
            <p><img src="<?php echo WCB2B_PLUGIN_URI . 'assets/images/logo.png'; ?>" height="80px"></p>
            <ul class="list-inline">

                <?php
                    printf( '<li><a href="%s" %s>%s</a></li>',
                        admin_url( 'admin.php?page=wc-settings&tab=' . $this->id ),
                        ( empty( $current_section ) ? 'class="active"' : false ),
                        esc_html__( 'Settings', 'woocommerce-b2b' )
                    );
                    printf( '<li><a href="%s" %s>%s</a></li>',
                        admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=status' ),
                        ( 'status' == $current_section ? 'class="active"' : false ),
                        esc_html__( 'Status', 'woocommerce-b2b' )
                    );
                    printf( '<li><a href="%s" %s>%s</a></li>',
                        admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=tools' ),
                        ( 'tools' == $current_section ? 'class="active"' : false ),
                        esc_html__( 'Tools', 'woocommerce-b2b' )
                    );

                    $links = WCB2B()->get_links();
                    printf( '<li><a href="%s" target="_blank">%s</a></li>', $links['changelog'], esc_html__( 'Changelog', 'woocommerce-b2b' ) );
                    printf( '<li><a href="%s" target="_blank">%s</a></li>', $links['docs'], esc_html__( 'Docs', 'woocommerce-b2b' ) );
                    printf( '<li><a href="%s" target="_blank">%s</a></li>', $links['faq'], esc_html__( 'Faq', 'woocommerce-b2b' ) );
                    printf( '<li><a href="%s" target="_blank">%s</a></li>', $links['blog'], esc_html__( 'Guide & Snippets', 'woocommerce-b2b' ) );
                    printf( '<li><a href="%s" target="_blank">%s</a></li>', $links['support'], esc_html__( 'Support', 'woocommerce-b2b' ) );
                ?>

            </ul>
            <hr />

            <?php include sprintf( WCB2B_ABSPATH . 'includes/views/settings/%s.php', $page ); ?>
    
        </div>
    </div>
</div>