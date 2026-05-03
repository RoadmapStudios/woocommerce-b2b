<?php

/**
 * WooCommerce B2B Admin settings page - General
 *
 * @version 3.0.3
 */

defined( 'ABSPATH' ) || exit;

?>

<fieldset>

    <?php WC_Admin_Settings::output_fields( $this->get_settings() ); ?>
    
    <br /><br />
    <p class="submit">
        <button name="save" class="button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce-b2b' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce-b2b' ); ?></button>
    </p>
</fieldset>