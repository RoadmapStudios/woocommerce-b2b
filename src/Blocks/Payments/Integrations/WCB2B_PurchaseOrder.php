<?php

/**
 * WooCommerce B2B Purchase order Gateway Blocks
 *
 * @version 3.3.7
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Invoice payment method integration
 */
final class WCB2B_PurchaseOrder_Gateway_Blocks extends AbstractPaymentMethodType
{

    /**
     * Payment method name/id/slug matches id in core.
     *
     * @var string
     */
    protected $name = 'wcb2b_purchaseorder_gateway';

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option( "woocommerce_{$this->name}_settings", array() );
    }

    /**
     * Returns an array of script handles to enqueue for this payment method in
     * the frontend context
     *
     * @return string[]
     */
    public function get_payment_method_script_handles() {
        wp_register_script(
            'wcb2b_purchaseorder_gateway-blocks-integration',
            WCB2B_PLUGIN_URI . '/assets/client/blocks/wcb2b-payment-method-purchaseorder.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ),
            null,
            true
        );
        if ( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'wcb2b_purchaseorder_gateway-blocks-integration' );
            
        }
        return array( 'wcb2b_purchaseorder_gateway-blocks-integration' );
    }

    /**
     * An array of key, value pairs of data made available to payment methods
     * client side.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title' => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
        );
    }

}
