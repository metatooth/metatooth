define(
    [
        'uiComponent',
        'Squareup_Omni/js/view/payment/giftcard/square'
    ],
    function (Component, squareGiftCard) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Squareup_Omni/summary/giftcard',
                giftCards: squareGiftCard.giftCards
            },

            checkGiftCards: function () {
                var self = this;
                return self.giftCards.length;
                var giftCards = window.checkoutConfig.payment.squareup.quoteGiftCards;

                if (giftCards.length) {
                    return JSON.parse(giftCards).length;
                }

                return null;
            }
        });
    }
);
