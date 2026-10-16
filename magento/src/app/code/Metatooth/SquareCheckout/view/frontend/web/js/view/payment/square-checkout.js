define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'squarecheckout',
        component: 'Metatooth_SquareCheckout/js/view/payment/method-renderer/square-checkout'
    });

    return Component.extend({});
});
