<?php
/**
 * WooCommerce B2B Live Search form
 *
 * @version  3.0.3
 */
?>

<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wcb2b-livesearch <?php echo $fixed ? 'wcb2b-livesearch-fixed' : false; ?>">
    <form method="post">
        <div class="form-group">
            <input type="search" name="wcb2b_livesearch" class="form-control" placeholder="<?php esc_html_e( 'Search for product', 'woocommerce-b2b' ); ?>" value="">
        </div>
    </form>
    <div class="wcb2b-livesearch-products"></div>
</div>