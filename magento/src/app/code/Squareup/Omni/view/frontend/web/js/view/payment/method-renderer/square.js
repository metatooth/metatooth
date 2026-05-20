define(
    [
        'jquery',
        'ko',
        'Magento_Payment/js/view/payment/cc-form',
        'mage/url',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Squareup_Omni/js/view/payment/giftcard/square',
        'Magento_Checkout/js/model/quote'
    ],
    function (
        $,
        ko,
        Component,
        url,
        additionalValidators,
        redirectOnSuccessAction,
        squareGiftCard,
        quote
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: {
                    name: 'Squareup_Omni/payment/square'
                },
                SquareupApplicationId: window.checkoutConfig.payment.squareup.squareupApplicationId,
                SquareupLocationId: window.checkoutConfig.payment.squareup.squareupLocationId,
                giftCards: squareGiftCard.giftCards,
                displayForm: squareGiftCard.displayForm,
                showBalance: squareGiftCard.showBalance,
                requestShippingAddress: false,
                requestBillingInfo: false,
                currencyCode: 'USD',
                squareupMerchantName: window.checkoutConfig.payment.squareup.squareupMerchantName,
            },
            verificationToken: null,

            context: function () {
                return this;
            },

            getCode: function () {
                return 'squareup_payment';
            },

            isActive: function () {
                return true;
            },

            getActionUrl: function () {
                return url.build('square/index/saveNonce');
            },

            getHaveSavedCards: function () {
                return window.checkoutConfig.payment.squareup.getHaveSavedCards;
            },

            getIsSaveOnFileEnabled: function () {
                return window.checkoutConfig.payment.squareup.getIsSaveOnFileEnabled;
            },

            displaySaveCcCheckbox: function () {
                return window.checkoutConfig.payment.squareup.displaySaveCcCheckbox;
            },

            getCustomerCards: function () {
                if (typeof(window.checkoutConfig.payment.squareup.customerCards.length) !== "undefined"
                    && typeof(window.checkoutConfig.payment.squareup.customerCards.length) !== "number") {
                    var cards = [];

                    Object.keys(window.checkoutConfig.payment.squareup.customerCards)
                        .forEach(function (key, index) {
                            window.checkoutConfig.payment.squareup.customerCards[key]['card_id'] = key;
                            cards.push(window.checkoutConfig.payment.squareup.customerCards[key]);
                        });
                    window.checkoutConfig.payment.squareup.customerCards = cards;
                    return cards;
                } else if (typeof(window.checkoutConfig.payment.squareup.customerCards.length) === "number") {
                    return window.checkoutConfig.payment.squareup.customerCards;
                } else if (typeof(window.checkoutConfig.payment.squareup.customerCards) === "object") {
                    var cards = [];

                    Object.keys(window.checkoutConfig.payment.squareup.customerCards)
                        .forEach(function (key, index) {
                            window.checkoutConfig.payment.squareup.customerCards[key]['card_id'] = key;

                            cards.push(window.checkoutConfig.payment.squareup.customerCards[key]);
                        });
                    return cards;
                } else {
                    return [];
                }
            },

            getCardInputTitle: function (data) {
                 return data.cardholder_name + " | " + data.card_brand + " | " + data.exp_month + "/" + data.exp_year + " | **** " + data.last_4;
            },

            getCanSaveCards: function () {
                return window.checkoutConfig.payment.squareup.getCanSaveCards;
            },

            onlyCardOnFileEnabled: function () {
                return window.checkoutConfig.payment.squareup.onlyCardOnFileEnabled;
            },

            isGiftCardEnabled: function () {
                return window.checkoutConfig.payment.squareup.isGiftCardEnabled;
            },

            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function (response) {
                                alert(response.responseJSON.message);
                                squareGiftCard.resetGiftCards();
                                if (self.displayForm()) {
                                    if (false === window.squareupCardOnFileUsed) {
                                        document.getElementById('card-nonce').value = "";
                                    }

                                    self.isPlaceOrderActionAllowed(true);
                                }

                            }
                        ).done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    );

                    return true;
                }

                return false;
            },

            initialize: function () {
                this._super();
                window.Squareup = {};

                $(document).ready(function () {
                    // options initialized on ready for afterRender timing
                });
            },

            initPayment: function () {
                var _self = this;
                squareGiftCard.getGiftCards();
                if (parseFloat(window.checkoutConfig.quoteData.grand_total) > 0) {
                    _self.displayForm(true);
                }
                window.squareupCardOnFileUsed = false;
                console.log('Initing payment');

                Square.payments(_self.SquareupApplicationId, _self.SquareupLocationId)
                    .then(function (payments) {
                        window.Squareup.payments = payments;

                        var initPromises = [
                            payments.card().then(function (card) {
                                window.Squareup.card = card;
                                return card.attach('#card-container');
                            })
                        ];

                        if (_self.isGiftCardEnabled()) {
                            initPromises.push(
                                payments.giftCard().then(function (giftCard) {
                                    window.Squareup.giftCard = giftCard;
                                    return giftCard.attach('#sq-gift-card');
                                })
                            );
                        }

                        return Promise.all(initPromises);
                    })
                    .catch(function (err) {
                        console.error('Failed to initialize Square payment form:', err);
                    });

                $(document).ready(function () {
                    if (_self.onlyCardOnFileEnabled() == true && window.checkoutConfig.payment.squareup.getHaveSavedCards) {
                        jQuery('#save_square_cards_empty').val('0');
                        jQuery('#save-square-card').val('0');
                    } else if (_self.onlyCardOnFileEnabled() == true) {
                        jQuery('#save_square_cards_empty').val('1');
                        jQuery('#save-square-card').val('1');
                    }
                    $('body').on('change', 'input[type=radio][name=squareup_cards]', function () {
                        if (this.value == 'other_card') {
                            if (_self.onlyCardOnFileEnabled() == true) {
                                var input = document.createElement("input");
                                input.type = 'hidden';
                                input.name = 'payment[save_square_card]';
                                input.id = 'save_card_on_file_input';
                                input.value = 1;
                                document.getElementById("square_form_fields").appendChild(input);
                            }
                            document.getElementById("square_form_fields").style.display = "block";
                            jQuery('input[name="payment[nonce]"]').val('');
                            window.squareupCardOnFileUsed = false;
                        } else {
                            var element = document.getElementById('save_card_on_file_input');
                            if (typeof(element) != 'undefined' && element != null) {
                                jQuery('input[name="payment[save_square_card]"]').val('');
                            }
                            document.getElementById("square_form_fields").style.display = "none";
                            jQuery('input[name="payment[nonce]"]').val(this.value);
                            window.squareupCardOnFileUsed = true;
                        }
                    });
                });

                if (this.isGiftCardEnabled()) {
                    squareGiftCard.resetTotals();
                }
            },

            requestCardNonce: function (item, event) {
                event.preventDefault();
                var _self = this;
                var existingNonce = document.getElementById('card-nonce');

                if (!_self.displayForm()) {
                    _self.placeOrder();
                    return;
                }

                if (existingNonce.value.length > 0) {
                    _self.placeOrder();
                    return;
                }

                var shippingAddressData = quote.shippingAddress();
                var email = (!quote.guestEmail) ? window.checkoutConfig.customerData.email : quote.guestEmail;

                window.Squareup.card.tokenize()
                    .then(function (result) {
                        _self.messageContainer.clear();
                        if (result.status !== 'OK') {
                            (result.errors || []).forEach(function (error) {
                                _self.messageContainer.addErrorMessage({
                                    message: error.message,
                                    parameters: {}
                                });
                                console.log('  ' + error.message);
                            });
                            return null;
                        }

                        var nonce = result.token;
                        var cardDetails = (result.details && result.details.card) ? result.details.card : {};

                        document.getElementById('card-nonce').value = nonce;
                        document.getElementById('digital-wallet').value = '';
                        document.getElementById('card-brand').value = cardDetails.brand || '';
                        document.getElementById('card-last-4').value = cardDetails.last4 || '';
                        document.getElementById('card-exp-month').value = cardDetails.expMonth || '';
                        document.getElementById('card-exp-year').value = cardDetails.expYear || '';

                        return window.Squareup.payments.verifyBuyer(nonce, {
                            amount: quote.totals().grand_total.toString(),
                            intent: "CHARGE",
                            currencyCode: "USD",
                            billingContact: {
                                familyName: shippingAddressData.firstname,
                                givenName: shippingAddressData.lastname,
                                email: email,
                                country: shippingAddressData.countryId,
                                city: shippingAddressData.city,
                                addressLines: shippingAddressData.street,
                                postalCode: shippingAddressData.postcode,
                                phone: shippingAddressData.telephone
                            }
                        });
                    })
                    .then(function (verificationResult) {
                        if (verificationResult) {
                            _self.verificationToken = verificationResult.token;
                            document.getElementById('buyerVerification-token').value = verificationResult.token;
                            _self.placeOrder();
                        }
                    })
                    .catch(function (err) {
                        console.error('Payment error:', err);
                        _self.messageContainer.addErrorMessage({
                            message: 'An error occurred during payment processing. Please try again.',
                            parameters: {}
                        });
                    });
            },

            getData: function () {
                var data = this._super();
                var self = this;

                var cardNonce = $('#card-nonce').val();
                var ccType = $('#card-brand').val();
                var digitalWallet = $('#digital-wallet').val();
                var ccLast4 = $('#card-last-4').val();
                var ccExpMonth = $('#card-exp-month').val();
                var ccExpYear = $('#card-exp-year').val();
                if (cardNonce) {
                    data.additional_data.nonce = cardNonce;
                    data.additional_data.cc_type = ccType;
                    data.additional_data.digital_wallet = digitalWallet;
                    data.additional_data.cc_last_4 = ccLast4;
                    data.additional_data.cc_exp_month = ccExpMonth;
                    data.additional_data.cc_exp_year = ccExpYear;
                }

                data.additional_data.buyerVerificationToken = self.verificationToken;
                data.additional_data.display_form = self.displayForm();

                var saveCard = $('#save-square-card').is(':checked');
                if (saveCard === true) {
                    data.additional_data.save_square_card = 1;
                }

                var saveCardHasValue = $('#save-square-card').val();
                if (saveCardHasValue == 1) {
                    data.additional_data.save_square_card = 1;
                }

                return data;
            },

            checkGiftCardBalance: function () {
                squareGiftCard.checkGiftCardBalance();
            },

            applyGiftCard: function () {
                squareGiftCard.applyGiftCard();
            },

            removeGiftCard: function (giftCard) {
                squareGiftCard.removeGiftCard(giftCard);
            },
        });
    }
);
