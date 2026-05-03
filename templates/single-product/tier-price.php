<?php

/**
 * WooCommerce B2B Product tier prices
 *
 * @version 3.3.6
 */

defined( 'ABSPATH' ) || exit;

?>

<ul class="wcb2b-tier_prices" data-interactive="<?php echo apply_filters( 'wcb2b_interactive_tier_prices', true ); ?>">

    <?php
        foreach ( $tier_prices as $tier_quantity => $tier_price ) {
            printf( '<li data-quantity="%s">' . esc_html__( 'Buy %s for %s each and save %s', 'woocommerce-b2b' ) . '</li>',
                $tier_quantity,
                '<strong>' . $tier_quantity . '</strong>',
                '<strong>' . wcb2b_display_tier_price( $tier_price ) . '</strong>',
                '<strong>' . number_format( ( ( wcb2b_adjust_price_tax( $price ) - wcb2b_adjust_price_tax( $tier_price ) ) / wcb2b_adjust_price_tax( $price ) ) * 100, 2, wc_get_price_decimal_separator(), '' ) . '%</strong>'
            );
        }
    ?>

</ul>
<br />