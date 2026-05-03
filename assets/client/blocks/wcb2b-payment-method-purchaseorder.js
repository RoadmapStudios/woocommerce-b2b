/*!
 * WooCommerce B2B v4.0.1 (https://woocommerce-b2b.com/)
 * Copyright 2020 Code4Life.it
 */

( function() {
    var settings = window.wc.wcSettings.getSetting( 'wcb2b_purchaseorder_gateway_data', {} );
    var label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Purchase order', 'woocommerce-b2b' );
    
    var useEffect = window.wp.element.useEffect;
    var useState = window.wp.element.useState;

    const Content = ( props ) => {
        const [ wcb2bPurchaseorderNumber, setWcb2bPurchaseorderNumber ] = useState('');
        const { eventRegistration, emitResponse } = props;
        const { onPaymentSetup } = eventRegistration;
        useEffect( () => {
            const unsubscribe = onPaymentSetup( async () => {
                // Here we can do any processing we need, and then emit a response.
                // For example, we might validate a custom field, or perform an AJAX request, and then emit a response indicating it is valid or not.
                const wcb2b_purchaseorder_number = wcb2bPurchaseorderNumber;
                const customDataIsValid = !! wcb2b_purchaseorder_number.length;
                if ( customDataIsValid ) {
                    return {
                        type: emitResponse.responseTypes.SUCCESS,
                        meta: {
                            paymentMethodData: {
                                wcb2b_purchaseorder_number,
                            },
                        },
                    };
                }
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: window.wp.i18n.__( 'There was an error', 'woocommerce-b2b' ),
                };
            } );
            // Unsubscribes when this component is unmounted.
            return () => {
                unsubscribe();
            };
        }, [
            emitResponse.responseTypes.ERROR,
            emitResponse.responseTypes.SUCCESS,
            onPaymentSetup,
            wcb2bPurchaseorderNumber
        ] );
        return React.createElement( 'div', null,
            React.createElement( 'p', null, window.wp.htmlEntities.decodeEntities( settings.description || '' ) ),
            React.createElement( 'div', {
                className: 'custom-input-field'
            }, React.createElement( 'label', {
                htmlFor: 'wcb2b_purchaseorder_number' 
            }, window.wp.i18n.__( 'Purchase order number', 'woocommerce-b2b' ) ),
                React.createElement( 'input', {
                    type: 'text',
                    id: 'wcb2b_purchaseorder_number',
                    name: 'wcb2b_purchaseorder_number',
                    required: true,
                    onChange: (e) => { setWcb2bPurchaseorderNumber( e.target.value ) }
                } )
            )
        );
    };
    var WCB2B_PurchaseOrder_Gateway = {
        name: 'wcb2b_purchaseorder_gateway',
        label: label,
        content: Object( window.wp.element.createElement )( Content, null ),
        edit: Object( window.wp.element.createElement )( Content, null ),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
    };
    window.wc.wcBlocksRegistry.registerPaymentMethod( WCB2B_PurchaseOrder_Gateway );
} )();
