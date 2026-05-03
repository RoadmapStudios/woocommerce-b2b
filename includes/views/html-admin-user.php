<?php

/**
 * WooCommerce B2B Admin Users view
 *
 * @version 3.3.5
 */

defined( 'ABSPATH' ) || exit;

?>

<?php if ( wcb2b_has_role( $user->ID, 'customer' ) ) : ?>
<div class="wcb2b-card">
    <input type="checkbox" id="wcb2b-collapse_<?php echo $user->ID; ?>" checked>
    <div class="wcb2b-card-title">
        <?php esc_html_e( 'WooCommerce B2B', 'woocommerce-b2b' ); ?>
            
        <label for="wcb2b-collapse_<?php echo $user->ID; ?>"></label> 
    </div>
    <div class="wcb2b-card-body">

        <?php $groups = wcb2b_get_groups(); ?>

        <div class="wcb2b-label"><?php esc_html_e( 'User group', 'woocommerce-b2b' ); ?></div>
        <table class="form-table">
            <tr>
                <th><label for="wcb2b_group"><?php esc_html_e( 'Group', 'woocommerce-b2b' ); ?></label></th>
                <td>
                    <select name="wcb2b_group" id="wcb2b_group" class="regular-text">
                        <option value=""><?php esc_html_e( '-- None --', 'woocommerce-b2b' ); ?></option>
                        <?php if ( $groups->have_posts() ) : ?>
                            <?php while ( $groups->have_posts() ) : $groups->the_post(); ?>
                            <option value="<?php the_ID(); ?>" <?php selected( get_the_author_meta( 'wcb2b_group', $user->ID ), get_the_ID() ); ?> ><?php the_title(); ?></option>
                            <?php endwhile; ?>
                        <?php endif; wp_reset_postdata(); ?>
                    </select>
                    <br />
                    <em class="wcb2b-help"><?php esc_html_e( 'Please select user group to apply discounts.', 'woocommerce-b2b' ); ?></em>
                </td>
            </tr>
        </table>
            
        <?php $statuses = WCB2B_Configuration::get_customer_statuses(); ?>

        <br />
        <div class="wcb2b-label"><?php esc_html_e( 'User status', 'woocommerce-b2b' ); ?></div>
        <table class="form-table">
            <tr>
                <th><label for="wcb2b_status"><?php esc_html_e( 'Status', 'woocommerce-b2b' ); ?></label></th>
                <td>
                    <select name="wcb2b_status" id="wcb2b_status" class="regular-text">
                        <?php foreach ( $statuses as $key => $status ) : ?>
                        <option value="<?php echo $key; ?>" <?php selected( get_the_author_meta( 'wcb2b_status', $user->ID ), $key ); ?> ><?php echo $status; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <br />
                    <em class="wcb2b-help"><?php esc_html_e( 'Please select user status to approve registration and enable purchases.', 'woocommerce-b2b' ); ?></em>
                </td>
            </tr>
        </table>
        <br />
        <div class="wcb2b-label"><?php esc_html_e( 'Limits', 'woocommerce-b2b' ); ?></div>
        <table class="form-table">
            <tr>
                <th><label for="wcb2b_unpaid_limit"><?php esc_html_e( 'Unpaid amount limit', 'woocommerce-b2b' ); ?></label></th>
                <td>
                    <input type="number" name="wcb2b_unpaid_limit" id="wcb2b_unpaid_limit" class="regular-text" value="<?php echo get_user_meta( $user->ID, 'wcb2b_unpaid_limit', true ); ?>" min="0">
                    <br />
                    <em class="wcb2b-help"><?php esc_html_e( 'Prevents this customer from placing further orders if they have reached a unpaid total orders amount equal to or greater than the set limit. Set empty (or 0) to give no limit.', 'woocommerce-b2b' ); ?></em>
                </td>
            </tr>
        </table>
        <br />
        <div class="wcb2b-label"><?php esc_html_e( 'Statistics', 'woocommerce-b2b' ); ?></div>
        <table class="form-table">
            <tr>
                <th><label for=""><?php esc_html_e( 'Total spent', 'woocommerce-b2b' ); ?></label></th>
                <td><?php echo wc_price( wc_get_customer_total_spent( $user->ID ) ); ?></td>
            </tr>
            <tr>
                <th><label for=""><?php esc_html_e( 'Total orders', 'woocommerce-b2b' ); ?></label></th>
                <td><?php echo wc_get_customer_order_count( $user->ID ); ?></td>
            </tr>
            <tr>
                <th><label for=""><?php esc_html_e( 'Unpaid amount', 'woocommerce-b2b' ); ?></label></th>
                <td><?php echo wc_price( wcb2b_get_total_unpaid( $user->ID ) ); ?></td>
            </tr>
        </table>
        <br />
        <div class="wcb2b-label"><?php esc_html_e( 'Additional info', 'woocommerce-b2b' ); ?></div>
        <table class="form-table">
            <tr>
                <th><label for=""><?php esc_html_e( 'Business certificate', 'woocommerce-b2b' ); ?></label></th>
                <td>

                    <?php
                        if ( $file = get_user_meta( $user->ID, 'wcb2b_business_certificate', true ) ) {
                            printf( '<a href="%s" target="_blank">%s</a>',
                                wp_get_attachment_url( $file ),
                                wp_get_attachment_url( $file )
                            );
                        } else {
                            printf( '<em>%s</em>', esc_html__( 'Not uploaded yet', 'woocommerce-b2b' ) );
                        }
                    ?>

                </td>
            </tr>
            <tr>
                <th><label for=""><?php esc_html_e( 'Valid VAT number', 'woocommerce-b2b' ); ?></label></th>
                <td>
                    <input type="checkbox" name="wcb2b_vies_validated_user" id="wcb2b_vies_validated_user" value="1" <?php checked( get_the_author_meta( 'wcb2b_vies_validated_user', $user->ID ), true ); ?> />
                    <br />
                    <em class="wcb2b-help"><?php esc_html_e( 'Indicates whether customer\'s VAT number has been verified.', 'woocommerce-b2b' ); ?></em>
                </td>
            </tr>
            <tr>
                <th><label for=""><?php esc_html_e( 'VIES response', 'woocommerce-b2b' ); ?></label></th>
                <td>

                    <?php echo get_user_meta( $user->ID, 'wcb2b_vies_response', true ); ?>

                </td>
            </tr>
        </table>
    </div>
</div>
<?php endif; ?>