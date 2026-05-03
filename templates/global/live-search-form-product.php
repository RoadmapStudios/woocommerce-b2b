<?php
/**
 * WooCommerce B2B Live Search form single product result entry
 *
 * @version  3.2.0
 */
?>

<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wcb2b-livesearch-product">
    <div class="wcb2b-livesearch-product-image-wrapper">
        <?php printf( '%s', $product->get_image( array( 80, 80 ) ) ); ?>
    </div>
    <div class="wcb2b-livesearch-product-content-wrapper">
        <div class="wcb2b-livesearch-product-title">
            <a href="<?php printf( '%s', $product->get_permalink() ); ?>"><?php printf( '%s', $product->get_name() ); ?></a>
        </div>
        <div class="wcb2b-livesearch-product-sku"><?php printf( esc_html__( 'Sku: %s', 'woocommerce-b2b' ), $product->get_sku() ); ?></div>
        <div class="wcb2b-livesearch-product-price"><?php printf( '%s', $product->get_price_html() ); ?></div>
    </div>
    <div class="wcb2b-livesearch-product-buttons-wrapper">
        <a class="button wcb2b-livesearch-product-addtocart" href="<?php printf( '%s', $product->add_to_cart_url() ); ?>" <?php echo $data; ?>><?php esc_html_e( 'Add to cart', 'woocommerce-b2b' ); ?></a>
    </div>
</div>