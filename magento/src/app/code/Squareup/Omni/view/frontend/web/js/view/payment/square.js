define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'squareup_payment',
                component: 'Squareup_Omni/js/view/payment/method-renderer/square'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
