<?php

/**
 * WooCommerce B2B Product barcode
 *
 * @version 3.2.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wcb2b-barcode"><?php printf( esc_html__( 'Barcode: %s', 'woocommerce-b2b' ), '<span>' . ($wcb2b_barcode ?: __( 'N/A', 'woocommerce-b2b' ) ) . '</span>' ); ?></div>