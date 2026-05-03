<?php

/**
 * WooCommerce B2B Admin settings page - Tools
 *
 * @version 3.3.3
 */

defined( 'ABSPATH' ) || exit;

?>

<fieldset>
    <h2><?php esc_html_e( 'Tools', 'woocommerce-b2b' ); ?></h2>
    <table class="wc_status_table wc_status_table--tools widefat" cellspacing="0">
        <tbody class="tools">
            <tr class="wcb2b_debug_mode">
                <th>
                    <strong class="name">
                        <?php esc_html_e( 'Debug mode [BETA]', 'woocommerce-b2b' ); ?>
                        <em class="wcb2b-notice error">
                            <span class="dashicons dashicons-warning"></span> 
                            <?php esc_html_e( 'This feature is experimental, enable it at your own risk!', 'woocommerce-b2b' ); ?>
                        </em>        
                    </strong>
                    <p class="description"><?php printf( esc_html__( 'When debug mode is enabled, all plugins except WooCommerce and WooCommerce B2B will be disabled for your current IP address (%s). Please take a look to plugin documentation for more informations before to proceed.', 'woocommerce-b2b' ), $_SERVER['REMOTE_ADDR'] ); ?></p>
                </th>
                <td class="run-tool">
                    <form method="post">
                        <input type="hidden" name="wcb2b_tools_action" value="wcb2b_form_debug_mode">
                        <input type="submit" class="button button-large" value="<?php echo get_option( 'wcb2b_debug', false ) ? esc_html__( 'Disable', 'woocommerce-b2b' ) : esc_html__( 'Enable', 'woocommerce-b2b' ); ?>">

                        <?php wp_nonce_field( 'wcb2b_tools', '_wcb2bnonce' ); ?>

                    </form>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>