<?php

/**
 * Admin View: Notice - Update
 *
 * @version 3.0.3
 */

defined( 'ABSPATH' ) || exit;

$update_url = wp_nonce_url(
    add_query_arg( 'do_update_wcb2b', 'true', admin_url( 'tools.php?page=action-scheduler' ) ),
    'wcb2b_db_update',
    'wcb2b_db_update_nonce'
);

?>

<div id="message" class="notice notice-warning">
    <p>
        <strong><?php esc_html_e( 'WooCommerce B2B database update required. Before to update, please be sure to have a database recovery backup.', 'woocommerce-b2b' ); ?></strong>
    </p>
    <p>
        <a href="<?php echo esc_url( $update_url ); ?>" class="button-primary">
            <?php esc_html_e( 'Update WooCommerce B2B Database', 'woocommerce-b2b' ); ?>
        </a>
    </p>
</div>