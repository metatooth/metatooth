define([
    'jquery',
    'ko',
    'mage/url',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache'
], function ($, ko, url, totals, getPaymentInformation, fullScreenLoader, quote, totalsDefaultProvider, cartCache) {
    'use strict';

    return {
        giftCards: ko.observableArray([]),
        displayForm: ko.observable(false),
        showBalance: ko.observable(false),
        giftCardNonce: "",
        giftCardForm: {},

        applyGiftCard: function () {
            var self = this;

            self.requestCardNonce("apply-giftcard");
        },

        applyGiftCardAction: function (giftCardNonce, giftCardCode) {
            var self = this;
            var sendGiftCard = "**** **** **** " + giftCardCode;
            fullScreenLoader.startLoader();
            if (!giftCardNonce) {
                fullScreenLoader.stopLoader();
                totals.isLoading(false);
                return;
            }

            $.ajax({
                url: url.build("squareupomni/giftcard/apply"),
                data: {
                    card_code: sendGiftCard,
                    card_nonce: giftCardNonce
                },
                type: "POST",
                success: function (response) {
                    cartCache.set('totals',null);
                    //let deferred = $.Deferred();
                    var card_code = response.card_code;

                    window.checkoutConfig.quoteData.grand_total = parseFloat(response.total);
                    if (response.total > 0) {
                        self.displayForm(true);
                    } else {
                        self.displayForm(false);
                    }

                    totals.isLoading(true);
                    //getPaymentInformation(deferred);
                    // $.when(deferred).done(function () {
                    //     fullScreenLoader.stopLoader();
                    //     totals.isLoading(false);
                    // });

                    console.log(response);
                    if (response.duplicate) {
                        alert(card_code + " is already in use");
                    } else {
                        self.giftCards.push({card_code: card_code, amount: response.amount});
                    }

                    cartCache.set('totals',null);
                    totalsDefaultProvider.estimateTotals();
                    fullScreenLoader.stopLoader();
                    totals.isLoading(false);
                },
                fail: function () {
                    fullScreenLoader.stopLoader();
                    totals.isLoading(false);
                },
                error: function () {
                    fullScreenLoader.stopLoader();
                    totals.isLoading(false);
                }
            });
        },

        removeGiftCard: function (giftCard) {
            var self = this;
            //console.log(giftCard);

            fullScreenLoader.startLoader();

            $.ajax({
                url: url.build("squareupomni/giftcard/remove"),
                data: {
                    card_code: giftCard.card_code,
                    card_nonce: "nonce"
                },
                type: "POST",
                success: function (response) {
                    var deferred = $.Deferred();
                    var giftCards = [];

                    self.displayForm(true);
                    self.giftCards.remove(giftCard);

                    if (window.checkoutConfig.payment.squareup.quoteGiftCards.length) {
                        var cards = JSON.parse(window.checkoutConfig.payment.squareup.quoteGiftCards);
                        for (var i = 0; i < cards.length; i++) {
                            if (cards[i].card_code == giftCard.card_code && cards[i].amount == giftCard.amount) {
                                cards.splice(i , 1);
                            }
                        }

                        window.checkoutConfig.payment.squareup.quoteGiftCards = JSON.stringify(cards);
                    }

                    // response.forEach(function (card) {
                    //     giftCards[card.card_code.split(" ").join("_")] = card.amount;
                    // });
                    //
                    // ko.utils.arrayForEach(self.giftCards(), function(giftCard) {
                    //     self.giftCards.replace(giftCard, {
                    //         card_code: giftCard.card_code,
                    //         amount: giftCards[giftCard.card_code.split(" ").join("_")]
                    //     });
                    // });

                    self.giftCards.removeAll();
                    response.forEach(function (card) {
                        self.giftCards.push({card_code: card.card_code, amount: card.amount});
                    });

                    totals.isLoading(true);
                    getPaymentInformation(deferred);

                    $.when(deferred).done(function () {
                        fullScreenLoader.stopLoader();
                        totals.isLoading(false);
                    });
                },
                fail: function () {
                    fullScreenLoader.stopLoader();
                    totals.isLoading(false);
                },
                error: function () {
                    fullScreenLoader.stopLoader();
                    totals.isLoading(false);
                }
            });
        },

        resetGiftCards: function () {
            var self = this;
            fullScreenLoader.startLoader();

            $.ajax({
                url: url.build("squareupomni/giftcard/reset"),
                data: {
                    card_nonce: "nonce"
                },
                type: "POST",
                success: function (response) {
                    cartCache.set('totals',null);
                    totals.isLoading(true);
                    window.checkoutConfig.payment.squareup.quoteGiftCards = "";
                    self.giftCards.removeAll();
                    totalsDefaultProvider.estimateTotals();
                    fullScreenLoader.stopLoader();
                    totals.isLoading(false);
                }
            });
        },

        getGiftCards: function () {
            var self = this;

            if (window.checkoutConfig.payment.squareup.quoteGiftCards.length) {
                var cards = JSON.parse(window.checkoutConfig.payment.squareup.quoteGiftCards);

                cards.forEach(function (card) {
                    self.giftCards.push({card_code: card.card_code, amount: card.amount});
                });
            }

        },

        build: function (elementId, placeholder) {
            var self = this;

            self.giftCardForm = new SqPaymentForm({
                applicationId: window.checkoutConfig.payment.squareup.squareupApplicationId,
                locationId: window.checkoutConfig.payment.squareup.squareupLocationId,
                inputClass: "sq-input",
                giftCard: {
                    elementId: elementId,
                    placeholder: placeholder
                },
                callbacks: {
                    cardNonceResponseReceived: function (errors, nonce, cardData) {
                        console.log(nonce);
                        if (errors) {
                            errors.forEach(function (error) {
                                if (error.type == "VALIDATION_ERROR") {
                                    alert(error.message);
                                }
                            });

                            return;
                        }

                        if (nonce) {
                            document.getElementById('gift-card-nonce').value = nonce;
                        }

                        if (cardData) {
                            document.getElementById('gift-card-code').value = cardData.last_4;
                        }
                    }
                }
            });

            self.giftCardForm.build();
        },

        requestCardNonce: function (action) {
            var self = this;

            self.giftCardForm.requestCardNonce();
            setTimeout(function () {
                var giftCardNonce = document.getElementById('gift-card-nonce').value;
                var giftCardCode = document.getElementById('gift-card-code').value;

                if (action == "check-balance") {
                    return self.checkGiftCardBalanceAction(giftCardNonce);
                } else if (action == "apply-giftcard") {
                    self.applyGiftCardAction(giftCardNonce, giftCardCode);
                }
            }, 1000);
        },

        checkGiftCardBalance: function () {
            var self = this;

            self.requestCardNonce("check-balance");
        },

        checkGiftCardBalanceAction: function (giftCardNonce) {
            var self = this;
            var balance = document.getElementById("square-gc-balance");
            if (giftCardNonce) {
                $.ajax({
                    url: url.build("squareupomni/giftcard/checkBalance"),
                    data: {
                        card_nonce: giftCardNonce
                    },
                    type: "POST",
                    success: function (response) {
                        self.showBalance(true);
                        balance.innerHTML = "Gift Card Balance: " + response.balance;
                    },
                });
            }
        },

        resetTotals: function () {
            cartCache.set('totals',null);
            totalsDefaultProvider.estimateTotals();
        }
    };
});
