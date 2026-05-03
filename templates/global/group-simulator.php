<?php
/**
 * WooCommerce B2B Live Search form
 *
 * @version  4.0.3
 */
?>

<?php defined( 'ABSPATH' ) || exit; ?>

<div id="wcb2b-switch-group-wrapper">
    <div>

        <?php
            printf( '<strong>%s</strong> %s',
                esc_html__( 'Original customer group:', 'woocommerce-b2b' ),
                get_the_title( $original_customer_group_id )
            );
        ?>

        <br />

        <?php
            printf( '<strong>%s</strong> %s',
                esc_html__( 'Current customer group:', 'woocommerce-b2b' ),
                get_the_title( $current_customer_group_id )
            );
        ?>

    </div>
    <form method="post">
        <select name="wcb2b_group" class="wcb2b-switch-group-selector">

            <?php
                if ( $groups->have_posts() ) {
                    while ( $groups->have_posts() ) {
                        $groups->the_post();

                        printf(
                            '<option value="%1$s" %2$s>%3$s</option>',
                            get_the_ID(),
                            ( $current_customer_group_id == get_the_ID() ? ' selected="selected"' : '' ),
                            get_the_title()
                        );
                    }
                }
                wp_reset_postdata();
            ?>

        </select>
        <button type="submit"><?php esc_html_e( 'Change group', 'woocommerce-b2b' ); ?></button>
    </form>
</div>
