define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Customer/js/customer-data',
    'mage/url'
], function ($, Component, placeOrderAction, fullScreenLoader, customerData, url) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Metatooth_SquareCheckout/payment/square-checkout'
        },

        getCode: function () {
            return 'squarecheckout';
        },

        placeOrder: function (data, event) {
            var self = this;

            if (event) {
                event.preventDefault();
            }

            fullScreenLoader.startLoader();
            this.isPlaceOrderActionAllowed(false);

            $.when(
                placeOrderAction(this.getData(), this.messageContainer)
            ).done(function (orderId) {
                customerData.invalidate(['cart']);
                window.location.replace(
                    url.build('squarecheckout/checkout/redirect') + '?order_id=' + orderId
                );
            }).fail(function () {
                self.isPlaceOrderActionAllowed(true);
                fullScreenLoader.stopLoader();
            });

            return true;
        }
    });
});
