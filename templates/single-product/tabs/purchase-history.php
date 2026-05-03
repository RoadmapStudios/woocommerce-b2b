<?php

/**
 * WooCommerce B2B Purchase history tab
 *
 * @version 3.2.5
 */

defined( 'ABSPATH' ) || exit;

?>

<?php
    if ( empty( $purchases ) ) {
        esc_html_e( 'It looks like you have never purchased this item before', 'woocommerce-b2b' );
        return;
    }
?>

<table class="table">
    <tr>
        <th><?php esc_html_e( 'Order', 'woocommerce-b2b' ); ?></th>
        <th><?php esc_html_e( 'Purchase date', 'woocommerce-b2b' ); ?></th>
        <th><?php esc_html_e( 'Quantity', 'woocommerce-b2b' ); ?></th>
    </tr>

    <?php foreach ( $purchases as $purchase ) : ?>
    <tr>
        <td><?php printf( '<a href="%s" target="_blank">#%s</a>', $purchase['url'], $purchase['id'] ); ?></td>
        <td><?php echo $purchase['date']; ?></td>
        <td>&times;<?php echo $purchase['quantity']; ?></td>
    </tr>
    <?php endforeach; ?>

</table>