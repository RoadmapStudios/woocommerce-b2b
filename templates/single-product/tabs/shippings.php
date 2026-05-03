<?php

/**
 * WooCommerce B2B Shippings tab
 *
 * @version 3.2.0
 */

defined( 'ABSPATH' ) || exit;

?>

<table class="table">
    <tr>
        <th><?php esc_html_e( 'Zone name', 'woocommerce-b2b' ); ?></th>
        <th><?php esc_html_e( 'Shipping method', 'woocommerce-b2b' ); ?></th>
        <th><?php esc_html_e( 'Cost', 'woocommerce-b2b' ); ?></th>
    </tr>

    <?php foreach ( $shippings as $shipping ) : $index = 0; ?>

        <?php foreach ( $shipping['methods'] as $method ) : $index++; ?>
        <tr>

            <?php if ( 1 == $index ) : ?>
            <td rowspan="<?php echo count( $shipping['methods'] ); ?>"><?php echo $shipping['name']; ?></td>
            <?php endif; ?>

            <td><?php echo $method['title']; ?></td>
            <td><?php echo $method['cost']; ?></td>
        </tr>
        <?php endforeach; ?>

    <?php endforeach; ?>

</table>