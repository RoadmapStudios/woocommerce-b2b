<?php

/**
 * WooCommerce B2B Coupons in cart
 *
 * @version 4.0.3
 */

defined( 'ABSPATH' ) || exit;

?>

<form id="wcb2b-coupons-form" method="post" action="<?php echo wc_get_cart_url(); ?>">
    <h2><?php esc_html_e( 'Available Coupons', 'woocommerce-b2b' ); ?></h2>
    <div id="wcb2b-coupons-wrapper">

        <?php foreach ( $coupons as $coupon_id ) : $coupon = new WC_Coupon( $coupon_id ); ?>            
        <div class="wcb2b-coupon">
            <input type="checkbox" id="<?php echo $coupon->get_code(); ?>" name="coupon_code" value="<?php echo $coupon->get_code(); ?>" <?php echo WC()->cart->has_discount( $coupon->get_code() ) ? 'checked' : false ; ?> onChange="this.form.submit()">
            <label for="<?php echo $coupon->get_code(); ?>" data-text="<?php esc_html_e( 'Click to apply this coupon', 'woocommerce-b2b' ); ?>"></label>
            <div class="wcb2b-coupon-content">
                <div class="wcb2b-coupon-code"><?php echo strtoupper( $coupon->get_code() ); ?></div>
                <div class="wcb2b-coupon-description"><?php echo $coupon->get_description(); ?></div>
            </div>
        </div>        
        <?php endforeach; ?>

    </div>
    <input type="hidden" name="apply_coupon" value="1">
</form>
