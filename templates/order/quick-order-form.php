<?php
/**
 * WooCommerce B2B Quick order form
 *
 * @version  3.2.0
 */
?>

<?php defined( 'ABSPATH' ) || exit; ?>

<form method="post" enctype="multipart/form-data">

    <?php wp_nonce_field( 'quick-order', 'quick-order-nonce' ); ?>

    <p class="form-row form-row-wide" id="wcb2b-quick-order-csv">
        <label for="wcb2b-quick-order-csv"></label>
        <span class="woocommerce-input-wrapper">
            <input type="file" name="wcb2b-quick-order-csv" id="wcb2b-quick-order-csv" class="wcb2b-quick-order input-text" accept=".csv">
        </span>
    </p>
    <button type="submit" class="button"><?php esc_html_e( 'Process now', 'woocommerce-b2b' ); ?></button>

    <?php if ( $wcb2b_quick_order_processed ) : ?>
    <a class="button alt" href="<?php echo wc_get_checkout_url(); ?>" rel="nowfollow"><?php esc_html_e( 'Proceed to checkout', 'woocommerce-b2b' ); ?></a>
    <?php endif; ?>

</form>