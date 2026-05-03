<?php

/**
 * WooCommerce B2B My account saved carts list
 *
 * @version 3.2.6
 */

defined( 'ABSPATH' ) || exit;

?>

<?php if ( ! empty( $carts ) ) : ?>

<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
    <thead>
        <tr>
            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number">
                <span class="nobr"><?php esc_html_e( 'ID', 'woocommerce-b2b' ); ?></span>
            </th>
            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date">
                <span class="nobr"><?php esc_html_e( 'Date', 'woocommerce-b2b' ); ?></span>
            </th>
            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-items">
                <span class="nobr"><?php esc_html_e( 'Items', 'woocommerce-b2b' ); ?></span>
            </th>
            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions">
                <span class="nobr"><?php esc_html_e( 'Actions', 'woocommerce-b2b' ); ?></span>
            </th>
        </tr>
    </thead>
    <tbody>

        <?php foreach ( $carts as $value ) : ?>

            <?php
                $entry = get_user_meta( get_current_user_id(), 'wcb2b_cart_' . $value, true );
                $date = '-';
                if ( $datetime = $entry['datetime'] ?? false ) {
                    $date = date_i18n( 'd-m-Y', DateTime::createFromFormat( 'YmdHis', $datetime )->format( 'U' ) );
                }
            ?>
            
            <tr class="woocommerce-orders-table__row order">
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-number" data-title="<?php esc_html_e( 'ID', 'woocommerce-b2b' ); ?>"><?php echo $value; ?></td>
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-date" data-title="<?php esc_html_e( 'Date', 'woocommerce-b2b' ); ?>"><?php echo $date; ?></td>
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-items" data-title="<?php esc_html_e( 'Items', 'woocommerce-b2b' ); ?>"><?php echo count( $entry['content'] ?? array() ); ?></td>
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-actions" data-title="<?php esc_html_e( 'Actions', 'woocommerce-b2b' ); ?>">
                    <form method="post">
                        <button type="submit" class="woocommerce-button wp-element-button button" name="wcb2b_cart_restore" value="<?php echo $value; ?>"><?php esc_html_e( 'Restore', 'woocommerce-b2b' ); ?></button>
                        <button type="submit" class="woocommerce-button wp-element-button button" name="wcb2b_cart_delete" value="<?php echo $value; ?>"><?php esc_html_e( 'Delete', 'woocommerce-b2b' ); ?></button>

                        <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>

                    </form>
                </td>
            </tr>
            
        <?php endforeach; ?>

    </tbody>
</table>

<?php else : ?>

<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">

    <?php esc_html_e( 'No saved carts available yet.', 'woocommerce-b2b' ); ?>

</div>

<?php endif; ?>