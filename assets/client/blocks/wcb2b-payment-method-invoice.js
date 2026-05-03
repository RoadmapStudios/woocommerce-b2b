/*!
 * WooCommerce B2B v3.3.7 (https://woocommerce-b2b.com/)
 * Copyright 2020 Code4Life.it
 */

( function() {
    var settings = window.wc.wcSettings.getSetting( 'wcb2b_invoice_gateway_data', {} );
    var label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Invoice payment', 'woocommerce-b2b' );
    var Content = () => {
        return window.wp.htmlEntities.decodeEntities( settings.description || '' );
    };
    var WCB2B_Invoice_Gateway = {
        name: 'wcb2b_invoice_gateway',
        label: label,
        content: Object( window.wp.element.createElement )( Content, null ),
        edit: Object( window.wp.element.createElement )( Content, null ),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
    };
    window.wc.wcBlocksRegistry.registerPaymentMethod( WCB2B_Invoice_Gateway );
} )();
